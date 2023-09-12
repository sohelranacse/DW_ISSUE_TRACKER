<?php
/* (C) Websplosion LLC, 2001-2021

IMPORTANT: This is a commercial software product
and any kind of using it must agree to the Websplosion's license agreement.
It can be found at http://www.chameleonsocial.com/license.doc

This notice may not be removed from the source code. */

class CHeader extends CHtmlBlock {

    var $message_template = "";
	private $name = '';
	private $set = '';

    static $url = array(
        'chat_from_user' => 'search_results.php?uid={user_id}&display=profile',
        'chat_from_user_mobile' => 'profile_view.php?user_id={user_id}',
    );

    function __construct($name, $html_path, $isTextTemplate = false, $textTemplate = false, $noTemplate = false)
	{
        global $g;

        if ($html_path != null && $g['main']['site_part'] == 'main' && is_string($html_path)) {
            if (strpos($html_path, '_header.html') !== false) {
                $headerTemplate = Common::getOptionTemplate('header_template');
                if ($headerTemplate) {
                    $html_path = $headerTemplate;
                }
            } elseif (strpos($html_path, '_footer.html') !== false) {
                $footerTemplate = Common::getOptionTemplate('footer_template');
                if ($footerTemplate) {
                    $html_path = $footerTemplate;
                }
            }
        }
        parent::__construct($name, $html_path, $isTextTemplate, $textTemplate, $noTemplate);
    }

	function init()	{
		$this->set = Common::getOption('set', 'template_options');
        $this->name = Common::getOption('name', 'template_options');
		parent::init();
	}

    static function parseAnimatedMainPage(&$html)
    {
        $isParseBlockAnimatedJs = true;
        $typeAnimated = Common::getOption('main_page_urban_animated');
        $typeAnimatedOr = $typeAnimated;
        $contentAnimatedClass = "content_animated";
        $contentWAnimatedClass = "cont_w_animated";

        if (in_array($typeAnimated, array('rectangles_d3', 'interests_chart_d3'))) {
            $html->parse('main_page_animated_d3', false);
            if ($typeAnimated == 'interests_chart_d3') {
                $interestsChart = DB::select('interests', '', 'counter DESC', 12);
                $interestTitle = array();
                $interestNumber = array();
                foreach ($interestsChart as $interest) {
                    $interestTitle[] = $interest['interest'];
                    $interestNumber[$interest['interest']] = $interest['counter'];
                }
                $html->setvar('interests_chart', json_encode($interestTitle));
                $html->setvar('interests_chart_number', json_encode($interestNumber));
            }
        } elseif (in_array($typeAnimated, array('zoomwall', 'diamond_small', 'diamond_big', 'diamond_narrow_small', 'diamond_narrow_big'))) {
            $numberPhotoRand = 60;
            $size = 'b';
            if ($typeAnimated == 'diamond_small') {
                $numberPhotoRand = 34;
            } elseif ($typeAnimated == 'diamond_big') {
                $numberPhotoRand = 74;
            } elseif ($typeAnimated == 'diamond_narrow_small') {
                $numberPhotoRand = 24;
            } elseif ($typeAnimated == 'diamond_narrow_big') {
                $numberPhotoRand = 30;
            }
            $randPhotos = DB::select('photo', "`visible` = 'Y' AND `private` = 'N'", 'RAND()', $numberPhotoRand);
            $isParseBlockAnimatedJs = false;
            $id = 0;
            if (in_array($typeAnimated, array('diamond_small', 'diamond_big', 'diamond_narrow_small', 'diamond_narrow_big'))) {
                $typeAnimated = 'diamond';
            }
            $blockContentAnimated = "main_page_image_content_{$typeAnimated}";
            foreach ($randPhotos as $photo) {
                $html->setvar("{$typeAnimated}_photo_id", $id++);
                $html->setvar("{$typeAnimated}_photo", User::photoFileCheck($photo, $size, ''));
                if ($typeAnimated == 'zoomwall') {
                    $html->setvar('zoomwall_photo_src', User::photoFileCheck($photo, 'src', ''));
                }
                $html->parse("{$blockContentAnimated}_photo", true);
                $isParseBlockAnimatedJs = true;
            }
            $contentAnimatedClass = "content_animated_{$typeAnimated}";
            $contentWAnimatedClass = "cont_w_animated_custom";
            if ($isParseBlockAnimatedJs) {
                $html->parse($blockContentAnimated, false);
            }
        } elseif (in_array($typeAnimated, array('world_map', 'world_globe'))) {
            $contentAnimatedClass = "content_animated_{$typeAnimated}";
            $contentWAnimatedClass = "cont_w_animated_custom";
            $blockContentAnimated = "main_page_image_content_{$typeAnimated}";
            $html->parse($blockContentAnimated, false);
        } elseif (in_array($typeAnimated, array('clouds'))) {
            //$contentAnimatedClass = "content_animated_{$typeAnimated}";
            //$contentWAnimatedClass = "cont_w_animated_custom";
            $blockContentAnimated = "main_page_image_content_{$typeAnimated}";
            $html->parse($blockContentAnimated, false);
        } elseif (!in_array($typeAnimated, array('rays_of_light_three'))) {
            $html->parse('main_page_animated_three', false);
        }
        $html->setvar('content_animated_class', $contentAnimatedClass);
        $html->setvar('cont_w_animated_class', $contentWAnimatedClass);

        if ($isParseBlockAnimatedJs && $html->blockExists("main_page_animated_{$typeAnimated}")) {
            if (in_array($typeAnimatedOr, array('diamond_narrow_small', 'diamond_narrow_big'))) {
                $html->parse('main_page_animated_diamond_custom', false);
            }
            $html->parse("main_page_animated_{$typeAnimated}", false);
        }
        $html->setvar('main_page_animated_type', $typeAnimated);
        $html->parse('main_page_animated_js', false);
    }

    static function showMap(&$html, $cityInfo = null, $numUsers = 7, $parseBlock = false, $sizePhoto = '')
    {
        $blockMap = 'ip_map';
        if ($html->blockexists($blockMap)) {
            if ($cityInfo === null) {
                //$cityInfo = IP::geoInfoCity();
                $cityInfo = getDemoCapitalCountry();
            }

            $params='';
            if(Common::getOption('title_location_urban')=='state'){
                $html->setvar('ip_city_id', $cityInfo['state_id']);
                $html->setvar('ip_city_title', l(Common::getLocationTitle('state',$cityInfo['state_id'])));
                $html->setvar('type_location', 'state');
                $params='&city=0&set_filter=1';
            } elseif(Common::getOption('title_location_urban')=='country'){
                $html->setvar('ip_city_id', $cityInfo['country_id']);
                $html->setvar('ip_city_title', l(Common::getLocationTitle('country',$cityInfo['country_id'])));
                $html->setvar('type_location', 'country');
                $params='&state=0&city=0&set_filter=1';
            } else {
                $html->setvar('ip_city_id', $cityInfo['city_id']);
                $html->setvar('ip_city_title', l($cityInfo['city_title']));
                $html->setvar('type_location', 'city');
            }
            $html->setvar('params', $params);

            $html->setvar('service', strtolower(Common::getOption('maps_service')));
            if (Common::getOption('maps_service') == 'Bing'){
                $html->setvar('url', Common::getMapImageUrl($cityInfo['lat'] / IP::MULTIPLICATOR , ($cityInfo['long']) / IP::MULTIPLICATOR , 815, 369, 10, FALSE));
            } elseif(Common::getOption('maps_service') == 'Google') {
                $html->setvar('url', Common::getMapImageUrl($cityInfo['lat'] / IP::MULTIPLICATOR , ($cityInfo['long']) / IP::MULTIPLICATOR , 640, 282, 2, FALSE));
            }
            $userIndex = 0;
            if (Common::isOptionActive('users_on_main_page_map_and_mobile')){
                $sql = Common::sqlUsersNearCity($cityInfo, $numUsers);
                $rows=DB::rows($sql);
                shuffle($rows);
                $userRow = 1;
                $userColumn = 1;

                foreach($rows as $row) {
                    if($userIndex == 4) {
                        $userRow = 2;
                        $userColumn = 1;
                    }

                    if (empty($sizePhoto)) {
                        if ($userIndex < 4) {
                            $photoNeeded = User::getPhotoDefault($row['user_id'], 'r', false, $row['gender']);
                        } else {
                            $photoNeeded = User::getPhotoDefault($row['user_id'], 's', false, $row['gender']);
                        }
                    } else {
                        $photoNeeded = User::getPhotoDefault($row['user_id'], $sizePhoto, false, $row['gender']);
                    }

                    $blockMapUser = $blockMap . '_user';
                    $html->setvar($blockMapUser . '_row', $userRow);
                    $html->setvar($blockMapUser . '_column', $userColumn);
                    $html->setvar($blockMapUser . '_num', ++$userIndex);
                    $html->setvar($blockMapUser . '_id', $row['user_id']);
                    $html->setvar($blockMapUser . '_name', $row['name']);
                    $html->setvar($blockMapUser . '_age', $row['age']);
                    //$html->setvar($blockMapUser . '_photo', $photo);
                    $html->setvar($blockMapUser . '_photo_needed', $photoNeeded);
                    $html->setvar($blockMapUser . '_url', User::url($row['user_id'], $row));
                    $html->parse($blockMapUser, true);
                    $userColumn++;
                }
            }
            if ($userIndex && $parseBlock) {
                //$html->parse($blockMap, false);
            }
        }
    }

    static function parseApp(&$html)
    {
        $isMobileModuleParsed = false;
        $blockMobileApp = 'mobile_app_ios';
        if($html->blockexists($blockMobileApp) && Common::isAppIos()) {
            $html->parse($blockMobileApp);
            $isMobileModuleParsed = true;
        }

        $blockMobileApp = 'mobile_app_android';
        if($html->blockexists($blockMobileApp) && Common::isAppAndroid()) {
            $html->parse($blockMobileApp);
            $isMobileModuleParsed = true;
        }

        if($isMobileModuleParsed) {

            Pay::parseInAppPurchaseProducts($html);

            $html->setvar('city_last_msg_id', City::lastMsgId());
            $block = 'mobile_app';
            $html->setvar("{$block}_push_notifications", intval(guser('set_notif_push_notifications') == 1));

            CBanner::getBlock($html, 'admob_top');
            CBanner::getBlock($html, 'admob_bottom');

            $html->setvar('mobileAppIsTokenUpdateRequired', PushNotification::isTokenUpdateRequired());

            $html->parse($block);
        }

        $valAppVibrationDuration = 'app_vibration_duration';
        if($html->varExists($valAppVibrationDuration)) {
            $html->setvar($valAppVibrationDuration, Common::getOption('app_vibration_duration'));
        }
    }

    function parseCounterNewMessagesMobileUrban(&$html, $allowUserMenuOnPage, $block = 'number_messages')
    {
        global $p;

        $display = get_param('display', '');
        $userId = User::getRequestUserId('user_id');
        if ($display == 'one_chat') {
            $counter = CIm::getCountNewMessages(null, $userId);
        } else {
            $counter = CIm::getCountNewMessages();
        }

        if (!$counter) {
            $counter = '';
        }
        $html->setvar('number_messages', $counter);
        $allowNumberMessagesPerPage = array('mutual_attractions.php',
                                            'users_viewed_me.php',
                                            'upgrade.php',
                                            'profile_settings.php',
                                            'profile_photo.php',
                                            'profile_interests_edit.php',
                                            'game_choose.php'
        );
        $notAllowNumberMessagesPerPage = array('search.php',
                                               'profile_view.php',
                                               'email_not_confirmed.php',
        );
        $notAllowNumberMessagesPerPage = array_merge($allowUserMenuOnPage, $notAllowNumberMessagesPerPage);
        if (in_array($p, $allowNumberMessagesPerPage)
            || ($p == 'messages.php' && $display == 'one_chat')
            || ($p == 'profile_view.php' && ($display == 'profile_info' || !$userId))
            || !in_array($p, $notAllowNumberMessagesPerPage)
        ) {
            if (!$counter && $p != 'messages.php') {
                $html->parse("{$block}_hide", false);
            }
            $html->parse($block, false);
        }
        if ($counter) {
            $html->parse('counter_events_show', false);
        }
    }

