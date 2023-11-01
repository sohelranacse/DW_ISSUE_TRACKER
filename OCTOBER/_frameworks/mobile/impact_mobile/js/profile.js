var CProfile = function(guid, sending_messages_per_day) {

    var $this=this;

    this.guid = guid;
    this.uid = 0;
    this.ua_user_id = 0;
    this.ajax = {status:0,send_msg:0,email:0};
    this.sendingMessagesPerDay = sending_messages_per_day*1;

    this.setData = function(data){
        for (var key in data) {
           $this[key] = data[key];
        }
    }

    this.init = function(){
        console.log('PROFILE INIT',$this.uid, $this.guid, $this.isReportUser);
        //history.replaceState(pageHistory, curPageData.title, location.href.split('#')[0]+$this.defaultHashPage['profile_view']);//???????????????????????????

        $this.setTabsLoadPage();
        clPhoto.initLazyLoadPhoto();
        $(function(){
            if($this.uid && $this.guid != $this.uid && activePage == 'profile_view.php' ){
                if ($this.isReportUser) {
                    $jq('#footer_more_menu_report_user').remove();
                }
                $this.blockUserTitleMenu();
                $jq('#footer_more_menu_show').click(function(e){
                    if(e.target!=this)return;
                    $this.toggleMoreMenu()
                })
                $doc.on('click', function(e){
                    var $tar=$(e.target);
                    if(!$tar.is('#footer_more_menu_show')&&!$tar.closest('#footer_more_menu_show')[0]){
                        $this.toggleMoreMenu(true)
                    }
                    if(!$tar.is('#photo_gallery_report')&&!$tar.closest('#photo_gallery_report')[0]){
                        clPhoto.toggleMoreMenu(true);
                    }
                })
            }
        })
    }

    this.toggleMoreMenu = function(notOpen){
        notOpen=notOpen||0;
        var $menu=$jq('#footer_more_menu_popup');
        if($menu.is('.animate'))return;
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
        var color=$this.getColor(num), brColor=brColor=colorRgbToHex($('.bl_profile').css('backgroundColor'));
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
		}
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
        $('#'+id).closest('.chart_statistics').addClass('to_show');
	}
	/* Chart */
    /* Action */
    /* Edit status */
    this.initStatusEditor = function(val){
        if(val==l('your_status_here'))val='';
        $this.setData({status_value:val});
        $jq('#profile_status_edit').keydown(function(e){
            if (e.keyCode==13) {
                $(this).blur();
                return false;
            }
        }).blur($this.saveStatus);
    }

    this.showStatusEditor = function(){
        if($this.ajax.status)return;
        var val=$this.status_value;
        if(val==l('your_status_here'))val='';
        $jq('#profile_status').hide();
        if (!$jq('#profile_status').is('.init')) {
            $win.on('resize',function(){
                var top=$jq('#profile_bot').offset().top+$jq('#main').scrollTop()+$jq('#profile_bot').height(),
                d=$win.height()-top;
                if(d<0){
                    $jq('#main').stop().animate({scrollTop:-1*d},300);
                }
            })
        }
        $jq('#profile_status').addClass('init');
        $jq('#profile_status_edit').val(val).show().focus()[0].select();
    }

    this.hideStatusEditor = function(val){
        if(!val)val=l('your_status_here');
        $jq('#profile_status_edit').hide();
        $jq('#profile_status').text(val).show();
    }

    this.statusError = function(){
        $jq('#profile_status').text($this.status_value);
        $jq('#profile_status_editor_btn').removeLoader();
        serverError();
    }

    this.saveStatus = function(){
        if($this.ajax.status)return;
        $this.ajax.status=true;
        var val=$.trim($jq('#profile_status_edit').val());
        $this.hideStatusEditor(val);
        if(val!==$this.status_value){

            var e_user_id = $this.uid
            /*if($("#ua_user_id").val())
                e_user_id = $("#ua_user_id").val()*/

            $.ajax({type:'POST',
                url:url_server+'?cmd=update_profile_status&id='+e_user_id,
                data:{data:val},
                beforeSend: function(){
                    $jq('#profile_status_editor_btn').addLoader();
                },
                success: function(res){
                    var data=checkDataAjax(res);
                    if(data) {
                        $this.status_value=val;
                        $jq('#profile_status_editor_btn').removeLoader();
                    }
                    else $this.statusError();
                    $this.ajax.status=false;
                },
                error:function(){
                    $this.statusError();
                    $this.ajax.status=false;
                }
            })
        }else{
            $this.ajax.status=false;
        }
    }
    /* Edit status */
    /* Like */
    this.sendLikeProfile = function(uid,$btn){
        if($this.ajax.like||$this.ajax.blocked)return;
        if($this.isBlockedUser){
            showConfirm(l('the_profile_will_be_unblocked_if_you_like_it'), function(){
                $this.sendLike(uid,$btn,1);
            })
        }else{
            closeAlert();
            $this.sendLike(uid,$btn);
        }
    }

    this.sendLike = function(uid,$btn,unblock){
        $btn=$btn||{};
        $this.ajax.like=true;
		var status=$btn.is('.selected')?'N':'Y';
        $btn.toggleClass('selected');
        unblock=unblock||0;
        $.post(url_ajax+'?cmd=set_want_to_meet',{uid:uid,status:status,unblock:unblock}, function(res){
            var data=checkDataAjax(res);
            if(data){
                if(data['number_blocked']){
                    $this.blockUserResponse();
                }
                if(parseInt(data['isMutual'])) {
                    showConfirmUserPhoto({photo:data['urlPhoto']}, '{user_photo}'+l('alert_title_mutual_like'), false, false, false, false, true);
                }else if($btn.is('.selected')&&!$.cookie('alert_liked_this_user')){
                    $.cookie('alert_liked_this_user', true);
                    showAlert(l('youve_liked_this_user'));
                }
                updateMenuCounterAll(data);
            }else{
                serverError();
                $btn.toggleClass('selected');
            }
            $this.ajax.like=false;
        })
    }
    /* Like */
    /* Block user */
    this.confirmBlockUser = function(){
        if($this.ajax.blocked||!requestUserId||$this.guid==requestUserId)return;
        $this.toggleMoreMenu(true);
        var msg=l('user_block_alert');
        if($this.isBlockedUser){
            msg=l('user_unblock_alert');
        }
        showConfirm(msg, $this.blockUser);
    }

    this.blockUserTitleMenu = function(){
        $jq('#footer_more_menu_block').text($this.isBlockedUser?l('profile_menu_user_unblock'):l('profile_menu_user_block'))
    }

    this.blockUserResponse = function(){
        $this.isBlockedUser = !$this.isBlockedUser;
        $jq('#profile_user_blocked_bl')[$this.isBlockedUser?'addClass':'removeClass']('to_show');
        $this.blockUserTitleMenu();
    }

    this.blockUser = function(cmd){
        closeAlert();
        $this.ajax.blocked=true;
        var cmd=$this.isBlockedUser?'user_unblock':'block_visitor_user';
        $.post(url_ajax+'?cmd='+cmd,
            {user_id:requestUserId,user_to:requestUserId},function(res){
            $this.ajax.blocked=false;
            var data=checkDataAjax(res);
            if(data){
                var msg=$this.isBlockedUser?l('the_user_has_been_unlocked'):l('the_user_has_been_blocked');
                showAlertDelayShow(msg);

                $this.blockUserResponse();

                if($this.isBlockedUser)scrollMainTo();
                updateMenuCounterAll(data);
                if(cmd=='block_visitor_user'){
                    $jq('#btn_send_like').removeClass('selected');
                }
            }else{
                serverError();
            }
        })
    }
    /* Block user */
    /* Profile send msg */
    this.openProfileMsg = function($btn){
        if(requestUserOnline&&$this.countMsgIm){
            goToPage($btn)
        }else{
            $this.ua_user_id = $btn.attr('data-id');
            if($this.ua_user_id == $this.guid) {
                showAlertDelayShow(`You can't message yourself.`)
                return
            }

            clCommon.openPopupMessage('pp_profile_msg',l('sent_message'),$this.sendProfileMsg,false,l('write_your_message_here'))
        }
    }

    this.sendProfileMsg = function(){
        if($this.ajax.send_msg)return;
        $this.ajax.send_msg=true;
        var id='pp_profile_msg', $btn=clCommon.btnSend[id], $txt=clCommon.textMsg[id],
            msg=$.trim($txt.val());
			
		msg=emojiToHtml(msg);
		
        $btn.prop('disabled',true).addLoader();
        $txt.prop('disabled',true);
        $.post(url_main+'messages.php?cmd=send_message_one',
              {ajax:1,display:'one_chat',user_to:$this.ua_user_id,msg:msg,to_delete:0,send_msg_from_profile:1},function(res){
                var data=checkDataAjax(res);
                var fn=function(){
                    if(data){
                        if (data=='buy_credits') {
                            var msg=l('you_have_no_enough_credits');
                            if(!isInAppPurchaseEnabled){
                                msg += '<br>'+l('buy_credits');
                                showAlert(msg)
                            }else{
                            var url=urlPagesSite.upgrade+'?action=refill_credits&service=message&request_uid='+$this.ua_user_id;
                            showConfirmToPage(msg, url, l('btn_buy_credits'), false)
                            }
                        }else if(data=='block_list'){
                            showAlertDelayShow(l('you_are_in_block_list'))
                        }else if(data=='you_are_in_block_list_spam'){
                            showAlertDelayShow(l('your_account_is_blocked_for_the_suspicios_usage_of_the_messaging'))
                        }else if(data=='msg_limit_is_reached_f' || data=='msg_limit_is_reached_m'){
                            var msg=l(data+'_impact_mobile').replace(/{link_start}|{link_end}/g, '')
                                                        .replace(/{number}/, $this.sendingMessagesPerDay);
                            showConfirmToPage(msg, urlPagesSite.upgrade, l('upgrade'), false);
                        }else{
                            $this.countMsgIm=1;
                            showAlertDelayShow(l('message_sent'))
                        }
                        /*$('#profile_im_reply_rate_decor')
                        .removeClass('im_reply_rate_high im_reply_rate_medium im_reply_rate_low')
                        .addClass('im_reply_rate_'+data);*/
                    }else{
                        serverError();
                    }
                }
                clCommon.closePopupMessage('pp_profile_msg',l('sent_message'),fn);
                $this.ajax.send_msg=false;
        })
    }
    /* Profile send msg */
    /* Action */
    this.prepareLinkTabs = function(id){
        if(activePage!='profile_view.php')return;
        $jq('.link_tabs').parent('.bl').removeClass('selected');
        if(id=='#albums'){
            $jq('#link-albums').parent('.bl').addClass('selected');
        }else{
            $jq('#link-tabs-1').parent('.bl').addClass('selected');
        }
    }

    this.setTabTrans=false;
    this.curHash='';

    this.defaultHashPage = {
        profile_view   : '#tabs-1',
        search_results : '#search'
    };
    this.allowedHashPage = {
        profile_view   : [/^#albums$|^#albums-public$|^#albums-private$|^#albums-video$|^#tabs-1$|^#tabs-4$|^#tabs-5$/,
                          /^#photo-\d+$|^#video-\d+$/],
        search_results : [/^#filter$|^#search$/]
    };

    this.allowedHashPageLoad = {
        profile_view   : /^#tabs-1$|^#albums$|^#albums-public$|^#albums-private$|^#albums-video$|^#photo-\d+$|^#video-\d+$/,
        search_results : /^#filter$|^#search$/
    };
    this.isHashLoadPage = function(key,id){
        return $this.allowedHashPageLoad[key] && $this.allowedHashPageLoad[key].test(id);
    }

    this.getDefaultHash = function(id){
        id=id||location.hash;
        var key=activePage.replace('.php', '');
        if($this.allowedHashPage[key]){
            var id0=false;
            $this.allowedHashPage[key].forEach(function(item) {
                if(item.test(id)){
                    id0=true;
                    return false;
                }
            })
            if(id0)return id;
            //if(in_array(id,$this.allowedHashPage[key]))return id;
            $this.setHash($this.defaultHashPage[key]);
        }
        return false;
    }

    this.prepareHashPage = function(id){
        //console.log(99999999999999,id, /^#photo-\d+$/.test(id)||/^#video-\d+$/.test(id));
        if($this.isHashAlbums(id)){
            id=id.replace(/-public|-private|-video/i, '');
        }else if(/^#photo-\d+$|^#video-\d+$/.test(id)){
            id='#tabs-3';
        }
        return id;
    }

    /* Albums */
    this.allowedHashAlbums = ['#albums', '#albums-public', '#albums-private', '#albums-video'];
    this.isHashAlbums = function(id){
        return in_array(id,$this.allowedHashAlbums);
    }

    this.setCurrentHashAlbums = function(){
        var setHashAlbums='';
        $this.allowedHashAlbums.forEach(function(item) {
            var $scrollTo=$(item+'_to_scroll');
            if($scrollTo[0] && $scrollTo.offset().top-100<0){
                setHashAlbums=item;
            }
        })
        if(setHashAlbums && setHashAlbums!=location.hash && window.history && history.replaceState){
            //console.log(location.href.split('#')[0]+setHashAlbums);
            //$this.setHash(setHashAlbums);
            history.replaceState(pageHistory, curPageData.title, location.href.split('#')[0]+setHashAlbums);
        }
    }

    this.getLastPosAlbums = function($scrollTo){
        var //d=curHash=='#albums-public'?93:95,
            top=$scrollTo.offset().top-93,
            lastPos=$.cookie('last_pos_scroll_albums');
        if(lastPos){
            lastPos=lastPos.split('=');
            //if(lastPos[0]==curHash){
                top=lastPos[1];
            //}
        }
        return top;
    }

    this.scrollToLastPosAlbums = function($scrollTo,callback){
        if(typeof callback!='function')callback=function(){};
        var top=$this.getLastPosAlbums($scrollTo);
        scrollMainTo(callback,0,top,30)
    }

    this.resetLastPosAlbums = function(){
        $.cookie('last_pos_scroll_albums','');
    }
    /* Albums */

    this.setTabsLoadPage = function(){
        $this.$blMainContent=$jq('#main_content_block');
        var id0=location.hash,
            id=curHash=$this.defaultHashPage['profile_view'];
        if(id0&&$this.isHashLoadPage('profile_view',id0)){
            id=$this.prepareHashPage(id0);
            curHash=id0;
        }else{
            $this.setHash(id);
        }
        $this.curHash=curHash;

        $this.prepareLinkTabs(id);

        if(/^#photo-\d+$|^#video-\d+$/.test(id0)){
            var pid=id0.replace(/#photo-|#video-/i, '');
            $(id+'_switch').addClass('calc_vh to_hide_load_page').addClass('target');
            if(/^#video-\d+$/.test(id0)){
                pid='v_'+pid;
            }

            var dTopLoader='10px';
            if(clPhoto.galleryPhotosInfo[pid] && clPhoto.galleryPhotosInfo[pid]['description']){
                dTopLoader='-10px';
            }
            $('<div id="layer_block_page_load_gallery" class="layer_block_page_load_gallery"></div>')
            .html(getLoader('page_load_gallery',false,true).css('marginTop', dTopLoader))
            .appendTo('body');


            clPhoto.openGalleryId(pid,false,false,true,true);
            $(function(){
                $jq('#footer').addClass('grey');
                $('.banner_footer_mobile_user').addClass('grey');
            })
            $jq('#main_content_block').removeClass('target');
        }else{
            $(id+'_switch').addClass('target');
            $jq('#main_content_block').addClass('target');
        }
        $this.initHash();

        setTimeout(function(){
            var $scrollTo=$(curHash+'_to_scroll');
            if($scrollTo[0]){
                $this.scrollToLastPosAlbums($scrollTo);
            }else{
                $this.resetLastPosAlbums();
            }
            $jq('#main').scroll(function(){
                if(!$jq('#main').is(':animated')
                    && !$this.setTabTrans
                    && !isUploadPage
                    && !clPhoto.curPid){
                    if($this.isHashAlbums(location.hash)){
                        $this.setCurrentHashAlbums();
                    }
                    $.cookie('last_pos_scroll_albums', location.hash+'='+$jq('#main')[0].scrollTop);
                }
            })
        },200)
    }

    this.initHash = function(){
        $win.on('hashchange', function(){$this.setTabs()})
    }

    this.setHash = function(id){
        location.hash=id;
    }

    this.setProfileDefaultTabs = function(){
        $this.setHash($this.defaultHashPage['profile_view']);
    }

    this.setSearchDefaultTabs = function(){
        $this.setHash($this.defaultHashPage['search_results']);
    }

    this.setProfileAlbumsTabs = function(id){
        id=id||'#albums';
        $this.setHash(id);
    }

    this.goToAlbums = function(){
        if($this.isUploadPhotoToSeePhotos){
            showConfirmToPage($this.pleaseUploadPhotoToSeePhotos, urlPagesSite.profile_view+'?show=albums', l('ok'), false)
        }else{
            $this.setFnTabsEnd(function(){});
            $this.setProfileAlbumsTabs('#albums-public');
        }
    }

    this.resetHash = function(url){
        if(url.indexOf('profile_view')!==-1&&$this.curHash){
            $this.setHash($this.curHash)
        }
    }

    this.loadTabs = function(id,fn){
        $this.setHash(id);
        if (typeof fn=='function') {
            setTimeout(fn,600)
        }
    }

    this.endTransTabs = function(){
        $this.switchTransEnd();
        $this.setTabTrans=false;
        hideLayerBlockPage();
        $jq('#main').scroll();
    }

    this.endTransS = function(){
        $this.$contWrap.removeClass('trans_up trans_down',0).removeAttr('style');
        $this.visTab.add($this.contTab).toggleClass('target move_to_left trans_none',0)
            .removeAttr('style').delay(1).removeClass('trans_none',0);
        //$this.contTab.removeAttr('style');
        $this.endTransTabs();
    }

    this.fnTabEnd=false;
    this.setFnTabsEnd = function(fn){
        $this.fnTabEnd = fn;
    }

    this.switchTransEnd = function(){
        if(typeof $this.fnTabEnd === 'function'){
            $this.fnTabEnd();
        }
        $this.fnTabEnd=false;
    }

    this.tabsEnd = function(){
        $this.setTabTrans=false;
        $this.switchTransEnd();
        hideLayerBlockPage();
    }


    this.setTabs = function(id){
        if($this.setTabTrans||isUploadPage)return;
        /*if(clCommon.isVisibleHeaderMenu()){
            //if($this.curHash)location.hash=$this.curHash;
            //clCommon.hideHeaderMenu();
            return;
        }*/
        id=$this.getDefaultHash(id);
        if(id===false)return;
        var id0=id;
        id=$this.prepareHashPage(id);


        $this.contTab=$(id+'_switch');
        console.log('HASH CHAGE SID:'+id+'_switch');

        if(!$this.contTab[0]||$this.contTab.is('.target')){
            return;
        }
        if(id=='#tabs-4'&&!$('.pp_popup_editor.visible','#tabs-4_switch')[0]){
            $this.setProfileDefaultTabs();
            return;
        }
        if(id=='#tabs-5'&&!$('.pp_popup_editor.visible','#tabs-5_switch')[0]){
            $this.setProfileDefaultTabs();
            return;
        }
        if(id=='#tabs-3'&&$jq('#tabs-3_switch').is(':empty')){
            $this.setHash('#albums');
            return;
        }

        if($this.isHashAlbums(id0) && typeof $this.fnTabEnd!='function'){
            var $scrollTo=$(id0+'_to_scroll');
            if($scrollTo[0]){
                $this.setFnTabsEnd(function(){
                    $this.scrollToLastPosAlbums($scrollTo);
                })
            }
        }

        showLayerBlockPageNoLoader();
        console.log('hashchange sid start:'+id+'_switch');
        $this.curHash=id;
        $this.setTabTrans=true;
        var $vis=$('.profile_info_content.target','#cont_wrap'),
            $main=$jq('#main'),$blProfileCont=$jq('#main_content_block'),
            $contWrap=$jq('#cont_wrap'),$blPage=$jq('#tabs-3_switch, #filter_switch'),
            $footer=$jq('#footer');
        $this.$contWrap=$contWrap;
        $this.visTab=$vis;


        var loadTab=function(){
            var clTrTnMl='target trans_none move_to_left',clTnMl='trans_none move_to_left',
                clTrTn='target trans_none';

            var visH=$blProfileCont.height(),contH=$blPage.height(),
                wCH=$win.height()-$this.blHeaderHeight,cl='to_hide';
            if(visH>wCH)cl='to_instant_hide';
            if(id=='#tabs-3'||id=='#filter'){
                var fnChangePage=function(){
                    //$blProfileCont.css({transform: 'translate3d(-30px, 0, 0)'});
                    $blProfileCont.css({height:wCH});
                    $blPage.css({overflow:'hidden',height:wCH}).stop().show().delay(10)
                    .toggleClass('move_to_left',0).oneTransEnd(function(){
                        if(id=='#tabs-3'){
                            $footer.addClass('grey');
                            $('.banner_footer_mobile_user').addClass('grey');
                            $jq('.profile_info_content',$blProfileCont).removeClass('target move_to_left',0);
                        }else{
                            $jq('#search_switch').removeClass('target')
                        }
                        $blPage.toggleClass(clTrTnMl,0).removeAttr('style').removeClass('trans_none');
                        $blProfileCont.toggleClass(clTrTn,0).removeAttr('style').removeClass('trans_none');

                        if(contH>wCH)$footer.addClass('trans_none');
                        $footer.toggleClass(cl,0).removeClass('trans_none');
                        $this.tabsEnd();
                    },'transform');
                }
                if($footer[0]){
                    $footer.oneTransEnd(fnChangePage).addClass(cl);
                }else{
                    fnChangePage()
                }
                return;
            }
            if($blPage.is('.target')){
                var fnChangePageBack=function(){
                    $this.contTab.addClass('target');
                    $blProfileCont.css({overflow:'hidden'}).height(wCH).addClass('target',0);//.removeClass(clTnMl,0);
                    $blPage.css({display:'block',overflow:'hidden'}).height(wCH).toggleClass(clTrTnMl,0)
                        .oneTransEnd(function(){
                            $footer.removeClass('grey');
                            $('.banner_footer_mobile_user').removeClass('grey');
                            $blPage.removeAttr('style');
                            $blProfileCont.removeAttr('style')
                            if(contH>wCH)$footer.addClass('trans_none');
                            $footer.toggleClass(cl,0).removeClass('trans_none');
                            if(activePage=='profile_view.php')clPhoto.clearGallery();
                            $this.endTransTabs();
                            //if(id=='#albums')$main.scroll();
                        },'transform').toggleClass(clTnMl,0);
                }
                if($footer[0]){
                    $footer.oneTransEnd(fnChangePageBack).addClass(cl);
                }else{
                    fnChangePageBack()
                }
                $this.prepareLinkTabs(id);
                return;
            }


            $this.contTab.css({display:'block'});
            if($this.contTab.find('textarea')[0])$win.resize();

            var hv=$vis.height()+18, hc=$this.contTab.height()+18, wh=$win.height(),
                clientVisH=wh-$contWrap.offset().top,clientVisHm=clientVisH;
            if(id=='#tabs-1'){
                console.log('Show Tabs-1');
                $this.contTab.height(clientVisHm).addClass(clTrTnMl,0)
                             .removeClass(clTnMl,0);
                $vis.css({display:'block',height:clientVisHm}).toggleClass(clTrTnMl,0)
                    .oneTransEnd(function(){
                        $vis.removeAttr('style');
                        $this.contTab.addClass('target',0).removeAttr('style');
                        $this.endTransTabs();
                },'transform').removeClass(clTnMl,0);
            }else{
                console.log('Show Tabs2');
                //if($vis.prev('.profile_info_content')[0]==$this.contTab[0]){
                if($vis.prev($this.contTab)[0]){
                    $vis.after($this.contTab);
                }
                if(hv<clientVisH||hc<clientVisH){
                    if(hv>=clientVisH){
                        console.log('Content < Visible - height to "HC" AFTER the animation');
                        $contWrap.height(clientVisHm).addClass('trans_up',0);
                        $vis.height(clientVisHm).addClass('move_to_left',0);
                        $this.contTab.height(clientVisHm).oneTransEnd(function(e){
                            $contWrap.oneTransEnd($this.endTransS,'height').height(hc)
                        },'transform').addClass('move_to_left',0);
                    }else{
                        console.log('Content > Visible - height to "HC" BEFORE the animation');
                        $contWrap.height(hv).addClass('trans_down',1,function(){
                            $contWrap.oneTransEnd(function(){
                                $vis.height(clientVisHm).addClass('move_to_left',0);
                                $this.contTab.height(clientVisHm).oneTransEnd(function(){
                                    $contWrap.oneTransEnd($this.endTransS,'height').height(hc)
                                },'transform').addClass('move_to_left',0);
                            },'height').height(clientVisHm);
                        })
                    }
                }else{
                    $this.contTab.oneTransEnd($this.endTransS,'transform');
                    $vis.add($this.contTab).height(clientVisHm).addClass('move_to_left',0);
                    //$vis.height(clientVisHm).addClass('move_to_left',0);
                    //$this.contTab.height(clientVisHm).oneTransEnd($this.endTransS,'transform').addClass('move_to_left',0);
                    console.log('No need to change the height');
                }
            }
            $this.prepareLinkTabs(id);
        }
        var top=0,mTop=$main.scrollTop();
        if(in_array(id,['#tabs-1', '#albums', '#tabs-4', '#tabs-5'])&&!$blPage.is('.target')){
            top=$contWrap.offset().top+mTop-50-24;//$this.blHeaderHeight
        }
        if(mTop==top){//||mTop==(top+1)
            loadTab()
        }else{
            scrollMainTo(loadTab,mTop,top,35)
        }
    }

    $this.handlerLinkResetFn={};
    this.handlerLinkSetTabs = function($el,cmd,data,prf,id){
        id=id||'#tabs-4';
        $el=$el||[];
        data=data||{};
        prf=prf||'';
        //if(!/#tabs-[4]/.test(id))return;
        var $cont=$(id+'_switch');
        if ($cont.is('.target')) {
            return;
        }
        var $pp=$('#'+cmd+prf,$cont);
        $cont.find('.pp_popup_editor').removeClass('visible');
        $el[0]&&$el.addLoader();
        showLayerBlockPageNoLoader();
        if($pp[0]){
            var rFn=cmd+prf;
            console.log(rFn);
            if(typeof $this.handlerLinkResetFn[rFn]=='function'){
                $this.handlerLinkResetFn[rFn]();
            }
            $this.setFnTabsEnd(function(){$el[0]&&$el.removeLoader()});
            $pp.addClass('visible');
            $this.loadTabs(id);
            return;
        }

        var e_user_id = $this.uid
        /*if($("#ua_user_id").val())
            e_user_id = $("#ua_user_id").val()*/
        console.log(`${url_ajax}?cmd=${cmd}&view=mobile&e_user_id=${e_user_id}`);

        // 1. profile edit main - name, location - pp_profile_edit_main
        // 2. about - pp_profile_about_edit
        $.post(`${url_ajax}?cmd=${cmd}&view=mobile&e_user_id=${e_user_id}`,data,function(res){
            var data=checkDataAjax(res);
            if(data!==false){
                $this.setFnTabsEnd(function(){$el[0]&&$el.removeLoader()});
                $cont.append(data);
                $this.loadTabs(id);
            }else{
                $el[0]&&$el.removeLoader();
                hideLayerBlockPage();
                serverError();
            }
        })
    }

    this.handlerLinkSetTabs2 = function($el,cmd,data,prf,id){
        id=id||'#tabs-4';
        $el=$el||[];
        data=data||{};
        prf=prf||'';
        //if(!/#tabs-[4]/.test(id))return;
        var $cont=$(id+'_switch');
        if ($cont.is('.target')) {
            return;
        }
        var $pp=$('#'+cmd+prf,$cont);
        $cont.find('.pp_popup_editor').removeClass('visible');
        $el[0]&&$el.addLoader();
        showLayerBlockPageNoLoader();
        if($pp[0]){
            var rFn=cmd+prf;
            console.log(rFn);
            if(typeof $this.handlerLinkResetFn[rFn]=='function'){
                $this.handlerLinkResetFn[rFn]();
            }
            $this.setFnTabsEnd(function(){$el[0]&&$el.removeLoader()});
            $pp.addClass('visible');
            $this.loadTabs(id);
            return;
        }

        // my code
        var e_user_id = $this.uid
        /*if($("#ua_user_id").val())
            e_user_id = $("#ua_user_id").val()*/
        console.log(cmd, e_user_id);

        $.ajax({
            url: '../profile_ajax.php',
            type: 'POST',
            data: {
                "cmd": cmd,
                "mobile": 1,
                e_user_id
            }, 
            success:function(data){
                var data=checkDataAjax(data);
                // console.log(data)
                if(data!==false){
                    $this.setFnTabsEnd(function(){$el[0]&&$el.removeLoader()});
                    $cont.append(data);
                    $this.loadTabs(id);
                }else{
                    $el[0]&&$el.removeLoader();
                    hideLayerBlockPage();
                    serverError();
                }
                $('.combo').select2();
            },
            error: function(xhr, status, error) {
                console.log("Error: " + error);
            }
 
        });
    }

    this.goUser = function(link){
        $(link).css({opacity:'.6', transition:'opacity .4s'})
               .closest('li').append(getLoader('loader_user',false,true));
        goToPage(link);
    }

    this.updateServerDataUser = function(status,countMsgIm){
        status=status*1;
        $this.countMsgIm=countMsgIm*1;
        if($this.guid!=$this.uid&&requestUserOnline!=status){
            $jq('#profile_status_online')[status?'addClass':'removeClass']('to_show');
            requestUserOnline=status;
        }
    }

    this.updateServerMyData = function(allowedFeature){
        userAllowedFeature=allowedFeature;
        if ($('.btn_upload_img:visible')[0]) {
            messages.initCheckPaydUploadImage();
        }
    }

    /* Email not confirmed */
    this.notConfirmedEmailInit = function(){
        $this.$frmNotConfirmedEmail = $('#form_email_not_confirmed');
        $this.$frmNotConfirmedEmailMail = $('#form_email_not_confirmed_mail');
        $this.$frmNotConfirmedEmailSubmit = $('#form_email_not_confirmed_submit');

        $this.$frmNotConfirmedEmailMail.keydown(function(e){
            if (e.keyCode == 13) {
                $this.$frmNotConfirmedEmailSubmit.click();
                return false;
            }
        });

        $this.$frmNotConfirmedEmailSubmit.click(function(){
            isSendConfirmedEmail=true;
            if($this.ajax.email)return;
            if($this.validateEmailNotConfirmed()){
                $this.$frmNotConfirmedEmail.submit();
            }
            $this.$frmNotConfirmedEmailSubmit.prop('disabled',true);
            return false;
        });

        var isSendConfirmedEmail=false;
        $this.$frmNotConfirmedEmail.submit(function(){
            $this.ajax.email=1;
            $this.$frmNotConfirmedEmailSubmit.addLoader();
            $this.$frmNotConfirmedEmail.ajaxSubmit({success:$this.confirmedEmailFrmResponse});
            $this.$frmNotConfirmedEmailMail.prop('disabled', true);
            return false;
        });

        $this.$frmNotConfirmedEmailMail.on('change propertychange input',function(){
            var val=trim(this.value);
            if(isSendConfirmedEmail){
                if($this.validateEmailNotConfirmed()){
                    $this.$frmNotConfirmedEmailSubmit.prop('disabled',false);
                }else{
                    $this.$frmNotConfirmedEmailSubmit.prop('disabled',true);
                }
            }else{
                $this.$frmNotConfirmedEmailSubmit.prop('disabled',val?false:true);
            }
        }).focus(function(){
            showErrorWrongEl($(this));
        }).blur(function(){
            hideError($(this))
        })
    }

    this.validateEmailNotConfirmed = function(){
        var val=trim($this.$frmNotConfirmedEmailMail.val()),isError=false;
        if(!checkEmail(val)){
            showError($this.$frmNotConfirmedEmailMail,l('incorrect_email'));
        }else{
            isError=true;
            resetError($this.$frmNotConfirmedEmailMail)
        }
        return isError;
    }

    this.confirmedEmailFrmResponse = function(data){
        var data = checkDataAjax(data);
        $this.ajax['email']=0;
        $this.$frmNotConfirmedEmailMail.prop('disabled', false);
        $this.$frmNotConfirmedEmailSubmit.removeLoader();
        if (data!==false){
            if(data == '') {
                $this.$frmNotConfirmedEmailMail.val('');
                showAlertAppearDelayClose(l('email_sent'));
            } else {
                showError($this.$frmNotConfirmedEmailMail,data);
            }
        }else{
            serverError();
        }
    }
    /* Email not confirmed */
    this.setSrcPhotoProfile = function(url){
        var $img=$jq('#profile_photo_b');
        if($img[0].complete){
            $img.parent('div').addClass('to_show');
            return;
        }else if($img.is('.empty_photo, .plug_private_photos')){
            $img.one('load', function(){
                $img.parent('div').addClass('to_show');
            })
            return;
        }
        var urlM=url.replace(/_bm|_b/, '_m');
        $img[0].src=urlM;
        $img.addClass('to_blur');
        $('<img>').one('load', function(){
            $img.before($img.clone().removeClass('to_blur').attr('src',url)).oneTransEnd(function(){
                $img.remove();
            }).addClass('to_hide');
        })[0].src=url;
        $img.parent('div').addClass('to_show');
    }

    this.showBanner = function($link, pos){
        if($link.find('.css_loader')[0])return;
        if(!userAllowedFeature['kill_the_ads']){
            if (isUpgradePage()) {
                showAlert(l('please_upgrade_your_account'));
                return;
            }
            $link.addLoader();
            uploadPageUpgrade($link);
            return;
        }

        var $bannerBl = $link.parent('.link').prev('.banner_footer_mobile_user_content'),
            isVisible = !$bannerBl.is('.to_hide');
        $link.addLoader();
        $.post(url_main+'tools_ajax.php?cmd=ads_visible',{pos:pos,status:isVisible*1}, function(res){
            var data=checkDataAjax(res);
            if(data!==false) {
                if(data=='upgrade'){
                    uploadPageUpgrade($link);
                    return;
                }
                if(isVisible) {
                    $bannerBl.oneTransEnd(function(){
                        $link.find('.fa').toggleClass('fa-eye-slash fa-eye');
                        $link.find('span').text(l('show_ads'));
                        $bannerBl.empty().addClass('to_hide').removeAttr('style');
                        $link.removeLoader();
                        showAdmobBanner(false);
                    },'height').css({height:$bannerBl.height()});
                    setTimeout(function(){
                        $bannerBl.css({height:0,paddingBottom:0});
                    },100)
                }else{
                    var $data=$(data);
                    $bannerBl.html($data.find('.banner_footer_mobile_user_content').html());
                    setTimeout(function(){
                        $bannerBl.oneTransEnd(function(){
                            $link.find('.fa').toggleClass('fa-eye-slash fa-eye');
                            $link.find('span').text(l('remove_ads'));
                            $link.removeLoader();
                            $bannerBl.removeClass('to_hide').removeAttr('style');
                            if(typeof adMobBannerConfig !== "undefined") {
                                isAdmobBannerVisible = true;
                                showAdmobBanner(adMobBannerConfig);
                            }
                        },'height').css({height:($bannerBl.find('.bl_ads').height()||50)+'px',paddingBottom:10});
                    },2000)
                }
            }else{
                serverError();
            }
       })
    }

    this.confirmDisallowPrivatePhoto = function(){
        $this.toggleMoreMenu(true);
        showConfirm(false, $this.disallowPrivatePhoto, false, false, false, false, true)
    }

    this.disallowPrivatePhoto = function(){
        if(!requestUserId||$this.guid==requestUserId)return;
        var $btn=$('.pp_btn_ok_bl:visible');
        $('.pp_btn_cancel_bl:visible').prop('disabled',true);
        $btn.addLoader();
        $.post(url_main+'my_friends.php?cmd=send_request_private_access',
            {type:'request_declined',
             user_to:requestUserId,
             mid:0},
            function(res){
                var data=checkDataAjax(res)
                if(data){
                    updateCounter('can_see_your_private_photos',data);
                    getPage($btn,curPageData.url);
                }else{
                    serverError();
                }
        })
    }

    this.prepareProfileName = function(){
        $('#profile_name > span').fadeTo(0,0).flowtype({
            maxWidth : 165,
            callback : function(){$(this).fadeTo(0,1)}
        })
    }

    this.goToProfileEditField = function(msg, nameField){
        showConfirmToPage(msg, urlPagesSite.profile_view+'?edit_field_name='+nameField, l('ok'), false)
    }

    this.editField = function(nameField){
        var $field = $('#basic_pen_'+nameField);
        if($field[0]){
            $field.click();
        }
    }

    this.setBrowseInvisibly = function($link, param) {
        $link.addLoader();
        if (param == 'upgrade') {
            uploadPageUpgrade($link);
        }else{
            $.post(url_server+'?cmd=set_do_not_show_me_visitors', {}, function(res){
                var data=checkDataAjax(res);
                if(data!==false){
                    $('.btn_browse_invisibly').closest('.bl').css('overflow','hidden').animate({height:0, opacity:0},300,function(){
                    })
                } else {
                    $link.removeLoader();
                    serverError();
                }
            })
        }
    }

    this.openPopupEditorVerification = function(){
        clCommon.showHeaderMenu(true,function(){
            var msg='<span>' + l('verify_your_account_now') + '</span>';
            msg +='<div id="pp_frm_verify">'+$('#pp_profile_verification').html()+'</div>';
            showConfirm(msg, function($btn){
                var url = $('select[name="profile_verification_system"]', '#pp_frm_verify').val();
                if(url) {
                    $btn.addChildrenLoader();
                    redirectUrl('social_login.php?redirect=' + url + '&page_from=' + location.href);
                } else {
                    closeAlert();
                }
                return false;
            }, false,l('verify'),false,false,true);
        });
    }

    $(function(){
        $('body').on('click', '.profile_verification_show', function(e){
            $this.openPopupEditorVerification();
            return false;
        })
    })

    this.deleteCV = function($btn){
        var user_id = 0
        if(this.uid)
            user_id = this.uid;
        
        showConfirm(l('are_you_sure'), function() {            
            $.ajax({
                url: url_ajax,
                data: { "cmd": "deleteCV", user_id },
                type: 'POST',    
                dataType: "json",    
                success:function(data){                   

                    if(data) {
                        alert('Deleted Successfully')
                        goToPage($btn)
                    }
                    else
                        alert('There is someting wrong, Please try again!');
                }
    
            });
        });
    }

    this.uploadCV = function($btn){
        var user_id = 0
        if(this.uid)
            user_id = this.uid;

        $("#UploadCV").trigger("click")
        

        $("#UploadCV").change(function() {
            var fileName = $(this).val();

            if (fileName) {
                $("#cv_upload").html(`<i class="fa fa-cog fa-spin"></i>`).prop("disabled", true).css("background-color", "gray")

                var pdf = new FormData();
                pdf.append('file', this.files[0]);
                pdf.append('e_user_id', user_id);
                pdf.append('cmd', 'uploadCV');

                $.ajax({
                    url: url_ajax,
                    type: 'POST',
                    data: pdf,
                    processData: false,
                    contentType: false,
         
                    success:function(data){
                        if(data == 1) {
                            alert('Successfully Uploaded')
                            goToPage($btn)
                        }
                        else
                            $("#cv_upload").html(`<i class="fa fa-file-pdf-o" aria-hidden="true"></i> Upload CV`).prop("disabled", false).css("background-color", "#40c7db")
                        return false
                    }
                });

            } else {
                $("#cv_upload").html(`<i class="fa fa-file-pdf-o" aria-hidden="true"></i> Upload CV`).prop("disabled", false).css("background-color", "#40c7db")
                return false
            }
        });
    }
    this.uploadNID = function($btn){
        var user_id = 0
        if(this.uid)
            user_id = this.uid;

        $("#UploadNID").trigger("click")
        

        $("#UploadNID").change(function() {
            var fileName = $(this).val();

            if (fileName) {
                $("#nid_upload").html(`<i class="fa fa-cog fa-spin"></i>`).prop("disabled", true).css("background-color", "gray")

                var pdf = new FormData();
                pdf.append('file', this.files[0]);
                pdf.append('e_user_id', user_id);
                pdf.append('cmd', 'upload_nid');

                $.ajax({
                    url: url_ajax,
                    type: 'POST',
                    data: pdf,
                    processData: false,
                    contentType: false,
         
                    success:function(data){
                        if(data == 1) {
                            alert('Successfully Uploaded.')
                            goToPage($btn)
                        }
                        else
                            $("#nid_upload").html(`<i class="fa fa-file-pdf-o" aria-hidden="true"></i> Upload NID`).prop("disabled", false).css("background-color", "#40c7db")
                        return false
                    }
                });

            } else {
                $("#nid_upload").html(`<i class="fa fa-file-pdf-o" aria-hidden="true"></i> Upload NID`).prop("disabled", false).css("background-color", "#40c7db")
                return false
            }
        });
    }

    // PROFILE FIELD CUSTOMIZATION
    const btnLoader = `
        <div class="css_loader btn_action_loader"><div class="spinner center spinnerw"><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div></div></div>`;

    this.get_state = function(info, label) {

        if(info.id == "country_id_"+label)
            $("#city_id_"+label).html(`<option value="">Select</option>`)
        else
            $("#city_id_"+label).html(`<option value="">Select</option>`)

        if(info.value) {
            $.ajax({
                url: '../profile_ajax.php',
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
                url: '../profile_ajax.php',
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

        $("#profile_field_save").prop('disabled',true).html(btnLoader)

        $.ajax({
            url: '../profile_ajax.php',
            type: 'POST',
            data: formData,
 
            success:function(data){
                var data = JSON.parse(data);
                if(data.msg == "success") {
                    if(data.current_address)
                        $("#current_address").html(`<i class="fa fa-map-marker"></i> ${data.current_address}`)

                    if(data.permanent_address)
                        $("#permanent_address").html(`<i class="fa fa-home"></i> ${data.permanent_address}`)

                }
                $("#profile_field_save").prop('disabled',false).text('Save')
                // done
                clProfile.loadTabs('#tabs-1')
            },
            error: function(xhr, status, error) {
                console.log("Error: " + error);
            }
 
        });
    }
    this.submit_fevorite_unfevorite_region = function(event, info) {
        event.preventDefault()
        var formData = $(info).serialize();

        $("#profile_field_save").prop('disabled',true).html(btnLoader)
        
        $.ajax({
            url: '../profile_ajax.php',
            type: 'POST',
            data: formData,
 
            success:function(data){
                var data = JSON.parse(data);
                if(data.msg == "success") {
                    if(data.favorite_address)
                        $("#favorite_address").html(`<i class="fa fa-thumbs-up"></i> ${data.favorite_address}`)
                    if(data.unfavorite_address)
                        $("#unfavorite_address").html(`<i class="fa fa-thumbs-down"></i> ${data.unfavorite_address}`)
                }
                $("#profile_field_save").prop('disabled',false).text('Save')
                // done
                clProfile.loadTabs('#tabs-1')
            },
            error: function(xhr, status, error) {
                console.log("Error: " + error);
            }
 
        });
    }
    // education
    this.submit_education = function(event, info) {
        event.preventDefault()
        var formData = $(info).serialize();

        $("#profile_field_save").prop('disabled',true).html(btnLoader)

        var currentDate = new Date();
        var currentYear = currentDate.getFullYear();

        var err = 0;
        $('[name="degree_title[]"]').each(function() {
            if($(this).val() == "") {
                err++;
                showAlert('Please type Exam/Degree Title!',true,'Information incomplete');
                return false
            }
        });
        $('[name="school_name[]"]').each(function() {
            if($(this).val() == "") {
                err++;
                showAlert('Please type Institute Name!',true,'Information incomplete');
                return false
            }
        });
        $('[name="passing_year[]"]').each(function() {
            if(!$.isNumeric($(this).val())) {
                err++;
                showAlert('Please input right passing year!',true,'Information incomplete');
                return false
            } else {
                if($(this).val() > currentYear || $(this).val() < 1980) {
                    err++;
                    showAlert('Please input right passing year!',true,'Information incomplete');
                    return false
                }
            }
        });

        if(err) {
            $("#profile_field_save").prop('disabled',false).text('Save')
            return false;
        }

        
        $.ajax({
            url: '../profile_ajax.php',
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

                
                $("#profile_field_save").prop('disabled',false).text('Save')
                // done
                clProfile.loadTabs('#tabs-1')
            },
            error: function(xhr, status, error) {
                console.log("Error: " + error);
            }
 
        });
    }    
    this.add_more_education_field = function(ind, data) {
        var newInd = Number(ind)+1;
        /*if(newInd > 5) {
            showAlert('Please input right information!',true,'Alert');
            return true
        }*/
        $("#more_education"+ind).append(`
            <div class="close_div">
                <button type="button" onclick="clProfile.close_multiple_div(${ind})"><i class="fa fa-times"></i></button>
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
    // profession
    this.add_more_profession_field = function(ind, data) {
        var newInd = Number(ind)+1;
        /*if(newInd > 10) {
            showAlert('Please input right information!',true,'Alert');
            return true
        }*/
        $("#more_profession"+ind).append(`
            <div class="close_div">
                <button type="button" onclick="clProfile.close_multiple_profession_div(${ind})"><i class="fa fa-times"></i></button>
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

        $("#profile_field_save").prop('disabled',true).html(btnLoader)

        var err = 0;
        $('[name="profession_type[]"]').each(function() {
            if($(this).val() == "") {
                err++;
                showAlert('Please type Profession Type!',true,'Information incomplete');
                return false
            }
        });
        $('[name="position[]"]').each(function() {
            if($(this).val() == "") {
                err++;
                showAlert('Please type Position/Title!',true,'Information incomplete');
                return false
            }
        });
        $('[name="company[]"]').each(function() {
            if($(this).val() == "") {
                err++;
                showAlert('Please type Company!',true,'Information incomplete');
                return false
            }
        });

        if(err) {
            $("#profile_field_save").prop('disabled',false).text('Save')
            return false;
        }

        
        $.ajax({
            url: '../profile_ajax.php',
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

                $("#profile_field_save").prop('disabled',false).text('Save')
                // done
                clProfile.loadTabs('#tabs-1')
            },
            error: function(xhr, status, error) {
                console.log("Error: " + error);
            }
 
        });
    }

    // relatives


    this.add_more_relatives_field = function(ind, data) {
        var newInd = Number(ind)+1;
        $("#more_relatives"+ind).append(`
            <div class="close_div">
                <button type="button" onclick="clProfile.close_multiple_relatives_div(${ind})"><i class="fa fa-times"></i></button>
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

        $("#profile_field_save").prop('disabled',true).html(btnLoader)

        var err = 0;
        $('[name="relative_name[]"]').each(function() {
            if($(this).val() == "") {
                err++;
                showAlert('Please type Relative name!',true,'Information incomplete');
                return false
            }
        });
        $('[name="relation[]"]').each(function() {
            if($(this).val() == "") {
                err++;
                showAlert('Please add relationship!',true,'Information incomplete');
                return false
            }
        });

        if(err) {
            $("#profile_field_save").prop('disabled',false).text('Save')
            return false;
        }

        
        $.ajax({
            url: '../profile_ajax.php',
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

                $("#profile_field_save").prop('disabled',false).text('Save')
                // done
                clProfile.loadTabs('#tabs-1')
            },
            error: function(xhr, status, error) {
                console.log("Error: " + error);
            }
 
        });
    }

    // Additional Information
    this.submit_additional_information = function(event, info) {
        event.preventDefault()
        var formData = $(info).serialize();

        $("#profile_field_save").prop('disabled',true).html(btnLoader)
        
        $.ajax({
            url: '../profile_ajax.php',
            type: 'POST',
            data: formData,
 
            success:function(data){
                var data = JSON.parse(data);

                if(data.success == 'success') {
                    var result = '<li>'+data.additional_info+'</li>';

                    $("#additional_section #ul_list").html(result)
                }                

                $("#profile_field_save").prop('disabled',false).text('Save')
                // done
                clProfile.loadTabs('#tabs-1')
            },
            error: function(xhr, status, error) {
                console.log("Error: " + error);
            }
 
        });
    }

    // POSTED BY
    this.submit_posted_by = function(event, info) {
        event.preventDefault()
        var formData = $(info).serialize();

        $("#profile_field_save").prop('disabled',true).html(btnLoader)
        
        $.ajax({
            url: '../profile_ajax.php',
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

                $("#profile_field_save").prop('disabled',false).text('Save')
                // done
                clProfile.loadTabs('#tabs-1')
            },
            error: function(xhr, status, error) {
                console.log("Error: " + error);
            }
 
        });
    }

    return this;
}