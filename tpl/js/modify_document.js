function moveDocument(document_srl,type,no){
	var list_order = 0;
	var change_order = 0;
	if(type=="up"){
	list_order = parseInt(no)+1;
	change_order = parseInt(jQuery("#"+list_order+"_order").attr("list_order"))-1;
	}else{
	list_order = parseInt(no)-1;
	change_order = parseInt(jQuery("#"+list_order+"_order").attr("list_order"))+1;
	}
	//return alert(change_order);
	var params = new Array();
	params["document_srl"] = document_srl;
	params["change_order"] = change_order;
	exec_xml("board_extend","procBoard_extendAdminWithOrder",params,function(){ location.reload(); });
}

function applyModify(document_srl){
	var params = new Array();
	params["document_srl"] = document_srl;
	params["readed_count"] = jQuery(".readed_count_"+document_srl).val();
	params["voted_count"] = jQuery(".voted_count_"+document_srl).val();
	params["regdate"] = jQuery(".regdate_"+document_srl).val();
	params["last_update"] = jQuery(".last_update_"+document_srl).val();
	params["list_order"] = jQuery(".list_order_"+document_srl).val();
	exec_xml("board_extend","procBoard_extendAdminBoardModify",params,function(ret_obj){ alert(ret_obj['message']); });
}