    function parseBlockImpact(&$html)
    {
        global $p;
        global $g_user;

        $guid = guid();
        $display = get_param('display');
        $cmd = get_param('cmd');
        $paramUid = User::getParamUid();

        $generalSchemeOptions = array(
            'color_scheme_button_primary_background_color_impact',
            'color_scheme_button_primary_background_color_hover_impact',
            'color_scheme_button_primary_text_color_impact',
        );

        if($guid && $g_user['role'] == 'group_admin') // added by sohel
            $html->parse('group_user', false);

        $noAllowPages = array('index.php', 'join.php', 'join_facebook.php');
        if(!in_array($p, $noAllowPages) && $html->blockExists('color_scheme_styles')) {
            $colorSchemeOptions = array(
                'color_scheme_header_text_color_impact',
                'color_scheme_header_text_color_hover_impact',
                'color_scheme_background_color_impact',
                'color_scheme_menu_icons_impact',
                'color_scheme_menu_text_color_impact',
                'color_scheme_menu_text_hover_color_impact',
                'color_scheme_menu_inactive_text_color_impact',
                'color_scheme_menu_counter_color_impact',
                'color_scheme_menu_counter_background_color_impact',
                'color_scheme_central_column_background_color_impact',
                'color_scheme_column_background_color_impact',
                'color_scheme_column_text_color_impact',
                'color_scheme_remove_ads_color_impact',
                'color_scheme_remove_ads_color_hover_impact',
                'color_scheme_remove_header_ads_color_impact',
                'color_scheme_remove_header_ads_color_hover_impact',
                'color_scheme_remove_footer_ads_color_impact',
                'color_scheme_remove_footer_ads_color_hover_impact',
                'color_scheme_footer_menu_color_impact',
                'color_scheme_footer_menu_color_hover_impact',
                'color_scheme_footer_copyright_color_impact',
                'color_scheme_menu_selected_item_background_color_impact',
                'color_scheme_join_button_yes_background_color_impact',
                'color_scheme_join_button_yes_background_color_hover_impact',
                'color_scheme_join_button_yes_text_color_impact',
                'color_scheme_join_button_no_background_color_impact',
                'color_scheme_join_button_no_background_color_hover_impact',
                'color_scheme_join_button_no_text_color_impact',

                'color_scheme_button_secondary_1_background_color_impact',
                'color_scheme_button_secondary_1_background_color_hover_impact',
                'color_scheme_button_secondary_1_text_color_impact',
                'color_scheme_button_secondary_2_background_color_impact',
                'color_scheme_button_secondary_2_background_color_hover_impact',
                'color_scheme_button_secondary_2_text_color_impact',
                'color_scheme_button_secondary_3_background_color_impact',
                'color_scheme_button_secondary_3_background_color_hover_impact',
                'color_scheme_button_secondary_3_text_color_impact',

                'color_scheme_button_upgrade_background_color_impact',
                'color_scheme_button_upgrade_background_color_hover_impact',
                'color_scheme_button_upgrade_text_color_impact',
                'color_scheme_button_like_background_color_impact',
                'color_scheme_button_like_background_color_hover_impact',
                'color_scheme_button_like_border_color_impact',
                'color_scheme_button_like_border_color_hover_impact',
                'color_scheme_button_like_text_color_impact',
                'color_scheme_button_active_like_background_color_impact',
                'color_scheme_button_active_like_background_color_hover_impact',
                'color_scheme_button_message_background_color_impact',
                'color_scheme_button_message_background_color_hover_impact',
                'color_scheme_button_message_text_color_impact',
                'color_scheme_button_message_reply_rate_background_color_impact',
                'color_scheme_button_message_bottom_background_color_impact',
                'color_scheme_button_message_bottom_background_color_hover_impact',
                'color_scheme_button_message_bottom_text_color_impact',
                'color_scheme_button_invisible_mode_background_color_impact',
                'color_scheme_button_invisible_mode_background_color_hover_impact',
                'color_scheme_button_invisible_mode_text_color_impact',
                'color_scheme_search_filter_background_color_impact',
                'color_scheme_search_extended_filter_background_color_impact',

                'color_scheme_chat_header_background_color_impact',
                'color_scheme_chat_header_icon_color_impact',
                'color_scheme_chat_header_text_color_impact',
                'color_scheme_chat_online_header_background_color_impact',
                'color_scheme_chat_online_header_background_color_hover_impact',
                'color_scheme_chat_online_header_icon_color_impact',
                'color_scheme_chat_online_header_text_color_impact',
                'color_scheme_chat_offline_header_background_color_impact',
                'color_scheme_chat_offline_header_background_color_hover_impact',
                'color_scheme_chat_offline_header_icon_color_impact',
                'color_scheme_chat_offline_header_text_color_impact',
                'color_scheme_pagination_active_item_text_color_impact',
                'color_scheme_pagination_active_item_background_color_impact',
                'color_scheme_pagination_inactive_item_text_color_impact',
                'color_scheme_pagination_disabled_item_color_impact',
            );

            $colorSchemeOptions = array_merge($colorSchemeOptions, $generalSchemeOptions);

            foreach($colorSchemeOptions as $colorSchemeOption) {

                if($colorSchemeOption == 'color_scheme_chat_online_header_background_color_hover_impact') {
                    $color = Common::getOption($colorSchemeOption);
                    $html->setvar($colorSchemeOption . '_darker', Common::colorLuminance(Common::getOption($colorSchemeOption),15));
                    $html->setvar($colorSchemeOption, $color);
                } else {
                    $value = Common::getOption($colorSchemeOption);
                    if ($colorSchemeOption == 'color_scheme_background_color_impact') {
                        if (Common::getOption('color_scheme_background_type_impact') == 'gradient') {
                            $value = Common::getOption('color_scheme_background_color_upper_impact');
                        }
                        $html->setvar('color_scheme_background_color_impact_one_color', $value);
                        $value = Common::getBackgroundColorSheme('color_scheme_background');
                    }
                    $html->setvar($colorSchemeOption, $value);
                }
            }
            $html->setvar('color_scheme_menu_item_border_top_color_rgba', implode(',', hex2rgb(Common::getOption('color_scheme_menu_item_border_top_color_impact'))) . ',' . $this->prepareOpacityValue(Common::getOption('color_scheme_menu_item_border_top_opacity_impact')));
            $html->setvar('color_scheme_menu_item_border_bottom_color_rgba', implode(',', hex2rgb(Common::getOption('color_scheme_menu_item_border_bottom_color_impact'))) . ',' . $this->prepareOpacityValue(Common::getOption('color_scheme_menu_item_border_bottom_opacity_impact')));

            $colorSchemeBackgroundImage = Common::getOption('color_scheme_background_image_impact');
            if($guid && $colorSchemeBackgroundImage != 'no_image') {
                $image = getFileUrl('main_page_image', $colorSchemeBackgroundImage, '_main_page_image_', 'color_scheme_background_image_impact', 'color_scheme_background_image');
                if ($image) {
                    $blockStyleBgImage = 'color_scheme_styles_background_image';
                    $html->setvar($blockStyleBgImage, $image);
                    $html->parse("{$blockStyleBgImage}_head_js");
                    $html->parse("{$blockStyleBgImage}_js");
                    $html->parse($blockStyleBgImage);
                }
            }

            $html->parse('color_scheme_styles');
        }

        if(!guid() && !in_array($p, array('join2.php', 'about.php', 'contact.php', 'page.php', 'info.php'))
                        && $html->blockExists('color_scheme_styles_main_page')) {
            $colorSchemeOptions = array(
                'main_page_header_background_color',
                'color_scheme_main_page_footer_background_color_impact',
                'color_scheme_main_page_footer_text_color_impact',
                'color_scheme_join_overlay_opacity_impact',
            );
            $colorSchemeOptions = array_merge($colorSchemeOptions, $generalSchemeOptions);
            foreach($colorSchemeOptions as $colorSchemeOption) {
                $value = Common::getOption($colorSchemeOption);
                if ($colorSchemeOption == 'main_page_header_background_color') {
                    $value = Common::getBackgroundColorSheme('main_page_header_background', '');
                }
                $html->setvar($colorSchemeOption, $value);
            }

            if(Common::getOption('main_page_image_size_impact') == 'big') {
                if ($p == 'index.php') {
                    $html->parse('color_scheme_styles_main_page_image_size_big');
                }
            }

            if ($p == 'join.php' || $p == 'join_facebook.php') {
                $html->parse('color_scheme_styles_main_page_image_join_size_big');
            }

            if (($p == 'join.php' || $p == 'join_facebook.php') && $html->blockExists('bl_header_join')) {
                $html->parse('bl_header_join', false);
            }

            $html->parse('color_scheme_styles_main_page');
        }

        if(!guid() && $p == 'join2.php' && $html->blockExists('color_scheme_styles_join2_page')) {
            $colorSchemeOptions = array(
                'color_scheme_column_background_color_impact',
            );
            foreach($colorSchemeOptions as $colorSchemeOption) {
                $html->setvar($colorSchemeOption, Common::getOption($colorSchemeOption));
            }

            $html->setvar('color_scheme_background_color_impact_light', Common::colorLuminance(Common::getOption('color_scheme_background_color_impact'), 50, false));


            $html->parse('color_scheme_styles_join2_page');
        }

        if ($p == 'city.php') {
            $html->setvar('color_scheme_3dcity_page_background_color_impact', Common::getOption('color_scheme_3dcity_page_background_color_impact'));
            $html->setvar('color_scheme_3dcity_background_color_impact', Common::getOption('color_scheme_3dcity_background_color_impact'));
            $html->parse('color_scheme_styles_city_page');
        }

        if ($html->varExists('user_photo_default_url') && $guid) {
            $html->setvar('user_photo_default_url', User::getPhotoDefault($guid, 'm'));
        }

        // PDF FILE
        $row_user = User::getInfoFull($guid, 2);
        if(isset($row_user['profile_pdf']) && $row_user['profile_pdf']!=''){
            $html->setvar("pdf_button_text", "Delete PDF");
            $html->setvar("pdf_button_class", "profile_delete_pdf");
            $html->setvar("pdf_file_name", $row_user['profile_pdf']);
        } else {
            $html->setvar("pdf_button_text", "Upload PDF");
            $html->setvar("pdf_button_class", "profile_upload_pdf");
        }

        $isJoinStep1 = $p == 'join.php' || $p == 'join_facebook.php';
        $isJoinStep2 = $p == 'join2.php';

        if ($guid) {
            $minNumberPhotosToUseSite = intval(Common::getOption('min_number_photos_to_use_site'));
            $html->setvar('min_number_photos_to_use_site', $minNumberPhotosToUseSite);
            $keyAlert = User::checkAccessToSiteWithMinNumberUploadPhotos();
            $html->setvar('alert_min_number_photos_to_use_site', $keyAlert);

            $html->setvar('profile_status_max_length', Common::getOptionTemplateInt('profile_status_max_length'));

            $html->setvar('url_profile', User::url($guid));
            /* Header */
            $blockMenu = 'header_menu_impact';
            if ($html->blockexists($blockMenu)) {
                $isSelectedProfileMenu = $p == 'profile_view.php'
                || ($p == 'search_results.php' && ($display == 'profile' && $guid == $paramUid));
                $html->cond($isSelectedProfileMenu, 'header_menu_profile_selected', 'header_menu_profile');
                $html->cond($p == 'upgrade.php', 'header_menu_upgrade_selected', 'header_menu_upgrade');
                $html->cond($p == 'moderator.php', 'header_menu_moderator_selected', 'header_menu_moderator');
                if ($html->varExists('header_credits_balance')) {
                    $html->setvar('header_credits_balance', lSetVars('credit_balance', array('credit' => $g_user['credits'])));
                }
                Menu::parseSubmenu($html, $blockMenu);
            }

			$blHeaderMember = 'header_member';
			if ($p == 'city.php') {
				$html->parse('body_city', false);
				$html->parse($blHeaderMember . '_logo', false);
			}

            if ($html->blockExists($blHeaderMember)) {
                if (Common::getOption('lang_loaded_rtl', 'main')) {
                    $html->parse($blHeaderMember . '_head_style_rtl', false);
                }
                $html->parse($blHeaderMember . '_head', false);
                //$html->parse('banner_header_bl', false);
                $html->parse($blHeaderMember, false);
            }
            /* Footer */

            if ($html->varExists('user_photo_default_id')) {
                $html->setvar('user_photo_default_id', User::getPhotoDefault($guid, 'b', true));
            }
            if ($html->blockExists('column_narow_item')) {
                CProfileNarowBox::parseItems($html);
            }
            if ($html->blockExists('right_recently_visited'))
                CProfileNarowBox::Right_recently_visited($html);
            

            /* Response payment system */
            $type = get_param('type');
            $blockPaymentShow = 'payment_pop_show_' . $type;
            //&& in_array($type, array('search', 'refill', 'video_chat'))
            if ($html->blockExists($blockPaymentShow)) {
                $param = explode('-', base64_decode(get_param('custom')));
                $isErrorPayment = false;
                if (count($param) && isset($param[5])) {
                    if ($param[5] == 'payment_error') {
                        $isErrorPayment = true;
                        $html->parse('system_payment_error', false);
                    }
                }
                if (!$isErrorPayment && $cmd == 'payment_thank') {
                    $html->parse($blockPaymentShow, false);
                }
            }
            /* Response payment system */

            $blFooterMember = 'footer_member';
            if ($html->blockExists($blFooterMember)) {

                User::parseProfileVerification($html, null, 'profile_verification_unverified_my');

                //$html->parse('banner_footer_bl', false);
                CBanner::getBlock($html, 'right_column');
                if (Common::isCreditsEnabled()) {
                    $html->parse($blFooterMember . '_increase');
                }
                if ($p != 'city.php') {
                    $html->parse("{$blFooterMember}_left", false);
                    $html->parse("{$blFooterMember}_right", false);
                }
                $html->parse($blFooterMember, false);
            }
        } else {
            $blHeaderVisitor = 'header_visitor';
            $blFooterVisitor = 'footer_visitor';
            if (in_array($p, array('about.php', 'contact.php', 'page.php', 'info.php'))) {
                //$html->parse('banner_header_bl', false);
                //$html->parse('banner_footer_bl', false);
                $html->parse('body_visitor_page', false);
                $blHeaderVisitor = 'header_visitor_page';
                $blFooterVisitor = 'footer_member';
                if (Common::isOptionActive('top_select') && $html->blockexists('show_lang_top')) {
                    Common::parseDropDownListLanguage($html, 'show_lang_top', 'lang_top');
                }
            } else {
                CustomPage::parseMenu($html, 'bottom_visitor');
            }
            if ($p == 'join_facebook.php') {
                $html->parse($blHeaderVisitor . '_join_social', false);
            }

            $html->parse($blHeaderVisitor . '_head', false);
            if ($isJoinStep2) {
                $html->parse($blHeaderVisitor . '_join2_head', false);
            } elseif ($html->blockExists($blHeaderVisitor)) {
                if ($isJoinStep1) {
                    $html->parse($blHeaderVisitor . '_join1_head', false);
                }
                if ($p != 'join.php' || ($p == 'join.php' && $cmd != 'please_login')) {
                    $html->parse($blHeaderVisitor . '_sign_in', false);
                }
                $html->parse($blHeaderVisitor . '_head_style', false);
                if (Common::getOption('lang_loaded_rtl', 'main')) {
                    $html->parse($blHeaderVisitor . '_head_style_rtl', false);
                }

                $html->parse($blHeaderVisitor, false);
            }
            $blSocialLogin = 'social_login';
            if ($html->blockExists($blSocialLogin)) {
                Social::parse($html);
            }

            if (($p == 'join.php' || $p == 'join_facebook.php') && $html->blockExists('info_page')) {
                $html->assign('terms', PageInfo::getInfo('term_cond'));
                $html->assign('priv', PageInfo::getInfo('priv_policy'));
                $html->parse('info_page', false);
            }

            if ($p != 'join.php' || ($p == 'join.php' && $cmd != 'please_login')) {
                $html->parse('form_sign_in', false);
            }

            if (!$isJoinStep2) {
				if ($p == 'index.php') {
					$html->parse($blFooterVisitor . '_info', false);
				}
                $html->parse($blFooterVisitor, false);
            }
        }

        if($html->varExists('user_allowed_feature')) {
            $html->setvar('user_allowed_feature', User::accessСheckFeatureSuperPowersGetList());
        }

        $profileBgVideoPlayDisabled = 0;

        if($isJoinStep1) {
            $profileBgVideoPlayDisabled = intval(Common::isOptionActive('main_page_video_stop_on_join_page'));
        }
        $html->setvar('profile_bg_video_play_disabled', $profileBgVideoPlayDisabled);

        if(IS_DEMO) {
            $html->setvar('demo_version', get_session('demo_version'));
        }

		if ($p == 'live_streaming.php') {
			if ($guid == $paramUid && $html->blockExists('pp_presenter_start')) {
				$html->parse('pp_presenter_start', false);
			}
		}

		if (in_array($p, array('live_list.php', 'live_list_finished.php', 'live_streaming.php'))) {
			if ($html->blockExists('base_url_main_head')) {
				$html->parse('base_url_main_head', false);
			}
		}

    }

