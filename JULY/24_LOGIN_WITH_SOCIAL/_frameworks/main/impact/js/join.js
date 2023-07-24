var allCities={}, responseRecaptcha='', isRecaptcha=false, usersAge=0,
    passwordLengthMin=0, passwordLengthMax=0, nameLengthMin=0, nameLengthMax=0;
var isUploadPhotoJoinAjax=false, isUploadPhotoJoin=false;
$(function(){
    /* STEP 1 */
	$('.geo', '#step-1').change(function() {
        var type=$(this).data('location');
        $.ajax({type: 'POST',
                url: url_ajax,
                data: { cmd:type,
                        select_id:this.value,
                        filter:'1',
                        list: 0},
                        beforeSend: function(){
                            $jq('#css_loader_location').removeClass('hidden');
                            $jq('.location').prop('disabled', true).trigger('refresh');
                        },
                        success: function(res){
                            var data=checkDataAjax(res);
                            if (data) {
                                var option='<option value="0">'+joinLangParts.choose_a_city+'</option>';
                                switch (type) {
                                    case 'geo_states':
                                        $jq('#state').html('<option value="0">'+joinLangParts.choose_a_state+'</option>' + data.list).trigger('refresh');
                                        $jq('#city').html(option).trigger('refresh');
                                        break
                                    case 'geo_cities':
                                        $jq('#city').html(option + data.list).trigger('refresh');
                                        break
                                }
                            }
                            $jq('#css_loader_location').addClass('hidden');
                            $jq('.location').prop('disabled', false).trigger('refresh');
                            setDisabledSubmitJoin();
                        }
                    });
        return false;
    })

    $('#city').change(function() {
        setDisabledSubmitJoin();
    })

    $('select.i_am').change(function() {
        setDisabledSubmitJoin();
    })

	/* Email */
    $jq('#email').on('change propertychange input',validateEmail);
	function validateEmail(f){
        var val=trim($jq('#email').val()),res=false,f=f||1;
        if(!checkEmail(val)){
            var msg=isFrmSubmit?joinLangParts.incorrect_email:'&nbsp;';
			showError('email',msg)
		} else {
			hideError('email',true);
        }

        return res;
    }
	/* Email */
	/* Birth */
	$jq('.birthday').styler({singleSelectzIndex: '12',
		selectAutoWidth : false,
        selectAnimation: true,
        selectAppearsNativeToIOS: false,
        onSelectOpened: function(){},
        onFormStyled: function(){
            $jq('.inp_birth').addClass('to_show');
        }
    })

	$jq('.birthday').change(function() {
        if(this.id!='day'){
            updateDay('month','frm_date','year','month','day');
            $jq('#day').trigger('refresh');
        }
        validateBirthday();
    })

	$jq('#month').change();
	/* Birth */

	$jq('#agree').on('change',function(){
        if($(this).prop('checked')){
            hideError('agree');
        }else{
            showError('agree')
        }
    })

    function disabledControl(state, context){
        $jq('select', context).prop('disabled', state).trigger('refresh');
        $jq('input, textarea', context).prop('disabled', state);
    }

    var dataFrm={},
        isFrmSubmit=false;
	$jq('#frm_register_submit').mouseenter(function(){
        //$jq('#city').blur();
    }).click(function(){
        isFrmSubmit=true;
        if($jq('#frm_register_submit').is('.disabled')){
           $jq('#agree').change();
           return false;
        }
        if(setDisabledSubmitJoin(false,true,true)){
            return false;
        }
        $jq('#frm_register_submit').html(getLoader('css_loader_btn', false, true))
                                   .prop('disabled',true);
        $jq('input:not([type="search"]), select', '#step-1').each(function(){
            dataFrm[this.name]=$.trim(this.value);
		})
        disabledControl(true, '#step-1');
        $.post(urlMain+'join.php?cmd=register&ajax=1',dataFrm,
                function(data){
                    console.log(data);
                    //return false;
                        if (!showErrorResponseForm(data,['name','password','captcha','recaptcha'])) {
                            if(!isFrmSubmitStep2){
                                $jq('#password').val('');
                                //$jq('#name').val('');
                            }
                            hideCheck('name');
                            hideCheck('password');
                            $jq('#step-1').fadeOut(400,function(){
                                $jq('#frm_register_submit_2').prop('disabled',true);
                                $jq('#step-2').show(1).addClass('to_show');
                                $jq('#frm_register_submit').html(joinLangParts.next);
                                disabledControl(false, '#step-1');
                            })
                        }else{
                            disabledControl(false, '#step-1');
                            $jq('#frm_register_submit').html(joinLangParts.next);
                        }
                        $jq('#frm_register_submit').prop('disabled',true);
        })
    });
    /* STEP 1 */
    /* STEP 2 */
    var isFrmSubmitStep2=false;
    $jq('#frm_register_submit_2').click(function(){
        isFrmSubmitStep2=true;
        //if(setDisabledSubmitJoin('#step-2',true)){
            //return false;
        //}
        $jq('#frm_register_submit_2').html(getLoader('css_loader_btn', false, true))
                                     .prop('disabled',true);
        $jq('input', '#step-2').each(function(){
            dataFrm[this.name]=$.trim(this.value);
		})
        disabledControl(true, '#step-2');
        $.post(urlMain+'join.php?cmd=register&ajax=1',dataFrm,
        
        function(data){
            var not=['mail','birthday','captcha','recaptcha'];
            $('span','<div>'+data+'</div>').each(function(){
                if(in_array($(this).attr('class'),not)){
                    $jq('#step-2').fadeOut(400,function(){
                        $jq('#step-1').fadeIn(400);
                    })
                    showErrorResponseForm(data,['name','password','captcha','recaptcha']);
                    return false;
                }
            })
            if(showErrorResponseForm(data,not,'#step-2')){
                $jq('#frm_register_submit_2').html(joinLangParts.done);
                disabledControl(false, '#step-2');
            }
            $jq('#frm_register_submit_2').prop('disabled',true);

        })
    })

    /* Phone Number country code */
    if (currentPage == 'join.php' || currentPage == "join_facebook.php") {
        var phone_number = window.intlTelInput(document.querySelector("#join_phone_number"), {
            separateDialCode: true,
            preferredCountries:["gb","us","sa","ae"],
            hiddenInput: "full",
            utilsScript: "//cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.3/js/utils.js"
        })

        document.getElementById('join_phone_number').onchange = function(){
            var full_number = phone_number.getNumber(intlTelInputUtils.numberFormat.E164);
            $("#full_phone_number").val(full_number);
        }
    }

    $jq('#password').on('change propertychange input',validatePassword);
    function validatePassword(){
        var val=$jq('#password').val(),l=val.length,msg='&nbsp;';
        if(~val.indexOf("'")<0){
            if(isFrmSubmitStep2)msg=joinLangParts.incorrect_password_contain;
            showError('password',msg,'#step-2');
        } else if(l<passwordLengthMin||l>passwordLengthMax) {
            if(isFrmSubmitStep2)joinLangParts.incorrect_password_length;
            showError('password',msg,'#step-2');
        } else {
            hideError('password',true,'#step-2');
        }
    }

    $jq('#name').on('change propertychange input',validateUserName);
    function validateUserName(){
        var val=$jq('#name').val(),l=$.trim(val).length,msg='&nbsp;';
        if (/[#&'"\/\\<]/.test(val)){
            if(isFrmSubmitStep2)msg=joinLangParts.incorrect_name;
            showError('name',msg,'#step-2');
        }else if((l<nameLengthMin||l>nameLengthMax)){
            if(isFrmSubmitStep2)msg=joinLangParts.incorrect_name_length;
            showError('name',msg,'#step-2');
        } else {
            hideError('name',true,'#step-2');
        }
    }
    /* STEP 2 */

    /* STEP 4 */
    /* Captcha */
    var isFrmSubmitStep3=false;
	$jq('#captcha').on('change propertychange input', function(){
		var val=trim($jq('#captcha').val());
		if(val){
			hideError('captcha',true,'#frm_card_join');
		}else{
            var msg=isFrmSubmitStep3?joinLangParts.incorrect_captcha:'&nbsp;';
			showError('captcha',msg,'#frm_card_join');
		}
    }).keydown(function(e){
        if (e.keyCode==13&&!$jq('#join_done').prop('disabled')) {
            $jq('#join_done').click();
            return false;
        }
    })
	/* Captcha */

    $jq('.fl_basic').on('change propertychange input', function(){
        hideError(this.id,false,'#frm_card_join');
		//setDisabledSubmitJoin('#frm_card_join');
    })

    $jq('#join_done').click(checkCaptcha);

    $('input.file','#photo_upload').click(function(){
        $jq('#photo_upload_error').removeClass('to_show');
        $jq('#photo_upload').data('id','');
        $jq('#photo_upload_reset').click();
    });

    function showUploadPhotoError(error){
        isUploadPhotoJoinAjax=false;
        $jq('#photo_loader').addClass('to_hide');
        $jq('.upload > .photo_add, .upload_pic').stop().fadeIn(200);
        $jq('.file').show();
        $jq('#photo_upload_error').html(error).attr('title',error).addClass('to_show');
    }

    var fileNameUpload='';
    $jq('#photo_upload').submit(function(e){
        if(isUploadPhotoJoinAjax)return false;
        isUploadPhotoJoinAjax=true;
        var fileName = $jq('#photo_upload').data('id'),
            file = $jq('#'+fileName),
            url = this.action + '?cmd=photo_upload&ajax=1&file=' + fileName,
            formData=new FormData(),
            error='',dur=200;
        $.each(file[0].files, function(i, file){
            var acceptTypes='image/jpeg,image/png,image/gif';
            if (acceptTypes.indexOf(file.type) === -1) {
                error=joinLangParts.acceptFileTypes;
                return false;
            }else if (file.size > fileSizeLimit) {
                error=joinLangParts.errorFileSizeLimit;
                return false;
            }
            formData.append(fileName, file);
        });
        if(error){
            showUploadPhotoError(error);
            return false;
        }

        var isRequest=false;
        $jq('.upload > .photo_add, .upload_pic').fadeOut(dur,function(){
            !isRequest&&$jq('#photo_loader').removeClass('to_hide')
        });

        var xhr = new XMLHttpRequest();
        xhr.open("POST", url);
        xhr.onerror = function(){
            isRequest=true;
            showUploadPhotoError(joinLangParts.errorFileUploadFailed);
        };
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4) {
                if(xhr.status == 200) {
                    isRequest=true;
                    var data = xhr.responseText;
                    data=checkDataAjax(data);
                    if(data){
                        if(data.error){
                            showUploadPhotoError(data.error);
                        } else {
                            $jq('#photo_loader').addClass('to_hide');
                            fileNameUpload=data;
                            $jq('#photo_img')[0].src =urlFiles+'temp/'+data+'m.jpg';
                            $jq('#photo_img').fadeIn(500);
                            isUploadPhotoJoin=true;
                            //setDisabledSubmitJoin('#frm_card_join');
                        }
                    }else{
                        showUploadPhotoError(joinLangParts.errorFileUploadFailed);
                    }
                }
            }
        };
        xhr.send(formData);
        return false;
    });

    var isFrmSubmitStep3Ajax=false;
    function checkCaptcha(){
        var isError=false;
        if (isJoinWithPhotoOnly && !isUploadPhotoJoin) {
            $jq('#photo_upload_error').html(l('upload_profile_photo')).attr('title',l('upload_profile_photo')).addClass('to_show');
            isError=true;
        }
        $jq('input, textarea', '#frm_card_join').each(function(){
			var val=$.trim(this.value), is=(val==0||val=='');
            if(is)showError(this.id);
            isError|=is;
		})
        if(isRecaptcha){
            if(grecaptcha.getResponse(recaptchaWd)==''){
                isError = true;
                showError('captcha','','#frm_card_join');
            }
        }
        if(isError){
           return false;
        }

        if(isFrmSubmitStep3Ajax) return false;
        isFrmSubmitStep3Ajax=true;
        isFrmSubmitStep3=true;
        var val=isRecaptcha?grecaptcha.getResponse(recaptchaWd):trim($jq('#captcha').val());
        $jq('#join_done').html(getLoader('css_loader_btn', false, true)).prop('disabled',true);
        disabledControl(true, '#frm_card_join');
        var data={captcha:val,photo:fileNameUpload};
        $('.fl_basic').each(function(){
            data[this.name]=this.value;
        })
        data.join_answers=dataAnswerJoin;
        data.users_like=joinLikeUser;
        $.post(urlMain+'join2.php?cmd=check_captcha&ajax=1',data,
        function(data){
            isFrmSubmitStep3Ajax=false;
            data=getDataAjax(data,'data');
            $jq('#join_done').html(joinLangParts.done).prop('disabled',false);
            if(data!==false){
                var $data=$(data),
                    $exEmail=$data.filter('.exists_email'),
                    $waitApproval=$data.filter('.wait_approval'),
                    $redirect=$data.filter('.redirect');
                if ($exEmail[0]) {
                    alertCustomRedirect($exEmail.html(),joinLangParts.existsEmail)
                }else if ($waitApproval[0]) {
                    alertCustomRedirect($waitApproval.html(),joinLangParts.noConfirmationAccount)
                }else if ($redirect[0]) {
                    redirectUrl($redirect.text());
                }else if ($data.filter('.error_captcha')[0]) {
                    disabledControl(false, '#frm_card_join');
                    if(isRecaptcha){
                        grecaptcha.reset(recaptchaWd);
                    }else{
                        $jq('#img_join_captcha').click();
                        $jq('#captcha').val('').focus();
                    }
                    showError('captcha','','#frm_card_join');
                }
            }
        })
    }

    $jq('.btn_question').click(function(){
        questionAnswer($(this).data('action'));
    })

    $('.card_question.first').css('z-index',4);
    function questionAnswer(action){
        if(isAnswerSend)return;
        isAnswerSend=true;
        var $el=$('.card_question.first:not(.answer)');
        var c=$('.card_question:not(.answer)').length-1;
        if($el[0]){
            dataAnswerJoin[$el.data('field')]=action?'yes':'no';
        }
        if(!c){
            $jq('#step_loader').fadeIn(400);
            $jq('#full_step_1').fadeOut(400,function(){
                getListUsersLike();
            })
            return;
            stepMade(0);
            $jq('#full_step_1').fadeOut(400,function(){
                $jq('#full_step_2').fadeIn(400,function(){
                    getListUsersLike();
                })
            })
            return;
        }
        var cl=action?'to_move_right':'to_move_left',
            cla=action?'yes':'no';
        $el.oneTransEnd(function(){
            $el.removeAttr('style');
            $el.oneTransEnd(function(){
                var $prev=$el.addClass('answer').prev('.card_question').addClass('first');
                $jq('#card_question_'+cla).oneTransEnd(function(){
                    isAnswerSend=false;
                    $prev.css('z-index',4);
                }).toggleClass('show hide');
            }, 'transform').toggleClass(cl+' to_hide');
        },'transform').addClass(cl,0);
        $jq('#card_question_'+cla).toggleClass('hide show');
        /*$jq('#card_question_'+cla).oneTransEnd(function(){
            $el.oneTransEnd(function(){
                $el.addClass('answer').prev('.card_question').addClass('first');
                $jq('#card_question_'+cla).oneTransEnd(function(){
                    isAnswerSend=false;
                }).toggleClass('show hide');
            }).toggleClass(cl+' to_hide');
        }).toggleClass('hide show');*/
    }
    /* STEP 4 */

    function showErrorResponseForm(data, not, context){
        context=context||'#step-1';
        not=not||[];
        var isError=false,
            blocks={mail:'email',
                    location:'city',
                    birthday:'birth'};
        $('span','<div>'+data+'</div>').each(function(){
            var $el=$(this),cl=$el.attr('class');
            if(!in_array(cl,not)){
                var s=blocks[cl]?blocks[cl]:cl,
                    msg=$el.text();
                if(s=='city')msg=joinLangParts.incorrect_city;
                if(s=='redirect'){
                    redirectUrl(msg);
                    return false;
                }
                showError(s,msg,context,true);
                //(name,msg,context,isSubmitDisabled)
                isError=true;
            }
        })
        return isError;
    }

    if (currentPage == 'join.php' || currentPage == 'join_facebook.php') {
        $jq('.location').styler({singleSelectzIndex: '11',
            selectAutoWidth : false,
            selectAnimation: true,
            selectAppearsNativeToIOS: false,
            onSelectOpened:function(){},
            onFormStyled: function(){
                $('.bl_location, .bl_inp_pos').addClass('to_show');
            }
        })
        $('.i_am').styler({
            singleSelectzIndex: '11',
            selectAutoWidth: false,
            selectAnimation: true,
            selectAppearsNativeToIOS: false,
            onFormStyled: function(){
                $jq('bl_i_am').addClass('to_show');
            }
        })
        /* Info */
        $jq('#pp_terms').modalPopup({shClass:'pp_shadow'});
        $('#terms_close').click(function(){
            $jq('#pp_terms').close(durOpenPpInfo);
            $jq('#agree').prop('checked',true).change();
        })

        $jq('#pp_priv').modalPopup({shClass:'pp_shadow'});
        $('#priv_close').click(function(){
            $jq('#pp_priv').close(durOpenPpInfo);
            $jq('#agree').prop('checked',true).change();
        })

        $jq('.scroll-info-terms, .scroll-info-priv').jScrollPane({
            verticalDragMinHeight: 50,
            verticalDragMaxHeight: 100,
            mouseWheelSpeed: 200,
            //autoReinitialise: true
        })
        /* Info */
    } else if(currentPage == 'join2.php'){
        $('.styler_looking').styler()
    }
    //setStep();
})

