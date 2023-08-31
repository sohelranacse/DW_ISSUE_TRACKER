var offsetPrevPageSearch=0,
    offsetNextPageSearch=0,
    totalPageSearch=0,
    onPageSearch=0,
    dScroll=0,
    isChangeFilterSearch=false,
    waitLoadPageSearch=1000, waitingLoadPageSearch;
$(function(){
    $('.search_show').change(function(){
        if(typeof clearGlobalSearchByUsernameF=='function'){
            clearGlobalSearchByUsernameF();
        }
		$(moduleFilterClass).change();

		return false;
	});

    $('#module_search_with_photo').change(function(){
        if(typeof clearGlobalSearchByUsernameF=='function'){
            clearGlobalSearchByUsernameF();
        }
        $(moduleFilterClass).change();
    })

    var $partFilterExtended=$('#filter_extended'),
        $filterSearch=$('#filter_part_all'),
        $partFilterMain=$('.filter_main',$filterSearch),
        $partFilterTitle=$('#filter_part_title'),
        durFilterExt=500,
        hFlDefault=50,
        isActionFilter=false;
    $('#link_filter_extended').click(function(){
        if(isActionFilter)return false;
        isActionFilter=true;
        if(!isSuperPowers){
            window.location.href=urlPageUpgrade;
            return false;
        }
        var cl='to_down to_up',
            state=$jq('#link_filter_extended span').is('.to_up')*1,
            fn='easeOutQuad';
        if(!state){
            cl='to_up to_down';
            fn='easeInQuad';
        }
        //$jq('#link_filter_extended_part').fadeTo(250,state?0:1);
        $jq('#link_filter_extended span').toggleClass(cl,0);
        //$partFilterExtended[state?'addClass':'removeClass']('to_hide');
        $partFilterExtended.slideToggle({complete:function(){
                isActionFilter=false;
                $win.scroll();
            },
            specialEasing:{height:fn},
            duration:durFilterExt
        })
        return false;
    })

    $('#link_filter_extended_part, #filter_part_title').click(function(){
        if(isActionFilter)return false;
        isActionFilter=true;
        var state=$filterSearch.is('.to_up')*1,
            hF=hFlDefault,fn='easeOutQuad',d=280,durFilter=500;
        //$filterSearch.css('overflow','visible');
        if(state){
            hF=$filterSearch.css('height','auto').height();
            $filterSearch.css('height',hFlDefault);
            $partFilterTitle.addClass('to_hide');
            $partFilterMain.oneTransEnd(function(){
                //$filterSearch.css('overflow','visible');
            }).delay(250).removeClass('to_hide',0);
        }else{
            d=1;
            //fn='easeOutQuad';
            if($partFilterExtended.is(':visible'))durFilter=700;
            $partFilterMain.oneTransEnd(function(){
                //$filterSearch.css('overflow','visible');
            }).delay(durFilter-350).toggleClass('to_hide',0);
            $partFilterTitle.find('strong').html(getTitleFilter());
            $partFilterTitle.delay(durFilter-100).removeClass('to_hide',0);
        }

        //$partFilterMain.fadeTo(250,state?0:1);
        //$jq('#link_filter_extended_part').fadeTo(250,state?0:1);
        //$jq('#link_filter_extended span').toggleClass(cl,0);
        $filterSearch.css('overflow','hidden').delay(d).animate({height:hF},
            {complete:function(){
                $filterSearch.css('overflow','visible');
                isActionFilter=false;
                //$.post(url_main+'ajax.php?cmd=set_state_filter',{state:state});
                $win.scroll();
                if(state){
                    $filterSearch.css('height','auto').removeClass('to_up');
                }else{
                    $filterSearch.addClass('to_up');
                    $partFilterExtended.hide();
                    $jq('#link_filter_extended span').removeClass('to_up').addClass('to_down');
                }
            },
            specialEasing:{height:fn},
            duration:durFilter
        })
        return false;
    })

    var $fieldIAmHere=$('.bl_filter .bl_here .rb').each(function() {
        $(this).click(function() {
            $fieldIAmHere.removeClass('selected').find('a').removeClass('checked');
            $(this).addClass('selected').find('a').addClass('checked');
        })
    })

	$jq('#search_cont').addClass('to_show');

	$('.profile_city_choose').one('click', function(){
        var pp_choose_city=$('#pp_choose_city')
			.modalPopup({css:{zIndex:1001},shCss:{}, wrCss:{}, wrClass:'wrapper_custom', shClass:'pp_shadow_white'})
			.open();
		var params;
		params = {cmd : 'pp_profile_city_choose'};
		if(typeof chooseLocationInit != 'undefined') {
			params['location'] = chooseLocationInit;
		}
		if(typeof isSearchList != 'undefined') {
			params['is_search_list'] = isSearchList;
		}
        $.post(url_main+'ajax.php',params,function(res){
            var data=checkDataAjax(res);
            if(data!==false){pp_choose_city.html(data)
            }else{/*Error server*/}
        });
        return false;
    })

    $doc.on('click', function(e){
        var $targ=$(e.target);
        if(!$targ.is('.bl_filter')&&!$targ.closest('.bl_filter')[0]
            &&!$targ.is('#pp_choose_city')&&!$targ.closest('#pp_choose_city')[0]
            &&!$targ.is('.search_results')&&!$targ.closest('.search_results')[0]
            &&!$targ.is('.selected.sel')){
            // closeFilter()
        }
    });

    $jq('.main').on('scroll',function(){
        var sF=$filterSearch[0].offsetTop+42+$filterSearch.height();
        if($jq('.main').scrollTop()>sF){
            // comment by sohel
            // closeFilter()
        }
    })

})