	function parseBlockUrban(&$html)
    {
		global $p;

		$paramUid = User::getParamUid();
		if (in_array($p, array('live_list.php', 'live_list_finished.php', 'live_streaming.php'))) {
			if ($html->blockExists('base_url_main_head')) {
				$html->parse('base_url_main_head', false);
			}
			if (guid() == $paramUid && $html->blockExists('pp_presenter_start')) {
				$html->parse('pp_presenter_start', false);
			}
		}
	}

    function prepareOpacityValue($value)
    {
        $value = intval(substr(trim($value), 0, 3));
        if($value > 100) {
            $value = 100;
        } elseif ($value < 0) {
            $value = 0;
        }

        $value = $value / 100;

        return $value;
    }

    function parseBlockImpact_mobile(&$html)
    {
        global $p, $g_user;

        $guid = guid();
        if($guid && $g_user['role'] == 'group_admin') // added by sohel
            $html->parse('group_user', false);

        if($html->blockExists('color_scheme_styles')) {
            $colorSchemeOptions = array(
                'color_scheme_mobile_background_color_impact',
                'color_scheme_mobile_header_color_impact',
                'color_scheme_mobile_header_icons_color_impact',
                'color_scheme_mobile_header_counter_color_impact',
                'color_scheme_mobile_header_counter_text_color_impact',
                'color_scheme_mobile_label_text_color_impact',
                'color_scheme_mobile_pencil_color_impact',
                'color_scheme_mobile_text_color_impact',
                'color_scheme_mobile_footer_color_impact',
                'color_scheme_mobile_footer_text_color_impact',
                'color_scheme_mobile_footer_arrow_up_color_impact',
                'color_scheme_mobile_footer_more_options_color_impact',
                'color_scheme_mobile_subheader_color_impact',
                'color_scheme_mobile_subheader_text_color_impact',
                'color_scheme_mobile_horizontal_lines_color_impact',
                'color_scheme_mobile_upgrade_page_text_color_impact',
                'color_scheme_mobile_main_page_background_color_impact',
                'color_scheme_mobile_main_page_text_color_impact',
                'color_scheme_mobile_main_button_color_impact',
                'color_scheme_mobile_main_button_text_color_impact',
                'color_scheme_mobile_main_page_button_terms_color_impact',
                'color_scheme_mobile_main_page_button_terms_text_color_impact',
                'color_scheme_mobile_button_transparent_border_color_impact',
                'color_scheme_mobile_button_transparent_active_color_impact',
                'color_scheme_mobile_button_transparent_background_color_impact',
                'color_scheme_mobile_button_transparent_text_color_impact',
                'color_scheme_mobile_main_page_button_terms_active_color_impact',
                'color_scheme_mobile_menu_background_color_impact',
                'color_scheme_mobile_menu_icon_color_impact',
                'color_scheme_mobile_menu_text_color_impact',
                'color_scheme_mobile_menu_text_background_color_impact',
                'color_scheme_mobile_button_disabled_background_color_impact',
                'color_scheme_mobile_alert_icon_color_impact',
                'color_scheme_mobile_alert_text_color_impact',
                'color_scheme_mobile_alert_button_ok_background_color_impact',
                'color_scheme_mobile_alert_button_ok_text_color_impact',
                'color_scheme_mobile_alert_button_cancel_background_color_impact',
                'color_scheme_mobile_alert_button_cancel_text_color_impact',
                'color_scheme_mobile_alert_button_cancel_border_right_color_impact',
                'color_scheme_mobile_main_button_active_color_impact',
                'color_scheme_mobile_main_page_overlay_opacity_impact',

                'color_scheme_mobile_button_message_background_color_impact',
                'color_scheme_mobile_button_message_background_color_active_impact',
                'color_scheme_mobile_button_message_text_color_impact',
                'color_scheme_mobile_button_message_reply_rate_background_color_impact'
            );
            foreach($colorSchemeOptions as $colorSchemeOption) {
                $value = Common::getOption($colorSchemeOption);
                if ($colorSchemeOption == 'color_scheme_mobile_main_page_background_color_impact') {
                    if (Common::getOption('color_scheme_mobile_main_page_background_type_impact') == 'gradient') {
                        $value = Common::getOption('color_scheme_mobile_main_page_background_color_upper_impact');
                    }
                    if (!$guid) {
                        $html->setvar('meta_theme_color', $value);
                    }
                    $value = Common::getBackgroundColorSheme('color_scheme_mobile_main_page_background');
                }
                $html->setvar($colorSchemeOption, $value);
            }

            $html->setvar('color_scheme_mobile_footer_color_number', substr(Common::getOption('color_scheme_mobile_footer_color_impact'), 1));

            if(!guid()) {
                $colorSchemeBackgroundImage = Common::getOption('color_scheme_mobile_main_page_background_image_impact');
                if($colorSchemeBackgroundImage != 'no_image') {
                    $image = getFileUrl('main_page_image', $colorSchemeBackgroundImage, '_main_page_image_', 'color_scheme_mobile_main_page_background_image_impact', 'color_scheme_mobile_main_page_background_image', 'mobile');
                    if ($image) {
                        $html->setvar('color_scheme_styles_mobile_background_image_pic', $colorSchemeBackgroundImage);
                        $html->setvar('color_scheme_styles_mobile_background_image', $image);
                        $html->parse('color_scheme_styles_mobile_background_image');
                    }
                }
                if(!Common::isOptionActive('color_scheme_mobile_main_page_show_top_shadow_impact')) {
                    $html->parse('color_scheme_mobile_main_page_show_top_shadow_impact');
                }

                $html->setvar('color_scheme_mobile_join_page_text_color_impact_rgba', implode(',', hex2rgb(Common::getOption('color_scheme_mobile_join_page_text_color_impact'))) . ',' . $this->prepareOpacityValue(Common::getOption('color_scheme_mobile_join_page_text_opacity_impact')));

                $html->setvar('color_scheme_mobile_main_page_text_shadow_color_impact_rgba', implode(',', hex2rgb(Common::getOption('color_scheme_mobile_main_page_text_shadow_color_impact'))) . ',' . $this->prepareOpacityValue(50));

                $html->parse('color_scheme_styles_mobile_main_page');
            }

            $html->setvar('color_scheme_mobile_button_transparent_active_color_impact_rgba', implode(',', hex2rgb(Common::getOption('color_scheme_mobile_button_transparent_active_color_impact'))) . ',' . $this->prepareOpacityValue(20));

            $html->setvar('color_scheme_mobile_menu_background_color_impact_rgba', implode(',', hex2rgb(Common::getOption('color_scheme_mobile_menu_background_color_impact'))) . ',' . $this->prepareOpacityValue(87));
            $html->setvar('color_scheme_mobile_alert_background_color_impact_rgba', implode(',', hex2rgb(Common::getOption('color_scheme_mobile_alert_background_color_impact'))) . ',' . $this->prepareOpacityValue(87));

            $html->parse('color_scheme_styles');
        }

        $display = get_param('display');
        $cmd = get_param('cmd');
        $paramUid = User::getParamUid($guid, 'user_id');

        $html->setvar('request_user_name', '');
        $the_user_info = User::getInfoFull($paramUid);
        if(isset($the_user_info['under_admin'])) { // added by sohel
            $paramUid = $the_user_info['under_admin'];
            $ua_info = User::getInfoFull($paramUid);
            $html->setvar('request_user_name', ' ('.$ua_info['name'].')'); // group admin name
        }

        $html->setvar('request_user_id_mobile', $paramUid);
        $isUserOnline = intval(User::isOnline($paramUid, null, true));
        $html->setvar('request_user_online', $isUserOnline);
        if (Common::isAppAndroid() && $html->blockExists('app_android_style')) {
            if (Common::getOption('lang_loaded_rtl', 'main')) {
                $html->parse('app_android_style_rtl', false);
            }
            $html->parse('app_android_style', false);
        }
        if ($guid) {
            $blHeaderParse = 'header_member';
            $blFooterParse = 'footer_member';
            if ($p == 'search_results.php') {
                $html->parse('header_additional_menu_filter', false);
            } else {
                $html->parse('header_additional_menu_match', false);
            }
            if ($guid != $paramUid) {
                if ($html->blockExists("{$blFooterParse}_more_menu")) {
                    $blockItemMoreMenu = "{$blFooterParse}_more_menu";
                    if (Common::isOptionActive('contact_blocking')){
                        $html->parse("{$blockItemMoreMenu}_contact_blocking", false);
                    }
                    if (Common::getOption('type_media_chat')=='webrtc'){
                        if(Common::isOptionActive('audiochat')){
                            $html->parse("{$blockItemMoreMenu}_audiochat", false);
                        }
                        if(Common::isOptionActive('videochat')){
                            $html->parse("{$blockItemMoreMenu}_videochat", false);
                        }
                    }
                    if (City::isActiveStreetChat()){
                        $html->parse("{$blockItemMoreMenu}_street_chat", false);
                    }
                    if (User::isFriend($guid, $paramUid)){
                        $html->parse("{$blockItemMoreMenu}_disallow_private_photo", false);
                    }
                    $html->parse($blockItemMoreMenu, false);
                }
            }

            if ($html->varExists("{$blHeaderParse}_visitors_counter")) {
                $viewers = User::getNumberViewersMeProfiles();
                $counterVisitors = $viewers['new'];
                if (!$counterVisitors) {
                    $counterVisitors = '';
                }
                $html->setvar("{$blHeaderParse}_visitors_counter", $counterVisitors);
                if ($counterVisitors) {
                    $html->parse("{$blHeaderParse}_visitors_counter_show", false);
                }
            }

            if ($html->varExists("{$blHeaderParse}_messages_counter")) {
                if ($display == 'one_chat') {
                    $countNewMsg = CIm::getCountNewMessages(null, $paramUid);
                } else {
                    $countNewMsg = CIm::getCountNewMessages();
                }
                if ($countNewMsg) {
                    $html->parse("{$blHeaderParse}_messages_counter_show", false);
                    $html->parse('counter_events_show', false);
                } else {
                    $countNewMsg = '';
                }
                $html->setvar("{$blHeaderParse}_messages_counter", $countNewMsg);
            }

            if ($p !== 'email_not_confirmed.php') {// && $p !== 'confirm_email.php'
                $html->parse('header_member_top_menu', false);

                $verifiedSystemsUser = User::getProfileVerificationData(User::getInfoBasic($guid));
                $verificationSystemsData = $verifiedSystemsUser['data'];
                if($verificationSystemsData) {
                    $html->setvar('footer_verification_system_options', h_options($verificationSystemsData, ''));
                    $html->parse('footer_verification_system_options', false);
                }
            }
        } else {
			$blHeaderParse = 'header_visitor';
            $blFooterParse = 'footer_visitor';
        }
        if ($html->blockExists($blHeaderParse)) {
            if (Common::getOption('lang_loaded_rtl', 'main')) {
                $html->parse('header_style_rtl', false);
            }
            $html->parse("{$blHeaderParse}_script", false);
            $html->parse("{$blHeaderParse}_body_cl", false);

            if ($guid || $p == 'confirm_email.php') {
                $html->parse("{$blHeaderParse}_html_cl", false);
                CUserMenu::setType('user_menu');
                CUserMenu::parseMenu($html);
            }

            $html->parse($blHeaderParse, false);
        }
        if ($html->blockExists($blFooterParse)) {
            $html->parse($blFooterParse, false);
        }
        if ($html->varExists('set_mobile_ajax_load')) {
            $setAjaxLoad = get_param('set_mobile_ajax_load', null);
            if ($setAjaxLoad !== null) {
                set_session('set_mobile_ajax_load', intval($setAjaxLoad));
            }
            $html->setvar('set_mobile_ajax_load', get_session('set_mobile_ajax_load', 1));
        }

        if($guid) {
            $metaThemeColor = Common::getOption('color_scheme_mobile_header_color_impact');
            $html->setvar('meta_theme_color', $metaThemeColor);
        }

        $html->setvar('tmpl_active', Common::getOption('tmpl_active', 'tmpl'));

        if($html->varExists('user_allowed_feature')) {
            $html->setvar('user_allowed_feature', User::accessСheckFeatureSuperPowersGetList());
        }

        $html->setvar('sending_messages_per_day', Common::getOption('sp_sending_messages_per_day_urban'));

        CBanner::isAdmobVisible($html);

    }

