var _dz={},
	_dzName='icon',
	_dzParamName = 'file',
	_dzUrlAction = '../ajax.php?cmd=dz_upload_file',
	_dzReceivedFile = '';
	_dzAcceptedFiles = typeof _dzAcceptedFilesPage!='undefined' ? _dzAcceptedFilesPage : 'image/*';

function _dzShowError(file,msg,$cont,name){
	if (file.previewElement) {
		var $preview=$(file.previewElement);
        $preview.find('.dz-error-message > span').attr('title', msg).text(msg);
        $preview.addClass('dz-error');
	}
	_dzResetDataUrl($cont,name);
	_dzDisabledFrmSubmitBtn();
}

function _dzDisabledFrmSubmitBtn(dis){
	dis=dis||false;
	$jq('.btn_submit, .btn_disabled').prop('disabled', dis);
}

function _dzSetDataUrl(data, $cont){
	$cont.find('.image-data-url').val(data);
}

function _dzResetDataUrl($cont, name){
	$cont.find('.image-data-url').val('');
	_dzReaderDeleteCheck(name);
}

function _dzSetReaderOnLoad(name, fn){
	_dz[name]['readerOnLoad'] = fn;
}

function _dzReaderOnLoadCheck(name){
	if (typeof _dz[name]['readerOnLoad'] === 'function') {
		_dz[name]['readerOnLoad']();
	}
}

function _dzSetReaderDelete(name, fn){
	_dz[name]['readerOnDelete'] = fn;
}

function _dzReaderDeleteCheck(name){
	if (typeof _dz[name]['readerOnDelete'] === 'function') {
		_dz[name]['readerOnDelete']();
	}
}

function _dzReset(name){
	_dz[name].removeAllFiles();
}

Dropzone.options.dpzUploadSingleIcon = {
	paramName: _dzParamName,
	name: _dzName,
	url: _dzUrlAction,
	dictDefaultMessage: l('drop_upload_file'),
	dictFallbackMessage: l('your_browser_does_not_support_dragndrop_file_uploads'),
	dictInvalidFileType: 'accept_file_types',
	acceptedFiles: _dzAcceptedFiles,
	ignoreHiddenFiles: true,
	timeout: 3600000,
	dictRemoveFileConfirmation: false,
	dictCancelUploadConfirmation: '',
	dictRemoveFile: '',
	dictCancelUpload: '',
	thumbnailWidth: _dzThumbnailWidth || 195,
	thumbnailHeight: _dzThumbnailHeight || 70,
	maxFiles: 1,
	addRemoveLinks: true,
    init: function() {
		var $cont=$(this.previewsContainer),
			name=this.options.name;
		if ($cont[0] && $cont.data('name')) {
			name=$cont.data('name');
		}
		_dz[name] = this;

        this.on("maxfilesexceeded", function(file) {
            this.removeAllFiles();
            this.addFile(file);
        })
		this.on("complete", function(file) {
			var $cont=$(this.previewsContainer);
            if (file.previewElement && file.status=='success'){
				var $preview=$(file.previewElement);
				try {
					var res=file.xhr.response;
					if (res=='ok') {
						var reader = new FileReader();
						reader.readAsDataURL(file);
						reader.onload = function(event) {
							_dzReceivedFile = event.target.result;
							_dzSetDataUrl(_dzReceivedFile,$cont);
							_dzDisabledFrmSubmitBtn();
							_dzReaderOnLoadCheck(name);
						};
						reader.onerror = function(event) {
							reader.abort();
							_dzShowError(file,l('photo_file_upload_failed'),$cont, name);
						}
						console.log('COMPLETE UPLOAD FILE', file);
					} else {
						console.log('COMPLETE ERROR NO OBJECT', file);
						_dzShowError(file,l('photo_file_upload_failed'),$cont, name);
					}
				} catch(e){
					console.log('COMPLETE ERROR TRY', e, file);
					_dzShowError(file,l('photo_file_upload_failed'),$cont, name);
				}
			} else {
				console.log('COMPLETE NO PREVIEW', file);
				_dzShowError(file,l('photo_file_upload_failed'),$cont,name);
			}
        })

		this.on("sending", function(file, xhr, formData){

		})

		this.on("addedfile", function(file) {
			_dzDisabledFrmSubmitBtn(true);
		})

		this.on("removedfile", function(file) {
			_dzResetDataUrl($(this.previewsContainer), name);
		})

		this.on("error", function(file,errorMessage,xhr) {
			if(file.status!='canceled'){
				console.log('ERROR', [file, errorMessage, xhr]);
				_dzShowError(file,errorMessage,$(this.previewsContainer),name)
			}
		})

    }
	/*dictFileTooBig: 'max_file_size',
		maxFilesize: $this['max'+type+'Size'],
		//maxFilesize:20, - how many files are processed by Dropzone(dz-max-files-reached)
		//capture
		parallelUploads:6,
		createImageThumbnails:false,
		previewTemplate:previewTemplate,
		clickable:'.'+type+'_upload_zone'*/
}