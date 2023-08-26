var CProfilePhoto = function(uid) {

    var $this=this;
    this.dur = 400;
    this.durfast = 200;
    this.durpopup = 300;

    this.uid = uid;
    this.guid = 0;
    this.fuid = 0;
    this.notLockedUser = 1;
    //this.ajaxLoginStatus = 0;

    this.pid = 0;
    this.cid = 0;

    this.type = '';

    this.confirm = '';
    this.maxFileSize;

    this.langParts = {};
    this.counter = {'public':0,'private':0,'video':0};
    this.counterUpload = {'public':0,'private':0,'video':0};
    this.uploadFileData = {'public':{},'private':{},'video':{}};
    this.idGenerator = 0;
    this.photoInfo = {};
    this.uploadPhotoDesc = [];
    this.requestAjaxRate = {};
    this.requestAjaxRateDelete = {};

    this.galleryPhotosInfo = {};
    this.galleryCurrenPhotoId;
    this.startVideoParams = {};
    this.autoPlayVideo = 'autoplay';


    this.url_main;
    this.url_main_images;
    this.url_files;
    this.url_ajax;

    this.photoWidthMain;

    this.pp = [];

    this.boxEditDesc;
    this.linkEditDesc;
    this.inputEditDesc;

    this.countFilesForUpload=0;
    this.countFilesUploaded=0;

    this.type='';

    this.isVideo=false;
    this.videoPlayer;
    this.currentFormatVideo='';

	//this.isShowGalleryProcess = false;

    this.setData = function(data){
        for (var key in data) {
           $this[key] = data[key];
           //console.log(key, $this[key]);
        }
    }

    this.isHotOrNot=false;
    this.init = function(isHotOrNot) {
        $this.isHotOrNot=isHotOrNot||false;
        if($this.isHotOrNot)return;
        //stylizeMainPhoto();
        if($this.guid!=$this.uid)return;
        $this.pp['public'] = $('#pp_add_photo_public');
        $this.pp['private'] = $('#pp_add_photo_private');
        $this.pp['video'] = $('#pp_add_photo_video');

        $this.pp['public'].modalPopup();
        $this.pp['private'].modalPopup();
        $this.pp['video'].modalPopup();

        $this.initFileUpload('private');
        $this.initFileUpload('public');
        $this.initFileUpload('video');

        $this.setEventField('private');
        $this.setEventField('public');
        $this.setEventField('video');

        if(!(/iPad/i.test(navigator.userAgent))){
            $('#add_photo_profile').hover(
                function(){$(this).addClass('hover_bg')},
                function(){$(this).removeClass('hover_bg')}
            ).click(function(){
                $(this).removeClass('hover_bg');
            })
        }
    }

    this.isUploadPhotoEnabled = function()
    {
        if(!isSuperPowers && !isFreeSite){
            var currentCount=0;
            for(var key in $this.uploadFileData['public']) {
                currentCount++;
            }
            for(var key in $this.uploadFileData['private']) {
                currentCount++;
            }
            currentCount+=$this.counter['public']*1+$this.counter['private']*1;
            if($this.uploadLimitPhotoCount>currentCount){
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    this.setEventField = function(type) {

        $('#some_add_photo_main_'+type+', #some_add_photo_'+type+', #some_link_photo_'+type).on('change', function(e){
            var el=$(this),val=this.value;
            $this.pp[type].open();
            setTimeout(function(){
                $('#photo_file_'+type).fileupload('add', {
                    files: e.target.files || [{name: val}],
                    fileInput: el
                });
                var w=$('.pp_add_more_photos:visible')[0].offsetWidth;
                $('.pp_link_add_photo').width(w);
            },220);
        }).on('click',function(){
            if($this.isUploadPhotoEnabled()){
                var $el=$(this),id=$el.attr('id'),reset=$('#'+id+'_reset');
                if(id=='some_add_photo_main_public'){
                    setTimeout(function(){$('#tab_photo').click()},220);
                }
                // And all can not hurt
                if(/WebKit||Chrome/i.test(navigator.userAgent)&&reset[0]){
                    reset.click();
                }
            } else {
                confirmCustom($this.langParts.upload_more_than_limit,function(){window.location.href=url_main+'upgrade.php'});
                return false;
            }
        })

        /* Hover link */

        if(!(/iPad/i.test(navigator.userAgent))){
            var photo_upload_more=$('#photo_upload_more_'+type);
            $('#photo_file_'+type).hover(
                function(){photo_upload_more.addClass('link_photo_more_hover')},
                function(){photo_upload_more.removeClass('link_photo_more_hover')}
            ).click(function(){
                if($this.isUploadPhotoEnabled()){
                    photo_upload_more.removeClass('link_photo_more_hover');
                } else {
                    confirmCustomRedirect(urlPagesSite.upgrade,$this.langParts.upload_more_than_limit,$this.langParts.upgrade,1);
                    return false;
                }
            });

            var some_link_photo_more=$('#some_link_photo_more_'+type);
            $('#some_link_photo_'+type).hover(
                function(){some_link_photo_more.addClass('link_photo_more_hover')},
                function(){some_link_photo_more.removeClass('link_photo_more_hover');}
            ).click(function(){some_link_photo_more.removeClass('link_photo_more_hover')});


            $('#some_add_photo_'+type).hover(
                function(){$(this).closest('.frame').addClass('hover_opacity')},
                function(){$(this).closest('.frame').removeClass('hover_opacity')}
            ).click(function(){
                $(this).closest('.frame').removeClass('hover_opacity');
            })
        }
        /* Hover link */

        $('#add_photo_close_'+type).click(function(){
            //$this.pp[type].find('.error:visible').closest('.item').remove();
            //$this.pp[type].close();
            $('#add_photo_cancel_'+type).click();
            return false;
        });

        $('#add_photo_done_'+type).click(function(){
            $this.type=type;
            $this.publishPhotos();
            return false;
        });

        $('#add_photo_cancel_'+type).click(function(){
            $this.type=type;
            if ($.isEmptyObject($this.uploadFileData[type])) {
                $this.pp[type].close().find('.item').remove().end()
                              .find('#add_photo_done_'+type).prop('disabled',true);
            } else {
               alertHtmlArea = '.column_main';
               if(type=='video' || type=='video_private'){
                    confirmCustom($this.langParts.uploaded_several_videos, $this.deletePendingPhotos);
               } else {
                    confirmCustom($this.langParts.uploaded_several_photos, $this.deletePendingPhotos);
               }
            }
            return false;
        });

    }

    this.initFileUpload = function(type) {
        'use strict';
        var add_done = $('#add_photo_done_'+type);
        var acceptFileTypes=/(\.|\/)(gif|jpe?g|png)$/i;
        var maxFileSize = $this.maxFileSize;
        var messMaxFileSize = $this.langParts.maxFileSize;
        if(type=='video'){
            //acceptFileTypes=/(\.|\/)(3gp|avi|flv|3mkv|mov|mpeg|mpg|wmv|mp4)$/i;
            acceptFileTypes=/^video\/.*$/;
            maxFileSize = $this.maxVideoSize;
            messMaxFileSize = $this.langParts.maxVideoSize;
        }
        var url = this.url_main+'ajax.php?cmd=photo_add_upload&type='+type;

        var e_user_id = 0
        if($("#ua_user_id").val())
            e_user_id = $("#ua_user_id").val()

        if(e_user_id)
            url = this.url_main+'ajax.php?cmd=photo_add_upload&type='+type+'&e_user_id='+e_user_id

        $('#photo_file_'+type).fileupload({
            url: url,
            dataType: 'json',
            autoUpload: true,
            acceptFileTypes: acceptFileTypes,
            maxFileSize: maxFileSize,
            //maxNumberOfFiles: 2,
            //getNumberOfFiles: function(){
                //return $this.counterUpload[type];
            //},
            messages: {
                maxNumberOfFiles: $this.langParts.maxNumberOfFiles,
                acceptFileTypes: $this.langParts.acceptFileTypes,
                maxFileSize: messMaxFileSize,
                minFileSize: $this.langParts.minFileSize
            },
            progressInterval: 50,
            bitrateInterval: 250,
            //disableImageResize: /Android(?!.*Chrome)|Opera/
            //.test(window.navigator.userAgent),
            imageQuality: 1,
            previewMaxWidth: 27,
            previewMaxHeight: 31,
            previewCrop: true,
            previewCanvas: true,
            limitConcurrentUploads: 10,
            //limitMultiFileUploads: 2,
        }).on('fileuploadsend', function (e, data) {
            if($this.countFilesUploaded>=$this.countFilesForUpload){
                return false;
            }
            $this.countFilesUploaded++;
        }).on('fileuploadadd', function (e, data) {
            if($this.isUploadPhotoEnabled()){
                $this.countFilesForUpload++;
            }else{
                return false;
            }
            var content = $('#cont_files_'+type);
            if(type=='video'){
                $("#tmpl_upload_photo .pic_cont a.add_desc").text($this.langParts.addTitle);
            }else {
                $("#tmpl_upload_photo .pic_cont a.add_desc").text($this.langParts.addCaption);
            }

            data.context = $('#tmpl_upload_photo').clone().attr('id', '').appendTo(content).show();
            $.each(data.files, function (index, file) {
                var context = data.context, fileName='';
                if (content.find('.item').length%2 == 0){
                    context.addClass('color');
                }

                var name = +new Date + '_' + $this.idGenerator++;
                $this.uploadFileData[type][name]={};
                $this.uploadFileData[type][name]['id'] = 0;
                $this.uploadFileData[type][name]['time_upload'] = 0;
                $this.uploadFileData[type][name]['desc'] = '';

                if (typeof file.name!='undefined') {
                    fileName = file.name;
                    if(fileName.indexOf('fakepath')+1){
                        fileName = fileName.split('fakepath\\')[1];
                    }
                }
                context.find('.name_pic').text(fileName).hide();

                context.data('item',name).find('.icon_delete').on('click', function(){
                    // Delete context
                    delete $this.uploadFileData[type][name];
                    data.abort();
                    context.slideDown(300,function(){context.remove()});
                    if ($.isEmptyObject($this.uploadFileData[type])){
                        add_done.prop('disabled', true);
                        $('#add_photo_close_'+type).click();
                    }
                    return false;
                });
                var pic_cont=context.find('.pic_cont'),
                    frm_desc=context.find('.frm_edit_desc'),
                    input_desc=frm_desc.find('.edit_desc_input'),
                    link_desc=context.find('.link_click'),
                    pen_desc=context.find('.pen_desc'),
                    cancel=context.find('.edit_desc_cancel'),
                    save=context.find('.edit_desc_save'),
                    desc=context.find('.description'),
                    edit_desc=context.find('.pic, .link_click, .edit_handle, .description');
                    context.find('.pic').hover(
                        function(){link_desc.addClass('hover')},
                        function(){link_desc.removeClass('hover')}
                    ).click(function(){
                        link_desc.removeClass('hover')
                    })
                edit_desc.on('click', function(e){
                    if(frm_desc.is(':visible'))return false;
                    $('#cont_files_'+type).find('.edit_desc_save:visible').click();
                    var val = $this.uploadFileData[type][name]['desc'];
                    pic_cont.hide();
                    frm_desc.show();
                    input_desc.val(val).focus();
                    return false;
                });

                cancel.on('click', function(e){
                    var val=$this.uploadFileData[type][name]['desc'];
                    input_desc.val(val);
                    frm_desc.hide();
                    if(val==''){
                        link_desc.show();
                        pen_desc.hide();
                    }else{
                        link_desc.hide();
                        desc.text(val);
                        pen_desc.show();
                    }
                    pic_cont.show();
                    return false;
                });

                save.on('click', function(){
                    var val = $.trim(input_desc.val());
                    $this.uploadFileData[type][name]['desc']=val;
                    cancel.click();
                    return false;
                });

                input_desc.on('keydown', function(e) {
                    if (e.type == 'keydown' && isKeyPressed(e, 13)){
                        save.click();
                        return false;
                    }
                });

                desc.css('max-width', '130px');
            });
            $this.setCounterUpload(type, true);
    }).on('fileuploadprocessalways', function (e, data) {
        var index = data.index,
            file = data.files[index],
            context = data.context;

        if(typeof context == 'undefined')return;

        var noPhoto='<img src="'+$this.url_main_images+'nophoto_add.png">';
        if(type=='video'){
            noPhoto='<img src="'+$this.url_main_images+'icon_video.png">';
        }

        if (file.error) {
            $this.showErrorUpload(type, context, file.error);
            $this.setCounterUpload(type, false);
            context.find('.pic').html(noPhoto);
        } else if (file.preview) {
            context.find('.pic').html(file.preview);
        } else {
            //$(noPhoto).load(function(){context.find('.pic').html(noPhoto)});
            context.find('.pic').html(noPhoto)
        }
    }).on('fileuploadprogress', function (e, data) {
        var progress = parseInt(data.loaded/data.total*100,10), context = data.context;
        context.find('.slider_range').css('width', progress + '%');
        if(progress > 95){
            context.find('.slider').hide();
            context.find('.description').css('max-width', '200px');
            context.find('.processing').show();
        };
    //}).on('fileuploadprogressall', function (e, data) {
        //if(data.loaded==data.total)add_done.prop('disabled', false);FF all
    }).on('fileuploaddone', function (e, data) {
        var result = (type == 'public') ? data.result.file_public : ((type == 'private') ? data.result.file_private : data.result.file_video);
        $.each(result, function (index, file) {
            var context = data.context;
            context.find('.processing').hide();
            if (file.error) {
                if(file.error=='error_license'){
                    file.error=$this.langParts.file_upload_failed;
                    alertCustom($this.langParts.error_license_signature,true,$this.langParts.error_license_title);
                }
                $this.showErrorUpload(type, context, file.error);
            } else if (file.url) {
                //context.find('.description').css('max-width', '200px');
                context.find('.uploaded').show();
                $this.uploadFileData[type][file.name]['id']=file.id;
                $this.uploadFileData[type][file.name]['time_upload']=+new Date;
                var pic=context.find('.pic');
                if (pic.find('img')[0]) {
                    var src=$this.url_files+file.src_r, preview='<img src="'+src+'">';
                    $(preview).load(function(){
						$('.pic>*', context).fadeTo(0,0);
						pic.css('background', 'url('+src+') center/cover')
					})
                    //context.find('.pic').html('<img src="'+$this.url_files+file.src_r+'">');
                }
            }
            $this.setCounterUpload(type, false);
        });
    }).on('fileuploadstop', function (e, data) {
            this.countFilesForUpload=0;
            this.countFilesUploaded=0;

    }).on('fileuploadfail', function (e, data) {
        $.each(data.files, function (index, file) {
            var context = data.context;
            $this.showErrorUpload(type, context, $this.langParts['file_upload_failed']);
            $this.setCounterUpload(type, false);
        });
    }).on('fileuploadsubmit', function (e, data) {
        data.formData = {file_name: data.context.data('item')};
    }).on('fileuploadprocessstart', function (e, data) {
        add_done.prop('disabled', true);
    //}).on('fileuploadprocessstop', function (e, data) {
        //add_done.prop('disabled', false);
        //console.log($this.uploadFileData);
    }).prop('disabled', !$.support.fileInput)
      .parent().addClass($.support.fileInput ? undefined : 'disabled');

    $('#cont_files_'+type).click(function(e){
            var $visBtnSave=$('.edit_desc_save:visible',this);
            if($visBtnSave[0]){
                var targ=e.target;
                if(targ.id=='cont_files_'+type){
                    $visBtnSave.click();
                    return;
                }
                var $item=$visBtnSave.closest('.item'), $frm=$visBtnSave.closest('.frm_edit_desc');
                if(targ==$item[0]||targ==$frm[0]){
                    $visBtnSave.click();
                }
            }
      })
    }

    this.showErrorUpload = function(type, context, error) {
        delete $this.uploadFileData[type][context.data('item')];
        if(redirectRequiresAuth(error)){
            return;
        }
        context.find('.name_pic').show();
        context.find('.add_desc').hide();
        context.find('.slider').hide();
        context.find('.processing').hide();
        context.find('.error').text(error).show();
        context.find('.icon_delete').hide();
    }

    this.setCounterUpload = function(type, enlarge) {
        if (enlarge) {$this.counterUpload[type]++;}else{$this.counterUpload[type]--;}
        if(type=='video' || type=='video_private'){
            value = $this.langParts.uploadCounterVideo.replace(/{count}/, $this.counterUpload[type]);
        } else {
            value = $this.langParts.uploadCounter.replace(/{count}/, $this.counterUpload[type]);
        }
        if ($this.counterUpload[type] <= 0) {
            var value = '';
            if (!$.isEmptyObject($this.uploadFileData[type])){
                $('#add_photo_done_'+type).prop('disabled', false);
            }
        }
        $('#some_link_photo_counter_'+type).text(value);
    }

    this.deletePendingPhotos = function() {
        closeAlert();
        var type=$this.type;
        if (type!='') {
            $.post($this.url_ajax,{cmd:'delete_pending_photos', type:type},
                function(res){
                    var data=checkDataAjax(res);
                    if (data!==false){
                        delete $this.uploadFileData[type];
                        $this.uploadFileData[type]={};
                        $('#some_link_photo_counter_'+type).text('');
                    }
            });
        }
        setTimeout(function(){
            $this.pp[type].close().find('.item').remove().end()
                          .find('#add_photo_done_'+type).prop('disabled',true);
        },220);
    }

    this.publishPhotos = function() {
        var type=$this.type;
        if(!type)return;

        var e_user_id = 0
        if($("#ua_user_id").val())
            e_user_id = $("#ua_user_id").val()

        $.ajax({url:$this.url_ajax,
                type:'POST',
                data:{cmd:'publish_photos_gallery',type:type, photos:$this.uploadFileData[type], e_user_id:e_user_id},
                beforeSend: function(){
                    for(var key in $this.uploadFileData[type]) {
                        $this.prepareAddPhotoToList(type, $this.uploadFileData[type][key]['id']);
                    }
                },
                success: function(res){
                    var data=checkDataAjax(res);
                    if (data!==false){
                        $this.galleryPhotosInfo=data;
                        $this.galleryMediaData=data;
                       // console.log(data);
                        // It is necessary to sort $this.uploadFileData[type] по time_upload
                        for(var id in $this.uploadFileData[type]) {
                            var pid=$this.uploadFileData[type][id]['id'];
                            if(typeof $this.galleryPhotosInfo[pid]=='undefined'){
                                delete $this.uploadFileData[type][id];
                                continue;
                            }
                            //$this.prepareAddPhotoToList(type, pid);
                            $this.addPhotoToList(type, pid);
                            if(type!='video'
                               &&$this.galleryPhotosInfo[pid]['default']=='Y'
                               //&&$currentDefaultId
                               &&$('#pic_main_img').data('mainPhoto')!=pid){
                               $this.replacePhotoMainDefault(pid);
                            }
                            delete $this.uploadFileData[type][id];
                        }
                        // To spotlight
                        Profile.isPhotoDefaultPublic = $this.isPhotoDefaultPublic();
                    }else{
                        $this.showErrorPublishPhotos(type)
                    }
                },
                error: function(){
                    $this.showErrorPublishPhotos(type)
                },
                complete: function(){
                }
                });
        $this.pp[type].close().find('.item').remove().end()
                      .find('#add_photo_done_'+type).prop('disabled',true);
    }

    this.showErrorPublishPhotos = function(type){
        for(var key in $this.uploadFileData[type]) {
            $this.removePhotoListIfError(type, $this.uploadFileData[type][key]['id']);
            delete $this.uploadFileData[type][key];
        }
        alertCustom($this.langParts['server_error_try_again'],true,ALERT_HTML_ALERT);
    }


    this.prepareAddPhotoToList = function(type, pid){
        var pidS=""+pid;
        var isVideo=(pidS.indexOf('v_')>=0);
        var $block=$('#photo_add_'+type),lDelete=$this.langParts.delete_photo;
        if(isVideo)lDelete=$this.langParts.delete_video;
        if($('#'+pid)[0])return;
        var $tmpl=$('#tmpl_photo_add').clone()
        .attr('id',''+pid)
        .css({display:'inline-block'})
        .find('.icon_delete').css({display:'none'}).click(function(){Photo.confirmPhotoDelete(pid)}).attr('title',lDelete).end()
        .find('.icon_rotate').css({display:'none'}).attr({id:'photo_rotate_'+pid,'data-photo-id':pid}).data('photoId',pid).end()
        .find('.icon_public').css({display:'none'}).click(function(){Photo.setAccess(pid)}).attr('id','profile_photo_set_status_'+pid).end()
        .find('.show_photo_gallery').data('offsetId',pid).click(function(){Photo.openGalleryId(pid);return false;}).end()
        .find('img').css({display:'inline'})
        .attr({id:(isVideo?'video_':'photo_')+pid,'data-photo-id':pid}).data('photoId',pid).end()
        .insertAfter($block);
        $this.showLoaderAction($tmpl);
        if(isVideo){
            $('#' + pid).addClass('item_video');
        }
    }


    this.addPhotoToList = function(type, pid){
        var $block=$('#'+pid);
        if(!$block[0])return;
        $this.setPhotoInfo(pid, type);
        $this.counter[type]++;
        Profile.isPhotoPublic = $this.counter.public>0;
        var pref='photo_';
        if(type=='video'){
            pref='video_';
        }
        var dur=300;
        $('#'+pref+pid,$block).load(function(){
            $this.hideLoaderAction($block);
            var $el=$(this);
            $block.find('a.show_photo_gallery').attr('data-offset-id',pid).data('offsetId',pid);
            if(type=='video'){
                $block.find('.icon_delete').fadeIn(dur);
                $block.find('.not_checked_photo').css({display:($this.galleryPhotosInfo[pid]['visible']=='Y')?'none':'block'})
                $block.find('a.show_video_gallery').attr('data-offset-id',pid).data('offsetId',pid);
            } else {
                var $makeStatus=$block.find('.icon_public');
                if(type=='private'){
                    $makeStatus.attr('title',$this.langParts.make_public);
                }else{
                    $makeStatus.toggleClass('icon_public icon_private')
                    $('span.icon_public_photo',$makeStatus).toggleClass('icon_public_photo icon_private_photo');
                    $('span.icon_public_photo_hover',$makeStatus).toggleClass('icon_public_photo_hover icon_private_photo_hover');
                    $makeStatus.attr('title',$this.langParts.make_private);
                }
                $makeStatus.fadeIn(dur);
                $block.find('.icon_delete,.icon_rotate').fadeIn(dur);
                $block.find('.not_checked_photo').css({display:($this.galleryPhotosInfo[pid]['visible']=='Y')?'none':'block'})
            }
            $el.attr('title', $this.galleryPhotosInfo[pid]['description']);
            $el.fadeTo(dur*.8,1);
            $('#photo_'+type+'_counter').html($this.getCounterLabel(type));
        }).attr({src:this.url_files+$this.galleryPhotosInfo[pid]['src_m']});

        /*var $replacePhotoM=$('[data-main-photo=0]');
        if($replacePhotoM.length){
            $replacePhotoM.attr('data-main-photo',pid).data('mainPhoto',pid);
            $replacePhotoM.each(function(){
                $this.replacePhotoMain(pid, $(this));
            })
        }*/
    }

    this.removePhotoListIfError = function(type,pid){
        $('#'+pid).oneTransEnd(function(){$(this).remove()}).addClass('to_collapse',0);
    }

    this.showLoaderAction = function($el) {
        var $layer=$el.find('.block_layer_action');
        if($layer[0]){
            $layer.addClass('to_show');
        }else{
            $('<div class="block_layer_action"><div class="photo_preloader_bg"></div></div>')
            .append(getLoader('photo_action',false,false,true)).prependTo($el).addClass('to_show');
        }
    }

    this.hideLoaderAction = function($el,remove) {
        if(remove||0){
            $el.find('.block_layer_action').remove();
        }else{
            $el.find('.block_layer_action').oneTransEnd(function(){
                $(this).remove();
            }).removeClass('to_show')
        }
    }

    this.setAccess = function(pid) {
        if ($this.photoInfo[pid].active) return;
        // already have the full $this.galleryPhotosInfo this is not necessary $this.photoInfo
        // here $this.photoInfo.active for ajax request
        if ($this.galleryPhotosInfo[pid]['default']=='Y'
            &&$this.galleryPhotosInfo[pid]['private']=='N'){
            alertCustom($this.langParts.can_only_be_public,false,ALERT_HTML_ALERT);
            return;
        }
        $this.photoInfo[pid].active=true;
        var el=$('#'+pid),$item=$('#'+pid),frame=el.closest('.profile_photo_frame');

        var e_user_id = 0
        if($("#ua_user_id").val())
            e_user_id = $("#ua_user_id").val()

        $.ajax({type: 'POST',
                url: $this.url_ajax+'?cmd=set_photo_access',
                data: {id: pid, e_user_id:e_user_id},
                beforeSend: function(){
                    $this.showLoaderAction($item);
                },
                success: function(res){
                    //frame.children('.icon_preloader_backgrounds').hide();
                    var data=checkDataAjax(res);
                    if(data){
                        var status=$this.photoInfo[pid].status, new_status=$this.getNewStatus(pid),
                            statusTitle=$this.langParts['make_'+status];
                        //frame.removeClass('photo_scale');

                        $item.removeClass('to_collapse to_expand').oneTransEnd(function(){
                            $this.hideLoaderAction($item,true);
                            $('#profile_photo_set_status_'+pid).toggleClass('icon_private icon_public')
                            .attr('title', statusTitle);

                            $this.counter[status]--;
                            $this.counter[new_status]++;
                            //$('#photo_'+status+'_counter').html($this.getCounterLabel(status));
                            if(data.indexOf('photo_approval')!==-1){
                                var $check=$('<div class="not_checked_photo" title="'+$this.langParts['being_checked_by_moderators']+'"></div>');
                                if (!el.find('.not_checked_photo')[0]) {
                                    el.find('.show_photo_gallery').prepend($check);
                                } else {
                                    el.find('.not_checked_photo').show();
                                }
                                $this.galleryPhotosInfo[pid]['visible'] = 'N';
                                $this.galleryMediaData[pid]['visible'] = 'N';
                            }

                            $item.insertAfter('#photo_add_'+new_status).oneTransEnd(function(){
                                //$('#photo_'+new_status+'_counter').html($this.getCounterLabel(new_status));
                                $this.photoInfo[pid].status = new_status;
                                $this.galleryPhotosInfo[pid]['private'] = (new_status=='private')?'Y':'N';
                                //frame.addClass('photo_scale');
                                // To spotlight
                                if(data.indexOf('set_default')!==-1){
                                    $this.replacePhotoMainDefault(pid);
                                    $this.updatePhotoInfoDefault(pid);
                                }
                                Profile.isPhotoDefaultPublic = $this.isPhotoDefaultPublic();
                                Profile.isPhotoPublic = $this.counter.public>0;//??? not used
                            }).toggleClass('to_collapse to_expand',0);
                        }).addClass('to_collapse')
                    }
                    $this.photoInfo[pid].active = false;
                }
        });
    }

    this.confirmPhotoDelete = function(pid) {
        $this.pid = pid;

        var title = l('confirm_delete_photo');

        if($this.isVideoData(pid)){
			title = l('confirm_delete_video');
		}

        confirmCustom(MSG_THIS_ACTION_CAN_NOT_BE_UNDONE, $this.photoDelete, title);
    }

    this.photoDelete = function() {
        confirmHtmlClose();
        var pid = $this.pid;
        if ($this.photoInfo[pid].active) return;
        $this.photoInfo[pid].active=true;
        var $el=$('#'+pid),status=$this.photoInfo[pid].status;

        if($this.isVideoData(pid)) {
			status = 'video';
		} else {
			status = $this.photoInfo[pid].status;
		}

        var e_user_id = 0
        if($("#ua_user_id").val())
            e_user_id = $("#ua_user_id").val()

        $.ajax({type:'POST',
                url:$this.url_ajax,
                data:{cmd:'delete_photo',id:pid, e_user_id:e_user_id},
                beforeSend: function(){
                    $this.showLoaderAction($el);
                },
                success: function(res){
                    var data=checkDataAjax(res);
                    if (data!==false){
                        // Main photo
                        //frame.removeClass('photo_scale');
                        $el.oneTransEnd(function(){
                            $(this).remove();
                            $this.counter[status]--;
                            //$('#photo_'+status+'_counter').html($this.getCounterLabel(status));
                            $this.deletePhotoInfo(pid, data);
                            if($this.isVideoData(pid)) {
                                /*$this.greetingVideoId=0;
                                    $("#see_my_video_greeting").hide();
                                */
                            }else{
                                // Replace the photo with remote src
                                var replaceMain=$('[data-main-photo = '+pid+']'),
                                    pidD=0;
                                if(replaceMain[0]){
                                    for (var id in $this.galleryPhotosInfo) {
                                        if($this.galleryPhotosInfo[id]['default']=='Y'){
                                            pidD=id;
                                            break;
                                        }
                                    }
                                    setTimeout(function(){$this.replacePhotoDefault(pidD)},200);
                                }
                                // To spotlight
                                Profile.isPhotoDefaultPublic = $this.isPhotoDefaultPublic();
                                Profile.isPhotoPublic = $this.counter.public>0;
                                    /*var $wallPhoto = $('#wall_photo_'+pid);
                                    if ($wallPhoto[0]) {
                                    var $li=$wallPhoto.parent('li');
                                    if($li[0]){
                                        var $ul=$li.parent('ul');
                                        if($ul.find('li').length>1){
                                            $li.animate({width:0,opacity:0},600,function(){
                                                $li.remove();
                                            })
                                        }else{
                                            Wall.itemExists($wallPhoto.data('wallItemId'), 'deleted');
                                        }
                                    }else{
                                        Wall.itemExists($wallPhoto.data('wallItemId'), 'deleted');
                                    }
                                    }*/
                            }
                            $jq('.main').scroll();
                        }).addClass('to_collapse',0);
                    }
                }
        });
    }

    this.isPhotoDefaultPublic = function(){
        var is=false;
        for (var id in $this.galleryPhotosInfo) {
            if($this.galleryPhotosInfo[id]['default']=='Y'
               &&$this.galleryPhotosInfo[id]['visible']=='Y'
               &&$this.galleryPhotosInfo[id]['private']=='N'){
                is=true;
                break;
            }
        }
        return is;
    }

    this.setPhotoMain = function(replacePhoto){

    }

    this.replacePhotoMainChangeGander = function(gender) {
        if(gender!=$this.gender)$this.gender=gender;
        if(!$this.countAllPhoto()){
            $('[data-main-photo=0]').each(function(){
                $this.replacePhotoMain(0, $(this));
            })
        }
    }

    this.replacePhotoMainDefault = function(pid) {
        $('[data-main-photo]').each(function(){
            if($(this).data('mainPhoto')==null)return;
            $this.replacePhotoMain(pid,$(this),true);
        })
    }

    this.replacePhotoDefault = function(pid) {
        $this.replacePhotoMainDefault(pid);
    }

    this.replacePhotoMain = function(pid,$replacePhoto,isDef) {
        var src,replacePhotoSrc=$replacePhoto.attr('src'),size,
            cursor='pointer',isDef=isDef||false;

        size=$replacePhoto.data('photoSize');
        if(size==undefined)size=replacePhotoSrc.split('?')[0].substr(-5,1);

        if(pid){
            src=$this.url_files+$this.galleryPhotosInfo[pid]['src_'+size];
        }else{
            src=$this.url_files+'impact_nophoto_'+$this.gender+'_'+size+'.png';
            cursor='default';
        }

        if(isDef){$this.galleryCurrenPhotoId=pid}//???

        var img=new Image();
        img.onload = function(){
            $replacePhoto.attr({'data-main-photo':pid}).data('mainPhoto', pid).css('opacity',0);
            if ($replacePhoto.is('.show_photo_gallery')) {
                $replacePhoto.removeAttr('onload')
            }
            if ($replacePhoto.is('button')) {
                $replacePhoto.css({backgroundImage:'url('+img.src+')', opacity:1});
            }else{
                $replacePhoto.attr({src:img.src}).css('opacity',1);
            }
            if ($replacePhoto.is('.show_photo_gallery')) {
                $replacePhoto.css('cursor',cursor);
                if(pid){
                    if($this.galleryPhotosInfo[pid]['visible']=='N'){
                        $replacePhoto.prev('div').show();
                    }else{
                        $replacePhoto.prev('div').hide();
                    }
                    $replacePhoto.attr('onclick','Photo.openGalleryId('+pid+')')
                                 .attr('data-offset-id',pid)
                                 .data('data-offset-id',pid);
                    $jq('#add_photo_main_profile').hide();
                }else{
                    $replacePhoto.prev('div').hide();
                    $replacePhoto.removeAttr('onclick')
                                 .removeAttr('data-offset-id')
                                 .data('offsetId',0);
                    $jq('#add_photo_main_profile').show();
                }
            }
            stylizeOneMainPhoto($replacePhoto);
        };
        img.src=src;
        delete img;
    }

    this.setNewPositionRandBox = function(img) {
        var newHeight = $this.photoWidthMain*img.height/img.width;
        $('.pic_main').css({'height':newHeight+'px','line-height':newHeight+'px'});
        $('#rand_box').css({'top':(newHeight-23)+'px'});
    }

    this.galleryMediaData = {};
    this.setPhotoInfo = function(pid, status, data) {
        if (typeof $this.photoInfo[pid] == 'undefined') {
            $this.photoInfo[pid] = {};
        }
        $this.photoInfo[pid].status = status;
        $this.photoInfo[pid].active = false;
        if(data){
            $this.galleryMediaData[pid] = data;
            $this.galleryPhotosInfo[pid] = data;
        }
        $('[data-photo-id="'+pid+'"].lazy_deferred').lazyload({appear: function(){
			var $el=$(this);
            $el.one('load', function(){
                $el.parent('a').css({opacity:1,transition:'opacity .3s'})
            })[0].src=$el.data('src')
        }, check: function(){
            return $('#tabs-2.target')[0] || $this.isHotOrNot
        },containerScroll:$jq('.main')}).removeClass('lazy_deferred');
    }

	this.galleryMediaDataListPage = {};
	this.setPhotoListPageInfo = function(pid, data) {
        if(data){
            $this.galleryMediaDataListPage[pid] = data;
        }
    }

    this.deletePhotoInfo = function(pid, data) {
        //delete $this.galleryPhotosInfo[pid];
        $this.galleryPhotosInfo=data;
        delete $this.photoInfo[pid];
    }

    this.getCounterLabel = function(status) {
        return ($this.counter[status]==0)?'':$this.langParts['counter'].replace(/{number}/, $this.counter[status]);
    }

    this.getNewStatus = function getNewStatus(pid) {
        return ($this.photoInfo[pid].status == 'private')?'public':'private';
    }

    /* Gallery */
    this.carouselReload = function() {
        pp_photo_carousel.jcarousel('reload');
    }

    this.setCurrentPhotoInfo = function(pid){
        delete $this.requestAjaxRate[$this.galleryCurrenPhotoId];
        delete $this.requestAjaxRateDelete[$this.galleryCurrenPhotoId];
        $this.galleryCurrenPhotoId=pid;
        //for (var key in $this.galleryPhotosInfo[pid]) {
            //$this.galleryCurrenPhotoInfo[key]=$this.galleryPhotosInfo[pid][key];
        //}
    }

    this.countAllPhoto = function(){
        return ($this.counter.private*1)+($this.counter.public*1);
    }

    this.setVideoProfile = function(){
        var video_id =  $this.getIdVideo($this.galleryCurrenPhotoId);
        $.post($this.url_ajax,{cmd:'add_greeting_video', video_id:video_id},
            function(res){
                var data=checkDataAjax(res);
                if (data!==false){
                        $('#gallery_videos_make_profile').hide();
                        $('#pp_gallery_photos_desc').width(730);
                        popupAlertHand($this.langParts.set_greeting_video, false, false, false, '', 'page_shadow_empty')
                        $this.greetingVideoId=$this.getIdVideo($this.galleryCurrenPhotoId);
                        $("#see_my_video_greeting").show();
                }
        });
    }

    this.setPhotoDefault = function(){
        var pid=$this.galleryCurrenPhotoId,alert=alert||false;
        $.ajax({type: 'POST',
                url: $this.url_ajax,
                data: {'cmd':'set_photo_default',
                       'photo_id': pid},
                beforeSend: function(){
                },
                success: function(res){
                    if (checkDataAjax(res)){
                        $('#gallery_photos_make_profile').hide();
                        $('#pp_gallery_photos_desc').width(730);
                        $this.updatePhotoInfoDefault(pid);
                        Profile.isPhotoDefaultPublic = true;
                        setTimeout(function(){
                            $this.replacePhotoDefault(pid);
                        },200);
                        alertSuccess($this.langParts.set_default_photo, false)
                    }
                }
        });
        return;
    }

    this.updatePhotoInfoDefault = function(pid){
        for (var id in $this.galleryPhotosInfo) {
            $this.galleryPhotosInfo[id]['default']=(id==pid)?'Y':'N';
        }
    }

    this.showFrmDescPhotoGallery = function(){
        var pid=$this.galleryCurrenPhotoId;
        $this.hideLabelDescPhotoGallery();
        pp_frm_gallery_photos_desc_inp.val($this.galleryPhotosInfo[pid]['description']);
        pp_frm_gallery_photos_desc.show();
        pp_frm_gallery_photos_desc_inp.focus();
        return false;
    }

    this.hideLabelDescPhotoGallery = function(){
        pp_gallery_photos_pen.hide();
        pp_gallery_photos_desc.hide();
    }

    this.showLabelDescPhotoGallery = function(){
        pp_frm_gallery_photos_desc.hide()
        pp_gallery_photos_pen.show();
        pp_gallery_photos_desc.show();
    }

    this.descPhotoGallerySave = function(){
        var pid=$this.galleryCurrenPhotoId,
            desc=$.trim(pp_frm_gallery_photos_desc_inp.val());
        desc=strip_tags(desc);
        if ($this.galleryPhotosInfo[pid]['description']!=desc) {
            $this.galleryPhotosInfo[pid]['description']=desc;
            $.ajax({type: 'POST',
                    url: $this.url_ajax,
                    data: {'cmd':'photo_save_desc',
                           'desc': desc,
                           'pid': pid},
                    beforeSend: function(){
                        pp_frm_gallery_photos_desc.hide();
                        pp_gallery_photos_desc.text($this.langParts.saving).show();
                    },
                    success: function(res){
                        if(checkDataAjax(res)){
                            $this.descPhotoGalleryCancel(1);
                        }
                    }
            });
        } else {
            $this.descPhotoGalleryCancel(1);
        }
        return;
    }

    this.descPhotoGalleryCancel = function(guid){
        var pid=$this.galleryCurrenPhotoId,
            desc=$this.galleryPhotosInfo[pid]['description'],color='#FFF',
            $ppGalleryPhotos=$('#pp_gallery_photos_content');
        if($.trim(desc)==''&&$this.guid==$this.uid){//guid*1
           desc=$this.isVideo?$this.langParts.video_caption:$this.langParts.photo_caption;
           color='#848484';
        }
        if(guid*1){//$.trim(desc)!='' ||
            $ppGalleryPhotos.find('.photo_funk').show();
            pp_gallery_photos_desc.css('color',color).attr('title',desc).text(desc).show();
            $this.showLabelDescPhotoGallery();
        } else {
           //$ppGalleryPhotos.find('.photo_funk').animate({height:'toggle'},300);

        }
        return false;
    }

    this.setLabelPositionPhoto = function(pid){
        var labelPositionPhoto,count=$this.countAllPhoto(),offset=0;
        if(typeof $this.galleryPhotosInfo[pid] != 'undefined'){
            offset=$this.galleryPhotosInfo[pid]['offset'];
        }
        labelPositionPhoto=$this.langParts.position_photo.replace(/\{offset\}/, (count-offset*1));
        labelPositionPhoto=labelPositionPhoto.replace(/\{num\}/, count);
        $('#gallery_photo_position').text(labelPositionPhoto);
    }

    this.setCurrentPhotoCarousel = function(pid){
        var currentPhoto=pp_photo_carousel.find('li#pp_photos_carousel_item_'+pid);
        if (pp_photo_carousel.jcarousel('visible').index(currentPhoto) < 0) {
            setTimeout(function(){

                pp_photo_carousel.on('jcarousel:fullyvisiblein', 'li', function(event, carousel) {
                    var el = $(event.target);
                    $('img, a', el).add(el).css({visibility: 'visible'}).removeClass('hidden');
                });

                pp_photo_carousel.on('jcarousel:fullyvisibleout', 'li', function(event, carousel) {
                    setTimeout(function(){
                        var el = $(event.target);
                        $('a.active', el).addClass('hidden');
                        $('a, img', el).add(el).css({visibility: 'hidden'});
                    }, 1000);
                });

                pp_photo_carousel.jcarousel('scroll', currentPhoto);
            }, 300);
        }
    }

    this.isPublic = function(pid){
        return (typeof($this.galleryPhotosInfo[pid])!='undefined'&&
                ($this.guid==$this.uid||$this.fuid*1!=0
                ||$this.galleryPhotosInfo[pid]['private']=='N'))||$this.isVideo;
    }
    /* ACTION */
    /* Comment */
    this.enterPostComment = function(e){
        var el=this;
        setTimeout(function(){el.scrollTop=0}, 0);
        if (e.which == 13){
            if (!e.shiftKey && !e.ctrlKey && !e.metaKey) {
                $this.postComment();
                return false;
            }
            if (e.ctrlKey) enterCaret(el)
        }
    }

    this.postComment = function(comment){
        if (typeof comment!='string') comment='';
        comment=comment||$.trim(pp_gallery_photos_comment_inp.val());
        var pid=$this.galleryCurrenPhotoId;
        pp_gallery_photos_comment_inp.val('').trigger('autosize');//.focus();
        if(comment){
            var data={comment:comment,
                      photo_id:pid,
                      photo_user_id:$this.uid,
                      private:$this.galleryPhotosInfo[pid]['private']};
            $.post($this.url_ajax+'?cmd=photo_comment_add',data,
            function(res){
                data=checkDataAjax(res);
                if (data!==false){
                    $this.showComment(data);
                }
            })
        }
        return false
    }

    this.showComment = function(data){
        pp_gallery_photos_footer.show();//????
        $(trim(data)).hide().prependTo('#pp_gallery_photos_list_comment_items')
          .animate({height:'toggle'},400);
    }

    this.deleteComment = function(){
        var cid=$this.cid,pid=$this.galleryCurrenPhotoId,
            comment=$('#gallery_photos_comment_'+cid);
        if(cid){
            confirmHtmlClose();
            comment.toggleClass('opacity');
            $.post($this.url_ajax,{cmd:'photo_comment_delete',cid:cid,user_id:$this.uid,pid:pid},
            function(res){
                var data=checkDataAjax(res);
                if (data){
                    if (data.votes!=undefined) {
                        $this.changeDisplayRating(pid,data);
                    } else {
                        $this.hideDeleteComment(comment);
                    }
                } else {
                    //Server error
                    comment.toggleClass('opacity');
                    alertCustom($this.langParts['server_error_try_again'],true,ALERT_HTML_ALERT);
                }
            })
        }
    }

    this.hideDeleteComment = function(comment){
        if(comment[0]){
            comment.slideUp(400,function(){
                $(this).remove();
                if ($('[id ^= "gallery_photos_comment_"]:visible').length < 20) {
                    $('[id ^= "gallery_photos_comment_"]:hidden').first()
                    .removeClass('no_visible').hide()
                    .animate({height:'toggle'},300,function(){$this.getLabelLoadMoreComments()});
                }
            })
        }
    }

    this.confirmDeleteComment = function(cid){
        $this.cid = cid;
        alertHtmlArea = '.column_main';
        confirmCustom(ALERT_HTML_ARE_YOU_SURE, this.deleteComment);
    }

    this.loadMoreComments = function(){
        var t=150,i=0,comment=$('[id ^= "gallery_photos_comment_"]:hidden');
        (function fu(){
            var item=comment.eq(i).removeClass('no_visible').hide();
            item.animate({height:'toggle'},t*=.9,function(){
                $this.getLabelLoadMoreComments();
                if(i<19){i++; fu();}
            })
        })()
    }
    /* Comment */

    /* Deny - Scip Private */
    this.privateScip = function(){
        var next=$('#pp_photos_carousel_item_'+$this.galleryCurrenPhotoId).nextAll('.public');
        if(!next[0]){
            next=$('#pp_photo_carousel_list > li.public').eq(0)
        }
        if(next[0]){$this.showGalleryPhotoOne(next.data('photosCarouselId'))}
    }

    this.sendRequestPrivateAccess = function(mode,fade){
        var mode=mode||1,fade=fade||1;
        mode *=1;
        fade *=1;
        if(mode)var btn=$('#request_private').prop('disabled',true);
        $.post($this.url_ajax,{cmd:'send_request_private_access',type:'request_access',user_to:$this.uid},
            function(res){
                if(checkDataAjax(res)){
                    var msg=$this.langParts.access_requested_please_wait;
                    if(mode){
                        msg=$this.langParts.access_been_requested;
                        if(fade){btn.animate({width:'toggle'},100)}
                        else{btn.fadeOut(100)}
                        $('#or_private').fadeOut(100);
                        $('.request_access_private_photo').hide();
                    }else{
                        $('.request_access_private_photo').addClass('to_hide');
                    }
                    alertSuccess(msg, false, ALERT_HTML_SUCCESS, false, {left:'0px', top:'0px', margin:'25px 27px 25px 0px'});
                } else {
                    if(mode)btn.prop('disabled',false);
                    alertServerError();
                }
        })
    }
    /* Deny - Scip Private */

    /* Rate */
    this.setRate = function(rate){
        var pid=$this.galleryCurrenPhotoId;
        if($this.requestAjaxRate[pid])return;
        $this.requestAjaxRate[pid]=1;
        $jq('.list_rate','#pp_gallery_photos_rating_scale').after(getLoader('loader_rate_photo_gallery',false,true));
        $.post($this.url_ajax,
               {cmd:'set_rate_photo',
                photo_id:pid,
                photo_user_id:$this.uid,
                rate:rate,
                comment:'{rating:'+rate+'}',
                system: 1},
            function(res){
                var data=checkDataAjax(res);
                if(data){
                    for(var key in data) {
                        $this.galleryPhotosInfo[pid][key]=data[key];
                    }
                    if(pid==$this.galleryCurrenPhotoId){
                        pp_gallery_photos_rating_scale.fadeOut($this.durfast,function(){
                            $this.showAverage(data);
                        })
                        $this.showComment(data.comment);
                    }
                }
                delete $this.requestAjaxRate[pid];
                $jq('loader_loader_rate_photo_gallery').remove();
        })
    }

    this.confirmDeleteRate = function(){
        confirmHtml(ALERT_HTML_ARE_YOU_SURE,$this.deleteRate);
    }

    this.deleteRate = function(){
        confirmHtmlClose();
        var pid=$this.galleryCurrenPhotoId;
        if($this.requestAjaxRateDelete[pid])return;
        $this.requestAjaxRateDelete[pid]=1;
        $.post($this.url_ajax,{cmd:'delete_rate_photo',photo_id:pid},
            function(res){
                var data=checkDataAjax(res);
                if(data){
                    $this.changeDisplayRating(pid,data);
                }
                delete $this.requestAjaxRateDelete[pid];
            }
        )
    }

    this.changeDisplayRating = function(pid,data){
        for(var key in data) {
            $this.galleryPhotosInfo[pid][key]=data[key];
        }
        if(pid==$this.galleryCurrenPhotoId){
            pp_gallery_photos_rating_average.fadeOut($this.durfast,function(){
                pp_gallery_photos_rating_scale.fadeIn($this.durfast);
            })
            $this.hideDeleteComment($('#gallery_photos_comment_'+data.comment_id));
        }
    }
    /* Rate */
    /* ACTION */

    /* SHOW */
    this.showHideFooterGallery = function(){
        if ($('[id ^= "gallery_photos_comment_"]')[0]||$('#pp_gallery_photos_frm_comment:visible')[0]) {//(comments+frmComment)>0
            pp_gallery_photos_footer.show();
        } else {
            pp_gallery_photos_footer.hide();
        }
    }

    /* showAlways did not seem necessary */
    this.showGalleryPhotoOne = function(pid, setCarousel, showAlways){
        loader_rate_photo_gallery.hide();
        var setCarousel=setCarousel||1,showAlways=showAlways||1,pid_cur=$this.galleryCurrenPhotoId,
            pp_list_comment=$('#pp_gallery_photos_list_comment'),
            pp_comment_inp=$('#pp_gallery_photos_comment, #pp_gallery_photos_post'),photo_id;

        photo_id=pid;
        if (pid=='prev_id'||pid=='next_id') {
            photo_id=$this.galleryPhotosInfo[pid_cur][pid];
            if(!$this.isPublic(photo_id) && !$this.isPublic($this.galleryCurrenPhotoId)){
                var $scipPhoto=$('#pp_photos_carousel_item_'+$this.galleryCurrenPhotoId)[pid=='next_id'?'prevAll':'nextAll']('.public');
                if(!$scipPhoto[0]){
                    if(pid=='prev_id'){
                        $scipPhoto=$('#pp_photo_carousel_list > li.public').first();
                    }else{
                        $scipPhoto=$('#pp_photo_carousel_list > li.public').last();
                    }
                }
                if($scipPhoto[0]){
                    photo_id=$scipPhoto.data('photosCarouselId');
                }
            }
        }
        //photo_id=(pid=='prev_id'||pid=='next_id')?$this.galleryPhotosInfo[pid_cur][pid]:pid;
		if ($this.isNeedLiveUpgrade(photo_id)) return false;

		var fnStart=function(){
			/* Scroll carousel and Save Description */
			if(setCarousel*1){
				$this.setCurrentPhotoCarousel(photo_id);
				if ($('#pp_frm_gallery_photos_desc:visible').length){
					$this.descPhotoGallerySave();
				}
			}
			/* Scroll carousel and Save Description */
			if ($this.isPublic(photo_id)){
				$this.descPhotoGalleryCancel($this.guid==$this.uid);
				var cmd=$this.isVideo?'get_video_comment':'get_photo_comment';
				$.ajax({type:'POST',
						url:$this.url_ajax+'?cmd='+cmd,
						data:{photo_id:$this.getIdVideo(photo_id)},
						beforeSend: function(){
							pp_list_comment.toggleClass('opacity');
						},
						success: function(res){
							var data=checkDataAjax(res);
							if (data!==false) {
								pp_list_comment.html(data).toggleClass('opacity');
								pp_gallery_photos_frm_comment.show();
								pp_gallery_photos_footer.show();
								$('.comments_hidden', pp_gallery_photos_footer_empty).hide();
								pp_gallery_photos_footer_empty.hide();
							}
						}
				})
			}else{
				pp_gallery_photos_footer_empty.show();
				$('.comments_hidden', pp_gallery_photos_footer_empty).fadeIn(300);
				pp_gallery_photos_footer.hide();
				pp_gallery_photos_frm_comment.hide();
			}
			$this.showCurrentPhoto(photo_id, showAlways);
		}

		$this.checkNeedLiveCredit(photo_id, fnStart, {target:$('#pp_photos_carousel_link_'+photo_id)[0]});

        return false;
    }

    this.hPhotoOneCont;
	this.showReportPhoto = function(pid){
		if($this.guid!=$this.uid
			&& typeof $this.galleryPhotosInfo[pid] != 'undefined'//Fix live stream finished
            && !in_array($this.guid, $this.galleryPhotosInfo[pid]['reports'].split(','))
            && $this.isPublic(pid)){
            $('#report_video_gallery').stop().fadeIn($this.durAction);
        }
	}

    this.showCurrentPhoto = function(pid, showAlways, toOpen){
        $this.setCurrentPhotoInfo(pid);
        $this.setLabelPositionPhoto(pid);

        // not showAlways;
        dur=(showAlways*1)?400:1;
        $('.report_photo_gallery').stop().fadeOut(dur);

        var $photoOneCont=$('#pp_gallery_photo_one_cont'), vid=(pid+'').replace('v_',''), info=$this.galleryPhotosInfo[pid];
        $this.hPhotoOneCont=$photoOneCont.height();
        $photoOneCont.css({height:$this.hPhotoOneCont});

/*
        if($this.isVideo){
            setTimeout(function(){
                if($this.greetingVideoId==$this.getIdVideo(pid)){
                    $('#gallery_videos_make_profile').hide();
//                    $('#pp_gallery_photos_desc').width(730);
                } else {
                    $('#pp_gallery_photos_desc').width(590);
//                    $('#gallery_videos_make_profile').show();
                }
            },300);
         }
*/
		function showCont(){
            if($this.isVideo){
                var nextLang=$this.langParts.next_video;
                var prevLang=$this.langParts.prev_video;
                /*var src=$this.url_files+$this.galleryPhotosInfo[pid]['src_b'],dur;
                var srcV=$this.url_files+$this.galleryPhotosInfo[pid]['src_v'],dur;
                var frmt=$this.galleryPhotosInfo[pid]['format'];*/
                $('.bl_photo_one_cont', $photoOneCont).hide();
                $this.setVideoPlayer(pid);
                $this.showReportPhoto(pid);
            } else {
				var src=$this.url_files+info['src_b'],dur;
                var nextLang=$this.langParts.next_photo;
                var prevLang=$this.langParts.prev_photo;
                $('.bl_video_one', $photoOneCont).hide();
                var $loaderBox = $('.bl_photo_one', $photoOneCont).removeClass('ready');
                pp_gallery_photo_one_img.fadeTo(0,0).one('load',function(){
                        pp_gallery_request_access.fadeOut(0);
                        pp_gallery_photo_rating.fadeOut(0);
                        pp_gallery_photo_one_img.attr({src:src}).delay(10).fadeTo(400, 1);
                        $loaderBox.addClass('ready');//.css({background:'none'});
                        //$photoOneCont.css('height', 'auto');
                        setTimeout(function(){$this.showActionsWithPhoto(pid)},15);
				}).attr({src:src});//$this.url_main_images+'empty.png'
                pp_gallery_photo_one.delay(10).fadeTo(1,1)
            }
            $("#photo_show_next").attr('title',info&&info['prev_title']||nextLang);
            $("#photo_show_prev").attr('title',info&&info['next_title']||prevLang);
        };
        if ($this.isVideo) showCont()
		else pp_gallery_photo_one.stop().fadeTo(dur,0,showCont)

		if (toOpen) return;

		$this.descComm(pid);

        $('a', pp_gallery_photos_carousel_item).removeClass('active').css('cursor','pointer');
        $('#pp_photos_carousel_link_'+pid).addClass('active').css('cursor','default');
        pp_gallery_request_access.stop().fadeOut(dur);
        pp_gallery_photo_rating.stop().fadeOut(dur);

        /* Description and Frm Comment */
        /* Photo notchecked and Make default*/
        if (info['visible']=='N' || info['visible']=='Nudity') {
            $('#gallery_videos_make_profile').hide();
            pp_gallery_photos_make_profile.hide();
            pp_gallery_photos_photo_not_checked.show();
        } else {
            pp_gallery_photos_photo_not_checked.hide();
            if ($this.guid==$this.uid && info['default']=='N' && info['private'] == 'N'){
                pp_gallery_photos_make_profile.show();
            }else{
                pp_gallery_photos_make_profile.hide();
            }
        }

        if($this.isVideo){
                if(info['visible']!='N'){
                        if($this.greetingVideoId==$this.getIdVideo(pid)){
                            $('#gallery_videos_make_profile').hide();
                        } else {
                            $('#gallery_videos_make_profile').show();
                        }
                }

        }

        $this.setWidthDesc();
        /* Photo notchecked and Make default*/
    }
	this.descComm=function(pid){
        /* Description and Frm Comment */
        if ($this.isPublic(pid)) {
            $this.descPhotoGalleryCancel(1);//$this.guid==$this.uid
        } else {
            $this.hideLabelDescPhotoGallery();
        }
	}

    this.durAction=500;
    this.fadeAction;
    this.showAverage = function(data,is){
        var is=is||false;
        if(!$this.isPublic(data['photo_id'])){
            pp_gallery_photos_rating_average.fadeOut($this.durAction);
            return;
        }
        if(!(data['average']*1)) return;
        //if($this.guid==$this.uid&&!data['visible_rating']) return;
        if ($this.guid==$this.uid||is){
            pp_gallery_photos_my_ball.hide();
        }else{
            pp_gallery_photos_my_ball.show();
        }
        pp_gallery_photos_my_ball.find('strong')
        .text(data['my_rating']).show();
        pp_gallery_photos_rating_average.find('.slider_range')
        .css('width',data['average']*10+'%');
        var av=data['average'].toString();
        if (av.indexOf(".")<0) {
            av += '.';
        }
        av += '00';
        pp_gallery_photos_rating_average.find('.count_cont')
        .text(av.substr(0, 4));
        if($this.isFirstShowPhotoGallery){
            $this.fadeAction=pp_gallery_photos_rating_average;
            pp_gallery_photos_rating_average.css({opacity:0,display:'block'});
        }else{
            pp_gallery_photos_rating_average.fadeIn($this.durAction);
        }
    }

    this.initGallery = function(){
        $this.$galleryPhotos=$this.$galleryPhotosContent=$('#pp_gallery_photos_content');
        $this.$galleryPhotosClose=$('#pp_gallery_photos_close');
        $this.$galleryPhotosOneCont=$('#pp_gallery_photo_one_cont');
        $this.$galleryPhotosCarouselItem = $('.pp_photos_carousel_item', $this.$galleryPhotos);
        $this.$galleryPhotosCarouselLink = $('.pp_photos_carousel_link', $this.$galleryPhotos);

        $this.$galleryPhotosOneImage=$('#gallery_photo_one_img'),
        $this.$galleryPhotosOneBl=$('#gallery_photo_one');

        $this.$galleryPhotosRatingScale=$('#pp_gallery_photos_rating_scale');
        $this.$galleryPhotosRatingScaleLi=$('#pp_gallery_photos_rating_scale ul li');

        $this.$galleryPhotosRequestAccess=$('.request_access',$this.$galleryPhotos);

        if($this.isVideo) {
            $this.$galleryPhotosReport=$('#report_video_gallery');
        } else {
            $this.$galleryPhotosReport=$('#report_photo_gallery');
        }
    }

    this.showActionsWithPhoto = function(pid){
        if($this.guid!=$this.uid
            && !in_array($this.guid, $this.galleryPhotosInfo[pid]['reports'].split(','))
            && $this.isPublic(pid)){
            var l=$this.$galleryPhotosOneImage[0].offsetWidth,
                d=($this.$galleryPhotosOneBl[0].offsetWidth-l)/2;
            $this.$galleryPhotosReport.css({left:(l+d-20)}).stop().fadeIn($this.durAction);
        }
        if(!$this.photoRatingEnabled){
            if(!$this.isPublic(pid))$this.$galleryPhotosRequestAccess.fadeIn($this.durAction);
            return;
        }
        /* Actions */
        if($this.fadeAction!=null){
            $this.fadeAction.removeAttr('style');
        }
        $this.fadeAction=null;
        if($this.isPublic(pid)){
            if ($this.guid!=$this.uid
                &&!$this.galleryPhotosInfo[pid]['my_rating']) {
                $this.$galleryPhotosRatingScaleLi.removeClass('selected');
                if($this.isFirstShowPhotoGallery){
                    $this.fadeAction=$this.$galleryPhotosRatingScale;
                    $this.$galleryPhotosRatingScale.css({opacity:0,display:'block'});
                }else{
                    $this.$galleryPhotosRatingScale.fadeIn($this.durAction);
                }
            } else {
                $this.showAverage($this.galleryPhotosInfo[pid]);
            }
        }else{
            $this.showAverage($this.galleryPhotosInfo[pid],true);
            $this.$galleryPhotosRequestAccess.fadeIn($this.durAction);
        }
        /* Actions */

        if($this.isFirstShowPhotoGallery){
            var aH=$this.$galleryPhotosOneCont.css('height', 'auto').height();
            if($this.fadeAction!==null){
                if(aH<=744){
                    var h=$this.fadeAction.height()+23,mT=$this.fadeAction.css('margin-top');
                    $this.fadeAction.css({marginTop:-h+'px'});
                    setTimeout(function(){
                        $this.fadeAction.css({marginTop:mT, opacity:1, display:'block', transition:'all .6s, margin .6s, opacity .3s .4s'})
                    },50);
                }else{
                    $this.fadeAction.css({opacity:1, transition:'all .4s .1s'});
                    $this.resizePhotoWithAcrion(aH);
                }
            }
            $this.isFirstShowPhotoGallery = false;
        }else{
            setTimeout($this.resizePhotoWithAcrion,10)
        }


    }

    this.setDataReports = function(pid){
        if(pid){
            if (!in_array($this.guid, $this.galleryPhotosInfo[pid]['reports'].split(','))) {
                if(trim($this.galleryPhotosInfo[pid]['reports'])){
                    $this.galleryPhotosInfo[pid]['reports'] +=','+$this.guid;
                }else{
                    $this.galleryPhotosInfo[pid]['reports'] =$this.guid;
                }
            }
            $this.$galleryPhotosReport.stop().fadeOut($this.durAction,function(){
                $this.$galleryPhotosReport.removeClass('response_loader')
            });
        }
    }

    this.openReport = function() {
        Profile.openReport($this.uid, $this.galleryCurrenPhotoId);
    }

    this.resizePhotoWithAcrion = function(aH){
        var aH=aH||pp_gallery_photo_one_cont.css('height', 'auto').height();
        if($this.hPhotoOneCont!=aH){
            pp_gallery_photo_one_cont.height($this.hPhotoOneCont);
            pp_gallery_photo_one_cont.animate({height:aH},{duration:300,step:function(){$(this).css('overflow', 'visible')}});//$photoOneCont.css({height:'auto'})
        }else{
            pp_gallery_photo_one_cont.css({height:'auto'});
        }
    }

    this.setPrepareFirstShowing = function(isNewStatus,pid,mod){
        var mod=mod||0;
        if (isNewStatus) {
            pp_gallery_photos_footer_empty.hide();
            pp_gallery_request_access.css({opacity:'1'}).hide();
        } else {
            $('.comments_hidden', pp_gallery_photos_footer_empty).fadeIn(300);
            if(mod){
                pp_gallery_request_access.fadeTo(400,1);
            }else{
                pp_gallery_request_access.animate({opacity:'1'},400);
            }
        }
    }

    this.setWidthDesc = function(){
        var w;
        if(pp_gallery_photos_photo_not_checked.is(':visible')){w=525}
        else if(pp_gallery_photos_make_profile.is(':visible')){w=590}
        else if($('#gallery_videos_make_profile').is(':visible')){w=590}
        else{w=730}
        pp_gallery_photos_desc.width(w);
    }

    this.setControls = function(pid){
        if($this.countAllPhoto()>1){
            pp_photo_show_arrows.fadeTo(600,1);
            pp_gallery_photo_one.css('cursor','pointer').attr('title', $this.langParts.next_photo);
            pp_photos_carousel_link.css('cursor','pointer');
            $('#pp_photos_carousel_link_'+pid).css('cursor','default');
        }else{
            pp_photo_show_arrows.hide();
            pp_gallery_photo_one.css('cursor','default').attr('title','');
            pp_photos_carousel_link.css('cursor','default');
        }
    }

    /*this.setNotLockedUser = function(locked){
        $this.notLockedUser = locked;
    }*/

    this.getLabelLoadMoreComments = function(){
        var load_more_link=$('#gallery_photos_load_comments'),
            count=$('[id ^= "gallery_photos_comment_"]:hidden').length;
        if (count>0) {
            load_more_link.html($this.langParts.load_more_comments.replace(/\{count\}/, count));
        } else {
            $('#pp_gallery_photos_list_comment_items').css('border-bottom','none');
            load_more_link.hide();
        }
    }
    /* SHOW */

    /* Gallery */
    this.getDataAjax = function(res) {
        var obj = jQuery.parseJSON(res),
            data = (obj.status) ? obj.page : false;
        if (!data) return false;
        if (!$this.isAuthOnly(data)) return false;
        return data;
    }

    this.isAuthOnly = function(value) {
        if (redirectRequiresAuth(value)) {
            return false;
        }
        return true;
    }

    this.getSrc = function(src){
        var t=src.search('v=');
        if(t!=-1)src=src.substring(0,t-1);
        return src;
    };

    /* Replace rotate */
    this.photoVersion = 0;
    this.replaceRotatePhoto = function(pid, sel) {
        sel=sel||'[data-main-photo='+pid+'], #gallery_carousel_'+pid;
        var i=0;
        $(sel).each(function(){
            var $img=$(this), v=+new Date, image=new Image();
            var size=$img.data('photo-size'),src;
            src=size==undefined?$img.attr('src'):urlFiles+$this.galleryPhotosInfo[pid]['src_'+size];
            src=$this.getSrc(src)+'?v='+(v+i++);
            image.onload = function(){
                if(size==undefined){
                    $img[0].src=src;
                }else{
                    $img.css('background-image','url('+src+')');
                }
            }
            image.src=src;
        })
    }

    this.replaceRotatePhotoToWall = function(pid) {
        $this.photoVersion++;
        var $userPhotos=$('.wall_item_user_photo_'+$this.guid), v=+new Date;
        v='?v='+v;
        if($userPhotos[0]){
            var src=$userPhotos.data('url')+v;
            $('<img src="'+src+'"/>').load(function(){
                $userPhotos.css('background-image','url('+src+')');
                var $photoPost=$('#wall_photo_'+pid);
                if($photoPost[0]){
                    $photoPost.children('img')[0].src=src;
                }
            })
        }
        var $userLikePhoto=$('.wall_like_user_pic_'+$this.guid);
        if($userLikePhoto[0]){
            var src=$userPhotos.data('url')+v;
            $('<img src="'+src+'"/>').load(function(){
                $userLikePhoto.css('background-image','url('+src+')');
            })
        }
    }
    /* Replace rotate */

    this.checkUploadPhotoToSeePhotos = function() {
        if($this.isUploadPhotoToSeePhotos){
            if($this.guid*1) {
                confirmCustomRedirect($this.url_profile+'?show=photos', $this.langParts['please_upload_photo_to_see_photos'],false,1);
            } else {
                redirectToLogin();
            }
            return true;
        }
        return false;
    }


	this.isNeedLiveUpgrade = function(pid){
		if (!userAllowedFeature['live_streaming'] && typeof $this.galleryPhotosInfo[pid] != 'undefined'){
			var data=$this.galleryPhotosInfo[pid];
			if (!data['is_video'])return false;
			if (data['live_id'] && data['user_id'] != $this.guid) {
				confirmCustom(l('watch_pasts_stream_need_upgrade'), function(){
					redirectUrl(urlPagesSite.upgrade)
				},l('alert_html_alert'));
				return true;
			}
		}
		return false;
	}

	this.checkNeedLiveCreditAction={};
	this.liveViewAllowed = false;
	this.checkNeedLiveCredit = function(pid, call, e){
		$this.liveViewAllowed = false;

		var dataMedia=$this.galleryPhotosInfo[pid]||$this.galleryMediaDataListPage[pid];
		if (typeof dataMedia == 'undefined' || !dataMedia['is_video']) {
			call();
			return;
		}

		if (dataMedia['live_id']
			&& upgrade.live_price && dataMedia['user_id'] != $this.guid) {

			var vid=dataMedia['video_id'];
			if(isPlayerNative){
				var vidCur=$this.galleryCurrenPhotoId+'';
				vidCur=parseInt(vidCur.replace('v_','')),
				player=videoPlayers[vidCur+'_gallery'];
				if(player)player.pause();
			} else if(typeof $this.videoPlayer =='object' && typeof $this.videoPlayer.dispose == 'function') {
				$this.videoPlayer.pause();
			}

			if ($this.checkNeedLiveCreditAction[vid])return;
			$this.checkNeedLiveCreditAction[vid]=1;
			var $targ=e?$(e.target):[], $layer=[], isLayer=false;

			/* Loader */
			if ($targ[0]) {
				var bl={1:'#pp_photos_carousel_link_v_'+vid, 2 : '[data-offset-id="v_'+vid+'"]', 3:'.list_videos_image_'+vid},
					$bl, $blC;
				for (var k in bl) {
					$bl=$(bl[k]);
					$blC=$targ.closest(bl[k]);
					isLayer=k == 3;
					if ($targ[0] == $bl[0]) {
						$layer=$bl;
						break;
					} else if ($blC[0]) {
						$layer=$blC;
						break;
					}
				}
				if ($layer[0]) {
					if (isLayer) {
						$layer=$layer.find('.layer_action_list');
						$layer.addClass('to_show').addChildrenLoader();
					} else {
						$layer.addChildrenLoader();
					}
				}
			}
			/* Loader */
			$this.liveViewAllowed = function(){
				$.post(url_ajax,{cmd:'live_stream_paid'},function(res){
					var data=checkDataAjax(res);
					if(data!==false){
						closePopupUpdate('#pp_boost_ajax',false,call);
					} else {
						closePopupUpdate('#pp_boost_ajax',false,function(){alertServerError()})
					}
				})
			}

			$.post(url_ajax+'?cmd=get_available_credits',{}, function(res){
				var data=checkDataAjax(res);
				if (data){
					var balance=data*1;
					if(balance<$this.live_price){
						// Buy credits
						var msg=l('you_have_no_enough_credits');
						if(typeof isInAppPurchaseEnabled != 'undefined' && !isInAppPurchaseEnabled){
							msg += '<br>'+l('buy_credits');
							alertCustom(msg, l('alert_html_alert'));
						}else{
							confirmCustom(msg,function(){
								setTimeout(function(){
									upgrade.requestIncreasePopularity('pp_payment','live_stream_past');
								},350)
							},l('alert_html_alert'),l('alert_html_ok'),l('buy_credits'))
						}
					} else {
						//This service costs *** credits
						upgrade.requestIncreasePopularity('pp_payment','live_stream_past');
					}
				}else{
					alertServerError(true)
				}

				$this.checkNeedLiveCreditAction[vid]=0;
				if ($layer[0]) {
					if (isLayer) {
						$layer.removeChildrenLoader().removeClass('to_show');
					} else {
						$layer.removeChildrenLoader();
					}
				}

			})

			//call();
			return;
		}
		call();
	}

    //this.isCreatPopupGallery = false;
    this.isShowGalleryPhoto = false;
    this.isFirstShowPhotoGallery = false;
    this.firstShowPhoto = 0;
    this.showGallery = function(pid, uid, notLockedUser, isVideo, gender, e) {
        pid=pid*1;
        if(!pid)return;
        var pidInt=pid;
        if(isVideo){
            pid='v_'+pid;
        }
        $this.isVideo=isVideo||false;
        //console.log(isVideo);
        if($this.isVideo){
            //$this.videoPlayer=videojs('my-video');
        }
        if (!checkLoginStatus()) {
            return false;
        }
        //console.log(pid);
        notLockedUser=notLockedUser||$this.notLockedUser;
        if (!notLockedUser) {
            alertCustom($this.langParts.not_see_this_gallery,true,ALERT_HTML_ALERT);
            return false;
        }

        //var pid=parseInt(el.data('offsetId'));
        if ($this.isShowGalleryPhoto || $this.checkUploadPhotoToSeePhotos() || !pid) return false;

		if ($this.isNeedLiveUpgrade(pid)) return false;

		var fnStart=function(){
			//+/$this.isShowGalleryProcess = true;
			$this.isShowGalleryPhoto=true;
			$this.firstShowPhoto=0;
			$this.isFirstShowPhotoGallery=false;

			$this.isWall=currentPage=='wall.php';

			//+$this.countAllPhoto()!=0&&/
			$('#loader_rate_photo_gallery').hide();
			var $ppGalleryPhotos=$('#pp_gallery_photos_content'),
				$ppGalleryPhotoOneImg=$('#gallery_photo_one_img'),
				$contrRequestAccess=$('.request_access',$ppGalleryPhotos),
				isPublic=false;
			/* Prepare */
			$ppGalleryPhotos[isVideo?'addClass':'removeClass']('pp_video_gallery');

			if (!isVideo) {
				var photoInfo=$this.galleryPhotosInfo[pid]=$this.galleryMediaData[pid]||$this.galleryPhotosInfo[pid];
				$this.setData({
					fuid : photoInfo.is_friend,
					uid : photoInfo.user_id,
				});
				isPublic=$this.isPublic(pid);
				if(isPublic){
					$contrRequestAccess.hide();
				} else {
					var marked=$this.langParts.marked_photos_private;
					if(gender)marked=$this.langParts['marked_photos_private_'+gender];
					$('#request_private_title').text(marked.replace(/\{count\}/, $this.counter.private));
				}
				var nextLang=$this.langParts.next_photo;
				var prevLang=$this.langParts.prev_photo;
				$("#photo_show_next")[0].title=photoInfo.prev_title||nextLang;
				$("#photo_show_prev")[0].title=photoInfo.next_title||prevLang;
			} else {
				$contrRequestAccess.hide().css('opacity',1);
			}

			/* Prepare */

			/* Open */
			/*if(!$this.isCreatPopupGallery){
				$this.isCreatPopupGallery=false;
				pp_gallery_photos.modalPopup({css: {margin:0}, shCss:{opacity: .7}, wrCss:{overflowY: 'scroll'}}).open();
			}else{pp_gallery_photos.open()}*/
			/* Open */

			$this.$ppGalleryPhotos.open();
			var $loaderBox = $('.bl_photo_one', '#pp_gallery_photo_one_cont').removeClass('ready');

			stopAllPlayers();

			if (!isVideo) {
				var isLoad=false, cmd='pp_profile_gallery_photo',
					$photoOneCont=$('#pp_gallery_photo_one_cont');
				$this.hPhotoOneCont=$photoOneCont.height();
				$photoOneCont.css({height:$this.hPhotoOneCont});
				$ppGalleryPhotoOneImg.fadeTo(0,0).one('load', function(){
					isLoad=true;
					$loaderBox.addClass('ready');//.css({background:'none'});

					$ppGalleryPhotoOneImg.fadeTo(400,1,function(){
						$this.isFirstShowPhotoGallery = true;
						if($this.firstShowPhoto){
							$this.showActionsWithPhoto($this.firstShowPhoto);
						}
						setTimeout(function(){
							var hd=95,
								d=($photoOneCont[0].offsetHeight-hd)/2+hd-window.innerHeight/2,
								$wr=$photoOneCont.closest('.pp_wrapper');
							if(d>0&&d!=$wr[0].scrollTop){
								var t=Math.round(Math.sqrt(Math.abs(d))*70);
								if(t<500)t=500;if(t>750)t=750;
								//console.log(t,d,$wr[0].scrollTop);
								$wr.animate({scrollTop:d},t,'easeInOutCubic')
							}
						},100)
					})
				})[0].src = photoInfo ? $this.url_files+photoInfo.src_b : $this.url_main_images+'empty.png';
				//var urlLoader='url('+$this.url_main_images+'lazy_loader_bl.gif) center / auto  no-repeat';
				//pp_gallery_photo_one_img[0].complete||
				$("#pp_photo_carousel_box").removeClass('is_video');
			} else {
				//$loaderBox.css({background:$this.urlLoader});
				//$('.bl_photo_one_cont', '#pp_gallery_photo_one_cont').hide();
				var cmd='pp_profile_gallery_video';
				$("#pp_photo_carousel_box").addClass('is_video');
				$this.showCurrentPhoto(pid,1,1);
				$this.firstShowPhoto = pid;
			}

			$.post($this.url_ajax,{cmd:cmd, uid:(uid||$this.uid), photo_id:pidInt},function(res){
				var data=checkDataAjax(res);
				if(data && $this.isShowGalleryPhoto){
					data=$(data);
					data.filter('#pp_photo_whose_photos').replaceAll($ppGalleryPhotos.find('.fl_left')).fadeTo(600,1);
					$('#pp_photo_carousel').remove();
					data.filter('#pp_photo_carousel').fadeTo(0,0).appendTo('#pp_photo_carousel_box').fadeTo(0,1);
					//pp_gallery_private_title.html(data.filter('.request_access_title').html());
					//$ppGalleryPhotoOneImg.attr('title', $this.langParts.next_photo);
					$('#request_access_action').replaceWith(data.filter('.gallery_photo_action').html());
					$ppGalleryPhotos.find('.photo_funk').replaceWith(data.filter('.cont_footer').html());

					isVideo && setTimeout(function(){//Fix live stream finished
						$this.showReportPhoto(pid);
					},100)
				}
				//$this.isShowGalleryProcess = false;
			})
		}


		$this.checkNeedLiveCredit(pid, fnStart, e);
    }

    this.openGalleryOnTheWall = function(pid, uid, notLockedUser, gender) {
        $('#pp_gallery_photos_content').empty().html($this.sourceGalleryHtml);
        setTimeout(function(){$this.showGallery(pid, uid, notLockedUser, false, gender)},100);
    }

    this.openGalleryOnTheWallVideo = function(pid, uid, notLockedUser, startParams, e) {
        $this.startVideoParams=startParams || {};
        $('#pp_gallery_photos_content').empty().html($this.sourceGalleryHtml);
        setTimeout(function(){$this.showGallery(pid, uid, notLockedUser, true, false, e)},100);
    }

    this.getIdVideo = function(pid) {
        pid=''+pid;
        return parseInt(pid.replace('v_',''));
    }

    this.openGalleryId = function(pid,e) {
        $('#pp_gallery_photos_content').empty().html($this.sourceGalleryHtml);
        var s=''+pid;
        if(s.indexOf('v_')!==-1){
          pid=parseInt(s.replace('v_',''));
          var isVideo=true;
        } else {
            var isVideo=false;
        }
        $this.isVideo=isVideo;
        setTimeout(function(){$this.showGallery(pid,null,null,isVideo,false,e)},100);
    }

    this.openGallery = function(el) {
        $('#pp_gallery_photos_content').empty().html($this.sourceGalleryHtml);
        var s=''+el.data('offsetId');
        if(s.indexOf('v_')!==-1){
          var pid=parseInt(s.replace('v_',''));
          var isVideo=true;
        } else {
            var pid=parseInt(el.data('offsetId'));
            var isVideo=false;
        }
        $this.isVideo=isVideo;
        setTimeout(function(){$this.showGallery(pid,null,null,isVideo)},100);
    }

    this.closeGallery = function() {
        $this.$ppGalleryPhotos.close();
        $this.isShowGalleryPhoto=false;
        $this.isFirstShowPhotoGallery = false;
        $this.firstShowPhoto = 0;
        if($this.isVideo&&isPlayerNative){
            setTimeout(function(){$('.bl_video_one_cont', '#pp_gallery_photo_one_cont').find('video, object, script').remove()},120);
        }
        if(typeof $this.videoPlayer =='object' && typeof $this.videoPlayer.dispose == 'function'){
            $this.videoPlayer.pause();
        }
        return false;
    }


    this.setVideoPlayer = function(pid) {
        var id=pid.replace('v_', ''), params=$this.startVideoParams;
		if (params.id!=id) params={};
        var src=params.poster||($this.url_files+$this.galleryPhotosInfo[pid]['src_src']),
            $media=$('.bl_video_one_cont', '#pp_gallery_photo_one_cont'), d=300, loaded;

        $('.bl_photo_one', '#pp_gallery_photo_one_cont').removeClass('ready');
		if(isPlayerNative){
            var $videoNative=$('.video_native', $media);
            if(!$videoNative[0]){
                $videoNative=$($this.galleryPhotosInfo[pid]['html_code']).hide();
                $media.prepend($videoNative);
            }else{
                $videoNative.stop().fadeTo(400,0,function(){
                    $('>:not(#video_loader)',$media).remove();
                    $media.prepend($this.galleryPhotosInfo[pid]['html_code']);
                    //$loaderBox.addClass('ready');
                })
            }
            $this.applyNativePlayerParams(params);
            loaded=1;
		}else{
            var srcV=params.src||($this.url_files+$this.galleryPhotosInfo[pid]['src_v']),
                frmt=/.+\.([^\?#]+)/.exec(srcV)[1];//$this.galleryPhotosInfo[pid]['format']
			if (!$this.videoPlayer || !$this.videoPlayer.dispose || !$('#video-js', $media)[0]){ // || $this.currentFormatVideo!=frmt){
                if ($this.videoPlayer && $this.videoPlayer.dispose){
					$this.videoPlayer.dispose();
					d=650;
				}
				loaded=1;
				$media.prepend($this.getVideoCode(src, srcV));//, frmt));

				$this.currentFormatVideo=frmt;
				$this.videoPlayer=videojs('#video-js').volume(getVolumeVideoPlayer())
				.on('fullscreenchange', function() {
					$(".main").toggle();
					$this.$ppGalleryPhotos.data('popup').toggleClass('full-screen-mode');
				}).on('ended', function() {
					var th=this;
                    setTimeout(function(){
                        th.load();
                        th.pause();
                    },250)
				}).on("volumechange",function(){
					if(this.muted()){
						this.volume(0);
					}
					setCookie('videojs_volume', $this.videoPlayer.volume());
				}).on('loadedmetadata', function(){this.controls(true)});
			}
			$('#video-js', $media).addClass('hidden').oneTransEnd(function(){
				if (!$(this).is('.hidden')) return;
				loaded=2;
				$this.videoPlayer.pause();
				if (img && img[0].complete) img.load()
			});
		}
		//to move in the event -> .on('ready')
		var img=$('<img />').on('load',function(){
            if (!loaded) return;
			loaded=0;
			$('#video-js', $media).removeClass('hidden');
			if (isPlayerNative) {
                $videoNative.delay(d).fadeTo(400,1);
            } else {
                //in the chain of functions does not work in chrome(Mazilu error in the console), each separately connected
                $this.videoPlayer.poster(src).src({type: "video/"+frmt, src:srcV});
                $this.videoPlayer.controls(!$this.autoPlayVideo||params.paused);
                $this.videoPlayer.currentTime(params.currentTime);
                $this.videoPlayer[($this.autoPlayVideo&&params.currentTime!==0||params.currentTime)?'play':'pause']();
                if (params.currentTime) $this.videoPlayer[(params.paused)?'pause':'play']();
            }
			//$.post($this.url_ajax+'?cmd=increase_view_count_video',{vid:id}, checkDataAjax);
            $this.startVideoParams={};
		}).prop('src',src);

        if(window.history && history.pushState) {
            var link=location.href.split('#');
            link=link[0]+'#site_video:'+id;
            history.replaceState(history.state, document.title, link);
        }
        //$('.bl_video_one_cont', '#pp_gallery_photo_one_cont').show();
    }


    this.getVideoCode = function(src,srcV, frmt) {
        return '<video id="video-js" class="video-js vjs-default-skin hidden" preload="auto" width="807" height="454" data-setup="{&quot;example_option&quot;:true}" />'// src="' + srcV + ' poster="' + src + '""
		//type="video/'+frmt+'" /> \n' +
    }

    this.applyNativePlayerParams = function(params) {
        var player=videoPlayers[params.id+'_gallery'];
        if(player){
            player.currentTime=params.currentTime;
            player[($this.autoPlayVideo&&params.currentTime!==0||params.currentTime)?'play':'pause']();
            if (params.currentTime) player[(params.paused)?'pause':'play']();
        }
    }

    this.photoRotateInit = {};

	this.isVideoData = function(id) {
		var s=''+id;
		return s.indexOf('v_')!==-1;
	}

    $(function(){

        $this.urlLoader='url('+$this.url_main_images+'lazy_loader_bl.gif) center / auto  no-repeat';
        preloadImages(
                $this.url_main_images+'carousel_bg.gif',
                $this.url_files+'urban_private_photo_b.png');

        //+/var htmlGallery='';
        if ($this.guid) {
            $this.$ppGalleryPhotos=$('#pp_gallery_photos_content').modalPopup({css:{margin:0}, shCss:{opacity: .7}, wrCss:{overflowY: 'scroll'}});
            $this.sourceGalleryHtml=$('#pp_gallery_photos_content').html();
        }

        $('body').on('click', '.pp_wrapper, #gallery_photo_one, \n\
                               [data-offset-id], #pp_gallery_photos_close, .pp_photos_carousel_item, .icon_rotate, a.timeline_photo_comment', function(e){
            var el=$(this),pid=el.closest('.frame').attr('id'),target=$(e.target);

			if(el.is('.timeline_photo_comment')){
				return false;
			}

            if(el.is('.pp_photos_carousel_item')){
                var pid=$(this).find('.pp_photos_carousel_link').data('galleryCarouselItem');
                if(pid!=$this.galleryCurrenPhotoId){$this.showGalleryPhotoOne(pid,'0')}
                return false;
            }
            if(el.is('#gallery_photo_one')){
                if ($this.countAllPhoto()>1
                    &&!target.is('#request_private')
                    &&!target.is('#scip_private')
                    &&!target.is('#report_photo_gallery')) {
                    $this.showGalleryPhotoOne('prev_id');
                }
                return false;
            }
            if(el.is('.pp_wrapper')){
                if ($('#pp_frm_gallery_photos_desc:visible')[0]
                    &&(target.is('.cont')||target.is('.bl_photo_one')||target.is('.bl_photo_one_cont'))){
                    $this.descPhotoGallerySave();
                } else if (target.is('.pp_body')
                            && $this.isShowGalleryPhoto
                            && Profile.checkCloseReport(target)
							&& !$('.pp_upgraded:visible')[0]
                            && !$('.pp_alert:visible').last()[0]) {
                    $this.closeGallery();
                    return false;
                }
            }
            if(el.is('.icon_rotate')){
                var pid=el.data('photoId'),$item=$('#'+pid);
                $this.photoRotateInit[pid]['set']=1;
                var angle=$this.photoRotateInit[pid]['angle']+90,
                    $photo=$('#photo_'+pid).css({transform:'rotate('+angle+'deg)'});
                //console.log('angle',angle);
                $photo.addClass('set_rotate');
                $this.showLoaderAction($item);

                var fnShowBtn=function(pid,angle){
                        $this.photoRotateInit[pid]['angle']=angle;
                        $this.photoRotateInit[pid]['set']=0;
                        $this.hideLoaderAction($item);
                    },
                    fnError=function(pid,angle){
                        angle -=90;
                        $('#photo_'+pid).css({transform:'rotate('+(angle)+'deg)'});
                        $this.photoRotateInit[pid]['set']=0;
                        $this.photoRotateInit[pid]['angle']=angle;
                        fnShowBtn(pid,angle);
                        alertCustom($this.langParts['server_error_try_again'],true,ALERT_HTML_ALERT);
                    };

                var e_user_id = 0
                if($("#ua_user_id").val())
                    e_user_id = $("#ua_user_id").val()

                $.ajax({url:$this.url_ajax+'?cmd=photo_rotate',
                        type:'POST',
                        data:{photo_id:pid, angle:90, e_user_id:e_user_id},
                        beforeSend: function(){
                            //$loader.show();
                        },
                        success: function(res){
                            if (checkDataAjax(res)){
                                if(typeof($this.galleryPhotosInfo[pid])!='undefined'){
                                    var photo=$this.uid+'_'+pid,
                                        sizes=['b', 's', 'r', 'm'],
                                        preloadArr=[],i=0,url,v=+new Date; v='?v='+v;
                                    sizes.forEach(function(size,i,arr) {
                                        url='photo/'+photo+'_'+size+'.jpg'+v;
                                        $this.galleryPhotosInfo[pid]['src_'+size] = url;
                                        preloadArr[i++]=$this.url_files+url;
                                    })
                                    preloadImageInsertInDom(preloadArr);
                                }

                                $this.replaceRotatePhoto(pid);
                                //$this.replaceRotatePhotoToWall(pid);
                                if ($this.isShowGalleryPhoto && pid==$this.galleryCurrenPhotoId) {
                                    $this.replaceRotatePhoto(pid,'#gallery_photo_one_img');
                                }
                                fnShowBtn(pid,angle);
                            }else{
                                fnError(pid,angle);
                            }
                        },
                        error: function(){
                            fnError(pid,angle);
                        }
                });
                return false;
            }
        }).on('mouseenter mouseleave', '.pp_gallery_photos_rating ul li, .icon_rotate, .lazy_deferred, .pen_desc, .pic', function(e){
            var el=$(this);
            if(el.is('.pp_gallery_photos_rating ul li')){
                //if($this.requestAjaxRate[$this.galleryCurrenPhotoId])return;
                if(e.type=='mouseenter'){
                    $(this).addClass('selected').prevAll().addClass('selected');
                }else{$(this).removeClass('selected').prevAll().removeClass('selected')}
            }
            /*if(el.is('.lazy_deferred')){
                var pid=el.data('photoId'),trans='';
                if($this.photoRotateInit[pid]!=undefined){
                    if ($this.photoRotateInit[pid]['set']) return;
                    trans='rotate('+$this.photoRotateInit[pid]['angle']+'deg)';
                }
                if(e.type=='mouseenter'){
                    trans += ' scale(1.12)'
                }
                if(el.is('.is_video')){
                    $('#video_'+pid).css({transform:trans});
                } else {
                    $('#photo_'+pid).css({transform:trans});
                }
            }*/
            if(el.is('.icon_rotate')){
                var pid=el.data('photoId');
                if(e.type=='mouseenter'){
                    if($this.photoRotateInit[pid]==undefined){
                        $this.photoRotateInit[pid] = {angle:0,start:0,set:0};
                    }
                }
            }
            if(el.is('.pen_desc')){
                el.next('.edit_handle').find('.icon_edit')[e.type=='mouseenter'?'addClass':'removeClass']('hover');
            }
            if(el.is('.pic')&&el.closest('.pp_add_photo')){
                el.next('.pic_cont').find('.icon_edit')[e.type=='mouseenter'?'addClass':'removeClass']('hover');
            }

        })

        $('#tabs-2_switch').on('click',function(){
            if($this.checkUploadPhotoToSeePhotos()){
                return false;
            }
        })

        window.onbeforeunload = function (e) {
            if (!$.isEmptyObject($this.uploadFileData['private'])
                    ||!$.isEmptyObject($this.uploadFileData['public'])
                    ||!$.isEmptyObject($this.uploadFileData['video'])) {
                var message = $this.langParts.photos_will_not_be_published;
                if(typeof e =='undefined'){e=window.event}
                if(e){e.returnValue = message}
                return message;
            }
        }

    })

    return this;
}

function onClickWallComments(video_id, user_id, wall_id)
{
    var player=videoPlayers[video_id];
    if (isPlayerNative) {
        if(player.muted)player.volume=0;
        var params={id:video_id,
                    poster: player.poster,
                    paused: player.paused,
                    currentTime:player.currentTime,
        };
        player.pause();
    } else {
        if(player.muted()) player.volume(0);
        var params={
            id:video_id,
            src: player.src(),
            poster: player.poster(),
            paused:player.paused(),
            currentTime:player.pause().currentTime()
        }
    }
    Photo.openGalleryOnTheWallVideo(video_id, user_id, 1, params);
}

function onClickLiveVideoFinished(video_id, user_id, src, poster, e)
{
	var params ={
		id:video_id,
        src: src,
        poster: poster
	};
	if(isPlayerNative){
		params['currentTime'] = 1;
	}
    Photo.openGalleryOnTheWallVideo(video_id, user_id, 1, params, e);
}