function closeFilter(){
    var res=false,$fl=$('#filter_part_all');
    if($fl[0]&&!$fl.is('.to_up')){
        $jq('#link_filter_extended_part').click();
    }
    return res=true;
}

function getTitleFilter(){
    var ageTitle='',hereToTitle='',lookingTitle='',title,
        age_from = $('select[name=p_age_from]', '.bl_filter'),
        age_to=$('select[name=p_age_to]', '.bl_filter'),
        here_to_input=$('input[name=i_am_here_to]', '.bl_filter'),
        here_to=$('input[name=i_am_here_to]:checked', '.bl_filter'),
        looking=$('input[id^=p_orientation_]:checked', '.bl_filter'),
        orientations=$('input[id^=p_orientation_]', '.bl_filter'),
        filterRadius=$('#radius'),
        filterCityTitle=$('#filter_module_location_title'),
        isPeopleNearby=$('#filter_people_nearby').val()*1;

        if(here_to[0]){
            hereToTitle=lGeneral['i_am_here_to'][here_to.val()];
        }
        title=lGeneralTitle.replace(/{here_to}/,hereToTitle);
        var lLooking = lSearchLooking;
        if (!ajax_login_status&&here_to_input[0]&&!here_to[0]) {
            lLooking = lGeneralTitleNotHereTo;
        }
        if(looking[0]){
            var l=looking.length,i=1;
            looking.each(function(){
                lookingTitle+=lGeneral['p_orientation'][$(this).val()];
                if(i<(l-1)&&(i+1)!=l){
                    lookingTitle+=lProfileOrientationsDelimiter;
                }else if(l>1&&(i+1)==l){
                    lookingTitle+=lProfileOrientationsLastDelimiter;
                }
                i++;
            })
            lookingTitle+=lFieldsDelimiter;
            lookingTitle=lLooking.replace(/{looking}/,lookingTitle);
        } else if (orientations[0]) {
            lookingTitle=lLooking.replace(/{looking}/,lProfileOrientationsSomebody+lFieldsDelimiter);
        }
        title=title.replace(/{looking}/,lookingTitle);
        if(age_from[0]&&age_to[0]){
            ageTitle=age_from.val()+lAgeDelimiter+age_to.val()+lFieldsDelimiter;
            ageTitle=lSearchAge.replace(/{age}/,ageTitle);
        }
        title=title.replace(/{age}/,ageTitle);

        var location,currentRadius=filterRadius.val();
        if(isPeopleNearby){
            if (maxRadius==currentRadius && isMaxFilterDistanceCountry) {
                title=title.replace(/{distance}/,'');
                location=lSearchFromCountry.replace(/{location}/,geoPointData.country);
            } else {
                currentRadius=(currentRadius>0)?lSearchRadius.replace(/{radius}/,currentRadius):'';
                title=title.replace(/{distance}/,currentRadius);
                location=lSearchFromCity.replace(/{location}/,geoPointData.city);
            }
        } else {
            var city=$('#filter_city').val()*1;
            if(maxRadius==currentRadius && isMaxFilterDistanceCountry && city!=0){
                title=title.replace(/{distance}/,'');
                location=lSearchFromCountry.replace(/{location}/,counterTitle);
            }else{
                if(city==0){
                    title=title.replace(/{distance}/,'');
                    location=lSearchFromAll.replace(/{location}/,filterCityTitle.text());
                } else {
                    currentRadius=(currentRadius>0)?lSearchRadius.replace(/{radius}/,currentRadius):'';
                    title=title.replace(/{distance}/,currentRadius);
                    location=lSearchFromCity.replace(/{location}/,filterCityTitle.text());
                }
            }
        }
        title=title.replace(/{location}/,location);
        return title;
}