function showError(name,msg,context,isSubmitDisabled){
    context=context||'#step-1';
    if(msg){
		$jq('#'+name+'_error').html(msg);
	}
	$jq('#'+name+'_error').removeClass('to_hide');
	hideCheck(name);
	setDisabledSubmitJoin(context,false,isSubmitDisabled);
}

function hideError(name,isShowCheck,context){
    context=context||'#step-1';
	$jq('#'+name+'_error').addClass('to_hide');
	if(isShowCheck){
		showCheck(name);
	}
	setDisabledSubmitJoin(context);
}

function showCheck(name){
	$jq('#'+name+'_check').removeClass('to_hide');
}

function hideCheck(name){
	$jq('#'+name+'_check').addClass('to_hide');
}

var durOpenPpInfo=350;
function infoOpen(name){
	$jq('#pp_'+name).open(durOpenPpInfo);
    setTimeout(function(){
        $jq('.scroll-info-'+name).data('jsp').reinitialise();
    },1)
}

function refreshCaptcha(captcha){
    var captcha=captcha||'#img_join_captcha';
    $jq(captcha).attr('src', url_main+'_server/securimage/securimage_show_custom.php?sid=' + Math.random());
    $jq('#captcha').val('').change();
    return false;
}

function changeUploadPhoto($el){
    $jq('.file').hide();
    $jq('#photo_upload').data('id', $el[0].id);
    $jq('#photo_upload_submit').click();
}

