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
			 * 파일 필터링 (추천 방식)
			 * =============================== */
			if($output->data && is_array($output->data)) {

				// 제외할 확장자
				$image_exts = array('jpg','jpeg','png','gif','bmp','webp','svg');
				$audio_exts = array('mp3','wav','ogg','aac','flac','m4a','wma');

				foreach($output->data as $key => $document) {
					$file_args = new stdClass();
					$file_args->upload_target_srl = $document->document_srl;
					$file_args->isvalid = 'Y';

					$file_output = executeQueryArray('file.getFiles', $file_args);

					if($file_output->data && is_array($file_output->data)) {
						$filtered_files = array();

						foreach($file_output->data as $file) {
							$ext = strtolower(pathinfo($file->source_filename, PATHINFO_EXTENSION));

							// image / audio 타입 제외 + 확장자 재확인
							if(
								!in_array($file->file_type, array('image','audio')) &&
								!in_array($ext, $image_exts) &&
								!in_array($ext, $audio_exts)
							) {
								$filtered_files[] = $file;
							}
						}

						/* ===============================
						 * 최신순 정렬 (regdate DESC) - 이 부분 지우면 오래된 순 정렬
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