<?php
	/**
	 * @class  board_extendAdminView
	 * @author xiso (xiso@xiso.co.kr)
	 * @brief  board_extend module admin view class
	 **/
	require_once(_XE_PATH_.'modules/board/board.admin.view.php');
	class board_extendAdminView extends boardAdminView {

		/**
		 * @brief initialization
		 *
		 * board_extend module can be divided into general use and admin use.\n
		 **/
		function init() {
			Context::loadLang(_XE_PATH_.'modules/board/lang');
			// module_srl이 있으면 미리 체크하여 존재하는 모듈이면 module_info 세팅
			$module_srl = Context::get('module_srl');
			if(!$module_srl && $this->module_srl) {
				$module_srl = $this->module_srl;
				Context::set('module_srl', $module_srl);
			}

			// module model 객체 생성 
			$oModuleModel = &getModel('module');

			// module_srl이 넘어오면 해당 모듈의 정보를 미리 구해 놓음
			if($module_srl) {
				$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
				if(!$module_info) {
					Context::set('module_srl','');
					$this->act = 'list';
				} else {
					ModuleModel::syncModuleToSite($module_info);
					$this->module_info = $module_info;
					Context::set('module_info',$module_info);
				}
			}

			if($module_info && $module_info->module != 'board') return $this->stop("msg_invalid_request");

			// 모듈 카테고리 목록을 구함
			$module_category = $oModuleModel->getModuleCategories();
			Context::set('module_category', $module_category);

			// 템플릿 경로 지정 (board의 경우 tpl에 관리자용 템플릿 모아놓음)
			$template_path = sprintf("%stpl/",$this->module_path);
			$this->setTemplatePath($template_path);

			// 정렬 옵션을 세팅
			foreach($this->order_target as $key) $order_target[$key] = Context::getLang($key);
			$order_target['list_order'] = Context::getLang('document_srl');
			$order_target['update_order'] = Context::getLang('last_update');
			Context::set('order_target', $order_target);
		}

		function dispBoard_extendAdminBoardList(){
			//게시판 리스트는 기존board와 똑같이받아옴
			$this->dispBoardAdminContent();
		}

		function dispBoard_extendAdminBoardModify(){
			if(!in_array($this->module_info->module, array('admin', 'board','blog','guestbook'))) {
				return $this->alertMessage('msg_invalid_request');
			}

			$oDocumentModel = &getModel('document');
			$oFileModel = &getModel('file');

			$args = new stdClass();
			$args->module_srl = $this->module_info->module_srl; 
			$args->page = Context::get('page');
			$args->list_count = Context::get('list_count') ? Context::get('list_count') : 20; 
			$args->page_count = $this->page_count; 

			$args->search_target = Context::get('search_target'); 
			$args->search_keyword = Context::get('search_keyword'); 

			if($this->module_info->use_category=='Y') {
				$args->category_srl = Context::get('category'); 
			}

			$args->sort_index = Context::get('sort_index');
			$args->order_type = Context::get('order_type');

			if(!in_array($args->sort_index, $this->order_target)) {
				$args->sort_index = $this->module_info->order_target ?: 'list_order';
			}

			if(!in_array($args->order_type, array('asc','desc'))) {
				$args->order_type = $this->module_info->order_type ?: 'asc';
			}

			if(!$args->page && (Context::get('document_srl') || Context::get('entry'))) {
				$oDocument = $oDocumentModel->getDocument(Context::get('document_srl'));
				if($oDocument->isExists() && !$oDocument->isNotice()) {
					$page = $oDocumentModel->getDocumentPage($oDocument, $args);
					Context::set('page', $page);
					$args->page = $page;
				}
			}

			if($args->category_srl || $args->search_keyword) {
				$args->list_count = $this->search_list_count;
			}

			Context::set('list_config', $this->listConfig);

			$output = $oDocumentModel->getDocumentList(
				$args,
				$this->except_notice,
				true,
				$this->columnList
			);

			/* ===============================
			 * 허용 확장자 및 제외 미디어 확장자 설정 불러오기 (v7/v8 호환)
			 * =============================== */
			$allowed_extensions = '';
			$excluded_media_extensions = '';

			// Rhymix v8: module_config 테이블에서 가져오기
			if(class_exists('Rhymix\\Framework\\Config')) {
				try {
					$db = \Rhymix\Framework\DB::getInstance();
					$query = "SELECT config FROM module_config WHERE module = ?";
					$stmt = $db->query($query, [$this->module_info->mid]);

					if($stmt) {
						$row = $stmt->fetch(\PDO::FETCH_OBJ);
						if($row && isset($row->config) && $row->config) {
							$config = @unserialize($row->config);
							if(is_object($config)) {
								if(isset($config->allowed_extensions)) {
									$allowed_extensions = $config->allowed_extensions;
								}
								if(isset($config->excluded_media_extensions)) {
									$excluded_media_extensions = $config->excluded_media_extensions;
								}
							}
						}
						$stmt->closeCursor();
					}
				} catch(Exception $e) {
					// 오류 무시
				}
			}
			// XE/Rhymix v7: extra_vars에서 가져오기
			else {
				if(isset($this->module_info->allowed_extensions)) {
					$allowed_extensions = $this->module_info->allowed_extensions;
				}
				if(isset($this->module_info->excluded_media_extensions)) {
					$excluded_media_extensions = $this->module_info->excluded_media_extensions;
				}
			}

			Context::set('allowed_extensions', $allowed_extensions);
			Context::set('excluded_media_extensions', $excluded_media_extensions);

			/* ===============================
			 * 파일 필터링
			 * =============================== */
			if($output->data && is_array($output->data)) {

				// 전체 미디어 파일 확장자 목록
				$all_media_extensions = array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico', 'tiff', 'tif',
											  'mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'webm', 'mpeg', 'mpg', 'm4v', '3gp',
											  'mp3', 'wav', 'ogg', 'flac', 'aac', 'm4a', 'wma', 'ape', 'alac');

				// 허용 확장자 배열로 변환
				$allowed_ext_array = array();
				$has_allowed_filter = false;
				if(!empty($allowed_extensions)) {
					$allowed_ext_array = array_map('trim', explode(',', $allowed_extensions));
					$allowed_ext_array = array_filter($allowed_ext_array, function($val) {
						return !empty($val);
					});
					$has_allowed_filter = !empty($allowed_ext_array);
				}

				// 제외 미디어 확장자 배열로 변환
				$excluded_media_array = array();
				if(!empty($excluded_media_extensions)) {
					$excluded_media_array = array_map('trim', explode(',', $excluded_media_extensions));
					$excluded_media_array = array_filter($excluded_media_array, function($val) {
						return !empty($val);
					});
				}

				// 미디어 파일 제외 여부
				$exclude_media = Context::get('exclude_media');
				
				// exclude_media가 URL에 없으면 기본값 Y (미디어 전체 제외)
				if($exclude_media === null || $exclude_media === '') {
					$exclude_media = 'Y';
				}
				
				Context::set('exclude_media', $exclude_media);

				foreach($output->data as $key => $document) {
					$file_args = new stdClass();
					$file_args->upload_target_srl = $document->document_srl;
					$file_args->isvalid = 'Y';

					$file_output = executeQueryArray('file.getFiles', $file_args);

					if($file_output->data && is_array($file_output->data)) {
						$filtered_files = array();

						foreach($file_output->data as $file) {
							$ext = strtolower(pathinfo($file->source_filename, PATHINFO_EXTENSION));

							// 1. 미디어 파일 제외 옵션이 활성화되어 있으면 모든 미디어 제외
							if($exclude_media === 'Y') {
								if(in_array($ext, $all_media_extensions)) {
									continue;
								}
							}
							// 2. 미디어 파일 제외 옵션이 비활성화되어 있으면 개별 제외 목록 확인
							else {
								if(in_array($ext, $excluded_media_array)) {
									continue;
								}
							}

							// 3. 허용 확장자 필터가 설정되어 있으면 체크
							if($has_allowed_filter) {
								if(!in_array($ext, $allowed_ext_array)) {
									continue;
								}
							}

							$filtered_files[] = $file;
						}

						/* ===============================
						 * 최신순 정렬 (regdate DESC)
						 * =============================== */
						usort($filtered_files, function($a, $b) {
							return $b->regdate <=> $a->regdate;
						});

						$output->data[$key]->uploaded_files = $filtered_files;
					} else {
						$output->data[$key]->uploaded_files = array();
					}
				}
			}

			Context::set('document_list', $output->data);
			Context::set('list_count', $args->list_count);
			Context::set('total_count', $output->total_count);
			Context::set('total_page', $output->total_page);
			Context::set('page', $output->page);
			Context::set('page_navigation', $output->page_navigation);

			$this->setTemplateFile('contentlist');
		}

	}
?>