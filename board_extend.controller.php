<?php
    /**
     * @class  board_extendAdminController
     * @author xiso (xiso@xiso.co.kr)
     * @brief  board_extend module admin controller class
     **/
	require_once(_XE_PATH_.'modules/board/board.controller.php');
    class board_extendController extends boardController {
		
		function triggerDisplay(){
			//trigger display before();
			if(strpos(Context::get('act'),"ispBoardAdmin") || strpos(Context::get('act'),"ispBoard_extendAdmin")){
				$core_ver = __XE_VERSION__ ? __XE_VERSION__ : __ZBXE_VERSION__;
				$is_active = (Context::get('act') == 'dispBoard_extendAdminBoardModify') ? true : false;
				$url = getNotEncodedUrl('act','dispBoard_extendAdminBoardModify', 'selected_var_idx', '', 'type', '');
				$text = "목록수정";
				if($core_ver >= 1.7){
					$active = $is_active ? "class='x_active'" : "";
					$html = sprintf("<li %s><a href='%s'>%s</a></li>",$active,$url,$text);
					$script = "jQuery(document).ready(function(\$){\$('.x_nav').append(\"$html\");});";
				}else if($core_ver >= 1.5){
					$active = $is_active ? "class='active'" : "";
					$html = sprintf("<li %s><a href='%s'>%s</a></li>",$active,$url,$text);
					$script = "jQuery(document).ready(function(\$){\$('.x .cnb ul').append(\"$html\");});";
				}else if($core_ver >= 1.4){
					$active = $is_active ? "class='on'" : "";
					$html = sprintf("<li %s><a href='%s'>%s</a></li>",$active,$url,$text);
					$script = "jQuery(document).ready(function(\$){\$('#xeAdmin .header4 .localNavigation').append(\"$html\");});";
				}else{
					$this->stop('지원하지 않는 버전입니다. board_extend 모듈을 제거해주시기 바랍니다.');
				}
				Context::addHtmlHeader("<script type='text/javascript'>$script</script>");
			}
		}
		
	}
	
?>