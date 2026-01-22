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
}
?>