    function parseBlock(&$html)
    {
        global $g;
        global $g_user;
        global $g_info;
        global $area;
        global $p;
        global $adsense;
        global $xajax;

        $this->name = Common::getTmplName();


        Common::parseGdprCookie($html);


        if($html->varExists('user_allowed_feature') && ($this->name == 'urban' || $this->name == 'urban_mobile')) {
            $html->setvar('user_allowed_feature', User::accessСheckFeatureSuperPowersGetList());
        }

        /* Send image to chat */
        if ($html->varExists('max_filesize')) {
            $maxFileSize = Common::getOption('photo_size');
            $html->setvar('max_filesize', mb_to_bytes($maxFileSize));
            $html->setvar('max_photo_file_size_limit', lSetVars('max_file_size', array('size' => $maxFileSize)));
        }
        /* Send image to chat */

        if ($html->varExists('load_router')) {
            $html->setvar('load_router', intval($g['router']['load']));
        }

        if ($html->varExists('site_options')) {
            $html->setvar('site_options', Common::getAllowedOptionsJs());
        }

        if ($html->varExists('guser_options')) {
            $html->setvar('guser_options', Common::getGUserJs());
        }

        if (IS_DEMO && $html->varExists('is_demo_site') ) {
            $html->setvar('is_demo_site', 1);
        }

        if(Common::isOptionActive('only_apps_active') && Common::getOption('site_part','main')=='main'){
            $url = Common::urlSite() . MOBILE_VERSION_DIR . '/index.php';
            redirect($url);
        }

        $headermenuItemsBlocks=Menu::getSubmenuItemsList('header_menu');


        //Показаны будут только те элементы подменю, которые будут в соответствующих массивах
        //$submenuParsingBlocks и $headermenuParsingBlocks
        $submenuParsingBlocks=array();
        $headermenuParsingBlocks=array( 'header_menu_people_nearby_item',
                                        'header_menu_encounters_item',
                                        'header_menu_messages_item',
                                     );

        if($html->varexists('current_page')) {
            $html->setvar('current_page', $p);
        }

        $optionTmplSet = Common::getOption('set', 'template_options');
        $optionTmplName = Common::getOption('name', 'template_options');

        if (isset($g['options']['hide_site_from_guests']) && $g['options']['hide_site_from_guests'] == 'Y') {
            if ($g_user['user_id'] == 0) {
                if (!in_array($p, $g['options']['guest_pages'])) {
                    redirect(Common::pageUrl('join'));
                }
            }
        }

        if($html->varexists('header_favicon')) {
            $html->setvar('header_favicon', Common::getFaviconSiteHtml());
        }

        if($html->varexists('header_favicon_url')) {//only web notification
            $faviconUrl = '';
            $faviconFileName = Common::getFaviconFilename();
            if ($faviconFileName) {
                $faviconUrl = $g['path']['url_files'] . $faviconFileName . '?v=' . filemtime($g['path']['dir_files'] . $faviconFileName);
            }
            $html->setvar('header_favicon_url', $faviconUrl);
        }

        if (Common::isMobile()) {
            if($html->varExists('header_url_logo_mobile')) {
                $urlLogo = Common::getUrlLogo('logo', 'mobile');
                Common::parseSizeParamLogo($html, 'logo', $urlLogo);
                $html->setvar('header_url_logo_mobile', $urlLogo);
            }
            if($html->varExists('header_url_logo_mobile_inner')) {
                $urlLogo = Common::getUrlLogo('logo_inner', 'mobile', 'inner');
                Common::parseSizeParamLogo($html, 'logo_inner', $urlLogo);
                $html->setvar('header_url_logo_mobile_inner', $urlLogo);
            }
        } else {
            if($html->varExists('header_url_logo')) {
                $urlLogo = Common::getUrlLogo();
                Common::parseSizeParamLogo($html, 'logo', $urlLogo);
                $html->setvar('header_url_logo', $urlLogo);
            }
            if($html->varExists('header_url_logo_inner')) {
                $urlLogo = Common::getUrlLogo('logo_inner', 'main', 'inner');
                Common::parseSizeParamLogo($html, 'logo_inner', $urlLogo);
                $html->setvar('header_url_logo_inner', $urlLogo);
            }
            if($html->varExists('header_url_logo_footer')) {
                $html->setvar('header_url_logo_footer', Common::getUrlLogo('logo_footer', 'main', 'footer'));
            }
        }

        $imMsgLayout = Common::getOption('im_msg_layout', 'template_options');
        $html->setvar('im_msg_layout',  ($imMsgLayout == NULL) ? 'default' : $imMsgLayout);
        $html->setvar('dir_main_tmpl',  str_replace('\\', '\\\\', Common::getOption('dir_tmpl_main', 'tmpl')));

        if(Common::isOptionActive('help') || Common::isOptionActive('partner') || Common::isOptionActive('news')) {
            $html->setvar('dash3','|');
        }
        if(Common::isOptionActive('news')) {
            $html->parse('news_on');
            $html->parse('news_on1');
            $html->setvar('dash1','|');
        } else {
          $html->parse('news_off');
        }
        if(Common::isOptionActive('contact')) {
            $html->parse('contact_on');
        }
        if(Common::isOptionActive('help')) {
            $html->parse('help_on');
        } else {
            $html->parse('invite_on_class');
        }
        if(Common::isOptionActive('invite_friends')) {
            $html->parse('invite_on');
        }
        if(Common::isOptionActive('help') || Common::isOptionActive('news')) {
            $html->setvar('dash2','|');
        }
        if (!empty($xajax)) {
            $sJsFile = 'xajax_js/xajax.js'.$g['site_cache']["cache_version_param"];
            $html->setvar('xajax_js', $xajax->getJavascript($g['path']['url_main'] . '_server/', $sJsFile));
        }
        if (Common::isOptionActive('header_color_admin', 'template_options')) {
            $darker = Common::getOption('color_darker_oryx');
            $upper = get_session('color_upper');
            $lower = get_session('color_lower');
            $html->setvar('upper_header_color', $upper);
            $html->setvar('upper_header_color_darker', Common::colorLuminance($upper, $darker));
            $html->setvar('lower_header_color', $lower);
            $html->setvar('lower_header_color_darker', Common::colorLuminance($lower, $darker));
        }

        // Urban
        if (Common::isOptionActive('smooth_scroll')
            && Common::isOptionActive('smooth_scroll', 'template_options')) {
            if ($optionTmplName == 'urban') {
                if ($p != 'city.php') {
                    $html->parse('smooth_scroll');
                }
            } else {
                $html->parse('smooth_scroll');
            }
        }

        $display = get_param('display', '');
        if($html->blockexists('header_menu_people')) {
            $html->cond($p == 'search_results.php' && ($display == '' || $display == 'info'), 'header_menu_people_selected', 'header_menu_people');
        }

        if (Common::getOption('name', 'template_options') == 'urban') {
            $html->cond($display == 'encounters', 'header_menu_encounters_selected', 'header_menu_encounters');
        }

        if ($html->blockexists('header_menu_city_module') && Common::isModuleCityActive()) {
            $html->setvar('header_menu_city_url_city', City::url('city', false, false));
            $place = get_param('place');
            $html->cond($p == 'city.php' && !in_array($place, array('3d_labyrinth', 'street_chat')), 'header_menu_city_selected', 'header_menu_city');
            $html->parse('header_menu_city_module');
            //$headermenuParsingBlocks[]='header_menu_city_module';
        }
        if($html->blockexists('header_menu_wall')) {
            $html->cond($p == 'wall.php', 'header_menu_wall_selected', 'header_menu_wall');
        }

/*------------ Repeat from csspage.class.php
        if (Common::isOptionActive('map_on_main_page_urban', 'template_options')) {
            $color = Common::getOption('header_color_urban');
            $html->setvar('header_color_urban', $color);
        }

        if (Common::isOptionActive('tiled_footer_urban', 'template_options')
            && ($html->varExists('footer_tile_url'))) {
            if (Common::getOption('tiled_footer_urban') == 'tiled') {
                $file = Common::getOption('footer_tile_image_urban');
                $image = getFileUrl('footer_tiles', $file, '_footer_tile_image_', 'footer_tile_image_urban', 'footer_tile_image_default_urban');
                $html->setvar('footer_tile_url', $image);
            } else {
                $color = Common::getOption('footer_solid_color_urban');
                $html->setvar('footer_solid_color', $color);
            }
        }

        if (Common::isOptionActive('footer_image_urban', 'template_options')) {
            $file = Common::getOption('footer_image_urban');
            $image = getFileUrl('footer_image', $file, '_footer_image_', 'footer_image_urban', 'footer_image_default_urban');
            $html->setvar('footer_image_url', $image);
        }

*/
        if ($p == 'index.php'
            && Common::isOptionActive('facebook_like_button', 'template_options')
            && Common::isOptionActive('facebook_like_button')) {
            $blocks = array('fb_like_button_script' => 'script',
                            'fb_like_button_html' => 'html');
            foreach ($blocks as $block => $item) {
                if ($html->blockexists($block)) {
                    $method = 'getLikeButton' . $item;
                    $html->setvar($block, Facebook::$method());
                    $html->parse($block);
                }
            }
        }

        $isMainPage = ($p == 'index.php');
        if ($this->name == 'impact') {
            $isMainPage = in_array($p, array('index.php', 'join.php', 'join_facebook.php'));
        }

        if (guid()) {
            if ($html->blockexists('messages_counter')) {
                $count = CIm::getCountNewMessages();
                set_session('window_count_event', $count);
                $html->setvar('messages_counter_value', $count);
                if ($count == 0) {
                    $html->parse('messages_counter');
                }
            }
        } else {
            if ($isMainPage) {
                if (( Common::isOptionActive('map_on_main_page_urban', 'template_options')
					&& (Common::getOption('map_on_main_page_urban') == 'image')
						|| (Common::getOption('map_on_main_page_urban') == 'random_image') ) )  {

                    $file = Common::getOption('image_main_page_' . $this->name);

					if ($this->name == 'impact' && $file == 'no_image') {

					} else {
                        $image = getFileUrl('main_page_image', $file, '_main_page_image_', 'image_main_page_' . $this->name, ($this->name == 'urban' ? 'main_page_image_default_urban' : 'main_page_image_default'));
						if (empty($image)) {
							$image = Common::getOption('url_tmpl_main', 'tmpl') . 'images/empty.gif';
						}
						$html->setvar('main_page_image', $image);
						$html->parse('main_page_image', false);
						$color = Common::getOption('background_color_urban');
						$html->setvar('background_color_urban', $color);
						//$html->parse('main_page_image_content_style', false);

                        $blockJs = 'main_page_image_content_js';
                        if (($p == 'join.php' || $p == 'join_facebook.php') && $html->blockExists("{$blockJs}_join")) {
                            $html->parse("{$blockJs}_join", false);
                        }
                        if ($html->blockExists($blockJs)) {
                            $html->parse($blockJs, false);
                        }
					}
                }
            } else {
                $html->setvar('visitor_footer_logout', 'logout');
            }
        }
        $profileBgVideo = '{}';
        $isBgVideoAllPage = Common::isOptionActive('youtube_video_background_users_all_pages_urban');
        if (guid() && $p != 'city.php' && $p != 'live_streaming.php') {
            if ($html->varExists('user_profile_bg')) {
                $profileBg = guser('profile_bg');
                $display = get_param('display');
                $userId = User::getParamUid();
                $userName = get_param('name', guser('name'));
                $isBgVideo = Common::isOptionActive('youtube_video_background_users_urban');
                $colOrder=Common::getColOrder('narrow');
                $isNoProfileBg=(!isset($colOrder['customization']) || $colOrder['customization']['status']=='N');
                if($isNoProfileBg){
                    $profileBg='';
                    $bgDefault = Common::getOption('default_profile_background');
                    if ($bgDefault) {
                        $profileBg = $bgDefault;
                    }
                }elseif ($isBgVideo) {
                    $profileBgVideo = guser('profile_bg_video');
                }
                if ($p == 'search_results.php' && !$isNoProfileBg) {
                    if ($display != 'encounters' && $display != 'rate_people'
                    && ($userId != guser('user_id') || $userName != guser('name'))) {
                        if($userId != guser('user_id')) {
                            $profileBgUser = User::getInfoBasic($userId);
                        } else {
                            $sql = 'SELECT `profile_bg`, `profile_bg_video`
                                  FROM `user`
                                 WHERE `name` = ' .to_sql($userName);
                            $profileBgUser = DB::row($sql);
                        }

                        if(!$profileBgUser) {
                            $profileBgUser = array(
                                'profile_bg' => '',
                                'profile_bg_video' => ''
                            );
                        }

                        $profileBg = $profileBgUser['profile_bg'];
                        if ($isBgVideo) {
                            $isBgVideoAllPage = true;
                            $profileBgVideo = $profileBgUser['profile_bg_video'];
                        }
                    }elseif($isBgVideo && $display == 'profile'){
                        $isBgVideoAllPage = true;
                    }
                } elseif ($isBgVideo && ($p == 'profile_view.php' || $isBgVideoAllPage)) {
                    $isBgVideoAllPage = true;
                }
                $html->setvar('user_profile_bg', $profileBg);
            }
        }
        if ($html->varExists('user_profile_bg_video')
            || $html->varExists('main_page_animated_type')
            || $html->blockExists('main_page_video_index')) {
            $optionVideo = 'main_page_urban_video';
            $bgVideoMainPage = Common::getOption("{$optionVideo}_code");
            if($isMainPage) {
                if (Common::getOption('map_on_main_page_urban') == 'video') {
                    if ($bgVideoMainPage) {
                        $isBgVideoAllPage = 1;
                        $varVideo = 'main_page_video';
                        $html->setvar("{$varVideo}_mute", intval(Common::isOptionActive("{$optionVideo}_mute")));
                        $html->setvar("{$varVideo}_volume", intval(Common::getOption("{$optionVideo}_volume")));
                        $html->setvar("{$varVideo}_show_video_once", intval(Common::isOptionActive("{$optionVideo}_show_video_once")));
                        //$html->parse('main_page_video_header_js', false);
                        $html->parse("{$varVideo}_style", false);
                        $html->parse("{$varVideo}_js", false);
                        $html->parse("{$varVideo}_index", false);
                        $profileBgVideo = $bgVideoMainPage;
                    }
                } elseif (Common::getOption('map_on_main_page_urban') == 'animated') {
                    self::parseAnimatedMainPage($html);
                }
            }
            /*if($p == 'index.php'
               && $bgVideoMainPage
               && Common::getOption('map_on_main_page_urban') == 'video'){
                $isBgVideoAllPage = 1;
                $varVideo = 'main_page_video';
                $html->setvar("{$varVideo}_mute", intval(Common::isOptionActive("{$optionVideo}_mute")));
                $html->setvar("{$varVideo}_volume", intval(Common::getOption("{$optionVideo}_volume")));
                $html->setvar("{$varVideo}_show_video_once", intval(Common::isOptionActive("{$optionVideo}_show_video_once")));
                //$html->parse('main_page_video_header_js', false);

                $html->parse("{$varVideo}_js", false);
                $html->parse("{$varVideo}_style", false);
                $profileBgVideo = $bgVideoMainPage;
            }*/
            if($profileBgVideo == '') {
                $profileBgVideo = '{}';
            }
            $html->setvar('user_profile_bg_video', $profileBgVideo);
        }
        if ($html->varExists('is_bg_video_all_page')) {
            $html->setvar('is_bg_video_all_page', intval($isBgVideoAllPage));
        }
        if ($html->varExists('profile_bg_video_quality')) {
            $html->setvar('profile_bg_video_quality', Common::getOption('youtube_video_background_users_urban_quality'));
        }

        if (($p == 'city.php' || $p == 'live_streaming.php') && $html->blockExists('content_city')) {
            $html->parse('content_city', false);
        }

        // Urban

        $isBackground = Common::isOptionActive('background_only_not_logged_oryx');
        if (Common::isOptionActive('website_background', 'template_options')
            && (($isBackground && !guid())
                || !$isBackground
                || get_session('set_bg') == 'N')) {
            $background = get_session('bg_image');
            $isbgUser = false;
            $bgUser = Common::getOption('main', 'tmpl') . '_bg_' . $background;
            $bgTmplUrl = $g['tmpl']['url_tmpl_main'] . 'images/backgrounds/';
            $bgUserUrl = $g['path']['url_files'] . 'tmpl/';
            if (file_exists($bgUserUrl . $bgUser)) {
                $backgroundSrc = $bgUser;
                $isbgUser = true;
            } else {
                if (file_exists($bgTmplUrl . $background)) {
                    $backgroundSrc = $background;
                } else {
                    $backgroundSrc = Common::getOption('website_background_default', 'template_options');
                    Config::update('options', 'website_background_oryx', $backgroundSrc);
                }
            }

            $html->setvar('src', $backgroundSrc);

            if ($isbgUser) {
                    $html->setvar('bg_path', $bgUserUrl);
            } else {
                    $html->setvar('bg_path', $bgTmplUrl);
            }
            if (get_session('bg_image_changed') != $background) {
                $html->parse('load_js', true);
                set_session('bg_image_changed', $background);
            } else {
                $html->parse('img_src', true);
            }
            if ($background != 'none') {
                $html->parse('background_img', true);
                $html->parse('background_js', true);
            }
        }

        $html->setvar('url_home_page', Common::getHomePage());

        $isMobile = Common::isMobile();
        if (!$isMobile) {
           // Social::parse($html);
        }

        // AUTOCOMPLETE OFF

        $html->setvar("autocomplete", autocomplete_off());

        // AUTOCOMPLETE OFF
        // widgets

        User::parseWidgets($html);

        if(Common::isOptionActive('widgets')) {
            $html->parse("header_widgets", true);
            // $submenuParsingBlocks[]='header_widgets';
        }


        // widgets

        if (!isset($adsense) or $adsense) {
            $html->parse("adsense", true);
        }
        $html->setvar("user_id",  guid());
        if (isset($g['options']['blogs']) and $g['options']['blogs'] == "Y") {
            $html->parse("header_blogs", true);
            //$submenuParsingBlocks[]='header_blogs';
        }
        if (isset($g['options']['videogallery']) and $g['options']['videogallery'] == "Y") {
            $html->parse("header_videogallery", true);
            //$submenuParsingBlocks[]='header_videogallery';
        }
        if (isset($g['options']['gallery']) and $g['options']['gallery'] == "Y") {
            $html->parse("header_gallery", true);
            //$submenuParsingBlocks[]='header_gallery';
        }

        if (isset($g['options']['music']) and $g['options']['music'] == "Y") {
            $html->parse("header_music", true);
            //$submenuParsingBlocks[]='header_music';
        }

        CBanner::getBlock($html, 'top');

        $isShowBanner = true;
        if ($optionTmplSet == 'urban' && in_array($p, array('index.php'))) {
            $isShowBanner = false;
        }
        if ($optionTmplName == 'edge') {
            $isShowBanner = false;
        }
        if ($isShowBanner) {
            CBanner::getBlock($html, 'header');
        }
        if ($isShowBanner) {
            CBanner::getBlock($html, 'footer');
        }
        CBanner::getBlock($html, 'footer_additional');

        if (Common::isMobile()) {
            $prfFooter = '';
            $isShowBannerFooterMobile = true;
            if ($optionTmplSet == 'urban' && $this->name != 'impact_mobile') {
                if (guid()){
                    if (($p == 'profile_view.php' && $display != 'profile_info')
                        || ($p == 'messages.php' && $display == 'one_chat')
                        || !in_array($p, array('upgrade.php', 'profile_settings.php', 'profile_view.php', 'profile_personal_edit.php', 'messages.php'))) {
                        $isShowBannerFooterMobile = false;
                    } else {
                        $prfFooter = '_user';
                    }
                } elseif (!in_array($p, array('index.php'))) {
                    CBanner::getBlock($html, 'header_mobile');
                }
            } elseif($this->name == 'impact_mobile') {
                $prfFooter = '_user';
            }
            $isParseBlockStart = true;
            if ($isShowBannerFooterMobile) {
                if(CBanner::getBlock($html, 'footer_mobile', $prfFooter)){
                    $isParseBlockStart = false;
                }
            }
            if ($isParseBlockStart && $html->blockExists('footer_mobile_user_start')) {
                $html->parse('footer_mobile_user_start');
            }
        }

        foreach ($g_user as $k => $v)
            $html->setvar($k, $v);
        foreach ($g_info as $k => $v)
            $html->setvar($k, $v);

        if (guid()) {
            $result = 0;
            if($html->varExists('num_users_pending')) {
                $result = DB::result("SELECT COUNT(*) FROM friends_requests WHERE friend_id='" . $g_user['user_id'] . "' AND accepted=0", 0, 0, true);
                if ($result) {
                    $html->setvar("num_users_pending", $result);
                    $html->parse('users_pending');
                }
            }
            if (Common::isOptionActive('header_block_info', 'template_options')) {
                if ($result > 0) {
                    $html->parse('info_pending_href');
                    $html->parse('info_pending_title');
                }
                if (Common::isOptionActive('mail')) {
                    if ($g_info['new_mails'] > 0) {
                        if ($g_info['new_mails'] == 1) {
                            $sql = 'SELECT `id`
                                      FROM `mail_msg`
                                     WHERE `user_id` = ' . to_sql($g_user['user_id'], 'Number') . '
                                       AND `user_to` = ' . to_sql($g_user['user_id'], 'Number') . '
                                       AND `folder` = 1
                                       AND `new` = "Y"
                                     ORDER BY `id` DESC
                                     LIMIT 1';
                            $mid = DB::result($sql);
                            $html->setvar('mid', $mid);
                            $html->parse('info_mail_href');
                        }
                        $html->setvar('info_mail_num', $g_info['new_mails']);
                        $html->parse('info_mail_title');
                    }
                    $html->parse('info_mail');
                }
                $html->parse('header_block_info');
            }
        }

        if(Common::isOptionActive('mail')) {
            if ($g_info['new_mails'] == 1) {
                $html->parse('mail_one_href');
            }
            $html->parse('mail_on');
            //$submenuParsingBlocks[]='mail_on';
        }

        if(Common::isOptionActive('wink')) {
            $html->parse('wink_on');
            //$submenuParsingBlocks[]='wink_on';
        }

        if($html->blockexists('header_my_account')){
            //$submenuParsingBlocks[]='header_my_account';
        }

        $title = $g['main']['title'];


        if ($area == "login") {
            $xajaxLoginStatus = 'true';

            if (Moderator::checkAccess(true)) {
                $html->parse('moderator');
            }

            if (Common::isOptionActive('online_tab_enabled')) {
                $html->parse('online_tab_header', false);
                //$submenuParsingBlocks[]='online_tab_header';
            }
            if(Common::isMobile()) {
                $html->parse('auth_info');
            }
            ////$submenuParsingBlocks[]='auth_info';
            $html->parse("auth");
        } else {
            $xajaxLoginStatus = 'false';
            $html->setvar("title", $g['main']['title_orig']);
            $html->parse("loginform");
        }


        $submenuParsingBlocks=array_merge($submenuParsingBlocks,array(
                                                                    'profile_pics_header',
                                                                    'header_your_settings',
                                                                )
                            );


        // ВЫвод пунктов подменю, по порядку, определенному в админке

        if ($html->blockexists('submenu')) {
            Menu::parseSubmenu($html,'submenu');
        } else {
            foreach($submenuParsingBlocks as $k=>$v){
                $html->parse($v);
            }
        }


        //ВЫвод пунктов верхнего меню (Урбан)  по порядку, определенному в админке
        if ($html->blockexists('header_menu')) {
            Menu::parseSubmenu($html,'header_menu');
        } else {
            foreach($headermenuParsingBlocks as $k=>$v){
                $html->parse($v);
            }
        }

        $html->setvar('xajax_login_status', $xajaxLoginStatus);

        $html->setvar("title", $title);

        if ($html->blockexists('menu_item')) {
            Menu::parseMainMenu($html, array('partner'));
        } elseif ($html->blockexists('menu_item_table')) {
            Menu::parseMainMenuTable($html, array('partner'));
        } else {
            //Menu::parseMainMenuOldVersion($html);
        }



        if (Common::isOptionActive('top_select') && $html->blockexists('view')) {
            Common::parseDropDownListLanguage($html);
        }


        // SEO
        /*if (isset($g['main']['description'])) {
            $seo['title'] = $g['main']['title'];
            $seo['description'] = $g['main']['description'];
            $seo['keywords'] = '';
            if (isset($g['main']['keywords'])) {
                $seo['keywords'] = $g['main']['keywords'];
            }
        } else {
            $queryString = isset($_SERVER['QUERY_STRING']) ? trim($_SERVER['QUERY_STRING']) : '';

            $mobile = '';
            if (Common::isMobile()) {
                $mobile = MOBILE_VERSION_DIR . '/';
            }

            $url = $mobile . $p . ( $queryString != '' ? '?' . $queryString : '' );

            $sql = 'SELECT *
                      FROM seo
                     WHERE url = ' . to_sql($url, 'Text') . '
                     ORDER BY id DESC ';
            $seo = DB::row($sql, 0, true);

            if (!$seo) {
                // get default config
                $seo = $g['seo'];
                if (isset($l[$p]['header_title']) && $l[$p]['header_title'] != '') {
                    $seo['title'] = $l[$p]['header_title'];
                }
            }
        }

        $html->setvar('title', $seo['title']);
        $html->setvar('site_title',  addslashes($seo['title']));
        $html->setvar('description', htmlspecialchars($seo['description'], ENT_QUOTES, 'UTF-8'));
        $html->setvar('keywords', $seo['keywords']);*/
        Common::parseSeoSite($html);
        // SEO

        if (Common::isOptionActive('partner')) {
            $html->parse('link_partner', false);
        }

        // MOBILE
        if ($p == "index.php") {
            $html->parse("logo_index");
        } else {
            if ($area == "login") {
                $html->parse("logo_home");
            } else {
                $html->parse("logo");
            }
        }

        if (isset($g['tmpl']['header_title_link'])) {
            $html->setvar('header_title_link', $g['tmpl']['header_title_link']);
            $html->parse('header_title_link', true);
        } else {
            if ($p == 'info.php') {
                $html->setvar('l_header_title', l('header_title'));
            }
            $html->parse('header_title', true);
        }
        // MOBILE

        $html->setvar('year', date('Y'));



        if (guid()) {
            $html->parse('menu_l');
        } else {
            $html->parse('menu_l_no');
        }

        if (!isset($g['page_mode'])) {
            $g['page_mode'] = 'for_login_page';
        }

        $html->parse('header_' . $g['page_mode']);

        $html->setvar('year_current', date('Y'));

        $mainPageHeaderMode = Common::getOption('main_page_header_mode');
        if($mainPageHeaderMode) {
            $html->parse('header_' . $mainPageHeaderMode);
        }
        Common::devCustomJs($html);


        // URBAN
        if ($html->varExists('request_user_id')) {
            $html->setvar('request_user_id', User::getRequestUserId());
        }
        if ($html->varExists('url_logo')) {
            $html->setvar('url_logo', Common::getHomePage());
        }

        $module = guid() ? 'member' : 'visitor';
        if ($html->blockexists($module)) {
            $html->parse($module);
        }

        if(guid()
            || $this->name != 'impact'
            || ($this->name == 'impact' && in_array($p, array('about.php', 'contact.php', 'page.php', 'info.php')))) {
            if ($this->name != 'edge') {
                CustomPage::parseMenu($html, 'bottom');
            }
        }
        Common::parseBtnDownloadApp($html);

        if ($html->varExists('im_history_messages')) {
            $optionImHistory = intval(Common::getOption('im_history_messages','options'));
            if ($p == 'messages.php' && $display == '') {
                $optionImHistory = intval(Common::getOption('im_history_chat','options'));
            }
            if (!$optionImHistory) {
                $optionImHistory = 10;
            }
            $html->setvar('im_history_messages', $optionImHistory);
        }

		if ($html->varExists('live_price')) {
			$html->setvar('live_price', Pay::getServicePrice('live_stream', 'credits'));
		}

        if (guid() && $html->varExists('last_new_msg_id')) {
            $lastNewMsgId = 0;
            $lastNewMsg = CIm::getDataNewMessagesLast(1, 'id DESC');
            if ($lastNewMsg && isset($lastNewMsg[0])) {
                $lastNewMsgId = $lastNewMsg[0]['id'];
            }
            $html->setvar('last_new_msg_id', $lastNewMsgId);
        }
        //$isMobile = Common::isMobile();
        if (guid() && Common::isEnabledAutoMail('welcoming_message')
                && (!$isMobile || ($isMobile && ($p == 'messages.php' || $optionTmplSet != 'urban')))) {
            if($g_user['welcoming_message_notify']) {
                $block = 'notif_welcoming_message';
                if ($html->blockExists($block)) {
                    $html->setvar($block, CIm::getDataJsNewMessages());
                    $html->parse($block, false);
                    $data = array('welcoming_message_notify' => 0);
                    User::update($data, $g_user['user_id']);
                    $g_user['welcoming_message_notify'] = 0;
                }
            }
        }


        if ($html->varExists('notifications_lifetime')) {
            $html->setvar('notifications_lifetime', abs(intval(Common::getOption('message_notifications_lifetime'))));
        }

        if ($html->varExists('notifications_position')) {
            $html->setvar('notifications_position', Common::getOption('message_notifications_position'));
        }

        $isFreeSite = intval(Common::isOptionActive('free_site'));
        if ($html->varExists('is_free_site')) {
            $html->setvar('is_free_site', $isFreeSite);
        }
        if ($html->varExists('is_credits_enabled')) {
            $html->setvar('is_credits_enabled', intval(Common::isCreditsEnabled()));
        }

        //$prf = Common::isMobile() ? '_mobile' : '';
        //$vars =  array('url' => self::$url['chat_from_user' . $prf]);
        if ($html->varExists('video_chat_from_user')) {
            $prf = Common::isMobile() ? '_mobile' : '';
            $url = array(
                'chat_from_user' => Common::pageUrl('search_results') . '?uid={user_id}&display=profile',
                'chat_from_user_mobile' => Common::pageUrl('profile_view') . '?user_id={user_id}',
            );
            $vars =  array('url' => $url['chat_from_user' . $prf]);
            $html->setvar('video_chat_from_user', Common::lSetLink('video_chat_from_user', $vars, true, '', null, 'toJsL'));
            $html->setvar('audio_chat_from_user', Common::lSetLink('audio_chat_from_user', $vars, true, '', null, 'toJsL'));
            $html->setvar('street_chat_from_user', Common::lSetLink('street_chat_from_user', $vars, true, '', null, 'toJsL'));
        }

        if ($html->varExists('is_allowed_video_chat')) {
            $isAllowedAudioChat = User::isSuperPowers() || Common::isOptionActive('free_site') || !Common::isActiveFeatureSuperPowers('audiochat');
            $html->setvar('is_allowed_audio_chat', intval($isAllowedAudioChat));
            $isAllowedVideoChat = User::isSuperPowers() || Common::isOptionActive('free_site') || !Common::isActiveFeatureSuperPowers('videochat');
            $html->setvar('is_allowed_video_chat', intval($isAllowedVideoChat));

        }
        if (($isMobile || guid()) && Common::isOptionActive('credits_enabled')) {
            $html->setvar('video_chat_price', Pay::getServicePrice('video_chat','credits'));
            $html->setvar('audio_chat_price', Pay::getServicePrice('audio_chat','credits'));
        }

        if ($html->varExists('is_allowed_street_chat')) {
            $isAllowedStreetChat = City::isActiveStreetChat() && User::accessCheckFeatureSuperPowers('3d_city');
            $html->setvar('is_allowed_street_chat', intval($isAllowedStreetChat));
        }

        if (IS_DEMO) {
            $demoUid = get_param('uid');
            if ($demoUid) {
                $html->setvar('is_demo_user', intval(User::isDemoUser($demoUid)));
            }
        }

        if ($html->varExists('is_in_app_purchase_enabled')) {
            $html->setvar('is_in_app_purchase_enabled', intval(Common::isInAppPurchaseEnabled()));
        }

        if ($html->varExists('request_uri')) {
            $html->setvar('request_uri', base64_encode(Pay::getUrl()));
        }

        if (guid()) {
            $blockCredits = 'credits_header';
            if ($html->blockexists($blockCredits) && Common::isCreditsEnabled()) {
                $html->setvar($blockCredits . '_balance', lSetVars('credit_balance', array('credit' => $g_user['credits'])));
                $html->parse($blockCredits, false);
            }
        }
        if ($html->varExists('user_name') && isset($g_user['name'])) {
            $html->setvar('user_name', $g_user['name']);
        }
        if ($html->varExists('user_age') && isset($g_user['age'])) {
            $html->setvar('user_age', $g_user['age']);
        }

        if ($optionTmplName == 'urban' && guid() && $html->varExists('min_number_photos_to_use_site')) {
            $minNumberPhotosToUseSite = intval(Common::getOption('min_number_photos_to_use_site'));
            $html->setvar('min_number_photos_to_use_site', $minNumberPhotosToUseSite);
            $keyAlert = User::checkAccessToSiteWithMinNumberUploadPhotos();
            $html->setvar('alert_min_number_photos_to_use_site', $keyAlert);
        }

        if ($html->blockexists('member_header')
            || $html->blockexists('visitor_header')
            || $html->blockexists('visitor_footer')
            || $html->blockexists('member_footer')) {
            $isHeaderCustom = true;
            if (guid()) {

                if ($html->varExists('is_photo_default_public')) {
                    $html->setvar('is_photo_default_public', CProfilePhoto::isPhotoDefaultPublic(null, true));
                }
                if ($html->varExists('is_photo_public')) {
                    $html->setvar('is_photo_public', CProfilePhoto::getNumberPhotosUser($g_user['user_id'], false));
                }
                if ($html->varExists('hide_my_presence')) {
                    $html->setvar('hide_my_presence', User::getInfoBasic($g_user['user_id'], 'set_hide_my_presence'));
                }
                if ($html->varExists('avka_user_photo')) {
                    $html->setvar('main_photo_id', User::getPhotoDefault($g_user['user_id'], 'r', true));
                    $html->setvar('avka_user_photo', User::getPhotoDefault($g_user['user_id'], 'r'));
                }

                if ($html->blockexists('spotlight') ) {
                    Spotlight::parseSpotlight($html);
                }

                if ($html->blockexists('member_header')) {

                    $html->parse('member_header', false);
                }
                if ($html->blockexists('member_footer')) {
                    $html->parse('member_footer', false);
                }
            } else {
                $allowPage = array('index.php', 'join.php', 'join_facebook.php', 'forget_password.php');
                if (in_array($p, $allowPage)) {
                    $html->parse('visitor_head_custom', false);
                    $isHeaderCustom = false;
                }

                $blockHeaderMembers = 'header_members';
                // Common::parseMap
                if ($html->blockexists($blockHeaderMembers) && $p != 'index.php' && Common::isOPtionActive('header_users_module_enabled_urban') ) {
                    //$cityInfo = IP::geoInfoCity();
                    $cityInfo = getDemoCapitalCountry();
                    $sql = Common::sqlUsersNearCity($cityInfo, 5);
                    $rows=DB::rows($sql);
                    shuffle($rows);
                    $index = 1;
                    $blockHeaderMembersUser = $blockHeaderMembers . '_user';
                    foreach ($rows as $row) {
                        $photo = User::getPhotoDefault($row['user_id'], 's', false, $row['gender']);
                        $html->setvar($blockHeaderMembersUser . '_index', $index++);
                        $html->setvar($blockHeaderMembersUser . '_id', $row['user_id']);
                        $html->setvar($blockHeaderMembersUser . '_name', $row['name']);
                        $html->setvar($blockHeaderMembersUser . '_age', $row['age']);
                        $html->setvar($blockHeaderMembersUser . '_photo', $photo);
                        $html->setvar($blockHeaderMembersUser . '_url', User::url($row['user_id'], $row));
                        $html->parse($blockHeaderMembersUser);
                    }
                    if ($index > 1) {
                        $html->parse($blockHeaderMembers);
                    }
                }

                $html->parse('visitor_header', false);
                $html->parse('visitor_footer', false);
            }
            if ($isHeaderCustom) {
                $html->parse('member_head_custom', false);
            }
        }

        if (guid()) {
            $blockMemberFooterCustom = 'member_footer_custom';
            if ($html->blockexists($blockMemberFooterCustom)) {
                ProfileGift::parseGiftBox($html);
                if ($html->blockexists('pp_message_blank')) {
                    $html->parse('pp_message_blank');
                }
                $html->parse($blockMemberFooterCustom);
            }
        } else {

            $blockVisitorFooterCustom = 'visitor_footer_custom';
            if ($html->blockexists($blockVisitorFooterCustom)) {
                $html->parse($blockVisitorFooterCustom, false);
            }
        }


        /*
        $fields = array('i_am_here_to', 'interests');
        if ($optionTmplName == 'urban_mobile') {
            $fields = array('interests');
        }
        UserFields::parseFieldsStyle($html, $fields);
        */


        // URBAN

        // URBAN MOBILE
        if ($optionTmplName == 'urban_mobile') {
            /*$varUrlFrom = 'url_from';
            if ($html->varExists($varUrlFrom)) {
                $urlFrom = get_param($varUrlFrom, Common::refererFromSite());
                $html->setvar($varUrlFrom, $urlFrom);
            }*/

            $isPageStyle = true;
            $blockHeader = 'header_mobile';
            $blockHeaderStyle = $blockHeader . '_style';
            $blockHeaderScript = $blockHeader . '_script';

            $blockFooter = 'footer_mobile';
            if (guid()) {
                $blockHeaderScriptUser = $blockHeaderScript . '_user';
                if ($html->blockexists($blockHeaderScriptUser)) {
                    $html->parse($blockHeaderScriptUser, false);
                }

                $action = get_param('action');
                $cmd = get_param('cmd');

                $blockHeaderUser = $blockHeader . '_user';
                $userId = User::getRequestUserId('user_id');
                if ($html->blockexists($blockHeaderUser)) {
                    if (Common::isApp()) {
                        $html->parse('detect_3dcity', false);
                    }
                    $allowUserMenuOnPage = array('profile_view.php',
                                                 'search_results.php',
                                                 'profile_photo.php',
                                                 'users_viewed_me.php',
                                                 'mutual_attractions.php',
                                                 'messages.php',
                                                 'game_choose.php'
                                           );
                    $isBlockNumberMessages = false;
                    $isPageTitleUserName = false;
                    $displayParams = '';
                    if ($display != '') {
                        $displayParams = '?display=' . $display;
                        $html->setvar('display_params', $displayParams);
                    }

                    $pageTitle = l('page_title');
                    if ($p == 'search_results.php') {
                        if ($display != '') {
                            $pageTitle = l('page_' . $display);
                        }
                        if (!in_array($display, array('visitors', 'encounters', 'rate_people'))) {
                            $html->parse($blockHeaderUser . '_filter', false);
                        } else if (in_array($display, array('encounters', 'rate_people'))) {
                            $html->parse($blockHeaderUser . '_filter_and_report', false);
                        }
                    } elseif ($p == 'upgrade.php') {
                        if ($action == 'refill_credits') {
                            $pageTitle = lSetVars('page_refill_credits', array('credits' => $g_user['credits']));
                        } elseif ($action == 'payment_services') {
                            $pageTitle = l('a_paid_service');
                        }
                        if (get_param('type') == '') {
                            $allowUserMenuOnPage[] = 'upgrade.php';
                        }
                        //$isBlockNumberMessages = true;
                    }elseif ($p == 'profile_view.php') {
                        if ($userId) {
                            $from = get_param('from');
                            if ($display == 'profile_info') {
                                if ($userId == guid()) {
                                    $urlBack = 'profile_view.php';
                                    //$isBlockNumberMessages = true;
                                } else {
                                    $isPageTitleUserName = true;
                                    $urlBack = "profile_view.php?user_id={$userId}&from={$from}";
                                }
                            } else {
                                $urlBack = 'search_results.php';
                                if ($from == 'users_viewed_me') {
                                    $urlBack = 'users_viewed_me.php';
                                } elseif ($from == 'want_to_meet_you') {
                                    $urlBack = 'mutual_attractions.php?display=want_to_meet_you';
                                } elseif ($from == 'matches'){
                                    $urlBack = 'mutual_attractions.php';
                                } elseif ($from == 'encounters') {
                                    $urlBack = 'search_results.php?display=encounters';
                                }
                                if ($userId != guid()) {
                                    unset($allowUserMenuOnPage[0]);
									if ($p == 'profile_view.php') {
										$isFriendRequested = User::isFriendRequestExists($userId, $g_user['user_id']);
										if ($isFriendRequested && $isFriendRequested != $g_user['user_id']) {
											$html->parse('change_settings_events_show', false);
										}
									}
                                }

                                $html->parse($blockHeaderUser . '_settings_user', false);
                            }
                            $html->setvar('back', $urlBack);
                        }
                        if (!$display && (!$userId || $userId == $g_user['user_id'])) {
                            $html->parse('click_logo_cancel', false);
                        }
                    } elseif ($p == 'gifts_send.php') {
                        $isPageTitleUserName = true;
                    } elseif ($p == 'messages.php' && $display == 'one_chat') {
                        $isPageTitleUserName = true;
                        //$isBlockNumberMessages = true;
                    } elseif ($p == 'profile_settings.php') {
                        if ($display == '') {
                            $allowUserMenuOnPage[] = 'profile_settings.php';
                        }
                        //$isBlockNumberMessages = true;
                    } elseif (in_array($p, array('mutual_attractions.php', 'users_viewed_me.php'))) {
                        //$isBlockNumberMessages = true;
                        if ($display == 'want_to_meet_you') {
                            $pageTitle = l('page_want_to_meet_you');
                        }
                    } elseif (in_array($p, array('live_list_finished.php', 'live_list.php'))) {
                         $allowUserMenuOnPage[] = $p;
                    } elseif ($p == 'live_streaming.php') {
						$guid = guid();
						$clientId = User::getParamUid($guid, 'user_id');
						$isPresenter = intval($guid == $clientId);
						$userInfo = User::getInfoBasic($clientId);
						$pageTitle = '';
				        if ($userInfo) {
							if ($isPresenter) {
								$pageTitle = l('page_title_my');
							} else {
								$name = User::nameShort($userInfo['name']);
								$pageTitle = lSetVars('page_title', array('name' => $name));
							}
						}
                    }

                    /* Header name user */
                    if ($isPageTitleUserName) {
                        $name = User::getInfoBasic($userId, 'name');
                        $vars = array('name' => User::nameOneLetterShort($name),
                                      'url' => 'profile_view.php?user_id=' . $userId);
                        $pageTitle = Common::lSetLink('name_link', $vars, false);
                    }
                    /* Header name user */
                    $html->setvar('page_title', $pageTitle);
                    /* User menu */
                    if (in_array($p, $allowUserMenuOnPage)) {
                        $isParseBack = $userId;
                        if ($p == 'profile_view.php'
                            && (($display == 'profile_info' && $userId == guid()) || $display == '')) {
                            $isParseBack = 0;
                        }
                        $blockHeaderUserMyProfile = $blockHeaderUser . '_my_profile';
                        if ($p == 'messages.php') {
                            $isParseBack = 0;
                            if ($display == '') {
                                $html->parse($blockHeaderUserMyProfile . '_settings_messages', false);
                            }
                        }
                        /* Number messages */
                        $this->parseCounterNewMessagesMobileUrban($html, $allowUserMenuOnPage);
                        /* Number messages */
                        $html->cond($isParseBack, $blockHeaderUserMyProfile . '_back', $blockHeaderUserMyProfile . '_menu');
                        $html->parse($blockHeaderUserMyProfile, false);
                    /* User menu */
                    } else {
                        $blockHeaderUserPage = $blockHeaderUser . '_page';
                        /* Number messages -> make a separate method */
                        $this->parseCounterNewMessagesMobileUrban($html, $allowUserMenuOnPage, 'number_messages_page');
                        /* Number messages */
                        $pageBack = array('profile_settings.php' => 'profile_view.php',
                                          'upgrade.php' => 'profile_view.php',
                                          'email_not_confirmed.php' => 'profile_view.php',
                                          'join.php' => 'index.php',
                                          'profile_photo.php' => 'profile_view.php',
                                          'search.php' => 'search_results.php' . $displayParams,
                                          'gifts_send.php' => 'profile_view.php?user_id=' . $userId,
                                          'profile_personal_edit.php' => 'profile_view.php?display=profile_info&user_id=' . guid(),
                                          'profile_interests_edit.php' => 'profile_view.php?display=profile_info&user_id=' . guid());
                        if ($p == 'upgrade.php' && get_param('action') == 'payment_services') {
                            $type = get_param('type');
                            if ($type == 'gift') {
                                $pageBack['upgrade.php'] = 'gifts_send.php?user_id=' . get_param('user_to');
                            } elseif ($type == 'spotlight') {
                                $pageBack['upgrade.php'] = base64_decode(get_param('request_uri'));
                            }
                        }elseif ($p == 'profile_view.php' &&  $userId != guid()) {
                            $pageBack['profile_view.php'] = $urlBack;//'search_results.php';
                        }
                        $html->setvar('back', isset($pageBack[$p]) ? $pageBack[$p] : '');
                        $html->parse($blockHeaderUserPage, false);
                    }

                    $html->parse($blockHeaderUser, false);
                }


                $blockFooterUser = $blockFooter . '_user';
                if ($html->blockexists($blockFooterUser)) {
                    if ($html->blockExists('footer_verification_system_options')) {
                        $verifiedSystemsUser = User::getProfileVerificationData(User::getInfoBasic(guid()));
                        $verificationSystemsData = $verifiedSystemsUser['data'];
                        if($verificationSystemsData) {
                            $html->setvar('footer_verification_system_options', h_options($verificationSystemsData, ''));
                            $html->parse('footer_verification_system_options', false);
                        }
                    }

                    $allowPage = array('search.php',
                                       'upgrade.php',
                                       'profile_personal_edit.php',
                                       'profile_settings.php',
                                       'email_not_confirmed.php'
                        );
                    if (in_array($p, $allowPage)) {
                        $html->setvar('action', str_replace('.php', '', $p));
                        $btnPerformAction = l('btn_perform_action');
                        if ($p == 'profile_settings.php' && $display == 'delete') {
                            $btnPerformAction = l('btn_perform_action_continue');
                        } elseif ($p == 'profile_personal_edit.php') {
                            $btnPerformAction = l('save');
                        } elseif ($p == 'videochat.php') {
                            $btnPerformAction = l('not_available');
                        }
                        $html->setvar('btn_perform_action', $btnPerformAction);
                        $isParse = true;
                        if ($p == 'upgrade.php' && ((User::isSuperPowers() && $action == '') || !Common::isInAppPurchaseEnabled())) {
                            $isParse = false;
                        }
                        if ($isParse) {
                            $html->parse($blockFooterUser . '_action', false);
                        }
                    }elseif ($p == 'videochat.php') {
                        $html->parse($blockFooterUser . '_videochat', false);
                    }elseif ($p == 'gifts_send.php') {
                        if($html->blockexists($blockFooterUser . '_frm_gift_credits') && Common::isTransferCreditsEnabled()){
                            $html->setVar('is_gift_credits_class', 'two_fields');
                            $html->parse($blockFooterUser . '_frm_gift_credits', false);
                        } else {
                            $html->setVar('is_gift_credits_class', '');
                        }
                        $html->parse($blockFooterUser . '_frm_gift', false);
                    }elseif ($p == 'messages.php' && $display == 'one_chat') {
                        if (User::isOnline($userId, null, true) && Common::getOption('type_media_chat')=='webrtc') {
                            if(Common::isOptionActive('audiochat')){
                                $html->parse('btn_invite_audiochat', false);
                            }
                            if(Common::isOptionActive('videochat')){
                                $html->parse('btn_invite_videochat', false);
                            }
                            if(City::isActiveStreetChat()){
                                $html->parse('btn_invite_streetchat', false);
                            }
                            $html->parse('frm_message_media_js', false);
                        }
                        $html->parse($blockFooterUser . '_frm_message', false);
                    }

                    $html->parse($blockFooterUser, false);
                }
            } else {
                $blockHeaderVisitor = $blockHeader . '_visitor';
                $blockFooterVisitor = $blockFooter . '_visitor';
                if ($p == 'index.php') {

                    $blockHeaderStyleIndex = $blockHeaderStyle . '_index';
                    if ($html->blockexists($blockHeaderStyleIndex)) {
                        $isPageStyle = false;
                        $html->parse($blockHeaderStyleIndex, false);
                    }

                    $blockHeaderVisitorIndex = $blockHeaderVisitor . '_index';
                    if ($html->blockexists($blockHeaderVisitorIndex)) {
                        $html->parse($blockHeaderVisitorIndex, false);
                    }

                    $blockFooterVisitorIndex = $blockFooterVisitor . '_index';
                    if ($html->blockexists($blockFooterVisitorIndex)) {
                        Social::parse($html);
                        $html->parse($blockFooterVisitorIndex, false);
                    }
                } else {
                    $blockHeaderVisitorScript = $blockHeaderScript . '_visitor';
                    if ($html->blockexists($blockHeaderVisitorScript)) {
                        $html->parse($blockHeaderVisitorScript, false);
                    }
                    $cmd = get_param('cmd');
                    $nameVisitorFooter = '';
                    $idVisitorFooter = '';
                    $isParseBack = true;
                    if ($p == 'join.php' || $p == 'join_facebook.php') {
                        if ($cmd == 'please_login') {
                            $title = l('log_in');
                            $nameVisitorFooter = 'log_in';
                            $idVisitorFooter = 'log_in';
                        } else {
                            $title = l('register');
                            $nameVisitorFooter = 'register';
                            $idVisitorFooter = 'register';
                        }
                    } elseif ($p == 'info.php') {
                        $title = l('header_title');
                        $isParseBack = false;
                    } elseif ($p == 'forgot_password.php') {
                        $title = l('forgot_password');
                        $nameVisitorFooter = 'send_password';
                        $idVisitorFooter = 'forgot_password';
                    }

                    $blockHeaderVisitorPage = $blockHeaderVisitor . '_page';
                    if ($html->blockexists($blockHeaderVisitorPage)) {
                        $html->setvar($blockHeaderVisitorPage . '_title', $title);
                        $html->setvar($blockHeaderVisitorPage . '_back', $title);
                        if ($isParseBack) {
                            $html->parse($blockHeaderVisitorPage . '_back', false);
                        }
                        $html->parse($blockHeaderVisitorPage, false);
                    }
                    $blockFooterVisitorPage = $blockFooterVisitor . '_page';
                    if ($html->blockexists($blockFooterVisitorPage) && $idVisitorFooter && $nameVisitorFooter) {
                        $html->setvar($blockFooterVisitorPage . '_id', $idVisitorFooter);
                        $html->setvar($blockFooterVisitorPage . '_name', l($nameVisitorFooter));
                        $html->parse($blockFooterVisitorPage, false);
                    }
                }
                if ($html->blockexists($blockHeaderVisitor)) {
                    $html->parse($blockHeaderVisitor, false);
                }
                if ($html->blockexists($blockFooterVisitor)) {
                    $html->parse($blockFooterVisitor, false);
                }
            }

            if ($isPageStyle) {
                $blockPageStyle = $blockHeaderStyle . '_page';
                if ($html->blockexists($blockPageStyle)) {
                    $html->parse($blockPageStyle, false);
                }
            }

        }
        // URBAN MOBILE

        /*$isMobileModuleParsed = false;
        $blockMobileApp = 'mobile_app_ios';
        if($html->blockexists($blockMobileApp) && isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'IOSWebview')) {
            $html->parse($blockMobileApp);
            $isMobileModuleParsed = true;
        }

        $blockMobileApp = 'mobile_app_android';
        if($html->blockexists($blockMobileApp) && isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'AppWebview')) {
            $html->parse($blockMobileApp);
            $isMobileModuleParsed = true;
        }

        if($isMobileModuleParsed) {
            $html->setvar('city_last_msg_id', City::lastMsgId());
            $block = 'mobile_app';
            $html->setvar("{$block}_push_notifications", intval(guser('set_notif_push_notifications') == 1));
            $html->parse($block);
        }

        $valAppVibrationDuration = 'app_vibration_duration';
        if($html->varExists($valAppVibrationDuration)) {
            $html->setvar($valAppVibrationDuration, Common::getOption('app_vibration_duration'));
        }*/

        self::parseApp($html);

        if ($html->varExists('is_player_native')) {
            $html->setvar('is_player_native', intval(Common::getOption('video_player_type') == 'player_native'));
        }

		if ($html->varExists('is_super_powers')) {
			$html->setvar('is_super_powers', User::isSuperPowers());
		}

		if ($html->varExists('paid_access_mode')) {
			$html->setvar('paid_access_mode', Common::getOption('paid_access_mode'));
		}

		if ($html->varExists('google_maps_api_key_for_city')) {
			$apiKey = trim(Common::getOption('google_maps_api_key', '3d_city_whole_world'));
			if ($apiKey) {
				$html->setvar('google_maps_api_key_for_city', '?key=' . $apiKey);
			}
		}

        if ($html->varExists('url_login_page')) {
            $html->setvar('url_login_page', Common::getLoginPage());
        }

        if ($html->varExists('url_home_page')) {
            $html->setvar('url_home_page', Common::getHomePage());
        }

        $block = 'watch_geo_position';
        if (guid()) {
            if ($html->blockExists($block) && Common::isOptionActive('gps_enabled')) {
                $timeout = Common::getOptionInt('watch_geo_position_time');
                if ($timeout <=0 ) {
                    $timeout = 60;
                }
                $html->setvar('watch_geo_position_time', $timeout);
                $html->parse($block, false);
            }
            if ($html->varExists('geo_position_data')) {
                $html->setvar('geo_position_data', User::getGeoPositionData());
            }
        }

        $parseTemplateMethod = 'parseBlock' . $optionTmplName;
        if (method_exists('CHeader', $parseTemplateMethod)) {
            $this->$parseTemplateMethod($html);
        }

        if($html->blockExists('facebook_invite')) {
            $sql = 'SELECT `status`
                FROM `pages`
                WHERE `menu_title` = "column_narrow_invite"';
            if(DB::result($sql)) {
                $html->setvar('facebook_appid', Common::getOption('facebook_appid'));
                $html->parse('facebook_invite');
            }
        }

        $blockAlertPageLoaded = 'alert_after_page_loaded';
        if ($html->blockExists($blockAlertPageLoaded) && $showAlert = get_session($blockAlertPageLoaded)) {
            $html->setvar("{$blockAlertPageLoaded}_msg", toJsL($showAlert));
            $titleKey = 'alert_html_alert';
            $titleDone = array('your_new_page_has_been_created', 'your_new_group_has_been_created',
                               'your_page_has_been_updated', 'your_group_has_been_updated');
            if (in_array($showAlert, $titleDone)) {
                $titleKey = 'alert_html_done';
            }
            $html->setvar("{$blockAlertPageLoaded}_title", toJsL($titleKey));
            $html->parse($blockAlertPageLoaded, false);
            delses($blockAlertPageLoaded);
        }

        if(IS_DEMO && !guid()) {
            $html->setvar('login_user', demoLogin());
            $html->setvar('login_password', '1234567');
            $html->parse('demo');
        }

        if (Common::isOptionActiveTemplate('include_template_class')) {
            $classTemplate = 'Template' . $optionTmplName;
            if (class_exists($classTemplate, true) && method_exists($classTemplate, 'headerParseBlock')) {
                $classTemplate::headerParseBlock($html);
            }
        }

        if ($html->varExists('body_class')) {
            $html->setvar('body_class', guid() ? 'member' : 'visitor');
        }

        if ($html->varExists('icon_pwa_url')) {
            $html->setvar('icon_pwa_url', PWA::getUrlIcon());
        }

        if(Common::getOption('ssl_seal_html', 'main') && $html->blockExists('ssl_seal') && !Common::isApp()) {
            $html->parse('ssl_seal');
        }

        parent::parseBlock($html);
    }

}