function birthDateToAge() {
	var birth=new Date($('#year').val(), $('#month').val()-1, $('#day').val()),
		now = new Date(),
		age = now.getFullYear() - birth.getFullYear();
		age = now.setFullYear(1972) < birth.setFullYear(1972) ? age - 1 : age;
	return age >= usersAge;
}

function validateBirthday(){
    if(birthDateToAge()){
		hideError('birth');
    }else{
    	showError('birth',joinLangParts.incorrect_date);
    }
}

function setDisabledSubmitJoin(context, setError, notSubmitDisabled){
        notSubmitDisabled=notSubmitDisabled||0;
        context=context||'#step-1';
        setError=setError||0;
		var is=0,isError;
		$jq('input:not([type="search"]), select, textarea', context).not('.not_frm').each(function(){
			var val=$.trim(this.value);
			if(this.id=='email'){
				isError=!checkEmail(val);
			}else{
				isError=(val==0||val=='');
			}
            /*if(isError&&setError){
                var k=this.id;
                if(k=='city'||k=='state'||k=='country')k='city';
                showError(k,joinLangParts['incorrect_'+k]);
            }*/
            is|=isError;
		})
        var $sb=$jq('#frm_register_submit_2');
        if(context=='#step-1'){
            isError=!birthDateToAge();
            if(isError&&setError){
                showError('birth',joinLangParts['incorrect_date']);
            }
            is|=isError;
            $sb=$jq('#frm_register_submit');
            //is|=!$jq('#agree').prop('checked');
            $sb[$jq('#agree').prop('checked')?'removeClass':'addClass']('disabled');
        }else if(context=='#frm_card_join'){
            is=0;
            /*is|=isError;
            if(isJoinWithPhotoOnly){
                is|=!isUploadPhotoJoin;
            }
            if(isRecaptcha){is|=(grecaptcha.getResponse(recaptchaWd)=='')}
            $sb=$jq('.btn_join_submit');
            notSubmitDisabled=true;*/
        }
		!notSubmitDisabled&&$sb.prop('disabled',is);
        return is;
}

