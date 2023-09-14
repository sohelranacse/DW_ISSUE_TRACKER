<?php
/* (C) Websplosion LLC, 2001-2021

IMPORTANT: This is a commercial software product
and any kind of using it must agree to the Websplosion's license agreement.
It can be found at http://www.chameleonsocial.com/license.doc

This notice may not be removed from the source code. */

Class Common {

    const ALLOWTAGS = '<b><i><u><s><strike><strong><em>';

    static $menuItemUrl = array(
            'gallery' => 'gallery_index.php',
            'users' => 'users_online.php',
            'profile' => 'profile_view.php',
            'rating' => 'users_hon.php',
            'my' => 'my_friends.php',
            'partner' => 'partner/',
        );

    static $urlHomePage = false;

    static $menuItemAuthOnly = array(
        'home',
        'wall',
        'games',
        'city',
        'flashchat',
        'mail',
        'chat',
        'profile',
        'my',
        'upgrade',
    );

    static $urlAutoMail = array(
        'join' => 'profile_view.php',
        'profile_approved' => 'profile_view.php',
        'confirm_email' => 'confirm_email.php?hash={hash}',
        'change_email' => 'confirm_email.php?hash={hash}',
        'forget' => 'join.php?cmd=forgot',
        'forget_link' => '{code_link}',
        'mail_message' => 'mail.php?display=text&mid={mid}',
        'interest' => 'mail_whos_interest.php?display=profile&uid={uid}',
        'interest_urban' => 'search_results.php?display=profile&uid={uid}',
        'invite' => 'invite_confirm.php?id={id}&key={key}',
        'invite_group' => 'groups_invite.php?cmd=join&group_id={group_id}&key={key}',
        'invite_group_site' => 'groups_invite.php?cmd=join&group_id={group_id}&key={key}',
        'friend_added' => 'search_results.php?display=profile&uid={uid}',
        'wall_alert_message' => 'wall.php?uid={uid}&item={item}',
        'wall_alert_message_urban' => 'search_results.php?display=profile&uid={uid}&show=wall&wall_item={item}',
        'wall_alert_like' => 'wall.php?uid={uid}&item={item}',
        'wall_alert_like_urban' => 'wall.php?wall_item={item}',
        'wall_alert_comment' => 'wall.php?uid={uid}&item={item}',
        'match_mail' => 'join.php',
        'end_paid' => 'upgrade.php',
        'admin_delete' => 'index.php',
        'partner_join' => 'index.php',
        'join_admin' => 'search_results.php?display=profile&uid={uid}',
        'partner_forget' => 'index.php',
        'partner_delete' => 'partner/index.php',
        'friend_request' => 'my_friends.php?show=requests&from={uid}',
        'contact' => 'administration/contact.php',
        'partner_contact' => 'administration/contact_partner.php',
        'profile_visitors' => 'search_results.php?display=profile&uid={uid}',
        'want_to_meet_you' => 'search_results.php?display=encounters&uid={uid_sender}',
        'mutual_attraction' => 'mutual_attractions.php',
        'gift' => 'profile_view.php',
        'voted_photo' => 'profile_view.php?show=gallery&photo_id={photo_id}',
        'new_message' => 'search_results.php?display=profile&uid={uid_sender}&show=messages',
        'new_comment_photo' => 'profile_view.php?show=gallery&photo_id={id}',
        'new_comment_video' => 'profile_view.php?show=video_gallery&video_id={id}',
        'welcoming_message' => 'search_results.php?display=profile&uid={user_id}',
        'approve_image_admin' => 'administration/users_photo.php',
        'approve_video_admin' => 'administration/users_video.php',
        'approve_text_admin' => 'administration/users_text.php',
        'partner_join_admin' => 'administration/partner_edit.php?id={user_id}',

        'photo_approved' => 'profile_view.php',
        'photo_declined' => 'profile_view.php',

        'video_approved' => 'profile_view.php',
        'video_declined' => 'profile_view.php',

        'text_approved' => 'profile_view.php',
        'text_declined' => 'profile.php',
        'like_comment_photo' => 'photos_list.php?uid={uid_gallery}&show=gallery&photo_id={id}&cid={cid}',
        'like_comment_video' => 'vids_list.php?uid={uid_gallery}&show=gallery&video_id={id}&cid={cid}'
    );

    static $urlAutoMailTemplate = array(
        'impact' => array(
            'new_message' => 'messages.php?display=one_chat&user_id={uid_sender}'
        ),
        'impact_mobile' => array(
            'new_message' => 'messages.php?display=one_chat&user_id={uid_sender}'
        )
    );

    static $urlAutoMailByCurrentLocation = array(
        'join',
        'confirm_email',
        'change_email',
        'forget',
        'forget_link',
        'partner_join',
        'partner_forget',
    );

    static $urlAutoMailAutologinOff = array(
        'confirm_email',
        'change_email',
        'forget',
        'forget_link',
        'admin_delete',
        'partner_join',
        'join_admin',
        'partner_forget',
        'partner_delete',
        'contact',
        'partner_contact',
        'approve_image_admin',
        'approve_video_admin',
        'approve_text_admin',
        'partner_join_admin',
    );

    static $uaHttpHeaders = array(
        // The default User-Agent string.
        'HTTP_USER_AGENT',
        // Header can occur on devices using Opera Mini.
        'HTTP_X_OPERAMINI_PHONE_UA',
        // Vodafone specific header: http://www.seoprinciple.com/mobile-web-community-still-angry-at-vodafone/24/
        'HTTP_X_DEVICE_USER_AGENT',
        'HTTP_X_ORIGINAL_USER_AGENT',
        'HTTP_X_SKYFIRE_PHONE',
        'HTTP_X_BOLT_PHONE_UA',
        'HTTP_DEVICE_STOCK_UA',
        'HTTP_X_UCBROWSER_DEVICE_UA'
    );

    static private $classLinks = '';
    static private $targetLinks = '_blank';
    static private $prepareLinks = array();
    static private $setTarget = false;
    static private $iterationLinks = 1;
    static public $pagerUrl = '';
    static public $lPleaseChoose = '';


    static function getUrlHomePage()
    {
        return self::$urlHomePage;
    }

    static function setUrlHomePage($urlHomePage)
    {
        self::$urlHomePage = $urlHomePage;
    }

    static function getMenuItemUrl($menu)
    {
        return isset(self::$menuItemUrl[$menu]) ? self::$menuItemUrl[$menu] : $menu . '.php';
    }

    static function getLocationTitle($type, $id, $index = 0)
    {
        $types = array(
            'city',
            'state',
            'country',
        );
        $title = '';
        $type = to_sql($type, 'Plain');

        if (in_array($type, $types)) {
            $sql = 'SELECT ' . $type . '_title
                FROM geo_' . $type . '
                WHERE ' . $type . '_id = ' . to_sql($id, 'Number');
            $result = DB::result($sql, 0, $index);
            if($result !== 0) {
                $title = $result;
            }
        }

        return $title;
    }

    static function filterProfileText($text, $isTags = false, $removeTags = true)
    {
        global $g;

        if($removeTags) {
            $text = strip_tags($text);
        }

        if ($g['options']['filter'] == 'Y') {
            $text = to_profile($text, '', '', $isTags);
        }

        return $text;
    }

    static function replaceByVars($text, $vars, $delimiters = array('{', '}'), $case = false)
    {

        if (is_array($vars) && count($vars)) {

            $search = array();
            $replace = array();

            if (is_array($delimiters) && count($delimiters) > 0) {
                if (!isset($delimiters[1])) {
                    $delimiters[1] = $delimiters[0];
                }
            } else {
                $delimiters = array('', '');
            }

            foreach ($vars as $key => $value) {
                $search[] = $delimiters[0] . $key . $delimiters[1];
                $replace[] = $value;
            }

            if($case) {
                $text = str_replace($search, $replace, $text);
            } else {
                $text = str_ireplace($search, $replace, $text);
            }
        }

        return $text;
    }

    static function automailInfo($type, $lang, $dbIndex = DB_MAX_INDEX)
    {
        $emailAuto = false;

        $sql = 'SELECT * FROM email_auto
            WHERE note = ' . to_sql($type, 'Text')
                . 'AND lang = ' . to_sql($lang);
        $emailAuto = DB::row($sql, $dbIndex);
        if(!$emailAuto) {
            $sql = 'SELECT * FROM email_auto
                WHERE note = ' . to_sql($type, 'Text')
                    . 'AND lang = "default"';
            $emailAuto = DB::row($sql, $dbIndex);
        }

        $sql = 'SELECT `value` FROM `email_auto_settings`
                 WHERE `option` = "template"';
        $emailAuto['template'] = DB::result($sql, 0, $dbIndex);

        $sql = 'SELECT `value` FROM `email_auto_settings`
                 WHERE `option` = ' . to_sql($lang);
        $emailAuto['thanks'] = DB::result($sql, 0, $dbIndex);
        if(!$emailAuto['thanks']) {
            $sql = 'SELECT `value` FROM `email_auto_settings`
                     WHERE `option` = "default"';
            $emailAuto['thanks'] = DB::result($sql, 0, $dbIndex);
        }

        return $emailAuto;
    }

    static function prepareUrlAutoMail($type, &$vars)
    {
        if ($type == 'profile_visitors' && isset($vars['to_user_id']) && $vars['to_user_id']) {
            $userInfo = User::getInfoBasic($vars['to_user_id']);
            if (!User::accessCheckFeatureSuperPowers('profile_visitors_paid', $userInfo['gold_days'], $userInfo['orientation'])) {
                self::$urlAutoMail['profile_visitors'] = Common::pageUrl('users_viewed_me');
            }
        }
        $optionTmplName = Common::getOption('name', 'template_options');
        if (Common::isOptionActiveTemplate('is_prepare_url_auto_mail')) {
            if (!isset(self::$urlAutoMailTemplate[$optionTmplName])) {
                self::$urlAutoMailTemplate[$optionTmplName] = array();
            }
            if ($optionTmplName == 'edge') {
                if ($type == 'new_message') {
                    self::$urlAutoMailTemplate[$optionTmplName]['new_message'] = User::url($vars['uid'], null, array('show' => 'message', 'uid_sender' => $vars['uid_sender'], 'group_id_sender' => $vars['group_id_sender']));
                } elseif ($type == 'friend_request') {
                    self::$urlAutoMailTemplate[$optionTmplName]['friend_request'] = User::url($vars['uid_send'], null, array('show' => 'friend_request'));
                } elseif ($type == 'friend_added') {
                    self::$urlAutoMailTemplate[$optionTmplName]['friend_added'] = User::url($vars['uid']);
                } elseif ($type == 'new_comment_photo') {
                    self::$urlAutoMailTemplate[$optionTmplName]['new_comment_photo'] = self::pageUrl('user_photos_list', $vars['to_user_id'], null, array('show' => 'gallery', 'photo_id' => $vars['id'], 'cid' => $vars['cid']));
                } elseif ($type == 'new_comment_video') {
                    self::$urlAutoMailTemplate[$optionTmplName]['new_comment_video'] = self::pageUrl('user_vids_list', $vars['to_user_id'], null, array('show' => 'video_gallery', 'video_id' => $vars['id'], 'cid' => $vars['cid']));
                } elseif ($type == 'wall_alert_like' || $type == 'wall_alert_message') {
                    self::$urlAutoMailTemplate[$optionTmplName][$type] = self::pageUrl('wall') . '?uid={uid}&item={item}';
                } elseif ($type == 'wall_alert_comment') {
                    self::$urlAutoMailTemplate[$optionTmplName]['wall_alert_comment'] = self::pageUrl('wall') . '?uid={uid}&item={item}';
                    if (isset($vars['cid']) && $vars['cid']) {
                        self::$urlAutoMailTemplate[$optionTmplName]['wall_alert_comment'] .= '&ncid={cid}';
                    }
                } elseif ($type == 'group_subscribe_new') {
                    self::$urlAutoMailTemplate[$optionTmplName]['group_subscribe_new'] = User::url($vars['uid']);
                } elseif ($type == 'group_subscribe_request') {
                    self::$urlAutoMailTemplate[$optionTmplName]['group_subscribe_request'] = User::url($vars['uid'], null, array('show' => 'friend_request'));
                }

            }
        }



        if ($type == 'profile_visitors'  && isset($vars['to_user_id']) && $vars['to_user_id']) {
            $isPaidVisitors = Common::isActiveFeatureSuperPowers('profile_visitors_paid');
            if($isPaidVisitors) {
                $userInfo = User::getInfoBasic($vars['to_user_id']);
                if ($userInfo && !User::accessCheckFeatureSuperPowers('profile_visitors_paid', $userInfo['gold_days'], $userInfo['orientation'], $isPaidVisitors)) {
                    $vars['name'] = l('mail_profile_visitors');
                    self::$urlAutoMail['profile_visitors'] = Common::pageUrl('users_viewed_me');
                }
            }
        }


    }

    static function sendAutomail($lang, $email, $type, $vars = array(), $emailAuto = false, $dbIndex = DB_MAX_INDEX, $isGetData = false)
    {
        global $g;
        global $sitePart;

        $sent = false;

        if(!Common::isOptionActive('mail') && $type == 'mail_message') {
            return false;
        }
        //Common::getOption('info_mail', 'main') 'partner_delete' 'partner_forget' 'partner_join' 'invite_group' 'invite'
        if(mb_strpos($type, 'partner', 0, 'UTF-8') === false
            && !in_array($type, array('invite_group', 'invite'))
            && $email != Common::getOption('info_mail', 'main')
            && User::getInfoBasicByEmail($email, 'use_as_online', $dbIndex)){
            return false;
        }

        if(Common::isOptionActive('send_emails_only_to_confirmed_emails')
            && $type != 'confirm_email'
            && $type != 'change_email'
            && $type != 'forget'
            && $type != 'forget_link'
            && User::getInfoBasicByEmail($email, 'active_code', $dbIndex)) {
            return false;
        }

        if(!$emailAuto) {
            $emailAuto = self::automailInfo($type, $lang, $dbIndex);
        }
        $data = array('subject' => '', 'text' => '');
        if ($emailAuto) {
            $subject = $emailAuto['subject'];
            $text = $emailAuto['text'];
            $data['subject'] = $subject;
            $data['text'] = $text;

            $vars['to_user_name'] = User::getInfoBasicByEmail($email, 'name', $dbIndex);
            $vars['to_user_id'] = User::getInfoBasicByEmail($email, 'user_id', $dbIndex);
            $vars['from_user_name'] = guser('name');
            $vars['from_user_id'] = guser('user_id');

            if(Common::isValidArray($vars)) {
                $subject = self::replaceByVars($subject, $vars);
                $data['subject'] = $subject;

                if (strip_tags($text) == $text) {
                    $text = nl2br($text);
                }
                if (isset($vars['text']) &&
                    (strip_tags($vars['text']) == $vars['text'])) {
                    $vars['text'] = nl2br($vars['text']);
                }

                self::prepareUrlAutoMail($type, $vars);

                $text = self::replaceByVars($text, $vars);
                /*if (strip_tags($text) == $text) {
                    $text = nl2br($text);
                }*/
                $data['text'] = $text;
                //$data['header']  = $emailAuto['header'];
                $data['header']  = self::replaceByVars($emailAuto['header'], $vars);
                $data['button']  = $emailAuto['button'];

                $autoMailTmpl = $type . '_' . Common::getOption('set', 'template_options');
                $optionTmplName = Common::getOption('name', 'template_options');
                if (isset(self::$urlAutoMailTemplate[$optionTmplName])
                        && isset(self::$urlAutoMailTemplate[$optionTmplName][$type])) {
                    $urlAutoMail = self::$urlAutoMailTemplate[$optionTmplName][$type];
                } else {
                    $urlAutoMail = isset(self::$urlAutoMail[$autoMailTmpl]) ? self::$urlAutoMail[$autoMailTmpl] : self::$urlAutoMail[$type];
                }

                if($type == 'forget' && $sitePart == 'administration') {
                    $urlAutoMail = 'index.php';
                }

                if(in_array($type, self::$urlAutoMailByCurrentLocation)) {
                    $data['url'] = Common::urlSite() . self::replaceByVars($urlAutoMail, $vars);
                } else {
                    $data['url'] = Common::urlSiteSubfolders() . self::replaceByVars($urlAutoMail, $vars);
                }

                if(!in_array($type, self::$urlAutoMailAutologinOff)) {
                    $receiverBasicInfo = User::getInfoBasicByEmail($email, false, $dbIndex);
                    if($receiverBasicInfo) {
                        $data['url'] = User::urlAddAutologin($data['url'], $receiverBasicInfo);
                    }
                }

                //$urlTmpl = str_replace($g['path']['url_main'], '', $g['tmpl']['url_tmpl_administration']);
                //$data['url_admin'] = Common::urlSiteSubfolders() . $urlTmpl;
                $data['url_logo_auto_mail'] = Common::getUrlLogoAutoMail();
                $data['thanks'] = self::replaceByVars($emailAuto['thanks'], array('title' => Common::getOption('title', 'main')));
                $text = self::replaceByVars($emailAuto['template'], $data);
            }
            if (!$isGetData) {
                send_mail($email, Common::getOption('info_mail', 'main'), $subject, $text);
                $sent = true;
                }
            }

        return $isGetData ? $data : $sent;
    }

    static function isEnabledAutoMail($type){
        global $g;

        $enabled = true;
        if (isset($g['automail'][$type])) {
            $enabled = false;
            if ($g['automail'][$type] == 'Y') {
                $enabled = true;
            }
        }
        return $enabled;
    }


    static function dirCopy($src, $dst, $chmod = 0777) {
        $dir = opendir($src);
        @mkdir($dst);
        @chmod($dst, $chmod);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    self::dirCopy($src . '/' . $file,$dst . '/' . $file);
                } else {
                    $filePath = $dst . '/' . $file;
                    copy($src . '/' . $file, $filePath);
                    @chmod($filePath, $chmod);
                }
            }
        }
        closedir($dir);
    }

    static function dirRemove($dir) {
        if(file_exists($dir)) {
            $files = array_diff(scandir($dir), array('.', '..'));
             foreach ($files as $file) {
                 $path = $dir . DIRECTORY_SEPARATOR . $file;
                 (is_dir($path)) ? self::dirRemove($path) : unlink($path);
             }
             return rmdir($dir);
        }
    }

    static function getHomePage()
    {
        return Menu::getUrlHomePage();
    }

    static function toHomePage()
    {
        $link = get_session('ref_login_link');
        $link_mobile = get_session('ref_login_link_mobile');
        $link_admin = get_session('ref_login_link_admin');
        $link_partner = get_session('ref_login_link_partner');
        $ref_link = self::getHomePage();

        if (Common::getOption('site_part', 'main') == 'mobile') {
            if ($link_mobile != '') {
                delses('ref_login_link_mobile');
                $ref_link = $link_mobile;
            }
        } elseif (Common::getOption('site_part', 'main') == 'administration') {
            if ($link_admin != '') {
                delses('ref_login_link_admin');
                $ref_link = $link_admin;
            }
        } elseif (Common::getOption('site_part', 'main') == 'partner') {
            if ($link_partner != '') {
                delses('ref_login_link_partner');
                $ref_link = $link_partner;
            }
        } else {
            if ($link != '') {
                delses('ref_login_link');
                $ref_link = $link;
            }
        }

        if (mb_strpos($ref_link, '_ajax.php') != false || strpos($ref_link, 'ajax.php') || strpos($ref_link, '&ajax=1') || strpos($ref_link, '?ajax=1')) {
            $ref_link = self::getHomePage();
        }

        redirect($ref_link);
    }

   static function getLoginPage()
    {
        if (Common::getOption('site_part', 'main') == 'mobile'
            || Common::getOption('site_part', 'main') == 'administration'
            || Common::getOption('site_part', 'main') == 'partner') {
            $page = 'index.php';
        } else {
            $page = Common::pageUrl('login');
        }

        global $g;
        return $g['to_root'] . $page;
    }

    static function toLoginPage()
    {
        global $p;

        if($p != 'ajax.php' && $p != 'js.php' && $p != 'css.php' && !strpos($p, '_ajax.php') && !get_param('ajax') && $p != 'manifest.php') {
            $link = get_session('ref_login_link');
            $link_mobile = get_session('ref_login_link_mobile');
            $link_admin = get_session('ref_login_link_admin');
            $link_partner = get_session('ref_login_link_partner');

            if (Common::getOption('site_part', 'main') == 'mobile') {
                if ($link != '') {
                    delses('ref_login_link');
                }
                set_session('ref_login_link_mobile', self::urlPage(true));
             } elseif (Common::getOption('site_part', 'main') == 'administration') {
                if ($link_admin != '') {
                    delses('ref_login_link_admin');
                }
                set_session('ref_login_link_admin', self::urlPage(true));
            } elseif (Common::getOption('site_part', 'main') == 'partner') {
                if ($link_partner != '') {
                    delses('ref_login_link_partner');
                }
                set_session('ref_login_link_partner', self::urlPage(true));
            } else {
                if ($link_mobile != '') {
                    delses('ref_login_link_mobile');
                }
                set_session('ref_login_link', self::urlPage(true));
            }
        }

        redirect(self::getLoginPage());
    }

    static function listMonths($prefix = '')
    {
        return array(
			1  => l($prefix . 'january'),
			2  => l($prefix . 'february'),
			3  => l($prefix . 'march'),
			4  => l($prefix . 'april'),
			5  => l($prefix . 'may'),
			6  => l($prefix . 'june'),
			7  => l($prefix . 'july'),
			8  => l($prefix . 'august'),
			9  => l($prefix . 'september'),
			10 => l($prefix . 'october'),
			11 => l($prefix . 'november'),
			12 => l($prefix . 'december'),
		);
    }

    static function plListMonths($format = 'F', $isFirstValueEmpty = false)
    {
        $list = array();
        if ($isFirstValueEmpty) {
            $list[0] = l('please_choose_empty');
        }
        for ($i = 1; $i < 13; $i++) {
            $m = $i;
            if ($m < 10) {
                $m = "0{$m}";
            }
            $list[$i] = pl_date($format, "2000{$m}01", true, false, true);
        }
        return $list;
    }

    static function listGroupAdmin($selected, $firstDefault = false, $list = false, $isFirstValueEmpty = false, $setAdminDefault = true) {
        $groupAdmin = '';
        
        $where = 'role ="group_admin"';
        $sql = 'SELECT `user_id`, `name`
                  FROM `user`
                 WHERE ' . $where . '
              ORDER BY `name` ASC';

        $groupAdmin .= ($list) ? DB::db_options_ul($sql, $selected) : DB::db_options($sql, $selected, 0, false, $isFirstValueEmpty, 'user');

        $where .= ' AND `user_id` = ' .  to_sql($selected, 'Number');
        if (DB::count('user', $where)) {
            $selected = '';
        }

        $sql = 'SELECT `user_id`, `name`
                  FROM `user`
              WHERE (user_id = ' . to_sql($selected, 'Number') . ')';

        if($isFirstValueEmpty && $groupAdmin !== '') {
            $isFirstValueEmpty = false;
        }

        $groupAdmin .= ($list) ? DB::db_options_ul($sql, $selected, 0, true) : DB::db_options($sql, $selected, 0, true, $isFirstValueEmpty, 'user');

        return $groupAdmin;
    }

    static function listCountries($selected, $firstDefault = false, $list = false, $isFirstValueEmpty = false, $setCountryDefault = true)
    {
        $countries = '';

        if ($setCountryDefault && guid() && $selected == '') {
            $sql = "SELECT `country_id`
                      FROM `geo_country`
                  WHERE hidden = 0
                  ORDER BY `first` DESC, `country_title` ASC LIMIT 1";
            $selected = DB::result($sql);
        }

        $where = '`first` != 0 AND hidden = 0';

        $sql = 'SELECT `country_id`, `country_title`
                  FROM `geo_country`
                 WHERE ' . $where . '
              ORDER BY `first` DESC, `country_title` ASC';

        $countries .= ($list) ? DB::db_options_ul($sql, $selected) : DB::db_options($sql, $selected, 0, false, $isFirstValueEmpty, 'country');

        $where .= ' AND `country_id` = ' .  to_sql($selected, 'Number');
        if (DB::count('geo_country', $where)) {
            $selected = '';
        }

        $sql = 'SELECT `country_id`, `country_title`
                  FROM `geo_country`
              WHERE (hidden = 0 OR country_id = ' . to_sql($selected, 'Number') . ')';

        if($isFirstValueEmpty && $countries !== '') {
            $isFirstValueEmpty = false;
        }

        $countries .= ($list) ? DB::db_options_ul($sql, $selected, 0, true) : DB::db_options($sql, $selected, 0, true, $isFirstValueEmpty, 'country');

        return $countries;
    }

    static function listStates($country, $selected = '', $list = false, $isFirstValueEmpty = false)
    {
        $sql = 'SELECT state_id, state_title FROM geo_state
            WHERE country_id = ' . to_sql($country, 'Number') . '
                AND (hidden = 0 OR state_id = ' . to_sql($selected, 'Number') . ')';
        $states = ($list) ? DB::db_options_ul($sql, $selected, 0, true) : DB::db_options($sql, $selected, 0, true, $isFirstValueEmpty);
        return $states;
    }

    static function listCities($state, $selected = '', $list = false, $isFirstValueEmpty = false)
    {
        $sql = 'SELECT city_id, city_title FROM geo_city
            WHERE state_id = ' . to_sql($state, 'Number') . '
                AND (hidden = 0 OR city_id = ' . to_sql($selected, 'Number') . ')';
        $cities = ($list) ? DB::db_options_ul($sql, $selected, 0, true) : DB::db_options($sql, $selected, 0, true, $isFirstValueEmpty);
        return $cities;
    }

    static function setSiteOptions()
    {
        global $g;
        global $p;
        global $l;

        if (get_param('bg') != ''
             && ($p != 'send.php' &&  $p != 'postcard_preview.php')) {
            $g['options']['website_background_oryx'] = get_param('bg') . '.jpg';
            $g['options']['background_only_not_logged_oryx'] = 'N';
            set_session('set_bg', 'Y');
        }
        if (get_param('color_scheme') != '') {
            $g['options']['allow_users_color_scheme'] = 'Y';
            set_session('set_color', 'Y');
        }
        if (get_param('maintenance_off') == 'Y') {
            set_session('maintenance_off', 'Y');
        }
        if (get_param('maintenance_on') == 'Y') {
            set_session('maintenance_off', 'N');
        }

        if (get_session('maintenance_off') == 'Y') {
            $g['options']['maintenance'] = "N";
        }


        $homePageModeParam = 'home_page_mode';
        $homePageMode = get_param($homePageModeParam, '');
        if($homePageMode != '') {
            set_session($homePageModeParam, $homePageMode);
            set_session('demo_widgets', false);
            $sql = 'DELETE FROM widgets
                WHERE user_id = ' . to_sql(get_session('user_id'), 'Number') . '
                    AND session = ' . to_sql(session_id(), 'Text');
            DB::execute($sql);
        }
        $homePageModeSession = get_session($homePageModeParam);
        if($homePageModeSession != '') {
            $g['options'][$homePageModeParam] = $homePageModeSession;
        }

        $mainPageModeParam = 'main_page_mode';
        $mainPageMode = get_param($mainPageModeParam, '');
        if($mainPageMode != '') {
            set_session($mainPageModeParam, $mainPageMode);
        }
        $mainPageModeSession = get_session($mainPageModeParam);
        if($mainPageModeSession != '') {
            $g['options'][$mainPageModeParam] = $mainPageModeSession;
        }

        $mainSectionModeParam = 'main_section';
        $mainSectionMode = get_param($mainSectionModeParam, '');
        if($mainSectionMode != '') {
            set_session($mainSectionModeParam, $mainSectionMode);
        }

        $partner = intval(get_param('p', ''));
		if ($partner != 0) {
			set_session('partner', $partner);
		}

        $webrtcOnOff = get_param('webrtc','');
        if($webrtcOnOff=='on'){
            $typeChat = 'webrtc';
            Config::update('options','type_media_chat',$typeChat,true);
        } elseif($webrtcOnOff=='off'){
            $typeChat = 'flash';
            Config::update('options','type_media_chat',$typeChat,true);
        }

        $widgetsModeParam = 'widgets';
        $widgetsMode = get_param($widgetsModeParam, '');
        if($widgetsMode == 'on') {
            $sql = 'DELETE FROM widgets
                WHERE user_id = ' . to_sql(get_session('user_id'), 'Number') . '
                    AND session = ' . to_sql(session_id(), 'Text');
            DB::execute($sql);
            set_session('demo_widgets', false);
        }
        if($widgetsMode == 'off') {
            $sql = 'DELETE FROM widgets
                WHERE user_id = ' . to_sql(get_session('user_id'), 'Number') . '
                    AND session = ' . to_sql(session_id(), 'Text');
            DB::execute($sql);
            set_session('demo_widgets', true);
        }

        // demo widgets
        // clean all old
        // disable demo widgets
        // reset widgets status
        // home page and social widgets are different things

        $demoPagesDisable = self::paramFromSession('demo_pages_disable');
        if($demoPagesDisable != '' && $demoPagesDisable != 'off') {
            $pages = explode(',', $demoPagesDisable);
            foreach($pages as $page) {
                if(isset($g['menu'][$page])) {
                    unset($g['menu'][$page]);
                }
            }
        }

        $demoPages = self::paramFromSession('demo_pages');
        if($demoPages != '' && $demoPages != 'off') {
            $pages = array_flip(explode(',', $demoPages));

            foreach($g['menu'] as $key => $value) {
                if(!isset($pages[$key])) {
                    unset($g['menu'][$key]);
                } else {
                    $g['menu'][$key] = $pages[$key];
                }
            }
        }

        $demoOptionsOff = self::paramFromSession('demo_options_off');
        if($demoOptionsOff != '' && $demoOptionsOff != 'off') {
            $optionsOff = explode(',', $demoOptionsOff);
            foreach($optionsOff as $optionOff) {
                Common::optionDeactivate($optionOff);
            }
        }

        $demoColorScheme = self::getDemoParamValueFromSession('demo_color_scheme');

        $currentTemplateName = $g['tmpl']['tmpl_loaded'];

        if($demoColorScheme && ($currentTemplateName == 'impact' || $currentTemplateName == 'impact_mobile')) {
            $templateOptions = loadTemplateSettings('main', 'impact');
            if(isset($templateOptions['color_scheme'][$demoColorScheme])) {
                foreach($templateOptions['color_scheme'][$demoColorScheme] as $key => $value) {
                    if(is_array($value)) {
                        $value = $value['value'];
                    }
                    $g['options'][$key] = $value;
                }
            }
        }

        if(IS_DEMO) {

            if(isset($_SERVER['QUERY_STRING']) && get_param('demo_site')) {
                set_session('demo_version', md5($_SERVER['QUERY_STRING']));
            }

            $demoOptions = self::paramFromSession('demo_options', true);
            if($demoOptions) {
                foreach($demoOptions as $demoOptionKey => $demoOptionValue) {
                    $g['options'][$demoOptionKey] = $demoOptionValue;
                }
            }
        }

        $setCityModule = 'set_city_module';
        $setCityModuleValue = get_param($setCityModule);
        if($setCityModuleValue == 'on' || $setCityModuleValue == 'off') {
            set_session($setCityModule, $setCityModuleValue);
        }
        /*$setWallModule = 'set_wall_module';
        $setWallModuleValue = get_param($setWallModule);
        if($setWallModuleValue == 'on' || $setWallModuleValue == 'off') {
            set_session($setWallModule, $setWallModuleValue);
        }*/

        $devColorSchemeModeParam = 'dev_color_scheme_mode';
        $devColorSchemeMode = get_param($devColorSchemeModeParam, '');
        if($devColorSchemeMode != '') {
            set_session($devColorSchemeModeParam, $devColorSchemeMode);
        }
        $devColorSchemeModeSession = get_session($devColorSchemeModeParam);
        if($devColorSchemeModeSession == 'on') {
            $templateOptions = loadTemplateSettings('main', 'impact');
            $siteColorScheme = Common::getOption('color_scheme_impact');
            if(isset($templateOptions['color_scheme'][$siteColorScheme])) {
                foreach($templateOptions['color_scheme'][$siteColorScheme] as $key => $value) {
                    if(is_array($value)) {
                        $value = $value['value'];
                    }
                    $g['options'][$key] = $value;
                }
            }
        }

        $edgeMainPageTitle = self::paramFromSession('edge_main_page_title');
        if($edgeMainPageTitle != '' && $edgeMainPageTitle != 'off') {
            $l['index.php']['are_you_social_enough'] = $edgeMainPageTitle;
        }

        $edgeMainPageText = self::paramFromSession('edge_main_page_text');
        if($edgeMainPageText != '' && $edgeMainPageText != 'off') {
            $l['index.php']['the_social_network_you_were_dreaming_about_all_your_life'] = $edgeMainPageText;
        }

        $edgeSetHomePage = self::paramFromSession('edge_set_home_page');
        if($edgeSetHomePage != '' && $edgeSetHomePage != 'off') {
            $g['edge']['set_home_page'] = $edgeSetHomePage;
        }

        $edgeColorSchemeVisitorOptions = array(
            'main_page_image',
            'main_page_header_background_color',
            'main_page_image_darken',
        );

        foreach($edgeColorSchemeVisitorOptions as $edgeColorSchemeVisitorOption) {
            $edgeColorSchemeVisitorOptionValue = self::paramFromSession($edgeColorSchemeVisitorOption);
            if($edgeColorSchemeVisitorOptionValue != '' && $edgeColorSchemeVisitorOptionValue != 'off') {
                $g['edge_color_scheme_visitor'][$edgeColorSchemeVisitorOption] = $edgeColorSchemeVisitorOptionValue;
            }
        }

        $edgeDisablePages = Common::paramFromSession('edge_disable_pages');

        if(IS_DEMO) {
            Demo::setSiteOptions();
        }

        self::paramFromSession('demo_logo');
    }

    static public function getDemoParamValueFromSession($param)
    {
        $paramValue = get_param($param, '');
        if($paramValue != '') {
            set_session($param, $paramValue);
        }
        $paramValueFromSession = get_session($param);

        return $paramValueFromSession;
    }

    static function paramFromSession($param, $array = false)
    {
        if($array) {
            $value = get_param_array($param);
        } else {
            $value = get_param($param, '');
        }

        if($value || get_param('demo_init')) {
            set_session($param, $value);
        }
        return get_session($param);
    }

    static function itemTitleOrBlank($location, $blank = 'Blank')
    {
        return ($location == '' || $location == '0') ? l($blank) : l($location);
    }

    static function newLinesLimit($text, $limit = 2, $endTrim = true)
    {
        $text = str_replace("\r\n", "\n", $text);
        $pattern = '#(\n){' . ($limit + 1) . ',}#';
        $text = preg_replace($pattern, str_repeat("\n", $limit), $text);
        // delete from the end of string

        if($endTrim) {
            #$pattern = '#[\n]$#';
            #$text = preg_replace($pattern, '', $text);
            $text = trim($text);
        }

        return $text;
    }

    static function parseLinks($text, $target = '_blank', $class = '', $method = 'getHref', $setTarget = false)
    {
        if (trim($class)) {
            self::$classLinks = " class='$class'";
        }

        if (trim($target)) {
            self::$targetLinks = " target='$target'";
        }
        self::$setTarget = $setTarget;
        #"/(https?:\/\/|ftp:\/\/)?([\da-z\.-]+\.?[a-z\.]{2,6})[\/\w\#,>)\];'\"!?=\&\.-]*\/?/i";
        $patterns = array("#(https?|ftp)://(\S+[^\s.,>)\];'\"!?])#i",
                         "#([[:space:]()[{}]|^)(www\.(\S+[^\s.,>)\];'\"!?]))#i");

        foreach ($patterns as $pattern) {
            $text = preg_replace_callback($pattern, 'Common::' . $method, $text);
        }
        return $text;
    }

    static function getHref($matches)
    {
        $domain  = explode('/', str_replace('www.', '', $matches[2]));
        if (self::$setTarget) {
            $target  = self::$targetLinks;
        } else {
            $target  = ('.' . $domain[0] == domain()) ? '' : self::$targetLinks;
        }
        $isHttp = trim($matches[1]);
        $http = (empty($isHttp)) ? 'http://' : '';
        $url  =  trim($matches[0]);
        $url  =  str_replace('"', '%22', $url);
        return ' <a href="'. $http . $url . '"'. $target . self::$classLinks . '>' . $matches[0] . '</a>';

    }

    static function parseLinksSmile($text, $target = '_blank', $class = '', $cutTag = true, $setTarget = false)
    {
        self::$prepareLinks = array();
        self::$iterationLinks = 1;
        $text = self::parseLinks($text, $target,  $class, 'getHrefSmile', $setTarget);
        if ($cutTag) {
            $text = self::cutTag($text);
        }
        $text = replaceSmile($text);
        return self::replaceByVars($text, self::$prepareLinks);
    }

    static function getHrefSmile($matches)
    {
        $domain  = explode('/', str_replace('www.', '', $matches[2]));
        if (self::$setTarget) {
            $target  = self::$targetLinks;
        } else {
            $target  = ('.' . $domain[0] == domain()) ? '' : self::$targetLinks;
        }
        $isHttp = trim($matches[1]);
        $http = (empty($isHttp)) ? 'http://' : '';
        $url  =  trim($matches[0]);
        $url  =  str_replace('"', '%22', $url);
        self::$prepareLinks['parse_links_' . self::$iterationLinks] = ' <a href="'. $http . $url . '"'. $target . self::$classLinks . '>' . $matches[0] . '</a>';
        $result = '{parse_links_' . self::$iterationLinks . '}';
        self::$iterationLinks++;
        return $result;
    }

    static function cutTag($text)
    {
        $vids = array('youtube', 'vimeo', 'metacafe', 'site', 'img');
        foreach ($vids as $type) {
            $objs = grabs($text, "{{$type}:", '}');
            foreach ($objs as $obj) {
                $num = 'parse_links_' . self::$iterationLinks;
                $tag = "{{$type}:{$obj}}";
                self::$prepareLinks[$num] = $tag;
                $text = str_replace($tag, "{{$num}}", $text);
                self::$iterationLinks++;
            }
        }
        return $text;
    }

    static function parseLinksTag($text, $tag = 'a', $attr = '<', $method = 'parseLinks', $target = '_blank', $class = '', $setTarget = false)
    {
        preg_match_all('{'.$attr.$tag.'[^>]*>(.*?)'.$attr.'/'.$tag.'>}', $text, $matches, PREG_PATTERN_ORDER);
        $i = 1;
        $prepare = array();
        foreach ($matches[0] as $tag) {
            $prepare['parse_tag_' . $i] = $tag;
            $text = str_replace($tag, '{parse_tag_' . $i . '}',$text);
            $i++;
        }
        $text = self::$method($text, $target,  $class, 'getHref', $setTarget);
        return self::replaceByVars($text, $prepare);
    }

    static function getImgTagToDb($matches)
    {
        $exts = array('.png', '.jpg', '.jpeg', '.gif');
        $replase = $matches[0];

        foreach ($exts as $ext) {
            $startExts = stripos($matches[0], $ext);
            if ($startExts !== false) {
                $endExts = $startExts + mb_strlen($ext, 'UTF-8');
                if (mb_substr($matches[0], $endExts, 1, 'UTF-8') == '?'
                    || mb_strlen($matches[0], 'UTF-8') == $endExts){
                    $image = OutsideImages::do_upload_image($matches[0]);
                    if ($image) {
                        $replase = '{img:' . $image['image_id'] . '}';
                    }
                }
            }
        }

        return $replase;
    }

    static function getLinkMetaSite($matches)
    {

        $replase = $matches[0];

        $replase = OutsideImages::uploadLinkMetaSite($replase);

        return $replase;
    }

    static function linksToVideo($text)
    {
        // youtube
        // http or www or youtube.com start of link
        $pattern = "#((https?)://(www.)?youtube\.com/\S+[^\s.,>)\];'\"!?])#i";
        $template = "[ID=$1]";
        #$pattern = "#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+(?=\?)|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#";
        #preg_match("#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+(?=\?)|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#", $link, $matches);
        $text = preg_replace($pattern, $template, $text);

        // vimeo


        return $text;
    }

    static function textToMedia($text, $target = '_blank', $class = '', $width = null)
    {
        require_once(dirname(__FILE__) . '/video_hosts.php');
        $text = VideoHosts::textUrlToVideoCode($text);
        $text = self::linksToVideo($text);
        $text = self::parseLinksSmile($text, $target, $class);
        $text = VideoHosts::filterFromDb($text, '', '', $width);
        $text = nl2br($text);

        return $text;
    }

    static function urlPage($domain = false)
    {
        $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        if($domain) {
            $protocol = self::urlProtocol();
            $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
            $url = "{$protocol}://{$domain}{$url}";
        }
        return $url;
    }

    static function urlSite()
    {
        $urlToPage = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
        $urlToPage = isset($_SERVER['ORIG_PATH_INFO']) ? $_SERVER['ORIG_PATH_INFO'] : $urlToPage;
        $urlToPageParts = explode('/', $urlToPage);
        array_pop($urlToPageParts);
        $urlToPage = implode('/', $urlToPageParts);

        $protocol = self::urlProtocol();
        $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $url = "{$protocol}://{$domain}{$urlToPage}/";
        return $url;
    }

    static function urlSiteSubfolders($domain = true)
    {
        global $g;

        $urlSubfolders = '';
        $baseDir = str_replace(array('\\', '//'), '/', $g['path']['dir_main']);

        if(isset($_SERVER['CONTEXT_PREFIX']) && $_SERVER['CONTEXT_PREFIX'] !== '' && isset($_SERVER['CONTEXT_DOCUMENT_ROOT']) && strpos($_SERVER['REQUEST_URI'], $_SERVER['CONTEXT_PREFIX']) !== false) {
            $urlSubfolders = str_replace(rtrim($_SERVER['CONTEXT_DOCUMENT_ROOT'], '/'), rtrim($_SERVER['CONTEXT_PREFIX'], '/'), $baseDir);
        } else {

            $serverDocumentRoot = $_SERVER['DOCUMENT_ROOT'];

            if(strpos($serverDocumentRoot, '\\') !== false) {
                $serverDocumentRoot = str_replace('\\', '/', $serverDocumentRoot);
            }

            // $serverDocumentRoot - /home/site/www
            // $baseDir - /usr/home/site/www/
            // use only left side of path
            if(strpos($baseDir, $serverDocumentRoot) > 0) {
                $baseDirParts = explode($serverDocumentRoot, $baseDir);
                if(count($baseDirParts) > 1) {
                    unset($baseDirParts[0]);
                    $baseDir = $serverDocumentRoot . implode('', $baseDirParts);
                }
            }

            $urlSubfolders = str_replace(rtrim($serverDocumentRoot, '/'), '', $baseDir);

            $serverDocumentRootParts = explode('/', $serverDocumentRoot);
            $baseDirParts = explode('/', $baseDir);

            $commonFolders = array_intersect($serverDocumentRootParts, $baseDirParts);

            if($commonFolders) {
                $commonPath = implode('/', $commonFolders);

                if(strlen($serverDocumentRoot) === strpos($serverDocumentRoot, $commonPath) + strlen($commonPath) && strpos($baseDir, $commonPath) === 0) {
                    $urlSubfolders = str_replace(rtrim($commonPath, '/'), '', $baseDir);
                }
            }

            if($urlSubfolders != '/' && isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI']) {
                $requestUriParts = explode('/', $_SERVER['REQUEST_URI']);

                $countRequestUriParts = count($requestUriParts);

                if($countRequestUriParts > 1) {

                    $urlSubfoldersParts = explode('/', $urlSubfolders);
                    $isUrlSubfoldersChanged = false;
                    $i = 0;
                    while($i < $countRequestUriParts) {
                        if(isset($urlSubfoldersParts[$i])) {
                            if($urlSubfoldersParts[$i] != '') {
                                if($requestUriParts[$i] != $urlSubfoldersParts[$i]) {
                                    $urlSubfoldersParts[$i] = $requestUriParts[$i];
                                    $isUrlSubfoldersChanged = true;
                                }
                            }
                        } else {
                            break;
                        }
                        $i++;
                    }

                    if($isUrlSubfoldersChanged) {
                        $urlSubfolders = implode('/', $urlSubfoldersParts);
                    }
                }

            }

        }

        // Fix for incorrect path detection
        if(strpos($_SERVER['REQUEST_URI'], $urlSubfolders) === false) {
            $urlSubfolders = '';
        }

        $url = $urlSubfolders;

        if($domain) {
        	$protocol = self::urlProtocol();
        	$domain   = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        	$url = "{$protocol}://{$domain}{$urlSubfolders}";
        }

        $url = rtrim($url, '/') . '/';

        return $url;
    }

    static function urlProtocol()
    {
        $protocol = ( (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
                        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
                        || (isset($_SERVER['HTTP_SSL']) && $_SERVER['HTTP_SSL'] == 'TRUE')
            ) ? 'https' : 'http';
        return $protocol;
    }

    static function page()
    {
        global $p;

        return $p;
    }

    static function pageBaseName()
    {
        global $p;

        $pageParts = explode('.', $p);
        array_pop($pageParts);
        $page = implode('.', $pageParts);

        return $page;
    }

    static function isOptionActive($option, $key = 'options')
    {
        global $g;

        $active = false;
        if(isset($g[$key][$option]) && $g[$key][$option] == 'Y') {
            $active = true;
        }

        return $active;
    }

    static function isOptionActiveTemplate($option)
    {
        return self::isOptionActive($option, 'template_options');
    }

    static function optionDeactivate($option)
    {
        global $g;
        $g['options'][$option] = 'N';
    }

    static function getOption($option, $key = 'options')
    {
        global $g;

        return isset($g[$key][$option]) ? $g[$key][$option] : null;
    }

    static function getOptionTemplate($option)
    {
        return self::getOption($option, 'template_options');
    }

    static function getOptionInt($option, $key = 'options')
    {
        return intval(self::getOption($option, $key));
    }

    static function getOptionTemplateInt($option)
    {
        return intval(self::getOption($option, 'template_options'));
    }

    static function setOptionRuntime($value, $option, $key = 'options')
    {
        global $g;
        $g[$key][$option] = $value;
    }

    static function tmplPath($type, $path)
    {
        if($type == 'mobile') {
            $path = str_replace('/_frameworks/main', '/_frameworks/mobile', $path);
        }

        return $path;
    }

    static function langPath($type, $path)
    {
        if($type == 'mobile') {

        }

        return $path;
    }

    static function sanitizeFilename($name)
    {
        return basename($name);
    }

    static function mobileRedirect()
    {
        global $g;
        if(isset($g['mobile_redirect_off'])) {
            return;
        }
        if(Common::isOptionActive('frameworks_version') && get_cookie('frameworks_version') == '1') {
            return;
        }
        if(self::isMobile()) {
            return;
        }
        $mobile = countFrameworks('mobile');
        if(!$mobile) {
            return;
        }
        global $p;
        if($p == 'confirm_email.php' || $p == 'invite_confirm.php' || $p == 'before.php' || $p == 'after.php') {
            return;
        }
        if(!self::isOptionActive('mobile_redirect') and (!self::isOptionActive('mobile_site_on_tablet'))) {
            return;
        }
        if (self::isMobile(false)){
            if(IS_DEMO && get_param('demo_redirect')) {
                if(get_param('demo_redirect') == '_server/city_js/index.php') {
                    $_GET['demo_redirect'] = urlencode('_server/city_js/index.php?view=mobile');
                }
                return;
            }

            $optionTemplateSet = Common::getOption('set', 'template_options');
			$optionTemplateName = Common::getOption('name', 'template_options');
            $url = self::urlSite() . MOBILE_VERSION_DIR . '/';

            $pagesAllowed = array(
                'search_results.php',
                'mutual_attractions.php',
                'my_friends.php'
            );

			$urlRedirect = '';
            if ($optionTemplateSet == 'urban') {
                /* Messages template urban */
                //Sent to the desktop Urban http://l/r/search_results.php?display=profile&uid=451&show=&email_auth_key=12_1
                $uid = get_param('uid');
                $show = get_param('show');
				$nameSeoParams = get_param('name_seo');
				$display = get_param('display');

                if ($uid && $show == 'messages') {
                    $urlRedirect = '/messages.php?display=one_chat&user_id=' . $uid;
                }

                //http://l/trunk/profile_view.php?show=gallery&photo_id=413859&email_auth_key=451_1
                $pid = get_param('photo_id');
                if($pid && $show == 'gallery'){
                    $urlRedirect = '/profile_view.php?show=albums&photo_id=' . $pid;
                }
                /* Messages template urban */

                $page = get_param('page');
                if ($p == 'info.php' && ($page == 'term_cond' || $page == 'priv_policy')) {
                    $alias = 'terms';
                    if ($page == 'priv_policy') {
                        $alias = 'privacy_policy';
                    }
                    $urlRedirect = '/' . Common::pageUrl($alias);
                }

				//http://l/r/york_hunt?email_auth_key=12_1
				if ($optionTemplateName != 'edge' && $p == 'search_results.php'
						&& $display == 'profile'
						&& ($uid || $nameSeoParams)) {
					if ($nameSeoParams) {
						$uid = User::getUidFromNameSeo($nameSeoParams);
					}
					if ($uid) {
						$urlRedirect = '/profile_view.php?user_id=' . $uid;
					}
				}

				if ($urlRedirect) {
					redirect(self::urlSiteSubfolders() . MOBILE_VERSION_DIR . $urlRedirect);
				}

                $pagesAllowed[] = 'messages.php';//mobile urban - mobile impact
            }
            if (!in_array($p, $pagesAllowed)) {
                $urlRedirect = $url . 'index.php';
				$place = get_param('place');
				if ($place && mb_strpos($url, '_server/city_js', 0, 'UTF-8') !== false ) {
					if(Common::isOptionActive('seo_friendly_urls')) {
						$urlRedirect = self::urlSiteSubfolders() . MOBILE_VERSION_DIR . '/3d' . '/' . $place;
					} else {
						$urlRedirect = self::urlSiteSubfolders() . '_server/city_js/index.php?view=mobile&place=' .$place ;
					}
				}

                if(IS_DEMO) {
                    // redirect to compatible mobile template
                    $mobileTemplatesCompatibility = array(
                        'new_age' => 'black',
                        'mixer' => 'black',
                        'oryx' => 'white',
                        'urban' => 'urban_mobile',
                        'impact' => 'impact_mobile',
                    );

                    $tmplLoaded = Common::getOptionTemplate('name');

                    $mobileTemplateCompatible = isset($mobileTemplatesCompatibility[$tmplLoaded]) ? $mobileTemplatesCompatibility[$tmplLoaded] : false;

                    if($mobileTemplateCompatible) {
                        $delimiter = (strpos($urlRedirect, '?') !== false) ? '&' : '?';
                        $urlRedirect .= $delimiter . 'set_template_mobile=' . $mobileTemplateCompatible;
                    }
                }

				redirect($urlRedirect);
            }
            $mobileRedirectUrl = array(
                'search_results.php_profile' => 'profile_view.php?user_id={uid}',//profile_visitors
            );
            $display = get_param('display');
            $type = "{$p}_{$display}";
            if (isset($mobileRedirectUrl[$type])) {
                $uid = get_param('uid');
                $vars = array('uid' => $uid);
                $url .= Common::replaceByVars($mobileRedirectUrl[$type], $vars);
            } else {
                $params = get_params_string();
                if ($params) {
                    $params = del_param("ref", $params);
                    $params = '?' . $params;
                }
                $url .= $p . $params;
            }

            redirect($url);
        }
    }

    static function getCacheHeadersUserAgent()
    {
        $userAgent = null;
        foreach(self::$uaHttpHeaders as $altHeader){
            if (isset($_SERVER[$altHeader])){
                $userAgent .= $_SERVER[$altHeader] . " ";
            }
        }

        $userAgent = (!empty($userAgent) ? trim($userAgent) : null);
        return md5($userAgent);
    }

    static function iosVersion()
    {
        $version = 0;

        if(isset($_SERVER['HTTP_USER_AGENT'])) {

            //$pattern = '/(IOSWebview\((?<app>.*)\)|OS (?<os>.*) like Mac OS X)/';
            $pattern = '/(IOSWebview\-(?<site>[\d\.]*)\((?<app>.*)\)|OS (?<os>.*) like Mac OS X)/U';
            if(preg_match($pattern, $_SERVER['HTTP_USER_AGENT'], $matches)) {
                if(isset($matches['os']) && $matches['os'] != '') {
                    $version = str_replace('_', '.', $matches['os']);
                } elseif(isset($matches['app']) && $matches['app'] != '') {
                    $version = $matches['app'];
                }
            }
        }

        return $version;
    }

    static function isIosVersionCompatible($versionMajor, $versionMinor = 0)
    {
        $isIosVersionCompatible = false;

        $version = self::iosVersion();
        if($version) {

            $versionParts = explode('.', $version);
            $iosVersionMajor = $versionParts[0];
            $iosVersionMinor = isset($versionParts[1]) ? $versionParts[1] : 0;

            if($iosVersionMajor > $versionMajor || ($iosVersionMajor == $versionMajor && $iosVersionMinor >= $versionMinor)) {
                $isIosVersionCompatible = true;
            }
        }

        return $isIosVersionCompatible;
    }

    static function isAppIosWebrtcAvailable()
    {
        return self::isIosVersionCompatible(14, 3) || file_exists(Common::getOption('dir_include', 'path') . 'config/ios_app_webrtc_on');
    }

    static function isAppIos()
    {
        $isIos = get_session('is_ios', null);
        //$isIos = true;
        if ($isIos === null) {
            $isIos = isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'IOSWebview');
            set_session('is_ios', $isIos);
        }
        return $isIos;
    }

    static function isIosDevice()
    {
        $isIosDevice = get_session('is_ios_device', null);
        if ($isIosDevice === null) {
            $detect = new mobile_detect;
            $isIosDevice = $detect->isiOS();
            set_session('is_ios_device', $isIosDevice);
        }
        return $isIosDevice;
    }

    static function isSafari()
    {
        $isSafari = get_session('is_safari', null);
        if ($isSafari === null) {
            $detect = new mobile_detect;
            $isSafari = $detect->isSafari();
            set_session('is_safari', $isSafari);
        }
        return $isSafari;
    }


    static function isAppAndroid()
    {
        $sessionName = 'is_app_android';
        $isApp = get_session($sessionName, null);
        if ($isApp === null) {
            $isApp = isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'AppWebview');
            set_session($sessionName, $isApp);
        }
        return $isApp;
    }

    static function isApp()
    {
        return self::isAppIos() || self::isAppAndroid();
    }

    static function isInAppPurchaseEnabled()
    {
        $isInAppPurchaseEnabled = true;
        if (Common::isApp()) {
            $isInAppPurchaseEnabled = Common::isOptionActive('in_app_purchase_enabled');
        }
        //return Common::isOptionActive('in_app_purchase_enabled');
        return $isInAppPurchaseEnabled;
    }

    static function getMobileOs()
    {
        $os = get_session('mobile_os', null);
        if ($os === null) {
            $os = '';
            $detect = new mobile_detect;
            if ($detect->isAndroidOS()) {
                $os = 'android';
            } elseif ($detect->isiOS()) {
                $os = 'ios';
            }
            set_session('mobile_os', $os);
        }
        return $os;
    }

    static public function getIosVersion()
    {
        $version = '';

        if(isset($_SERVER['HTTP_USER_AGENT'])) {
            $regex = '/OS (.*) like/Uis';
            if(preg_match($regex, $_SERVER['HTTP_USER_AGENT'], $matches)) {
                $version = str_replace('_', '.', $matches[1]);
            }
        }

        return $version;
    }

    static function isMobile($version = true, $isMobileRedirect = null, $isMobileSiteOnTablet = null)
    {
        global $g;
        if ($version){
            return isset($g['mobile']);
        } else {
            $result = '';
            $browserVersionCache = get_session('browser_version_cache');
            $isMobileNoTablet = get_session('is_mobile_no_tablet_browser', null);
            $isTablet = get_session('is_tablet_browser', null);
            if ($isMobileRedirect === null) {
                $isMobileRedirect = Common::isOptionActive('mobile_redirect');
            }
            if ($isMobileSiteOnTablet === null) {
                $isMobileSiteOnTablet = Common::isOptionActive('mobile_site_on_tablet');
            }
            if (($isTablet !== null or $isMobileNoTablet !== null) && $browserVersionCache == self::getCacheHeadersUserAgent())
            {
                if ($isMobileRedirect){
                    $result = $isMobileNoTablet;
                }
                if ($isMobileSiteOnTablet){
                    if (!$result){
                        $result = $isTablet;
                    }
                }
            } else {
                $detect = new mobile_detect;
                $isTablet =  $detect->isTablet();
                $isMobileNoTablet = $detect->isMobileNoTablet();
                if (($isMobileSiteOnTablet && $isTablet) or ($isMobileRedirect && $isMobileNoTablet)){
                    $result = true;
                } else {
                    $result = false;
                }
            }
            $count = countFrameworks('main');
            if ($count) {
                return $result;
            } else {
                return true;
            }
        }

    }

    static function dirFiles($dir)
    {
        $files = false;

        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                $files = array();
                while (($file = readdir($dh)) !== false) {
                    if (is_file($dir . $file) and $file != "." and $file != "..") {
                        $files[] = $file;
                    }
                }
                closedir($dh);
            }
        }

        return $files;
    }

    static function dirDirs($dir)
    {
        $files = false;

        if(substr($dir, -1) != DIRECTORY_SEPARATOR) {
            $dir .= DIRECTORY_SEPARATOR;
        }

        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                $files = array();
                while (($file = readdir($dh)) !== false) {
                    if ($file != "." and $file != ".." and is_dir($dir . $file)) {
                        $files[] = $file;
                    }
                }
                closedir($dh);
            }
        }

        return $files;
    }

    static function parsePagesList(&$html, $block, $count, $start = 0, $limit = 10, $pagesLimit = 5, $pageUrlDefault = '')
    {
        $eu = $start;
        $this_page = $eu + $limit;
        $back = $eu - $limit;
        $next = $eu + $limit;

        $nextCount = $next;
        if($nextCount > $count) {
            $nextCount = $count;
        }

        $html->setvar('this_count', $eu + 1);
        $html->setvar('next_count', $nextCount);
        $html->setvar('all_count', $count);

        $pageUrlDelimiter = '';
        $isPagerSeo = $html->varExists('pager_url_seo');
        if ($isPagerSeo) {
            $pageUrlDelimiter = self::getPagesListDelimiter($pageUrlDefault, 'offset');
            $html->setvar('pager_url_seo', $pageUrlDefault);
        }

        if ($count / $limit > $pagesLimit) {
            $tostart = $eu;
            $tonume = (1 + round($eu / $limit)) * $limit;
        } else {
            $tostart = 0;
            $tonume = $count;
        }

        if ($tostart > 0) {
            $html->setvar('toleft', $tostart - $limit);
            $html->parse($block . '_left_2', true);
        }
        $n = $tostart / $limit + 1;

        $n_pages = ceil($count / $limit);
        $page = max(1, min($n_pages, round($eu / $limit)));
        $links = pager_get_pages_links($n_pages, $page, $pagesLimit);
        foreach($links as $link) {
            $i = ($link - 1) * $limit;
            $n = $link;
            if ($i == $eu) {
                $html->setvar('l', $n);
                $html->setblockvar($block . '_page', '');
                $html->parse($block . '_page_1', false);
                $html->parse($block . '_pages_1', true);
            } else {
                if ($isPagerSeo) {
                    $html->setvar('page_i', self::getPagesListParam($i, $pageUrlDelimiter));
                }
                $html->setvar('i', $i);
                $html->setvar('l', $n);
                $html->parse($block . '_page', false);
                $html->setblockvar($block . '_page_1', '');
                $html->parse($block . '_pages_1', true);
            }
        }

        /*for ($i = $tostart; $i < $tonume; $i = $i + $limit) {
            if ($i <> $eu) {
                $html->setvar('i', $i);
                $html->setvar('l', $n);
                $html->parse($block . '_page', false);
                $html->setblockvar($block . '_page_1', '');
                $html->parse($block . '_pages_1', true);
            } else {
                $html->setvar('l', $n);
                $html->setblockvar($block . '_page', '');
                $html->parse($block . '_page_1', false);
                $html->parse($block . '_pages_1', true);
            }
            $n = $n + 1;
        }*/
        if ($count > $tonume) {
            $html->setvar('toright', $tonume);
            $html->parse($block . '_right_2', true);
        }
        if ($back >= 0) {
            if ($isPagerSeo) {
                $html->setvar('page_back', self::getPagesListParam($back, $pageUrlDelimiter));
            }
            $html->setvar('back', $back);
            $html->parse($block . '_prev', true);
        } else {
            $html->parse($block . '_prev_disabled', true);
        }
        if ($back >= 0 && $this_page < $count) {
            $html->parse($block . '_separator', true);
        }
        if ($this_page < $count) {
            if ($isPagerSeo) {
                $html->setvar('page_next', self::getPagesListParam($next, $pageUrlDelimiter));
            }
            $html->setvar('next', $next);
            $html->parse($block . '_next', true);
        } else {
            $html->parse($block . '_next_disabled', true);
        }
        if($count > $limit) {
            $html->parse($block . '_pages', true);
        }
    }

    static function getPagesListParam($page, $delimiter = '')
    {
        if ($page > 1) {
            return $delimiter . $page;
        }
        return '';
    }

    static function getPagesListDelimiter($pageUrl, $pageUrlParam = 'page')
    {
        if (Common::isOptionActive('seo_friendly_urls')) {
            $pageUrlDelimiter = '/';
        } else {
            $pageUrlDelimiter = '&';
            if (mb_strpos($pageUrl, '?', 0, 'UTF-8') === false) {
                $pageUrlDelimiter = '?';
            }
            $pageUrlDelimiter .= "{$pageUrlParam}=";
        }

        return $pageUrlDelimiter;

    }

    static function parsePagesListUrban(&$html, $page, $n_results, $n_results_per_page, $n_links = 5, $pageUrlDefault = '', $pageUrlParam = 'page')
    {
        global $p;

        if ($html->varExists('pager_url')) {
            $pagerUrlDelimiter = '?';
            if (self::$pagerUrl) {
                $pageUrl = self::$pagerUrl;
                if (mb_strpos($pageUrl, '?', 0, 'UTF-8') !== false) {
                    $pagerUrlDelimiter = '&';
                }
            } else {
                $pageUrl = substr($p, 0, -4);
                $pageUrl = Common::pageUrl($pageUrl);
            }
            $html->setvar('pager_url_delimiter', $pagerUrlDelimiter);
            $html->setvar('pager_url', $pageUrl);

        }

        $pageUrlDelimiter = '';
        $isPagerSeo = $html->varExists('pager_url_seo');
        if ($isPagerSeo) {
            $pageUrlDelimiter = self::getPagesListDelimiter($pageUrlDefault, $pageUrlParam);
            $html->setvar('pager_url_seo', $pageUrlDefault);
        }

        $page = intval($page);
        if($page < 1) {
            $page = 1;
        }
        $n_pages = ceil($n_results / $n_results_per_page);
        $page = max(1, min($n_pages, $page));

        if($n_pages > 1) {
            if($page > 1) {
                if ($isPagerSeo) {
                    $html->setvar('page_n_seo', self::getPagesListParam($page - 1, $pageUrlDelimiter));
                }
                $html->setvar('offset', ($page - 1) * $n_results_per_page + 1 - $n_results_per_page);
                $html->setvar('page_n', $page-1);
                $html->parse('pager_prev');
            } else {
				$html->parse('pager_prev_disabled');
			}

            $links = pager_get_pages_links($n_pages, $page, $n_links);
            $total = count($links);
            $i = 0;
            $j = 0;
            $offsetNext = 0;
            foreach($links as $link) {
                $i++;
                if ($isPagerSeo) {
                    $html->setvar('page_n_seo', self::getPagesListParam($link, $pageUrlDelimiter));
                }
                $html->setvar('page_n', $link);
                $offset = ($link - 1) * $n_results_per_page + 1;
                $html->setvar('offset', $offset);
                if($page == $link) {
                    $class = 'sel_c';
                    if ($i == 1) {
                        $class = 'sel_f';
                    } else if ($i == $total) {
                        $class = 'sel_l';
                    }
                    $html->setvar('class_link_active', $class);
                    $html->parse('pager_link_active', false);
                    $html->setblockvar('pager_link_not_active', '');
                    $j = 1;
                } else {
                    if ($j) {
                        $j = 0;
                        $offsetNext = $offset;
                    }
                    $html->parse('pager_link_not_active', false);
                    $html->setblockvar('pager_link_active', '');
                }
                $html->parse('pager_link');
            }

            if($page < $n_pages) {
                if ($isPagerSeo) {
                    $html->setvar('page_n_seo', self::getPagesListParam($page + 1, $pageUrlDelimiter));
                }
                $html->setvar('offset', $offsetNext);
                $html->setvar('page_n', $page+1);
                $html->parse('pager_next');
            } else {
				$html->parse('pager_next_disabled');
			}
            $html->parse('pager');
        }
    }

    static function isModuleCityExists()
    {
        static $exists = 0;
        if($exists === 0) {
            $exists = file_exists(dirname(__FILE__) . '/../../_server/city_js');
        }
        return (Common::isMultisite()) ? 0 : $exists;
    }

    static function isModuleCityActive()
    {
        return self::isOptionActive('city') && self::isModuleCityExists();
    }

    static function isValidArray($array)
    {
        return (is_array($array) && count($array));
    }

    static function mainPageRedirect()
    {
        if(Common::isOptionActive('main_page_by_first_menu_item', 'template_options')) {
            $url = Menu::getUrlHomePage();
            // prevent infinite redirect
            if($url != Common::page()) {
                redirect($url);
            }
        }
    }

    static function demoSetMainSection($menuItems)
    {
        $mainSectionModeSession = get_session('main_section');
        if($mainSectionModeSession) {
            if(isset($menuItems[$mainSectionModeSession])) {
                $menuItems[$mainSectionModeSession] = '-1';
            }
        }
        return $menuItems;
    }

    static function authRequiredExit()
    {
        if(!guid()) {
            die('please_login');
        }
    }

    static function tmplName($tmplPath = NULL)
    {
        if ($tmplPath == NULL) {
            $tmplPath = self::getOption('dir_tmpl_main', 'tmpl');
        }
        $tmplPathParts = explode('/', $tmplPath);
        array_pop($tmplPathParts);
        return end($tmplPathParts);
    }

    static function refererFromSite()
    {
        $url = Common::getHomePage();
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
        if($referer) {
            // check if from our domain
            $urlSite = self::urlSite();
            if(substr($referer, 0, strlen($urlSite)) == $urlSite) {
                $url = $referer;
            }
        }

        return $url;
    }

    static function refererPageFromSite()
    {
        $refererPage = '';
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
        if($referer) {
            $urlSite = self::urlSite();
            $lenUrlSite = mb_strlen($urlSite, 'UTF-8');
            if(mb_substr($referer, 0, $lenUrlSite, 'UTF-8') == $urlSite) {
                $refererPage = mb_substr($referer, $lenUrlSite, mb_strlen($referer, 'UTF-8'), 'UTF-8');
            }
        }

        return $refererPage;
    }

    static function getIdsByNear($cityInfo, $howNear=0, $onlyWithPublicPhoto = true)
        {
            $eq=array('=','=','=');
            if($howNear>0){
                $eq[0]='!=';
            }
            if($howNear>1){
                $eq[1]='!=';
            }
            if($howNear>2){
                $eq[2]='!=';
            }

            $where = '';
            if ($onlyWithPublicPhoto) {
                $where = ' AND is_photo_public = "Y" ';
            }
            $sql='SELECT user_id
                    FROM `user`
                   WHERE `is_photo` = "Y"
                     AND `hide_time` = 0
                     AND `set_who_view_profile` = "anyone"
                     AND city_id '.$eq[0] . $cityInfo['city_id'] . '
                     AND state_id '.$eq[1] . $cityInfo['state_id'] . '
                     AND country_id '.$eq[2] . $cityInfo['country_id']
                    . $where . '
                   ORDER BY user_id DESC
                   LIMIT 100';

            $idsNear=DB::column($sql);

            return $idsNear;
        }


    static function sqlUsersNearCity($cityInfo, $limit = 5, $onlyWithPublicPhoto = true)
    {

        $sessCacheKeyIds='users_near_ids_'.$cityInfo['city_id'];

        $idsCache=get_session($sessCacheKeyIds,array('time'=>0, 'values'=>array()));

        $updateCache=false;
        if( $idsCache['time']+3600>time()){
            $cacheArr=$idsCache['values'];
        }else {
            $updateCache=true;
        }

        $idsArr=array();
        $countUsersNear=0;
        $nNear=0;
        while($countUsersNear<$limit && $nNear<=3){
            if(!isset($cacheArr[$nNear])){
                $cacheArr[$nNear]=self::getIdsByNear($cityInfo, $nNear, $onlyWithPublicPhoto);
                $updateCache=true;
            }
            $idsNear=$cacheArr[$nNear];
            shuffle($idsNear);
            foreach($idsNear as $v){
                $idsArr[]=$v;
                $countUsersNear++;
                if($countUsersNear>=$limit){
                    break;
                }
            }
            $nNear++;
        }

        if($updateCache){
            set_session($sessCacheKeyIds,array('time'=>time(),'values'=>$cacheArr));
        }

        if(count($idsArr)==0){
            $idsArr[]=0;
        }

        $where = '';
        if ($onlyWithPublicPhoto) {
            $where = ' AND is_photo_public = "Y" ';
        }
        $sqlHide = User::isHiddenSql('');
        $sql = 'SELECT *, IF(city_id = ' . $cityInfo['city_id'] . ', 1, 0) +
                          IF(state_id = ' . $cityInfo['state_id'] . ', 1, 0) +
                          IF(country_id = ' . $cityInfo['country_id'] . ', 1, 0) AS near,
                        DATE_FORMAT(NOW(), "%Y") - DATE_FORMAT(birth, "%Y") - (DATE_FORMAT(NOW(), "00-%m-%d") < DATE_FORMAT(birth, "00-%m-%d")) AS age
                  FROM `user`
                 WHERE `is_photo` = "Y"
                   AND ' . $sqlHide
                         . $where . '
                   AND `set_who_view_profile` = "anyone"
                   AND user_id IN ('.implode(',',$idsArr).')';

        return $sql;
    }

    static function isNotAllowedLanguage($lang)
    {
        return false;

        $optionSet = Common::getOption('set', 'template_options');
        $defaultLang = array('chinese','default','dutch','french','german','italian','lithuanian','portuguese','russian','spanish','turkish');
        $allowedUrbanLang = array('default', 'russian', 'spanish', 'polish', 'portuguese', 'german', 'french', 'italian', 'turkish', 'polish', 'bulgarian', 'czech', 'hungarian', 'norwegian', 'swedish', 'italian', 'greek', 'persian', 'thai', 'vietnamese');

        return IS_DEMO && $optionSet == 'urban' && in_array($lang, $defaultLang) && !in_array($lang, $allowedUrbanLang);
    }

    static function listLangs($dir = 'main', $hide = true)
    {
        $dirPath = self::getOption('dir_lang', 'path') . $dir . '/';
        $langs = Common::dirDirs($dirPath);
        $langsList = false;
        if (is_array($langs) && count($langs)) {
            $langsList = array();
            foreach ($langs as $file) {
//                if (self::isNotAllowedLanguage($file)) {
//                    continue;
//                }
                if ($hide && Common::getOption($file, 'hide_language_' . $dir)) {
                    continue;
                }
                $langKey = $file;
                if($file == 'default') {
                    $langKey = 'language_default';
                }
                $langsList[$file] = l(ucfirst($langKey));
            }
            if ($langsList) {
                natcasesort($langs);
                //$langsList = array_flip($langsList);
                $langsList = Common::sortingLangsList($langsList, $dir);
            }
        }
        return $langsList;
    }

    static function setFirstCurrentLanguage($langs, $part = null) {
        if ($part === null) {
            $part = 'main';
        }
        $languageCurrent = Common::getOption($part, 'lang_value');
        if(isset($langs[$languageCurrent])) {
            $firstLanguage = array($languageCurrent => $langs[$languageCurrent]);
            unset($langs[$languageCurrent]);
            $langs = array_merge($firstLanguage, $langs);
        }
        return $langs;
    }

    static function sortingLangsList($langs, $part = 'main')
    {
        global $g;
        if (!isset($g['lang_order'][$part])) {
            $languageCurrent = Common::getOption($part, 'lang_value');
            if(isset($langs[$languageCurrent])) {
                $g['lang_order'][$part] = serialize(array($languageCurrent => '0'));
            } else {
                $g['lang_order'][$part] = serialize(array('default' => '0'));
            }
        }
        natcasesort($langs);
        $languagesOrder = unserialize($g['lang_order'][$part]);
        if(count($languagesOrder) > 0){
            $langsTmp = $langs;
            $langs = array();
            foreach($languagesOrder as $k => $v){
                $key = isset($langsTmp[$k]);
                if(isset($langsTmp[$k])){
                    $langs[$k] = $langsTmp[$k];
                    unset($langsTmp[$k]);
                }
            }
            $langs = array_merge($langs, $langsTmp);
        }

        return $langs;
    }

    static function listTmpls($part)
    {
        $parts = array('main', 'mobile', 'partner', 'administration');
        if(!in_array($part, $parts)) {
            return;
        }

        $dir = self::getOption('dir_tmpl', 'path') . $part . '/';
        $tmpls = Common::dirDirs($dir);
        $tmplsList = false;
        if (is_array($tmpls) && count($tmpls)) {
            $tmplsList = array();
            foreach ($tmpls as $file) {
                $tmplsList[$file] = ucfirst(str_replace('_', ' ', $file));
            }
            natcasesort($tmplsList);
            $tmplsList = array_flip($tmplsList);
        }

        return $tmplsList;
    }

    static function isLanguageFileExists($language,$part = 'main')
    {
        $file = self::getOption('dir_lang', 'path') . $part . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . 'language.php';

        return file_exists($file);
    }

    static function devCustomJs(&$html)
    {
        global $g;
        global $p;
        global $sitePart;
        global $sitePartParam;

        if (FOOTER_CUSTOM_JS && $html->varExists('footer_custom_js')) {
            $dir = Common::getOption('dir_tmpl', 'path') . $sitePart;

            $tmplsAvailable = array(
                'main' => array('new_age', 'oryx', 'mixer'),
                'mobile' => array('edge_mobile', 'black', 'white', 'urban_mobile', 'impact_mobile'),
            );

            if($sitePart == 'main') {
                $urbanIsActive = true;
                $pagesUrban = array(
                    'index.php',
                    'join.php',
                    'forget_password.php',
                    'profile_view.php',
                    'search_results.php',
                    'mail_whos_interest.php',
                );

                if($p == 'search_results.php') {
                    if(get_param('display') == 'photo') {
                        $urbanIsActive = false;
                    }
                }

                if($urbanIsActive && in_array($p, $pagesUrban)) {
                    $tmplsAvailable['main'][] = 'urban';
                    $tmplsAvailable['main'][] = 'impact';
                    $tmplsAvailable['main'][] = 'edge';
                }

            }

            if(isset($tmplsAvailable[$sitePart])) {
                $tmpls = $tmplsAvailable[$sitePart];
            } else {
                $tmpls = Common::dirDirs($dir);
            }

            $tmplsToJs = "var tmplsList = [];\n";
            $index = 0;
            foreach($tmpls as $tmpl) {
                $tmplsToJs .= "tmplsList[$index] = '$tmpl';\n";
                $index++;
            }

            //$tmplLoaded = Common::getOption('tmpl_loaded', 'tmpl');
            $tmplLoaded = Common::getOptionTemplate('name');

            $languageOfUser = get_session('language_of_user');
            $languageLoaded = Common::getOption('lang_loaded', 'main');
            $analyticsCode = '';
            if ($g['main']['site_part'] != 'administration') {
               $analyticsCode = '</script>' . Common::getOption('analytics_code', 'main') . '<script>';
            }

            if(IS_DEMO && $sitePart != 'mobile' && !file_exists(__DIR__ . '/../config/jivosite_off')) {
                $analyticsCode .= "
                <!-- BEGIN JIVOSITE CODE {literal} -->

                function jivo_onOpen() {
                    jivo_api.setCustomData([
                        {
                            content: '$tmplLoaded',
                        }
                    ]);
                }

                function jivo_onChangeState() {
                    isCloseJivoPopup=false;
                }

                (function(){ var widget_id = 'MTk1Njc4';
                if(parent.window.frames['iframe_demo']) {
                    return;
                }
                var s = document.createElement('script'); s.type = 'text/javascript'; s.async = true; s.src = '//code2.jivosite.com/script/widget/'+widget_id; var ss = document.getElementsByTagName('script')[0]; ss.parentNode.insertBefore(s, ss);})();
                <!-- {/literal} END JIVOSITE CODE -->
                ";
            }

            $guid = guid();
            if (DEV === true) {
                // disable it on real site
                $initDevFunctions = Common::getOption('version', 'db_info') ? 'false' : 'true';

            $footerCustomJs = <<<JS
$tmplsToJs

var tmplCurrent = '$tmplLoaded';

if($initDevFunctions) {
    initDevFunctions();
}

var sitePart = '$sitePart';
var sitePartParam = '$sitePartParam';
var languageOfUser = '$languageOfUser';
var siteLanguage = '$languageLoaded';
var siteGuid = '$guid'*1;
$analyticsCode
JS;

} else {
    $footerCustomJs = <<<JS
$analyticsCode
JS;
}
            $footerCustomJsGroup = '';
            $groupId = Groups::getParamId();
            if ($groupId) {
                $footerCustomJsGroup = ", group_vids_list_{$groupId} : '" . self::pageUrl('group_vids_list', $groupId) . "'
                                        , group_songs_list_{$groupId} : '" . self::pageUrl('group_songs_list', $groupId) . "'
                                        , group_photos_list_{$groupId} : '" . self::pageUrl('group_photos_list', $groupId) . "'";
            }

            $footerCustomJs .= "
                var IS_DEMO = " . intval(IS_DEMO) . ";
                var urlPageJoin = '" . Common::pageUrl('join') . "';
                var urlPageLogin = '" . Common::pageUrl('login') . "';
                var urlPageUpgrade = '" . Common::pageUrl('upgrade') . "';
                var urlPagesSite = {
                    index : '" . self::pageUrl('index') . "',
                    login : '" . self::pageUrl('login') . "',
                    join : '" . self::pageUrl('join') . "',
                    home : '" . self::getHomePage() . "',
                    upgrade : '" . self::pageUrl('upgrade') . "',
                    search_results : '" . self::pageUrl('search_results') . "',
                    profile_view : '" . self::pageUrl('profile_view') . "',
                    messages : '" . self::pageUrl('messages') . "',
                    refill_credits : '" . self::pageUrl('refill_credits') . "',
                    my_vids_list : '" . self::pageUrl('user_vids_list', guid()) . "',
                    my_photos_list : '" . self::pageUrl('user_photos_list', guid()) . "',
                    my_songs_list : '" . self::pageUrl('user_songs_list', guid()) . "',
                    photos_list : '" . Common::pageUrl('photos_list') . "',
                    blogs_add : '" . Common::pageUrl('blogs_add') . "',
                    profile_settings : '" . Common::pageUrl('profile_settings') . "',
                    wall : '" . Common::pageUrl('wall') . "'"
                    . $footerCustomJsGroup ."
                };
                var cacheVersionParam = '" . (isset($g['site_cache']['cache_version_param']) ? $g['site_cache']['cache_version_param'] : '') . "';

                setAjaxPrefilter();
            ";

            // For compatibility with old browsers and Android apps
            if(isset($_SERVER['HTTP_USER_AGENT']) && preg_match('~Chrome/([89]\d|\d{3,})\.~', $_SERVER['HTTP_USER_AGENT'])) {

                $footerCustomJs .= "
                async function requestWakeLockScreen(unlock){
                    unlock=unlock||false;
                    try {
                        if (unlock) {
                            if (_lockDisplay!==false) {
                                _lockDisplay.release();
                                _lockDisplay=false;
                                console.info('requestWakeLockScreen released');
                            }
                        } else {
                            _lockDisplay = await navigator.wakeLock.request('screen');
                        }
                        console.info('INIT RequestWakeLock', unlock);
                    } catch (err) {
                        console.error('RequestWakeLock', unlock, err.name, err.message);
                    }
                }
                ";
            }

            $html->setvar('footer_custom_js', $footerCustomJs);
        }
    }

    static function langParamValue($param = 'lang', $part = 'main')
    {
        $value = get_param($param, self::getOption($part, 'lang_value'));
        return $value;
    }

    static function swf($src, $params = NULL, $flashVars = NULL)
    {
        global $g;

        $attributes = '';
        $paramsFlashVars = '';
        $paramsObject = self::swfPrepareParam('movie', $src);
        $paramsEmbed  = self::swfPrepareParam('src', $src, false);

        if (Common::isValidArray($params)) {
            foreach ($params as $keys => $param) {
                $type = $keys;
                foreach ($param as $key => $value) {
                    if ($type == 'main') {
                        $paramsObject .= self::swfPrepareParam($key, $value);
                        $paramsEmbed  .= self::swfPrepareParam($key, $value, false);
                    } else {
                        $attributes  .= self::swfPrepareParam($key, $value, false);
                    }
                }
            }
        }

        if (Common::isValidArray($flashVars)) {

            if(isset($flashVars['lang'])) {
                $flashVars['lang'] = str_replace('{lang}', Common::getOption('lang_loaded', 'main'), $flashVars['lang']);
            }

            foreach ($flashVars as $key => $value) {
                if ($key == 'dateFormat'){
                $paramsFlashVars  .= self::swfPrepareParam($key, $g['date_formats']['flashchat_message_datetime'], false, '&', '');
                } else {
                $paramsFlashVars  .= self::swfPrepareParam($key, $value, false, '&', '');
                }
            }
            $paramsFlashVars = substr($paramsFlashVars, 0, -1);
        }

        $object =  "<object classid=\"clsid:d27cdb6e-ae6d-11cf-96b8-444553540000\"
                    codebase=\"http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0\" "
                    . $attributes . ">\r"
                    . $paramsObject
                    . "<param name = \"FlashVars\" value=\"" . $paramsFlashVars . "\"/>\r";
        $embed  =  "<embed type=\"application/x-shockwave-flash\" pluginspage=\"http://www.macromedia.com/go/getflashplayer\" "
                    . $paramsEmbed . ' ' . $attributes . "\r flashvars=\"" . $paramsFlashVars . "\"/>\r";

        return $object . $embed . "</object>";
    }

    static function swfPrepareParam($name, $value, $object = true, $separator = ' ', $quote = '"')
    {
        return ($object) ? "<param name=\"" . $name . "\" value=\"" . $value . "\"/>\r" : $name . '=' . $quote . $value . $quote . $separator;
    }

    static function getUrlAbsolute()
    {
        return self::urlSite();
    }

    static function colorLuminance($hex, $lum = 0, $darker = true, $percent = true)
    {
        if ($percent === true) {
           $lum =  $lum/100;
        }
        if ($darker === true) {
            $lum =  -1*$lum;
        }
        // validate $hex string
        $hex = preg_replace('/[^0-9a-f]/i', '', $hex);
        if (mb_strlen($hex) < 6) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        // convert to decimal and change luminosity
        $rgb = "#";
        for ($i = 0; $i < 3; $i++) {
            $color = intval(mb_substr($hex, $i*2, 2), 16);
            $color = $color + ($color * $lum);
            $color = ($color > 0) ? $color : 0;
            $color = ($color < 255) ? round($color) : 255;
            $color = dechex($color);
            $rgb .= mb_substr('00' . $color, mb_strlen($color));
        }
        return $rgb;
    }

    static function grabsTags($text, $tags = NULL)
    {
        if ($tags === NULL)
            $tags = array('{youtube:', '{img:', '{site:', '{vimeo:', '{metacafe:');

        $grabs = array();
        foreach ($tags as $start) {
           $grabs = array_merge($grabs, grabs($text, $start, '}', true));
        }
        return $grabs;
    }

    static function getTextTagsToBr($text, $tag, $tagHtml)
    {
        $lengthText = mb_strlen($text, 'UTF-8');
        $lengthTag  = mb_strlen($tag, 'UTF-8');
        $lengthTagHtml = mb_strlen($tagHtml, 'UTF-8');
        $parseBr = !Common::isOptionActiveTemplate('get_text_tags_to_br_no_parse_br');
        while (($offset = mb_strpos($text, $tag, 0, 'UTF-8'))!== FALSE){
            $offsetTag = $offset + $lengthTag;
            $transferOne = preg_match('/\\n/', mb_substr($text, $offsetTag, 1, 'UTF-8'));
            $transferTwo = preg_match('/\\r\\n/', mb_substr($text, $offsetTag, 2, 'UTF-8'));
            if ($parseBr) {
                $br = ($offsetTag == $lengthText || !empty($transferOne) || !empty($transferTwo)) ? '' : '<br>';
            } else {
                $br = '';
            }
            $text = mb_substr($text, 0, $offset, 'UTF-8') . $tagHtml . $br . mb_substr($text, $offsetTag, $lengthText, 'UTF-8');
            $offset = $offset + $lengthTagHtml;
            $lengthText = mb_strlen($text, 'UTF-8');
            if ($offset > $lengthText) break;
        }

        return $text;
    }

    static function findExistsTmpl($part, $path = true)
    {
        $tmpl = false;
        $tmplDir = Common::getOption('dir_tmpl', 'path') . $part . '/';

        $openDir = opendir($tmplDir);

        while(false !== ( $dir = readdir($openDir)) ) {
            if ($dir != '.' && $dir != '..' && is_dir($tmplDir . '/' . $dir)) {
                if($path) {
                    $tmpl = $tmplDir. $dir;
                } else {
                    $tmpl = $dir;
                }
                break;
            }
        }
        closedir($openDir);

        return $tmpl;
    }

    static function getFolderTitle($name, $count, $tmplBox, $tmplCounter, $nameItemBox)
    {
        $countTitle = ($count > 0) ? lSetVars($tmplCounter, array('count' => $count)) : '';
        $vars = array($nameItemBox => strip_tags($name), 'counter' => $countTitle);
        $title = lSetVars($tmplBox, $vars);

        return $title;
    }

    static function getDateTemplate($date, $template = 'im_msg_date')
    {
        $vars = array('number' => date('j', $date),
                      'month' => l('m_' . date('n', $date)),
                      'year' => date('Y', $date)
                );

        return lSetVars('im_msg_date', $vars);
    }

    static function faviconName()
    {
        $name = 'favicon';
        if(Common::isMultisite()) {
            $name = 'f';
        }

        return $name;
    }

    static function getFaviconSiteHtml()
    {
        global $g;

        $html = (Common::getOption('site_part', 'main') === 'administration' ? '' : Common::getOption('meta_tags', 'main'));
        $faviconFileName = self::getFaviconFilename();

        if($faviconFileName) {
            $html .= '<link rel="shortcut icon" href="' . $g['path']['url_files'] . $faviconFileName . '?v=' . filemtime($g['path']['dir_files'] . $faviconFileName) . '" type="image/x-icon">';
        }

        return $html;
    }

    static function getFaviconFilename()
    {
        global $g;
        global $sitePart;

        $faviconName = self::faviconName();

        $faviconTypes = array(
            '',
            '_' . (IS_DEMO && ($sitePart == 'main' || $sitePart == 'mobile') ? str_replace('_mobile', '', $g['tmpl']['tmpl_loaded']) : Common::getOption('main', 'tmpl')),
            '_all',
        );

        $faviconFileNameResult = false;

        foreach($faviconTypes as $faviconType) {
            $faviconFileName = $faviconName . $faviconType . '.ico';
            $faviconFile = $g['path']['dir_files'] . $faviconFileName;
            if (file_exists($faviconFile)) {
                $faviconFileNameResult = $faviconFileName;
                break;
            }
        }

        return $faviconFileNameResult;
    }

    static function getUrlLogo($name = 'logo', $part = 'main', $particle = '', $isParticleSet = false)
    {
        global $g;

        $url = '';
        if ($particle != '') {
            $particle = '_' . $particle;
        }
        $typeAllowed = array('png', 'svg');
        foreach ($typeAllowed as $ext) {
            $fileName = 'logo/' . $g['main']['site_part'] . '_' . $g['tmpl']['tmpl_loaded'] . "{$particle}.{$ext}";
            if (file_exists($g['path']['dir_files'] . $fileName)) {
                $url = $g['path']['url_files'] . $fileName;
            }
        }
        if (!$url) {
            $patchDir = array('images', 'img');
            $typeImage = array('png', 'gif', 'svg');

            $logoDemoPrefixes = array('');

            if(IS_DEMO) {
                Demo::setLogoData($logoDemoPrefixes, $patchDir);
            }

			if (!$isParticleSet) {
				$particle = '';
			}

            foreach($logoDemoPrefixes as $logoDemoPrefix) {

				foreach ($patchDir as $patchV) {
					foreach ($typeImage as $ext) {
						if($g['multisite']) {
							$name = $name . '_joomph';
						}
						$fileName = $patchV . '/' . $logoDemoPrefix . $name . $particle . '.' . $ext;
						if (file_exists($g['tmpl']['dir_tmpl_' . $part] . $fileName)) {
							$url = $g['tmpl']['url_tmpl_' . $part] . $fileName;
							break(2);
						}
					}
				}

			}
        }
        if ($url != '') {
            $url .= self::getVersionFile($url);
        }

        return $url;
    }

    static function parseSizeParamLogo(&$html, $block, $url)
    {
        if ($html->blockExists("{$block}_params")) {
            $url = explode('/', $url);
            $fileLogo = end($url);
            $fileLogo = explode('?', $fileLogo);
            if (!isset($fileLogo[0])) {
                return;
            }
            $fileLogo = $fileLogo[0];
            $isSetParams = false;
            $logosSizeparams = Common::getOption('logos_size_params', 'image');
            if ($logosSizeparams !== null) {
                $logosSizeparams = json_decode($logosSizeparams, true);
                if (is_array($logosSizeparams)) {
                    if (isset($logosSizeparams[$fileLogo])) {
                        $isSetParams = true;
                        $widthLogo = $logosSizeparams[$fileLogo]['w'];
                        $heightLogo = $logosSizeparams[$fileLogo]['h'];
                    }
                }
            }
            if (!$isSetParams) {
                $widthLogo = Common::getOption("{$block}_w", 'template_options');
                $heightLogo = Common::getOption("{$block}_h", 'template_options');
            }
            if ($widthLogo && $heightLogo) {
                $html->setvar("{$block}_width", $widthLogo);
                $html->setvar("{$block}_height", $heightLogo);
                $html->parse("{$block}_params", false);
            }
        }
    }

    static function getUrlLogoAutoMail()
    {
        global $g;

        $url = '';
        $fileName = 'logo/administration_' . $g['tmpl']['administration'] . '_auto_mail.png';
        $fileNameSvg = 'logo/administration_' . $g['tmpl']['administration'] . '_auto_mail.svg';
        $patchFile = $g['path']['dir_files'] . $fileName;
        $patchFileSvg = $g['path']['dir_files'] . $fileNameSvg;
        if (file_exists($patchFile)) {
            $url = str_replace($g['path']['dir_main'], '', $patchFile);
        } elseif (file_exists($patchFileSvg)) {
            $url = str_replace($g['path']['dir_main'], '', $patchFileSvg);
        } else {

            $name = 'logo_auto_mail';

            if(Common::isMultisite()) {
                $name = $name . '_joomph';
            }

            $patchFile = $g['tmpl']['dir_tmpl_administration'] . 'images/' . $name . '.png';
            if (file_exists($patchFile)) {
               $url = str_replace($g['path']['url_main'], '', $g['tmpl']['url_tmpl_administration'] . 'images/' . $name . '.png');
            }
        }
        if ($url != '') {
            $url = Common::urlSiteSubfolders() . $url . self::getVersionFile($patchFile);
        }

        return $url;
    }

    static function getVersionFile($path)
    {
        $result = '';
        if ($path != '' && file_exists($path) && is_readable($path)) {
            $result = '?v=' . filemtime($path);
        }
        return $result;
    }

    static function saveFileSize($path = null, $add = true, $update = true)
    {
        $fileSize = 0;
        if ($path !== null) {
            if (is_array($path) && count($path) > 0) {
                foreach ($path as $file) {
                    $fileSize += self::getFileSize($file);
                }
            } else {
                $fileSize = self::getFileSize($path);
            }
        }
        if ($fileSize > 0) {
            $fileSizeConfig = self::getOption('files_size', 'main');
            if ($add) {
                $fileSize = $fileSizeConfig + $fileSize;
            } else {
                $fileSize = $fileSizeConfig - $fileSize;
            }
            if ($fileSize < 0) {
                $fileSize = 0;
            }
            Config::update('main', 'files_size', $fileSize, $update);
        }

    }

    static function getFileSize($file = '')
    {
        if ($file != '' && file_exists($file)) {
            return filesize($file);
        } else {
            return 0;
        }
    }

    static function existsFilesUserDelete($path = null)
    {
        if ($path !== null) {
            if (is_array($path) && count($path) > 0) {
                foreach ($path as $file) {
                    self::existsOneFileUserDelete($file);
                }
            } else {
                self::existsOneFileUserDelete($file);
            }
        }
    }

    static function existsOneFileUserDelete($file)
    {
        if (file_exists($file)) {
            self::saveFileSize($file, false);
            unlink($file);
        }
    }

    static function isLoadTemplate()
    {
        global $g;

        $sitePart = $g['main']['site_part'];
        $dirTmpl = $g['tmpl']['dir_tmpl_' . $sitePart];
        if (is_dir($dirTmpl)) {
            return true;
        }
        return false;
    }

    static function setTemplateNotIs($sitePart)
    {
        global $g;

        check_template_settings_status();

        if (!self::isLoadTemplate()) {
            $dirTmpl = $g['path']['dir_tmpl'] . $sitePart;
            $tmpl = array();
            $tmpl = scandir($dirTmpl);

            unset($tmpl[0]);
            unset($tmpl[1]);

            foreach ($tmpl as $item) {
                $patch = $dirTmpl . '/' . $item;
                if (is_dir($patch)) {
                    Config::update('tmpl', $sitePart, $item);
                    break;
                }
            }
        }
    }

    static function isMultisite()
    {
        global $g;
        return $g['multisite'] != '';
    }

    static function itemDateFormat($date, $format = 'common_item_date_format', $formatCurrentYear = 'common_item_date_format_year')
    {
        global $g;
        $format = $g['date_formats'][$format];
        $formatCurrentYear = $g['date_formats'][$formatCurrentYear];
        if ($formatCurrentYear && date('Y') == pl_date('Y', $date)) {
            $format = $formatCurrentYear;
        }

        return pl_date($format, $date);
    }

    static function isWallActive()
    {
        global $p;
        $isActive = Common::getOption('home_page_mode') != 'dating';
        if (Common::getOption('set', 'template_options') == 'urban') {
            $isActive = Common::isOptionActive('wall_enabled');
        }
        return $isActive;
    }

    static function validateField($fieldName, $fieldData)
    {
        if (DB::result("SELECT `user_id` FROM `user` WHERE ".$fieldName." = " . to_sql($fieldData, "Text")) > 0) {
           return true;
        }
    }
    static function uniqueNameSEO($name)
    {
        $name_seo = Router::prepareNameSeo($name);

        for($i=1; $i<100; $i++) {
            if(Common::validateField('name_seo', $name_seo))
                $name_seo = Router::prepareNameSeo($name.' '.$i);
            else
                return $name_seo;
        }
    }

    static function validateEmail($email)
    {
       $maxLength = Common::getOption('mail_length_max');
       $pattern = "/^[a-zA-Z\-_\.\+0-9]{1," . $maxLength . "}@[a-zA-Z\-_\.0-9]{1," . $maxLength . "}\.[a-zA-Z\-_\.0-9]{1," . $maxLength . "}$/";

       if ($email == ''
            || mb_strlen($email, 'UTF-8') > $maxLength
            || !preg_match($pattern, $email)) {
            return false;
       } else {
           return true;
       }
    }

    static function sendWink($to, $from = null)
    {
        global $g_user;

        if($from === null) {
            $from = $g_user;
        }

        $response = false;
        $table = 'users_interest';
        if ($from && isset($from['user_id']) && isset($from['name']) && $to) {

            $where = 'user_from = ' . to_sql($from['user_id'], 'Number') . '
                AND user_to = ' . to_sql($to, 'Number');
            DB::delete($table, $where);

            DB::insert($table, array('user_from' => $from['user_id'], 'user_to' => $to, 'date' => date('Y-m-d H:i:s')));
            CStatsTools::count('winks_sent');

            $sql = 'UPDATE user SET new_interests = (
                    SELECT COUNT(*) FROM ' . $table . '
                        WHERE new = "Y"
                            AND user_to = ' . to_sql($to, 'Number') . '
                    )
                WHERE user_id = ' . to_sql($to, 'Number');
            DB::execute($sql);
            $response = true;

            if (Common::isEnabledAutoMail('interest')) {
                $row = DB::row('SELECT * FROM user WHERE user_id = ' . to_sql($to, 'Number'));
                if ($row) {
                    if ($row['set_email_interest'] != '2') {
                        $vars = array(
                            'title' => Common::getOption('title', 'main'),
                            'name' => $from['name'],
                            'uid' => $from['user_id']
                        );
                        Common::sendAutomail($row['lang'], $row['mail'], 'interest', $vars);
                    }
                }
            }
        }
        return $response;
    }

    static function sendMailByAdmin($uid, $toName, $type, $admin = false)
    {
        if (Common::isEnabledAutoMail($type)) {
            $uidAdmin = DB::result('SELECT `user_id` FROM `user` WHERE `admin` = 1');
            if (!$uidAdmin) {
                return;
            }
            if (Common::getOption('set', 'template_options') == 'urban') {
                global $g_user;
                $g_user['welcoming_message_sender'] = 1;
                $g_user['user_id'] = $uidAdmin;
                $g_user['name'] = DB::result('SELECT `name` FROM `user` WHERE `user_id` = ' . to_sql($uidAdmin));
                $sendId = CIm::addMessageToDb($uid, 'welcoming_message', null, 1, 0, true, false, 1);
                if ($sendId) {
                    User::update(array('welcoming_message_notify' => 1), $uid);
                    if (Common::isEnabledAutoMail('new_message')
                        && (User::isOptionSettings('set_notif_new_msg') || $admin)) {
                        $vars = array('title' => Common::getOption('title', 'main'),
                                      'name' => $toName,
                                      'name_sender'  => $g_user['name'],
                                      'uid_sender' => $g_user['user_id'],
                                      'uid' => $uid,
                                      'group_id_sender' => 0,
                                      'url_site' => Common::urlSite());
                        $userInfo = User::getInfoBasic($uid);
                        Common::sendAutomail($userInfo['lang'], $userInfo['mail'], 'new_message', $vars);
                    }
                }
            } else {
                $_GET['user_from'] = $uidAdmin;
                $_GET['user_to'] = $uid;
                $_GET['save'] = 1;
                $vars = array('name' => $toName);
                $emailAuto = Common::sendAutomail(Common::getOption('lang_loaded', 'main'), '', $type, $vars, false, DB_MAX_INDEX, true);
                $_GET['subject'] = $emailAuto['subject'];
                $_GET['text'] = $emailAuto['text'];
                self::sendMail(true, $type);
            }
        }
    }

    static function sendMail($admin = false, $typeAutoMail = 'mail_message')
    {
        global $g, $g_user;

        $message = '';

        $type = get_param('type');// Для админа 'plain'
        $subject = get_param('subject');
        $text = get_param('text');
        $isSave = get_param('save');
        $userTo = get_param('user_to');
        $userFrom = get_param('user_from');

        $subject = trim(Common::filterProfileText(strip_tags($subject)));
        if (!$subject) {
            $subject = l('no_subject');
        }

        $text = Common::filterProfileText($text);
        if ($type == 'postcard') {
            $text = urldecode($text);
        }
        $text = trim(strip_tags($text));

        if ($userTo && $userFrom && $subject && $text)
        {
            $id = $userTo;

            if ($admin) {
				$g_user['user_id'] = $userFrom;
				$g_user['name'] = DB::result('SELECT `name` FROM `user` WHERE `user_id` = ' . to_sql($id, 'Number'));
                $textHash = md5(time());
            } else {
                $textHash = md5(mb_strtolower($text, 'UTF-8'));
                if (User::isBanMails($textHash) || User::isBanMailsIp()) {
                    redirect('ban_mails.php');
                }
            }

            $block = 0;
            if (!$admin) {
                $block = User::isBlocked('mail', $id, $g_user['user_id']);
            }

            $to_myself = (guid() == to_sql($id, "Number"));
            $empty_text = (trim(get_param('text')) == '');

            if ($id && !$block && !$to_myself && !$empty_text)
            {
                $idMailFrom = 0;
                $sqlInto = '';
                $sqlValue = '';
                if ($type != 'postcard') {
                    $sqlInto = ', text_hash';
                    $sqlValue = ', ' . to_sql($textHash);
                }
                if ($isSave == '1') {
                    $sql = "INSERT INTO
                            mail_msg (user_id, user_from, user_to, folder, subject, text, date_sent, new, type, receiver_read" . $sqlInto . ")
							VALUES(
							" . $g_user['user_id'] . ",
							" . $g_user['user_id'] . ",
							" . to_sql($id, 'Number') . ",
							" . 3 . ",
							" . to_sql($subject) . ",
							" . to_sql($text) . ",
							" . time() . ",
							'N',
							" . to_sql($type) . ",
                            'N'" . $sqlValue . ")";
                    DB::execute($sql);
                    $idMailFrom = DB::insert_id();
                }

                $sql = "INSERT INTO
                        mail_msg (user_id, user_from, user_to, folder, subject, text, date_sent, type, receiver_read, sent_id" . $sqlInto . ")
						VALUES(
						" . to_sql($id, "Number") . ",
						" . $g_user['user_id'] . ",
						" . to_sql($id, "Number") . ",
						" . 1 . ",
						" . to_sql($subject) . ",
						" . to_sql($text) . ",
						" . time() . ",
						" . to_sql($type) . ",
                        'N',
                        " . to_sql($idMailFrom, 'Number') . $sqlValue . ")";

                DB::execute($sql);
                $idMailTo = DB::insert_id();

                $sql = "UPDATE user
                           SET new_mails = new_mails+1
                         WHERE user_id = " . to_sql($id, 'Number');
                DB::execute($sql);
                CStatsTools::count('mail_messages_sent');
                User::updateActivity($id);

                if (Common::isEnabledAutoMail($typeAutoMail)) {
                    $sql = 'SELECT * FROM `user` WHERE `user_id` = ' . to_sql($id, 'Number');
                    DB::query($sql);
                    if ($row = DB::fetch_row()) {
                        if ($row['set_email_mail'] != '2'){
                            $textMail = Common::isOptionActive('mail_message_alert') ? $text : '';
                            $vars = array('title' => $g['main']['title'],
                                          'name'  => $g_user['name'],
                                          'text'  => $textMail,
                                          'mid' => $idMailTo,
                                          'user_id' => $id);
                            Common::sendAutomail($row['lang'], $row['mail'], $typeAutoMail, $vars);
                        }
                    }
                }

                if (!$admin) {
                    $message_sent = true;
                    if ($message_sent) {
                        $to = get_param('page_from', '');
                        set_session('send_message', true);
                        redirect($to);
                    }
                }

            } elseif ($block) {
                    $message = l('You are in Block List') . '<br>';
            } elseif ($to_myself) {
                    $message = l('You can not do this with yourself!') . '<br>';
            } elseif ($empty_text) {
                    $message = l('Message text is empty!') . '<br>';
            } else {
                    $message = l('Incorrect Username') . '<br>';
            }
        } else {
            $message = l('Incorrect Username, subject or message' . '<br>');
        }

        return $message;
    }

    static function isParseModule($name)
    {
        $notDisplay = Common::getOption('not_display_module', 'template_options');
        if (is_array($notDisplay)) {
            return !in_array($name, $notDisplay);
        } else {
            return true;
        }
    }

    static function isAllowedModuleTemplate($name)
    {
        $modules = Common::getOption('display_module', 'template_options');
        if (is_array($modules)) {
            return in_array($name, $modules);
        } else {
            return false;
        }
    }

    static function parseDropDownListLanguage(&$html, $block = 'view', $blockLang = 'language', $part = 'main')
    {
        global $g;

        $langs = Common::listLangs($part);
        $isParse = false;
        if ($langs && count($langs) > 1) {
            $languageCurrent = Common::getOption('main','lang_value');
            if (!isset($langs[$g['lang_loaded']])) {
                redirect('index.php?set_language=' . Common::getOption('main', 'lang_value'));
                return;
            }
            $html->setvar("{$blockLang}_current", $langs[$g['lang_loaded']]);
            if ($html->varExists("{$blockLang}_bright")) {
                $uid = User::getRequestUserId();
                if (!$uid) {
                    $uid = guid();
                }
                $bg = User::getInfoBasic($uid, 'profile_bg');
                if (!empty($bg)) {
                    $linkColor = cache("profile_bg_link_color_bright_{$bg}", 7 * 24 * 60);
                    if ($linkColor === false) {
                        $image = $g['tmpl']['dir_tmpl_main'] . '/images/patterns_sm/' . $bg;
                        $linkColor = changeLinkColorOfBackgroundBright($image);
                        cache_update("profile_bg_link_color_bright_{$bg}", $linkColor);
                    }
                    if  (!intval($linkColor)) {
                        $html->setvar("{$blockLang}_bright", 'white');
                    }
                }
            }
            $urlPageParams = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
            if($urlPageParams != '') {
                $html->setvar('url_page_params', del_param('set_language', $urlPageParams, true, true));
            }

            //$langs = self::setFirstCurrentLanguage($langs);
            //$langs = Common::sortingLangsList($langs, 'main');

            foreach ($langs as $file => $title) {
                $html->setvar("{$blockLang}_value", $file);
                $html->setvar("{$blockLang}_title", $title);
                if ($g['lang_loaded'] == $file)
                    $html->setvar("{$blockLang}_class", 'selected');
                else
                    $html->setvar("{$blockLang}_class", '');
                $html->parse($blockLang, true);
            }
            $html->parse($block, true);
            $isParse = true;
        }
        return $isParse;
    }

    static function filter_text_to_db($v, $parse_media = true, $old_text = null, $validTags = false)
    {
        if ($parse_media) {
    	   $v = VideoHosts::filterToDb($v);
    	   $v = OutsideImages::filter_to_db($v, $old_text);
        }
        $v = str_replace("\r\n", "\n", $v);
        $v = str_replace("\r", "\n", $v);

        $validTags = ($validTags) ? self::ALLOWTAGS : null;
        $v = strip_tags_attributes($v, $validTags);
        $v = trim($v);

        return $v;
    }

    static function getLinkHtml($url, $isTarget = false, $attr = array())
    {
        global $g;

        $target = '';
        if ($isTarget) {
            $target = 'target="_blank"';
        }
        $attrLink = '';
        if (!empty($attr) && is_array($attr)) {
            foreach ($attr as $key => $value) {
                $attrLink .= $key . '="' . $value . '" ';
            }
        }
        $url = '<a ' . $attrLink . $target . ' href="' . $g['path']['url_main'] . $url . '">';
        return $url;
    }

    /*
     * @param string $fn the name of a language functions(l, toAttrL, toJsL).
     */
    static function lSetLink($s, $vars, $isTarget = false, $indx = '', $attr = array(), $fn = 'l', $isCascade = true)
    {
        if (isset($vars['url'])) {
            $vars['link_start' . $indx] = self::replaceByVars(self::getLinkHtml($vars['url'], $isTarget, $attr), $vars);
        }
        $vars['link_end' . $indx] = '</a>';
        if ($isCascade) {
            $result = lSetVarsCascade($s, $vars);
        } else {
            $result = Common::replaceByVars($fn($s), $vars);
        }
        return $result;
    }

    static function parseCaptcha(&$html)
    {
        global $g;
        if (Common::isOptionActive('recaptcha_enabled')) {
            $block = 're_captcha';
            $html->setvar($block . '_lang', self::getLocaleCode());
            $theme = Common::getOption('recaptcha_theme');
            $html->setvar($block . '_theme', $theme);
            $siteKey = Common::getOption('recaptcha_site_key');
            $html->setvar($block . '_class', 'recaptcha_bl');
            $html->setvar($block . '_sitekey', $siteKey);
            $html->parse($block . '_js', false);
            $html->parse($block . '_script', false);
            $html->parse($block, false);
        } else {
            if ($html->varExists('tmpl_set_captcha')) {
                $html->setvar('tmpl_set_captcha', Common::getOption('set', 'template_options'));
            }
            if(!function_exists('parseParam')) {
                function parseParam(&$html, $name){
                    $param = Common::getOption($name, 'template_options');
                    if ($param && $html->varExists($name)) {
                        $html->setvar($name, "&{$name}={$param}");
                    }
                }
            }
            $params = array('width_captcha', 'height_captcha');
            foreach ($params as $name) {
                parseParam($html, $name);
            }
            $html->setvar('sid', time());
            $html->parse('default_captcha_js', true);
            $html->parse('default_captcha', false);
        }

    }

    static function getWhereSearchLocation($user, $tableUser = 'u')
    {
        $where = '';
        if ($user['country'] != 0){
            $where .= " AND {$tableUser}.country_id=" . $user['country'];
        }
        if ($user['state'] != 0){
            $where .= " AND {$tableUser}.state_id=" . $user['state'];
        }
        if ($user['city'] != 0){
            $where .= " AND {$tableUser}.city_id=" . $user['city'];
        }

        return $where;
    }

    static function getLanguageCode($langName)
    {
        $lang=loadLanguageSite($langName);
        return l('language_code',$lang);
    }

    static function getLocaleCode($lCode='')
    {
        if($lCode==''){
            $lCode = l('language_code');
        }

        $lCodeParts = explode('-', $lCode);
        if(isset($lCodeParts[1])) {
            $lCodeParts[1] = strtoupper($lCodeParts[1]);
        }

        $lCodeValue = implode('_', $lCodeParts);

        return $lCodeValue;
    }

    static function getLocaleShortCode($lCode='')
    {
        if($lCode==''){
            $lCode = l('language_code');
        }

        $lCodeParts = explode('-', $lCode);

        return $lCodeParts[0];
    }

    static function sortArrayByLocale($array)
    {
        if(class_exists('Collator', false)) {
            $collator = new Collator(Common::getLocaleCode());
            $collator->asort($array);
        } else {

            static $mbEncodings = false;

            if($mbEncodings === false) {
                $mbEncodings = mb_list_encodings();
            }

            $localeCurrent = setlocale(LC_COLLATE, 0);

            $localeWindows = l('locale_windows');
            if($localeWindows == 'locale_windows') {
                $localeWindows = false;
            }

            $isLinux = false;
            if(DIRECTORY_SEPARATOR === '/') {
                $isLinux = true;
            }

            $arraySrc = $array;

            $lcCollate = Common::getLocaleCode();

            if($isLinux) {
                $lcCollate .= '.UTF-8';
            } else {
                if($localeWindows && is_array($mbEncodings) && in_array($localeWindows, $mbEncodings)) {
                    foreach($array as $key => $value) {
                        $array[$key] = mb_convert_encoding($value, $localeWindows, 'utf-8');
                    }
                }
            }

            setlocale(LC_COLLATE, $lcCollate);
            if($isLinux || !DEV_PROFILING) {
                global $g;

                $compareFunction = 'strcoll';
                if($g['php'] >= 7) {
                    $compareFunction = 'strcmp';
            }

                uasort($array, $compareFunction);
            }
            setlocale(LC_COLLATE, $localeCurrent);

            if(!$isLinux && $localeWindows) {
                //$array = array_merge($array, $arraySrc);
                foreach($array as $key => $item) {
                    if(!$isLinux && $localeWindows) {
                        $array[$key] = $arraySrc[$key];
                    }
                }
            }

        }
        return $array;
    }

    static function isAvailableFeaturesSuperPowers()
    {
        $typePaymentFeatures = Common::getOption('type_payment_features', 'template_options');
        $typePaymentFeatures = '%' . $typePaymentFeatures . '%';
        $where = '`status` = 1 AND `type` LIKE ' . to_sql($typePaymentFeatures);
        return DB::count('payment_features', $where, '', 1);
    }

    static function isAvailableFeaturesSuperPowersMobile()
    {
        $typePaymentFeatures = Common::getOption('type_payment_features', 'template_options');
        $typePaymentFeatures = '%' . $typePaymentFeatures . '%';
        $where = "`status` = 1 AND `alias` != 'extended_search' AND `type` LIKE " . to_sql($typePaymentFeatures);
        return DB::count('payment_features', $where, '', 1);
    }

    static function isActiveFeatureSuperPowers($feature)
    {
		$typePaymentFeatures = Common::getOption('type_payment_features', 'template_options');
        $typePaymentFeatures = '%' . $typePaymentFeatures . '%';
		$sql = 'SELECT `status`
				  FROM `payment_features`
				 WHERE `alias` = ' . to_sql($feature) .
				 ' AND `type` LIKE ' . to_sql($typePaymentFeatures);
        return intval(DB::result($sql, 0, DB_MAX_INDEX, true));
    }

    static function parseBlockBtnDownloadApp(&$html, $app, $block, $class = array())
    {
        $isParse = false;
        if ($html->blockExists($block) && Common::isOptionActive("app_{$app}_active")){
            $cl = $app;
            if (isset($class[$app])) {
                $cl = $class[$app];
            }
            $html->setvar("{$block}_class", $cl);
            $html->setvar("{$block}_url", Common::getOption("app_{$app}_url"));
            $html->setvar("{$block}_icon", $app);
            $html->setvar("{$block}_title", l("btn_title_{$app}"));
            $html->parse($block, true);
            $isParse = true;
        }
        return $isParse;
    }

    static function parseBtnDownloadApp(&$html, $position = null, $class = array())
    {
        global $p;

        if ($position === null) {
            $position = Common::getOption('app_btn_position');
            $optionTmplSet = Common::getOption('set', 'template_options');
            $optionTmplPosition = Common::getOption('app_btn_position', 'template_options');
            if ($optionTmplPosition) {
                $position = $optionTmplPosition;
            } elseif ($optionTmplSet == 'urban') {
                if (guid()) {
                    $position = 'bottom';
                } elseif ($p == 'index.php' && $position == 'bottom') {
                    $position = 'bottom_login_form';
                } else {
                    $position = 'top';
                }
            }
        }
        $blockBtn = "btn_download_app_{$position}";
        $isParseBlock = false;
        if ($html->blockExists($blockBtn)) {
            $blockBtnItem = "{$blockBtn}_item";
            $appList = array('ios', 'android');
            foreach ($appList as $app) {
                $isParse = self::parseBlockBtnDownloadApp($html, $app, $blockBtnItem, $class);
                $isParseBlock = $isParseBlock || $isParse;
            }
            if ($isParseBlock) {
                $html->parse($blockBtn, false);
            }
        }
        return $isParseBlock;
    }

    static function parseMobileBtnDownloadApp(&$html, $block = 'btn_download_app')
    {
        $os = self::getMobileOs();
        $method = "isApp{$os}";
        $isParse = false;
        if ($os && !self::$method()) {
            self::parseBlockBtnDownloadApp($html, $os, $block);
            $isParse = true;
        }
        return $isParse;
    }

    static function getSeoSite($urls, $uid, $userInfo = null, $noPlaceCity = false, $groupId = false)
    {
        global $g, $p;

        $vars = array('site_title' => $g['main']['title']);
        //"SELECT * FROM `seo` WHERE `lang` = 'russian' AND `default` = 1 AND `url` = 'profile_group'"
        if ($groupId) {
            $groupInfo = Groups::getInfoBasic($groupId);
            if ($groupInfo) {
                $uid = 0;
                $vars = array('title' => $groupInfo['title'],
                              'description' => $groupInfo['description']
                        );
            } else {
                $urls = 'profile';
            }
        }

        if(!is_array($urls)) {
            $urls = array($urls);
        }

        if ($uid) {
            if ($userInfo === null) {
                $userInfo = User::getInfoBasic($uid);
            }
            $vars = array('site_title' => $g['main']['title'],
                          'name'       => $userInfo['name'],
                          'age'        => $userInfo['age'],
                          'location'   => $userInfo['city'] ? l($userInfo['city']) : l($userInfo['country'])
            );
        }
        if (in_array('3dcity', $urls) && !$noPlaceCity) {
            $vars['place'] = City::getSeoTitlePlace(get_param('place'));
        }

        $langLoad = Common::getOption('lang_loaded', 'main');

        //var_dump_pre($urls);

        // For compatibility:
        // first of all - new
        // then - old

        foreach($urls as $url) {

            $sql = 'SELECT * FROM `seo`
                     WHERE `url` = ' . to_sql($url, 'Text') . '
                       AND `lang` = ' . to_sql($langLoad) . '
                       AND `default` = 0';

            $seo = DB::row($sql, 0, true);

            if($seo) {
                //echo "Found: $url<br>";
                break;
            }
        }

        if (!$seo) {
            // get default for this language
            $url = array_pop($urls);

            if ($url == '3dcity') {
                $title = $g['main']['title'] . '.' . l('3dcity');
                $seo = array('title' => $title,
                             'description' => $title,
                             'keywords' => $title);
            } else {
                $where = '`lang` = ' . to_sql($langLoad) . ' AND `default` = 1';
                if ($url == 'profile' || $url == 'profile_group') {
                    $where .= ' AND `url` = ' . to_sql($url);
                } else {
                    $where .= ' AND `url` = ""';
                }
                $sql = 'SELECT * FROM `seo` WHERE ' . $where;
                $seo = DB::row($sql, 0, true);
            }
            if (!$seo) {
                // get default config
                $option = 'seo';
                if ($url == 'profile' || $url == 'profile_group') {
                    $option = "seo_{$url}";
                }
                $seo = $g[$option];
            }
            if (isset($l[$p]['header_title']) && $l[$p]['header_title'] != '') {
                $seo['title'] = $l[$p]['header_title'];
            }
        }
        if ($seo['title']) {
            $seo['title'] = Common::replaceByVars($seo['title'], $vars);
        }
        if ($seo['description']) {
            $seo['description'] = Common::replaceByVars($seo['description'], $vars);
            $seo['description'] =  htmlspecialchars($seo['description'], ENT_QUOTES, 'UTF-8');
        }
        if ($seo['keywords']) {
            $seo['keywords'] = Common::replaceByVars($seo['keywords'], $vars);
            $seo['keywords'] = htmlspecialchars($seo['keywords'], ENT_QUOTES, 'UTF-8');
        }
        return $seo;
    }

    static function parseSeoSite(&$html)
    {
        global $g, $p;

        if (isset($g['main']['description'])) {
            $seo['title'] = $g['main']['title'];
            $seo['description'] = $g['main']['description'];
            $seo['keywords'] = '';
            if (isset($g['main']['keywords'])) {
                $seo['keywords'] = $g['main']['keywords'];
            }
            $seo['description'] =  htmlspecialchars($seo['description'], ENT_QUOTES, 'UTF-8');
            $seo['keywords'] = htmlspecialchars($seo['keywords'], ENT_QUOTES, 'UTF-8');
        } else {
            $queryString = isset($_SERVER['QUERY_STRING']) ? trim($_SERVER['QUERY_STRING']) : '';
            if (isset($_GET['router_query_string'])) {
                $queryString = $_GET['router_query_string'];
            }

            $mobile = '';
            if (Common::isMobile()) {
                $mobile = MOBILE_VERSION_DIR . '/';
            }

            if ($queryString) {
                $delParams = array('set_template',
                                   'set_template_runtime',
                                   'set_template_mobile',
                                   'set_template_mobile_runtime',
                                   'site_part_runtime',
                                   'upload_page_content_ajax',
                                   'ajax',
                                   'site_guid',
                                   'name_seo');
                foreach ($delParams as $par) {
                    $queryString = del_param($par, $queryString, true, false);
                    if (!$queryString) {
                        break;
                    }
                }
            }

            $url = $mobile . $p . ( $queryString != '' ? '?' . $queryString : '' );

            if(isset($g['c_user_id'])) // added by sohel
                $uid = $g['c_user_id'];
            else
                $uid = guid();

            $groupId = false;
            $groupId = Groups::getParamId();

            if ($groupId) {
                $uid = User::getRequestUserId();
                $url = 'profile_group';
            } elseif (($p == 'search_results.php' && get_param('display') == 'profile')
                || $p == 'profile_view.php') {
                $url = 'profile';
                if ($mobile && !isset($g['c_user_id'])) {
                    $uid = get_param('user_id', guid());
                } elseif ($p == 'search_results.php') {
                    $uid = User::getRequestUserId();
                }
            } elseif ($p == 'city.php' || City::isCityInTab()) {
                $url = '3dcity';
            }

            $urls = self::prepareUrlForSeoRequest();
            array_push($urls, $url);

            $seo = self::getSeoSite($urls, $uid, null, false, $groupId);
        }
        $html->setvar('title', $seo['title']);
        $html->setvar('description', $seo['description']);
        $html->setvar('keywords', $seo['keywords']);

        if ($html->varExists('js_title')) {
            $html->setvar('js_title', toJs($seo['title']));
        }
        if ($html->varExists('js_description')) {
            $html->setvar('js_description', toJs($seo['description']));
        }
        if ($html->varExists('js_keywords')) {
            $html->setvar('js_keywords', toJs($seo['keywords']));
        }
    }

    static function prepareUrlForSeoRequest($url = false)
    {
        if($url === false) {
            $urlBase = Common::urlSiteSubfolders(false);
            $urlFull = Common::urlPage();

            $start = mb_strlen($urlBase);
            $url = rtrim(mb_substr($urlFull, $start), '/');
        }

        // page?z=1:
        // page?z=1
        // page*

        // page:
        // page
        // page*

        if($url != '') {
            $urlParts = explode('?', $url);

            if(isset($urlParts[1])) {

                $url = $urlParts[0];

                $deleteParams = array(
                    'set_template',
                    'set_template_runtime',
                    'set_template_mobile',
                    'set_template_mobile_runtime',
                    'site_part_runtime',
                    'upload_page_content_ajax',
                    'ajax',
                    'site_guid',
                    'name_seo',
                    'offset',
                );
                foreach ($deleteParams as $param) {
                    $urlParts[1] = del_param($param, $urlParts[1], true, false);
                    if (!$urlParts[1]) {
                        break;
                    }
                }

                if($urlParts[1]) {
                    $urls = array(
                        $url . '?' . $urlParts[1],
                        $url . '*',
                    );
                }
            }
        }

        if(!isset($urls)) {
            $urls = array(
                $url,
                $url . '*',
            );
        }

        return $urls;
    }


    static function getOptionSetTmpl()
    {
        $tmplOptionSet = Common::getOption('set', 'template_options');
        if (!$tmplOptionSet) {
            $tmplOptionSet = 'old';
        }
        return $tmplOptionSet;
    }

    static function getTmplSet()
    {
        return self::getOptionSetTmpl();
    }

    static function getTmplName()
    {
        return Common::getOption('name', 'template_options');
    }

    static function isCreditsEnabled()
    {
        return !Common::isOptionActive('free_site') && Common::isOptionActive('credits_enabled');
    }

    static function isTransferCreditsEnabled()
    {
        return Common::isCreditsEnabled() && Common::isOptionActive('credit_transfer_to_another_user');
    }

    static function dateFormat($timestamp, $formatName, $strtotime = true, $isObjDateTime = false, $isTimeStamp = false, $siteTime = false, $isFormat = false) {
        global $g;

        if ($strtotime){
            $timestamp = strtotime($timestamp);
        }
        //echo $formatName.' - '.$timestamp.' <br>';
        if ($isFormat) {
            $format = $formatName;
        } else {
            $format = $g['date_formats'][$formatName];
        }
        return pl_date($format, $timestamp, $isObjDateTime, $isTimeStamp, $siteTime);
    }

    static function getMapImageUrl($x, $y, $sizeX, $sizeY, $scale, $needMarker) {
        $service = Common::getOption('maps_service');
        if ($service == 'Google'){
            if ($needMarker){
                $url = '//maps.googleapis.com/maps/api/staticmap?markers=color:red%7Clabel:%7C' . $x . ',' . $y . '&center=' . $x . ',' . $y . '&zoom=10&size=' . $sizeX . 'x' . $sizeY .'&sensor=false&scale='.$scale;
            } else {
                $url = '//maps.googleapis.com/maps/api/staticmap?center=' . $x . ',' . $y . '&zoom=10&size=' . $sizeX . 'x' . $sizeY .'&sensor=false&scale='.$scale;
            }

            $apiKey = trim(Common::getOption('google_apikey'));
            if($apiKey) {
                $url .= '&key=' . $apiKey;
            }
        } else {
            $apiKey = Common::getOption('bing_apikey');
            if ($needMarker){
                $url = '//dev.virtualearth.net/REST/V1/Imagery/Map/Road/'. $x . ',' . $y . '/' . $scale . '?mapSize=' . $sizeX . ',' . $sizeY .'&pp=' . $x . ',' . $y . ';66;&c='.Common::getLocaleShortCode().'&format=png&key='.$apiKey;
            } else {
                $url = '//dev.virtualearth.net/REST/V1/Imagery/Map/Road/'. $x . ',' . $y . '/' . $scale . '?mapSize=' . $sizeX . ',' . $sizeY .'&c='.Common::getLocaleShortCode().'&format=png&key='.$apiKey;
            }
        }

        return $url;
    }

    static function isOptionTemplateSet($template = 'urban')
    {
        return Common::getOption('set', 'template_options') == $template;
    }

    static function getDefaultBirthday($param = null, $value = 0)
    {
        $defaultBirthday = array('day' => 1, 'month' => date('n'), 'year' => Common::getOption('default_birth_year'));
        if ($param === null) {
            return $defaultBirthday;
        }
        if (!$value) {
            $value = $defaultBirthday[$param];
        }
        return $value;
    }

    static function searchWhere($typeSearch, $tableUser = 'u', $isAlwaysInterestMy = false)
    {
        global $g;

        $guid = guid();
        $guidSql = to_sql($guid, 'Number');
        $optionSet = Common::getOption('set', 'template_options');
        $isFreeSite = Common::isOptionActive('free_site');
        $optionTemplateSet = Common::getOption('set', 'template_options');
        $display = get_param('display');

        $where = '';
        $whereCore = '1=1 ';
        $user = array();

        $user['horoscope'] = intval(get_checks_param('p_star_sign'));
        if ($user['horoscope']){
            $where .= " AND {$user['horoscope']} & (1 << (cast({$tableUser}.horoscope AS signed) - 1))";
        }

        $user['p_orientation'] = intval(get_checks_param('p_orientation'));
        if ($user['p_orientation'] > 0){
            $where .= " AND {$user['p_orientation']} & (1 << (cast({$tableUser}.orientation AS signed) - 1))";
        }

        $user['p_relation'] = intval(get_checks_param('p_relation'));
        if ($user['p_relation'] != '0'){
            $where .= " AND {$user['p_relation']} & (1 << (cast({$tableUser}.relation AS signed) - 1))";
        }

        $user['name'] = trim(get_param('name_key'));
        if ($user['name']) {
            $where .= " AND {$tableUser}.name LIKE '%" . to_sql($user['name'], 'Plain') . "%'";
        }

        $user['name'] = trim(get_param('name'));
        if ($user['name']) {
            $where .= ' AND {$tableUser}.name=' . to_sql($user['name']);
        }

        $user['p_age_from'] = intval(get_param('p_age_from'));
        if ($user['p_age_from'] == $g['options']['users_age']) {
            $user['p_age_from'] = 0;
        }

        $user['p_age_to'] = intval(get_param('p_age_to'));
        if ($user['p_age_to'] == $g['options']['users_age_max']) {
            $user['p_age_to'] = 10000;
        }

        if ($user['p_age_from']) {
            $where .= " AND (DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT({$tableUser}.birth, '%Y')
                          - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT({$tableUser}.birth, '00-%m-%d'))) >= " . $user['p_age_from'];
        }

        if ($user['p_age_to']) {
            $where .= " AND (DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT({$tableUser}.birth, '%Y')
                          - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT({$tableUser}.birth, '00-%m-%d')) <= " . $user['p_age_to'] . ') ';
        }

        $addWhereInterests = '';

        foreach ($g['user_var'] as $k => $v){
            $user[$k] = intval(get_param($k));
        }
        $typeFields = array('from', 'checks', 'checkbox');
        $numCheckbox = 0;
        $from_add = '';
        $from_group = '';
        foreach ($g['user_var'] as $k => $v) {
        if (in_array($v['type'], $typeFields) && $v['status'] == 'active') {
            if ($v['type'] == 'from'){
                $key = $k;
                if (substr($key, 0, 2) == "p_") $key = substr($key, 2);
                if (substr($key, -5) == "_from") $key = substr($key, 0, strlen($key) - 5);

                $valFieldFrom = $user[$k];

                $fieldTo = substr($k, 0, strlen($k) - 4) . 'to';
                $valFieldTo = intval($user[$fieldTo]);

                if ($valFieldTo) {
                    $where .= ' AND i.' . $key . '<=' . $valFieldTo;

                    if(!$valFieldFrom) {
                        $valFieldFrom = 1;
                    }
                }

                if($valFieldFrom) {
                    $where .= " AND i." . $key . ">=" . intval($valFieldFrom);
                }

                if($valFieldFrom || $valFieldTo) {
                    // save real value for defaul select value
                    $valFieldFrom = $user[$k];

                    $keyFilter = $k;

                    if(!$valFieldFrom) {
                        $keyFilter = $fieldTo;
                    }

                    $userSearchFilters[$keyFilter] = array(
                        'field' => $key,
                        'values' => array($k => $valFieldFrom, $fieldTo => $valFieldTo),
                    );
                }
            } elseif ($v['type'] == 'checks' && $user[$k] != 0) {
                $user[$k] = intval(get_checks_param($k));
                if ($user[$k] != 0) {
					$key = $k;
					if (substr($key, 0, 2) == "p_") $key = substr($key, 2);

                    $userSearchFilters[$k] = array(
                        'field' => $key,
                        'value' => get_param_array($k),
                    );

                    if ($k != 'p_star_sign') {
                        $where .= " AND " . to_sql($user[$k], 'Number') . " & (1 << (cast(i." . $key . " AS signed) - 1))";
                    }
				}
            } elseif ($v['type'] == 'checkbox' && $user[$k] != 0) {
                $params = get_param_array($k);
                foreach ($params as $key => $value) {
                    if ($value == 0) {
                        unset($params[$key]);
                    }
                }
                if (!empty($params)){
                    $userSearchFilters[$k] = array(
                        'field' => $k,
                        'value' => $params,
                    );
                    $nameTable = 'uck' . $numCheckbox;
                    $from_add .= " LEFT JOIN users_checkbox AS " . $nameTable . " ON " . $nameTable . ".user_id = {$tableUser}.user_id AND " . $nameTable . ".field = " . to_sql($v['id'], 'Number') . " AND "  . $nameTable . ".value IN (" . implode(',', $params) . ")";
                    $where .=  " AND " . $nameTable . ".user_id IS NOT NULL";
                    $numCheckbox++;
                }
            }
        }
        }

        if ($numCheckbox) {
            $from_group = "{$tableUser}.user_id";
        }
        if (intval(get_param('photo'))){
            $where .= " AND {$tableUser}.is_photo='Y'";
        }
        if (intval(get_param('couple'))){
            $where .= " AND {$tableUser}.couple='Y'";
        }

        if (get_param('status') == 'online'){
            $time = date('Y-m-d H:i:s', time() - $g['options']['online_time'] * 60);
            if ($optionSet == 'urban') {
                $where .= " AND ({$tableUser}.last_visit> " . to_sql($time, 'Text') . ' AND ' . User::isHiddenSql($tableUser . '.') . ')';
                //$where .= " AND ({$tableUser}.last_visit> " . to_sql($time, 'Text') . " AND {$tableUser}.set_hide_my_presence = 2)";
            } else {
                $where .= " AND {$tableUser}.last_visit> " . to_sql($time, 'Text');
            }
        } elseif (get_param('status') == 'new'){
            $where .= " AND {$tableUser}.register > " . to_sql(date('Y-m-d H:i:s', (time() - $g['options']['new_time'] * 3600 * 24)), 'Text');
        } elseif (get_param('status') == 'birthday'){
            $where .= " AND (DAYOFMONTH({$tableUser}.birth)=DAYOFMONTH('" . date('Y-m-d H:i:s') . "') AND MONTH({$tableUser}.birth)=MONTH('" . date('Y-m-d H:i:s') . "'))";
        }

        $day = to_sql(get_param('day', 0), 'Number');
        $month = to_sql(get_param('month', 0), 'Number');
        $year = to_sql(get_param('year', 0), 'Number');

        if($day && $month && $year) {
            $month = sprintf('%02d', $month);
            $day = sprintf('%02d', $day);
            $where .= " AND {$tableUser}.register >= '{$year}-{$month}-{$day} 00:00:00' ";
        }

        $day = to_sql(get_param('day_to', 0), 'Number');
        $month = to_sql(get_param('month_to', 0), 'Number');
        $year = to_sql(get_param('year_to', 0), 'Number');

        if ($day && $month && $year) {
            $month = sprintf('%02d', $month);
            $day = sprintf('%02d', $day);
            $where .= " AND {$tableUser}.register <= '{$year}-{$month}-{$day} 23:59:59' ";
        }

        // IF active distance search, then exclude others
        // DISTANCE
        $distance = intval(get_param('radius', 0));
        $user['city'] = intval(get_param('city', 0));
        $user['state'] = intval(get_param('state', 0));
        $user['country'] = intval(get_param('country', 0));

        $maxDistance = 200;
        if ($optionTemplateSet == 'urban') {
            $maxDistance = Common::getOption('max_search_distance');
        }
        if($distance > $maxDistance) {
            $user['city'] = 0;
            $user['state'] = 0;
        }
        // search only by distance from selected city
        $whereLocation = '';
        if ($distance && $user['city']) {
            // find MAX geo values
            $whereLocation = inradius($user['city'], $distance);
        } else {
            $whereLocation = Common::getWhereSearchLocation($user);
        }

        $from_add .= " LEFT JOIN userinfo AS i ON {$tableUser}.user_id=i.user_id LEFT JOIN geo_city AS gc ON gc.city_id = {$tableUser}.city_id";

        $keyword = trim(get_param('keyword'));
        $search_header = get_param('search_header');
        $where_search = '';
        if ($keyword ) {
            if ($search_header == 1) {
                $where_search = " OR {$tableUser}.mail =" . to_sql($keyword);
            }
            $keyword_search_sql = '';
            $keyword = to_sql(strip_tags($keyword), 'Plain');
            foreach ($g['user_var'] as $k => $v){
                if ($v['type'] == 'text' or $v['type'] == 'textarea') {
                    $keyword_search_sql .= " OR i.{$k} LIKE '%{$keyword}%'";
                }
            }
            $where .= " AND ({$tableUser}.name LIKE '%{$keyword}%'{$keyword_search_sql}{$where_search}) ";
        }

        $ht = '';
        if ($typeSearch != 'wall_urban') {
            //Для урбана u.hide_time = 0 убрать вообще
            $ht = $user['name'] ? '1 ' : "{$tableUser}.hide_time = 0";
            $wallItemId = get_param('wall_item_id');
            if($wallItemId) {
                $where .= ' AND wl.wall_item_id = ' . to_sql($wallItemId, 'Number');
                $from_add .= " LEFT JOIN wall_likes AS wl ON wl.user_id = {$tableUser}.user_id ";
                // show hidden profiles in likes
                $ht = ' 1 ';
            }
        }

        $whereCore .=" AND {$ht}";

        $uidsExclude = get_param('uids_exclude', '');
        if($uidsExclude) {
            $where .= " AND {$tableUser}.user_id NOT IN (" . to_sql($uidsExclude, 'Plain') . ') ';
        }

        $paramUid = intval(get_param('uid'));
        if (!$paramUid) {
            $interest = get_param('interest');
            if($interest) {
                $addWhere = '';
                if ($isAlwaysInterestMy) {
                    $addWhereInterests=" AND {$tableUser}.user_id IN (SELECT uint.user_id as uint_user FROM user_interests uint WHERE uint.interest = " . to_sql($interest) ." UNION SELECT " . to_sql($guid) ." AS uint_user )";
                } else {
                    $from_add .= " JOIN user_interests AS uint ON ({$tableUser}.user_id = uint.user_id AND uint.interest = " . to_sql($interest) .') ' ;
                }
            }
            $where = "{$ht} {$where}";
        }

        $order = '';
        if (intval(get_param('with_photo')) && $display != 'encounters'
            && $display != 'rate_people' && $typeSearch != 'wall_urban') {
            $order = "is_photo = 'Y' DESC, ";
        }

        $user['i_am_here_to'] = intval(get_param('i_am_here_to'));
        if ($user['i_am_here_to'] && $typeSearch != 'wall_urban'){
            //$where .= " AND u.i_am_here_to = " . to_sql($user['i_am_here_to']);
            $order .= 'i_am_here_to = ' . to_sql($user['i_am_here_to']) . ' DESC, ';
        }

        if ($optionTemplateSet == 'urban') {
            if ($display == '') {
                $order .= ($isFreeSite) ? 'near DESC, user_id DESC' : 'date_search DESC, near DESC, user_id DESC';
            }
        } else {
            $order .= 'near DESC, user_id DESC';
        }

        if (Common::getOption('do_not_show_me_in_search', 'template_options')
                && $typeSearch != 'wall_urban'
                    && $guid != User::getRequestUserId()) {
            $where .= " AND {$tableUser}.user_id != {$guidSql}";
            $whereCore .= " AND {$tableUser}.user_id != {$guidSql}";
        }

        if ($guid) {
            if ($optionTemplateSet == 'urban' && Common::isOptionActive('contact_blocking')) {
                //$order = ' date_search DESC, near DESC,  user_id DESC';
                $from_add .= " LEFT JOIN user_block_list AS ubl1 ON (ubl1.user_to = {$tableUser}.user_id AND ubl1.user_from = {$guidSql})
                               LEFT JOIN user_block_list AS ubl2 ON (ubl2.user_from = {$tableUser}.user_id AND ubl2.user_to = {$guidSql})";
                $where .=' AND ubl1.id IS NULL AND ubl2.id IS NULL';
                $whereCore .= ' AND ubl1.id IS NULL AND ubl2.id IS NULL';

            }
            if ($display == 'encounters') {
                $where .= " AND {$tableUser}.is_photo_public = 'Y' AND {$tableUser}.user_id != {$guidSql}";
                if (!$paramUid) {
                    $where .= ' AND enc1.user_from IS NULL AND enc2.user_from IS NULL ';
                    $from_add .= " LEFT JOIN encounters AS enc1 ON ({$tableUser}.user_id = enc1.user_to AND enc1.user_from = {$guidSql})
                                   LEFT JOIN encounters AS enc2 ON ({$tableUser}.user_id = enc2.user_from AND enc2.user_to = {$guidSql}
                                                       AND ((enc2.from_reply != 'N' AND enc2.to_reply != 'P')OR(enc2.from_reply = 'N')))";

                }
                $order .= ($isFreeSite) ? 'near DESC,  user_id DESC' : 'date_encounters DESC, near DESC,  user_id DESC';
            } elseif ($display == 'rate_people') {
                $where .= " AND {$tableUser}.is_photo_public = 'Y' AND {$tableUser}.user_id != {$guidSql} AND upr.photo_id IS NULL ";
                $from_add .= " LEFT JOIN photo AS up ON {$tableUser}.user_id = up.user_id AND up.private = 'N'
                               LEFT JOIN photo_rate AS upr ON up.photo_id = upr.photo_id AND upr.user_id = {$guidSql}";
                $order .= 'votes ASC, RAND()';
                $from_group = "{$tableUser}.user_id";
                if (!$paramUid) {
                    if(Users_List::isBigBase()){
                        //Внимание!!! Все поля, которые могут попасть в $order необходимо добавить в SELECT
                        $sql="
                        SELECT {$tableUser}.user_id, SUM(votes) AS votes, {$tableUser}.i_am_here_to
                        FROM (SELECT DISTINCT {$tableUser}.user_id, {$tableUser}.i_am_here_to
                            FROM user AS u ".$from_add. ' WHERE '.$where.' '.$whereLocation.') u
                        LEFT JOIN photo AS up ON {$tableUser}.user_id = up.user_id AND up.private = "N"
                        GROUP BY '.$from_group.'
                        ORDER BY '.$order.' LIMIT 0,1';
                        $user = DB::row($sql);
                        if (!$user['user_id']) {
                            $user['user_id']=0;
                        }
                        $where = " {$tableUser}.user_id = " . $user['user_id'];
                        $order = '';
                    }
                }
            }
        }

        $addWhereLocation = true;
        if ($display == 'encounters' && $paramUid && $typeSearch != 'wall_urban') {
            $addWhereLocation = false;
        }
        if ($addWhereLocation) {
            $where .= $whereLocation;
        }

        $where .= $addWhereInterests;

        $globalUsernameSearch = trim(get_param('global_search_by_username'));
        if($globalUsernameSearch){
            $where = "{$whereCore} AND {$tableUser}.name LIKE '%" . to_sql($global_username_search,'Plain') . "%'";
        }
        return array('from_add' => $from_add, 'where' => $where, 'order' => $order, 'from_group' => $from_group);
    }

    static function parseErrorAccessingUser(&$html)
    {
        $blockErrorAccessing = 'error_accessing_user';
        $errorAccessing = get_session($blockErrorAccessing);
        if ($html->blockexists($blockErrorAccessing) && $errorAccessing) {
            delses($blockErrorAccessing);
            $errorParam = get_session("{$blockErrorAccessing}_param");
            delses("{$blockErrorAccessing}_param");
            $html->setvar($blockErrorAccessing, lSetVars($errorAccessing, array('param' => $errorParam)));
            $html->parse($blockErrorAccessing, false);

            if ($html->varExists('is_mode_viewing_moderator')) {//EDGE
                $html->setvar('is_mode_viewing_moderator', intval(Moderator::isAllowedViewingUsers($errorAccessing)));
            }
        }
    }

    static function parseErrorForNotLoginUserNotExist(&$html)
    {
        if (!guid()) {
            $uid = User::getRequestUserId();
            if ($uid && !User::isExistsByUid($uid)) {
                $urlRedirect = Common::refererPageFromSite();
                if (!$urlRedirect) {
                    $urlRedirect = Common::getHomePage();
                }
                $html->setvar('url_redirect', $urlRedirect);
                $html->parse('for_not_login_user_not_exist');
            }
        }
    }

    static function autoTranslate($msg, $fromLang, $toLang)
    {
        $translatorEngine = 'google';

        $trMsg='';
        if($translatorEngine == 'google'){
            $APIkey=trim(Common::getOption('autotranslator_key'));
            if($APIkey!=''){
                $fromLocale=Common::getLocaleShortCode(Common::getLanguageCode($fromLang));
                $toLocale=Common::getLocaleShortCode(Common::getLanguageCode($toLang));

                if (function_exists('curl_init')) {

                    $query = "https://www.googleapis.com/language/translate/v2?key=".$APIkey.
                            "&q=".urlencode($msg)."&source=".$fromLocale."&target=".$toLocale;

                    $curl = curl_init();                                // Create Curl Object
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);       // Allow self-signed certs
                    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);       // Allow certs that do not match the hostname
                    curl_setopt($curl, CURLOPT_HEADER,0);               // Do not include header in output
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);       // Return contents of transfer on curl_exec
                    curl_setopt($curl, CURLOPT_URL, $query);            // execute the query
                    $result = curl_exec($curl);
                    if ($result == false) {

                        $result='ERROR';
                    }
                    curl_close($curl);

                    $result=json_decode($result);

                    if(isset($result->data->translations)){
                        $texts=$result->data->translations;
                        foreach($texts as $k=>$v){
                            $trMsg.=$v->translatedText;
                        }
                    }
                    if($msg==$trMsg){
                        $trMsg='';
                    }
                }
            }
        }

        return $trMsg;

    }

    static public function getColOrder($section='main')
    {
        $sql="SELECT * FROM `col_order` WHERE `section`=".to_sql($section)." ORDER BY `position`";
        $rows=DB::rows($sql);
        $result=array();
        foreach($rows as $k=>$row){
            $result[$row['name']]=$row;
        }
        return $result;
    }

    static public function redirectFromWithBaseUrl($page, $uid = null, $id = null, $params = null)
    {
        redirect(self::pageUrlWithBaseUrl($page, $uid = null, $id = null, $params = null));
    }

    static public function pageUrlWithBaseUrl($page, $uid = null, $id = null, $params = null)
    {
        global $g;

        return $g['path']['base_url_main'] . Common::pageUrl($page, $uid = null, $id = null, $params = null);
    }

    static public function pageUrl($page, $uid = null, $id = null, $params = null)
    {
        global $g, $g_user;

        $pageType = $page;
        if ($uid === null) {
            $uid = User::getParamUid();
        }

        if(Common::isOptionActive('seo_friendly_urls')) {
            $paramsAddSymbol = '?';
            $optionTmplName = Common::getTmplName();
			$isCustomUrls = $optionTmplName == 'edge';
			if (($optionTmplName == 'impact' || $optionTmplName == 'urban') && in_array($page, array('live', 'live_', 'live_id'))) {
				$isCustomUrls = true;
			}
            if ($isCustomUrls) {
                $customUrls = array(
                    'user_photos_list'  => 'photos',
                    'user_vids_list'    => 'vids',
                    'user_songs_list'    => 'songs',

                    'user_friends_list' => 'friends',
                    'my_friends_online' => 'friends_online',

                    'blogs_list'        => 'blogs',
                    'user_blogs_list'   => 'blogs',

                    'photos_list'        => 'photos',
                    'pages_photos_list'  => 'photos_pages',
                    'groups_photos_list' => 'photos_groups',

                    'pages_songs_list'  => 'songs_pages',
                    'groups_songs_list' => 'songs_groups',

                    'pages_vids_list'  => 'vids_pages',
                    'groups_vids_list' => 'vids_groups',

                    'songs_list'   => 'songs',
                    'vids_list'    => 'vids',
                    'wall_liked'   => 'wall_liked/' . get_param_int('wall_item_id', $id),
                    'wall_shared'  => 'wall_shared/' . get_param_int('wall_shared_item_id', $id),
                    'wall_liked_comment' => 'wall_liked_comment/' . get_param_int('comment_id', $id),

                    'photo_liked'         => 'photo_liked/' . get_param_int('photo_id', $id),
                    'photo_liked_comment' => 'photo_liked_comment/' . get_param_int('comment_id', $id),

                    'video_liked'         => 'video_liked/' . get_param_int('video_id', $id),
                    'video_liked_comment' => 'video_liked_comment/' . get_param_int('comment_id', $id),

                    'group_photos_list' => 'photos',
                    'group_page_liked'  => 'liked',
                    'group_subscribers'  => 'subscribers',
                    'group_vids_list'   => 'vids',
                    'group_block_list'  => 'block_list',
                    'group_songs_list'  => 'songs',
                    'pages_list'        => 'pages',
                    'user_pages_list'   => 'pages',
                    'groups_list'       => 'groups',
                    'user_groups_list'  => 'groups',
                    'user_my_pages_photos_list' => 'photos_my_pages',
                    'user_my_groups_photos_list' => 'photos_my_groups',

                    'user_my_pages_songs_list' => 'songs_my_pages',
                    'user_my_groups_songs_list' => 'songs_my_groups',

                    'user_my_pages_vids_list' => 'vids_my_pages',
                    'user_my_groups_vids_list' => 'vids_my_groups',

                    'user_calendar' => 'calendar',
                    'user_my_calendar' => 'calendar',
                    'task_create' => 'task_create',
                    'task_my_create' => 'task_create',

                    'blogs_post_liked'   => 'blogs_post_liked/' . get_param_int('blog_id', $id),
                    'blogs_post_liked_comment' => 'blogs_post_liked_comment/' . get_param_int('comment_id', $id),

                    'live' => 'live',
                    'live_' => 'live_/' . $id,
                    'live_id' => 'live/' . $id,
                );
                if (isset($customUrls[$page])) {
                    if ($page == 'my_friends_online') {
                        $urlSeo = User::url(guid(), null, null, true, true);
                    } else {
                        $pageGroupsProfile = array('group_photos_list',
                                                   'group_vids_list',
                                                   'group_songs_list',
                                                   'group_page_liked',
                                                   'group_subscribers',
                                                   'group_block_list');
                        if (in_array($page, $pageGroupsProfile)) {
                            $urlSeo = Groups::url($uid, null, null, true, true);
                        } else {
                            $urlSeo = User::url($uid, null, null, true, true);
                        }
                    }
                    $assignProfilePage = array('user_photos_list',
                                               'user_vids_list',
                                               'user_songs_list',
                                               'user_blogs_list',
                                               'user_pages_list',
                                               'user_friends_list',
                                               'my_friends_online',

                                               'user_groups_list',
                                               'group_photos_list',
                                               'group_vids_list',
                                               'group_songs_list',
                                               'group_page_liked',
                                               'group_subscribers',
                                               'group_block_list',
                                               'photos_my_pages',
                                               'user_my_pages_photos_list',
                                               'user_my_groups_photos_list',
                                               'user_my_pages_songs_list',
                                               'user_my_groups_songs_list',
                                               'user_my_pages_vids_list',
                                               'user_my_groups_vids_list',
                                               'user_calendar',
                                               'user_my_calendar',
                                               'task_create',
                                               'task_my_create',
                                               'live',
                                               'live_',
                                               'live_id'
                                        );
                    if (in_array($page, $assignProfilePage)) {
                        $page = $urlSeo . '/' . $customUrls[$page];
                    } else {
                        $page = $customUrls[$page];
                    }
                }

                if ($page == 'page_edit' || $page == 'group_edit' || $page == 'blog_edit') {
                    $page .= '/' . $uid;
                }

                if (in_array($pageType, array('task_create', 'task_my_create', 'task_edit', 'calendar', 'user_calendar', 'user_my_calendar')) && $id) {
                    $page .= '/' . $id;
                }
            }

			if (Common::isMobile()) {
				$urls = array(
					'live_' => 'live_?user_id=' . $uid . '&live=' . $id,
					'live_id' => 'live?user_id=' . $uid . '&live=' . $id
				);
				if (isset($urls[$page])) {
					$page = $urls[$page];
				}
            }

            $result = $page;
        } else {
            $paramsAddSymbol = '';
            $urls = array(
                'encounters' => 'search_results.php?display=encounters',
                'hot_or_not' => 'search_results.php?display=encounters',
                'rate_people' => 'search_results.php?display=rate_people',
                'login' => 'join.php?cmd=please_login',
                'mutual_likes' => 'mutual_attractions.php',
                'whom_you_like' => 'mutual_attractions.php?cmd=whom_you_like',
                'who_likes_you' => 'mutual_attractions.php?cmd=who_likes_you',
                'private_photo_access' => 'my_friends.php',
                'terms' => 'info.php?page=term_cond',
                'privacy_policy' => 'info.php?page=priv_policy',
                'profile_boost' => 'upgrade.php?action=refill_credits',
                'refill_credits' => 'upgrade.php?action=refill_credits',
                /* Edge */
                'social_network_info' => 'info.php?page=social_network_info',
                'my_friends_online'   => 'friends_list_online.php?uid=' . $uid,
                'user_photos_list'    => 'photos_list.php?uid=' . $uid,
                'user_vids_list'      => 'vids_list.php?uid=' . $uid,
                'user_songs_list'     => 'songs_list.php?uid=' . $uid,
                'user_blogs_list'     => 'blogs_list.php?uid=' . $uid,
                'user_friends_list'   => 'friends_list.php?uid=' . $uid,
                'wall_liked'          => 'search_results.php?show=wall_liked&wall_item_id=' . get_param_int('wall_item_id', $id),
                'wall_shared'         => 'search_results.php?show=wall_shared&wall_shared_item_id=' . get_param_int('wall_shared_item_id', $id),
                'wall_liked_comment'  => 'search_results.php?show=wall_liked_comment&comment_id=' . get_param_int('comment_id', $id),

                'photo_liked'         => 'search_results.php?show=photo_liked&photo_id=' . get_param_int('photo_id', $id),
                'photo_liked_comment' => 'search_results.php?show=photo_liked_comment&comment_id=' . get_param_int('comment_id', $id),

                'video_liked'         => 'search_results.php?show=video_liked&video_id=' . get_param_int('video_id', $id),
                'video_liked_comment' => 'search_results.php?show=video_liked_comment&comment_id=' . get_param_int('comment_id', $id),

                'page_add'            => 'group_add.php?view=group_page',
                'pages_list'          => 'groups_list.php?view=group_page',
                'user_pages_list'     => 'groups_list.php?view=group_page&uid=' . $uid,
                'groups_list'         => 'groups_list.php',
                'user_groups_list'    => 'groups_list.php?uid=' . $uid,
                'page_edit'           => 'group_add.php?cmd=edit&view=group_page&group_id=' . $uid,
                'group_edit'          => 'group_add.php?cmd=edit&group_id=' . $uid,
                /* Edge */
                'calendar'            => 'calendar.php',
                'user_calendar'       => 'calendar.php?uid=' . $uid,
                'user_my_calendar'    => 'calendar.php?uid=' . $uid,
                'task_create'         => 'calendar_task_create.php?uid=' . $uid,
                'task_my_create'      => 'calendar_task_create.php?uid=' . $uid,
                'task_edit'           => 'calendar_task_edit.php?event_id=' . $id,

                'pages_photos_list'  => 'photos_list.php?view_list=group_page',
                'groups_photos_list' => 'photos_list.php?view_list=group',
                'pages_songs_list'   => 'songs_list.php?view_list=group_page',
                'groups_songs_list'  => 'songs_list.php?view_list=group',

                'user_my_pages_photos_list'  => 'photos_list.php?view_list=group_page&uid=' . $uid,
                'user_my_groups_photos_list' => 'photos_list.php?view_list=group&uid=' . $uid,

                'user_my_pages_songs_list'  => 'songs_list.php?view_list=group_page&uid=' . $uid,
                'user_my_groups_songs_list' => 'songs_list.php?view_list=group&uid=' . $uid,

                'user_my_pages_vids_list'  => 'vids_list.php?view_list=group_page&uid=' . $uid,
                'user_my_groups_vids_list' => 'vids_list.php?view_list=group&uid=' . $uid,

                'pages_vids_list'    => 'vids_list.php?view_list=group_page',
                'groups_vids_list'   => 'vids_list.php?view_list=group',

                'user_blogs_list'    => 'blogs_list.php?uid=' . $uid,

                'blog_edit'          => 'blogs_add.php?blog_id=' . $uid,

                'blogs_post_liked'          => 'search_results.php?show=blogs_post_liked&blog_id=' . get_param_int('blog_id', $id),
                'blogs_post_liked_comment'  => 'search_results.php?show=blogs_post_liked_comment&comment_id=' . get_param_int('comment_id', $id),

                'street_chat'        => 'city.php?place=street_chat',

                'live'     => 'live_streaming.php?uid=' . $uid,
                'live_'    => 'live_streaming.php?uid=' . $uid . '&stream=1&live=' . $id,
                'live_id'  => 'live_streaming.php?uid=' . $uid . '&live=' . $id,
            );
            if ($id && in_array($page, array('calendar', 'task_create', 'task_my_create', 'user_calendar', 'user_my_calendar'))) {
                $delimiter = '&';
                if ($page == 'calendar') {
                    $delimiter = '?';
                }
                $urls[$page] .= $delimiter . 'date=' . $id;
            } elseif (in_array($page, array('group_photos_list', 'group_vids_list', 'group_songs_list', 'group_page_liked', 'group_subscribers'))) {
                $typeGroupParam = Groups::getTypeParam($uid);
                if ($typeGroupParam) {
                    $pagesGroupUrl = array('group_photos_list' => 'photos_list.php',
                                           'group_vids_list'   => 'vids_list.php',
                                           'group_songs_list'  => 'songs_list.php',
                                           'group_page_liked'  => 'groups_social_subscribers.php',
                                           'group_subscribers' => 'groups_social_subscribers.php',
                                           'group_block_list'  => 'groups_social_block_list.php');
                    if (isset($pagesGroupUrl[$page])) {
                        $urlGroupParam = '?group_id=' . $uid . '&' . $typeGroupParam;
                        $urls[$page] = $pagesGroupUrl[$page] . $urlGroupParam;
                    }
                }
            }

            if (Common::isMobile()) {
                $urls['profile_boost'] = 'upgrade.php?action=refill_credits&service=search';
                $urls['whom_you_like'] = 'mutual_attractions.php?display=whom_you_like';
                $urls['who_likes_you'] = 'mutual_attractions.php?display=who_likes_you';

				$urls['live'] = 'live_streaming.php';
				$urls['live_'] = 'live_streaming.php?user_id=' . $uid . '&stream=1&live=' . $id;
				$urls['live_id'] = 'live_streaming.php?user_id=' . $uid . '&live=' . $id;
            }

            $result = isset($urls[$page]) ? $urls[$page] : $page . '.php';
        }

        if ($params !== null && !$paramsAddSymbol) {
            $paramsAddSymbol = mb_strpos($result, '?', 0, 'UTF-8') === false ? '?' : '&';
        }

        $result .= $params ? $paramsAddSymbol . http_build_query($params) : '';

        return $result;
    }

    static public function ageToDate($age, $maxDate = false)
    {
        //$baseDate = strtotime('2016-12-31');
        //$baseDate = strtotime('2016-01-01');
        //$baseDate = strtotime('2016-03-29');
        //$baseDate = strtotime('2016-03-31');
        //$baseDate = strtotime('2016-02-29');
        //$baseDate = strtotime('2016-02-28');
        $baseDate = time();
        $today = date('m-d', $baseDate);
        $date = new DateTime(date('Y-m-d', $baseDate));

        $age = intval($age);
        if($age < 0) {
            $age = 0;
        }

        if($maxDate) {
            $age++;
        }

        $date->sub(new DateInterval('P' . $age . 'Y'));

        if($maxDate) {
            if($today != '02-29' || $date->format('m-d') == '02-29') {
                $date->add(new DateInterval('P1D'));
            }
        } else {
            if($today == '02-29' && $date->format('m-d') != '02-29') {
                $date->sub(new DateInterval('P1D'));
            }
        }

        return $date->format('Y-m-d');
    }

    static public function getSearchOrderNear()
    {
        if(Users_List::isBigBase()) {
            $orderNear = '';
        } else {
            $orderNear = ' near DESC, ';
        }

        return $orderNear;
    }

    static public function prepareSearchWhereOrderByPhoto(&$where, &$order)
    {
        if(Users_List::isBigBase()) {
            $where .= " AND is_photo = 'Y' ";
        } else {
            $order = "is_photo = 'Y' DESC, ";
        }
    }

    static public function prepareSearchWhereOrderByIAmHereTo(&$where, &$order, $value)
    {
        if(Users_List::isBigBase()) {
            $where .= ' AND u.i_am_here_to = ' . to_sql($value) . ' ';
        } else {
            $order .= 'i_am_here_to = ' . to_sql($value) . ' DESC, ';
        }
    }

    static public function checkAreaLogin($toLogin = true)
    {
        global $p;
        $result = true;
        if (!guid()) {
            $area = Common::getOption('area_login', 'template_options');
            if ($area) {
                $result = !in_array($p, $area);
            }
        }
        if ($toLogin && !$result) {
            Common::toLoginPage();
        }
        return $result;
    }

    static function getBlockIntoWhichAddPart($namePart)
    {
        $blockAdd = 'page';
        $blockAddTmpl = Common::getOption("block_add_{$namePart}", 'template_options');
        if ($blockAddTmpl) {
            $blockAdd = $blockAddTmpl;
        }
        return $blockAdd;
    }

    static function allowedFeatureSuperPowersFromTemplate($feature)
    {
        $allowedFeatures = Common::getOption('feature_super_powers_allowed', 'template_options');
        if (is_array($allowedFeatures)) {
            return in_array($feature, $allowedFeatures);
        } else {
            return false;
        }
    }

    static function mainPageSetRandomImage()
    {
        global $g;

        $isRandomImageActive = false;

        $tmplName = Common::getTmplName();

        if(Common::getTmplSet() == 'urban') {
            if( (($tmplName != 'edge') && Common::isOptionActive('map_on_main_page_urban', 'template_options') && ('random_image' == Common::getOption('map_on_main_page_urban'))) || ($tmplName == 'edge' && ('random_image' == Common::getOption('main_page_background_type', 'edge_color_scheme_visitor'))) ) {
                $isRandomImageActive = true;
            }
        }

        if ($isRandomImageActive) {
            $dir = Common::getOption('dir_tmpl_main', 'tmpl') . 'images/main_page_image';
            if (file_exists($dir)){
                $optionsArray = readAllFileArrayOfDir($dir, '');
                $dir = Common::getOption('url_files', 'path') . 'tmpl';
                $templateFile = Common::getOption('tmpl_loaded', 'tmpl') . '_main_page_image_';
                $optionsArray += readAllFileArrayOfDir($dir,'', '', $templateFile);
                if (!empty($optionsArray)){
                    $rand_file = array_rand($optionsArray);
                    $image = getFileUrl('main_page_image', $rand_file, '_main_page_image_', 'image_main_page_urban', 'main_page_image_default_urban');
                    if (file_exists($image)){
                        $g['options']['image_main_page_urban'] = $rand_file;
                        $g['options']['image_main_page_impact'] = $rand_file;
                        $g['edge_color_scheme_visitor']['main_page_image'] = $rand_file;
                        if($tmplName == 'urban') {
                            $infoImage = getimagesize($image);
                            $g['options']['image_main_page_height_urban'] = $infoImage[1];
                        }
                    }
                }
            }
        }
    }

    static function impactGetMapOnMainPageUrbanValue($value)
    {
        $allowValues = array('image', 'random_image', 'video');

        if(!in_array($value, $allowValues)) {
            $value = 'image';
        }

        return $value;
    }

    static public function isCountryHidden($countryId, $dbIndex = DB_MAX_INDEX)
    {
        $sql = 'SELECT hidden FROM geo_country
            WHERE country_id = ' . to_sql($countryId);
        return DB::result($sql, 0, $dbIndex);
    }

    static public function isStateHidden($stateId, $dbIndex = DB_MAX_INDEX)
    {
        $sql = 'SELECT hidden FROM geo_state
            WHERE state_id = ' . to_sql($stateId);
        return DB::result($sql, 0, $dbIndex);
    }

    static public function isCityHidden($cityId, $dbIndex = DB_MAX_INDEX)
    {
        $sql = 'SELECT hidden FROM geo_city
            WHERE city_id = ' . to_sql($cityId);
        return DB::result($sql, 0, $dbIndex);
    }

    static public function getFirstStateInCountry($countryId, $dbIndex = DB_MAX_INDEX)
    {
        $sql = 'SELECT * FROM geo_state
            WHERE country_id = ' . to_sql($countryId) . '
                AND hidden = 0
            ORDER BY state_id ASC
            LIMIT 1';
        return DB::row($sql, $dbIndex);
    }

    static public function getFirstCityInState($stateId, $dbIndex = DB_MAX_INDEX)
    {
        $sql = 'SELECT * FROM geo_city
            WHERE state_id = ' . to_sql($stateId) . '
                AND hidden = 0
            ORDER BY city_id ASC
            LIMIT 1';
        return DB::row($sql, $dbIndex);
    }

    static public function getBackgroundColorSheme($option, $prf = 'impact', $module = 'options')
    {
        if ($prf) {
            $prf = '_' . $prf;
        }
        $type = Common::getOption("{$option}_type{$prf}", $module);
        if ($type == 'color') {
            return Common::getOption("{$option}_color{$prf}", $module);
        } else {
            $colorDirection = Common::getOption("{$option}_color_direction{$prf}", $module);
            $colorUpper = Common::getOption("{$option}_color_upper{$prf}", $module);
            $colorUpperStop = intval(Common::getOption("{$option}_color_upper_stop{$prf}", $module));
            $colorLower = Common::getOption("{$option}_color_lower{$prf}", $module);
            $colorLowerStop = intval(Common::getOption("{$option}_color_lower_stop{$prf}", $module));
            return "linear-gradient(to {$colorDirection}, {$colorUpper} $colorUpperStop%, {$colorLower} $colorLowerStop%)";
        }
    }

    static public function getAllowedOptionsJs()
    {
        $optionTemplateName = Common::getTmplName();
        $allowedOptions = array(
            'options' => array(
                'hide_im_on_page_city',
                'number_of_columns_in_language_selector',
                'message_notifications_not_show_when_3d_city',
                'forced_open_chat_with_new_message',
                'photo_approval',
				'seo_friendly_urls'
            )
        );

        if ($optionTemplateName == 'edge') {
            $allowedOptions = array(
                'edge_member_settings' => array(
                    'show_your_photo_browse_photos',
                    'show_your_video_browse_videos',
                    'show_your_song_browse_songs'
                ),
                'options' => array(
                    'seo_friendly_urls'
                ),
                'edge_gallery_settings' => array(
                    'gallery_show_download_original'
                ),
                'edge_events_settings' => array(
                    'first_day_week'
                ),
            );
        }
        $result = array();
        foreach ($allowedOptions as $key => $options) {
            $result[$key] = array();
            foreach ($options as $option) {
                $value = Common::getOption($option, $key);
                if ($option == 'forced_open_chat_with_new_message') {
                    $value = IS_DEMO ? 'Y' : 'N';
                }
                if ($value && !is_array($value)) {
                    $result[$key][$option] = is_string($value) ? toJs($value) : intval($value);
                }
            }
        }
        return json_encode($result);
    }

    static function isPage($page, $isMyProfile = false)
    {
        global $p;

        $isPage = false;

        $display = get_param('display');
        $show = get_param('show');
        $paramUid = User::getParamUid(0);//get_param('uid');
        $paramTypeGroup = get_param('view');
        $paramIsGroupPage = $paramTypeGroup == 'group_page';

        $uid = User::getParamUid();
        $cmd = get_param('cmd');
        $guid = guid();
        $viewList = get_param('view_list');
        if ($page == 'profile_view') {
            $isPage = $p == 'profile_view.php'
                  || ($p == 'search_results.php' && ($display == 'profile' && $guid == $uid));
        } elseif ($page == 'profile') {
            $isPage = $p == 'profile_view.php'
                  || ($p == 'search_results.php' && $display == 'profile');
        } elseif ($page == 'search_results') {
            $isPage = $p == 'search_results.php' && !$display && !$show;

        } elseif ($page == 'user_vids_list' || $page == 'group_vids_list') {
            $isPage = $p == 'vids_list.php' && $paramUid && !$viewList;
        } elseif ($page == 'user_my_vids_list') {
            $isPage = $p == 'vids_list.php' && $guid == $paramUid && !$viewList;
        } elseif ($page == 'vids_list') {
            $isPage = $p == 'vids_list.php' && !$paramUid && !$viewList;
        } elseif ($page == 'pages_vids_list') {
            $isPage = $p == 'vids_list.php' && $viewList == 'group_page' && !$paramUid;
        } elseif ($page == 'groups_vids_list') {
            $isPage = $p == 'vids_list.php' && $viewList == 'group' && !$paramUid;
        } elseif ($page == 'user_my_pages_vids_list') {
            $isPage = $p == 'vids_list.php' && $viewList == 'group_page' && $paramUid == $guid;
        } elseif ($page == 'user_my_groups_vids_list') {
            $isPage = $p == 'vids_list.php' && $viewList == 'group' && $paramUid == $guid;

        } elseif ($page == 'user_photos_list' || $page == 'group_photos_list') {
            $isPage = $p == 'photos_list.php' && $paramUid && !$viewList;
        } elseif ($page == 'user_my_photos_list') {
            $isPage = $p == 'photos_list.php' && $guid == $paramUid && !$viewList;
        } elseif ($page == 'photos_list') {
            $isPage = $p == 'photos_list.php' && !$paramUid && !$viewList;
        } elseif ($page == 'pages_photos_list') {
            $isPage = $p == 'photos_list.php' && $viewList == 'group_page' && !$paramUid;
        } elseif ($page == 'groups_photos_list') {
            $isPage = $p == 'photos_list.php' && $viewList == 'group' && !$paramUid;
        } elseif ($page == 'user_my_pages_photos_list') {
            $isPage = $p == 'photos_list.php' && $viewList == 'group_page' && $paramUid == $guid;
        } elseif ($page == 'user_my_groups_photos_list') {
            $isPage = $p == 'photos_list.php' && $viewList == 'group' && $paramUid == $guid;

        } elseif ($page == 'user_friends_list') {
            $isPage = $p == 'friends_list.php' && get_param('show', 'all') == 'all';
        //} elseif ($page == 'friends_list') {
           // $isPage = $p == 'my_friends.php' && $guid == $uid && get_param('show', 'all') == 'all';
        } elseif ($page == 'my_friends_online') {
             $isPage = $p == 'friends_list_online.php';
        } elseif ($page == 'wall') {
            $isPage = $p == 'wall.php' && !get_param_int('item');
        /* Groups */
        } elseif ($page == 'group_add') {
            $isPage = $p == 'group_add.php' && !$paramTypeGroup;
        } elseif ($page == 'groups_list') {
            $isPage = $p == 'groups_list.php' && !$paramIsGroupPage && !$paramUid;
        } elseif ($page == 'user_my_groups_list') {
            $isPage = $p == 'groups_list.php' && !$paramIsGroupPage && $guid == $paramUid;
        } elseif ($page == 'user_groups_list') {
            $isPage = $p == 'groups_list.php' && !$paramIsGroupPage && $paramUid;
        /* Groups */
        /* Pages */
        } elseif ($page == 'page_add') {
            $isPage = $p == 'group_add.php' && $paramIsGroupPage && !$cmd;
        } elseif ($page == 'pages_list') {
            $isPage = $p == 'groups_list.php' && $paramIsGroupPage && !$paramUid;
        } elseif ($page == 'user_my_pages_list') {
            $isPage = $p == 'groups_list.php' && $paramIsGroupPage && $guid == $paramUid;
        } elseif ($page == 'user_pages_list') {
            $isPage = $p == 'groups_list.php' && $paramIsGroupPage && $paramUid;
        } elseif ($page == 'group_page_liked') {
            $isPage = $p == 'groups_social_subscribers.php';
        } elseif ($page == 'group_subscribers') {
            $isPage = $p == 'groups_social_subscribers.php';
        /* Pages */
        /* Blogs */
        } elseif ($page == 'blogs_list') {
            $isPage = $p == 'blogs_list.php' && !$paramUid;
        } elseif ($page == 'user_my_blogs_list') {
            $isPage = $p == 'blogs_list.php' && $guid == $paramUid;
        } elseif ($page == 'user_blogs_list') {
            $isPage = $p == 'blogs_list.php' && $paramUid;
        } elseif ($page == 'blogs_add') {
            $isPage = $p == 'blogs_add.php' && !get_param_int('blog_id');
        /* Blogs */
        } elseif ($page == 'user_my_calendar') {
            $isPage = $p == 'events_calendar.php' && $guid == $paramUid;
        } elseif ($page == 'user_calendar') {
            $isPage = $p == 'events_calendar.php' && $paramUid;
        } elseif ($page == 'task_my_create') {
            $isPage = $p == 'events_event_edit.php' && $uid == $guid && !get_param_int('event_id');
        } elseif ($page == 'task_create') {
            $isPage = $p == 'events_event_edit.php' && $paramUid && !get_param_int('event_id');
        /* Live streaming */
        } elseif ($page == 'my_live') {
            $isPage = $p == 'live_streaming.php' && $guid == $paramUid;
        } elseif ($page == 'live') {
            $isPage = $p == 'live_streaming.php' && $paramUid;
        /* Live streaming */
        /* Songs */
        } elseif ($page == 'user_songs_list' || $page == 'group_songs_list') {
            $isPage = $p == 'songs_list.php' && $paramUid && !$viewList;
        } elseif ($page == 'user_my_songs_list') {
            $isPage = $p == 'songs_list.php' && $guid == $paramUid && !$viewList;
        } elseif ($page == 'songs_list') {
            $isPage = $p == 'songs_list.php' && !$paramUid && !$viewList;
        } elseif ($page == 'pages_songs_list') {
            $isPage = $p == 'songs_list.php' && $viewList == 'group_page' && !$paramUid;
        } elseif ($page == 'groups_songs_list') {
            $isPage = $p == 'songs_list.php' && $viewList == 'group' && !$paramUid;
        } elseif ($page == 'user_my_pages_songs_list') {
            $isPage = $p == 'songs_list.php' && $viewList == 'group_page' && $paramUid == $guid;
        } elseif ($page == 'user_my_groups_songs_list') {
            $isPage = $p == 'songs_list.php' && $viewList == 'group' && $paramUid == $guid;
        /* Songs */
        } else {
            $isPage = $p == ($page . '.php');
        }
        if ($isMyProfile) {
            $isPage = $isPage && $guid == $uid;
        }

        return $isPage;
    }

    static function parseBackgroundImage(&$html, $module, $prf = '')
    {
        $option = 'main_page_image';
        $blockImage = "{$option}_pic{$prf}";
        if ($html->blockExists($blockImage)) {
            $bgImage = Common::getOption($option, $module);
            if($bgImage != 'no_image') {
                $image = getFileUrl($option, $bgImage, "_{$option}_", $option, "{$option}_default", 'main', $module);
                if ($image) {
                    $html->setvar($blockImage, $image);
                    $html->parse("{$blockImage}_head_js", false);
                    $html->parse("{$blockImage}_js", false);
                    if (Common::isOptionActive('main_page_image_darken', $module)) {
                        $html->parse("{$blockImage}_darken", false);
                    }
                    $html->parse($blockImage, false);
                }
            }
        }
    }

    static public function sendAutoMailApproveImageToAdmin()
	{
        if(Common::isOptionActive('photo_approval') && Common::isEnabledAutoMail('approve_image_admin')){
            $vars = array('name'  => guser('name'));
            Common::sendAutomail(Common::getOption('administration', 'lang_value'), Common::getOption('info_mail', 'main'), 'approve_image_admin', $vars);
        }
    }

    static public function getOptionUsersInfoPerPage()
	{
        $mOnPage = Common::getOptionTemplate('usersinfo_per_page');
        if ($mOnPage === 'number_of_profiles_in_the_search_results') {
            $mOnPage = Common::getOptionInt('number_of_profiles_in_the_search_results');
        }
        if (!intval($mOnPage)) {
            $mOnPage = 20;
        }
        return $mOnPage;
    }

    static public function parseGdprCookie(&$html)
	{
        if ($html->blockExists('gdpr_cookie_popup')) {
            if (Common::isApp()) {
                $isParseCookiePopup = Common::isOptionActive('gdpr_cookie_consent_popup_app');
            } else {
                $isParseCookiePopup = Common::isOptionActive('gdpr_cookie_consent_popup');
            }
            if ($isParseCookiePopup) {
                $varsLink = array(
                    'link_start' => '<a class="more_link" href="' . Common::pageUrl('privacy_policy') . '">',
                    'link_end' => '</a>'
                );
                $html->setvar('cookies_accept_text', lSetVars('cookies_accept_text', $varsLink, 'toJsL'));

                if (!Common::isMobile() && Common::getOption('gdpr_cookie_consent_popup_theme') == 'small') {
                    $html->setvar('cookies_class', 'gdpr_small');
                    $html->setvar('cookies_width', Common::getOptionInt('gdpr_cookie_consent_popup_width'));
                    $html->setvar('cookies_width_unit', Common::getOption('gdpr_cookie_consent_popup_unit') == 'unit_px' ? 'px' : '%');

                    $html->setvar('cookies_position', Common::getOption('gdpr_cookie_consent_popup_position'));
                }
                $html->parse('gdpr_cookie_popup', false);
            }
        }
    }

    static public function getFileFromStringParam($param)
    {
        $fileString = str_replace(' ', '+', get_param($param));

        if($fileString) {
            $fileString = base64_decode($fileString);
        }

        return $fileString;
    }

    static public function isAdminSitePart()
    {
        global $sitePart;
        return isset($sitePart) && $sitePart === 'administration';
    }

    static public function getGUserJs()
    {
        if (!guid()) {
            return json_encode(array());
        }

        global $g_user;

        $allowedOptions = array(
            'sound' => array('type' => 'check', 'value' => 1)
        );


        $result = array();
        foreach ($allowedOptions as $key => $option) {
            if ($option['type'] == 'check') {
                if (isset($g_user[$key])) {
                    $result[$key] = $g_user[$key] == $option['value'];
                } else {
                    $result[$key] = false;
                }
            }
        }
        return json_encode($result);
    }

    static public function getAppIosApiVersion()
    {
        return intval(Common::getOption('api_version', 'app_ios'));
    }

    static public function setPleaseChoose($value)
    {
        self::$lPleaseChoose = $value;
    }

    static public function getPleaseChoose($clear = true)
    {
        $lPleaseChoose = l('please_choose');
        if (self::$lPleaseChoose) {
            $lPleaseChoose = self::$lPleaseChoose;
        }
        if ($clear) {
            self::clearPleaseChoose();
        }
        return $lPleaseChoose;
    }

    static public function clearPleaseChoose()
    {
        self::$lPleaseChoose = '';
    }

    static public function deleteTags($id, $type = '')
	{
        $tableTags = '';
        if ($type == 'video') {
            $tableTags = 'vids_tags';
            $tableTagsRelations = 'vids_tags_relations';
            $fieldRelationsId = 'video_id';
        } elseif ($type == 'photo') {
            $tableTags = 'photo_tags';
            $tableTagsRelations = 'photo_tags_relations';
            $fieldRelationsId = 'photo_id';
        } elseif ($type == 'blogs') {
            $tableTags = 'blogs_post_tags';
            $tableTagsRelations = 'blogs_post_tags_relations';
            $fieldRelationsId = 'blog_id';
        }

        if (!$tableTags) {
            return;
        }

        $sql = "SELECT TR.id, TR.tag_id, T.counter
                  FROM `{$tableTagsRelations}` as TR
                  LEFT JOIN `{$tableTags}` as T ON TR.tag_id = T.id
                 WHERE TR.{$fieldRelationsId} = " . to_sql($id);
        $tags = DB::all($sql);
        if ($tags) {
            foreach ($tags as $key => $tag) {
                $count = $tag['counter'] - 1;
                if ($count) {
                    DB::update($tableTags, array('counter' => $count), '`id` = ' . to_sql($tag['tag_id']));
                } else {
                    DB::delete($tableTags, '`id` = ' . to_sql($tag['tag_id']));
                }
                DB::delete($tableTagsRelations, '`id` = ' . to_sql($tag['id']));
            }
        }
    }

	static public function uploadDataImage($file, $param = 'icon')
	{
		global $g;

		if (!Common::isAdminModer()) {
			return false;
		}

		$data = get_param($param);
		if (!$data) {
			return false;
		}

		$cmd = get_param('cmd');

		$iconExt = '';
		if ($cmd == 'update_favicon') {
			$iconExt = '|x-icon';
		}
		$reg = "/^data:image\/(?<extension>(?:png|gif|jpg|jpeg{$iconExt}));base64,(?<image>.+)$/";
		if (get_param_int('allow_svg')) {
			$reg = "/^data:image\/(?<extension>(?:png|gif|jpg|jpeg{$iconExt}|(svg\+xml)));base64,(?<image>.+)$/";
		}
		if(preg_match($reg, $data, $matchings)){
            $imageData = base64_decode($matchings['image']);
            $extension = $matchings['extension'];
			if ($extension == 'svg+xml') {
				$extension = 'svg';
			} elseif($extension == 'x-icon'){
				$extension = 'ico';
			}
            $file .= '.' . $extension;
            if(file_put_contents($file, $imageData)){
                return $file;
            } else {
				return false;
			}
		}
		return false;
	}

	static public function uploadDataImageFromSetData($fileTemp = null, $param = 'icon')
	{
		global $g;

		if ($fileTemp === null) {
			$fileTemp = $g['path']['dir_files'] . 'temp/admin_upload_' . $param . '_' . time();
		}
		$fileImageData = Common::uploadDataImage($fileTemp, $param);
		if (!$fileImageData) {
			return false;
		}

		$_FILES[$param]['name'] = pathinfo($fileImageData, PATHINFO_BASENAME);
		$_FILES[$param]['tmp_name'] = $fileImageData;
		$_FILES[$param]['error'] = 0;
		$_FILES[$param]['type'] = '';
		$fileImageDataInfo = @getimagesize($fileImageData);
		if(isset($fileImageDataInfo['mime'])) {
			$_FILES[$param]['type'] = $fileImageDataInfo['mime'];
		}
		$_FILES[$param]['size'] = filesize($fileImageData);
		$_GET['image_upload_data'] = 1;

		return $fileImageData;
	}

	static public function isAdminModer()
	{
		$tmplAdmin = Common::getOption('administration', 'tmpl');
		return $tmplAdmin == 'modern';
	}
}