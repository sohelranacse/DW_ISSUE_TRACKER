var CProfile = function(guid,spotlightNumber,requestUri,isFreeSite) {

    var $this=this;

    this.guid=guid;
    this.langParts={};

    this.requestUri=requestUri;
    this.isFreeSite=isFreeSite*1;

    this.isPhotoDefaultPublic;
    this.spotlightNumber=spotlightNumber;
    this.spotlightItems={};
    this.blink = {};
    this.$spotlightResponse;
    this.$spotlight;

    $this.dur=400;

    this.cacheJq={};
    this.cacheData={};
    this.requestAjax={};

    this.isMyProfile = function(){
        var isMy=activePage == 'profile_view.php'
                 || (activePage == 'search_results.php' && requestUserId && $this.guid==requestUserId);
        return isMy;
    }

    this.init = function(isPhotoDefaultPublic, isPhotoPublic, iAmInspotlight, spotlightCosts, hideMyPresence,
                         minNumberPhotosToUseSite, keyAlertMinNumberPhotosToUseSite, profileStatusMaxLength){
        $this.isPhotoDefaultPublic = isPhotoDefaultPublic*1;
        $this.isPhotoPublic = isPhotoPublic*1;
        $this.iAmInspotlight = iAmInspotlight*1;
        $this.spotlightCosts = spotlightCosts*1;
        $this.hideMyPresence = hideMyPresence*1;
        $this.minNumberPhotosToUseSite=minNumberPhotosToUseSite*1;
        $this.keyAlertMinNumberPhotosToUseSite=keyAlertMinNumberPhotosToUseSite;
        $this.profileStatusMaxLength=profileStatusMaxLength*1;
        if($this.profileStatusMaxLength == 0){
           $this.profileStatusMaxLength = 30;
        }
    }

    this.confirmLogout = function(){
        confirmCustom($this.langParts.do_you_want_to_log_out,$this.logout);
		return false
    }

    this.logout = function(){
        window.name=''; confirmHtmlClose();
        window.location.href=url_main+'index.php?cmd=logout';
    }

    this.getCacheJq = function(sel){
        if(typeof $this.cacheJq[sel] == 'undefined'){
            $this.cacheJq[sel]=$(sel);
        }
        return $this.cacheJq[sel];
    }

    this.openPopupEditor = function(id,title,hSave,hCancel,wrClass,isRemoveBtn){
        isRemoveBtn=isRemoveBtn||0;
        if(typeof cacheJq[id] == 'undefined'){
            var css = {zIndex: 1001, margin: '25px 3px'};
            if(id != 'pp_profile_settings_editor') {
                css['width'] = '617px';
            }
            cacheJq[id]=getCacheJq('#pp_edit_info').clone().attr('id',id)
            .modalPopup({css:css,shCss:{}, wrCss:{}, wrClass:wrClass||'', shClass:'pp_shadow_white'});
        }
        var $pp=cacheJq[id];
        if($pp.data('isOpen')){
            $pp.open();
            return true;
        }

        if (isRemoveBtn) {
            $('.foot',$pp).remove();
        }else{
            if(typeof(hCancel) == 'function'){
                $('.frm_editor_cancel',$pp).on('click',hCancel);
            }else{
                $('.frm_editor_cancel',$pp).on('click',function(){$this.closePopupEditor(id)});
            }
            if(typeof(hSave) == 'function'){
                $('.frm_editor_save',$pp).on('click',hSave);
            }else{
                $('.frm_editor_save',$pp).on('click',function(){$this.closePopupEditor(id)});
            }
        }
        $pp.data('isOpen',true);
        $('.head',$pp).text(title);
        $pp.open();
        return false;
    }

    this.updatePopupEditor = function(id,data){
        var $pp=getCacheJq(id);
        if($pp[0]){
            var $data=$(data);
            $('.loader_edit_popup',$pp).addClass('hidden');
            $('.bl_frm_editor',$pp).append($data);
            setTimeout(function(){
                var h=$data[0].offsetHeight,t=(h*.3/400).toFixed(1)*1,o=.5;
                if(t<.3)t=.3;
                if(o>t)o=t;
                //t=2;
                $pp.find('.frame').css({overflow:'hidden'});
                $('.bl_frm_editor',$pp).css({overflow:'hidden'}).oneTransEnd(function(){
                    $(this).css({height:'auto', overflow:''});
                    $pp.find('.frame').removeAttr('style');

                },'height').css({height:h+'px', opacity:1, transition:'height '+t+'s linear, opacity '+o+'s linear'});
                $('.bl_btn',$pp).css({transitionDelay:(t-.3)+'s'}).addClass('to_show');
            },10)
        }
    }

    this.closePopupEditor = function(id,fn,t){
        t=defaultFunctionParamValue(t, durClosePp);
        getCacheJq(id).close(t,fn);
    }

    this.closePopupEditorDelay = function(id,fn,d,t){
        setTimeout(function(){
            $this.closePopupEditor(id,fn,t);
        },(d||500))
    }

    this.hStub = function(){
        return false;
    }

    /* Edit basic fields */
    this.showBasicFieldEditor = function(field){
        var $editor=$jq('#basic_editor_'+field);
        if($editor.is('.to_show')) {
            $this.closeBasicFieldEditor(field);
            return;
        }
        var $field=$jq('#basic_editor_text_'+field);
        if($field.data('desc')==$field.val())$field.val('');
        $this.cacheData[field+'_value']=$field.val();
        $field.addClass('active').prop('disabled',false).oneTransEnd(function(){
            $field.focus();
        }).addClass('focus');
        $editor.addClass('to_show');
    }

    this.closeBasicFieldEditor = function(field){
        var $field=$jq('#basic_editor_text_'+field);
        var val=$this.cacheData[field+'_value'];
        if(!trim($field.val())&&$field.data('desc')){
            val=$field.data('desc');
        }
        if ($this.cacheData[field+'_value']==$field.val()) {
            $field.prop('disabled',true).removeClass('focus');
            $jq('#basic_editor_'+field).removeClass('to_show');
        }
        $jq('#basic_editor_save_'+field).prop('disabled', true);
        $jq('#basic_editor_cancel_'+field).text($this.langParts.cancel);
        $field.val(val).trigger('autosize');
    }

    this.handlerBasicFieldEditor = function(field){
        $(function(){
            var $field=$jq('#basic_editor_text_'+field)
            .on('change propertychange input', function(){
                $this.changeBasicFieldEditor(field);
            })
            if($field.data('type')=='textarea'){
                $field.autosize({isSetScrollHeight:false}).css('opacity',1);
            }else{
                $field.css('opacity',1);
            }
        })
    }

    this.changeBasicFieldEditor = function(field){
        if ($this.cacheData[field+'_value']!=trim($this.getCacheJq('#basic_editor_text_'+field).val())) {
            $jq('#basic_editor_save_'+field).prop('disabled', false);
            $jq('#basic_editor_cancel_'+field).text($this.langParts.reset);
        } else {
            $jq('#basic_editor_save_'+field).prop('disabled', true);
            $jq('#basic_editor_cancel_'+field).text($this.langParts.cancel);
        }
    }

    this.saveBasicFieldEditor = function(field, uid, c_user_id){
        var uid = uid || 0;
        var c_user_id = c_user_id || 0;
        var value=$jq('#basic_editor_text_'+field).val();
        $this.cacheData[field+'_old_value']=$this.cacheData[field+'_value'];
        $this.cacheData[field+'_value']=value;
        var $loader=getLoader('loader_edit_field');
        $jq('#basic_anchor_'+field).after($loader);
        $this.closeBasicFieldEditor(field);
        var data={ajax: 1, name: field};

        var cmd = 'update_about_field', val=emojiToHtml(trim(value));

        if(uid) {
            cmd = 'update_private_note';
            data['uid'] = uid;
            data['comment'] = val;
        } else {
            if(c_user_id)
                data['c_user_id'] = c_user_id;

            data[field] = val;
        }

        $.post(url_main+'ajax.php?cmd=' + cmd + '&no_format=1', data, function(res){
            $loader.remove();
            var data=checkDataAjax(res);
            if(data!==false){
                if (data!=value) {
                    $jq('#basic_editor_text_'+field).val(data).trigger('autosize');
                }
            } else {
                $jq('#basic_editor_text_'+field).val($this.cacheData[field+'_old_value']).trigger('autosize');
            }
        });
    }

    this.editFieldOnStart = function(name){
        var $pen=$jq('#basic_pen_'+name);
        if($pen[0]){
            $jq('.main').animate({scrollTop:$pen.offset().top},300);
            $pen.click();
        }
    }
    /* Edit basic fields */
    /* Edit looking for */
    this.showLookingForEditor = function(){
        var id='pp_profile_looking_for_editor';

        var e_user_id = 0
        if($("#ua_user_id").val())
            e_user_id = $("#ua_user_id").val()
        
        if($this.openPopupEditor(id,$this.langParts.who_are_you_looking_for,$this.hStub,$this.hStub))return;
        $.post(url_main+'ajax.php',{cmd:'pp_profile_edit_looking', e_user_id:e_user_id},function(res){
            var data=checkDataAjax(res);
            if(data!==false){
                $this.updatePopupEditor(id,data);
            }else{
                alertServerError()
            }
        })
    }

    /* Edit looking for */
    /* Edit main */
    this.showMainEditor = function(){
        var id='pp_profile_main_editor';

        var e_user_id = 0
        if($("#ua_user_id").val())
            e_user_id = $("#ua_user_id").val()

        if($this.openPopupEditor(id,$this.langParts.edit_basic_details,$this.hStub,$this.hStub))return;
        $.post(url_main+'ajax.php',{cmd:'pp_profile_edit_main', e_user_id:e_user_id},function(res){
            var data=checkDataAjax(res);
            if(data!==false){
                $this.updatePopupEditor(id,data);
            }else{
                alertServerError()
            }
        })
    }
    /* Edit main */
	/* Edit personal */
    this.showPersonalEditor = function(){
        var id='pp_profile_personal_editor';

        var e_user_id = 0
        if($("#ua_user_id").val())
            e_user_id = $("#ua_user_id").val()

        if($this.openPopupEditor(id,$this.langParts.edit_personal_details,$this.hStub,$this.hStub,'wrapper_custom'))return;
        // $.post(url_main+'ajax.php?cmd=pp_profile_edit_field_personal',{},function(res){
        $.post(url_main+'ajax.php',{cmd:'pp_profile_edit_field_personal', e_user_id:e_user_id},function(res){
            var data=checkDataAjax(res);
            if(data!==false){
	            $this.updatePopupEditor(id,data);
            }else{
                alertServerError()
            }
        })
    }
    /* Edit personal */
    /* Edit settings */
    this.showSettingsEditor = function(){
        if($this.notAccessToSite())return false;

        var id='pp_profile_settings_editor';

        if(typeof disabledProfileSettingsFrm === 'function') {
            disabledProfileSettingsFrm();
        }

        if($this.openPopupEditor(id,$this.langParts.profile_settings,$this.hStub,$this.hStub,'wrapper_custom',true))return;
        $.post(url_main+'profile_settings.php?cmd=pp_profile_settings_editor',{},function(res){
            var data=checkDataAjax(res);
            if(data!==false){
	            $this.updatePopupEditor(id,data);
            }else{
                alertServerError()
            }
        })
    }
    /* Edit settings */
    /* Edit status */
    this.initSatusEditor = function(){
        var $ps=$jq('#profile_status');
        $ps.css({minHeight:$ps.height(), minWidth:$ps.width()});
        $jq('#profile_status_edit').css('opacity',1);
        $(function(){
            $ps.editable({
                lAdd: $this.langParts.your_status_here,
                lSave: '',
                inputLength: $this.profileStatusMaxLength,
                empty: true,
                type: 'status',
                hBeforeSend: $this.beforeSendStatusEditor,
                hSuccessSend: $this.successSendStatusEditor,
                classHover: 'editable_hover',
            }).on('click',function(){
                selectText($ps[0])
            })
        })
    }
    this.showStatusEditor = function(){
        $jq('#profile_status').focus();
    }

    this.beforeSendStatusEditor = function(){
        if(typeof $this.cacheJq['loader_edit_status'] == 'undefined'){
            $this.cacheJq['loader_edit_status']=getLoader('loader_edit_status');
        }
        $jq('#profile_status_edit').after($this.cacheJq['loader_edit_status'].removeClass('hidden'));
    }

    this.successSendStatusEditor = function(){
        $this.cacheJq['loader_edit_status'].addClass('hidden');
    }
    /* Edit status */
    /* Encounters - Like */
    this.sendLikeProfile = function(uid,$btn){
        if (ajax_login_status) {
            var $btnBlock=$jq('#profile_menu_more_user_block_li'),cmdBlock='';
            if($btnBlock[0]){
                cmdBlock=$btnBlock.data('cmd');
                if($btnBlock.data('cmd')=='user_unblock'){
                    confirmCustom($this.langParts.the_profile_will_be_unblocked_if_you_like_it,
                                  function(){
                                    $this.sendLike(uid,$btn,1);
                                  },
                                  ALERT_HTML_ALERT)
                }else{
                    $this.sendLike(uid,$btn);
                }
            }else{
                $this.sendLike(uid,$btn);
            }
        }else{
            redirectToLogin();
        }
    }

    this.sendLike = function(uid,$btn,unblock){
        if($this.requestAjax['like']||$this.requestAjax['blocked'])return;
        $btn=$btn||{};
        $this.requestAjax['like']=1;
		var status=$btn.is('.active')?'N':'Y';
		var $likeName=$btn.find('#like_title');
		if($btn.is('.active')){
			$likeName[0]&&$likeName.text($this.langParts['profile_like']);
			$btn.attr('title', $this.langParts['like']).removeClass('active');
		}else{
			$likeName[0]&&$likeName.text($this.langParts['profile_liked']);
			$btn.attr('title', $this.langParts['unlike']).addClass('active');
		}
        unblock=unblock||0;
        $.post(url_ajax+'?cmd=set_want_to_meet',{uid:uid,status:status,unblock:unblock}, function(res){
            var data = checkDataAjax(res);
            if(data){
                updateCountersLikes(data);
                if(parseInt(data['isMutual'])) {
                    alertMutualLike(data['urlProfile'], data['urlPhoto']);
                }
                if(data['number_blocked']){
                    $this.blockUserResponse($jq('#profile_menu_more_user_block_li'), 'user_unblock', data, data['number_blocked']);
                }
            }else{
                alertServerError()
            }
            $this.requestAjax['like']=0;
        })
    }

	this.sendLikeEncounters = function(uid,status,$btn){
        if($this.requestAjax['like'])return;
		$btn.html(getLoader('loader_like_encounters',false,true));
        $this.requestAjax['like']=1;
		var cmd='?display=encounters&cmd_enc=reply&reply_enc='+status+'&uid_enc='+uid;
		$.post(urlSearchResults + cmd, {ajax:1}, function(res){
			var $data=$('<div>'+res+'</div>');
			var $cont=$data.find('.col_center');
			if($cont.data('uid')*1){
                //banner_header
				var urlLoader=Photo.urlLoader;
				var $ppGalleryPhotos=Photo.$ppGalleryPhotos;
				var sourceGalleryHtml=Photo.sourceGalleryHtml;
				$('.col_center:last').toggleClass('to_show to_hide').oneTransEnd(function(){
					$(this).remove();
				})
                //$cont.find('#photo_pic_main').addClass('delay_show');
				setTimeout(function(){
                    if ($('.banner_header_bl')[0]) {
                        $('.banner_header_bl').after($cont);
                    }else{
                        $cont.prependTo($jq('.column_main'));
                    }
					Photo.urlLoader=urlLoader;
					Photo.$ppGalleryPhotos=$ppGalleryPhotos;
					Photo.sourceGalleryHtml=sourceGalleryHtml;
					Photo.uid=$cont.data('uid');
					Photo.fuid=$cont.data('fuid');
					Photo.gender=$cont.data('gender');
				},1);
			}else{
				goLink(urlPagesSite.search_results,'show=alert_change_filter');
			}
			$this.requestAjax['like']=0;
		})
    }
    /* Encounters - Like */
    /* Block user */
    this.confirmBlockUser = function(uid){
        var $btn=$jq('#profile_menu_more_user_block_li');
        confirmHtml(ALERT_HTML_ARE_YOU_SURE,function(){$this.blockUser(uid)},$this.langParts[$btn.data('cmd')+'_alert']);
    }

    this.blockUserResponse = function($btn, cmdCur, data, numberBlocked){
        var titleMenu=$this.langParts['profile_menu_user_unblock'],
            cmd='user_unblock',msg=siteLangParts.user_has_been_blocked;

        if (cmdCur=='user_unblock') {
            updateCounter('#narrow_blocked_count', numberBlocked||data['number'], true);
        }else{
            $jq('#update_server').append($(data).filter('script'));

            var $btnLike=$jq('#btn_send_like');
            if($btnLike[0] && $btnLike.is('.active')){
                var $likeName=$btnLike.find('#like_title');
                $likeName[0]&&$likeName.text($this.langParts['profile_like']);
                $btnLike.attr('title', $this.langParts['like']).removeClass('active');
            }
        }
        if (cmdCur=='user_unblock') {
            titleMenu=$this.langParts['profile_menu_user_block'];
            cmd='block_visitor_user';
            msg=siteLangParts.user_has_been_unlocked;
        }
        $btn.data('cmd',cmd).attr('data-cmd',cmd);
        $jq('#profile_menu_more_user_block').text(titleMenu);
        $jq('#bl_user_blocked')[cmdCur=='user_unblock'?'removeClass':'addClass']('to_show');
        return msg;
    }

    this.blockUser = function(uid){
        if($this.requestAjax['blocked'])return;
        $this.requestAjax['blocked']=true;
        closeAlert();
        var $btn=$jq('#profile_menu_more_user_block_li'),
            cmdCur=$btn.data('cmd');
        $.post(url_main+'ajax.php?cmd='+cmdCur,{user_id:uid,user_to:uid},function(res){
            $this.requestAjax['blocked']=false;
            var data = checkDataAjax(res);
            if(data){
                var msg=$this.blockUserResponse($btn, cmdCur, data);
                alertSuccess(msg);
            }else{
                alertServerError(true)
            }
        })
    }
    /* Block user */
    /* Unblock user */
    this.confirmUnblockUser = function(uid){
        confirmHtml(ALERT_HTML_ARE_YOU_SURE,function(){$this.unblockUser(uid)},$this.langParts['user_unblock_alert']);
    }

    this.unblockUser = function(uid){
        if($this.requestAjax['blocked'])return;
        $this.requestAjax['blocked']=true;
        closeAlert();
        var $btn=$('#block_btn_'+uid);
        if($btn[0]){
            $btn.prop('disabled',true);
            $btn.find('span').fadeTo(0,0);
            $btn.append(getLoader('loader_btn_list_md'));
        }
        isLoadBaseListUsers=true;
        $.post(url_main+'user_block_list.php?cmd=user_unblock',{ajax:1,user_to:uid,on_page:1,id:lastIdBaseListUsers},
            function(res){
                $this.requestAjax['blocked']=false;
                var data=checkDataAjax(res)
                if(data){
                    updateCounterTitle('#narrow_blocked_count',true);
                    $('.item_'+uid).slideUp(durRemoveListItem, 'easeOutCirc', function(){
                        $(this).remove();
                        var items=$('[id ^= profile_item_]:visible');
                        if(!items[0]){
                            alertCustomRedirect(urlPagesSite.home,$this.langParts.you_havent_blocked_anyone_yet);
                            return false;
                        }
                        var user=$($.trim(data)).find('.item');
                        if(user[0]){
                            user.hide().appendTo('#page_list_users').slideDown(200,function(){
                                $(this).removeAttr('style')
                            });
                        }
                    })
                }else{
                    if($btn[0]){
                        $btn.prop('disabled',false);
                        $btn.find('.loader_btn_list_md').remove();
                        $btn.find('span').fadeTo(0,1);
                    }
                    alertServerError(true);
                }
                isLoadBaseListUsers=false;
        })
    }
    /* Unblock user */

    /* Report */
    this.reportUserId = 0;
    this.reportPhotoId = 0;
    this.openReport = function(uid, pid) {
        if(!checkLoginStatus())return;
        $this.reportUserId=uid||0;
        $this.reportPhotoId=pid||0;
        if(($this.requestAjax['report'] && !pid) || !uid)return;

        var title = Photo.isShowGalleryPhoto ? (Photo.isVideo ? l('report_this_video_to_administrator') : l('report_this_photo_to_administrator')) : l('report_this_user_to_the_administrator');

        $('.head', $this.$userReportPopup).text(title);
        $jq('#pp_user_report_msg').change();
        $this.$userReportPopup.open();
    }

    this.closeReport = function() {
        $this.$userReportPopup.close();
        $jq('#pp_user_report_msg').val('');
    }

    this.cancelReport = function() {
        var msg=$jq('#pp_user_report_msg').val();
        if(trim(msg)){
            $jq('#pp_user_report_msg').val('');
            $jq('#pp_user_report_msg').change();
        }else{
            $this.closeReport();
        }
    }

    this.checkCloseReport = function($el) {
        if ($this.$userReportPopup.is(':visible')) {
            var msg=trim($jq('#pp_user_report_msg').val());
            if($el.is('.pp_wrapper')&&!msg)$this.closeReport();
            return false;
        }
        return true;
    }

    this.sendReport = function() {
        $this.requestAjax['report']=1;
        var uid=$this.reportUserId,
            pid=$this.reportPhotoId,
            msg=trim($jq('#pp_user_report_msg').val());
        $this.closeReport();
        if(pid) {
            if(Photo.isVideo) {
                $('#report_video_gallery').addClass('response_loader');
            } else {
                $('#report_photo_gallery').addClass('response_loader');
            }
        }
        $.post(url_ajax+'?cmd=report_user',
               {user_to : uid, msg : msg, photo_id: pid},
                function(res){
                    if(checkDataAjax(res)){
                        setTimeout(function(){alertSuccess($this.langParts['report_sent'],false,ALERT_HTML_SUCCESS)},220);
                        if(!pid){
                            $('#menu_additional_report_'+uid).remove();
                            var $profileMenuMoreItems=$jq('#profile_menu_more_options_items');
                            if($profileMenuMoreItems[0]){
                                hMenuMore=$profileMenuMoreItems.find('.pp_info_cont').height()+28;
                            }
                        }else{
                            Photo.setDataReports(pid);
                        }
                    }else if(pid) {
                        if(Photo.isVideo) {
                            $('#report_video_gallery').removeClass('response_loader');
                        } else {
                            $('#report_photo_gallery').removeClass('response_loader');
                        }
                    }
                    $this.requestAjax['report']=0;
        });
    }
    /* Report */
	/* Chart */
	this.randomScalingFactor = function(){
		return Math.round(Math.random()*100);
	}

	this.getColor = function(num){
		var backgroundColor={
			1:['rgb(45,190,254)', 'rgb(255,87,109)'],
			2:['rgb(0,201,231)', 'rgb(255,122,75)'],
			3:['rgb(64,129,252)', 'rgb(255,60,193)']
		}
		return backgroundColor[num];
	}

	this.renderChart = function(id,pr,num,r){
		r=r||60;
        pr=pr*1;
        var pr1=pr,//||$this.randomScalingFactor(),
            pr2=100-pr1;
        var color=$this.getColor(num), brColor=colorRgbToHex($('.column_main').css('backgroundColor'));
        var $chart=$jq('#'+id);
        if($chart.closest('#page_list_users')[0]){
            brColor=colorRgbToHex($chart.closest('.item').css('backgroundColor'));
        }
        if(!pr2){
            brColor=color[1];
        }
		var data={labels:[],
              datasets: [{
                data: [
                    pr2,
                    pr1
                ],
                backgroundColor:color,
				hoverBackgroundColor:color,
                borderWidth:1,
				borderColor:brColor,
				hoverBorderWidth:1,
				hoverBorderColor:brColor
              }]
		};

        //var rotation = 90 * Math.PI/180 - (pr2) * (360/100) * Math.PI/180;
        //var rotation = -(pr2/2) * (360/100) * Math.PI/180 + 90 * Math.PI/180;
        //console.log('rotation', rotation, pr1, pr2);

		new Chart(document.getElementById(id).getContext('2d'), {
              type:'doughnut',
              options: {
				cutoutPercentage:r,
                animation:false,
                animateScale:false,
                animateRotate:false,
                //borderColor:'#FFFFFF',
                responsive:false,
                tooltips :{enabled: false},
                rotation: -(pr2/2) * (360/100) * Math.PI/180
              },
			data:data
		})
        $chart.closest('.chart_statistics').addClass('to_show');
	}
	/* Chart */

    this.redirectToUploadPhoto = function(url){
        redirectUrl(url);
        // $jq('#some_add_photo_public').trigger('click');
    }

    this.setTabs = function(id0){
        var $tabs=$('#tabs_profile'), $tabsSwitch=$('li.switch_tab',$tabs),h,$li;
        $win.on('hashchange', function(e){
            var id=location.hash||id0;
            if(id=='#upload_photo'){
                location.hash='#tabs-2';
                return;
            }
            var $a=$('a[href="'+id+'"]',$tabs);
            if(!$a[0]||$a.is('.not_allowed')){
                var af=$('a', $tabs)[0];
                if(af){
                    h=af.href.match(/#(tabs-[1-2])/);
                    h[0]&&(location.hash=h[0]);
                }
                return;
            }
            if(!/#tabs-[1-2]/.test(id)&&!$('#tabs_profile>a.target')[0]) id=id0;
            $li=$('#'+id.replace(/#/g,'')+'_switch');
            if (!/#tabs-[1-2]/.test(id) || $(id+'.target')[0]) {
                if (!$li.is('.selected')) {
                    $tabsSwitch.removeClass('selected');
					$li.addClass('selected');
                    //document.title=siteTitleTemp+' '+$li.addClass('selected').text();
                }
                return;
            }
            $('.tab_a', '#tabs_content').removeClass('target');
            $(id).addClass('target');
            if(id=='#tabs-2'){
                $jq('.main').scroll();
            }
            $tabsSwitch.removeClass('selected');
			$li.addClass('selected');
            //document.title=siteTitleTemp+' '+$li.addClass('selected').text();
            //siteTitle=document.title;
            $win.scroll();
        }).trigger('hashchange')
    }

    this.statusOnline=0;
    this.realStatusOnline=0;
    this.updateOnlineStatus = function(status, realStatus){
        if($this.realStatusOnline!=realStatus){
            $this.realStatusOnline=realStatus;
        }
        if ($this.statusOnline!=status) {
            $jq('#profile_status_online')[status?'addClass':'removeClass']('to_show');
            $this.statusOnline=status;
        }
	}

    this.setStatusOnline = function(realStatus, userStatus){
        $this.realStatusOnline=realStatus*1;
        $this.statusOnline=userStatus*1;
	}

    this.getRealStatusOnline = function(){
        return $this.realStatusOnline;
	}

    var hMenuMore=0;
    this.menuMoreExpand = function(h,$el){
        $el=$el||$jq('#profile_menu_more_options_items');
        h=h||0;
        $el[h?'addClass':'removeClass']('open');
        $el.stop().animate({height:(h?hMenuMore:0)+'px'},300)
    }

    this.closeMenuMoreAll = function(){
        audioChat.menuClose();
        videoChat.menuClose();
        //$jq('#pp_audio_chat')[0]&&$this.menuMoreExpand(false, $jq('#pp_audio_chat'));
        //$jq('#pp_video_chat')[0]&&$this.menuMoreExpand(false, $jq('#pp_video_chat'));
    }

    this.visibleBanners = function($link, pos){
        if($link.find('.css_loader')[0])return;
        $link.addLoader();

        if(!userAllowedFeature['kill_the_ads']){
            redirectToUpgrade();
            return;
        }

        if (pos == 'content') {
            var $bannerBl=$link.parent('.link').prev('.banner_header, .banner_footer');
        } else {
            var $bannerBl=$('.bl_banner_'+pos).find('.bl_ads');
        }
        var isVisible=$bannerBl[0]&&$bannerBl.is(':visible')?1:0;

        $.post(url_main + 'ajax.php?cmd=ads_visible', {status: isVisible}, function(res){
            var data = checkDataAjax(res);
            if(data !== false) {
                if (data == 'upgrade') {
                    redirectToUpgrade();
                } else if(isVisible) {
                    var isQue=true;
                    if(activePage=='general_chat.php' || activePage=='messages.php'){
                        isQue=false;
                    }
                    var isBContent=$this.hideBannerContent('header', isQue);
                    isBContent |=$this.hideBannerContent('footer', isBContent||isQue);
                    if(!isBContent||isQue){
                        $this.hideBannerColumn('right_column');
                        $this.hideBannerColumn('left_column');
                    }
                } else {
                    location.reload();
                }
            } else {
                $link.removeLoader();
                alertServerError();
            }
        })

    }

    this.hideBannerContent = function(pos, isBColumn){
        var $bannerBl=$('.banner_'+pos), isBColumn=isBColumn||0;
        if (!$bannerBl[0]) {
            return false;
        }
        var $link=$('.banner_'+pos+'_bl').find('.link_show_banner');
        if(pos=='header'){
            $bannerBl=$('.banner_header_bl');
        }
        /*$bannerBl.slideUp({
        duration:450,
        step:function(){
            //if(pos=='header')preparePageWithShowBanner();
        }, complete:function(){
            $link.removeLoader();
            $link.text(l('show_ads'));
            $link.addClass('action_show');
            $bannerBl.hide().html('');
            //preparePageWithShowBanner();

        }});*/
        $bannerBl.slideUp(450,0,function(){
            $link.removeLoader();
            $link.text(l('show_ads'));
            $link.addClass('action_show');
            $bannerBl.hide().html('');
            preparePageWithShowBanner();
            if(!isBColumn){
                $this.hideBannerColumn('right_column');
                $this.hideBannerColumn('left_column');
            }
        })
        return true;
    }

    this.hideBannerColumn = function(pos){
        var $blG=$('.bl_banner_'+pos);
        if (!$blG[0]) {
            return false;
        }
        var $bannerBl = $blG.find('.bl_ads'),
            $link=$blG.find('.link_show_banner'),
            $columnB=$bannerBl.parent('.bl_banner'),hD=1,next=$columnB.next().is('.bl_banner_empty');
        if($columnB[0]&&next){
            hD=$columnB.height();
            $columnB.height(hD);
            $link.parent('.link').addClass('absolute');
        }

        var fnHideBanner=function(){
            $link.removeLoader();
            $link.text(l('show_ads'));
            $link.addClass('action_show');
            $bannerBl.hide().html('');
            if($columnB.is('.bl_banner_l')){
                isPrepareBannerL=true;
            }else{
                isPrepareBannerR=true;
            }
            $win.resize();
        }
        $jq('.main').animate({scrollTop:0}, 300, 'easeOutQuad');
        $bannerBl[next?'fadeTo':'slideUp'](350,0,fnHideBanner);
    }

    this.initClosePpEditorButton = function(editor)
    {
        $('.pp_body').on('click', function(e){
            if(e.target == this && editor.is(':visible')) {
                $('.icon_close', editor).click()
            }
        })
    }

    this.initCloseEditorButton = function(id, editor, isModifiedFunction)
    {
        $('.pp_body').on('click', function(e){
            if(e.target == this && $('#' + id + ':visible')[0]) {
                if(isModifiedFunction()) {
                    confirmCustom(l('are_you_sure'), function(){$('.icon_close', editor).click()}, l('close_window'));
                } else {
                    editor.close(durClosePp);
                }
            }
        })
    }

    this.toOpenIm = function(uid, $btn){
        if ($this.statusOnline) {
            imChats.openImWithUser($btn, uid);
        }else{
            redirectUrl(urlPagesSite.messages+'?display=one_chat&user_id='+uid)
        }
    }

    this.updateServerMyData = function(allowedFeature){
        userAllowedFeature=allowedFeature;
        if ($('.pp_message_upload_img:visible')[0]) {
            imChats.initCheckPaydUploadImage($('.pp_message_upload_img'));
        }
    }

    this.alignWidthBtnRightColum = function(){
        var $btnRCol=$('.profile_sign .btn'),wBtnRCol=0;
        if($btnRCol.length==2){
            $btnRCol.each(function(){
                var w=$(this).width();
                if(w>wBtnRCol)wBtnRCol=w;
            }).width(wBtnRCol);
        }
    }

    this.onLoadMainPhoto = function($img) {
        //console.log('NAVIGATOR: ', /MSIE|Trident|Edge/i.test(navigator.userAgent), navigator.userAgent);
        if (/MSIE|Trident|Edge/i.test(navigator.userAgent)){
            if (!Modernizr.objectfit){
                var imgUrl = $img.prop('src');
                if (imgUrl) {
                    $img.closest('.profile_pic_one').css('backgroundImage', 'url(' + imgUrl + ')')
                        .addClass('compat-object-fit');
                }
            }
        }
        $img.parent('div').addClass('to_show');
    }

    this.notAccessToSite = function() {
        if(userAllowedFeature['site_access_paying']){
            alertCustom(l('upgrade_your_account'),true,l('alert_html_alert'));
            return true;
        }
        if(!$this.isAccessToSiteFromPageEmailNotConfirmed())return true;
        if(!$this.isAccessToSiteWithMinNumberUploadPhotos())return true;
        return false;
    }

    this.isAccessToSiteWithMinNumberUploadPhotos = function() {
        if(!$this.minNumberPhotosToUseSite)return true;
        if($this.isMyProfile()){
            var numberVisPhoto=0, numberAllPhoto=0;
            for (var id in Photo.galleryPhotosInfo) {
                if (!Photo.isVideoData(id)) {
                    if(Photo.galleryPhotosInfo[id]['visible']=='Y'){
                        numberVisPhoto++;
                    }
                    numberAllPhoto++;
                }
            }
            if ($this.minNumberPhotosToUseSite > numberVisPhoto) {
                var msg=l('site_available_after_uploading_photos').replace(/{param}/, $this.minNumberPhotosToUseSite);
                if($this.minNumberPhotosToUseSite<=numberAllPhoto){
                    msg=l('photos_are_approved_by_the_administrator');
                }
                alertCustom(msg,true,l('alert_html_alert'));
                return false;
            }
            return true;
        }else if($this.keyAlertMinNumberPhotosToUseSite){
            var msg=l($this.keyAlertMinNumberPhotosToUseSite).replace(/{param}/, $this.minNumberPhotosToUseSite);
            alertCustom(msg,true,l('alert_html_alert'));
            return false;
        }else{
            return true;
        }
    }

    this.isAccessToSiteFromPageEmailNotConfirmed = function() {
        if (activePage == 'email_not_confirmed.php') {
            alertCustom(l('please_confirm'),true,l('alert_html_alert'));
            return false;
        }
        return true;
    }

    this.setBrowseInvisibly = function($link, param) {
        $link.addLoader();
        if (param == 'upgrade') {
            redirectToUpgrade();
        }else{
            $.post(url_main+'ajax.php?cmd=set_do_not_show_me_visitors', {}, function(res){
                var data=checkDataAjax(res);
                if(data!==false){
                    $('.btn_browse_invisibly').closest('.bl').animate({height:0, opacity:0, margin:0},300,function(){
                        $(this).remove();
                        if(typeof profileSettingsData != 'undefined' && profileSettingsData['set_do_not_show_me_visitors']){
                            profileSettingsData['set_do_not_show_me_visitors']=1;
                            $('#radio_set_do_not_show_me_visitors_1').click();
                        }
                    });
                } else {
                    $link.removeLoader();
                    alertServerError();
                }
            })
        }
    }

    this.openPopupEditorVerification = function(){
        var id='pp_profile_verification';
        if(typeof cacheJq[id] == 'undefined'){
            var css = {zIndex: 1001, margin: '25px 3px'};
            cacheJq[id]=getCacheJq('#pp_profile_verification')
            .modalPopup({css:css,shCss:{}, wrCss:{}, wrClass:'wrapper_custom', shClass:'pp_shadow_white'});
        }
        var $pp=cacheJq[id];
        if($pp.data('isOpen')){
            $pp.open();
            return true;
        } else {
            $('.select_main',$pp).styler({singleSelectzIndex: '11',
                selectAutoWidth : false,
                selectAppearsNativeToIOS: false,
                selectAnimation: true
            })
        }

        $pp.data('isOpen',true);
        $pp.open();
        return false;
    }

    this.openPopupEditorUploadPDF = function(){
        var id='pp_profile_upload_pdf';
        if(typeof cacheJq[id] == 'undefined'){
            var css = {zIndex: 1001, margin: '25px 3px'};
            cacheJq[id]=getCacheJq('#pp_profile_upload_pdf')
            .modalPopup({css:css,shCss:{}, wrCss:{}, wrClass:'wrapper_custom', shClass:'pp_shadow_white'});
        }
        var $pp=cacheJq[id];
        if($pp.data('isOpen')){
            $pp.open();
            return true;
        } else {
            $('.select_main',$pp).styler({singleSelectzIndex: '11',
                selectAutoWidth : false,
                selectAppearsNativeToIOS: false,
                selectAnimation: true
            })
        }

        $pp.data('isOpen',true);
        $pp.open();
        return false;
    }

    this.openPopupEditorUploadNID = function(){
        var id='pp_profile_upload_nid';
        if(typeof cacheJq[id] == 'undefined'){
            var css = {zIndex: 1001, margin: '25px 3px'};
            cacheJq[id]=getCacheJq('#pp_profile_upload_nid')
            .modalPopup({css:css,shCss:{}, wrCss:{}, wrClass:'wrapper_custom', shClass:'pp_shadow_white'});
        }
        var $pp=cacheJq[id];
        if($pp.data('isOpen')){
            $pp.open();
            return true;
        } else {
            $('.select_main',$pp).styler({singleSelectzIndex: '11',
                selectAutoWidth : false,
                selectAppearsNativeToIOS: false,
                selectAnimation: true
            })
        }

        $pp.data('isOpen',true);
        $pp.open();
        return false;
    }

    this.verifyAccount = function() {
        var url = $('select[name="profile_verification_system"]').val();
        if(url) {
            $jq('#btn_verify_account').addChildrenLoader();
            redirectUrl('social_login.php?redirect=' + url + '&page_from=' + location.href);
        }
        return false;
    }

    this.uploadPDF = function(e) {
		var pdf = new FormData();
        pdf.append('file', input_profile_pdf.files[0]);

        var validExtensions = ["pdf"]
        var file = $('#input_profile_pdf').val().split('.').pop();
        if (validExtensions.indexOf(file) == -1) {
            alert("Only formats are allowed : "+validExtensions.join(', '));
            $("#input_profile_pdf").val('');
            return false;
        }
        $('#btn_upload_pdf').prop('disabled', true);
        $('#btn_upload_pdf').text('Uploading');


        if($("#btn_upload_pdf").val())
            pdf.append('e_user_id', $("#btn_upload_pdf").val());

        $.ajax({
            url: 'profile_upload_pdf.php',
            type: 'POST',
            data: pdf,
            processData: false,
            contentType: false,
 
            success:function(data){
                if(data){
                    //$('#upload_pdf_msg').css({'color': 'green'}).text('PDF uploaded successfully!').show();
                    alert('Your PDF is successfully uploaded!');
                    location.reload();
                } else{
                    $('#upload_pdf_msg').css({'color': 'red'}).text('There is some error in uploading, try again!').show();
                }
                $('#btn_upload_pdf').prop('disabled', false);
                $('#btn_upload_pdf').text('Uploaded');
            }
 
        });
        return false;
    }

    // nid
    this.uploadNID = function(e) {
        var pdf = new FormData();
        pdf.append('file', input_profile_nid.files[0]);

        pdf.append('cmd', 'upload_nid');

        var validExtensions = ["pdf","jpg","jpeg","png"]
        var file = $('#input_profile_nid').val().split('.').pop();
        if (validExtensions.indexOf(file) == -1) {
            alert("Only formats are allowed : "+validExtensions.join(', '));
            $("#input_profile_nid").val('');
            return false;
        }
        $('#btn_upload_nid').prop('disabled', true);
        $('#btn_upload_nid').text('Uploading');


        if($("#btn_upload_nid").val())
            pdf.append('e_user_id', $("#btn_upload_nid").val());

        // due the work here for create a php file and upload nid.
        $.ajax({
            url: 'profile_document.php',
            type: 'POST',
            data: pdf,
            processData: false,
            contentType: false,
 
            success:function(data){
                if(data){
                    // $('#upload_pdf_msg').css({'color': 'green'}).text('PDF uploaded successfully!').show();
                    alert('Your NID is successfully uploaded!');
                    location.reload();
                } else{
                    $('#upload_pdf_msg').css({'color': 'red'}).text('There is some error in uploading, try again!').show();
                }
                $('#btn_upload_nid').prop('disabled', false);
                $('#btn_upload_nid').text('Uploaded');
            }
 
        });
        return false;
    }

    $(function(){
        $this.$userReportPopup=$('#pp_user_report');
        if($this.$userReportPopup[0]){
            $this.$userReportPopup.modalPopup({wrCss:{zIndex:1001}, css:{left:0, top:0, margin:'25px 25px 25px 3px'}});
            $jq('#pp_user_report_msg').on('change propertychange input', function(){
                if(trim($jq('#pp_user_report_msg').val())){
                    $jq('#pp_user_report_cancel').text($this.langParts.reset);
                }else{
                    $jq('#pp_user_report_cancel').text($this.langParts.cancel);
                }
            })
        }
        $('body').on('click', '.pp_wrapper', function(e){
            var target=$(e.target);
            $this.checkCloseReport(target);
        })

        if(isPageProfile){
            /* Profile menu more options */
            var $profileMenuMore=$jq('#profile_menu_more_options');
            if($profileMenuMore[0]){
                var $profileMenuMoreItems=$jq('#profile_menu_more_options_items');
                hMenuMore=$profileMenuMoreItems.find('.pp_info_cont').height()+28;
                var menuMoreOpen = function(){
                    $this.closeMenuMoreAll();
                    clearTimeout($profileMenuMoreItems.data('action'));
                    $this.menuMoreExpand(!$profileMenuMoreItems.is('.open'));
                }
                var menuMoreHover = function(e){
                    var $targ=$(e.target);
                    if($targ.closest('#profile_menu_more_options')[0]){
                        clearTimeout($profileMenuMoreItems.data('action'));
                    }
                    if($targ.is('.pp_info')||$targ.closest('.pp_info')[0]){
                        $profileMenuMoreItems.addClass('open');
                    }
                }
                var menuMoreClose = function(){
                    $profileMenuMoreItems.removeClass('open')
                    $profileMenuMoreItems.data('action',setTimeout(function(){
                        !$profileMenuMoreItems.is('.open')&&$this.menuMoreExpand();
                    },1000))
                }
                $profileMenuMore.on('mouseenter',menuMoreHover)
                        .on('mouseleave',menuMoreClose)
                        .click(menuMoreOpen);

            }
            //To -> css
            $('.add_photo').hover(
                function(){$(this).addClass('hover_bg')},
                function(){$(this).removeClass('hover_bg')}
            ).click(function(){
                $(this).removeClass('hover_bg');
            })
            /* Profile menu more options */
        }

        var $columnLang = $('#column_lang');
        if ($columnLang[0]) {
            var fnShowLang=function($el){
                if($el.is(':animated'))return;
                $el.css('left', '-'+($el.width() + 18)+'px').stop().animate({height:'toggle'},300);
            }
            $columnLang.find('ul').autocolumnlist({columns: getSiteOption('number_of_columns_in_language_selector'),
                clickEmpty:function(){fnShowLang($('#column_lang > #column_lang_item'))}})
            //fnShowLang($('#column_lang_item'));
            $('#column_lang').click(function(){
               var item=$(this).find('#column_lang_item');
               fnShowLang(item);
            }).find('#column_lang_item').mouseleave(function(){
               var item=$(this);
               if(!item.is(':visible'))return;
               fnShowLang(item);
            })
            $doc.on('click', function(e){
                var $targ=$(e.target),$langDrop=$('#column_lang_item');
                if($targ.is('#column_lang')||$targ.closest('#column_lang')[0])return;
                if($langDrop.is(':animated')||!$langDrop.is(':visible'))return;
                fnShowLang($langDrop);
            })
        }

        $('.profile_verification_show').click(function(){
            $this.openPopupEditorVerification();
            return false;
        });

        // pdf - personal
        $('.profile_upload_pdf').click(function(){
            $this.openPopupEditorUploadPDF();
            $("#btn_upload_pdf").val('')
            return false;
        });

        // edit user
        $('.user_profile_upload_pdf').click(function(){
            $this.openPopupEditorUploadPDF();
            $("#btn_upload_pdf").val($("#ua_user_id").val())
            return false;
        });

        // nid - personal
        $('.user_profile_upload_nid').click(function(){
            $this.openPopupEditorUploadNID();
            $("#btn_upload_nid").val($("#ua_user_id").val())
            return false;
        });

        $('.profile_delete_pdf').click(function(){
            var e_user_id = 0
            if(this.value)
                e_user_id = this.value;

            if (confirm("Are you sure, do you want to really delete this file?")) {
                $.ajax({
                    url: 'profile_delete_pdf.php',
                    data: { "e_user_id": e_user_id },
                    type: 'POST',    
                    dataType: "json",    
                    success:function(data){
                        if(data){
                            alert('PDF File deleted successfully!');
                        } else{
                            alert('There is someting wrong, Please try again!');
                        }
                        location.reload();
                    }
        
                });
            }
            
            return false;
        });
    })


    const btnLoader = `
        <div class="css_loader pp_profile_edit_main_loader">
            <div class="spinner center">
                <div class="spinner-blade"></div>
                <div class="spinner-blade"></div>
                <div class="spinner-blade"></div>
                <div class="spinner-blade"></div>
                <div class="spinner-blade"></div>
                <div class="spinner-blade"></div>
                <div class="spinner-blade"></div>
                <div class="spinner-blade"></div>
                <div class="spinner-blade"></div>
                <div class="spinner-blade"></div>
                <div class="spinner-blade"></div>
                <div class="spinner-blade"></div>
            </div>
        </div>`;


    /* Edit profile modal */

    this.loadAdressEdit = function(id) {
        var e_user_id = 0
        if($("#ua_user_id").val())
            e_user_id = $("#ua_user_id").val()

        $.ajax({
            url: 'profile_ajax.php',
            type: 'POST',
            data: {
                "cmd": "get_address_field",
                e_user_id
            },
 
            success:function(data){
                var result = JSON.parse(data);
                $this.updatePopupEditor(id,result.data);
                $('.combo').select2();


                $(`#${id} .frm_editor_save`).attr('disabled', false)
            },
            error: function(xhr, status, error) {
                console.log("Error: " + error);
            }
 
        });
    }

    this.get_state = function(info, label) {

        if(info.id == "country_id_"+label)
            $("#city_id_"+label).html(`<option value="">Select</option>`)
        else
            $("#city_id_"+label).html(`<option value="">Select</option>`)

        if(info.value) {
            $.ajax({
                url: 'profile_ajax.php',
                type: 'POST',
                data: {
                    "cmd": "get_state",
                    country_id: info.value
                },
     
                success:function(data){
                    // console.log(data)
                    if(info.id == "country_id_"+label)
                        $("#state_id_"+label).html(data)
                    else
                        $("#state_id_"+label).html(data)

                    $('.combo').select2();
                },
                error: function(xhr, status, error) {
                    console.log("Error: " + error);
                }
     
            });
        } else {
            if(info.id == "country_id_"+label)
                $("#state_id_"+label).html(`<option value="">Select</option>`)
            else
                $("#state_id_"+label).html(`<option value="">Select</option>`)
        }

        
    }

    this.get_city = function(info, label) {

        if(info.value) {
            $.ajax({
                url: 'profile_ajax.php',
                type: 'POST',
                data: {
                    "cmd": "get_city",
                    state_id: info.value
                },
     
                success:function(data){
                    // console.log(data)
                    if(info.id == "state_id_"+label)
                        $("#city_id_"+label).html(data)
                    else
                        $("#city_id_"+label).html(data)

                    $('.combo').select2();
                },
                error: function(xhr, status, error) {
                    console.log("Error: " + error);
                }
     
            });
        } else {
            if(info.id == "state_id_"+label)
                $("#city_id_"+label).html(`<option value="">Select</option>`)
            else
                $("#city_id_"+label).html(`<option value="">Select</option>`)
        }
    }
    this.submit_frm_profile_edit_address = function(event, info) {
        event.preventDefault()
        var formData = $(info).serialize();

        $.ajax({
            url: 'profile_ajax.php',
            type: 'POST',
            data: formData,
 
            success:function(data){
                var data = JSON.parse(data);
                if(data.msg == "success") {
                    if(data.current_address)
                        $("#current_address").html(`<i class="fa fa-map-marker"></i> ${data.current_address}`)

                    if(data.permanent_address)
                        $("#permanent_address").html(`<i class="fa fa-home"></i> ${data.permanent_address}`)
                    $this.closePopupEditor("address");

                    // done
                    $(`#address .frm_editor_save`).attr('disabled', false)
                    $(`#address .frm_editor_save`).html('Save')
                }
            },
            error: function(xhr, status, error) {
                console.log("Error: " + error);
            }
 
        });
    }

    // FAVORITE/UNFAVORITE REGIONS
    this.loadFavoriteAddressEdit = function(id) {
        var e_user_id = 0
        if($("#ua_user_id").val())
            e_user_id = $("#ua_user_id").val()

        $.ajax({
            url: 'profile_ajax.php',
            type: 'POST',
            data: {
                "cmd": "loadFavoriteAddressEdit",
                e_user_id
            },
 
            success:function(data){
                var result = JSON.parse(data);
                $this.updatePopupEditor(id,result.data);
                $('.combo').select2();


                $(`#${id} .frm_editor_save`).attr('disabled', false)
            },
            error: function(xhr, status, error) {
                console.log("Error: " + error);
            }
 
        });
    }
    this.submit_fevorite_unfevorite_region = function(event, info) {
        event.preventDefault()
        var formData = $(info).serialize();

        
        $.ajax({
            url: 'profile_ajax.php',
            type: 'POST',
            data: formData,
 
            success:function(data){
                var data = JSON.parse(data);
                if(data.msg == "success") {
                    if(data.favorite_address)
                        $("#favorite_address").html(`<i class="fa fa-thumbs-up"></i> ${data.favorite_address}`)
                    if(data.unfavorite_address)
                        $("#unfavorite_address").html(`<i class="fa fa-thumbs-down"></i> ${data.unfavorite_address}`)
                    $this.closePopupEditor("favorite_unfavorite_address");

                    // done
                    $(`#favorite_unfavorite_address .frm_editor_save`).attr('disabled', false)
                    $(`#favorite_unfavorite_address .frm_editor_save`).html('Save')
                }
            },
            error: function(xhr, status, error) {
                console.log("Error: " + error);
            }
 
        });
    }

    // Education
    this.loadEducationEdit = function(id) {
        var e_user_id = 0
        if($("#ua_user_id").val())
            e_user_id = $("#ua_user_id").val()

        $.ajax({
            url: 'profile_ajax.php',
            type: 'POST',
            data: {
                "cmd": "loadEducationEdit",
                e_user_id
            },
 
            success:function(data){
                var result = JSON.parse(data);
                $this.updatePopupEditor(id,result.data);
                $('.combo').select2();


                $(`#${id} .frm_editor_save`).attr('disabled', false)
            },
            error: function(xhr, status, error) {
                console.log("Error: " + error);
            }
 
        });
    }
    this.add_more_education_field = function(ind, data) {
        var newInd = Number(ind)+1;
        /*if(newInd > 5) {
            alertCustom('Please input right information!',true,'Alert');
            return true
        }*/
        $("#more_education"+ind).append(`
            <div class="close_div">
                <button type="button" onclick="Profile.close_multiple_div(${ind})"><i class="fa fa-times"></i></button>
            </div>
            ${data}
        `);
        $("#paginate").append(`<div class="add_more_div" id="more_education${newInd}"></div>`);
        $("#ind").val(newInd)
        $(".combo").select2()
    }
    this.close_multiple_div = function(ind) {
        $("#more_education"+ind).empty()
    }
    this.submit_education = function(event, info) {
        event.preventDefault()
        var formData = $(info).serialize();

        var currentDate = new Date();
        var currentYear = currentDate.getFullYear();

        var err = 0;
        $('[name="degree_title[]"]').each(function() {
            if($(this).val() == "") {
                err++;
                alertCustom('Please type Exam/Degree Title!',true,'Information incomplete');
                return false
            }
        });
        $('[name="school_name[]"]').each(function() {
            if($(this).val() == "") {
                err++;
                alertCustom('Please type Institute Name!',true,'Information incomplete');
                return false
            }
        });
        $('[name="passing_year[]"]').each(function() {
            if(!$.isNumeric($(this).val())) {
                err++;
                alertCustom('Please input right passing year!',true,'Information incomplete');
                return false
            } else {
                if($(this).val() > currentYear || $(this).val() < 1980) {
                    err++;
                    alertCustom('Please input right passing year!',true,'Information incomplete');
                    return false
                }
            }
        });

        if(err) {
            $(`#education .frm_editor_save`).attr('disabled', false)
            $(`#education .frm_editor_save`).html('Save')
            return false;
        }

        
        $.ajax({
            url: 'profile_ajax.php',
            type: 'POST',
            data: formData,
 
            success:function(data){
                var data = JSON.parse(data);
                
                var result = '';
                if(data.length)
                    for(var i=0; i < data.length; i++) {
                        result += `
                            <li><i class="fa fa-graduation-cap"></i> ${data[i].degree_title}</li>
                            <ul>
                                <li><i class="fa fa-university"></i> ${data[i].school_name}</li>
                                ${data[i].address ? `<li><i class="fa fa-map-marker"></i> ${data[i].address}</li>` : ``}
                                ${data[i].results ? `<li><i class="fa fa-calculator"></i> ${data[i].results}</li>` : ``}
                                ${data[i].passing_year ? `<li><i class="fa fa-calendar"></i> ${data[i].passing_year}</li>` : ``}
                            </ul>
                        `;
                    }

                $("#education_section #ul_list").html(result)

                // done
                $(`#education .frm_editor_save`).attr('disabled', false)
                $(`#education .frm_editor_save`).html('Save')
                
                $this.closePopupEditor("education");
            },
            error: function(xhr, status, error) {
                console.log("Error: " + error);
            }
 
        });
    }

    // profession
    this.loadProfessionEdit = function(id) {
        var e_user_id = 0
        if($("#ua_user_id").val())
            e_user_id = $("#ua_user_id").val()

        $.ajax({
            url: 'profile_ajax.php',
            type: 'POST',
            data: {
                "cmd": "loadProfessionEdit",
                e_user_id
            },
 
            success:function(data){
                var result = JSON.parse(data);
                $this.updatePopupEditor(id,result.data);
                $('.combo').select2();


                $(`#${id} .frm_editor_save`).attr('disabled', false)
            },
            error: function(xhr, status, error) {
                console.log("Error: " + error);
            }
 
        });
    }
    this.add_more_profession_field = function(ind, data) {
        var newInd = Number(ind)+1;
        /*if(newInd > 10) {
            alertCustom('Please input right information!',true,'Alert');
            return true
        }*/
        $("#more_profession"+ind).append(`
            <div class="close_div">
                <button type="button" onclick="Profile.close_multiple_profession_div(${ind})"><i class="fa fa-times"></i></button>
            </div>
            ${data}
        `);
        $("#paginate").append(`<div class="add_more_div" id="more_profession${newInd}"></div>`);
        $("#ind").val(newInd)
        $(".combo").select2()
    }    
    this.close_multiple_profession_div = function(ind) {
        $("#more_profession"+ind).empty()
    }
    this.submit_profession = function(event, info) {
        event.preventDefault()
        var formData = $(info).serialize();

        var err = 0;
        $('[name="profession_type[]"]').each(function() {
            if($(this).val() == "") {
                err++;
                alertCustom('Please type Profession Type!',true,'Information incomplete');
                return false
            }
        });
        $('[name="position[]"]').each(function() {
            if($(this).val() == "") {
                err++;
                alertCustom('Please type Position/Title!',true,'Information incomplete');
                return false
            }
        });
        $('[name="company[]"]').each(function() {
            if($(this).val() == "") {
                err++;
                alertCustom('Please type Company!',true,'Information incomplete');
                return false
            }
        });

        if(err) {
            $(`#profession .frm_editor_save`).attr('disabled', false)
            $(`#profession .frm_editor_save`).html('Save')
            return false;
        }

        
        $.ajax({
            url: 'profile_ajax.php',
            type: 'POST',
            data: formData,
 
            success:function(data){
                var data = JSON.parse(data);

                var result = '';
                if(data.length)
                    for(var i=0; i < data.length; i++) {
                        result += `
                            <li><i class="fa fa-level-up"></i> ${data[i].position}</li>
                            <ul>
                                <li><i class="fa fa-industry"></i> ${data[i].company}</li>
                                ${data[i].address ? `<li><i class="fa fa-map-marker"></i> ${data[i].address}</li>` : ``}
                                <li><i class="fa fa-bullhorn"></i> ${data[i].title}</li>
                            </ul>
                        `;
                    }

                $("#profession_section #ul_list").html(result)

                // done
                $(`#profession .frm_editor_save`).attr('disabled', false)
                $(`#profession .frm_editor_save`).html('Save')
                $this.closePopupEditor("profession");
            },
            error: function(xhr, status, error) {
                console.log("Error: " + error);
            }
 
        });
    }

    // relatives
    this.loadRelativesEdit = function(id) {
        var e_user_id = 0
        if($("#ua_user_id").val())
            e_user_id = $("#ua_user_id").val()

        $.ajax({
            url: 'profile_ajax.php',
            type: 'POST',
            data: {
                "cmd": "loadRelativesEdit",
                e_user_id
            },
 
            success:function(data){
                var result = JSON.parse(data);
                $this.updatePopupEditor(id,result.data);
                $('.combo').select2();


                $(`#${id} .frm_editor_save`).attr('disabled', false)
            },
            error: function(xhr, status, error) {
                console.log("Error: " + error);
            }
 
        });
    }

    this.add_more_relatives_field = function(ind, data) {
        var newInd = Number(ind)+1;
        $("#more_relatives"+ind).append(`
            <div class="close_div">
                <button type="button" onclick="Profile.close_multiple_relatives_div(${ind})"><i class="fa fa-times"></i></button>
            </div>
            ${data}
        `);
        $("#paginate").append(`<div class="add_more_div" id="more_relatives${newInd}"></div>`);
        $("#ind").val(newInd)
        $(".combo").select2()
    }    
    this.close_multiple_relatives_div = function(ind) {
        $("#more_relatives"+ind).empty()
    }
    this.submit_relatives = function(event, info) {
        event.preventDefault()
        var formData = $(info).serialize();

        var err = 0;
        $('[name="relative_name[]"]').each(function() {
            if($(this).val() == "") {
                err++;
                alertCustom('Please type Relative name!',true,'Information incomplete');
                return false
            }
        });
        $('[name="relation[]"]').each(function() {
            if($(this).val() == "") {
                err++;
                alertCustom('Please add relationship!',true,'Information incomplete');
                return false
            }
        });

        if(err) {
            $(`#relatives .frm_editor_save`).attr('disabled', false)
            $(`#relatives .frm_editor_save`).html('Save')
            return false;
        }

        
        $.ajax({
            url: 'profile_ajax.php',
            type: 'POST',
            data: formData,
 
            success:function(data){
                var data = JSON.parse(data);

                var result = '';
                if(data.length)
                    for(var i=0; i < data.length; i++) {
                        result += `
                            <li><i class="fa fa-user"></i> ${data[i].relative_name}</li>
                            <ul>
                                <li><i class="fa fa-link"></i> ${data[i].relation}</li>
                                ${data[i].marital_status ? `<li><i class="fa fa-circle"></i> ${data[i].marital_title}</li>` : ``}
                                ${data[i].address ? `<li><i class="fa fa-map-marker"></i> ${data[i].address}</li>` : ``}
                                ${data[i].profession_type ? `<li><i class="fa fa-bullhorn"></i> ${data[i].title}</li>` : ``}
                                ${data[i].position ? `<li><i class="fa fa-level-up"></i> ${data[i].position}</li>` : ``}
                                ${data[i].company ? `<li><i class="fa fa-industry"></i> ${data[i].company}</li>` : ``}
                                ${data[i].degree_title ? `<li><i class="fa fa-graduation-cap"></i> ${data[i].degree_title}</li>` : ``}
                            </ul>
                        `;
                    }

                $("#relatives_section #ul_list").html(result)

                // done
                $(`#relatives .frm_editor_save`).attr('disabled', false)
                $(`#relatives .frm_editor_save`).html('Save')
            
                $this.closePopupEditor("relatives");
            },
            error: function(xhr, status, error) {
                console.log("Error: " + error);
            }
 
        });
    }


    // relatives
    this.loadAdditionalInformationEdit = function(id) {
        var e_user_id = 0
        if($("#ua_user_id").val())
            e_user_id = $("#ua_user_id").val()

        $.ajax({
            url: 'profile_ajax.php',
            type: 'POST',
            data: {
                "cmd": "loadAdditionalInformationEdit",
                e_user_id
            },
 
            success:function(data){
                var result = JSON.parse(data);
                $this.updatePopupEditor(id,result.data);
                $('.combo').select2();


                $(`#${id} .frm_editor_save`).attr('disabled', false)
            },
            error: function(xhr, status, error) {
                console.log("Error: " + error);
            }
 
        });
    }

    this.submit_additional_information = function(event, info) {
        event.preventDefault()
        var formData = $(info).serialize();

        
        $.ajax({
            url: 'profile_ajax.php',
            type: 'POST',
            data: formData,
 
            success:function(data){
                var data = JSON.parse(data);

                if(data.success == 'success') {
                    var result = '<li>'+data.additional_info+'</li>';

                    $("#additional_section #ul_list").html(result)
                }                

                // done
                $(`#additional_information .frm_editor_save`).attr('disabled', false)
                $(`#additional_information .frm_editor_save`).html('Save')
            
                $this.closePopupEditor("additional_information");
            },
            error: function(xhr, status, error) {
                console.log("Error: " + error);
            }
 
        });
    }

    // posted by
    this.loadPostedByEdit = function(id) {
        var e_user_id = 0
        if($("#ua_user_id").val())
            e_user_id = $("#ua_user_id").val()

        $.ajax({
            url: 'profile_ajax.php',
            type: 'POST',
            data: {
                "cmd": "loadPostedByEdit",
                e_user_id
            },
 
            success:function(data){
                var result = JSON.parse(data);
                $this.updatePopupEditor(id,result.data);
                $('.combo').select2();


                $(`#${id} .frm_editor_save`).attr('disabled', false)
            },
            error: function(xhr, status, error) {
                console.log("Error: " + error);
            }
 
        });
    }
    this.submit_posted_by = function(event, info) {
        event.preventDefault()
        var formData = $(info).serialize();
        
        $.ajax({
            url: 'profile_ajax.php',
            type: 'POST',
            data: formData,
 
            success:function(data){
                var data = JSON.parse(data);

                var result = `
                    ${data.poster_name ? `<li><i class="fa fa-user"></i> ${data.poster_name}</li>` : ``}
                    ${data.poster_phone ? `<li><i class="fa fa-phone"></i> ${data.poster_phone}</li>` : ``}
                    ${data.poster_address ? `<li><i class="fa fa-map-marker"></i> ${data.poster_address}</li>` : ``}
                `;

                $("#posted_by_section #ul_list").html(result)

                // done
                $(`#posted_by .frm_editor_save`).attr('disabled', false)
                $(`#posted_by .frm_editor_save`).html('Save')
            
                $this.closePopupEditor("posted_by");
            },
            error: function(xhr, status, error) {
                console.log("Error: " + error);
            }
 
        });
    }
    $(function(){
        $('.showModal').click(function(){
            var id = this.id;

            if($this.openPopupEditor(id,this.title,$this.hStub,$this.hStub,'wrapper_custom'))return;

            // close modal
            $('.icon_close, .frm_editor_cancel').click(function (){
                $this.closePopupEditor(id);
                return false;
            })


            // open modal
            if(id == "address") {
                $('#address .frm_editor_save').click(function (){
                    $(this).attr('disabled', true)
                    $(this).html(btnLoader)
                    $("#frm_profile_edit_address").submit()
                    return false;
                })
                $this.loadAdressEdit(id)
            }
            else if(id == "favorite_unfavorite_address") {
                $('#favorite_unfavorite_address .frm_editor_save').click(function (){
                    $(this).attr('disabled', true)
                    $(this).html(btnLoader)
                    $("#frm_profile_edit_favorite_unfavorite_address").submit()
                    return false;
                })
                $this.loadFavoriteAddressEdit(id)
            }
            else if(id == "education") {
                $('#education .frm_editor_save').click(function (){
                    $(this).attr('disabled', true)
                    $(this).html(btnLoader)
                    $("#frm_update_education").submit()
                    return false;
                })
                $this.loadEducationEdit(id)
            }
            else if(id == "profession") {
                $('#profession .frm_editor_save').click(function (){
                    $(this).attr('disabled', true)
                    $(this).html(btnLoader)
                    $("#frm_update_profession").submit()
                    return false;
                })
                $this.loadProfessionEdit(id)
            }
            else if(id == "relatives") {
                $('#relatives .frm_editor_save').click(function (){
                    $(this).attr('disabled', true)
                    $(this).html(btnLoader)
                    $("#frm_update_relatives").submit()
                    return false;
                })
                $this.loadRelativesEdit(id)
            }
            else if(id == "additional_information") {
                $('#additional_information .frm_editor_save').click(function (){
                    $(this).attr('disabled', true)
                    $(this).html(btnLoader)
                    $("#frm_update_additional_information").submit()
                    return false;
                })
                $this.loadAdditionalInformationEdit(id)
            }
            else if(id == "posted_by") {
                $('#posted_by .frm_editor_save').click(function (){
                    $(this).attr('disabled', true)
                    $(this).html(btnLoader)
                    $("#frm_update_posted_by").submit()
                    return false;
                })
                $this.loadPostedByEdit(id)
            }

        })
    })



    return this;
}