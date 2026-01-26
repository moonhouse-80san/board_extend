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

function updateDownloadCount(file_srl) {
	var input = document.getElementById('download_' + file_srl);
	var download_count = input.value;
	
	if(!download_count || download_count < 0) {
		alert('올바른 숫자를 입력해주세요.');
		return false;
	}

	if(!confirm('다운로드 수를 ' + download_count + '회로 변경하시겠습니까?')) {
		return false;
	}

	var params = {
		file_srl: file_srl,
		download_count: download_count
	};

	exec_xml('board_extend', 'procBoard_extendAdminFileDownload', params, function(ret_obj) {
		alert(ret_obj.message);
		if(ret_obj.error == '0' || ret_obj.error == 0) {
			location.reload();
		}
	});
	return false;
}

// 전체 선택/해제
function toggleAllExtensions(checkbox, type) {
	var targetClass = type === 'media' ? '.media-checkbox' : '.ext-checkbox';
	var checkboxes = document.querySelectorAll(targetClass);
	for(var i = 0; i < checkboxes.length; i++) {
		checkboxes[i].checked = checkbox.checked;
	}
}

// 전체 선택 상태 업데이트
function updateSelectAllState(type) {
    // type 인자가 없을 경우를 대비한 기본값 처리
    if(!type) type = 'allowed'; 
	var selectAllId = type === 'media' ? 'selectAllMedia' : 'selectAllAllowed';
	var targetClass = type === 'media' ? '.media-checkbox' : '.ext-checkbox';
	
	var checkboxes = document.querySelectorAll(targetClass);
    if(checkboxes.length === 0) return;

	var allChecked = true;
	for(var i = 0; i < checkboxes.length; i++) {
		if(!checkboxes[i].checked) {
			allChecked = false;
			break;
		}
	}
	var selectAllBtn = document.getElementById(selectAllId);
	if(selectAllBtn) selectAllBtn.checked = allChecked;
}

// 커스텀 확장자 추가
function addCustomExtension(type) {
	var input = document.getElementById('customExtension');
	var extensionList = document.getElementById('extensionList');
	if(!input || !extensionList) return false;
	
	var inputValue = input.value.trim();
	if(!inputValue) {
		alert('추가할 확장자를 입력해주세요.');
		return false;
	}
	
	var extensions = inputValue.split(',');
	var addedCount = 0;
	
	for(var i = 0; i < extensions.length; i++) {
		var ext = extensions[i].trim().toLowerCase().replace(/^\./g, '');
		if(!ext || !/^[a-z0-9]+$/.test(ext)) continue;
		
		// 중복 체크
		var isDuplicate = false;
		var existing = document.querySelectorAll('.ext-checkbox');
		for(var j=0; j<existing.length; j++) {
			if(existing[j].value === ext) { isDuplicate = true; break; }
		}
		if(isDuplicate) continue;
		
		var label = document.createElement('label');
		label.style.cursor = 'pointer';
		label.innerHTML = '<input type="checkbox" class="ext-checkbox" name="allowed_extensions[]" value="'+ext+'" checked onclick="updateSelectAllState(\'allowed\')"> .' + ext;
		extensionList.appendChild(label);
		addedCount++;
	}
	
	if(addedCount > 0) {
		input.value = '';
		updateSelectAllState('allowed');
	}
	return false;
}

// 미디어 파일 필터 토글
function toggleMediaFilter(checkbox) {
    var mediaSection = document.getElementById('mediaExtensionSection');
    var mediaDesc = document.getElementById('excludeMediaDesc');
    
    if(checkbox.checked) {
        // 체크됨: 미디어 섹션 숨기기
        if(mediaSection) mediaSection.style.display = 'none';
        if(mediaDesc) mediaDesc.style.display = 'block';
    } else {
        // 체크 해제됨: 미디어 섹션 보이기
        if(mediaSection) mediaSection.style.display = 'block';
        if(mediaDesc) mediaDesc.style.display = 'none';
    }
    
    // URL 업데이트 (페이지 새로고침 없이 히스토리만 변경)
    var isChecked = checkbox.checked ? 'Y' : 'N';
    var currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('exclude_media', isChecked);
    history.replaceState(null, '', currentUrl.toString());
}

// 페이지 로드 시 초기화
window.addEventListener('load', function() {
    var excludeMediaCheckbox = document.getElementById('excludeMedia');
    if(excludeMediaCheckbox) {
        toggleMediaFilter(excludeMediaCheckbox);
    }
    
    // 다른 초기화 함수들
    updateSelectAllState('allowed');
    updateSelectAllState('media');
});