function verifyCallback(response) {
    //setDisabledSubmitJoin('#frm_card_join');
    $jq('#captcha_error').addClass('to_hide');
}

/* Step 3*/
function setSlogan(n){
    n=n||$jq('#join_step').find('li:visible').length;
    if(n==0)n=3;
    $jq('#join_slogan').text(joinLangParts['slogan_'+n]);
}

function setStepJoin(){
    var i=1;
    $jq('#join_step').find('li').each(function(){
        $(this).text(i++);
    })
}

function stepSelected(n){
    setSlogan(n);
    $jq('#join_step').find('li').eq(n-1).addClass('selected');
}

function stepChecked(num){
    $jq('#join_step').find('li').eq(num).text('').toggleClass('selected checked');
}

function stepMade(num,n){
    stepChecked(num);
    stepSelected(n);
}

var joinLikeUser={},numJoinLikeUser=0,maxJoinLikeUser=0;
function joinLike(uid,$btn){
    if(joinLikeUser[uid]==undefined){
        joinLikeUser[uid]=1;
        numJoinLikeUser++;
    }else{
        delete joinLikeUser[uid];
        numJoinLikeUser--;
    }
    if(joinLikeUser[uid]==undefined){
        $btn.attr('title', joinLangParts['like']).removeClass('active');
    }else{
        $btn.attr('title', joinLangParts['unlike']).addClass('active');
    }
    if(numJoinLikeUser==numberPhotoLikes||numJoinLikeUser==maxJoinLikeUser){
        stepMade(1,3);
        $jq('#join_step').find('li').eq(2).delay(100).fadeIn(600);
        $jq('#full_step_2').fadeOut(400,showStep3)
    }
}

