var CProfilePhoto = function(uid) {
    var $this=this;

    this.uid=uid;
    this.guid;
    this.fuid;
    this.pid=0;

    this.dur=400;

    this.videoPlayer = false;
    this.videoImage = false;
    this.videoSrc = false;
    this.videoCanPlay = false;
    this.videoPageLoaded = false;
    this.videoPlayTimeout = false;

    this.setData = function(data){
        for (var key in data) {
           $this[key] = data[key];
        }
    }

    this.galleryPhotosInfo = {};
    this.setPhotoInfo = function(pid, data) {
        if(data){
            $this.galleryPhotosInfo[pid] = data;
        }
    }

    this.initLazyLoadPhoto = function() {
        $('img.lazy_deferred','#albums_switch').lazyload({appear: function(){
            var $el=$(this),src=$el.data('src');
            $('<img src="'+src+'" />').one('load', function(){
                $el.closest('.item_cont').oneTransEnd(function(){
                   $el[0].src=src;
                   $el.closest('.item_cont').oneTransEnd(function(){
                       $(this).addClass('init')
                   }).toggleClass('loader hide show');
                }).addClass('hide');//.addClass('show');
            })
        }, check: function(){
            return $('#albums_switch.target')[0];
        },containerScroll:$('#main'),placeholder:'',threshold:parseInt($win[0].innerWidth*.32/2)}).removeClass('lazy_deferred')
    }

    this.updatePhotoInfo = function(data) {
        $this.galleryPhotosInfo=data;
    }

    this.getPhotoDefaultId = function() {
        for (var id in $this.galleryPhotosInfo) {
            if($this.galleryPhotosInfo[id]['default']=='Y'){
                return id;
                break;
            }
        }
        return 0;
    }

    this.updatePhotoInfoDefault = function(pid,info){
        info=info||$this.galleryPhotosInfo;
        for (var id in info) {
            info[id]['default']=(id==pid)?'Y':'N';
        }
    }

    this.replacePhotoMainChangeGander = function(gender) {
        if(gender!=$this.gender)$this.gender=gender;
        if(!$this.photosNumber)$this.replacePhotoDefault(-1);
    }

    this.sizePhotoDefault={bm:1};
    this.replacePhotoDefault = function(pidDo){
        var pidD=$this.getPhotoDefaultId();
        if(pidDo==pidD)return;
        if(pidD&&!$this.galleryPhotosInfo[pidD])return;
        for (var s in $this.sizePhotoDefault) {
            var $photos=$('.main_photo_'+s);
            if($photos[0]){
                var url=url_files+'impact_mobile_nophoto_'+$this.gender+'_'+s+'.png';
                if(pidD)url=url_files+$this.galleryPhotosInfo[pidD]['src_'+s];
                var img=new Image();
                img.onload = function(){
                    if($photos.closest('.bl_profile_pic')){
                        if(pidD)$jq('#add_photo_main').fadeOut(200);
                        else $jq('#add_photo_main').fadeIn(200);
                        var clP=pidD?'':'empty_photo';
                        $('<img class="main_photo_bm '+clP+' to_hide" src="'+url+'" alt="">')
                        .insertBefore($photos).removeClass('to_hide');
                        $photos.oneTransEnd(function(){
                            $photos.remove()
                        }).addClass('to_hide')
                    }else{
                        $photos.attr('src',url)
                    }
                    delete img;
                }
                img.src=url;
            }
        }
    }

    /* Action */
    this.isAction = function(pid) {
        if (!$this.galleryPhotosInfo[pid]) {
            return true;
        }
        if (!$this.galleryPhotosInfo[pid].action) {
            $this.setAction(pid,true);
            return false;
        }
        return true;
    }

    this.setAction = function(pid,status) {
        if ($this.galleryPhotosInfo[pid]) {
            $this.galleryPhotosInfo[pid].action=status;
        }
    }

    this.showLoaderAction = function($el,pid) {
        var $layer=$el.find('.block_layer_action');
        if($this.guid != $this.uid && pid
            && $this.galleryPhotosInfo[pid]
               && !$this.isPublic(pid,$this.galleryPhotosInfo[pid])){
            $el.find('.item_cont').addClass('to_img_hide');
        }
        if($layer[0]){
            $layer.addClass('to_show');
        }else{
            $('<div class="block_layer_action"></div>')
            .append(getLoader('photo_action',false,true,true)).prependTo($el).delay(1).toggleClass('to_show',0);
        }
    }

    this.hideLoaderAction = function($el,remove) {
        $el.find('.item_cont').removeClass('to_img_hide');
        if(remove||0){
            $el.find('.block_layer_action').remove();
        }else{
            $el.find('.block_layer_action').oneTransEnd(function(){
                $(this).remove();
            }).removeClass('to_show')
        }
    }

    this.serverActionError = function($el,pid) {
        $this.hideLoaderAction($el);
        serverError();
        $this.setAction(pid,false);
    }

    this.confirmPhotoDelete = function(pid) {
        $this.pid=pid;
        showConfirm(l('this_action_can_not_be_undone'), $this.photoDelete);
    }

    this.photoDelete = function() {
        closeAlert();
        var pid=$this.pid;
        if($this.isAction(pid))return;
        var $el=$('#photo_item_'+pid),pidD=$this.getPhotoDefaultId();
        $.ajax({type:'POST',
                url:url_main+'profile_photo.php?cmd=photo_delete',
                data:{ajax:1,photo_id:pid,get_photo_id:0},
                beforeSend: function(){
                    $this.showLoaderAction($el);
                },
                success: function(res){
                    var data=checkDataAjax(res);
                    if (data!==false){
                        $this.photosCount--;
                        $el.oneTransEnd(function(){
                            $this.updatePhotoInfo(data['gallery_info']);
                            userAllowedFeature['min_number_upload_photos'] = data['min_number_upload_photos'];
                            $(this).remove();
                            if(pidD==pid)$this.replacePhotoDefault(pidD);
                            $jq('#main').scroll();
                        },'width').addClass('to_collapse');
                    }else{
                        $this.serverActionError($el,pid);
                    }
                },
                error:function(){
                    $this.serverActionError($el,pid);
                }
        });
    }

    this.photoAccess = function(pid) {
        if($this.galleryPhotosInfo[pid]['default']=='Y'
            &&$this.galleryPhotosInfo[pid]['private']=='N'){
            showAlert(l('profile_picture_can_only_be_public'));
            return;
        }
        if($this.isAction(pid))return;
        var $el=$('#photo_item_'+pid);
        $.ajax({type:'POST',
                url:url_main+'profile_photo.php?cmd=set_photo_private',
                data:{ajax:1,photo_id:pid},
                beforeSend: function(){
                    $this.showLoaderAction($el);
                },
                success: function(res){
                    var data=checkDataAjax(res);
                    if(data){
                        var newStatus=$this.galleryPhotosInfo[pid]['private']=='N'?'Y':'N';
                        $this.galleryPhotosInfo[pid]['private']=newStatus;
                        $el.removeClass('to_collapse to_expand').oneTransEnd(function(){
                            $el.find('.icon_status').toggleClass('unlock lock');
                            $this.hideLoaderAction($el,true);

                            if(data.indexOf('photo_approval')!==-1){
                                var $check=$('<div class="not_checked_photo"><i class="fa fa-clock-o" aria-hidden="true"></i></div>');
                                if (!$el.find('.not_checked_photo')[0]) {
                                    $el.find('.icons_photo').before($check);
                                } else {
                                    $el.find('.not_checked_photo').removeClass('hide');
                                }
                                $this.galleryPhotosInfo[pid]['visible'] = 'N';
                            }

                            $el.insertAfter('#photo_add_'+(newStatus=='Y'?'private':'public')).oneTransEnd(function(){
                                $el.removeClass('to_expand');
                                if(data.indexOf('set_default')!==-1){
                                    $this.updatePhotoInfoDefault(pid);
                                    $this.replacePhotoDefault(-1);
                                }
                                $this.setAction(pid,false);
                                $jq('#main').scroll();
                            }).delay(10).toggleClass('to_collapse to_expand',0);
                        },'width').addClass('to_collapse')
                    }else{
                        $this.serverActionError($el,pid);
                    }
                },
                error:function(){
                    $this.serverActionError($el,pid);
                }
        })
    }

    this.photoRotateInit={};
    this.photoRotate = function(pid) {
        if($this.isAction(pid))return;
        var $el=$('#photo_item_'+pid);
        if($this.photoRotateInit[pid]==undefined){
            $this.photoRotateInit[pid] = {angle:0,start:0,set:0};
        }
        $this.photoRotateInit[pid]['set']=1;
        var angle=$this.photoRotateInit[pid]['angle']+90,
            $photo=$('#photo_'+pid);
        $photo.closest('.item_cont').addClass('accel');
        $photo.css({transform:'rotate('+angle+'deg)'});
        $photo.addClass('set_rotate');

        $this.showLoaderAction($el);
        var fnShowBtn=function(pid,angle){
                $this.photoRotateInit[pid]['angle']=angle;
                $this.photoRotateInit[pid]['set']=0;
                $this.hideLoaderAction($el);
                $this.setAction(pid,false);
            },
            fnError=function(pid,angle){
                angle -=90;
                $photo.css({transform:'rotate('+(angle)+'deg)'});
                $this.photoRotateInit[pid]['set']=0;
                $this.photoRotateInit[pid]['angle']=angle;
                fnShowBtn(pid,angle);
                $this.serverActionError($el,pid);
            };
        $.ajax({url:url_main+'profile_photo.php?cmd=photo_rotate',
                type:'POST',
                data:{ajax:1,photo_id:pid,angle:90},
                beforeSend: function(){
                },
                success: function(res){
                    if (checkDataAjax(res)){
                        var v=+new Date, size={b:1,s:1,r:1,m:1}; v='?v='+v;
                        for (var s in size) {
                            $this.galleryPhotosInfo[pid]['src_'+s]=$this.galleryPhotosInfo[pid]['src_'+s].split('?')[0]+v;
                        }
                        $this.replacePhotoDefault(-1);
                        fnShowBtn(pid,angle);
                    }else{
                        fnError(pid,angle);
                    }
                },
                error: function(){
                    fnError(pid,angle);
                }
        })
    }

    this.sendAjaxSetDefault=false;
    this.setPhotoDefault = function($btn){
        if(!$this.curPid||$this.sendAjaxSetDefault)return;
        $this.sendAjaxSetDefault=true;
        var pid=$this.curPid;
        $.ajax({type:'POST',
                url:url_ajax+'?cmd=set_photo_default',
                data:{photo_id: pid},
                beforeSend: function(){
                    $btn.addLoader();
                },
                success: function(res){
                    if (checkDataAjax(res)){
                        setTimeout(function(){$btn.removeLoader()},180)
                        $this.updatePhotoInfoDefault(pid);
                        $this.updatePhotoInfoDefault(pid,$this.actualPhotosData);
                        $this.showLinkActionPhoto($this.actualPhotosData[pid]);
                        $this.replacePhotoDefault(-1);
                        showAlertAppearDelayClose(l('this_photo_has_been_set'))
                    }else{
                        $btn.removeLoader();
                        serverError()
                    }
                    $this.sendAjaxSetDefault=false;
                }
        })
        return;
    }

    this.sendAjaxRequestPrivate=false;
    this.sendRequestPrivateAccess = function(){
        if($this.uid==$this.guid||$this.sendAjaxRequestPrivate)return;
        $this.sendAjaxRequestPrivate=true;
        $('#request_access_btn').addLoader().prop('disabled',true);
        $.ajax({type:'POST',
                url:url_ajax_mobile+'?cmd=send_request_private_access',
                data:{user_to:$this.uid},
                beforeSend: function(){

                },
                success: function(res){
                    var data=checkDataAjax(res);
                    $this.sendAjaxRequestPrivate=false;
                    if(data){
                        $('#request_access').fadeOut(200,function(){
                            $('#access_requested').fadeIn(200);
                        })
                    }else{
                        $('#request_access_btn').removeLoader().prop('disabled',false);
                        serverError()
                    }
                }
        })
    }
    /* Action */
    /* File upload */
    this.$contextUploadFile={};
    this.prepareUpload = function(type, idUpload, isVideo) {
        if(isVideo) {
            var $cnt = $('<div id="photo_item_'+idUpload+'" class="item to_collapse">'+
                        '<div class="icons_cancel_upload" onclick="clPhoto.cancelUpload(\''+idUpload+'\');">'+
                        '<span class="icon_background"><span class="icon cancel"></span></span>'+
                        '</div>'+
                        '<div class="error_upload_photo hide"><i class="fa fa-info-circle" aria-hidden="true"></i></div>'+
                        '<div class="item_cont">'+
                            '<a href="#tabs-3" onclick="return false;">'+
                                '<img id="'+idUpload+'" oncontextmenu="return false;" class="photo" src="'+urlTmplMobileImages+'1px.png" alt="" />'+
                            '</a>'+
                            '<div class="not_checked_photo hide"><i class="fa fa-clock-o" aria-hidden="true"></i></div>'+
                            '<div class="icons_photo to_hide">'+
                            '<span class="icon_background"><span class="icon cancel icon_action"></span></span>'+
                        '</div></div></div>');
            $this.showLoaderAction($cnt);
            $cnt.insertAfter('#video_add_'+type).oneTransEnd(function(){
                $cnt.removeClass('to_expand');
            }).toggleClass('to_collapse to_expand',0);
        } else {
            var icon=type=='public'?'lock':'unlock',
                $cnt = $('<div id="photo_item_'+idUpload+'" class="item to_collapse">'+
                        '<div class="icons_cancel_upload" onclick="clPhoto.cancelUpload(\''+idUpload+'\');">'+
                        '<span class="icon_background"><span class="icon cancel"></span></span>'+
                        '</div>'+
                        '<div class="error_upload_photo hide"><i class="fa fa-info-circle" aria-hidden="true"></i></div>'+
                        '<div class="item_cont">'+
                            '<a href="#tabs-3" onclick="return false;">'+
                                '<img id="'+idUpload+'" oncontextmenu="return false;" class="photo" src="'+urlTmplMobileImages+'1px.png" alt="" />'+
                            '</a>'+
                            '<div class="not_checked_photo hide"><i class="fa fa-clock-o" aria-hidden="true"></i></div>'+
                            '<div class="icons_photo to_hide">'+
                            '<span class="icon_background"><span class="icon cancel icon_action"></span></span>'+
                            '<span class="icon_background"><span class="icon '+icon+' icon_status icon_action"></span></span>'+
                            '<span class="icon_background"><span class="icon rotate icon_action"></span>'+
                        '</div></div></div>');
            $this.showLoaderAction($cnt);
            $cnt.insertAfter('#photo_add_'+type).oneTransEnd(function(){
                $cnt.removeClass('to_expand');
            }).toggleClass('to_collapse to_expand',0);
        }
        return $this.$contextUploadFile[idUpload]=$cnt;
    }

    this.showImageUpload = function(data) {
        var pid = data.id;
        if ($this.$contextUploadFile[data.id_upload] && pid) {
            var src = url_files+data.src_r,
                $cnt = $this.$contextUploadFile[data.id_upload];
            $.post(url_main+'profile_photo.php?cmd=publish_one_photo',{ajax:1,photo_id:pid,get_photo_info:1},
                function(res){
                    var data=checkDataAjax(res);
                    if (data!==false){
                        delete $this.$contextUploadFile[data.id_upload];
                        $cnt.addClass('upload');
                        $cnt.data({uploadId:pid});
                        $cnt.attr('id','photo_item_'+pid);
                        $this.galleryPhotosInfo=data['gallery_info'];
                        userAllowedFeature['min_number_upload_photos'] = data['min_number_upload_photos'];
                        var img=new Image();
                        img.onload = function(){
                            $this.hideLoaderAction($cnt);
                            $cnt.find('.not_checked_photo')[$this.galleryPhotosInfo[pid]['visible']=='Y'?'addClass':'removeClass']('hide');
                            $cnt.find('.error_upload_photo').remove();
                            $cnt.find('img').attr({src:src,id:'photo_'+pid}).end()
                                .find('.item_cont').addClass('show').addClass('init').click(function(e){
                                    clPhoto.openGalleryId(pid, $(this), e)
                                });
                            $cnt.find('.icons_photo').oneTransEnd(function(){
                                $cnt.find('.icons_cancel_upload').remove();
                            }).toggleClass('to_hide to_show',0);
                            var $icon=$cnt.find('.icon_action');
                            $icon.eq(0).click(function(){
                                clPhoto.confirmPhotoDelete(pid);
                            })
                            $icon.eq(1).click(function(){
                                clPhoto.photoAccess(pid);
                            })
                            $icon.eq(2).click(function(){
                                clPhoto.photoRotate(pid);
                            })
                            $this.replacePhotoDefault(0);
                            delete img;
                        }
                        img.src=src;
                    } else {
                        data.error=l('server_error_try_again');
                        $this.showUploadError(data);
                    }
            })
        }
    }

    this.showVideoUpload = function(data) {
        var data = data[0];
        var pid = data.id;
        if ($this.$contextUploadFile[data.id_upload] && pid) {
            var src = url_files+data.src_r,
                $cnt = $this.$contextUploadFile[data.id_upload];
            $.post(url_main+'profile_photo.php?cmd=publish_one_video',{ajax:1,photo_id:pid,get_photo_info:1},
                function(res){
                    var data=checkDataAjax(res);
                    if (data!==false){
                        delete $this.$contextUploadFile[data.id_upload];
                        $cnt.addClass('upload');
                        $cnt.data({uploadId:pid});
                        $cnt.attr('id','photo_item_'+pid);
                        $this.galleryPhotosInfo=data;
                        var img=new Image();
                        img.onload = function(){
                            $this.hideLoaderAction($cnt);
                            $cnt.find('.not_checked_photo')[$this.galleryPhotosInfo[pid]['visible']=='Y'?'addClass':'removeClass']('hide');
                            $cnt.find('.error_upload_photo').remove();
                            $cnt.find('img').attr({src:src,id:'photo_'+pid}).end()
                                .find('.item_cont').addClass('show').addClass('init').click(function(e){
                                    clPhoto.openGalleryId(pid, $(this), e)
                                });
                            $cnt.find('.icons_photo').oneTransEnd(function(){
                                $cnt.find('.icons_cancel_upload').remove();
                            }).toggleClass('to_hide to_show',0);
                            var $icon=$cnt.find('.icon_action');
                            $icon.eq(0).click(function(){
                                clPhoto.confirmPhotoDelete(pid);
                            })
                            $this.replacePhotoDefault(0);
                            delete img;
                        }
                        img.src=src;
                    } else {
                        data.error=l('server_error_try_again');
                        $this.showUploadError(data);
                    }
            })
        }
    }

    this.showUploadError = function(data) {
        if ($this.$contextUploadFile[data.id_upload]) {
            var $cnt = $this.$contextUploadFile[data.id_upload];
            $this.hideLoaderAction($cnt);
            $cnt.click(function(){
                showAlert(data.error,function(){
                    $cnt.oneTransEnd(function(){
                        $cnt.remove();
                    }).addClass('to_collapse');
                });
                return false;
            }).find('.error_upload_photo').removeClass('hide');
        }
    }

    this.clickUpload = function($file) {
        $file.next('input[type=reset]').click();
    }

    this.changeUpload = function($file) {
        $file.parent('form').find('input[type=submit]').click();
    }

    this.changeUploadMain = function($file) {
        clProfile.setProfileAlbumsTabs();
        $file.parent('form').find('input[type=submit]').click();
    }

    this.photosCount = 0;
    this.uploadLimitPhotoCount = 0;
    this.checkUploadLimit = function(){
        if(!isFreeSite && !isCurUserSuperPowers && $this.photosCount>=$this.uploadLimitPhotoCount){
            showConfirmToPage($this.upload_more_than_limit, urlPagesSite.upgrade, l('upgrade'), false);
            return false;
        }
        return true;
    }

    this.submitUpload = function($frm) {
        var e_user_id = 0
        if($("#ua_user_id").val())
            e_user_id = $("#ua_user_id").val()

        var file = $frm.find('input[type=file]'),
            fileName = file.attr('name'),
            type = $frm.data('type'),
            idUpload = +new Date+'_'+$this.guid,
            isVideo = $frm.data('is_video')||0,
            uploadPreviewFileSize = isVideo ? 'src' : 'mm',
            url = $frm[0].action +
                  '&cmd=insert&ajax=1&pending=P&size=' + uploadPreviewFileSize + '&' +
                  'file=' + fileName + '&id_upload=' + idUpload + '&is_video=' + isVideo + '&e_user_id=' + e_user_id,
            formData = new FormData(),
            error = '',
            acceptTypes='image/jpeg,image/png,image/gif',
            patt = new RegExp(/^video\/.*$/);
        $.each(file[0].files, function(i, file){
            var tpp = file.type,
                maxSize = isVideo ? $this.maxVideoSize : $this.maxFileSize;
            if(isVideo && !patt.test(tpp)){
                error = l('accept_file_types');
                return false;
            }else if (!isVideo && acceptTypes.indexOf(tpp) === -1) {
                error = l('accept_file_types');
                return false;
            }else if (file.size > maxSize) {
                error = $this['maxFileSize_' + (isVideo ? 'video' : 'photo')];
                return false;
            }
            formData.append(fileName, file);
        });
        if (error) {
            showAlert(error);
            return false;
        }
        $this.photosCount++;
        $this.prepareUpload(type, idUpload, isVideo);
        //return false;
        var xhr = new XMLHttpRequest();
        xhr.open("POST", url);
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4) {
                if(xhr.status == 200) {
                    var data = xhr.responseText;
                    data = checkDataAjax(data);
                    if (data) {
                        if (data.error) {
                            $this.showUploadError(data);
                            $this.photosCount--;
                        } else {
                            if(isVideo){
                                $this.showVideoUpload(data);
                            } else {
                                $this.showImageUpload(data);
                            }
                        }
                    }else{
                        $this.photosCount--;
                    }
                }
            }
        };
        xhr.send(formData);
        return false;
    }

    this.cancelUpload = function(id){
        showConfirm(l('cancel_upload'), function(){
            if ($this.$contextUploadFile[id]) {
                var $cnt = $this.$contextUploadFile[id];
                if($cnt.is('.upload')){
                    $this.pid=$cnt.data('uploadId');
                    $this.photoDelete();
                    return;
                }
                delete $this.$contextUploadFile[id];
                $cnt.oneTransEnd(function(){
                    $cnt.remove();
                },'width').addClass('to_collapse');
            }
        })
    }

    /* File upload */
    /* Action */
    /* Gallery */
    this.isPublic = function(pid,info){
        info=info||$this.actualPhotosData[pid];
        return (($this.guid==$this.uid||$this.fuid*1!=0||info['private']=='N'))||$this.isVideo;
    }

    this.setLoadParamPhoto = function(pid,info){
        var info=info||$this.actualPhotosData[pid];
        if(info && !info.load) {
            var img=$this.isPublic(pid,info)?$('<img src="'+url_files+info['src_bm']+'" class="hidden">'):$this.$privateBox;
            info.load=$this.display=='profile'?img:1;
        }
    }

    this.preLoadingPhotos = function(direct, n){
        if(!$this.photosNumber || $this.photosNumber<2)return;
        var direct=direct=='left'?'prev_id':'next_id',pid=$this.curPid,
            info=$this.actualPhotosData[pid];
        if (!info[direct]) return;
        for(var i=1;i<(n||4);i++) {
            info=$this.actualPhotosData[pid];
            pid=info[direct]; //console.log($this.curPid, pid, direct)
            $this.setLoadParamPhoto(pid,info);
        }
    }

    this.firstPreLoadingPhotos = function(){
        $this.preLoadingPhotos('right', 3);
        $this.preLoadingPhotos('left', 3);
    }

    /* Comments */
    this.loadMoreComments = function(limit){
        limit=limit||0;
        if($this.$photoComments.find('.loader_more_comments')[0])return;
        var $lastComment=$this.$photoComments.find('.item:last');
        if(!$lastComment[0])return;
        var $dots=$this.$photoComments.find('.message_dots'),$fa=$dots.find('.fa');
        $fa.hide();
        $dots.append(getLoader('loader_more_comments',false,false,false));
        var pid=$this.curPid,
        fnShowDots=function(){
            $dots.find('.loader_more_comments').remove();
            $fa.show();
        };

        var cmd = 'get_photo_comment';

        if($this.isVideo) {
            cmd = 'get_video_comment';
            pid = parseInt(pid.replace('v_', ''));
        }

        $.ajax({type:'POST',
            url:url_ajax+'?cmd=' + cmd + '&view=mobile&load_more=1',
            data:{photo_id:pid,last_id:$lastComment.data('cid'),limit:limit},
            beforeSend: function(){
            },
            success: function(res){
                var data=checkDataAjax(res);
                if (data!==false) {
                    if (pid!=$this.curPid) {
                        fnShowDots();
                        return;
                    }
                    var $data=$('<div>'+data+'</div>'),
                        $comments=$data.find('.item').hide(),
                        $loadMore=$data.find('.message_dots');
                    if($comments[0]){;
                        var $comment,i=0,t=300;
                        (function fu(){
                            $comment=$comments.eq(i);
                            if(!$comment[0])return;
                            if(!$('#'+$comment[0].id)[0]){
                                $comment.insertBefore($dots).slideDown(t *=.8,function(){
                                })
                            }
                            i++;fu();
                        })()
                    }
                    if($loadMore[0]){
                        fnShowDots()
                    }else{
                        $dots.slideUp(200)
                    }
                }else{
                    fnShowDots();
                    serverError();
                }
            }
        })
    }

    this.initCommentText = function(){
        if(!$this.$photoFrmPostCommentText.is('.init')&&!$this.$photoCommentsBl.is('.private')){
            $this.$photoFrmPostCommentText.val('').keydown(doOnEnter($this.postComment))
                 .autosize({isSetScrollHeight:false,callback:$this.prepareMessagesCont});
            $this.$photoFrmPostCommentText.focus(function(){
                $this.prepareCommentText()
            });
            $this.$photoFrmPostCommentText.addClass('init');
        }
    }

    this.messageTextBlH=50;
    this.prepareMessagesCont = function(ta,ta,height){
        var th=parseInt(ta)+20;
        if($this.messageTextBlH!=th){
            $this.messageTextBlH=th;
        }else{
            th=0;
        }
        if(th&&$this.$photoFrmPostCommentText.is('.init')){
            $this.prepareCommentText(th);
        }
    }

    this.prepareCommentText = function(h,dh,is){
        console.log('FOCUS');
        is=defaultFunctionParamValue(is,true);
        if(isIos&&is)return;
        var $bl=$this.$photoFrmPostComment;
        h=h||$bl.height();dh=dh||0;
        var top=$bl.offset().top+$jq('#main').scrollTop()-h-26,
            d=$win.height()-top+dh;
        $jq('#main').stop().animate({scrollTop:d},300,'easeOutCubic');
    }

    this.postComment = function(){
        var comment=$.trim($this.$photoFrmPostCommentText.val());
        if(!comment)return;
        var pid=$this.curPid;
        $this.$photoFrmPostCommentText.val('').trigger('autosize').prop('disabled',true).blur();
        $this.$photoFrmPostCommentBtn.prop('disabled',true).addLoader();
        $this.messageTextBlH=30;
        comment=emojiToHtml(comment);
        var data={comment:comment,
                  photo_id:pid,
                  photo_user_id:$this.uid,
                  private:$this.actualPhotosData[pid]['private']};
        $.post(url_ajax+'?cmd=photo_comment_add&view=mobile',data,
            function(res){
                $this.$photoFrmPostCommentText.prop('disabled',false);
                $this.$photoFrmPostCommentBtn.removeLoader();
                data=checkDataAjax(res);
                if (data!==false){
                    $('<div>'+trim(data)+'</div>').find('.item').hide().prependTo($this.$photoComments).slideDown(300,function(){
                        $this.prepareCommentText(false,$(this).height()+20,false);
                        //showAlertAppearDelayClose(l('comment_has_been_added'));
                    })
                }else{
                    serverError();
                }
        })
        return false
    }

    this.confirmDeleteComment = function(cid){
        showConfirm(l('are_you_sure'), function(){$this.deleteComment(cid)});
    }

    this.deleteComment = function(cid){
        closeAlert();
        if(!cid)return;
        var pid=$this.curPid, $comment=$('#photo_comment_'+cid);
        if($comment.is('.delete'))return;
        $comment.addClass('delete');
        $.post(url_ajax+'?cmd=photo_comment_delete&view=mobile',
              {cid:cid,user_id:$this.uid,pid:pid},
            function(res){
                var data=checkDataAjax(res);
                if (data){
                    $comment.slideUp(300,function(){
                        $(this).remove();
                        $this.loadMoreComments(1);
                    })
                } else {
                    $comment.removeClass('delete');
                    serverError();
                }
       })
    }
    /* Comments */
    /* Report */
    this.setDataReports = function(pid){
        if(pid){
            if (!in_array($this.guid, $this.actualPhotosData[pid]['reports'].split(','))) {
                if(trim($this.actualPhotosData[pid]['reports'])){
                    $this.actualPhotosData[pid]['reports'] +=','+$this.guid;
                }else{
                    $this.actualPhotosData[pid]['reports']=''+$this.guid;
                }
            }
        }
    }
    /* Report */

    this.ajaxUploadComment={};
    this.showUploadComment = function(pid){
        if(!$this.ajaxUploadComment[pid]){
            $this.ajaxUploadComment[pid]=true;
            $this.$photoComments.addClass('change_comments');
            $this.$photoFrmPostCommentText.prop('disabled', true)
            $.post(url_ajax+'?cmd=get_photo_comment&view=mobile',{photo_id:pid,last_id:0},
                function(res){
                    //console.log(pid,$this.curPid,pid==$this.curPid);
                    if(pid==$this.curPid){
                        var data=checkDataAjax(res);
                        if (data!==false) {
                            var $data=$('<div>'+data+'</div>');
                            $this.$photoComments.html($data.html());
                        }else{
                            $this.$photoComments.html('');
                        }
                        $this.$photoComments.removeClass('change_comments');
                        $this.$photoFrmPostCommentText.prop('disabled', false);
                        $this.$photoCommentsBl.removeClass('private');
                        $this.initCommentText();
                    }
                    $this.ajaxUploadComment[pid]=false;
                }
            )
        }
    }

    this.photoLoad=true;
    this.curPid=0;
    //this.curPidComment=0;
    this.show = function(direct, pid, fnPhotoLoaded){
        if(!$this.photoLoad||!$this.curPid)return false;
        var _dir=direct=='left'?'right':'left',isOneLoad=false,
            nextPid = pid||$this.actualPhotosData[$this.curPid][direct=='left'?'prev_id':'next_id'];
        if(direct==='show'){
            direct='';
            _dir='';
            isOneLoad=true;
        }
        if (!$this.actualPhotosData[nextPid]||!$this.actualPhotosData[nextPid].load) return false

        $this.photoLoad=false;
        var isPrivatePrevImg=!$this.isPublic($this.curPid);
        $this.curPid=nextPid;
        $this.$photoInfo.stop().fadeTo(0,0);

        var img0=[],pr,isPublic=$this.isPublic(nextPid);
        if($this.$image){
            img0=$this.$image.addClass(direct);
        }

        if(!isPublic)$this.photoLoad=pr=true;

        var img=$this.$image=$this.actualPhotosData[nextPid].load.removeClass(direct);
        if(isOneLoad){

        }else if(isPublic){
            $this.showUploadComment(nextPid);
        }else{
            $this.$photoCommentsBl.addClass('private');
        }
        if($this.display == 'profile' && !isOneLoad && $jq('#main')[0].scrollTop){
            var top=$jq('#main')[0].scrollTop;
            if(top<200)top=200;if(top>450)top=450;
            $jq('#main').stop().delay(750).animate({scrollTop:0},top,'easeOutCubic');
        }
        $this.$container.addClass('change_photo');
        setTimeout(function(){
            var fnOneLoad=function(){
                $this.resImg(img[0]);
                $this.$container.removeClass('change_photo');
                img.stop().show().delay(10).removeClass('left right hidden', 0).oneTransEnd(function(){
                    //$this.$container.removeClass('change_photo');
                    if(img0==$this.$image) return;
                    img.addClass('ready');
                    img0[0]&&img0.remove().removeClass(direct);
                    //$this.showMutualAttractionEncounters();
                    $this.preLoadingPhotos(direct);
                    if($this.display=='profile'&&!isPrivatePrevImg&&!isPublic){
                        setTimeout(function(){$this.$image.appendTo($this.$cont.removeAttr('style'))},10);
                        $this.$cont.css({transition: '0s', transform: 'none', opacity:1}).removeClass('left right anim');
                    }
                    if($this.display=='profile'){
                        var pidHash=location.hash;
                        if(pidHash ){
                            pidHash=pidHash.replace(/#photo-/i, '')*1;
                            if(pidHash!=(nextPid*1) && window.history && history.replaceState){
                                history.replaceState(pageHistory, curPageData.title, location.href.split('#')[0]+'#photo-'+nextPid);
                                console.log('SETHASSSSS',nextPid);
                            }
                        }
                    }
                }, 'transform');
                $this.photoLoad = true;
                if(typeof fnPhotoLoaded=='function')fnPhotoLoaded();
            }
            img.addClass(_dir).css('display', 'none').prependTo($this.$container).one('load', fnOneLoad);
            if(pr||img[0].complete)img.load();
            if(isOneLoad&&$this.display=='profile'&&!isPublic){
                setTimeout(function(){$this.$image.appendTo($this.$cont)}, 10);
            }
        }, 150);
        $this.setGalleryPhotoInfo(nextPid);
        return true
    }
    /* Gallery */
    this.responsePrepareGallery = function(hash) {
        var $tabs=$jq('#tabs-3_switch');
        if($tabs.is('.to_hide_load_page')){
            $('#layer_block_page_load_gallery').fadeTo(200,0,function(){
                $(this).remove();
            })
            $tabs.removeClass('calc_vh').fadeTo(400,1,function(){
                $tabs.removeClass('to_hide_load_page')
                     .removeAttr('style');
            })
        }else{
            clProfile.setHash(hash);
        }
    }

    this.prepareGallery = function() {
        var pid=$this.curPid;
        $this.messageTextBlH=50;
        $this.$image = $('#photo_gallery_img_box > img').on('dragstart',function(){return false}).addClass('ready');
        $this.$privateBox=$('#photo_gallery_private_box');
        $this.$photoInfo=$('.photo_gallery_photo_info');
        $this.$photoDescription=$('#photo_gallery_description');
        $this.$photoSetDefault=$('#photo_gallery_set_default');
        $this.$photoBackList=$('#photo_gallery_back');
        $this.$photoReport=$('#photo_gallery_report');
        $this.$photoCommentsBl=$('#photo_gallery_comments_bl');
        $this.$photoComments=$('#photo_gallery_comments');
        $this.$photoCommentsHidden=$('#photo_gallery_comments_hidden');
        $this.$photoFrmPostComment=$('#photo_gallery_frm_post_comment');
        $this.$photoFrmPostCommentBtn=$('#photo_gallery_frm_post_comment_send');
        $this.$photoFrmPostCommentText=$('#photo_gallery_frm_post_comment_text')
        .on('change propertychange input', function(){
            var val=$.trim(this.value);
            $this.$photoFrmPostCommentBtn.prop('disabled',!val);
        })
        if ($this.photosNumberPrivate) {
            var numberPrivate = l('number_private_photo').replace(/\{number\}/, $this.photosNumberPrivate);
            $this.$privateBox.find('.count').html(numberPrivate);
        }

        if($this.isVideo) {
            //$this.$loader=getLoader('loader_gallery_photo',false,true).insertAfter('#photo_gallery_img_box');
            $this.setVideoPlayer();
            $this.setVideoImage();
            $this.$photoInfo.fadeTo(0,0);
            $this.setGalleryPhotoInfo(pid);
            $('.video_gallery_back').click(function(){
                $this.videoPlayer.trigger('stop');
            });
            if($this.galleryPhotosInfo[pid]){
                $this.galleryPhotosInfo[pid]['description']=$this.actualPhotosData[pid]['description'];
            }

            setTimeout(function(){
                $this.responsePrepareGallery('#video-'+$this.getIdVideo(pid));
                $this.initCommentText();
            },200);

            if(navigator.userAgent.match('AppWebview')){
                $doc.off('fullscreenchange webkitfullscreenchange')
                .on('fullscreenchange webkitfullscreenchange',function(){
                    $('.bl_profile.bl_gallery_cont')[isFullScreen()?'addClass':'removeClass']('will_change_initial')
                })
            }
        } else {
            $this.setLoadParamPhoto(pid);
            $this.firstPreLoadingPhotos();
            $this.activateSwipeGallery();

            $this.initCommentText();
            $this.show('show',pid,function(){
                setTimeout(function(){
                    $this.responsePrepareGallery('#photo-'+pid);
                },100)
            })
        }

        if($this.isResizeCriOSAndFxiOS()){
            $win.on('orientationchange',$this.handlerGalleryResizeCriOSAndFxiOS);
        }else{
            $win.on('resize',$this.handlerGalleryResize);
        }
        $win.on('touchend mouseup pointerup', $this.handlerGalleryMouseup).resize();
    }

    this.isResizeCriOSAndFxiOS = function() {
        return typeof window.orientation!='undefined' && (isFxiOS||isCriOS);
    }

    this.clearGallery = function() {
        $this.setData({
            display:'',
            actualPhotosData:{},
            photosNumber:0,
            photosNumberPrivate:0,
            curPid:0
        });
        $this.$photoFrmPostCommentText && $this.$photoFrmPostCommentText[0] && $this.$photoFrmPostCommentText.off('change propertychange input');
        if($this.isResizeCriOSAndFxiOS()){
            $win.off('orientationchange',$this.handlerGalleryResizeCriOSAndFxiOS);
        }else{
            $win.off('resize',$this.handlerGalleryResize);
        }
        $win.off('touchend mouseup pointerup', $this.handlerGalleryMouseup)
        $jq('#tabs-3_switch').empty();
    }

    this.handlerGalleryResizeCriOSAndFxiOS = function() {
        setTimeout($this.handlerGalleryResize,isFxiOS?300:230)
    }

    this.handlerGalleryResize = function(e) {
        //$('.bl_one_photo', '#tabs-3_switch').height($win.height()-180);
        if($this.isVideo) {
            $this.setVideoPlayerSize();
        }
        $this.resImg($this.$image[0]);
        if($this.$photoFrmPostCommentText
            &&$this.$photoFrmPostCommentText[0]
               &&$this.$photoFrmPostCommentText.is('.init')&&$this.$photoFrmPostCommentText.is(':focus')){
            setTimeout(function(){$this.prepareCommentText()},100)
        }
    }

    this.handlerGalleryMouseup = function(e) {
        if($this.$container)$this.$container.triggerHandler(e.type)
    }

    this.resImg = function(img) {
        if(!img)return;
        var w=img.naturalWidth,
            W=Math.min(w, window.innerWidth),
            dH=window.innerHeight-50-img.naturalHeight/w*W;
        $(img)[dH<70?'addClass':'removeClass']('resize')
    }

    this.showLinkActionPhoto = function(info){
        if($this.uid==$this.guid){
            $this.$photoSetDefault.delay($this.dur).fadeTo(1,0, function(){
                if(info['visible']=='N'||info['visible']=='Nudity'){
                    $this.$photoSetDefault.hide()
                }else{
                    if (info['default']=='N'&&info['private']=='N'){

                    }else{
                        $this.$photoSetDefault.hide()
                    }
                }
            }).fadeTo(1,1)
        }else{
            $this.$photoReport.delay($this.dur).fadeTo(1,0, function(){
                if(!in_array($this.guid, info['reports'].split(',')) &&
                   $this.isPublic(0,info)){
                    $this.$photoReport.show()
                }else{
                    $this.$photoReport.hide()
                }

            }).fadeTo(1,1)
        }
    }

    this.setGalleryPhotoInfo = function(pid){
        $this.$photoBackList.delay($this.dur).fadeTo(1,0,function(){$(this).fadeTo(1,1)});
        var info=$this.actualPhotosData[pid];
        $this.showLinkActionPhoto(info);
        if (!$this.isPublic(pid))return;
        var desc = info['description'];
        if (desc){
            $this.$photoDescription.delay($this.dur).fadeTo(1,0, function(){$(this).html(desc)}).fadeTo(1,1);
        } else {
            if($this.isVideo) {
                $this.$photoDescription.remove();
            }
        }
    }

    this.openGalleryMainPhoto = function($el, e){
        if(!$(e.target).is('.main_photo_bm'))return;
        var pid=$this.getPhotoDefaultId()*1;
        if(!pid)return;
        $this.openGalleryId(pid,$el,e,true);
    }

	this.isNeedLiveUpgrade = function(pid){
		if (!userAllowedFeature['live_streaming'] && typeof $this.galleryPhotosInfo[pid] != 'undefined'){
			var data=$this.galleryPhotosInfo[pid];
			if (data['is_video'] && data['live_id'] && data['user_id'] != $this.guid) {
				showConfirmToPage(l('watch_pasts_stream_need_upgrade'), urlPagesSite.upgrade, l('upgrade'), false, 'fa-exclamation-circle');
				return true;
			}
		}
		return false;
	}

	this.checkNeedLiveCreditAction={};
	this.checkNeedLiveCredit = function(pid, call, e){
		var dataMedia=$this.galleryPhotosInfo[pid];

		if (dataMedia['is_video'] && dataMedia['live_id']
			&& $this.live_price	&& dataMedia['user_id'] != $this.guid) {
			var vid=dataMedia['video_id'];
			if ($this.checkNeedLiveCreditAction[vid]) {
				return;
			}
			$this.checkNeedLiveCreditAction[vid]=1;
			var $targ=e?$(e.target):[], $layer=[], isLayer=false;
			/* Loader */
			if ($targ[0]) {
				var sel='#photo_item_v_'+vid,
					$bl=$(sel), $blC=$targ.closest(sel);
				if ($targ[0] == $bl[0]) {
					$layer=$bl;
				} else if ($blC[0]) {
					$layer=$blC;
				}
				if ($layer[0]) {
					$layer=$targ.closest('.item').find('.block_layer_action');
					if ($layer[0]) {
						$layer.addClass('to_show');
					} else {
						$layer=$('<div class="block_layer_action"></div>')
						.append(getLoader('photo_action',false,true,true))
						.prependTo($('#photo_item_v_'+vid)).delay(1).toggleClass('to_show',0);
					}
				}
			}
			/* Loader */

			var liveViewAllowed = function(){
				$.post(url_ajax,{cmd:'live_stream_paid'},function(res){
					var data=checkDataAjax(res);
					if(data!==false){
						call();
					} else {
						serverError();
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
							showAlert(msg, l('alert_html_alert'));
						}else{
							var url=urlPagesSite.upgrade+'?action=refill_credits';
							showConfirmToPage(msg, url, l('btn_buy_credits'), false, 'fa-exclamation-circle');
						}
					} else {
						//This service costs *** credits
						var msg=l('this_service_costs_credits').replace('{credit}',balance)
								.replace('{price}',$this.live_price);
						showConfirm(msg, liveViewAllowed, function(){
							var $preloadPage=$('#layer_block_page_load_gallery').fadeTo(200,0,function(){
								$(this).remove();
							})
							if ($preloadPage[0]){
								var $tabs=$jq('#tabs-3_switch');
								if($tabs.is('.to_hide_load_page')){
									$tabs.removeClass('to_hide_load_page').removeAttr('style');
									setTimeout(function(){
										location.hash='albums-video';
									},100)
								}
							}
						}, false, false, false, false, false, false, 1003);
					}
				}else{
					serverError();
				}

				$this.checkNeedLiveCreditAction[vid]=0;
				if ($layer[0]) {
					$layer.oneTransEnd(function(){
						$(this).remove();
					}).removeClass('to_show');
				}

			})

			//call();
			return;
		}
		call();
	}

    this.isVideo=false;
    this.openGalleryId = function(pid, $el, e, al, isLoadPage) {
        if(e && $(e.target).closest('.icons_photo')[0])return;
        if($('#photo_item_'+pid).find('.loader')[0]&&!al)return;
        if(!$this.checkAccessToSiteWithMinNumberUploadPhotos())return;

        if(clProfile.isUploadPhotoToSeePhotos){
            showConfirmToPage(clProfile.pleaseUploadPhotoToSeePhotos, urlPagesSite.profile_view+'?show=albums', l('ok'), false);
            return;
        }
		if($this.isNeedLiveUpgrade(pid)) return false;


		var fnStart=function(){

			!isLoadPage&&showLayerBlockPageNoLoader();

			clearTimeout($this.videoPlayTimeout);

			var $elItem=[], $bl=[];
			if (!isLoadPage && $el && $el[0]) {
				$elItem=$el.closest('.item');
				if($elItem[0]){
					$this.showLoaderAction($elItem,pid);
				}else{
					$bl=$('<div class="block_layer_main_photo"></div>')
					.append(getLoader('loader_main_photo',false,true,true)).prependTo($el).delay(1).toggleClass('to_show',0);
					if($this.guid != $this.uid && pid
						&& $this.galleryPhotosInfo[pid]
							&& !$this.isPublic(pid,$this.galleryPhotosInfo[pid])){
						$el.addClass('to_img_hide');
					}
				}
			}

			var fnHideLayerGlobal=function(){
				if($elItem[0]){
					$this.hideLoaderAction($elItem,true);
				}else if($bl[0]){
					$el.removeClass('to_img_hide');
					$bl.remove();
				}
			};

			if($this.checkIsVideoFromId(pid)){
				$this.isVideo = true;

				$this.videoCanPlay = false;
				$this.videoPageLoaded = false;

				$this.curPid=pid;

				var fnHideLayerVideo=function(){
					fnHideLayerGlobal();

					$this.setVideoPlayerSize();

					$this.videoImage && $this.videoImage[0] && $this.videoImage.css({opacity:1,transition:'opacity .5s linear'});
					$('#photo_gallery_img_box').data({clLoader:'page_load_gallery_video'}).addLoader();
					$('.css_loader', '#photo_gallery_img_box').addClass('loader');
					$('#photo_gallery_img_box.bl_video .spinner.spinnerw').css('marginTop', (-1 * $('#photo_gallery_description').height() / 2) + 'px');
					$this.videoPageLoaded = true;
					$this.videoPlay();
				}

				$.post(
					url_ajax_mobile + '?cmd=get_data_videos_gallery',
					{uid: $this.uid, video_current_id: pid, last_id: 0},
					function(res){
						var data = checkDataAjax(res);
						!isLoadPage&&hideLayerBlockPage();
						if (data !== false){
							!isLoadPage&&clProfile.setFnTabsEnd(fnHideLayerVideo);
							$jq('#tabs-3_switch').append($(data).html());
							isLoadPage&&setTimeout(fnHideLayerVideo,600)
						} else {
							fnHideLayerVideo();
							serverError();
						}
					}
				)

			}else{
				$this.isVideo = false;
				$this.curPid=pid;

				$.post(url_ajax_mobile+'?cmd=get_data_photos_gallery',{uid:$this.uid,photo_cur_id:pid,last_id:0},
					function(res){
						var data=checkDataAjax(res);
						!isLoadPage&&hideLayerBlockPage();
						if (data!==false){
							!isLoadPage&&clProfile.setFnTabsEnd(fnHideLayerGlobal);
							$jq('#tabs-3_switch').append($(data).html());
						} else {
							fnHideLayerGlobal();
							serverError();
						}
				})
			}
		}
		$this.checkNeedLiveCredit(pid, fnStart, e);
    }

    this.toProfileFromCommentsName = function(link){
        $(link).closest('.msg').find('.pic > div').click();
    }

    this.toProfileFromCommentsGallery = function(link,uid){
        uid *=1;
        var $link=$(link);
        $link.addLoader();
        if($this.guid==uid){
            var $link=$(link);
            showLayerBlockPageNoLoader();
            $link.addLoader();
            clProfile.setFnTabsEnd(function(){
                hideLayerBlockPage();
                $link.removeLoader();
            })
            clProfile.setProfileDefaultTabs();
        }else{
            goToPage(link);
        }
    }

    this.getIdVideo = function(pid) {
        pid=''+pid;
        return parseInt(pid.replace('v_',''));
    }

    this.checkIsVideoFromId = function(pid) {
        var s = '' + pid;
        return s.indexOf('v_') !== -1;
    }

    this.activateSwipeGallery = function() {
        var $container=$('#photo_gallery_img_box'),
            $cont=$('<div class="bl_img trans"/>').insertBefore($container);
        $this.$container=$container;
        $this.$cont=$cont;
        $this.$loader=getLoader('loader_gallery_photo',false,true).insertAfter($container);
        var dW=100,abs=0,_abs=0,el,_swipe;

        var selSwipe='.bl_one_photo',$blTinderInfo,
            allowPageScroll='none';
        if($this.display=='encounters'){
            selSwipe='.bl_one_photo_encounters';
        }else if($this.display == 'profile') {
            if ($this.photosNumber<2) {
                $cont.css('cursor','default');
                return false;
            }
            allowPageScroll='vertical';
        }
        if(/profile|encounters/.test($this.display)) $(selSwipe).css({overflow: 'hidden'}).swipe({
                swipeStatus:function(e,ph,dir,d,duration,c,f) {
					if (ph=='start') {
                        if($this.display=='encounters')e.preventDefault();
                        _swipe=$this.isSwipe=true;
                        $this.$userTinder[0]&&$this.$userTinder.filter('.active:visible').delay(100).fadeTo(0,0)
                        el=$this.$image.not('.hidden, .left, .right');
                        //console.log(el,el.closest($container)[0]);
                        if (el.closest($container)[0]) {
                            setTimeout(function(){if (el) el.appendTo($cont.removeAttr('style'))}, 10);
                            $cont.off(transEvent).css({transition: '0s', transform: 'none', opacity:1}).removeClass('left right anim');
                        }
                        $this.$loader.addClass('hidden');
                        $this.$container.removeClass('change_photo');
                        if($this.display=='profile'){
                            $this.toggleMoreMenu(true);
                            $this.$photoFrmPostCommentText.blur();
                        }
                        return;
                    }
                    var isAnimPhoto=/left|right/.test(dir);
                    //console.log(_swipe,ph,dir);
                    if (ph=='move'&&_swipe) { _swipe--;
                        if ($this.display=='encounters'&&!$this.photoLoad) return false;//
                        if ($this.display == 'profile'&&($this.photosNumber<2)) return false;
                        if (!el[0] && dir!='left' && !/mouse/.test(e.type)) return false;
                    }
                    if (/end|move/.test(ph)&&isAnimPhoto) {
                        var ds=f[0].end.x-f[0].start.x,
                            dr=ds>0?'right':'left',
                           _dr=ds<0?'right':'left';
                        if (ph=='move') _abs=abs;
                        abs=Math.abs(ds);
                        /* Fix IPhone*/
                        if(f[0].end.x==f[0].start.x && d){
                            abs=d;
                            dr=dir;
                            _dr=dir=='left'?'right':'left';
                            ds=dir=='left'?-1:1;
                        }
                        /* Fix IPhone*/
                        if(el && abs>1)$cont.not('.anim').removeClass(_dr).addClass(dr+' anim');
                        if(el && abs>dW*.1)$cont.not('.'+dr).removeClass(_dr).addClass(dr);
                        if(el && abs<dW*.1 && _abs>abs){
                            $cont.removeClass(dr); //tind.fadeTo(0,0)
                        };
                        //if (el && $this.display=='encounters' && $cont.is('.'+dr) && !csearch.likeToMeetData){
                           // tind[0].style.opacity=(ds<0)*1;
                            //tind[1].style.opacity=(ds>0)*1;
                        //}
                        if(abs>dW&&el){
                            $this.swipeCallback(e, dr);
                            $this.isSwipe=el=abs=_abs=0;
                            return false;
                        }
                    }
                    if (/end|cancel/.test(ph)) {
                        if ((abs>dW*.4 || _abs<abs) && el && $cont.hasClass(dr) && ph=='end') {
                            if(el&&isAnimPhoto)$this.swipeCallback(e, dr);
                        } else {
                            $this.$userTinder[0]&&$this.$userTinder.filter('.active').stop().fadeTo(0,1);
                            if(el){
                                $cont.removeClass('left right anim');
                               // tind.fadeTo(0,0)
                            }
                        }
                        $this.isSwipe=el=abs=_abs=0;
                        if (ph=='end' && d<2 && !$cont.is('.anim')) $this.swipeCallback(e);
                    }
                }, threshold:0,
                excludedElements: 'button, a, #photo_gallery_set_default, #photo_gallery_report, #request_access',
                //triggerOnTouchLeave:true,
                allowPageScroll:allowPageScroll
        })
    }

    this.swipeCallback = function(e, direct) {
        //console.log($this.display,e, direct);
        if ($this.display=='encounters' && direct) $this.likeToMeet(direct=='left'?'N':'Y');

        if(!$this.photoLoad||!$this.curPid)return;
        if($this.photosNumber<2)return;
        if (direct) {
            //if ($('.tip_alert:visible')[0])return;
            //if ($this.uid != $this.guid && $this.isBlockedUser && activePage == 'profile_view.php') return;
            if (!$this.show(direct)) $this.$cont.removeClass('left right')
            else{$this.$loader.stop().delay(200).removeClass('hidden', 0)}
        } else {
            if ($(e.target).filter('#request_access button').click()[0]) return;
            if (!$this.photoLoad || !$this.curPid) return;
            if ($this.uid != $this.guid) {
                //if ($('.tip_alert:visible')[0])return;
                //if ($this.isBlockedUser && activePage == 'profile_view.php') return;
                if (activePage == 'profile_view.php') {
                    $this.setGalleryPhotoInfo($this.curPid);
                }
            }
        }
    }

    /* Encounters */
    $this.$userTinder=[];
    this.initEncounters = function() {
        $win.on('resize',$this.handlerGalleryResize)
            .on('touchend mouseup pointerup', $this.handlerGalleryMouseup);
        $this.$image = $('#photo_gallery_img_box > img').load(function(){
            $this.handlerGalleryResize()
        }).on('dragstart',function(){return false}).addClass('ready');

        $this.$userInfo=$('.encounters_user_info').first();
        $this.$userTinder=$('.bl_tinder').first();
        $this.numberListUsers=$this.numberListUsers/2;
        if ($this.numberListUsers<1) {
            $this.numberListUsers = 1;
        }

        $this.activateSwipeGallery();
        //$this.showMutualAttractionEncounters();
        if($this.curPid){
            //if(!$this.isMutualAttractionEncounter)
            $jq('#btn_encounters_action').removeClass('to_hide');
        }else{
            $this.showOneFoundEncounters();
        }
    }

    this.clearEncounters = function() {
        $this.uid=0;
        $this.curPid=0;
        $this.$image.remove();
        $('.encounters_user').remove();
        $this.setEncountersDataCash=[];
    }

    this.updateEncounters = function($data) {
        $this.clearEncounters();
        var $userInfo = $data.find('.encounters_user');
        if($userInfo[0]){
            var $el=$('.no_one_found');
            if($el.is('.to_show'))$el.toggleClass('to_show to_hide');
            $jq('#search_switch').append($userInfo);
            $jq('#btn_encounters_action').removeClass('to_hide');
            $jq('#photo_gallery_img_box').html($data.find('#photo_gallery_img_box').html());
            //$userInfo.after($data.find('#bl_tinder'));
            $userInfo.first().show();
            $this.$userInfo=$('.encounters_user_info').first();
            $this.$userTinder=$('.bl_tinder').first();

            $this.$image = $('#photo_gallery_img_box > img').load(function(){
                $this.handlerGalleryResize()
            }).on('dragstart',function(){return false}).addClass('ready');
            $this.photoLoad = true;
        }else{
            $this.showOneFoundEncounters();
        }
    }

    this.setEncountersDataCash = [];
    this.setEncountersDataUsers = function(uid, pid, photosInfo, is, from, num, requestAjax) {
        if(typeof photosInfo[pid] == 'undefined')return;
        requestAjax*=1;
        if (num == 1 && !requestAjax) {
            $this.setEncountersUserInfo(uid, pid, photosInfo, is, from);
        } else {
            is*=1;
            var data = {
                uid:uid,
                pid:pid,
                photosInfo:photosInfo,
                is:is,
                from:from
            }
            $this.setEncountersDataCash.push(data);
            preloadImageInsertInDom([url_files+photosInfo[pid]['src_b']]);
        }
    }

    this.isMutualAttractionEncounter=0;
    this.isAttractionFrom=false;
    this.setEncountersUserInfo = function(uid, pid, photosInfo, is, from) {
        $this.uid=uid*1;
        $this.curPid=pid*1;
        $this.actualPhotosData=photosInfo;
        $this.isMutualAttractionEncounter=is*=1;
        $this.isAttractionFrom=1*!is*from;
    }

    this.showMutualAttractionEncounters = function(isM){
        if ($this.isMutualAttractionEncounter||isM) {
            $jq('#btn_encounters_action').addClass('to_hide');
            $this.$userTinder.addClass('active').stop().css({opacity:1, visibility:'visible'});
        }
    }

    this.hideTinder = function(){
        $this.$userTinder.stop().fadeTo(0,0).removeClass('active').removeAttr('style');
    }

    this.showOneFoundEncounters = function(){
        $jq('#btn_encounters_action').addClass('to_hide');
        $this.$userTinder.removeClass('active').removeAttr('style');
        var $el=$('.no_one_found');
        if($el.is('.to_show'))return;
        $el.removeClass('to_hide').delay(1).toggleClass('to_show',0);
    }

    this.showEncounters = function(pid, userInfo, dir){
        if (!pid)return;
        if ($this.actualPhotosData[pid]) {
            //$this.btnTinder.css({opacity:0});
            var url = url_files + $this.actualPhotosData[pid]['src_b'];
            $this.$image=$('<img>').addClass('hidden').one('load', function(){//.addClass(dir)
                $this.resImg(this);
                $this.$container.removeClass('change_photo');
                $(this).prependTo($this.$container).delay(300).removeClass('hidden', 0)//.removeClass('right left', 0)
                 .off('transitionend webkitTransitionEnd').oneTransEnd(function(){
                    $this.$loader.addClass('hidden');
                    //$this.showMutualAttractionEncounters();
                });
                if (userInfo[0]) {
                    setTimeout(function(){
                       $this.$userInfo.html(userInfo.html()).removeClass('to_hide');
                    },320)
                }
                $this.photoLoad = true;
            }).one('error', function(){
                console.log('Error load photo Encounters');
                $this.likeToMeetSuccess('N');
            }).attr('src', url);
        }
    }

    this.likeToMeetSuccess = function(status, dir){
        var noItems = true;
        if($this.setEncountersDataCash.length>0){
            var nextData=$this.setEncountersDataCash.shift();
            var $data = $('#encounters_user_info_'+nextData['uid']);
            if($data[0]){
                noItems = false;
                var itemInfo = $data.find('.encounters_user_info');
                $this.setEncountersUserInfo(nextData['uid'], nextData['pid'], nextData['photosInfo'], nextData['is'], nextData['from']);
                $jq('#btn_encounters_action').removeClass('to_hide');
                //$this.$userTinder.replaceWith($data.find('.bl_tinder'));
                setTimeout(function(){$this.$userTinder.html($data.find('.bl_tinder').html())},400);
                $this.showEncounters($this.curPid, itemInfo, dir||(status=='N'?'right':'left'));
                $data.remove();
            }
        }
        if(noItems){
            $this.$container.removeClass('change_photo');
            setTimeout(function(){
                $this.clearEncounters();
                $this.showOneFoundEncounters();
            },350)
        }
    }

    this.curUserLikeToMeetData=false;
    this.startLike=false;
    this.likeToMeet = function(status,el,cmd,clickBtn){
        clickBtn=clickBtn||false;
        if(clickBtn&&$this.startLike)return;
        $this.startLike=true;
        $this.cmd = cmd||'reply';
        var isFrom=$this.isAttractionFrom&&status!='N';
        if(!$this.curUserLikeToMeetData){
            $.ajax({
                url: activePage+'?cmd_enc='+$this.cmd,
                type: 'POST',
                data: {ajax : 1,
                   set_filter : 0,
                   display : 'encounters',
                   reply_enc : status,
                   uid_enc : $this.uid,
                   uids_exclude : $this.setEncountersDataCash.map(function(user){return user['uid']*1})
                },
                success: function(data){
                    data=$(data);
                    //console.log($this.setEncountersDataCash.length,$this.numberListUsers);
                    if($this.setEncountersDataCash.length<$this.numberListUsers){
                        var $users=data.find('.encounters_user');
                        if($users[0]){
                            //console.log($this.setEncountersDataCash);
                            $users.each(function(){
                                var $el=$(this);
                                if(!$('#'+$el[0].id)[0]){
                                    $jq('#search_switch').append($el.hide());
                                }
                            })
                        }
                    }
                }
            })
        }
        if (!isFrom||$this.curUserLikeToMeetData) {
            $this.photoLoad = false;
            var img=$this.$image.addClass((status=='N'?'left':'right')+' hidden')
             .oneTransEnd(function(){
                 setTimeout(function(){
                     $this.startLike=false;
                     img.remove();
                 }, 1)
            }, 'transform');
            $this.$userInfo.addClass('to_hide');
            //$this.$userTinder.fadeTo(0,0);
            $this.hideTinder();

            $this.$loader.addClass('hidden').stop().delay(200).removeClass('hidden', 0);
            $this.$container.addClass('change_photo');
            $this.likeToMeetSuccess(status);
            $this.curUserLikeToMeetData=false;
        } else {
            $this.startLike=false;
            $this.curUserLikeToMeetData=true;
            $this.showMutualAttractionEncounters(1);
            $this.$cont.removeClass('left right');
        }

    }
    /* Encounters */

    this.setVideoPlayerSize = function() {
        //var dimencions = centerItemInArea(640, 360, $('#photo_gallery_img_box').width(), $('#photo_gallery_img_box').height() - $('#photo_gallery_description').height());
        var dimencions = centerItemInArea(640, 360, $(window).width(), $(window).height() - $('#bl_header').height() - $('#photo_gallery_description').height() + 1); // one additional pixel for height because it show strange small gap at the bottom

        var cssStyles = {
            'width': dimencions['width'],
            'height': dimencions['height'],
            'minHeight': dimencions['height'],
            'marginTop': dimencions['verticalGap']
        }

        $this.videoPlayer && $this.videoPlayer[0] && $this.videoPlayer.css(cssStyles);
        $this.videoImage && $this.videoImage[0] && $this.videoImage.css(cssStyles);
    }

    this.setVideoPlayer = function() {
        $this.videoPlayer = $('#video_player');

        $this.videoPlayer.on('canplay', function(){
            $this.videoPlayer.off('canplay');
            $this.videoCanPlay = true;
            $this.videoPlay();
        });

        $this.videoPlayer.attr('src', $this.videoSrc);

        if(mobileAppLoaded) {
            $this.videoPlayer[0].muted = true;
        }

        $this.videoPlayer.load();
    }

    this.setVideoImage = function() {
        $this.videoImage = $('.video_image');
    }

    this.videoPlay = function() {

        clearTimeout($this.videoPlayTimeout);

        if($this.videoCanPlay && $this.videoPageLoaded) {
            if(mobileAppLoaded) {
                $this.videoPlayer.trigger('play');
                $this.videoPlayingShow();
            } else {
                $this.videoPlayerShow();
            }
        } else {
            if($this.isVideo) {
                $this.videoPlayTimeout = setTimeout($this.videoPlay, 500);
            }
        }
    }

    this.videoPlayingShow = function() {
        clearTimeout($this.videoPlayingTimeout);
        if($this.videoPlayer[0].currentTime >= 0.1) {
            $this.videoPlayer[0].currentTime = 0;
            $this.videoPlayer[0].muted = false;
            $this.videoPlayerShow();
        } else {
            if($this.isVideo) {
                $this.videoPlayingTimeout = setTimeout($this.videoPlayingShow, 100);
            }
        }
    }

    this.videoPlayerShow = function() {
        $this.videoImage && $this.videoImage[0] && $this.videoImage.css({opacity:0,transition:'opacity .3s linear'});
        $('#photo_gallery_img_box').removeLoader();
        $this.videoPlayer && $this.videoPlayer[0] && $this.videoPlayer.oneTransEnd(function(){
            $this.videoPlayer.css({transition:''});
        }).css({opacity:1,transition:'opacity .3s linear'});

        $this.videoPlayer && $this.videoPlayer[0] && $this.videoPlayer.trigger('play');
    }

    this.galleryBack = function(e){
        var id=$this.curPid;
        clProfile.setFnTabsEnd(function(){
            var $el=$('#photo_'+id);
            if($el[0]){
                clProfile.scrollToLastPosAlbums($el,clProfile.setCurrentHashAlbums);
            }
        })
        if($('#photo_gallery_more_menu_popup').is('.to_hide')){
            clProfile.setProfileAlbumsTabs();
        }else{
            setTimeout(function(){clProfile.setProfileAlbumsTabs()},180)
        }
    }

    this.openMoreMenuGallery = function(e){
        var $targ=$(e.target)
        if($targ.is('.bl_options')||$targ.closest('.bl_options')[0])return;
        $this.toggleMoreMenu()
    }

    this.toggleMoreMenu = function(notOpen){
        notOpen=notOpen||0;
        var $menu=$('#photo_gallery_more_menu_popup');
        if(!$menu[0]||$menu.is('.animate'))return;
        if(!$menu.is('.to_hide')){
            $menu.addClass('animate').oneTransEnd(function(){
                $menu.removeClass('animate show')
            }).delay(1).addClass('to_hide',0);
        }else if(!notOpen){
            $menu.addClass('animate show').delay(1).oneTransEnd(function(){
                $menu.removeClass('animate')
            }).removeClass('to_hide',0);
        }
        return true;
    }

    this.openReport = function(type){
        $this.toggleMoreMenu();
        clCommon.openReport(type)
    }

    this.checkAccessToSiteWithMinNumberUploadPhotos = function(){
        if (typeof userAllowedFeature!='undefined' && userAllowedFeature['min_number_upload_photos']) {
            showAlert(userAllowedFeature['min_number_upload_photos']);
            return false;
        }
        return true;
    }

    $(function(){
        if (activePage == 'profile_view.php'||activePage == 'search_results.php') {
            /*if ($.support.opacity) $.cssHooks.opacity.set=function(el, o){
                el.style.opacity=(o==1&&el.parentNode==($this.$container[0]||{}).parentNode)?.999:o
            }*/
        }
    })

    return this;
}
