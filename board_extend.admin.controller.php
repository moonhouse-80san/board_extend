<?php
	/**
	 * @class  board_extendAdminController
	 * @author xiso (xiso@xiso.co.kr)
	 * @brief  board_extend module admin controller class
	 **/
	require_once(_XE_PATH_.'modules/board/board.admin.controller.php');

	class board_extendAdminController extends boardAdminController {

		function procBoard_extendAdminWithOrder(){
			$order_value = Context::get('change_order');
			$args = new stdClass();
			$args->change_order = $order_value;
			$args->document_srl = Context::get('document_srl');
			$output = executeQuery('board_extend.updateListOrder', $args);
		}

		function procBoard_extendAdminBoardModify(){
			$args = new stdClass();
			$args->document_srl = Context::get('document_srl');
			$args->readed_count = Context::get('readed_count');
			$args->voted_count = Context::get('voted_count');
			$args->regdate = Context::get('regdate');
			$args->last_update = Context::get('last_update');
			$args->list_order = Context::get('list_order');
			$output = executeQuery("board_extend.updateDocument", $args);
			if(!$output->toBool()) return new BaseObject(-1, "반영에 실패하였습니다.");
			return $this->setMessage("반영되었습니다.");
		}

		function procBoard_extendAdminFileDownload(){
			$file_srl = Context::get('file_srl');
			$download_count = Context::get('download_count');
			
			if(!$file_srl) {
				$this->add('message', '파일 번호가 없습니다.');
				$this->setError(-1);
				return;
			}

			if(!is_numeric($download_count) || $download_count < 0) {
				$this->add('message', '올바른 다운로드 수를 입력해주세요.');
				$this->setError(-1);
				return;
			}

			// 직접 DB 업데이트
			$oDB = DB::getInstance();
			$query = "UPDATE xe_files SET download_count = " . (int)$download_count . " WHERE file_srl = " . (int)$file_srl;
			$result = $oDB->_query($query);
			
			if($result) {
				$this->add('message', '다운로드 수가 ' . $download_count . '회로 변경되었습니다.');
				$this->setError(0);
			} else {
				$this->add('message', '업데이트에 실패했습니다.');
				$this->setError(-1);
			}
		}

		function procBoard_extendAdminSaveExtensions(){
			$module_srl = Context::get('module_srl');
			$allowed_extensions = Context::get('allowed_extensions');

			if(!$module_srl) {
				return $this->makeObject(-1, '모듈을 선택해주세요.');
			}

			// 배열을 쉼표로 구분된 문자열로 변환
			$extension_string = '';
			if(is_array($allowed_extensions) && count($allowed_extensions) > 0) {
				$extension_string = implode(',', $allowed_extensions);
			}

			$success = false;
			$error_msg = '';
			$debug_info = '';

			// Rhymix v8 방식: module_config 테이블 사용
			if(class_exists('Rhymix\\Framework\\Config')) {
				try {
					$db = \Rhymix\Framework\DB::getInstance();

					// ModuleModel로 mid 가져오기
					$oModuleModel = getModel('module');
					$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);

					if(!$module_info) {
						return $this->makeObject(-1, '모듈 정보를 찾을 수 없습니다.');
					}

					$mid = $module_info->mid;

					// 기존 config 가져오기
					$query = "SELECT config FROM module_config WHERE module = ?";
					$stmt = $db->query($query, [$mid]);

					$config = new stdClass();
					if($stmt) {
						$row = $stmt->fetch(\PDO::FETCH_OBJ);
						if($row && isset($row->config) && $row->config) {
							$config = @unserialize($row->config);
							if(!is_object($config)) {
								$config = new stdClass();
							}
						}
						$stmt->closeCursor();
					}

					// allowed_extensions 설정
					$config->allowed_extensions = $extension_string;
					$serialized = serialize($config);

					// INSERT or UPDATE (regdate 제외)
					$insert_query = "INSERT INTO module_config (module, config) VALUES (?, ?) 
									 ON DUPLICATE KEY UPDATE config = ?";
					$result = $db->query($insert_query, [$mid, $serialized, $serialized]);

					if($result !== false) {
						$success = true;

						// 저장 후 바로 확인
						$verify_stmt = $db->query($query, [$mid]);
						if($verify_stmt) {
							$verify_row = $verify_stmt->fetch(\PDO::FETCH_OBJ);
							if($verify_row && isset($verify_row->config)) {
								$verify_config = @unserialize($verify_row->config);
								$debug_info = ' [검증: ' . (isset($verify_config->allowed_extensions) ? $verify_config->allowed_extensions : '없음') . ']';
							}
							$verify_stmt->closeCursor();
						}
					} else {
						$error_msg = 'v8 쿼리 실행 실패';
					}
				} catch(Exception $e) {
					$error_msg = 'v8 예외: ' . $e->getMessage();
				}
			}
			// XE/Rhymix v7 방식: extra_vars 사용
			else {
				try {
					$oModuleModel = getModel('module');
					$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
					
					if(!$module_info) {
						return $this->makeObject(-1, '모듈 정보를 찾을 수 없습니다.');
					}

					$extra_vars = new stdClass();
					
					if($module_info->extra_vars && is_object($module_info->extra_vars)) {
						foreach($module_info->extra_vars as $key => $val) {
							$extra_vars->{$key} = $val;
						}
					}

					$extra_vars->allowed_extensions = $extension_string;
					$serialized = addslashes(serialize($extra_vars));

					$oDB = DB::getInstance();
					$query = sprintf(
						"UPDATE %smodules SET extra_vars = '%s' WHERE module_srl = %d",
						DB_TABLE_PREFIX,
						$serialized,
						(int)$module_srl
					);

					$result = $oDB->query($query);

					if($result !== false) {
						$success = true;

						$verify_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
						if($verify_info && isset($verify_info->allowed_extensions)) {
							$debug_info = ' [검증: ' . $verify_info->allowed_extensions . ']';
						}
					} else {
						if(method_exists($oDB, 'error')) {
							$error_msg = 'v7 쿼리 오류: ' . $oDB->error();
						} else {
							$error_msg = 'v7 쿼리 실행 실패';
						}
					}
				} catch(Exception $e) {
					$error_msg = 'v7 예외: ' . $e->getMessage();
				}
			}

			if(!$success) {
				return $this->makeObject(-1, 'DB 업데이트에 실패했습니다. ' . $error_msg);
			}

			$message = '확장자 설정이 저장되었습니다.';
			if($extension_string) {
				$message .= ' (저장된 확장자: ' . $extension_string . ')' . $debug_info;
			} else {
				$message .= ' (모든 확장자 표시)' . $debug_info;
			}

			return $this->makeObject(0, $message);
		}

		// 미디어 파일 제외 확장자 저장
		function procBoard_extendAdminSaveMediaExtensions(){
			$module_srl = Context::get('module_srl');
			$excluded_media_extensions = Context::get('excluded_media_extensions');

			if(!$module_srl) {
				return $this->makeObject(-1, '모듈을 선택해주세요.');
			}

			// 배열을 쉼표로 구분된 문자열로 변환
			$extension_string = '';
			if(is_array($excluded_media_extensions) && count($excluded_media_extensions) > 0) {
				$extension_string = implode(',', $excluded_media_extensions);
			}

			$success = false;
			$error_msg = '';

			// Rhymix v8 방식
			if(class_exists('Rhymix\\Framework\\Config')) {
				try {
					$db = \Rhymix\Framework\DB::getInstance();
					$oModuleModel = getModel('module');
					$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);

					if(!$module_info) {
						return $this->makeObject(-1, '모듈 정보를 찾을 수 없습니다.');
					}

					$mid = $module_info->mid;
					$query = "SELECT config FROM module_config WHERE module = ?";
					$stmt = $db->query($query, [$mid]);

					$config = new stdClass();
					if($stmt) {
						$row = $stmt->fetch(\PDO::FETCH_OBJ);
						if($row && isset($row->config) && $row->config) {
							$config = @unserialize($row->config);
							if(!is_object($config)) {
								$config = new stdClass();
							}
						}
						$stmt->closeCursor();
					}

					$config->excluded_media_extensions = $extension_string;
					$serialized = serialize($config);

					$insert_query = "INSERT INTO module_config (module, config) VALUES (?, ?) 
									 ON DUPLICATE KEY UPDATE config = ?";
					$result = $db->query($insert_query, [$mid, $serialized, $serialized]);

					if($result !== false) {
						$success = true;
					} else {
						$error_msg = 'v8 쿼리 실행 실패';
					}
				} catch(Exception $e) {
					$error_msg = 'v8 예외: ' . $e->getMessage();
				}
			}
			// XE/Rhymix v7 방식
			else {
				try {
					$oModuleModel = getModel('module');
					$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
					
					if(!$module_info) {
						return $this->makeObject(-1, '모듈 정보를 찾을 수 없습니다.');
					}

					$extra_vars = new stdClass();
					
					if($module_info->extra_vars && is_object($module_info->extra_vars)) {
						foreach($module_info->extra_vars as $key => $val) {
							$extra_vars->{$key} = $val;
						}
					}

					$extra_vars->excluded_media_extensions = $extension_string;
					$serialized = addslashes(serialize($extra_vars));

					$oDB = DB::getInstance();
					$query = sprintf(
						"UPDATE %smodules SET extra_vars = '%s' WHERE module_srl = %d",
						DB_TABLE_PREFIX,
						$serialized,
						(int)$module_srl
					);

					$result = $oDB->query($query);

					if($result !== false) {
						$success = true;
					} else {
						if(method_exists($oDB, 'error')) {
							$error_msg = 'v7 쿼리 오류: ' . $oDB->error();
						} else {
							$error_msg = 'v7 쿼리 실행 실패';
						}
					}
				} catch(Exception $e) {
					$error_msg = 'v7 예외: ' . $e->getMessage();
				}
			}

			if(!$success) {
				return $this->makeObject(-1, 'DB 업데이트에 실패했습니다. ' . $error_msg);
			}

			$message = '미디어 파일 설정이 저장되었습니다.';
			if($extension_string) {
				$message .= ' (제외된 확장자: ' . $extension_string . ')';
			} else {
				$message .= ' (모든 미디어 파일 표시)';
			}

			return $this->makeObject(0, $message);
		}

		function makeObject($error, $message) {
			$this->add('message', $message);
			$this->setError($error);
			$this->setMessage($message);
			return new BaseObject($error, $message);
		}
	}
?>