function showStep3(){
    $jq('#full_step_3').fadeIn(400,function(){
        var $baseField=$jq('#full_step_3').find('.placeholder_always');
        if($baseField[0])$baseField.eq(0).focus();
    })
}

var isAnswerSend=false;
var dataAnswerJoin={};
function getListUsersLike(){
    $.post('search_results.php?join_search_page=1&ajax=1',
           {with_photo:1,
            join_answers:dataAnswerJoin},
            function(data){
                        stepMade(0,2);
                        $jq('#join_step').find('li').eq(1).delay(100).fadeIn(600);
                        var $data=$(data),$items=$data.find('.item');
                        $jq('#step_loader').fadeOut(200);
                        if($items[0]){
                            maxJoinLikeUser=$items.length;
                            if(maxJoinLikeUser!=maxUsersPagesJoin){
                                var num=4,n=Math.floor(maxJoinLikeUser/num),d=0,html='';
                                if(n){
                                    if(maxJoinLikeUser>num){
                                        d=num-(maxJoinLikeUser-n*num);
                                    }
                                }else{
                                    d=num-maxJoinLikeUser;
                                }
                                if (d) {
                                    for(var i=0;i<d;i++) {
                                        html +='<div class="item"></div>';
                                    }
                                }
                                if(html)$items=$data.append(html).find('.item');
                            }
                            /*maxJoinLikeUser=$items.length;
                            if(maxJoinLikeUser!=maxUsersPagesJoin){
                                var d=maxUsersPagesJoin-maxJoinLikeUser;
                                if(maxJoinLikeUser<4){
                                    d=4-maxJoinLikeUser;
                                }
                                var html='';
                                for(var i=0;i<d;i++) {
                                    html +='<div class="item"></div>';
                                }
                                $items=$data.append(html).find('.item');
                            }*/
                            $('#list_photos_card').prepend($items).closest('.bl_card_question').fadeIn(400);
                        }else{
                            setSlogan(3);
                            showStep3();
                        }

    })
}
/* Step 3*/