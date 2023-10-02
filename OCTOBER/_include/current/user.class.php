<?php
/* (C) Websplosion LLC, 2001-2021

IMPORTANT: This is a commercial software product
and any kind of using it must agree to the Websplosion's license agreement.
It can be found at http://www.chameleonsocial.com/license.doc

This notice may not be removed from the source code. */

// cache userinfo like name and other profile fields

Class User {

    static private $cacheInfoBasic = array();

    static $blockOptions = array(
        'mail' => 'block_mail',
        'im' => 'block_im',
        'audiochat' => 'block_audio_chat',
        'videochat' => 'block_video_chat',
        'games' => 'block_flash_games',
        'wall' => 'block_wall',
    );
    static $error = array();
    static $orientations = null;

    static private $maxPopularityInCity = array();
    static private $levelOfPopularity = array();
    static private $popularInYourCity = array();
    static private $isActiveService = array();

    static $noPhotoPprivateInOffset = false;
    static $table_prefix  = '';
    static $onlineTimeBroadcast = 60;


    static function setNoPhotoPprivateInOffset($value = true)
    {
        self::$noPhotoPprivateInOffset = $value;
    }

    static function getNoPhotoPprivateInOffset()
    {
        /*$optionTemplateName = Common::getTmplName();
        if (IS_DEMO && $optionTemplateName == 'edge') {
            return true;
        }*/
        return self::$noPhotoPprivateInOffset;
    }

    static function getOrientationInfo($userOrientation, $dbIndex = DB_MAX_INDEX)
    {
        if(self::$orientations === null) {
            $orientations = DB::rows('SELECT * FROM const_orientation', $dbIndex, true);
            if($orientations) {
                foreach ($orientations as $orientation) {
                    self::$orientations[$orientation['id']] = $orientation;
                }
            }
        }
        return isset(self::$orientations[$userOrientation]) ? self::$orientations[$userOrientation] : null;
    }

    static function isFreeAccess($goldDays = NULL, $orientation = NULL)
    {
       if ($goldDays == NULL && $orientation == NULL) {
            global $g_user;
            //var_dump($g_user);
            //die();
            if (isset($g_user['gold_days']) && isset($g_user['orientation'])) {
                $goldDays = $g_user['gold_days'];
                $orientation = $g_user['orientation'];
            } else {
                if($g_user['user_id']) {
                    $user = User::getInfoBasic($g_user['user_id']);
                    $goldDays = $user['gold_days'];
                    $orientation = $user['orientation'];
                } else {
                    return false;
                }
            }
       }
       $row = self::getOrientationInfo($orientation);
       $result = isset($row['free']) && $row['free'] != 'none';
       if (Common::getOption('set', 'template_options') != 'urban') {
           $result = $result && self::isPaidFree();
       }
       return $result;
       //return (isset($row['free']) && $row['free'] != 'none' && self::isPaidFree()) ? true : false;
    }

    static function getBlockOptions()
    {
        return self::$blockOptions;
    }

    static function getBlockOptionsActive()
    {
        $blockOptionsActive = array();

        $blockOptions = self::getBlockOptions();

        foreach($blockOptions as $blockOption => $value) {
            if(($blockOption == 'wall' && Wall::isActive()) || Common::isOptionActive($blockOption) !== false) {
                $blockOptionsActive[$blockOption] = $blockOptions[$blockOption];
            }
        }

        return $blockOptionsActive;
    }

    static function getBlockOptionsActiveSections()
    {
        return array_keys(self::getBlockOptionsActive());
    }

    static function isBlocked($option, $from, $to, $dbIndex = DB_MAX_INDEX)
    {
        if(!Common::isOptionActive('contact_blocking')) {
            return 0;
        }
        $options = array_keys(self::getBlockOptions());
        if(!in_array($option, $options)) {
            return 0;
        }

        $sql = 'SELECT ' . to_sql($option, 'Plain') . ' FROM user_block_list
            WHERE user_from = ' . to_sql($from, 'Number') . '
                AND user_to = ' . to_sql($to, 'Number');
        return DB::result($sql, 0, $dbIndex, true);
    }

    static function isEntryBlocked($from, $to, $dbIndex = DB_MAX_INDEX)
    {
        if(!Common::isOptionActive('contact_blocking')) {
            return 0;
        }

        $sql = 'SELECT `id` FROM `user_block_list`
                 WHERE user_from = ' . to_sql($from, 'Number') . '
                   AND user_to = ' . to_sql($to, 'Number');
        return DB::result($sql, 0, $dbIndex, true);
    }

    static function blockedOptions($from, $to, $dbIndex = DB_MAX_INDEX)
    {
        $sql = 'SELECT * FROM user_block_list
            WHERE user_from = ' . to_sql($from, 'Number') . '
                AND user_to = ' . to_sql($to, 'Number');
        $result = DB::row($sql, $dbIndex);
        return $result;
    }

    static function blockOption($option, $from, $to, $dbIndex = DB_MAX_INDEX)
    {
        $where = ' WHERE user_from = ' . to_sql($from, 'Number') . '
                AND user_to = ' . to_sql($to, 'Number');

        $block = to_sql($option, 'Plain') . ' = 1 ';

        $sql = 'SELECT id FROM user_block_list ' . $where;
        if(DB::result($sql, 0, $dbIndex)) {
            $sql = 'UPDATE user_block_list
                SET ' . $block . $where;
        } else {
            $sql = 'INSERT INTO user_block_list
                SET user_from = ' . to_sql($from, 'Number') . ',
                    user_to = ' . to_sql($to, 'Number') . ',
                    ' . $block;
        }
        DB::execute($sql);
    }

    static function blockAll($from, $to, $dbIndex = DB_MAX_INDEX)
    {
        $options = array_keys(self::getBlockOptions());
        foreach($options as $option) {
            self::blockOption($option, $from, $to, $dbIndex);
        }
    }

    static function blockRemoveAll($from, $to)
    {
        $sql = 'DELETE FROM user_block_list
            WHERE user_from = ' . to_sql($from, 'Number') . '
                AND user_to = ' . to_sql($to, 'Number');
        DB::execute($sql);
    }
    static function friendRequestSend($user, $comment, $isSendmail = true) {
        global $g_user;
        global $g;

        // ??? Subjects of such can be removed
        $lang = loadLanguage($user['lang'], 'main');

        $subject = l('friend_add_subject',$lang);
        $text = l('friend_add_text',$lang);
        if($subject == 'friend_add_subject') {
            $lang = loadLanguage(Common::getOption('main','lang_value'), 'main');
            $subject = l('friend_add_subject',$lang);
        }
        if($text == 'friend_add_text') {
            $lang = loadLanguage(Common::getOption('main','lang_value'), 'main');
            $text = l('friend_add_text',$lang);
        }
        if(!empty($comment)) {
            $comment = $comment.'
                ';
        }

        $subject = Common::replaceByVars($subject, array('NAME' => $g_user['name']));
        $text = Common::replaceByVars($text, array('COMMENT' => $comment,'NAME' => $g_user['name']));

        DB::execute("INSERT IGNORE INTO friends_requests SET user_id = " . $g_user["user_id"] . ", friend_id = " . $user["user_id"] . ", message = " . to_sql($comment,'Text') . ", accepted = 0, created_at = NOW();");

        if ($isSendmail
            && Common::isEnabledAutoMail('friend_request')) {
            $block = User::isBlocked('mail', $user['user_id'], guid());
            if (empty($block)) {
                /*DB::execute("
                        INSERT INTO mail_msg (user_id, user_from, user_to, folder, subject, text, date_sent)
                            VALUES(
                            " . $user["user_id"] . ",
                            " . $g_user['user_id'] . ",
                            " . $user["user_id"] . ",
                            " . 1 . ",
                            " . to_sql($subject) . ",
                            " . to_sql($text) . ",
                            " . time() . ")
                        ");
                DB::execute("UPDATE user SET new_mails=new_mails+1 WHERE user_id=" . $user["user_id"] . "");*/
                $optionSendMail = $user['set_email_mail'];
                if (Common::getOption('set', 'template_options') == 'urban') {
                    $optionSendMail = $user['set_notif_new_msg'];
                }
                if ($optionSendMail != '2') {
                    $vars = array('name' => $g_user['name'],
                                  'comment' => $comment,
                                  'uid' => $g_user["user_id"],
                                  'uid_send' => $user['user_id']
                    );
                    Common::sendAutomail($user['lang'], $user['mail'], 'friend_request', $vars);
                }
            }
        }
    }
    static function getFriendsList($uid, $include = false)
    {
        $key = 'friendsList_' . $uid . '_' . intval($include);
        $friendsList = Cache::get($key);
        if($friendsList === null) {
            $friendsList = self::friendsList($uid, $include);
            Cache::add($key, $friendsList);
        }

        return $friendsList;
    }

    static function getFriendsListMutual($first, $second, $includeFirst = false, $includeSecond = false)
    {
        $friendsListFirst = explode(',', self::getFriendsList($first, $includeFirst));
        $friendsListSecond = explode(',', self::getFriendsList($second, $includeSecond));

        // intersection of lists
        $list = array_intersect($friendsListFirst, $friendsListSecond);
        if($includeFirst) {
            $list[] = $first;
        }
        if($includeSecond) {
            $list[] = $second;
        }
        $list = implode(',', array_unique($list));

        return $list;
    }

    static function add($admin = false)
    {
        global $g;
        global $g_user;

        $optionSet = Common::getOption('set', 'template_options');

        $userName = get_session("j_name");
        $email = get_session('j_mail');
        $phone = get_session('j_phone');
        $signup_as = get_session('j_signup_as');

        if (!trim($userName) || !trim($email)) {
            return 0;
        }

        set_session('j_captcha', false);

        $partner = (int) get_session('partner');

        $birth = get_session('j_year') . '-' . get_session('j_month') . '-' . get_session('j_day');

        $city = Common::getLocationTitle('city', get_session('j_city'));
        $state = Common::getLocationTitle('state', get_session('j_state'));
        $country = Common::getLocationTitle('country', get_session('j_country'));

        if ((IS_DEMO || $admin) && $optionSet != 'urban') {
            $sql_pay = "gold_days=9999, type='platinum',";
        } else {
            $sql_pay = "gold_days=0, type='none',";

            if($g['trial']['days'] > 0) {
                $timeStamp = time() + 3600; //+60 minutes
                $date = date('Y-m-d', $timeStamp);
                $hour = intval(date('H', $timeStamp));

                $sql_pay = 'gold_days = ' . to_sql($g['trial']['days'], 'Number') . ',
                    type = ' . to_sql($g['trial']['type'], 'Text') . ',
                    payment_day = ' . to_sql($date) . ',
                    payment_hour = ' . to_sql($hour) . ',';
            }

            if($g['trial']['credits'] > 0) {
                $sql_pay .=  'credits = ' . to_sql($g['trial']['credits'], 'Text') . ',';
        }
        }
        $isUserApproval = ($admin) ? false : Common::isOptionActive('manual_user_approval');
        $approval = 1;
        $hideTime = 0;
        if ($isUserApproval) {
            $approval = 0;
            $hideTime = 1;
        }
        /* URBAN */
        //$looking = get_param('looking', 1);
        //$defaultOnlineView = array(1 => 'B', 2 => 'M', 3 => 'F');
        //default_online_view=" . to_sql($defaultOnlineView[$looking]) . ",

        $bg = '';
        if ($optionSet == 'urban') {
            $bg = Common::getOption('default_profile_background');
        }

        $socialIDQuery='';
        $socialType=get_session('social_type');
        if($socialType){
            $socialID=get_session($socialType.'_id');
            if($socialID){
                $socialIDQuery=", ".$socialType."_id = ".to_sql($socialID,'Text');
            }
        }


        $cityId = get_session("j_city");
        $geoPosition = self::getGeoPosition($cityId);
        $geoPositionSql = '';
        if ($geoPosition !== false) {
            foreach ($geoPosition as $key => $value) {
                $geoPositionSql .= ", `{$key}` = " . to_sql($value);
            }
        }

        $orientation = intval(get_session('j_orientation'));
        if(!$orientation) {
            $defaultOrientation = self::getDefaultOrientation();
            set_session('j_orientation', $defaultOrientation);
        }

        $sql = "INSERT IGNORE INTO user SET
			partner=" . $partner . ",
			" . $sql_pay . "
			name=" . to_sql($userName, "Text") . ",
			orientation=" . to_sql(get_session("j_orientation"), "Number") . ",
			p_orientation=" . to_sql(DB::result("SELECT search FROM const_orientation WHERE id=" . to_sql(get_session("j_orientation"), "Number")), "Number") . ",
			gender=" . to_sql(DB::result("SELECT gender FROM const_orientation WHERE id=" . to_sql(get_session("j_orientation"), "Number")), "Text") . ",
			mail=" . to_sql($email, 'Text') . ",
			password=" . to_sql(self::preparePasswordForDatabase(get_session("j_password")), "Text") . ",
			country_id=" . to_sql(get_session("j_country"), "Number") . ",
			state_id=" . to_sql(get_session("j_state"), "Number") . ",
			city_id=" . to_sql($cityId, "Number") . ",
			country=" . to_sql($country, "Text") . ",
			state=" . to_sql($state, "Text") . ",
			city=" . to_sql($city, "Text") . ",
			birth=" . to_sql($birth, 'Text') . ",
			phone=" . to_sql($phone, 'Text') . ",
			signup_as=" . to_sql($signup_as, 'Text') . ",
			p_age_from=" . to_sql(get_session("j_partner_age_from"), "Number") . ",
			p_age_to=" . to_sql(get_session("j_partner_age_to"), "Number") . ",
			horoscope=" . to_sql(zodiac($birth), "Number") . ",
			p_horoscope=0,
			active=" . to_sql($approval) . ",
			hide_time=" . to_sql($hideTime) . ",
			register='" . date('Y-m-d H:i:s') . "',
			last_visit='" . date('Y-m-d H:i:s') . "',
			last_ip=" . to_sql(IP::getIp(), 'Text') . ",
			set_email_mail='1',
			set_email_interest='1',
            profile_bg=" . to_sql($bg) . ",
			relation=" . to_sql(get_session("j_relation"), "Number") . ",
			auth_key=" . to_sql(md5(IP::getIp() . rand() . microtime() . rand() . $userName . rand() . $email)) . ",
			lang=" . to_sql($g['main']['lang_loaded']) . ",
            i_am_here_to=" . to_sql(intval(DB::result('SELECT MIN(id) FROM `const_i_am_here_to`')))
            . $geoPositionSql
            . $socialIDQuery;

        DB::execute($sql);
        set_session('social_id', '');
        if($socialType){
            set_session($socialType.'_id', '');
        }

        $uid = DB::insert_id();
        if (!$uid) {
            return $uid;
        }
        $g_user['user_id'] = $uid;
        $g_user['name'] = $userName;

        self::addToPartner($partner);
        self::emailAdd($email);

        #session_unset();
        if (!$isUserApproval) {
            set_session("user_id", $uid);
            set_session("user_id_verify", $uid);
        }
        CStatsTools::count('registrations', $uid);

        $sql = 'INSERT INTO userpartner
            SET user_id = ' . to_sql($uid, 'Number');
        DB::execute($sql);

        $userinfoNumbers = '';
        $userinfoTexts = '';
        foreach ($g['user_var'] as $k => $v) {
            $k = to_sql($k, 'Plain');
            $key = 'j_' . $k;
            $value = get_session($key);
            delses($key);

            if (substr($k, 0, 2) != 'p_') {
                /*if ($v[0] == 'text' && $value != '') {
                    $userinfoTexts .= $k . ' = ' . to_sql(Common::filterProfileText($value), 'Text') . ', ';
                } elseif ($v[0] == 'textarea' && $value != '') {
                    $userinfoTexts .= $k . ' = ' . to_sql(Common::filterProfileText($value), 'Text') . ', ';
                } elseif ($v[0] == 'from_table') {
                    if ($v[1] == 'int') {
                        $userinfoNumbers .= $k . ' = ' . to_sql($value, 'Number') . ', ';
                    } elseif ($v[1] == 'checks') {
                        $userinfoNumbers .= $k . ' = ' . to_sql($value, 'Number') . ', ';
                    }
                }*/

                if ($v['type'] == 'text' && $value != '') {
                    $userinfoTexts .= $k . ' = ' . to_sql(Common::filterProfileText($value), 'Text') . ', ';
                } elseif ($v['type'] == 'textarea' && $value != '') {
                    $userinfoTexts .= $k . ' = ' . to_sql(Common::filterProfileText($value), 'Text') . ', ';
                } elseif ($v['type'] == 'int') {
                    $userinfoNumbers .= $k . ' = ' . to_sql($value, 'Number') . ', ';
                } elseif ($v['type'] == 'checks') {
                    $userinfoNumbers .= $k . ' = ' . to_sql($value, 'Number') . ', ';
                }
            }
        }
        $userinfoNumbers = trim(trim($userinfoNumbers), ',');
        $userinfoTexts = trim(trim($userinfoTexts), ',');

        if ($userinfoNumbers != '') {
            $sql = 'INSERT INTO userinfo
                SET user_id = ' . $uid . ', ' . $userinfoNumbers;
        } else {
            $sql = 'INSERT INTO userinfo SET user_id = ' . $uid;
        }

        DB::execute($sql);

        if ($userinfoTexts != '') {
            if ($g['options']['texts_approval'] == 'N') {
                $sql = 'UPDATE userinfo
                    SET ' . $userinfoTexts . '
                    WHERE user_id = ' . $uid;
            } else {
                $sql = 'INSERT INTO texts
                    SET user_id = ' . $uid . ', ' . $userinfoTexts;
            }
            DB::execute($sql);
            if ($g['options']['texts_approval'] == 'Y'){
                if(Common::isEnabledAutoMail('approve_text_admin')){
                    $vars = array(
                        'name'  => User::getInfoBasic($uid,'name'),
                    );
                    Common::sendAutomail(Common::getOption('administration', 'lang_value'), Common::getOption('info_mail', 'main'), 'approve_text_admin', $vars);
                }

            }
        }

        if ($optionSet == 'urban') {
            $isCustomRegister = Common::isOptionActive('custom_user_registration', 'template_options');
            $usersLike = get_param_array('users_like');
            if ($usersLike) {
                foreach ($usersLike as $uidLike => $userLike) {
                    MutualAttractions::setWantToMeet($uidLike, 'Y');
                }
            }
            self::setDefaultParamsFilterUser($uid, true, $isCustomRegister);
        }

        if (!Common::isOptionActive('manual_user_approval')
                && Common::isOptionActive('wall_join_message_enabled')) {
            Wall::setUid($uid);
            Wall::add('comment', 0, $uid, 'joined the website');
        }

// GROUPS INVITE

        $key = get_session("group_key");
        $gid = get_session("group_id");

        if ($key != "" && $gid != "") {

            require_once(dirname(__FILE__) . '/../current/groups/tools.php');

            $group_id = $gid;


            $group['group_id'] = $gid;

            $groups_invite = DB::row("SELECT * FROM groups_invite WHERE group_id=" . $group['group_id'] . " AND invite_key=" . to_sql($key));
            if ($groups_invite) {
                CGroupsTools::create_group_member($group['group_id']);

                DB::execute("DELETE FROM groups_invite WHERE group_id=" . $group['group_id'] . " AND invite_key=" . to_sql($key));
            }

            set_session("group_key", "");
            set_session("group_id", "");
        }

        // GROUPS INVITE

        $g['client_error_off'] = true;

        if (Common::isEnabledAutoMail('join')) {
            $vars = array(
                'title' => $g['main']['title'],
                'name' => get_session('j_name'),
                'password' => get_session('j_password'),
            );
            Common::sendAutomail(Common::getOption('lang_loaded', 'main'), $email, 'join', $vars);
        }
        if (Common::isEnabledAutoMail('join_admin')) {
            $vars = array(
                'name'  => get_session('j_name'),
                'url' => $_SERVER['HTTP_HOST'],
                'uid' => $uid,
            );
            Common::sendAutomail(Common::getOption('administration', 'lang_value'), Common::getOption('info_mail', 'main'), 'join_admin', $vars);
        }
        if (!$socialIDQuery) {
            user_change_email($uid, $email);
        }

        Common::sendMailByAdmin($uid, $g_user['name'], 'welcoming_message', true);

        //send mobile verification email to user by deepak
        set_session("is_verified", 0);
        $verification_message = 'Hello '.$userName.',<br>
        You registered an account on '.Common::getOption('title', 'main').', before being able to use your account you need to verify that this is your email address by clicking here: <br>

        Kind Regards,<br>'.Common::getOption('title', 'main');

        $ver_headers = "";
        $ver_headers .= "From: " . Common::getOption('title', 'main') . "<" . Common::getOption('info_mail', 'main') . ">" . "\n";
        $ver_headers .= "Reply-To: " . "<" . Common::getOption('info_mail', 'main') . ">" . "\n";
        $ver_headers .= "Return-Path: " . "<" . Common::getOption('info_mail', 'main') . ">" . "\n";
        $ver_headers .= "Message-ID: <" . time() . "-" . Common::getOption('info_mail', 'main') . ">" . "\n";
        $ver_headers .= "X-Mailer: PHP v" . phpversion() . "\n";
        $ver_headers .= 'Date: ' . date("r") . "\n";
        $ver_headers .= 'MIME-Version: 1.0' . "\n";
        $ver_headers .= "Content-Type: text/html; charset=utf-8" . "\n";
        $ver_headers .= "Content-Transfer-Encoding: 8bit" . "\n";

        mail($email, 'Please verify your email for'.Common::getOption('title', 'main'), $verification_message, $ver_headers);

        $verification_code = random_int(100000, 999999);
        $sql = 'UPDATE user SET verification_code = '.$verification_code.' WHERE user_id = ' . $uid;
        DB::execute($sql);

        /*if (!$admin && Common::isEnabledAutoMail('welcoming_message')) {
            $vars = array('name' => $userName);
            $emailAuto = Common::sendAutomail(Common::getOption('lang_loaded', 'main'), '','welcoming_message', $vars, false, DB_MAX_INDEX, true);
            $subject = $emailAuto['subject'];
            $text = $emailAuto['text'];
            $data = array('user_id' => $uid,
                          'user_from' => $uid,
                          'user_to' => $uid,
                          'folder' => 1,
                          'new' => 'Y',
                          'subject' => $subject,
                          'text' => $text,
                          'date_sent' => time(),
                          'receiver_read' => 'N',
                          'system' => 1);
            DB::insert('mail_msg', $data);
            DB::execute('UPDATE `user` SET new_mails=new_mails+1 WHERE `user_id`=' . to_sql($uid, 'Number'));
            $data = array('from_user' => $uid,
                          'to_user' => $uid,
                          'mid' => 1,
                          'z' => time());
            DB::insert('im_open', $data);
            $data = array('from_user' => $uid,
                          'to_user' => $uid,
                          'msg' => 'welcoming_message',
                          'is_new' => 1,
                          'system' => 1,
                          'born' => date('Y-m-d H:i:s'),
                          'flag' => 1);
            DB::insert('im_msg', $data);
        }*/

        $g['client_error_off'] = false;

        return $uid;
    }

static function profileComplite() {
        global $g;
        global $g_user;

        g_user_full();

        // 3 = username,  email, birth
        $basic = 3;
        // 6 = username, email, birth,country,state and city
        $basiccount = 6;

        $personalc = 0;

        $personalcount = 0;

        $partnerc = 0;
        $partnercount = 0;
        $partner = Common::getOption('partner_settings', 'options');
        $personal = Common::getOption('personal_settings', 'options');
        if (!empty($g_user['country'])) {
            $basic++;
        }
        if (!empty($g_user['state'])) {
            $basic++;
        }
        if (!empty($g_user['city'])) {
            $basic++;
        }
        if(UserFields::isActive('age_range')) {
            if(!empty($g_user['p_age_from'])) {
                $partnerc++;
            }
            if(!empty($g_user['p_age_to'])) {
                $partnerc++;
            }
            $partnercount = 2;
        }
        UserFields::removeUnavailableField();
        foreach ($g['user_var'] as $k => $v) {
            if (UserFields::isActive($k)) {
                if ($k != 'age_range') {
                    if ($v['type'] == 'text' || $v['type'] == 'textarea' || $v['type'] == 'const') {
                        if (!empty($g_user[$k])) {

                            $basic++;
                        }
                        $basiccount++;
                    } elseif (substr($k, 0, 2) == 'p_') {
                        if($partner != "Y") {
                            continue;
                        }
                        if (!empty($g_user[$k])) {
                            $partnerc++;
                        }
                        $partnercount++;
                    } elseif ($personal == "Y") {
                        if (!empty($g_user[$k])) {
                            $personalc++;
                        }
                        $personalcount++;
                    }
                }
            }
        }
        if($partner != "Y") {
            $partnerc = 0;
            $partnercount = 0;
        }
        if($personal != "Y") {
            $personalc = 0;
            $personalcount = 0;
        }
        if($personalcount != 0 || $partnercount != 0 || $basiccount != 0) {
            $com_count = $personalcount + $partnercount + $basiccount;
            $complite = $basic + $partnerc + $personalc;
            $complite = ceil($complite / $com_count * 100);
        } else {
            $complite = 0;
        }
        if($partner == "Y" && $partnercount != 0) {
            $partnerc = ceil($partnerc / $partnercount * 100);
        }
        if($basiccount != 0) {
            $basic = ceil($basic / $basiccount * 100);
        }
        if($personal == "Y" && $personalcount != 0) {
            $personalc = ceil($personalc / $personalcount * 100);
        }
        $return = array(
            'completed' => $complite,
            'basic' => $basic,
            'personalc' => $personalc,
            'partnerc' => $partnerc,
        );
        return $return;
    }
    static function emailAdd($email)
    {
        $id = DB::result('SELECT mail FROM email WHERE mail = ' . to_sql($email));
        if(!$id) {
            $sql = 'INSERT IGNORE INTO email
                SET mail = ' . to_sql($email, 'Text');
            DB::execute($sql);
        }
    }

    static function emailRemove($email)
    {
        $sql = 'DELETE FROM email
            WHERE mail = ' . to_sql($email, 'Text');
        DB::execute($sql);
    }

    static function emailIsSubscribed($email)
    {
        $id = DB::result('SELECT id FROM email WHERE mail = ' . to_sql($email));
        return ($id > 0);
    }

    static function emailChange($emailOld, $emailNew)
    {
        if($emailOld !== $emailNew) {
            if(self::emailIsSubscribed($emailOld)) {
                self::emailRemove($emailOld);
                self::emailAdd($emailNew);
            }
        }
    }

    static function addToPartner($partner)
    {
        global $g;

        $partner = intval($partner);

        if ($partner == 0) {
            return;
        }

        $sql = 'UPDATE partner SET
			account = (account + ' . to_sql($g['options']['partner_price_user'], 'Number') . '),
			summary = (summary + ' . to_sql($g['options']['partner_price_user'], 'Number') . '),
			count_users = (count_users + 1)
			WHERE partner_id = ' . to_sql($partner, 'Number');
        DB::execute($sql);

        $p_partner = DB::result('SELECT p_partner FROM partner WHERE partner_id=' . to_sql($partner, 'Number'));

        $plus = ($g['options']['partner_percent_ref'] / 100) * $g['options']['partner_price_user'];
        $sql = 'UPDATE partner SET
            account = (account + ' . to_sql($plus, 'Number') . '),
			summary = (summary + ' . to_sql($plus, 'Number') . ')
			WHERE partner_id = ' . to_sql($p_partner, 'Number');
        DB::execute($sql);
    }

    static function getInfoFull($uid, $dbIndex = 0, $useCache = true, $updateData = null)
    {
        static $cache = array();

        if ($useCache && isset($cache[$uid])) {
            if ($updateData !== null) {
                $cache[$uid] = $updateData;
            }
            $info = $cache[$uid];
        } else {
            $year = to_sql(date('Y'), 'Text');
            $monthAndDay = to_sql(date('00-m-d'), 'Text');
            $sqls = array(
                //"SELECT *, ( $year - DATE_FORMAT(birth, '%Y') - ($monthAndDay < DATE_FORMAT(birth, '00-%m-%d') ) ) AS age
                //FROM user WHERE user_id = " . to_sql($uid, 'Number'),
                'SELECT * FROM userinfo WHERE user_id = ' . to_sql($uid, 'Number'),
                'SELECT * FROM userpartner WHERE user_id = ' . to_sql($uid, 'Number'),
            );

            $user = array(self::getInfoBasic($uid, false, $dbIndex));

            foreach ($sqls as $sql) {
                $values = DB::row($sql, $dbIndex);
                if (!is_array($values)) {
                    $values = array();
                }
                $user[] = $values;
            }
            $user[3]['checkbox'] = self::getInfoCheckboxAll($uid, $dbIndex, false);
            $info = array_merge((array)$user[0], $user[1], $user[2], $user[3]);
            $cache[$uid] = $info;
        }

        return $info;
    }

    static function getInfoBasic($uid, $field = false, $dbIndex = 0, $cache = true)
    {
        $key = 'userinfo_' . $uid;
        $info = null;
        if ($cache) {
            $info = Cache::get($key);
        }
        if($info === null) {
            $year = to_sql(date('Y'), 'Text');
            $monthAndDay = to_sql(date('00-m-d'), 'Text');
            $sql = "SELECT *, ( $year - DATE_FORMAT(birth, '%Y') - ($monthAndDay < DATE_FORMAT(birth, '00-%m-%d') ) ) AS age
                      FROM `" . to_sql(self::$table_prefix . 'user', 'Plain') . "` WHERE user_id = " . to_sql($uid, 'Number');
            $info = DB::row($sql, $dbIndex);

            if(Common::isOptionActive('your_orientation') && isset($info['user_id'])) {
                $orientationInfo = self::getOrientationInfo($info['orientation'], $dbIndex);
                $info['p_orientation'] = isset($orientationInfo['search']) ? $orientationInfo['search'] : 0;
            }

            Cache::add($key, $info);
            if(isset($info['email']) && $info['email']) {
                Cache::add('userinfo_by_email_' . $info['email'], $info);
            }
        }

        $return = $info;

        if($field !== false) {
            $return = isset($info[$field]) ? $info[$field] : '';
        }

        return $return;
    }

    static function getInfoBasicByEmail($email, $field = false, $dbIndex = 0)
    {
        $key = 'userinfo_by_email_' . $email;
        $info = Cache::get($key);
        if($info === null) {
            $sql = 'SELECT user_id FROM user WHERE mail = ' . to_sql($email);
            $uid = DB::result($sql, 0, $dbIndex);
            $info = self::getInfoBasic($uid, false, $dbIndex);
        }

        $return = $info;

        if($field !== false) {
            $return = isset($info[$field]) ? $info[$field] : '';
        }

        return $return;
    }

    static function getInfoCheckbox($uid, $field, $dbIndex = 0)
    {
        $sql = "SELECT UC.value AS 'options'
                  FROM config AS C,
                       users_checkbox AS UC
                 WHERE C.module = 'user_var'
                   AND C.option = " . to_sql($field) . "
                   AND UC.field = C.id
                   AND UC.user_id = " . to_sql($uid, 'Number');

        return DB::column($sql, $dbIndex);

    }

    static function getInfoCheckboxAll($uid, $dbIndex = 0, $useCache = true)
    {
        $sql = "SELECT UC.field, UC.value
                  FROM config AS C,
                       users_checkbox AS UC
                 WHERE C.module = 'user_var'
                   AND UC.field = C.id
                   AND UC.user_id = " . to_sql($uid, 'Number');
        $all = array();
        $chks = DB::rows($sql, $dbIndex, $useCache);
        foreach ($chks as $chk) {
            if (!isset($all[$chk['field']])) $all[$chk['field']] = array();
            $all[$chk['field']][] = $chk['value'];
        }
        return $all;
    }

    static function getPhotoDefault($uid, $size = 's', $returnId = false, $gender = false, $dbIndex = DB_MAX_INDEX, $noVisPrivate = false, $getPhotoInfo = false, $isOnlyAvailableToAll = false, $cityUserVisitor = false, $groupId = 0, $cache = true)
    {
        global $g;

        $key = 'user_photo_default_' . $size . '_' . intval($returnId) . '_' . intval($noVisPrivate) . '_' . intval($getPhotoInfo) . '_' . intval($isOnlyAvailableToAll) . '_' . $uid;
        $whereGroup = ' AND `group_id` = 0';
        if ($groupId) {
            $key = 'group_photo_default_' . $size . '_' . intval($returnId) . '_' . intval($noVisPrivate) . '_' . intval($getPhotoInfo) . '_' . intval($isOnlyAvailableToAll) . '_' . $uid . '_' . $groupId;
            $gender = '';
            $whereGroup = ' AND `group_id` = ' . to_sql($groupId);
        }
        $photoDefault = null;
        if ($cache) {
            $photoDefault = Cache::get($key);
        }
        if($photoDefault === null) {
            $isHidePrivatePhoto = CProfilePhoto::isHidePrivatePhoto();
            if ($gender === false) {
                /*
                if($uid == 638) {
                    echo error_get_backtrace();
                    die();
                }
                echo "getInfoBasic($uid, 'gender', $dbIndex)<br>";
                 */
                $gender = self::getInfoBasic($uid, 'gender', $dbIndex);
            }

            $noVisPrivateEdge = false;
            $optionTmplName = Common::getTmplName();
            if ($optionTmplName == 'edge' && Common::isOptionActive('hide_private_photos', 'edge_general_settings')){
                $noVisPrivateEdge = true;
            }
            $whereVis = '';
            if ($uid != guid() || $isOnlyAvailableToAll) {
                $whereVis = $g['sql']['photo_vis'];
                if ($noVisPrivate || $noVisPrivateEdge) {
                    $whereVis .= " AND private = 'N'";
                }
            } elseif($noVisPrivateEdge) {
                $whereVis .= " AND private = 'N'";
            }

            $table = 'photo';
            if ($cityUserVisitor) {
                $table = City::getTable('city_photo');
                $key .= '_city_visitor';
            }
            $field = 'default';
            if ($groupId) {
                $field = 'default_group';
            }
            $sql = 'SELECT * FROM ' . $table . '
                     WHERE user_id = ' . to_sql($uid, 'Number') .
                     " AND visible != 'P' "
                        . $whereVis
                        . $whereGroup
                 . ' ORDER BY `' . $field . '` ASC, `visible` ASC, `private` DESC, photo_id ASC LIMIT 1';

            $photo = DB::row($sql, $dbIndex, $cache);

            if (isset($photo['photo_id']) && $photo['default'] != 'Y') {
                $sql = "UPDATE `photo`
                           SET `" . $field . "` = 'Y'
                         WHERE `photo_id` = " . to_sql($photo['photo_id']);
                DB::execute($sql);
            }
            if ($returnId) {
                $result = 0;
                if (isset($photo['photo_id'])) {
                    if ($getPhotoInfo) {
                        $result = $photo;
                    } else {
                        $result = $photo['photo_id'];
                    }
                    if ($isHidePrivatePhoto && $photo['private'] == 'Y') {
                        $result = 0;
                    }
                }
                Cache::add($key, $result);
                return $result;
                //return isset($photo['photo_id']) ? $photo['photo_id'] : 0;
            }
            if ($isHidePrivatePhoto && isset($photo['private']) && $photo['private'] == 'Y') {
                $photo = NULL;
            }
            $photoDefault = self::getPhotoFile($photo, $size, $gender, $dbIndex, $cityUserVisitor, $groupId);
            Cache::add($key, $photoDefault);
        }
        return $photoDefault;
    }

    static function getPhotoProfile($photoId, $size = 's', $gender = '', $dbIndex = DB_MAX_INDEX)
    {
        $sql = 'SELECT p.*, u.gender FROM photo as p
            JOIN user AS u ON u.user_id = p.user_id
            WHERE photo_id = ' . to_sql($photoId, 'Number');
        $photo = DB::row($sql, $dbIndex, true);
        if($gender == '' && isset($photo['gender'])) {
            $gender = $photo['gender'];
        }
        return self::getPhotoFile($photo, $size, $gender, $dbIndex);
    }

    static function getPhotoFile($photo, $size, $gender, $dbIndex = DB_MAX_INDEX, $cityUserVisitor = false, $groupId = 0)
    {
        global $g_user;

        $templatePrefix = '';
        if(Common::isOptionActive('private_photo_by_template', 'template_options')) {
            $templatePrefix = Common::getOption('name', 'template_options') . '_';
        }
        $templatePrefixGroup = '';
        if ($groupId) {
            $templatePrefixGroup = '_group';
        }

        $noVisPrivatePhoto = false;
        $noPrivatePhoto = Common::isOptionActiveTemplate('no_private_photos');
        if (isset($photo['private']) && $photo['private'] == 'Y' && !$noPrivatePhoto) {
            // path to photo if user have no access to view
            $ext = 'png';
            $extTmpl = Common::getOption('private_photo_ext', 'template_options');
            if ($extTmpl) {
                $ext = $extTmpl;
            }
            $photoPath = "{$templatePrefix}private_photo{$templatePrefixGroup}_{$size}.{$ext}";
            $noVisPrivatePhoto = true;
            // photo owner and friend can see photo
            if ($photo['user_id'] == $g_user['user_id'] || self::isFriend($g_user['user_id'], $photo['user_id'], $dbIndex)) {
                $photoPath = self::photoFileCheck($photo, $size, $gender, true, false, '', $groupId);
                $noVisPrivatePhoto = false;
            }
        } else {
            $photoPath = self::photoFileCheck($photo, $size, $gender, true, $cityUserVisitor, '', $groupId);
        }
        $key = "no_vis_private_photo_{$photo['user_id']}_{$photo['photo_id']}_{$groupId}";
        Cache::add($key, $noVisPrivatePhoto);
        return $photoPath;
    }

// CHECK photo file
    static function photoFileCheck($photo, $size, $gender = '', $version = true, $cityUserVisitor = false, $prfPhoto = '', $groupId = 0)
    {
        global $g;

        $templatePrefix = '';
        if(Common::isOptionActive('no_photo_by_template', 'template_options')) {
            $templatePrefix = Common::getOption('name', 'template_options') . '_';
        }
        $templatePrefixGroup = '';
        if ($groupId) {
            $templatePrefixGroup = '_group';
        }

        $folder = '';
        if ($cityUserVisitor) {
            $folder = 'city/';
        }
        $file = "{$g['path']['dir_files']}{$folder}photo/{$photo['user_id']}_{$photo['photo_id']}_$size.jpg";
        if ($photo && ((isset($photo['visible']) && $photo['visible'] != 'P') || file_exists($file))) {
            $file = "{$folder}photo/{$photo['user_id']}_{$photo['photo_id']}_$size.jpg" . (($version && $photo['version']) ? '?v=' . $photo['version'] : '');
        } else {
            if ($prfPhoto) {
                $prfPhoto = '_' . $prfPhoto;
            }
            $filePlug = '';
            $exts = array('.jpg', '.png');
            if ($gender != '') {
                $file = "{$templatePrefix}nophoto_{$gender}{$prfPhoto}_{$size}";
                foreach ($exts as $ext) {
                    if (file_exists("{$g['path']['dir_files']}/{$file}{$ext}")) {
                        $filePlug = $file . $ext;
                        break;
                    }
                }
            } else {
                $file = "{$templatePrefix}nophoto{$prfPhoto}{$templatePrefixGroup}_{$size}";
                foreach ($exts as $ext) {
                    if (file_exists("{$g['path']['dir_files']}/{$file}{$ext}")) {
                        $filePlug = $file . $ext;
                        break;
                    }
                }
            }
            if (!$filePlug) {
                $filePlug = '1px.png';
                $file = "{$templatePrefix}nophoto{$prfPhoto}{$templatePrefixGroup}_$size";
                foreach ($exts as $ext) {
                    if (file_exists("{$g['path']['dir_files']}/{$file}{$ext}")) {
                        $filePlug = $file . $ext;
                        break;
                    }
                }
            }
            $file = $filePlug;
        }
        return $file;
    }

    static function getVideoFile($video, $size, $gender, $dbIndex = DB_MAX_INDEX)
    {
        global $g_user;

        $templatePrefix = '';

        if(Common::isOptionActive('private_photo_by_template', 'template_options')) {
            $templatePrefix = Common::getOption('name', 'template_options') . '_';
        }

        if (isset($video['private']) && $video['private'] == 1) {
            // path to photo if user have no access to view
            $videoPath = "{$templatePrefix}private_photo_{$size}.png";

            // photo owner and friend can see photo
            if ($video['user_id'] == $g_user['user_id'] || self::isFriend($g_user['user_id'], $video['user_id'], $dbIndex)) {
                $videoPath = self::videoFileCheck($video, $size, $gender);
            }
        } else {
            $videoPath = self::videoFileCheck($video, $size, $gender);
        }

        if(isset($video['version']) && $video['version']) {
            $videoPath .= '?v=' . $video['version'];
        }

        return $videoPath;
    }

    static function videoFileCheck($video, $size, $gender = '')
    {
        global $g;

        $templatePrefix = '';

        if(Common::isOptionActive('no_photo_by_template', 'template_options')) {
            $templatePrefix = Common::getOption('name', 'template_options') . '_';
        }

        if($size == 'video_src'){
            $ext = 'mp4';
            $file = "{$g['path']['dir_files']}video/{$video['id']}.$ext";
            $ext2 = 'flv';
            $file2 = "{$g['path']['dir_files']}video/{$video['id']}.$ext2";
            if (file_exists($file)) {
                $file = "video/{$video['id']}." . $ext;
                VideoHosts::$ext = $ext;
            }elseif(file_exists($file2)) {
                $file = "video/{$video['id']}." . $ext2;
                VideoHosts::$ext = $ext2;
            }elseif ($gender != '') {
                //$file = "{$templatePrefix}nophoto_{$gender}_{$size}.jpg";
                $file = "empty_video2.jpg";
            } else {
                //$file = "{$templatePrefix}nophoto_$size.jpg";
                $file = "empty_video2.jpg";
            }
        } else {
            $sizeName = '';
            if ($size) {
                $sizeName = '_' . $size;
            }
            $file = "{$g['path']['dir_files']}video/{$video['id']}$sizeName.jpg";
            $fileOld = "{$g['path']['dir_files']}video/{$video['id']}.jpg";
            if (file_exists($file)) {
                $file = "video/{$video['id']}$sizeName.jpg";
            } elseif(file_exists($fileOld)){
                $file = "video/{$video['id']}.jpg";
            } elseif ($gender != '') {
                //$file = "{$templatePrefix}nophoto_{$gender}_{$size}.jpg";
                $file = "empty_video2.jpg";
            } else {
                //$file = "{$templatePrefix}nophoto_$size.jpg";
                $file = "empty_video2.jpg";
            }
        }
        return $file;
    }

    static function isFriend($from, $to, $dbIndex = DB_MAX_INDEX, $useCache = true)
    {
        if($from == 0 || $to == 0 || $from == $to) {
            return false;
        }

        $sql = 'SELECT user_id
            FROM friends_requests
            WHERE accepted = 1
                AND user_id IN (' . to_sql($from, 'Number') . ',' . to_sql($to, 'Number') . ')
                AND friend_id IN (' . to_sql($from, 'Number') . ',' . to_sql($to, 'Number') . ')';
        return DB::result($sql, 0, $dbIndex, $useCache);
    }

    static function isFriendForPhoto($from, $to, $dbIndex = DB_MAX_INDEX)
    {

        $noPrivatePhoto = Common::isOptionActiveTemplate('no_private_photos');
        if ($noPrivatePhoto) {
            return $from == guid() ? $to : $from;
        }

        return self::isFriend($from, $to, $dbIndex);
    }

    static function isExistsByUid($uid)
    {
        $uid = intval($uid);
        $exists = false;
        if($uid) {
            $uid = User::getInfoBasic($uid, 'user_id');
            if($uid) {
                $exists = true;
            }
        }
        return $exists;
    }

    static function loginByCookies($dbIndex = 0)
    {
        if(get_session('user_id') == '' && !get_session('logout')) {
            $name = trim(get_cookie('c_user'));
            $password = trim(get_cookie('c_password'));

            if ($name != '' && $password != '') {
                $user = User::getUserByLoginAndPassword($name, $password);
                if($user) {
                    set_session('user_id', $user['user_id']);
    				set_session('user_id_verify', $user['user_id']);
                } else {
                    set_cookie('c_user', '', -1);
                    set_cookie('c_password', '', -1);
                }
            }
        }
    }

    static function logout()
    {
        self::logoutWoRedirect();
        redirect('index.php');
    }

    static function logoutWoRedirect()
    {
        $sql = 'UPDATE user
                   SET last_visit = ' . to_sql(date('Y-m-d H:i:s', intval(time() - Common::getOption('online_time') * 60)), 'Text') . '
                 WHERE user_id = ' . to_sql(guid(), 'Number');
        DB::execute($sql);
        set_cookie('c_user', '', -1);
        set_cookie('c_password', '', -1);
        set_session('user_id', '');
        set_session('user_id_verify', '');
        set_session('ip_city_info', '');
        set_session('logout', 1);
    }

    static function redirectToHomePage()
    {
        $urlHomePage = 'home.php';
        if ($g_user['user_id'] != 0 && $g['options']['feed_as_home_page'] == 'Y') {
            $urlHomePage = 'wall.php';
        }
    }

    static function isGenderViewActive($index = DB_MAX_INDEX)
    {
        $active = false;
        $sql = 'SELECT COUNT(DISTINCT(gender)) FROM const_orientation';
        #if(DB::result($sql, 0, $index) > 1) {
        if(DB::result_cache('count_genders', 30, $sql, 0, $index) > 1) {
            $active = true;
        }
        return $active;
    }

    static function defaultOnlineView($gender = null)
    {
        global $g;

        $where = '';
        if(UserFields::isActive('orientation') && self::isGenderViewActive()) {
            if (Common::isOptionActive('your_orientation')) {
                $where = $g['sql']['your_orientation'];
            } else {
                if (Common::isOptionActive('user_choose_default_profile_view')) {
                    $onlineView = $gender === null ? guser('default_online_view') : $gender;
                    if($onlineView != 'B' && $onlineView != null) {
                        $where = ' AND gender = ' . to_sql($onlineView, 'Text');
                    }
                }
            }

        }

        return $where;
    }

    static function updateLastVisit($uid)
    {
        $sql = 'UPDATE user
            SET last_ip = ' . to_sql(IP::getIp(), 'Text') . ',
                last_visit = ' . to_sql(date('Y-m-d H:i:s'), 'Text') . '
            WHERE user_id = ' . to_sql($uid, 'Number');
        DB::execute($sql);
    }

    static function usersNew($city = false)
    {
        global $g;

        $where = ' AND user_id != ' . to_sql(guser('user_id'));
		if ($g['options']['your_orientation'] == 'Y') {
            $where .= ' AND orientation = ' . to_sql(guser('p_orientation'), 'Number');
        }

        if($city !== false) {
            $city = guser('city_id');
        }

        if($city) {
            $where .= ' AND city_id = ' . to_sql($city, 'Number');
        }

        $sql = 'SELECT COUNT(*) FROM user
            WHERE register > ' . to_sql(date('Y-m-d H:i:s', (time() - 60 * 60 * 24 * $g['options']['new_time'])), 'Text') . $where;
        $result = DB::result_cache('users_new' . to_php_alfabet($g['sql']['your_orientation']) . '_' . $city, 30, $sql);

        return $result;
    }

    static function parseUserinfoModule(&$html, $uid = false)
    {
        global $g;
        global $g_user;

        if($uid === false) {
            $vars = $g_user;
        } else {
            $vars = User::freeAccessApply(User::getInfoBasic($uid));
        }

        if($vars['user_id'] == guid()) {
            $urlProfile = 'profile_view.php';
        } else {
            $display = User::displayProfile();
            $urlProfile = "search_results.php?display={$display}&name={$vars['name']}";
            $vars['class_wall_info_other'] = 'wall_info_other';
        }

        $vars['url_profile'] = $urlProfile;
        $vars['photo'] = User::getPhotoDefault($vars['user_id'], 'r', false, $vars['gender']);

        $vars['city_title'] = Common::itemTitleOrBlank($vars['city'], '');
        $vars['state_title'] = Common::itemTitleOrBlank($vars['state'], '');
        $vars['country_title'] = Common::itemTitleOrBlank($vars['country'], '');

        $vars['users_new'] = User::usersNew();
        $vars['users_new_near'] = User::usersNew(true);

        htmlSetVars($html, $vars);

        $isFriend = false;

//        if(guid() && $vars['user_id'] != guid()) {
//            $isFriend = User::isFriend(guid(), $uid);
//            if($isFriend) {
//                $html->parse('wall_unfriend_style', false);
//                $html->parse('wall_unfriend', false);
//            } else {
//                $html->parse('wall_add_friend', false);
//            }
//        }
        if ($vars['city_title'] != '') {
            $html->parse('city', false);
        }
        if ($vars['state_title'] != '') {
            $html->parse('state', false);
        }
        if ($vars['country_title'] != '') {
            $html->parse('country', false);
        }

        if($vars['user_id'] == guid()) {

            if(Common::isOptionActive('recorder')) {
                $html->setvar('unique', str_replace('.', '_', domain()));
                $html->parse('recorder_button');
                $html->parse('myrecorder_swf');
            }

            if($g_user['new_mails'])
                $html->parse('new_mails_exists', true);
            else
                $html->parse('new_mails_none', true);
            if($g_user['new_views'])
                $html->parse('new_views_exists', true);
            else
                $html->parse('new_views_none', true);

            if (self::isYourOrientationSearch() && $html->varExists('p_orientation_for_search')) {
                $html->setvar("p_orientation_for_search", $g_user['p_orientation']);
            }

            $html->parse('wall_info_my');
        } else {
            $html->setvar('invite_friends_class', 'last');
            $imResult = User::parseImLink($html, $vars['user_id'], $vars['type'], $vars['gold_days'], 'im');

            if(guid() && $vars['user_id'] != guid()) {
                $isFriend = User::isFriend(guid(), $uid);
                if($isFriend) {
                    $html->parse('im_delimiter', false);
                    $html->parse('wall_unfriend_style', false);
                    $html->parse('wall_unfriend', false);
                } else {
                    if(!$imResult) {
                        $html->parse('im_delimiter_add_friend', false);
                    }
                    $html->parse('wall_add_friend', false);
                }
            }

            if(Common::isOptionActive('gallery') && $html->blockexists('wall_gallery_images')) {
                $albumAccess = '"public"';
                if($isFriend) {
                    $albumAccess = '"public", "friends"';
                }

                $sqlBase = 'FROM gallery_images AS i
                    JOIN gallery_albums AS a ON i.albumid = a.id
                    WHERE i.user_id = ' . to_sql($vars['user_id']) . '
                        AND a.access IN (' . $albumAccess . ')';

                $sql = 'SELECT COUNT(*) ' . $sqlBase;
                $imagesCount = DB::result($sql);

                // choose random images
                if($imagesCount >= 7) {
                    $sql = 'SELECT i.*, a.*, i.id AS img_id ' . $sqlBase . '
                        ORDER BY RAND()
                        LIMIT 7';
                    DB::query($sql);
                    while($image = DB::fetch_row()) {
                        $imageUrl = 'gallery/thumb/' . $image['user_id'] . '/' . $image['folder'] . '/' . $image['filename'];
                        $html->setvar('wall_gallery_image_url', $imageUrl);
                        $html->setvar('wall_gallery_image_id', $image['img_id']);
                        $html->parse('wall_gallery_image');
                    }
                    $html->parse('wall_gallery_images');
                }
            }

            $html->parse('wall_profile_info');
        }
    }

    static function friendsList($uid, $includeUser = false)
    {
        if($includeUser) {
            $uids = $uid;
        } else {
            $uids = 0;
        }

        $index = 4;

        $sql = 'SELECT user_id, friend_id FROM friends_requests
            WHERE (user_id = ' . to_sql($uid, 'Number') . '
                OR friend_id = ' . to_sql($uid, 'Number') . ')
                AND accepted = 1';

        $rows = DB::rows($sql, $index, true);

        if($rows) {
            foreach ($rows as $row) {
                if ($row['friend_id'] != $uid) {
                    $uids .= ',' . $row['friend_id'];
                } else {
                    $uids .= ',' . $row['user_id'];
                }
            }
        }

        return $uids;
    }

    static function displayProfile()
    {
        $display = 'profile';
        $home_page_mode = Common::getOption('home_page_mode');
        if($home_page_mode == 'social') {
            $display = 'profile_info';
        }
        return $display;
    }

    static function displayWall()
    {
        /*$display = 'profile';
        $home_page_mode = Common::getOption('home_page_mode');
        if($home_page_mode !== 'social') {
            $display = 'wall';
        }*/
        $display = 'wall';
        return $display;
    }

    static function delete($uid, $redirect = 'index.php')
    {
        delete_user($uid);

        set_cookie('c_user', '', -1);
        set_cookie('c_password', '', -1);
        set_session('user_id', '');
        set_session('user_id_verify', '');

        if($redirect) {
            redirect($redirect);
        }
    }

    static function friendAction($fid = null, $uid = null)
    {
        $response = false;
        if ($uid === null) {
            $uid = guid();
        }
        if ($fid === null) {
            $fid = get_param('uid');
        }

        $action = get_param('action');
        if ($action && $fid && $uid != $fid) {
            if($action == 'approve') {
                $result = self::friendApprove($fid, $uid);
                if ($result) {
                    CIm::updateSystemMessagePrivateAccess($fid, 'you_granted_access', 'private_photo_request_approved');
                }
                $response = 'approve';
            } elseif ($action == 'decline') {
                self::friendDecline($fid, $uid);
                $response = 'decline';
            } elseif ($action == 'remove') {
                $isFriendRequestExists = self::isFriendRequestExists($fid, $uid);
                if ($isFriendRequestExists) {
                    if ($isFriendRequestExists == $uid) {
                        $where = "`to_user` = " . to_sql($fid)
                               . " AND `from_user` = " . to_sql($uid)
                               . "  AND `system` = 1 AND (`msg` = 'private_photo_request' OR `msg` = 'private_photo_report')";
                        DB::delete('im_msg', $where);
                        CIm::closeEmptyOneIm($fid);
                    } else {
                        CIm::updateSystemMessagePrivateAccess($fid, 'private_photo_request_declined', 'private_photo_request_declined');
                    }
                }
                self::friendDelete($uid, $fid);
                $response = 'remove';
            }  elseif ($action == 'request') {
                $user = self::getInfoBasic($fid);
                if ($user) {
					$response = 'request';
                    if (!self::isFriendRequestExists($fid, $uid)) {
                        self::friendRequestSend($user, '');
                        $response = 'request';
                        CIm::sendRequestMsgPrivateAccess($fid);
                    } elseif (self::isFriendRequestExists($fid, $uid) == $fid) {
                        self::friendApprove($fid, $uid);
                        CIm::updateSystemMessagePrivateAccess($fid, 'you_granted_access', 'private_photo_request_approved');
                        $response = 'approve';
                    }
                }
            }
        }
        return $response;
    }

    static function friendDelete($uid, $fid)
    {
		// accepted = 1 AND
        $sql = 'DELETE FROM friends_requests
			     WHERE  user_id IN(' . to_sql($uid, 'Number') . ',' . to_sql($fid, 'Number') . ')
                   AND friend_id IN(' . to_sql($uid, 'Number') . ',' . to_sql($fid, 'Number') . ')';
        DB::execute($sql);

        Wall::remove('friends', $fid, $uid);
        Wall::remove('friends', $uid, $fid);
    }

    static function friendApprove($uid, $fid, $isSendMail = true, $isWallAdd = true)
    {
        $result = false;
        if (self::isFriendRequestExists($uid, $fid)) {
            $sql = 'UPDATE friends_requests
                       SET created_at = NOW(),
                           accepted = 1
                     WHERE user_id = ' . to_sql($uid, 'Number') . '
                       AND friend_id = ' . to_sql($fid, 'Number') . '
                       AND accepted = 0';
            DB::execute($sql);

            if ($isWallAdd) {
                Wall::add('friends', $uid, $fid);
            }

            CStatsTools::count('added_to_friends');
            self::updateApproveActivity($uid, $fid);
            if ($isSendMail && Common::isEnabledAutoMail('friend_added')) {
                $user = User::getInfoBasic($uid);
                $optionSendMail = $user['set_email_mail'];
                if (Common::getOption('set', 'template_options') == 'urban') {
                    $optionSendMail = $user['set_notif_new_msg'];
                }
                if ($optionSendMail != '2') {
                    $vars = array('uid'  => $fid);
                    Common::sendAutomail($user['lang'], $user['mail'], 'friend_added', $vars);
                }
            }
            $result = true;
        }
        return $result;
    }

    static function friendDecline($uid, $fid)
    {
        $sql = 'DELETE FROM friends_requests
            WHERE user_id = ' . to_sql($uid, 'Number') . '
                AND friend_id = ' . to_sql($fid, 'Number') . '
                AND accepted = 0';
        DB::execute($sql);
    }

    static function friendAdd($uid, $fid, $accepted = 0, $isSendMail = true, $isWallAdd = true)
    {
        $sql = 'SELECT user_id FROM user
            WHERE user_id = ' . to_sql($uid, 'Number');
        if($uid == guid() || DB::result($sql) != $uid) {
            return;
        }

        // check if request already exists
        $id = self::isFriendRequestExists($uid, $fid);

        if(!$id) {
            $sql = 'INSERT IGNORE INTO friends_requests
                SET accepted = ' . to_sql($accepted, 'Number') . ',
                    created_at = ' . to_sql(date('Y-m-d H:i:s'), 'Text') . ',
                    user_id = ' . to_sql($uid, 'Number') . ',
                    friend_id = ' . to_sql($fid, 'Number');
            DB::execute($sql);
        }

        if($accepted == 1) {
            // update request if it was sent before
            self::friendApprove($uid, $fid, $isSendMail, $isWallAdd);
        }
    }

    static function isFriendRequestExists($from, $to, $accepted = 0, $dbIndex = DB_MAX_INDEX)
    {
        if($from == $to) {
            return false;
        }

        $sql = 'SELECT user_id FROM friends_requests
            WHERE user_id = ' . to_sql($from, 'Number') . '
                AND friend_id = ' . to_sql($to, 'Number') . '
                AND accepted = ' . to_sql($accepted, 'Number') . '
                OR user_id = ' . to_sql($to,'Number') . '
                AND friend_id = ' . to_sql($from,'Number') . '
                AND accepted = ' . to_sql($accepted, 'Number');
        return DB::result($sql, 0, $dbIndex, true);
    }


    static function isBookmarkExists($from,$to) {
           $sql = 'SELECT user_id FROM friends
               WHERE user_id = ' . to_sql($from, 'Number') . '
                   AND fr_user_id =' . to_sql($to,'Number');
           return DB::result($sql, 0, 0);
    }

    static function isFavoriteExists($from,$to) {
        $sql = 'SELECT `user_from` FROM users_favorite
		WHERE user_from=' .  to_sql($from, 'Number') . '
                AND user_to=' . to_sql($to,'Number');
          return DB::result($sql, 0, 2);
    }
    static function inviteDelete($id)
    {
        $sql = 'DELETE FROM invites
            WHERE id = ' . to_sql($id, 'Number');
        DB::execute($sql);
    }

    static function inviteBySession($accepted = 1)
    {
        $invite = get_session('invite');
        if($invite) {
            $inviteInfo = explode(':', $invite);
            if(count($inviteInfo == 2)) {
                $id = $inviteInfo[0];
                $key = $inviteInfo[1];

                $sql = 'SELECT * FROM invites
                    WHERE id = ' . to_sql($id, 'Number') . '
                        AND invite_key = ' . to_sql($key, 'Text');
                $row = DB::row($sql);
                if($row) {
                    self::friendAdd($row['user_id'], guid(), $accepted);
                    self::inviteDelete($id);
                }
            }
            set_session('invite', '');
        }
    }

    static function photoToDefault($photo_id)
    {
        if (!$photo_id) {
            return;
        }
        $whereGroup = ' AND `group_id` = 0';
        // current default photo
        $sql = 'SELECT photo_id FROM photo
            WHERE user_id = ' . to_sql(guid(), 'Number') . $whereGroup . '
                AND `default` = "Y"
            LIMIT 1';
        $photoDefault = DB::result($sql);
        if($photoDefault == 0) {
            $sql = 'SELECT photo_id FROM photo
                WHERE user_id = ' . to_sql(guid(), 'Number') . $whereGroup . '
                    ORDER BY photo_id ASC
                LIMIT 1';
            $photoDefault = DB::result($sql);
        }

        $sql = "UPDATE photo
            SET `default`='Y'
            WHERE photo_id = " . to_sql($photo_id, 'Number') . "
                AND user_id = " . to_sql(guid(), 'Number');
        DB::execute($sql);

        $sql = "UPDATE photo
            SET `default`='N'
            WHERE photo_id != " . to_sql($photo_id, 'Number') . $whereGroup . "
                AND user_id = " . to_sql(guid(), 'Number');
        DB::execute($sql);

        if($photoDefault && $photoDefault != $photo_id) {
            $sql = 'SELECT `private` FROM `photo`
                     WHERE `photo_id` = ' . to_sql($photo_id, 'Number')
                   . ' AND `user_id` = ' . to_sql(guid(), 'Number') . ' LIMIT 1';
            $access = (DB::result($sql) == 'Y') ? 'friends' : 'public';
            Wall::add('photo_default', $photo_id, false, $photo_id, false, 0, $access);
        }
    }

    //check is default photo set
    static function photoDefaultCheck(){
        // current default photo
        $sql = 'SELECT photo_id FROM photo
            WHERE user_id = ' . to_sql(guid(), 'Number') . '
                AND `default` = "Y"
            LIMIT 1';
        $photoDefault = DB::result($sql);
        if($photoDefault == 0){
            $sql = "UPDATE photo
                SET `default`='Y'
                WHERE user_id = " . to_sql(guid(), 'Number') .
                ' LIMIT 1';
            DB::execute($sql);
        }
    }

    static function getTitlePhotoToOffset($uid, $offset)
    {
        global $g, $g_user;

		$vis = '';
		if ($uid != $g_user['user_id']) {
			$vis = $g['sql']['photo_vis'];
		}

        if (self::getNoPhotoPprivateInOffset()) {
            $vis .= " AND private = 'N' ";
        }

        $sql = 'SELECT `description`, `private`, `user_id`
                  FROM `photo`
                 WHERE `user_id` = ' . $uid . " "
                . $vis .
			     " AND visible!='P' " .
               ' ORDER BY `photo_id` ASC LIMIT ' . $offset . ' , 1';

        $row=DB::row($sql, 0, true);
        $private=$row['private'];
        if($private=='Y' && (!self::isFriend($g_user['user_id'], $row['user_id']) && $row['user_id']!=$g_user['user_id'])){
            $title='';
        } else {
            $title=$row['description'];
        }
        return $title;
    }

    static function getIdPhotoToOffset($uid, $offset, $order = '`photo_id` ASC')
    {
        global $g, $g_user;

		$vis = '';
		if ($uid != $g_user['user_id']) {
			$vis = $g['sql']['photo_vis'];
		}

        if (self::getNoPhotoPprivateInOffset()) {
            $vis .= " AND private = 'N' ";
        }

        $sql = 'SELECT `photo_id`
                  FROM `photo`
                 WHERE `user_id` = ' . $uid . " "
                . $vis .
			     " AND visible!='P' " .
               ' ORDER BY ' . $order . ' LIMIT ' . $offset . ' , 1';

        return DB::result($sql, 0, DB_MAX_INDEX, true);
    }

    static function getTitleVideoToOffset($uid, $offset)
    {
        global $g, $g_user;

		$vis = ' AND `private` = 0';
        if ($uid != $g_user['user_id']) {
            $vis .= ' AND `active` = 1';
        }

        $sql = 'SELECT `subject`, `user_id`, `private`
                  FROM `vids_video`
                 WHERE `user_id` = ' . $uid . " "
                . $vis .
			     " AND active != 2 AND is_uploaded = 1 " .
               ' ORDER BY `id` ASC LIMIT ' . $offset . ' , 1';

        $row = DB::row($sql,0);
        if($row['private'] == 1 && $g_user['user_id'] != $uid && !self::isFriend($g_user['user_id'], $row['user_id'])){
            $title = '';
        } else {
            $title = $row['subject'];
        }

        return $title;
    }


    static function getIdVideoToOffset($uid, $offset)
    {
        global $g, $g_user;

		$vis = '';
                /*
		if ($uid != $g_user['user_id']) {
			//$vis = $g['sql']['photo_vis'];
			$vis = " AND visible='Y' ";
		}

                 */
        if ($uid != $g_user['user_id']) {
                        $vis = " AND active=1 ";
        }

        $vis.= " AND private = 0 ";

/*
        if (self::getNoPhotoPprivateInOffset()) {
            $vis .= " AND private = 'N' ";
        }
*/
        $sql = 'SELECT `id`
                  FROM `vids_video`
                 WHERE `user_id` = ' . $uid . " "
                . $vis .
			     " AND active<>2 AND is_uploaded = 1 " .
               ' ORDER BY `id` ASC LIMIT ' . $offset . ' , 1';

        return DB::result($sql, 0, DB_MAX_INDEX, true);
    }

    static function photoOffset($uid, $photo_id, $notChecking = true)
    {
		global $g, $g_user;

		$vis = '';
		if ($notChecking) {
			$vis = $g['sql']['photo_vis'];
		} elseif ($uid != $g_user['user_id'] && !$notChecking) {
			$vis = $g['sql']['photo_vis'];
		}

        if (self::getNoPhotoPprivateInOffset()) {
            $vis .= " AND private = 'N' ";
        }

        $sql = "SELECT COUNT(1) FROM `photo` WHERE `user_id` = " . to_sql($uid, 'Number')
               . " AND `photo_id` < " . to_sql($photo_id, 'Number') . " "
               . $vis . " AND visible!='P' ";

        return DB::result($sql, 0, DB_MAX_INDEX, true);
    }

    static function videoOffset($uid, $photo_id, $notChecking = true)
    {
		global $g, $g_user;

		$vis = '';
/*
		if ($notChecking) {
			$vis = $g['sql']['photo_vis'];
		} elseif ($uid != $g_user['user_id'] && !$notChecking) {
			$vis = " AND visible='Y' ";
		}
*/

        if ($uid != $g_user['user_id'] && !$notChecking) {
            $vis = " AND active=1 ";
        }


        $sql = "SELECT COUNT(1) FROM `vids_video` WHERE `user_id` = " . to_sql($uid, 'Number')
               . " AND `id` < " . to_sql($photo_id, 'Number') . " "
               . $vis . " AND active<>2 AND is_uploaded = 1";

        return DB::result($sql, 0, DB_MAX_INDEX, true);
    }

    static function paramsPhotoOffset($uid, $offset, $photoId = null, $numPhoto = null, $order = null)
    {
        global $g, $g_user;

        if ($order === null) {
            $order = '`photo_id` ASC';
        }
        $offsetInfo = array('offset' => '', 'next' => '', 'prev' => '', 'next_id' => '', 'prev_id' => '', 'prev_title'=>'', 'next_title'=>'');

		$vis = '';
		if ($uid != $g_user['user_id']) {
			$vis = $g['sql']['photo_vis'];
		}
        if (self::getNoPhotoPprivateInOffset()) {
            $vis .= " AND private = 'N' ";
        }

        if($numPhoto === null) {
            $sql = "SELECT COUNT(photo_id)
                      FROM `photo`
                     WHERE `user_id` = " . to_sql($uid, 'Number') . ' ' . $vis . " AND visible!='P' ";
            $numPhoto = DB::result($sql, 0, DB_MAX_INDEX);
        }

        if ($numPhoto > 0) {
            if (($offset > $numPhoto - 1) || ($offset < 0)) {
                if ($photoId === null) {
                    $photoId = User::getPhotoDefault($uid, 'r', true, false);
                }
                $offsetCurrent = User::photoOffset($uid, $photoId, false);
            } else {
                $offsetCurrent = $offset;
            }

            if ($numPhoto > 1) {
                if ($offsetCurrent == 0) {
                    $next = $offsetCurrent + 1;
                    $prev = $numPhoto - 1;
                } elseif ($offsetCurrent == $numPhoto - 1) {
                    $next = 0;
                    $prev = $numPhoto - 2;
                } else {
                    $next = $offsetCurrent + 1;
                    $prev = $offsetCurrent - 1;
                }
            } else {
                $next = 0;
                $prev = 0;
            }
            $offsetInfo['offset'] = $offsetCurrent;
            $offsetInfo['next'] = $next;
            $offsetInfo['prev'] = $prev;
            $offsetInfo['next_id'] = self::getIdPhotoToOffset($uid, $next, $order);
            $offsetInfo['prev_id'] = self::getIdPhotoToOffset($uid, $prev, $order);
            $offsetInfo['next_title'] = self::getTitlePhotoToOffset($uid, $next);
            $offsetInfo['prev_title'] = self::getTitlePhotoToOffset($uid, $prev);
        }

        return $offsetInfo;

    }

    static function paramsVideoOffset($uid, $offset, $photoId = 0)//URBAN
    {
        global $g, $g_user;

        $offsetInfo = array('offset' => '', 'next' => '', 'prev' => '', 'next_id' => '', 'prev_id' => '', 'next_title' => '', 'prev_title' => '');

		$vis = ' AND `private` = 0';
		if ($uid != $g_user['user_id']) {
			//$vis = $g['sql']['photo_vis'];
			$vis .= " AND `active` = 1";
		}
        $sql = "SELECT COUNT(id)
                  FROM `vids_video`
                 WHERE `user_id` = " . to_sql($uid, 'Number') . $vis
               . " AND `active` != 2
                   AND `is_uploaded` = 1";
        $numPhoto = DB::result($sql, 0, DB_MAX_INDEX, true);
        if ($numPhoto > 0) {
            if (($offset > $numPhoto - 1) || ($offset < 0)) {
                $offsetCurrent = User::videoOffset($uid, $photoId, false);
            } else {
                $offsetCurrent = $offset;
            }

            if ($numPhoto > 1) {
                if ($offsetCurrent == 0) {
                    $next = $offsetCurrent + 1;
                    $prev = $numPhoto - 1;
                } elseif ($offsetCurrent == $numPhoto - 1) {
                    $next = 0;
                    $prev = $numPhoto - 2;
                } else {
                    $next = $offsetCurrent + 1;
                    $prev = $offsetCurrent - 1;
                }
            } else {
                $next = 0;
                $prev = 0;
            }
            $offsetInfo['offset'] = $offsetCurrent;
            $offsetInfo['next'] = $next;
            $offsetInfo['prev'] = $prev;
            $offsetInfo['next_id'] = 'v_' . self::getIdVideoToOffset($uid, $next);
            $offsetInfo['prev_id'] = 'v_' . self::getIdVideoToOffset($uid, $prev);
            $offsetInfo['next_title'] = self::getTitleVideoToOffset($uid, $next);
            $offsetInfo['prev_title'] = self::getTitleVideoToOffset($uid, $prev);
        }

        return $offsetInfo;

    }

    static function nameShort($name)
    {
        $nameParts = explode(' ', $name);
        $nameShort = $nameParts[0];
        return $nameShort;
    }

    static function nameOneLetterShort($name)
    {
        if (!empty($name)) {
           $parts = explode(' ', $name);
           $numParts = count($parts);
           if ($numParts > 2) {
               $name = $parts[0] . ' ' . $parts[$numParts - 1];
           }
           $name = self::nameOneLetterFull($name);
        }
        return $name;

    }

    static function nameOneLetterFull($name)
    {
        $fullName = '';

        $name = preg_replace('/(\s)+/u', ' ', trim($name));

        if (!empty($name)) {
           $parts = explode(' ', $name);
           $numParts = count($parts);
           if ($numParts == 1) {
                $fullName = $name;
           } elseif (($numParts == 2)) {
                if (mb_strlen($parts[0], 'UTF-8') == 2) {
                    $fullName = $name;
                } else {
                    $fullName = self::nameOneLetter($parts[0]) . $parts[1];
                }
           } else {
                $count = count($parts);
                for ($i = 0; $i < $count - 1; $i++) {
                    $fullName .= self::nameOneLetter($parts[$i]);
                }
                $fullName .= $parts[$count - 1];
           }
        }
        return mb_ucwords($fullName);
    }

    static function nameOneLetter($name)
    {
        $count = pl_strlen($name);

        if ($count == 1) {
            $letter = $name . '. ';
        } elseif ($count == 2 && $name[1] == '. ') {
            $letter = $name;
        } else {
            $letter = pl_substr($name, 0, 1) . '. ';
        }

        return $letter;
    }

    static function getUserEditorXml($uid)
    {
        $sql = 'SELECT user_editor_xml FROM userinfo
            WHERE user_id = ' . to_sql($uid, 'Number');
        $xml = trim(DB::result($sql));
        return $xml;
    }

    static function updateUserEditorXml($uid, $xml)
    {
        $sql = 'UPDATE userinfo
            SET user_editor_xml = ' . to_sql($xml, 'Text') . '
            WHERE user_id = ' . to_sql($uid, 'Number');
        DB::execute($sql);
    }

    static function isPaid($uid, $dbIndex = DB_MAX_INDEX)
    {
        if($uid != 0) {
            $info = self::getInfoBasic($uid, false, $dbIndex);
            if ($info['type'] != 'none' && $info['gold_days'] > 0) {
                $isPaid = true;
            } else {
                $isPaid = self::isFreeAccess($info['gold_days'], $info['orientation']);
            }
        } else {
            $isPaid = false;
        }
        return $isPaid;
    }

    static function freeAccessApply($row)
    {
        $orientationInfo = self::getOrientationInfo($row['orientation']);
		if($orientationInfo) {
            $free = $orientationInfo['free'];
            if (self::isPaidFree($row['type'], $row['gold_days']) && $free != 'none') {
                $row['type'] = $free;
                $row['gold_days'] = 1;
            }
        }
        return $row;
    }

    static function nameAddPostfix($name)
    {
        global $l;

        if(isset($l['all']['name_postfix'])) {
            $name .= $l['all']['name_postfix'];
        }

        return $name;
    }

    static function parseWidgets(&$html)
    {
        static $vars = false;

        if(!guid()) {
            return false;
        }

        if(!Common::isOptionActive('widgets')) {
            return false;
        }

        if($vars === false) {
            if (Common::page() == Common::getHomePage()) {
                $is_home_widget = 'true';
            } else {
                $is_home_widget = 'false';
            }
            $vars['is_home_widget'] = $is_home_widget;
            $sql = 'SELECT COUNT(*) FROM widgets
                WHERE user_id = ' . to_sql(guid(), 'Number');
            $vars['widgets_count'] = DB::result($sql, 0, DB_MAX_INDEX);

            $widgets_calendar_long = 'false';
            $sql = 'SELECT settings FROM widgets
                WHERE user_id = ' . to_sql(guid(), 'Number') . '
                    AND widget = 5';
            $settings = DB::result($sql, 0, DB_MAX_INDEX);
            if ($settings > 0) {
                $time = $settings;
            } else {
                $time = time();
            }

            $date['m'] = date('m', $time);
            $date['y'] = date('Y', $time);
            $count_day = date('t', mktime(0, 0, 0, $date['m'], 1, $date['y']));
            $first_day = date('w', mktime(0, 0, 0, $date['m'], 1, $date['y']));

            if (($first_day + $count_day) > 35) {
                $widgets_calendar_long = 'true';
            }
            $vars['widgets_calendar_long'] = $widgets_calendar_long;
        }

        htmlSetVars($html, $vars);
        //$html->parse('header_widgets', true);
    }

    static function isOnline($uid, $userInfo = null, $isOnlyOnline = false, $dbIndex = DB_MAX_INDEX)
    {
        $key = "user_online_{$uid}_" . intval($isOnlyOnline);
        $isOnline = Cache::get($key);
        if($isOnline !== null) {
            return $isOnline;
        }
        if($userInfo === null) {
            $userInfo = self::getInfoBasic($uid, false, $dbIndex);
        }

        $isShowStatus = true;
        if (!$isOnlyOnline) {
            $isShowStatus = !self::isInvisibleModeOptionActive('set_hide_my_presence');
        }
        $lastVisitTime = time_mysql_dt2u($userInfo['last_visit']);
        $isOnline = ((time() - $lastVisitTime) / 60 < Common::getOption('online_time')) && $isShowStatus;
        Cache::add($key, $isOnline);

        return $isOnline;
    }

    static function forgotPasswordChange($user)
    {
        $newPass = substr(md5(microtime()), 0, 6);
        $md5 = Common::isOptionActive('md5');
        $sql = 'UPDATE user '
            . 'SET password = ' . to_sql(User::preparePasswordForDatabase($newPass)).', password_reminder=NULL '
            . 'WHERE user_id = ' . to_sql($user['user_id']);
        DB::execute($sql);

        $vars = array(
                'title' => Common::getOption('title', 'main'),
                'name' => $user['name'],
                'mail' => $user['mail'],
                'password' => $newPass,
        );
        Common::sendAutomail(Common::getOption('lang_loaded', 'main'), $user['mail'], 'forget', $vars);
    }


    static function forgotPassword($user)
    {
        global $p;
        $passReminderCode = md5(microtime() . rand(0,999999));
        //$md5 = Common::isOptionActive('md5');
        $sql = 'UPDATE user '
            . 'SET password_reminder = ' . to_sql($passReminderCode)
            . 'WHERE mail = ' . to_sql($user['mail']);
        DB::execute($sql);

        $vars = array(
                'title' => Common::getOption('title', 'main'),
                'name' => $user['name'],
                'mail' => $user['mail'],
                'code_link' => $p.'?login='.$passReminderCode,
                'url_site' => Common::urlSite(),
        );
        Common::sendAutomail(Common::getOption('lang_loaded', 'main'), $user['mail'], 'forget_link', $vars);
    }

    static function getListAvatar($type = 'gender')
    {
        global $g_user;

        if ($type == 'gender') {
            $value = array('M', 'F');
        }
        if ($g_user[$type] == $value[0])
            $avs = array(1 => 1, 2 => 2, 3 => 6, 4 =>7, 5 =>8, 6 =>14, 7 =>15);
        elseif ($g_user[$type] == $value[1])
            $avs = array(1 => 3, 2 => 4, 3 => 5, 4 => 9, 5 => 10, 6 => 11, 7 => 12, 8 => 13);
        else
            $avs = array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8,
                         9 => 9, 10 => 10, 11 => 11, 12 => 12, 13 => 13, 14 => 14, 15 => 15);
        return $avs;
    }

    static function setAvatar($numAvatar = 0)
    {
        global $g_user;
        $setAvatar = 0;

        if (($numAvatar != 0) && ($numAvatar != $g_user['avatar'])) {
            $setAvatar = to_sql($numAvatar, "Number");
        } else {
            if ($g_user['avatar'] == 0) {
                $avs = self::getListAvatar();
                $setAvatar = $avs[rand(1, count($avs))];
            }
        }

        if ($setAvatar > 0) {
            $g_user['avatar'] = $setAvatar;
            DB::execute("UPDATE user SET avatar = " . $setAvatar . "
                                  WHERE user_id = " . $g_user['user_id']);
        }
   }

   static function getUserOrientationInfo($orientation)
   {
        $sql = 'SELECT `search`, `gender`, `title`
                  FROM `const_orientation`
                 WHERE id = ' . to_sql($orientation, "Number");
        $search = DB::result($sql, 0);
        $gender = DB::result($sql, 1);
        $title = DB::result($sql, 2);
        return array('gender' => $gender, 'search' => $search, 'title' => $title);
   }

   static function setOrientation($user_id, $orientation = null)
   {
        global $g_user;

        $data = array();
        if (UserFields::isActive('orientation')) {
            if ($orientation === null) {
                $orientation = get_param('orientation', $g_user['orientation']);
            }
            if ($orientation != $g_user['orientation']) {
                $data = self::getOrientationInfo($orientation);
                $sql = "UPDATE `user`
                           SET `orientation` = " . to_sql($orientation, "Number") . ",
                               `gender` = " . to_sql($data['gender'], "Text") . "
                         WHERE `user_id` = " . to_sql($user_id, "Number");
                        //`p_orientation` = " . to_sql($data['search'], "Number") . "
                DB::execute($sql);

                City::setParamsAvatarChangingOrientation($data['gender']);

                if (Common::getOption('paid_access_mode') != 'free_site'
                    && Common::isActiveFeatureSuperPowers('invisible_mode')) {
                        if ($data && isset($data['free']) && $data['free'] == 'none') {
                            $where = '(`set_hide_my_presence` = 1 OR `set_do_not_show_me_visitors` = 1)' .
                                     ' AND `gold_days` = 0 AND `user_id` = ' . to_sql($user_id);
                            $vars = array('set_hide_my_presence' => 2, 'set_do_not_show_me_visitors' => 2);
                            DB::update('user', $vars, $where);
                        }
                }
                $data = array('gender' => $data['gender'], 'search' => $data['search'], 'title' => $data['title']);
                /*if (Common::isMobile() && Common::getOptionSetTmpl() == 'urban') {
                    User::update(array('p_orientation' => get_checks_param('p_orientation')));
                }*/
            }
        }
        return $data;
   }

   static function checkNameCompatibilityWithSystem($name)
   {
        $name = Router::prepareNameSeo($name);

        $urlDir = Common::getOption('dir_main', 'path') . $name;
        if (file_exists($urlDir)) {
            return false;
        }
        $seo = array(
            'blogs_add', 'blogs', 'calendar', 'task_create', 'task_edit',
            'favorite_list', 'group_add', 'page_add',
            'search_results', 'encounters', 'rate_people', 'wall',
            'city', 'general_chat', 'moderator', 'upgrade',
            'user_block_list', 'my_friends', 'mutual_attractions', 'users_viewed_me',
            'mail_whos_interest', 'users_rated_me', 'increase_popularity', 'profile_settings',
            'join', 'login', 'forget_password', 'index',
            'profile_view', 'page', 'messages', 'contact',
            'about', 'games', 'join2', 'private_photo_access',
            'terms', 'privacy_policy', 'forgot_password', 'email_not_confirmed',
            'street_chat', '3d_labyrinth', '3d_tic_tac_toe', 'who_likes_you',
            'whom_you_like', 'mutual_likes', 'contact', 'photos', 'vids'
        );

        $routerPages = array_keys(Router::getPagesCompatibleWithSystem(true));
        $routerPages = array_diff($routerPages, $seo);
        $seo = array_merge($seo, $routerPages);

        if (array_search($name, $seo) !== false) {
            return false;
        }
        return true;
   }
    static function checkExistPhone($phone) {
        global $g_user;
        if (isset($g_user['phone']) && ($phone == $g_user['phone'])) {
           return '';
        }

        $exist = DB::row("SELECT COUNT(*) AS have FROM user WHERE phone = ".to_sql($phone, 'Text'));
        if($exist['have'])
            return l('exists_phone_number') . '<br>';
    }
   static function validateName($name)
   {
       global $g_user;

       if (isset($g_user['name']) && ($name == $g_user['name'])) {
           return '';
       }

       $maxLength = Common::getOption('username_length');
       $minLength = Common::getOption('username_length_min');
       $nameLength = mb_strlen($name, 'UTF-8');

       if ($nameLength < $minLength || $nameLength > $maxLength) {
            self::$error['name'] = true;
            return sprintf(l("max_min_length_username"), $minLength, $maxLength);
       }
       if (preg_match('/[%#&\'"\/\\\\<]/', $name) || (trim($name, '.') === '')) {
           self::$error['name'] = true;
           return l('invalid_username') . '<br>';
       }
       if (DB::result("SELECT `user_id` FROM `user` WHERE `name` = " . to_sql($name, "Text")) > 0) {
           self::$error['name'] = true;
           return l('exists_username') . '<br>';
       }
       if (!self::checkNameCompatibilityWithSystem($name)) {
           self::$error['name'] = true;
           return sprintf(l('the_name_cannot_be_used'), $name);
       }

       /* DO NOT CHECK name_seo - number can be added at the end to make it unique
       $nameSeo = Router::getNameSeo($name, 0, '', false);
       if (!$nameSeo) {
           self::$error['name'] = true;
           return l('exists_username') . '<br>';
       }*/

       return '';
   }

   static function validateNameNormal($name)
   {
       global $g_user;

       if (isset($g_user['name']) && ($name == $g_user['name'])) {
           return '';
       }

       $maxLength = Common::getOption('username_length');
       $minLength = Common::getOption('username_length_min');
       $nameLength = mb_strlen($name, 'UTF-8');

       if ($nameLength < $minLength || $nameLength > $maxLength) {
            self::$error['name'] = true;
            return sprintf(l("max_min_length_username"), $minLength, $maxLength);
       }
       /* if (preg_match('/[%#&\'"\/\\\\<]/', $name) || (trim($name, '.') === '')) {
           self::$error['name'] = true;
           return l('invalid_username') . '<br>';
       }
       if (DB::result("SELECT `user_id` FROM `user` WHERE `name` = " . to_sql($name, "Text")) > 0) {
           self::$error['name'] = true;
           return l('exists_username') . '<br>';
       }
       if (!self::checkNameCompatibilityWithSystem($name)) {
           self::$error['name'] = true;
           return sprintf(l('the_name_cannot_be_used'), $name);
       } */

       /* DO NOT CHECK name_seo - number can be added at the end to make it unique
       $nameSeo = Router::getNameSeo($name, 0, '', false);
       if (!$nameSeo) {
           self::$error['name'] = true;
           return l('exists_username') . '<br>';
       }*/

       return '';
   }

   static function validateEmail($email = NULL)
   {

       global $g_user;

       if ($email == NULL) {
            $email = trim(get_param('email', ''));
       }

       if (isset($g_user['mail']) && ($email == $g_user['mail'])) {
           return '';
       }

       /* if (!Common::validateEmail($email)) {
           self::$error['email'] = true;
            return l('incorrect_email') . '<br>';
       } */

       /*$maxLength = Common::getOption('mail_length_max');
       $pattern = "/^[a-zA-Z\-_\.0-9]{1," . $maxLength . "}@[a-zA-Z\-_\.0-9]{1," . $maxLength . "}\.[a-zA-Z\-_\.0-9]{1," . $maxLength . "}$/";

       if ($email == ''
            || mb_strlen($email, 'UTF-8') > $maxLength
            || !preg_match($pattern, $email)) {
            self::$error['email'] = true;
            return l('incorrect_email') . '<br>';
       }*/

       $sql = (isset($g_user['user_id'])) ? ' AND `user_id` != ' . $g_user['user_id'] : '';
       if (DB::result("SELECT `user_id` FROM `user` WHERE `mail` = " . to_sql($email, "Text") . $sql) > 0) {
           self::$error['email'] = true;
           return l('exists_email') . '<br>';
       }
       return '';
   }

   static function validateBirthday($month = NULL, $day = NULL, $year = NULL)
   {

       if (($month == NULL) && ($day == NULL) && ($year == NULL)) {
            $month  = (int)get_param('month', 1);
            $day    = (int)get_param('day', 1);
            $year   = (int)get_param('year', 1980);
       }

       if (!checkdate($month, $day, $year)) {
            self::$error['birthday'] = true;
            return l('incorrect_date') . '<br>';
       }

       $now = new DateTime();
       $birthday = new DateTime("$year-$month-$day");
       $age = $now->diff($birthday)->y;
       if($age < Common::getOption('users_age') || $age > Common::getOption('users_age_max')) {
            self::$error['birthday'] = true;
            return l('incorrect_date') . '<br>';
       }

       return '';
   }

   static function validateLocation($country = NULL, $state = NULL, $city = NULL, $ajax = false)
   {
       $msg = '';
       $isErrorsSeparat = get_param_int('errors_separat');
       if (($country == NULL) && ($state == NULL) && ($city == NULL)) {
            $country = intval(get_param('country'));
            $state   = intval(get_param('state'));
            $city    = intval(get_param('city'));
       }

       $country       = DB::result("SELECT `country_id` FROM `geo_country` WHERE `country_id` = " . to_sql($country, 'Number'));
       $countryStateC = DB::rows("SELECT `country_id`, `state_id` FROM `geo_state` WHERE `state_id` = " . to_sql($state, 'Number'));
       $stateCityC    = DB::rows("SELECT `city_id`, `state_id` FROM `geo_city` WHERE `city_id` = " . to_sql($city, 'Number'));

       if ($country == 0){
            if ($isErrorsSeparat) {
                $msg .= '<span id="country">' . l('country_is_incorrect') . '</span>';
            } else {
                $msg .= l('country_is_incorrect') . '<br> ';
            }
            self::$error['country'] = true;
       }
       if (empty($countryStateC)
            || ($country != $countryStateC[0]['country_id']
            ||  $countryStateC[0]['state_id'] == 0)){
            if ($isErrorsSeparat) {
                $msg .= '<span id="state">' . l('state_is_incorrect') . '</span>';
            } else {
                $msg .= l('state_is_incorrect') . '<br> ';
            }
            self::$error['state'] = true;
       }
       if ((empty($stateCityC)
            || empty($countryStateC))
            || ($stateCityC[0]['city_id'] == 0
            ||  $countryStateC[0]['state_id'] != $stateCityC[0]['state_id'])){
            if ($isErrorsSeparat) {
                $msg .= '<span id="city">' . l('city_is_incorrect') . '</span>';
            } else {
                $msg .= l('city_is_incorrect') . '<br>';
            }
            self::$error['city'] = true;
       }
       return $msg;
   }

   static function validateCountry($country = NULL)
   {
       if ($country == NULL) {
            $country = intval(get_param('country'));
       }
       if (DB::result('SELECT `country_id` FROM `geo_country` WHERE `country_id` = ' . to_sql($country, 'Number')) == 0) {
            self::$error['country'] = true;
            return l('country_is_incorrect') . '<br>';
       }

       return '';

   }

   static function validatePassword($password = NULL, $minLength = NULL, $maxLength = NULL)
   {
       if ($password == NULL) {
            $password = trim(get_param('password', ''));
       }

       $maxLength = ($maxLength === NULL) ? Common::getOption('password_length_max') : $maxLength;
       $minLength = ($minLength === NULL) ? Common::getOption('password_length_min') : $minLength;
       $passLength = mb_strlen($password, 'UTF-8');

       if ($passLength < $minLength
            || $passLength > $maxLength) {
            return sprintf(l('max_min_length_password'), $minLength, $maxLength) . '<br>';
       }
       if (preg_match('/[\']/', $password)) {
            self::$error['password'] = true;
            return l('invalid_password_contain') . '<br>';
       }
       return '';
   }

   static function validatePhoto($name)
   {

        return  validatephoto($name);

   }

   static function validate($method)
   {
       $msg = '';
       if (is_string($method)){
           $method = explode(',', str_replace(" ", "", $method));
       }
       if (Common::isValidArray($method)) {
           foreach ($method as $value) {
               $static = 'validate' . $value;
               if (method_exists('User', $static)){
                    $msg .= User::$static();
               } else {
                   #РћР±СЂР°Р±РѕС‚РєР° РѕС€РёР±РєРё
               }
           }
       }
       return $msg;
   }

   static function updateProfileStatus($status, $e_user_id=0)
   {
        global $g_user;

        $user_id = to_sql($g_user['user_id'], 'Number');
        if($e_user_id !=0) // added by sohel
            $user_id = to_sql($e_user_id, 'Number');


        $sqlstat = "SELECT `status` FROM `profile_status` WHERE user_id = " . $user_id;
        DB::query($sqlstat);
        $sql2assoc = DB::fetch_row();
        if($sql2assoc['status'] == $status) {
            return true;
        }

        $sql = "DELETE FROM `profile_status` WHERE user_id = " . $user_id;
        DB::execute($sql);

        if (trim($status)!= '') {
            $sql = "INSERT INTO `profile_status` VALUES(" . $user_id . "," . to_sql($status,'Text') . ",NOW())";
            DB::execute($sql);
            Wall::setUid(guid());
            Wall::add('status', 0, false, trim($status));
        }
        return true;
   }

   static function flashGames($games, $user)
   {
        global $g_user;
        global $swf;

        switch ($games) {
            case 'lovetree':
            case 'test':
                # 1 - kolvoPopitok
                # 2 - kolvoAll
                # 3 - my_name
                # 4 - emeny_name
                $src = sprintf($swf[$games]['params']['movie'], 10, 6, $g_user['name'], $user['name']);
                unset($swf[$games]['params']['movie']);
                break;
            default:
                # 1 - user_id
                # 2 - my_name
                # 3 - emeny_name
                $src = sprintf($swf[$games]['params']['movie'], $user['user_id'], $g_user['name'], $user['name']);
                unset($swf[$games]['params']['movie']);
                break;
        }

        $params['main']    = $swf['games']['params'];
        $params['attributes'] = $swf['games']['attributes'];
        if (isset($swf[$games]['attributes']['height'])) {
            $params['attributes']['height'] = $swf[$games]['attributes']['height'];
        }
        if (isset($swf[$games]['attributes']['width'])) {
            $params['attributes']['width'] = $swf[$games]['attributes']['width'];
        }
        $params['attributes']['id'] = $swf[$games]['attributes']['id'];
        $flashVars = $swf['games']['flashvars'];
        //print_r($params);
        return Common::swf($src, $params, $flashVars);
   }

   static function flashChat()
   {
        global $g_user;
        global $swf;

        $src = sprintf($swf['flashchat']['params']['movie'], $g_user['name']);
        unset($swf['flashchat']['params']['movie']);

        $params['main']    = $swf['flashchat']['params'];
        $params['attributes'] = $swf['flashchat']['attributes'];

        $flashVars = $swf['flashchat']['flashvars'];

        return Common::swf($src, $params, $flashVars);
   }

   static function flashBanner($file, $width, $height)
   {
        global $g;
        global $swf;

        $swf['banner']['attributes']['width'] = $width;
        $swf['banner']['attributes']['height'] = $height;

        $src = $g['path']['url_files'] . 'banner/' . $file;
        $swf['banner']['params']['movie'] = $src;

        $params['main']    = $swf['banner']['params'];
        $params['attributes'] = $swf['banner']['attributes'];

        return Common::swf($src, $params);
   }

   static function flashPostcard($uid, $type = 'send', $text = '', $flVars = null, $langPage = null)
   {
        global $swf;

        $swf['postcard']['flashvars']['receiver_id'] = get_param('user_id');
        $swf['postcard']['flashvars']['sender_id'] = guid();

        $src = $swf['postcard']['params']['movie'];
        unset($swf['postcard']['params']['movie']);

        $params['main']    = $swf['postcard']['params'];
        $params['attributes'] = $swf['postcard']['attributes'];
        if ($type != 'send') {
            $params['attributes']['width'] = $swf['postcard_inbox']['attributes']['width'];
        }
        if ($type == 'send') {
            unset($swf['postcard']['flashvars']['params']);
            $flashVars = $swf['postcard']['flashvars'];
            $flashVars['uid'] = sprintf($flashVars['uid'], $uid);
        } else {
            if ($flVars === null) {
                $flashVars['params'] = sprintf($swf['postcard']['flashvars']['params'], $text);
            } else {
                $flashVars['params'] = $flVars;
            }
        }
        if ($langPage === null) {
            $page = explode('?', Common::urlPage());
            $page = $page[0];
        } else {
            $page = $langPage;
        }
        $flashVars['lang'] = str_replace(array('{url_page}', '{lang_loaded}'), array($page, Common::getOption('lang_loaded', 'main')), $swf['postcard']['flashvars']['lang']);

        return Common::swf($src, $params, $flashVars);
   }

   static function flashProfile($userId, $type = 'editor')
   {
        global $swf;

        $src = sprintf($swf['profile']['params']['movie'], 'geditor.swf');
        unset($swf['profile']['params']['movie']);

        $params['main']    = $swf['profile']['params'];
        $params['attributes'] = $swf['profile']['attributes'];

        $flashVars = $swf['profile']['flashvars'];
        $flashVars['system_security'] = sprintf($swf['profile']['flashvars']['system_security'], Common::getUrlAbsolute());
        $flashVars['type'] = $type;

        $method = ($type == 'editor' || $type == 'viewer') ? 'preparedFlashEditor' : 'preparedFlash'. $type;
        $flashVars = array_replace($flashVars,  self::$method($userId));

        return Common::swf($src, $params, $flashVars);
   }

   static function preparedFlashEditor($userId)
   {
        global $swf;

        $rand = (rand(0, 10000000));
        $flashVars['id_owner']   = sprintf($swf['profile']['flashvars']['id_owner'], $userId);
        $flashVars['galleryxml'] = sprintf($swf['profile']['flashvars']['galleryxml'], $userId);
        $flashVars['xml_music']  = sprintf($swf['profile']['flashvars']['xml_music'], $userId);
        $flashVars['xml_video']  = sprintf($swf['profile']['flashvars']['xml_video'], $userId);
        $flashVars['xml_file']   = urlencode(sprintf($swf['profile']['flashvars']['xml_file'], $userId, $rand));
        $flashVars['prof'] = sprintf($swf['profile']['flashvars']['prof'], Common::getUrlAbsolute(), $userId);
        $flashVars['r'] = sprintf($swf['profile']['flashvars']['r'], $rand);

        return $flashVars;
   }

   static function  paidLevel($type = NULL, $goldDays = NULL, $freeAccess = NULL)
   {
        $result = array();
        if ($type == NULL && $goldDays == NULL && $freeAccess == NULL) {
            global $g_user;
            $g_user = self::freeAccessApply($g_user);
        } else {
            if (self::isPaidFree($type, $goldDays) && $freeAccess != 'none') {
                $result['gold_days'] = 1;
                $result['type'] = $freeAccess;
            } else {
                $result['gold_days'] =  $goldDays;
                $result['type'] = $type;
            }
        }
        return $result;
   }

   static function isPaidFree($type = NULL, $goldDays = NULL)
   {
        if ($type == NULL && $goldDays == NULL) {
            global $g_user;
            $type = $g_user['type'];
            $goldDays = $g_user['gold_days'];
        }

        if ($type == 'none' || $goldDays == 0) {
            return true;
        } else {
            return false;
        }
   }

   static function parseImLink(&$html, $uid, $type, $goldDays, $block = 'wall_item_im')
   {
        if(guid() && guid() != $uid && Common::isOptionActive('im') && !User::isBlocked('im', $uid, guid()) && payment_check_return('im') && payment_check_return('im', $type, $goldDays) ) {
           $html->parse($block, false);
           return true;
        }
      return false;
   }

   static function isSimpleProfile($userId)
   {
        if ((self::getInfoBasic($userId, 'smart_profile') == 1 && Common::isOptionActive('allow_users_profile_mode'))
             || (Common::getOption('mode_profile') == 'smart' && !Common::isOptionActive('allow_users_profile_mode'))) {
            return false;
        } else {
            return true;
        }
   }

   static function saveImSound()
   {
        global $g_user;

        $responseData = false;
        if ($g_user['user_id']) {
            $sound = ($g_user['sound'] == 2) ? 1 : 2;
            $sql = 'UPDATE `user` SET `sound` = ' . to_sql($sound, 'Number')
                 . ' WHERE `user_id` = ' . to_sql(guid(), 'Number');
            DB::execute($sql);
            $responseData = $sound;
        }
		return $responseData;
   }

   static function setStatusUsersIm()
   {
        global $g_user;

        $responseData = false;

		if ($g_user['user_id']) {
			$visible = ($g_user['is_online_users_im'] == 2) ? 1 : 2;

			$sql = 'UPDATE `user` SET `is_online_users_im` = ' . to_sql($visible, 'Number')
				. ' WHERE `user_id` = ' . to_sql($g_user['user_id'], 'Number');
			DB::execute($sql);
            $responseData = true;
		}

        return $responseData;
   }

   static function upgradeCouple($uid, $goldDays, $type)
   {
        if (Common::isOptionActive('upgrade_couple')){
            $couple = self::getInfoBasic($uid);
            if ($couple['couple'] == 'Y' && $couple['couple_id'] != 0){
                $timeStamp=time()+3600;  //+60 minutes
                $date=date('Y-m-d',$timeStamp);
                $hour=intval(date('H',$timeStamp));
                $sql = 'UPDATE `user`
                           SET `gold_days` = ' . to_sql($goldDays, 'Number')
                          . ', `type` = ' . to_sql($type).",
                         payment_day=".to_sql($date).",
                         payment_hour=".to_sql($hour, 'Number')
                     . ' WHERE user_id = ' . to_sql($couple['couple_id'], 'Number');

                DB::execute($sql);
            }
        }
   }

   static function updateActivity($uid, $count = NULL)
   {
        if ($uid && $uid != guid()){
            $count = ($count == NULL) ? 1 : $count;
            $sql = 'UPDATE `friends_requests`
                       SET `activity` = `activity` + ' . to_sql($count, 'Number')
                 . ' WHERE (`user_id` = ' . to_sql(guid(), 'Number')
                         . ' AND
                            `friend_id` = ' . to_sql($uid, 'Number') . ')'
                        . ' OR
                            (`user_id` = ' . to_sql($uid, 'Number')
                         . ' AND
                            `friend_id` = ' . to_sql(guid(), 'Number') . ')';
            DB::execute($sql);
        }
   }

   static function updateApproveActivity($uid, $fid)
   {
       $sql = '(SELECT COUNT(*) as count
                 FROM `wall_comments` as WC,
                      `wall` as W
                WHERE (WC.user_id = ' . to_sql($uid, 'Number') . ' AND WC.wall_item_id = W.id AND W.user_id = ' . to_sql($fid, 'Number') . ')
                       OR
                      (WC.user_id = ' . to_sql($fid, 'Number') . ' AND WC.wall_item_id = W.id AND W.user_id = ' . to_sql($uid, 'Number') . '))
                UNION ALL
              (SELECT COUNT(*) as count
                 FROM `wall`
                WHERE ((`user_id` = ' . to_sql($uid, 'Number') . ' AND `comment_user_id` = ' . to_sql($fid, 'Number') . ')
                         OR
                      (`user_id` = ' . to_sql($fid, 'Number') . ' AND `comment_user_id` = ' . to_sql($uid, 'Number') . '))
                       AND `section` = "comment")
                UNION ALL
         	  (SELECT COUNT(*) as count
                 FROM `mail_msg`
                WHERE (`user_id` = ' . to_sql($uid, 'Number') . ' AND `user_from` = ' . to_sql($uid, 'Number') . ' AND `user_to` = ' . to_sql($fid, 'Number') . ')
                         OR
                      (`user_id` = ' . to_sql($fid, 'Number') . ' AND `user_from` = ' . to_sql($fid, 'Number') . ' AND `user_to` = ' . to_sql($uid, 'Number') . '))';

       $rows = DB::all($sql);
       $count = 0;
       foreach ($rows as $row) {
           $count += $row['count'];
       }
       User::updateActivity($uid, $count);
    }

    static function getColorScheme()
    {
        $setColorScheme = get_param('color_scheme');
        $templateOptionName = Common::getOption('name', 'template_options');

        $upper = Common::getOption("upper_header_color_{$templateOptionName}");
        $lower = Common::getOption("lower_header_color_{$templateOptionName}");

        $isAllowUserColorSchema = Common::isOptionActive('allow_users_color_scheme') && $templateOptionName != 'impact';
        if ($isAllowUserColorSchema) {
            $colorTemplate = Common::getOption('color_scheme', 'template_options');
            if ($setColorScheme != '') {
                $userColorScheme = $setColorScheme;
            }elseif (guid()) {
                $userColorScheme = guser('color_scheme');
            } else {
                $userColorScheme = get_cookie('user_color_scheme');
            }
            if ($userColorScheme && isset($colorTemplate[$userColorScheme])) {
                $upper = $colorTemplate[$userColorScheme]['upper'];
                $lower = $colorTemplate[$userColorScheme]['lower'];
            }
        }
        return array('upper' => $upper, 'lower' => $lower);
    }
    static function howOnline($gender = '')
    {
        global $g_user;

        //$filter = ($gender == '') ? '' : ' AND gender = ' . to_sql($gender, 'Text');

        $defaultOnlineView = User::defaultOnlineView($gender);

        $sql = 'SELECT COUNT(*)
                  FROM `user`
                 WHERE `hide_time` = 0
                   AND `user_id` != ' . to_sql(guid()) . '
                   AND `last_visit` > ' . to_sql(date('Y-m-d H:i:00', time() - Common::getOption('online_time') * 60), 'Text')
             . $defaultOnlineView;

        return DB::result($sql);
    }

    static function isListOrientationsSearch()
    {
        $gender = get_param('gender', true);
        $orientation = get_param('p_orientation');

        $isSearch = true;
        if (!empty($orientation)) {
            if ($gender === true) {
                $isSearch = false;
            }
        }

        return $isSearch;
    }

    static function isNarrowBox($type)
    {
        if (guid()) {
            $allState = guser('state_narrow_box');
            if ($allState != NULL) {
                $prepareState = unserialize(stripcslashes($allState));
                $state = isset($prepareState[$type]) ? $prepareState[$type] : 1;
            } else {
                $state = 1;
            }
        } else {
            $state = get_cookie('state_narrow_box_' . $type);
            $state = ($state == '') ? 1 : $state;
        }
        return $state;
    }

    static function setVisibleNarrowBox(&$html, $type)
    {
        $state = User::isNarrowBox($type);
        if  ($state) {
           $html->setvar('display', 'table-cell');
           $html->setvar('hide_narrow_box', 'block');
           $html->setvar('show_narrow_box', 'none');
        } else {
           $html->setvar('display', 'none');
           $html->setvar('hide_narrow_box', 'none');
           $html->setvar('show_narrow_box', 'block');
        }
    }

    static function isBanMails($textHash, $id = null)
    {
        global $g_user;

        if ($g_user['use_as_online']) {
            return false;
        }

        $isBan = false;
        if ($id === null) {
            $id = guid();
        }

        $numberAutoBan = Common::getOption('auto_ban_messages');
        if ($numberAutoBan) {
            $where = '`user_id` != ' . to_sql($id, 'Number') .
                      ' AND `user_from` = ' . to_sql($id, 'Number') .
                      ' AND `type` != "postcard"
                        AND `date_sent` > ' . to_sql($g_user['ban_time_release'] , 'Number') .
                      ' AND `text_hash` = ' . to_sql($textHash);
                // AND `folder` = 1
            $count = DB::count('mail_msg', $where, '', $numberAutoBan);
            if ($count >= $numberAutoBan) {
                self::setBan($id);
                $isBan = true;
            }
        }
        return $isBan;

    }

    static function isBanMailsIp($id = null, $ip = null)
    {
        global $g_user;

        if ($g_user['use_as_online']) {
            return false;
        }

        $isBan = false;
        if ($id === null) {
            $id = guid();
        }
        if ($ip === null) {
            $ip = $g_user['last_ip'];
        }

        $where = '`ban_mails` = 1
                   AND `last_ip` = ' . to_sql($ip);
        if (DB::count('user', $where, '', 1) >= 1) {
            self::setBan($id, $ip);
            $isBan = true;
        }
        return $isBan;
    }

    static function setBan($id = null, $ip = null)
    {
        global $g_user;

        $isBan = false;
        if ($id === null) {
            $id = guid();
        }
        if ($ip === null) {
            $ip = $g_user['last_ip'];
        }

        $data = array('ban_mails' => 1,
                      'ban_time' => date('Y-m-d H:i:s'));
        $where = '(`user_id` = ' . to_sql($id, 'Number')
               . ' OR (`last_ip` = ' . to_sql($ip) . ' AND `ban_mails` != 1))  '
               . ' AND `use_as_online` = 0';
        DB::update('user', $data, $where);
    }

    static function flashProfileFiles($xml)
    {
        $files = array();
        $pattern = '# type="image" img="_server\/editor\/images\/(.*)\/editor\/(.*)\.jpg" x="#';
        $matches = null;
        preg_match_all($pattern, $xml, $matches);
        if(isset($matches[2])) {
            $files = array_unique($matches[2]);
        }

        return $files;
    }

    static function flashProfileFilesDelete($xmlPrev, $xmlNext = '')
    {
        global $g;

        $xmlPrevFiles = User::flashProfileFiles($xmlPrev);
        if($xmlNext) {
            $xmlNextFiles = User::flashProfileFiles($xmlNext);
            $diff = array_diff($xmlPrevFiles, $xmlNextFiles);
        } else {
            $diff = $xmlPrevFiles;
        }

        if(Common::isValidArray($diff)) {
            foreach($diff as $fileName) {
                $filePath = $g['path']['dir_files'] . 'editor/' . $fileName;
                $files = array($filePath . '_src.jpg', $filePath . '.jpg');
                Common::saveFileSize($files, false);
                foreach($files as $file) {
                    @unlink($file);
                }
            }
        }
    }

    static function getAge($y, $m, $d)
    {
        $y = intval($y);
        $m = intval($m);
        $d = intval($d);
        if ($m > date('m') || $m == date('m') && $d > date('d'))
            return (date('Y') - $y - 1);
        else
            return (date('Y') - $y);
    }

	static function getInterests($uid, $limit = '', $order = 'DESC', $wallId = null)
    {
		if ($limit != '') {
			$limit = ' LIMIT ' . to_sql($limit, 'Number');
		}
        $where = '';
        if ($wallId !== null) {
            $where = ' AND UI.wall_id = ' . to_sql($wallId, 'Number');
        }
		$sql = 'SELECT I.*
                  FROM `user_interests` AS UI,
                       `interests` AS I
                 WHERE UI.user_id = ' . to_sql($uid, 'Number') .
                 $where .
                 ' AND I.id = UI.interest
              ORDER BY UI.id ' . $order . $limit;

        return DB::rows($sql);
    }

	static function getInterestsArray($uid = null)
    {
        if ($uid === null) {
            $uid = guid();
        }
        $interestsAll = array();
        $guidInterests = User::getInterests($uid);
        foreach ($guidInterests as $item) {
            $interestsAll[$item['id']] = $item;
        }
        return $interestsAll;
    }

    static function setAvailabilityPublicPhoto($uid)
    {
        global $g;

        $sql = "SELECT count(photo_id)
                  FROM `photo`
                 WHERE `user_id` = " . to_sql($uid, 'Number') .
                 " AND `visible` != 'P'
                   AND `private` = 'N' " . $g['sql']['photo_vis'];
        $countPhotoPublic = DB::result($sql);
        DB::execute('UPDATE `user` SET `is_photo_public` = ' . to_sql($countPhotoPublic ? 'Y' : 'N') . ' WHERE `user_id` = ' . to_sql($uid, 'Number'));
    }

    static function getRequestUserId($paramUid = 'uid', $default = 0)
    {
        $uidParam = get_param($paramUid);
        $uid = intval($uidParam);
        if ($uid && $uidParam === strval($uid)){
            return $uid;
        }

        $name = get_param('name');
        if ($name) {
            $uid = DB::field('user', 'user_id', '`name` = ' . to_sql($name));
            $uid = (isset($uid[0])) ? $uid[0] : 0;
        }

        $name = get_param('name_seo');
        if ($name) {
            $uid = self::getUidFromNameSeo($name);
        }

        return $uid ? $uid : $default;
    }

    static function getTitleFromSetOfValues($userValue, $table = 'var_sexuality', $key = 'sexuality', $prf = '', $mask = false)
    {
        $response = '';
        $rows = DB::all('SELECT * FROM `' . to_sql($table, 'Plain') . '`');
        if($rows) {
            $values = array();
            foreach ($rows as $row) {
                if ($mask) {
                    if ($userValue & (1 << ($row['id'] - 1))) {
                        $values[] = UserFields::translation($key, $row['title'], $prf);
                    }
                } elseif (is_array($userValue) && in_array($row['id'], $userValue)){
                    $values[] = UserFields::translation($key, $row['title'], $prf);
                }
            }

            $response = l('profile_' . $key . '_empty');

            if ($values) {
                $valuesCount = count($values);
                if($valuesCount == 1) {
                    $response = $values[0];
                } elseif($valuesCount > 1) {
                    $valuesLast = array_pop($values);
                    $response = implode(l('profile_' . $key . '_delimiter'), $values) . l('profile_' . $key . '_last_delimiter') . $valuesLast;
                }
                if ($response && l('profile_' . $key . '_allow_lowercase') == 'Y') {
                    $response = mb_strtolower($response, 'UTF-8');
                }
            }
            if ($response && l('profile_' . $key . '_allow_ucfirst') == 'Y') {
                $response = mb_ucfirst($response, 'UTF-8');
            }
        }
        return $response;
    }

    static function getLookingFor($uid, $data = null, $lKey = '')
    {
        global $g_user;

        $tmpl = Common::getOption('name', 'template_options');
        $guid = true; // Fix to show short profile info for site visitors
        if ($data == null) {
            $data = User::getInfoBasic($uid);
        }

        $format = $lKey;
        if ($lKey != '') {
            $lKey = '_' . $lKey;
        }

        $vars = array('here_to' => '',
                      //'looking' => l('looking_gender_' . $data['default_online_view']),
                      'looking' => '',
                      'age' => '');

        $lFieldsDelimiter = l('profile_short_info_fields_delimiter');

        $isAmHereTo = UserFields::isActive('i_am_here_to');
        if ($guid && $isAmHereTo) {
            $vars['here_to'] = self::prepareIAmHereToValue($data['i_am_here_to'], $format);
        }
        if ($guid && UserFields::isActive('orientation')) {
            if(self::$orientations) {
                $orientationValues = array();
                $prf = null;
                if ($format == 'search') {
                    $prf = 'filter';
                } elseif ($tmpl == 'urban_mobile') {
                    $prf = $format;
                }
                $vars['looking'] = self::getTitleOrientationLookingFor($data, $prf);
                if($vars['looking'] && l('profile_orientations_allow_lowercase') == 'Y') {
                    $vars['looking'] = mb_strtolower($vars['looking'], 'UTF-8');
                }
                /*foreach(self::$orientations as $orientation) {
                    if ($data['p_orientation'] & (1 << ($orientation['id'] - 1))) {
                        $orientationValues[] = UserFields::translation('orientation', $orientation['title'], $prf);
                    }
                }

                $vars['looking'] = l('somebody');
                if($orientationValues) {
                    $orientationValuesCount = count($orientationValues);
                    if($orientationValuesCount == 1) {
                        $vars['looking'] = $orientationValues[0];
                    } elseif($orientationValuesCount > 1) {
                        $orientationLast = array_pop($orientationValues);
                        $vars['looking'] = implode(l('profile_orientations_delimiter'), $orientationValues) . l('profile_orientations_last_delimiter') . $orientationLast;
                    }

                    if($vars['looking'] && l('profile_orientations_allow_lowercase') == 'Y') {
                        $vars['looking'] = mb_strtolower($vars['looking'], 'UTF-8');
                    }
                }*/

            }
            if ($format != 'search' && $vars['here_to'] == '' && $vars['looking']) {
                $vars['looking'] = lSetVars('to_meet_with', array('looking' => $vars['looking']));
                }
            if ($format == 'search' && $vars['looking']) {
                $vars['looking'] .= $lFieldsDelimiter;
            }
        }

        if ($guid && UserFields::isActive('age_range') && $data['p_age_from'] && $data['p_age_to']) {
            $ageRange = ($data['p_age_from'] == $data['p_age_to']) ? $data['p_age_from'] : $data['p_age_from'] . l('profile_short_info_age_delimiter') . $data['p_age_to'];
            if ($format != 'search') {
                $vars['age'] = ($vars['looking'] ? $lFieldsDelimiter : '') . $ageRange;
            } else {
                $vars['age'] = $ageRange;
            }
        }

        if (!in_array($format, array('search', 'profile_view')) && $vars['here_to'] && ($vars['age'] || $vars['looking'])) {
            $vars['here_to'] .= $lFieldsDelimiter;
        }

        if ($vars['here_to'] == '') {
            //$lKey = '_search_not_here_to';
            $vars['here_to'] = self::prepareIAmHereInactive();
        }

        if ($format == 'search') {
            if ($vars['age']) {
                $vars['age'] = lSetVars('profile_search_filter_age', array('age' => $vars['age'] . $lFieldsDelimiter));
            }
            if($vars['looking']){
                $vars['looking'] = lSetVars('profile_search_filter_looking', array('looking' => $vars['looking']));
            }

            $radius = '';

            if(isset($data['all_items_select']) && $data['all_items_select']==1){
                $data['radius']=0;
            }

            $customVars = array();
            if ($data['radius'] != 0 && $data['radius'] <= intval(Common::getOption('max_search_distance'))) {
                $customVars['unit'] = l(Common::getOption('unit_distance'));
                $customVars['radius'] = $data['radius'];
                $radius = lSetVars('profile_search_filter_radius', $customVars);
            }
            $vars['distance'] = $radius;

            $customVars = array();
            if ($radius == '' && $data['radius'] != 0) {
                $customVars['location'] = l($data['country']);
                $location = 'country';
            } else {
                $customVars['location'] = $data['city'];
                $location = 'city';
            }
            if(isset($data['all_items_select']) && $data['all_items_select']==1){
                $location = 'all';
            }

            $vars['location'] = lSetVars('profile_search_filter_from_' . $location, $customVars);
        } elseif ($format == 'profile_view') {
            $vars['location'] = '';
            $template = 'profile_view_from_city';
            $prf = '';
            if (empty($vars['age']) && empty($vars['looking'])) {
                $lFieldsDelimiter = '';
                $prf = '_one';
            }
            $customVars['location'] = User::getLocationFiltersMobile($uid);
            if ($customVars['location']) {
                $vars['location'] = $lFieldsDelimiter . lSetVars($template . $prf, $customVars);
            }
        }
        return lSetVars('wants_to_with' . $lKey, $vars);
    }


    static public function getTitleOrientationLookingFor($row = null, $prf = null, $uid = null)
    {
        $title = '';
        if (UserFields::isActive('orientation')) {
            if(self::$orientations) {
                if ($row == null) {
                    $row = User::getInfoBasic($uid);
                }
                $orientationValues = array();
                foreach(self::$orientations as $orientation) {
                    if ($row['p_orientation'] & (1 << ($orientation['id'] - 1))) {
                        $orientationValues[] = UserFields::translation('orientation', $orientation['title'], $prf);
                    }
                }
                $title = l('somebody');
                if($orientationValues) {
                    $orientationValuesCount = count($orientationValues);
                    if($orientationValuesCount == 1) {
                        $title = $orientationValues[0];
                    } elseif($orientationValuesCount > 1) {
                        $orientationLast = array_pop($orientationValues);
                        $title = implode(l('profile_orientations_delimiter'), $orientationValues) . l('profile_orientations_last_delimiter') . $orientationLast;
                    }
                }
            }
        }
        return $title;
    }

    static public function getLookingForImpact(&$html, $uid, $row = null)
    {
        global $g_user, $g;

        $guid = $g_user['user_id'];

        if(EUsers_List::$c_user_id) // added by sohel
            $guid = EUsers_List::$c_user_id;

        // edit for mobile version
        $guid = isset($g['c_user_id']) ? $g['c_user_id'] : $guid;

        if ($row == null) {
            $row = User::getInfoBasic($uid);
        }
        $isMyProfile = $row['user_id'] == $guid;
        $optionTmplName = Common::getOption('name', 'template_options');
        $blockLooking = $isMyProfile ? 'looking_for' : 'looking_for_visitor';
        if ($optionTmplName == 'impact_mobile' ) {
            $blockLooking = 'looking_for';
        }
        $titleOrientation = self::getTitleOrientationLookingFor($row);
        if ($titleOrientation) {
            if (l('profile_looking_for_orientations_allow_lowercase') == 'Y'){
                $titleOrientation = mb_strtolower($titleOrientation, 'UTF-8');
            }
            if (l('profile_looking_for_orientations_allow_ucfirst') == 'Y'){
                $titleOrientation = mb_ucfirst($titleOrientation, 'UTF-8');
            }
            $html->setvar("{$blockLooking}_orientation", $titleOrientation);
            $html->parse("{$blockLooking}_orientation", false);
        }
        if (UserFields::isActive('age_range')) {
            $ageTo = intval($row['p_age_to']);
            if (!$ageTo) {
                $ageTo = Common::getOption('users_age_max');
            }
            $ageFrom = intval($row['p_age_from']);
            if (!$ageFrom) {
                $ageFrom = Common::getOption('users_age');
            }
            $titleAges = lSetVars('for_loking_for_ages_impact', array('age_from' => $ageFrom, 'age_to' => $ageTo));
            $html->setvar("{$blockLooking}_ages", $titleAges);
            $html->parse("{$blockLooking}_ages", false);
        }
        if (UserFields::isActive('i_am_here_to')) {
            $blockIAmHereTo = "{$blockLooking}_i_am_here_to";
            $title = DB::result("SELECT title FROM `const_i_am_here_to` WHERE `id` = " . to_sql($row['i_am_here_to']));
            if ($title) {
                $html->setvar($blockIAmHereTo, UserFields::translation('i_am_here_to', $title));
                $html->parse($blockIAmHereTo, false);
            }
        }
        $html->setvar("{$blockLooking}_near_me", self::getTitleSearchNearMe($row['user_id']));
        if ($isMyProfile && $html->blockExists("{$blockLooking}_edit")) {
            $html->parse("{$blockLooking}_edit", false);
        }
        $html->parse($blockLooking, false);

        if (!$isMyProfile) {
            if (!Common::isOptionActive('free_site') && Common::isActiveFeatureSuperPowers('invisible_mode')) {
                $param = '';
                if (!self::isSuperPowers()) {
                    $param = 'upgrade';
                }elseif(!self::isOptionSettings('set_do_not_show_me_visitors')){
                    $param = 'set_option';
                }
                if ($param) {
                    $html->setvar('browse_invisibly_param', $param);
                    $html->parse('browse_invisibly', false);
                }
            }
        }
    }

    static function prepareIAmHereToValue($value, $format)
    {
        static $values = null;

        if($values == null) {
            $values = Cache::get('field_values_const_i_am_here_to');
        }

        $result = '';

        $title = '';

        if($values && isset($values[$value])) {
            $title = $values[$value]['title'];
        } else {
            $where = '`id`= ' . to_sql($value, 'Number');
            $row = DB::one('const_i_am_here_to', $where, '', 'title', '', DB_MAX_INDEX, true);
            if(isset($row['title'])) {
                $title = $row['title'];
            }
        }

        if ($title) {
            $value = $title;
            $titleToPhp = to_php_alfabet($value);
            $one = '';
            if ($format != 'search') {
                $one = '_one';
            }

            $wordTemplateBaseName = 'profile_i_am_here_to_template';

            $template = $wordTemplateBaseName . $one;

            $templateValue = '';
            if($one == '') {
                $templateValue = '_template_value';
            }

            $keys = array('profile_i_am_here_to' . $templateValue . '_' . $titleToPhp);
            $delimiter = l('profile_short_info_fields_delimiter');
            if ($format == 'search') {
                $template = $wordTemplateBaseName . '_' . $format;
                $keys = array('profile_i_am_here_to_template_value' . '_' . $titleToPhp);

                $isOrientationActive = UserFields::isActive('orientation');

                if($isOrientationActive) {
                    $delimiter = l('profile_short_info_here_to_delimiter');
                }
            }

            $innerVars = array(
                'delimiter' => $delimiter,
                'value' => lCascade($value, $keys),
                'field' => l("I am here"),
            );
            $result = lSetVars($template, $innerVars);
        }

        return $result;
    }

    static function prepareIAmHereInactive()
    {
        return lSetVars('i_am_here_to_inactive_field_value', array('field' => l('i_am_here')));
    }

    static function getNumberViewersMeProfiles()
    {
        global $g_user;

        /*
        $viewers = DB::field('users_view', 'new', '`user_to` = ' . to_sql(guid()));
        $isNew = 0;
        foreach ($viewers as $new) {
            if ($new == 'Y') {
                $isNew++;
                break;
            }
        }
         */

        $user_id = guid();

        // $sql = 'SELECT COUNT(*) AS counter, MAX(`new`) AS is_new FROM `users_view` WHERE `user_to` = ' . to_sql(guid());

        /*$sql = "SELECT COUNT(*) AS counter, MAX(`new`) AS is_new FROM user AS u JOIN users_view AS v ON (u.user_id=v.user_from AND (v.user_to= {$user_id} OR v.user_to IN (SELECT user_id FROM user WHERE under_admin = {$user_id}))) WHERE u.user_id != {$user_id} GROUP BY v.user_to";*/

        $sql = "SELECT COUNT(*) AS counter, is_new FROM (SELECT COUNT(u.user_id), MAX(`new`) AS is_new FROM user AS u  JOIN users_view AS v ON (u.user_id=v.user_from AND (v.user_to= {$user_id} OR v.user_to IN (SELECT user_id FROM user WHERE under_admin = {$user_id}))) WHERE u.user_id != {$user_id} GROUP BY u.user_id, user_to) t";

        $row = DB::row($sql);
        if($row) {
            $count = $row['counter'];
            $isNew = $row['is_new'] == 'Y' ? 1 : 0;
        } else {
            $count = 0;
            $isNew = 0;
        }

        return array('count' => $count, 'new' => $isNew);
    }

    static function getDataJsNewVisitors()
    {
        global $g_user;

        $responseData = '';
        if (!Common::isEnabledAutoMail('profile_visitors')) {
            return '';
        }
        $allMsgs = array();
        $timeoutSecServer = get_param('timeout_server');

        $where = '`user_to` = ' . to_sql($g_user['user_id'])
               . ' AND `visited` = 1 '
               . ' AND `created_at` >= ' . to_sql(date('Y-m-d H:i:s', time() - $timeoutSecServer));
        $usersVisitors = DB::select('users_view', $where);
        if ($usersVisitors) {
            $i = 0;
            $urlFiles = Common::getOption('url_files', 'path');
            foreach ($usersVisitors as $user) {
                    $allMsgs[$i]['id'] = 'visitor_' . $user['id'];
                    $allMsgs[$i]['title'] = 'VISITOR';
                    $vars = array('url' => "search_results.php?display=profile&uid={$user['user_from']}",
                                  'name' => User::nameOneLetterFull(User::getInfoBasic($user['user_from'], 'name')));
                    $allMsgs[$i]['title'] = Common::lSetLink('name_sent_you_a_message', $vars);
                    $allMsgs[$i]['photo'] = $urlFiles . User::getPhotoDefault($user['user_from'], 'r');
                    $vars['text'] = hard_trim(l('has_visited_your_profile'), 55);
                    $allMsgs[$i]['text'] = Common::lSetLink('new_message_notif', $vars);
                    $i++;
            }
            if ($allMsgs) {
                $allMsgs = defined('JSON_UNESCAPED_UNICODE') ? json_encode($allMsgs, JSON_UNESCAPED_UNICODE) : json_encode($allMsgs);
                $responseData = "<script>Messages.showNotifAllMsg(" . $allMsgs . ",1);</script>";
            }

            DB::update('users_view', array('visited' => 0), 'user_to = ' . to_sql($g_user['user_id'], 'Number'));
        }

        return $responseData;
    }

    static function getNumberFriendsAndPending($uid = null)
    {
        if ($uid === null) {
            $uid = guid();
        }
        $guidSql = to_sql($uid);
        $sql = "SELECT
                    (SELECT COUNT(*) as cnt
                       FROM `friends_requests`
                      WHERE user_id = {$guidSql}
                        AND `accepted` = 1)
                    +
                    (SELECT COUNT(*) as cnt
                       FROM `friends_requests`
                      WHERE friend_id = {$guidSql}
                        AND `accepted` = 1)
                    +
                    (SELECT COUNT(*) as cnt
                       FROM `friends_requests`
                      WHERE `friend_id` = {$guidSql}
                        AND `accepted` = 0)
                    ";
		return DB::result($sql);
    }

    static function getNumberRequestsToFriendsPending($uid = null)
    {
        if ($uid === null) {
            $uid = guid();
        }
        $sql = "SELECT COUNT(*)
                  FROM `friends_requests`
                 WHERE `friend_id` = " . to_sql($uid) .
                 " AND `accepted` = 0";
		return DB::result($sql);
    }


    static function getNumberFriendsOnline($uid = null)
    {
        global $g_user;

        if ($uid === null) {
            $uid = $g_user['user_id'];
        }

        $whereOnline = ' AND U.last_visit > ' . to_sql(date('Y-m-d H:i:s', time() - Common::getOption('online_time') * 60)) . ' ';

        $sql = 'SELECT
                (SELECT COUNT(*)
                  FROM `friends_requests` as F
                  JOIN `user` AS U ON U.user_id = F.friend_id
                 WHERE F.accepted = 1
                   AND F.user_id = ' . to_sql($uid, 'Number') . $whereOnline . ')
                +
                (SELECT COUNT(*)
                   FROM `friends_requests` as F
                   JOIN `user` AS U ON U.user_id = F.user_id
                  WHERE F.accepted = 1
                    AND F.friend_id = ' . to_sql($uid, 'Number') . $whereOnline . ')';


        /*$sql = 'SELECT COUNT(*)
                  FROM `friends_requests` AS F
                  LEFT JOIN `user` AS U ON U.user_id = IF(F.user_id = ' . to_sql($uid) . ', F.friend_id, F.user_id)
                 WHERE F.accepted = 1 ' . $whereOnline .
                '  AND (F.user_id = '. to_sql($uid) . " OR F.friend_id = " . to_sql($uid) . ')';*/

        return DB::result($sql);
    }

    static function getListFriends($uid = null, $online = false, $limit = '', $fidIndex = false, $isLiveUserCheck = false)
    {
        global $g;

        if ($uid == null) {
            $uid = guid();
        }

        $key = 'User_getListFriends_' . $uid . '_' . intval($online) . '_' . intval($fidIndex) . ($limit ? '_' . $limit : '_all');
        $friendsList = Cache::get($key);
        if ($friendsList !== null) {
            return $friendsList;
        }

        if ($limit) {
            $limit = ' LIMIT ' . $limit;
        }

        $whereOnline = '';
        if ($online) {
            $whereOnline = ' AND U.last_visit > ' . to_sql(date('Y-m-d H:i:s', time() - Common::getOption('online_time') * 60)) . ' ';
        }
        $sql = "(SELECT F.activity, F.created_at, F.friend_id, F.user_id AS fuser_id, U.name, U.user_id, U.gender, U.birth, U.set_notif_show_my_age
                   FROM `friends_requests` as F
                   JOIN `user` AS U ON U.user_id = F.friend_id
                  WHERE F.accepted = 1
                    AND F.user_id = " . to_sql($uid, 'Number') . $whereOnline . ')' .
                ' UNION ' .
               "(SELECT F.activity, F.created_at, F.friend_id, F.user_id AS fuser_id, U.name, U.user_id, U.gender, U.birth, U.set_notif_show_my_age
                   FROM `friends_requests` as F
                   JOIN `user` AS U ON U.user_id = F.user_id
                  WHERE F.accepted = 1
                    AND F.friend_id = " . to_sql($uid, 'Number') . $whereOnline . ')
                  ORDER BY activity DESC, created_at DESC, fuser_id DESC, friend_id DESC ' . $limit;

        $fetchType = DB::getFetchType();
        DB::setFetchType(MYSQL_ASSOC);
        $friendsList = DB::rows($sql, 5, true);
        DB::setFetchType($fetchType);
        if ($fidIndex) {
            $result = array();
            foreach ($friendsList as $key => $item) {
                $fid = $item['user_id'];
                $result[$fid] = $item;
                $photo = $g['path']['url_files'] .  User::getPhotoDefault($item['user_id'], 's', false, $item['gender']);
                $result[$fid]['friend_photo'] = $photo;
                $result[$fid]['friend_url'] = User::url($fid);
                $result[$fid]['friend_name'] = $item['name'];
                $result[$fid]['live_now'] = 0;
                if ($isLiveUserCheck && LiveStreaming::isAviableLiveStreaming()) {
                    $userLiveNowId = LiveStreaming::getUserLiveNowId($item['user_id']);
                    $url = $userLiveNowId ? Common::pageUrl('live_id', $item['user_id'], $userLiveNowId) : '';
                    $result[$fid]['live_now'] = $url;
                }

            }
            $friendsList = $result;
        }
        Cache::add($key, $friendsList);

        return $friendsList;
    }

    static function getNumberFriends($uid = null)
    {
        global $g_user;

        if ($uid === null) {
            $uid = $g_user['user_id'];
        }

        $sql = 'SELECT
                (SELECT COUNT(*) FROM `friends_requests`
                WHERE `user_id` = ' . to_sql($uid, 'Number') . '
                   AND `accepted` = 1)
                +
                (SELECT COUNT(*) FROM `friends_requests`
                WHERE `friend_id` = ' . to_sql($uid, 'Number') . '
                   AND `accepted` = 1)';
        return DB::result($sql);
    }

    static function update($data, $uid = null, $table = 'user')
    {
        if($uid === null) {
            $uid = guid();
        }
        if(!$uid) {
            return;
        }
        DB::update($table, $data, 'user_id = ' . to_sql($uid));

        // Fix old cache after update
        if($table == 'user') {
            $key = 'userinfo_' . $uid;
            $info = Cache::get($key);
            if($info) {
                $info = array_merge($info, $data);
                Cache::add($key, $info);
            }
        }

        if($uid === guid()) {
            global $g_user;
            foreach($data as $key => $value) {
                $g_user[$key] = $value;
            }
        }
    }

    static function getPositionInSearchResult($uid = null)
    {
        global $g_user;

        if ($uid === null) {
            $userInfo = $g_user;
        } else {
            $userInfo = User::getInfoBasic($uid);
        }

        $sqlCity = to_sql($userInfo['city_id']);
        $sqlState = to_sql($userInfo['state_id']);
        $sqlCountry =  to_sql($userInfo['country_id']);

        $sql = 'SELECT user_id FROM (
            (SELECT ' . to_sql($userInfo['user_id']) . ' AS user_id, ' . to_sql($userInfo['date_search']) . ' AS date_search, 3 AS near)
            UNION
            (SELECT user_id, date_search, IF(city_id=' . $sqlCity . ', 1, 0) + IF(state_id=' . $sqlState . ', 1, 0) + IF(country_id=' . $sqlCountry . ', 1, 0) AS near
                FROM `user`
                WHERE `set_hide_my_presence` = 2
                AND `country_id` = ' . $sqlCountry . ')
            ) AS T
            ORDER BY date_search DESC, near DESC, user_id DESC';
        DB::query($sql, 2);

        $numSearch = 1;
        while ($search = DB::fetch_row(2)) {
            if ($search['user_id'] == $userInfo['user_id']) {
                break;
            }
            $numSearch++;
        }
        return $numSearch;
    }

    static function updatePopularity($uid, $counter = null)
    {
        if (!$uid) {
            return;
        }
        if ($counter === null) {
            $counter = 1;
        }
        $sql = 'UPDATE `user`
                   SET `popularity` = `popularity` + ' . to_sql($counter) .
               ' WHERE `user_id` = ' . to_sql($uid);
        DB::execute($sql);
    }

    static function getMaxPopularityInCity($cityId, $update = false)
    {
        if($update || empty(self::$maxPopularityInCity) || !isset(self::$maxPopularityInCity[$cityId])) {
            $sql = 'SELECT MAX(popularity)
                      FROM `user`
                     WHERE `city_id` = ' . to_sql($cityId);
            self::$maxPopularityInCity[$cityId] = DB::result($sql);
        }

        return self::$maxPopularityInCity[$cityId];
    }

    static function getPopularInYourCity($uid = null, $update = false)
    {
        global $g_user;

        if ($uid === null) {
            $userInfo = $g_user;
        } else {
            $userInfo = User::getInfoBasic($uid);
        }

        $maxPopularity = self::getMaxPopularityInCity($userInfo['city_id'], $update);
        $popularity = 0;
        if (!empty($maxPopularity)) {
            $popularity = round($userInfo['popularity']*100/$maxPopularity);
        }
        return $popularity;
    }

    static function getLevelOfPopularity($uid = null, $update = false)
    {

        if ($uid === null) {
            $uid = guid();
        }
        if($update || empty(self::$levelOfPopularity) || !isset(self::$levelOfPopularity[$uid])) {
            $userPopular = User::getPopularInYourCity($uid, $update);
            $graduation = array('very_low' => array(0,20),
                                'low' => array(20,40),
                                'medium' => array(40,60),
                                'high' => array(60,80),
                                'very_high' => array(80,100));
            self::$levelOfPopularity[$uid] = 'very_low';
            foreach ($graduation as $key => $row) {
                if ($row[0] < $userPopular && $userPopular <= $row[1]) {
                    self::$levelOfPopularity[$uid] = $key;
                    break;
                }
            }
        }
        return self::$levelOfPopularity[$uid];
    }

    static function hideFromUsers($isHide = true)
    {
        $hideTime = ($isHide) ? Common::getOption('hide_time') : 0;
        $sql = "UPDATE `user`
                   SET `hide_time` = " . to_sql($hideTime,'Number')
             . " WHERE `user_id` = " . to_sql(guid(), 'Number');
  	    DB::execute($sql);
    }

    static function isSettingEnabled($option)
    {
        $hideOption = Common::getOptionTemplate('hide_profile_settings');
        if (is_array($hideOption)) {
            $result = !in_array($option, $hideOption);
        } else {
            $result = true;
        }

        if (!$result) {
            $showOption = Common::getOptionTemplate('show_profile_settings');
            if (is_array($showOption)) {
                $result = $result || in_array($option, $showOption);
            }
        }

        return $result;
    }

    static function isOptionSettings($option, $user = null)
    {
        global $g_user;

        if ($user === null) {
            $user = $g_user;
        }
        if ($user['user_id'] && isset($user[$option])) {
            return $user[$option] == 1;
        } else {
            return false;
        }
    }

    static function isSuperPowers($goldDays = null, $orientation = null)
    {
        if ($goldDays === null) {
            $goldDays = guser('gold_days');
        }
        if ($orientation === null) {
            $orientation = guser('orientation');
        }
        return $goldDays > 0 || self::isFreeAccess($goldDays, $orientation);
        /*if ($goldDays) {
            return $goldDays > 0;
        } else {
            return false;
        }*/
    }

    static function accessCheckFeatureSuperPowers($feature, $goldDays = null, $orientation = null, $status = null)
    {
        if(Common::isOptionActive('free_site')) {
            return true;
        }
        if ($status === null) {
            $status = Common::isActiveFeatureSuperPowers($feature);
        }
        if (!$status) {
            return true;
        }
        if ($goldDays === null) {
            $goldDays = guser('gold_days');
        }
        if ($orientation === null) {
            $orientation = guser('orientation');
        }
        $result = true;
        if (!User::isSuperPowers($goldDays, $orientation)) {
            $result = false;
        }
        return $result;
    }

    static function accessСheckFeatureSuperPowersGetList($json = true)
    {
        $result = array();
        if (guid()) {
            $typePayment = Common::getOption('type_payment_features', 'template_options');
            $typePaymentFeatures = '%' . $typePayment . '%';
            $features = DB::select('payment_features', '`type` LIKE ' . to_sql($typePaymentFeatures));
            foreach ($features as $key => $item) {
                if ($item['status']) {
                    $result[$item['alias']] = intval(User::accessCheckFeatureSuperPowers($item['alias'], null, null, $item['status']));
                } else {
                    $result[$item['alias']] = 1;
                }
            }
            $result['site_access_paying'] = intval(Common::isOptionActive('access_paying') && !User::isPaid(guid()));
            $result['min_number_upload_photos'] = User::checkAccessToSiteWithMinNumberUploadPhotos(true);
        }
        if ($json) {
            $result = json_encode($result);
        }
        return $result;
    }

    static function accessCheckFeatureSuperPowersToRedirect($feature, $goldDays = null, $orientation = null, $url = null)
    {
        global $g;

        if ($goldDays === null) {
            $goldDays = guser('gold_days');
        }
        if ($orientation === null) {
            $orientation = guser('orientation');
        }

        if (!self::accessCheckFeatureSuperPowers($feature, $goldDays, $orientation)){
            if ($url === null) {
                $url = Common::isMobile() ? $g['path']['url_main_mobile'] : $g['path']['url_main'];
            }
            redirect($url . 'upgrade.php');
        }
    }

    // Dubl self::getPositionInSearchResult
    static function isActiveService($type = 'spotlight')
    {
        if(empty(self::$isActiveService) || !isset(self::$isActiveService[$type])) {
            global $g_user;

            if ($type == 'spotlight') {
                $sql = Spotlight::getSql();
            } else {
                $sqlCity = to_sql($g_user['city_id']);
                $sqlState = to_sql($g_user['state_id']);
                $sqlCountry =  to_sql($g_user['country_id']);

                $sql = 'SELECT user_id, IF(city_id=' . $sqlCity . ', 1, 0) + IF(state_id=' . $sqlState . ', 1, 0) + IF(country_id=' . $sqlCountry . ', 1, 0) AS near
                          FROM `user`
                         WHERE `country_id` = ' . $sqlCountry . "
                           AND `date_" . $type . "` != '0000-00-00 00:00:00'
                           AND (set_hide_my_presence = 2 OR (set_hide_my_presence = 1 AND user_id = " . to_sql($g_user['user_id']) . "))
                         ORDER BY `near` DESC, `date_" . $type . "` DESC
                         LIMIT " . to_sql(Common::getOption('search_service_number'), 'Number');
            }
            self::$isActiveService[$type] = false;
            $users = DB::rows($sql);
            foreach ($users as $user) {
                if ($user['user_id'] == $g_user['user_id']) {
                    /*if ($type == 'spotlight') {
                        if (User::getPhotoDefault($g_user['user_id'], 's', true)) {
                            self::$isActiveService[$type] = true;
                        }
                    } else {*/
                        self::$isActiveService[$type] = true;
                    //}
                    break;
                }
            }
        }
        return self::$isActiveService[$type];
    }

    static function isActivityAllServices($update = false)
    {
        $isActivity = get_session('activity_services');
        $isLastUpdate = get_session('activity_services_time');
        if($update || $isLastUpdate === '' || $isActivity === '' || ((time() - $isLastUpdate) >= 600)) {
            $services = array('spotlight', 'search', 'encounters');
            $isActivity = true;
            foreach ($services as $type) {
                if (!self::isActiveService($type)) {
                    $isActivity = false;
                    break;
                }
            }
            set_session('activity_services', $isActivity);
            set_session('activity_services_time', time());
        }
        return $isActivity;
    }

    static function getWhatDateActiveSuperPowers($userInfo = null)
    {
        if ($userInfo === null) {
            global $g_user;
            $userInfo = $g_user;
        }
        $date = new DateTime(date('Y-m-d'));
        $date->add(new DateInterval('P' . $userInfo['gold_days'] . 'D'));
        return Common::dateFormat($date->format('Y-m-d H:i:s'), 'super_powers_date_format', false, true);
    }

    static function getPartnerOrientationWhereSql($tablePrefix = 'u.', $partnerOrientation = false)
    {
        if($partnerOrientation === false) {
            $partnerOrientation = guser('p_orientation');
        }
        if($partnerOrientation) {
            $partnerOrientation = $partnerOrientation . " & (1 << (cast({$tablePrefix}orientation AS signed) - 1)) ";
        } else {
            $partnerOrientation = '';
        }
        return $partnerOrientation;
    }

    static function checkLocationFilter($filtersInfo, $json = false)
    {
        if (is_string($filtersInfo)) {
            $filtersInfo = json_decode($filtersInfo, true);
        }
        if (is_array($filtersInfo)) {
            $isAllowedLocation = true;

            $optionTmplName = Common::getTmplName();
            if (Common::isOptionActiveTemplate('join_location_allow_disabled') && !Common::isOptionActive('location_enabled', "{$optionTmplName}_join_page_settings")) {
                $filtersInfo['radius'] = array('field' => 'radius', 'value' => 0);
                $geoDefaultInfo = IP::geoInfoCityDefault();
                $filtersInfo['country'] = array('field' => 'country', 'value' => $geoDefaultInfo['country_id']);
                $filtersInfo['state'] = array('field' => 'state', 'value' => $geoDefaultInfo['state_id']);
                $filtersInfo['city'] = array('field' => 'city', 'value' => $geoDefaultInfo['city_id']);
            }

            if (isset($filtersInfo['country'])) {
                $sql = 'SELECT `country_id`
                          FROM `geo_country`
                         WHERE (hidden = 0 AND country_id = ' . to_sql($filtersInfo['country']['value']) . ')';
                if (!DB::result($sql)) {
                    //reset all countries
                    $isAllowedLocation = false;
                    $filtersInfo['country'] = array('field' => 'country', 'value' => 0);
                    $filtersInfo['state'] = array('field' => 'state', 'value' => 0);
                    $filtersInfo['city'] = array('field' => 'city', 'value' => 0);
                }
            }
            if ($isAllowedLocation && isset($filtersInfo['state'])) {
                $sql = 'SELECT `state_id`
                          FROM `geo_state`
                         WHERE (hidden = 0 AND state_id = ' . to_sql($filtersInfo['state']['value']) . ')';
                if (!DB::result($sql)) {
                    //to reset to all regions in the country
                    $isAllowedLocation = false;
                    $filtersInfo['state'] = array('field' => 'state', 'value' => 0);
                    $filtersInfo['city'] = array('field' => 'city', 'value' => 0);
                }
            }
            if ($isAllowedLocation && isset($filtersInfo['city'])) {
                $sql = 'SELECT `city_id`
                          FROM `geo_city`
                         WHERE (hidden = 0 AND city_id = ' . to_sql($filtersInfo['city']['value']) . ')';
                if (!DB::result($sql)) {
                    //to reset to all cities in the region
                    $filtersInfo['city'] = array('field' => 'city', 'value' => 0);
                }
            }
            if (!$isAllowedLocation) {
            }

            if ($json) {
                $filtersInfo = json_encode($filtersInfo);
            }
        }

        return $filtersInfo;
    }

    static function setGetParamsFilter($typeFilter = null, $userinfo = null)
    {
        global $g_user;

        if ($typeFilter === null) {
            $typeFilter = 'user_search_filters';
        }
        if ($userinfo == null) {
            $userinfo = User::getInfoFull($g_user['user_id']);
        }
        /*if (IS_DEMO && $g_user['user_id'] == 638 && get_session("demo_{$typeFilter}")) {
            $userinfo[$typeFilter] = get_session("demo_{$typeFilter}");
        }*/
        $optiontTmplName = Common::getOption('name', 'template_options');
        $isFilterSocial = Common::isOptionActiveTemplate('list_users_filter_social');
        $isActiveLocation = Common::isOptionActive('location_enabled', "{$optiontTmplName}_join_page_settings");

        $userinfo[$typeFilter] = self::getParamsFilter($typeFilter, $userinfo[$typeFilter]);
        $g_user[$typeFilter] = $userinfo[$typeFilter];
        $g_user['state_filter_search'] = $userinfo['state_filter_search'];
        $filters = $userinfo[$typeFilter];
        $filtersInfo = array();

        if($filters) {
            $filtersInfo = json_decode($filters, true);
            $filtersInfo = self::checkLocationFilter($filtersInfo);
            $allowedFilterFields = array();
            if ($isFilterSocial) {//Edge
                $allowedFilterFields = array(
                    'country', 'state', 'city', 'radius', 'all_countries', 'with_photo', 'people_nearby', 'status'
                );
                if (!$isActiveLocation) {
                    $allowedFilterFields = array(
                        'with_photo', 'status'
                    );
                }
            }
            foreach($filtersInfo as $filterInfoKey => $filterInfoValue) {
                if($allowedFilterFields && !in_array($filterInfoValue['field'], $allowedFilterFields)) {
                    continue;
                }
                if(isset($filterInfoValue['values'])) {
                    foreach($filterInfoValue['values'] as $key => $value) {
                        $_GET[$key] = $value;
                    }
                } else {
                    $_GET[$filterInfoKey] = $filterInfoValue['value'];
                }
            }
        }
        $_GET['country'] = get_param('country', $g_user['country_id']);
        $_GET['state'] = get_param('state', $g_user['state_id']);
        $_GET['city'] = get_param('city', $g_user['city_id']);
        if (guid()) {
            if (UserFields::isActive('age_range')) {
                $ageFrom = get_param('p_age_from', $g_user['p_age_from']);
                $_GET['p_age_from'] = $ageFrom;
                $filtersInfo['p_age_from'] = $ageFrom;
                $ageTo = get_param('p_age_to', $g_user['p_age_to']);
                $_GET['p_age_to'] = $ageTo;
                $filtersInfo['p_age_to'] = $ageTo;
            }
            if (!$isFilterSocial && UserFields::isActive('i_am_here_to')) {
                $_GET['i_am_here_to'] = get_param('i_am_here_to', $g_user['i_am_here_to']);
            }
            if (UserFields::isActive('orientation')) {// || Common::isOptionActive('your_orientation')
                $orientation = UserFields::checksToParamsArray('const_orientation', $g_user['p_orientation']);
                $_GET['p_orientation'] = $orientation;
                $filtersInfo['p_orientation'] = $orientation;
            }
        }
        return $filtersInfo;
    }

    static function getParamsFilter($typeFilter, $filter)
    {
        $guid = guid();
        if (IS_DEMO && in_array($guid, demoLoginId()) && get_session("demo_{$guid}_{$typeFilter}")) {
            $filter = get_session("demo_{$guid}_{$typeFilter}");
        }
        return $filter;
    }

    static function updateParamsFilterUserInfoForData($typeFilter, $userSearchFilters, $userinfo = null)
    {
        global $g_user;

        if ($userinfo == null) {
            $userinfo = User::getInfoFull($g_user['user_id']);
        }
        $filter = json_decode($userinfo[$typeFilter], true);
        $isUpdate = false;
        foreach($userSearchFilters as $k => $field) {
            if (!$field['value']) {
                $isUpdate = true;
                unset($filter[$k]);
                continue;
            }
            if (!isset($filter[$k]) || $filter[$k]['value'] != $field['value']) {
                $isUpdate = true;
            }
            $filter[$k] = $field;
        }
        if ($isUpdate) {
            $filter = json_encode($filter);
            self::updateParamsFilter($typeFilter, $filter);
            $userinfo[$typeFilter] = $filter;
        }
        return $userinfo;
    }

    static function updateParamsFilterUserInfo($typeFilter, $userinfo = null, $fields = array('country', 'state', 'city'))
    {
        global $g_user;

        if ($userinfo == null) {
            $userinfo = User::getInfoFull($g_user['user_id']);
        }
        $filter = json_decode($userinfo[$typeFilter], true);

        $isUpdate = false;
        foreach($fields as $field) {
            $value = get_param($field, null);
            if ($field == 'country' && $value == 'people_nearby') {
                $value = 0;
                if (isset($filter[$field])) {
                    $value = $filter[$field]['value'];
                }
            }
            //var_dump_pre($field . ' /' . $value);
            if ($value === null) {
                $isUpdate = true;
                unset($filter[$field]);
                continue;
            }
            if (!isset($filter[$field]) || $filter[$field]['value'] != $value) {
                $isUpdate = true;
            }

            $filter[$field]['field'] = $field;
            $filter[$field]['value'] = $value;
        }
        if ($isUpdate) {
            $filter = json_encode($filter);
            self::updateParamsFilter($typeFilter, $filter);
            $userinfo[$typeFilter] = $filter;

            // Fix old cache after update
            self::getInfoFull($g_user['user_id'], 0, true, $userinfo);
        }
        return $userinfo;
    }

    static function updateFilterAll($userinfo = null, $fields = array('country', 'state', 'city'))
    {
        global $g_user;

        if (!$g_user['user_id']) return;
        if ($userinfo == null) {
            $userinfo = User::getInfoFull($g_user['user_id']);
        } else
            $userinfo = User::getInfoFull($userinfo);
            
        User::updateParamsFilterUserInfo('user_search_filters', $userinfo, $fields);
        User::updateParamsFilterUserInfo('user_search_filters_mobile', $userinfo, $fields);
        return $userinfo;
    }

    static function updateFilterLocationChangingUserLocation($userinfo = null)
    {
        global $g_user;

        if (!$g_user['user_id']) return;
        if ($userinfo == null) {
            $userinfo = User::getInfoFull($g_user['user_id']);
        }
        $filter = json_decode($userinfo['user_search_filters'], true);
        if ($filter) {
            User::updateParamsFilterUserInfo('user_search_filters', $userinfo);
        }
        $filterMobile = json_decode($userinfo['user_search_filters_mobile'], true);
        if ($filterMobile) {
            User::updateParamsFilterUserInfo('user_search_filters_mobile', $userinfo);
        }
    }

    static function updateParamsFilter($typeFilter, $filter)
    {
        $guid = guid();
        if (IS_DEMO && in_array($guid, demoLoginId())) {
            set_session("demo_{$guid}_{$typeFilter}", $filter);
        } else {
            $data = array($typeFilter => $filter);
            User::update($data, $guid, 'userinfo');
        }
    }

    static function updateParamsFilterUser()
    {
        global $g_user;

        $data = array();
        $fields = array(
            'i_am_here_to',
            'p_orientation',
            'p_age_from',
            'p_age_to',
        );
        $fieldsActive = array(
            'i_am_here_to' => 'i_am_here_to',
            'p_orientation' => 'orientation',
            'p_age_from' => 'age_range',
            'p_age_to' => 'age_range',
        );
        foreach($fields as $field) {
            if (!UserFields::isActive($fieldsActive[$field])) {
                continue;
            }
            $fieldValue = get_param($field);
            if($field == 'p_orientation') {
                if(Common::isOptionActive('your_orientation')) {
                    $fieldValue = $g_user['p_orientation'];
                } else {
                    $fieldValue = get_checks_param($field);
                }
            }
            if(guser($field) != $fieldValue) {
                $data[$field] = $fieldValue;
                $g_user[$field] = $fieldValue; //desctop not
            }
        }

        if($data) {
            //var_dump($data);
            User::update($data);
        }
    }

    static function setDefaultParamsFilterUser($uid, $isSetParams = true, $isCustomRegister = false)
    {
        $radiusDefault = intval(Common::getOption('default_search_distance'));
        $radiusMax = Common::getOption('max_search_distance');
        if ($radiusDefault > $radiusMax) {
            $radiusDefault = $radiusMax;
        }
        $userSearchFilters['radius'] = array(
            'field' => 'radius',
            'value' => $radiusDefault,
        );

        $userSearchFilters['people_nearby'] = array(
            'field' => 'people_nearby',
            'value' => intval(Common::getOption('default_search_type') == 'people_nearby'),
        );

        $userSearchFiltersLocation = array();
        $defaultLocation = Common::getOption('default_search_location');

        if (!guid() && !$isSetParams) {
            $paramsCity = get_param('city');
            if ($paramsCity) {
				$userSearchFilters['people_nearby']['value'] = 0;
                $sql = 'SELECT * FROM geo_city WHERE city_id = ' . to_sql($paramsCity);
                $cityInfo = DB::row($sql);
                $userSearchFiltersLocation = array('country' => array('field' => 'country',
                                                                      'value' => $cityInfo['country_id']),
                                                   'state'   => array('field' => 'state',
                                                                      'value' => $cityInfo['state_id']),
                                                   'city'    => array('field' => 'city',
                                                                      'value' => $paramsCity),
                                             );
            }
        }
        //$defaultLocation != 'city'
        if ($isSetParams || (!$isSetParams && !$paramsCity)) {
            if ($isSetParams) {
                $location = array('all_country' => 0,
                                  'country' => get_session('j_country'),
                                  'state' => get_session('j_state'),
                                  'city' => get_session('j_city'),
                            );
            } else {
                $geoInfo = getDemoCapitalCountry();
                $location = array('all_country' => 0,
                                  'country' => $geoInfo['country_id'],
                                  'state' => $geoInfo['state_id'],
                                  'city' => $geoInfo['city_id']
                            );
            }
            $fields = array('all_country', 'country', 'state', 'city');
            $isResetFiled = false;
            foreach ($fields as $field) {
                $userSearchFiltersLocation[$field] = array(
                    'field' => $field,
                    'value' => $isResetFiled ? 0 : $location[$field],
                );
                if ($field == $defaultLocation) {
                    $isResetFiled = true;
                }
            }
            unset($userSearchFiltersLocation['all_country']);
        }
        $userSearchFilters = array_merge($userSearchFilters, $userSearchFiltersLocation);
        if ($isCustomRegister || get_param('join_search_page')) {
            $joinAnswersFilters = User::getParamsFilterJoinUser($isCustomRegister);
            if ($joinAnswersFilters) {
                $userSearchFilters = array_merge($userSearchFilters, $joinAnswersFilters);
            }
        }
        $filter = json_encode($userSearchFilters);

        if ($isSetParams) {
            User::update(array('user_search_filters' => $filter), $uid, 'userinfo');
			User::update(array('user_search_filters_mobile' => $filter), $uid, 'userinfo');
        } else {
            return $filter;
        }
    }

    static function getParamsFilterJoinUser($isUpdateProfile = false)
    {
        global $g, $g_user;

        $orientation = null;
        if (UserFields::isActive('orientation')) {
            $orientation = get_session('j_orientation');
            if (!$orientation) {
                $orientation = DB::result('SELECT `id` FROM `const_orientation` WHERE `default` = 1');
                if (!$orientation) {
                    $orientation = 1;
                }
            }
            $sql = 'SELECT `search` FROM `const_orientation` WHERE `id` = ' . to_sql($orientation);
            $orientation = DB::result($sql);
            if (!$isUpdateProfile) {
                $_GET['p_orientation'] = $orientation;
            }
        }

        $joinAnswers = get_param_array('join_answers');
        //if (!$joinAnswers) {
            //return array();
        //}
        $userSearchFilters = array();
        $count = 0;
        $isSetIamHereTo = false;
        foreach ($joinAnswers as $key => $value) {
            $data = UserFields::checkFiledQuestion($key);
            if ($data) {
                $answer = json_decode($data['answer'], true);
                if (!$answer || !isset($answer[$value])) {
                    continue;
                }
                if ($isUpdateProfile && $count == 3) {
                    break;
                }
                $answer = $answer[$value];
                if ($data['type_field'] == 'radio') {
                    if ($isUpdateProfile) {
                        $isSetIamHereTo = true;
                        User::update(array('i_am_here_to' => $answer));
                    } else {
                        $_GET[$key] = $answer;
                    }
                } elseif ($data['type_field'] == 'checks') {
                    $count++;
                    $answerFrom = array('from' => 0, 'to' => 0);
                    if ($orientation !== null) {
                        if (isset($answer[$orientation])) {
                            $answerFrom = $answer[$orientation];
                        }
                    } elseif (isset($answer['no'])) {
                        $answerFrom = $answer['no'];
                    }
                    if ($answerFrom) {
                        foreach ($answerFrom as $k => $v) {
                            $answerFrom[$k] = intval($v);
                        }
                    }
                    if ($answerFrom['from'] || $answerFrom['to']) {
                        $userSearchFilters['p_' . $key . '_from'] = array(
                                'field' => $key,
                                'values' => array('p_' . $key . '_from' => $answerFrom['from'],
                                                  'p_' . $key . '_to' => $answerFrom['to']),
                        );
                    }
                } else {
                    $count++;
                    $keyParam = $key;
                    if ($data['type_field'] == 'selection') {
                        $keyParam = 'p_' . $key;
                    }
                    $userSearchFilters[$keyParam] = array(
                        'field' => $key,
                        'value' => $answer,
                    );
                }
            }
        }
        if ($isUpdateProfile && $count < 3) {
            $users = get_param_array('users_like');
            $userMatchesFilters = self::getParamsFilterByMatchesUsersInfo($users, 3 - $count, $isSetIamHereTo);
            if ($userMatchesFilters) {
                $userSearchFilters = array_merge($userSearchFilters, $userMatchesFilters);
            }
        }
        return $userSearchFilters;
    }

    static function getParamsFilterByMatchesUsersInfo($users, $countParams, $isSetIamHereTo = false, $isUidKey = true)
    {
        global $g;

        $userSearchFilters = array();
        if (!$users) {
            return $userSearchFilters;
        }
        $fields = $g['user_var'];
        foreach ($fields as $key => $field) {
            if (!in_array($field['type'], array('text', 'textarea'))) {
                $data = UserFields::checkFiledQuestion($key);
                if($data){
                    $fields[$key]['type_field'] = $data['type_field'];
                }else{
                    unset($fields[$key]);
                }
            }else{
                unset($fields[$key]);
            }
        }
        //print_r_pre($fields);
        $usersVars = array();
        if ($isUidKey) {
            $users = array_keys($users);
        }
        foreach ($users as $uid) {
            $user = User::getInfoFull($uid);
            foreach ($fields as $key => $field) {
                if (isset($user[$key]) && $user[$key]) {
                    if (!isset($usersVars[$key])) {
                        $usersVars[$key] = array();
                    }
                    $usersVars[$key][] = $user[$key];
                } elseif($field['type_field'] == 'checkbox'
                            && isset($field['id'])
                            && isset($user['checkbox'][$field['id']])) {
                    if (!isset($usersVars[$key])) {
                        $usersVars[$key] = array();
                    }
                    $usersVars[$key] = array_merge($usersVars[$key], $user['checkbox'][$field['id']]);
                }
            }
        }
        //print_r_pre($usersVars);
        $matchesVars = array();
        $max = array();
        foreach ($usersVars as $key => $vars) {
            $result = array_count_values($vars);
            foreach ($result as $k => $count) {
                if (!isset($max[$key])) {
                    $max[$key] = 1;
                }
                //if ($fields[$key]['type_field'] == 'radio') {
                    //echo $key . '_' . $k . '_' . $count . '<br>';
                //}
                if ($count > $max[$key]) {
                    if (!isset($matchesVars[$key])) {
                        $matchesVars[$key] = array('type_field' => $fields[$key]['type_field'],
                                                   'value' => array());
                    }
                    if ($fields[$key]['type_field'] == 'checks' || $fields[$key]['type_field'] == 'radio') {
                        $matchesVars[$key]['value'][0] = $k;
                        $max[$key] = $count;
                    } else {
                        $matchesVars[$key]['value'][] = $k;
                    }
                }
            }
        }
        //print_r_pre($matchesVars);
        $i = 1;
        foreach ($matchesVars as $key => $data) {
            if ($i > $countParams) {
                break;
            }
            $userFilters = array();
            if ($data['type_field'] == 'radio') {
                if (isset($data['value'][0]) && !$isSetIamHereTo) {
                    User::update(array('i_am_here_to' => $data['value'][0]));
                }
            } elseif ($data['type_field'] == 'checks') {
                $vars = array();
                if (isset($data['value'][0])) {
                    $userFilters['p_' . $key . '_from'] = array(
                                'field' => $key,
                                'values' => array('p_' . $key . '_from' => $data['value'][0],
                                                  'p_' . $key . '_to' => 0),
                                );
                }
            } else if ($data['value']){
                $keyParam = $key;
                if ($data['type_field'] == 'selection') {
                    $keyParam = 'p_' . $key;
                }
                $userFilters[$keyParam] = array(
                    'field' => $key,
                    'value' => $data['value'],
                );
            }
            if ($userFilters) {
                $userSearchFilters = array_merge($userSearchFilters, $userFilters);
                $i++;
            }
        }
        return $userSearchFilters;
    }

    static function blockFull($uid = null, $deleteVisitors = true)
    {
        $guid = guid();
        if ($uid === null) {
            $uid = get_param('user_id', 0);
        }
        $responseData = false;
        if ($guid && $uid) {
            CIm::closeIm($uid);//error checking
            User::blockAll($guid, $uid);
            User::friendDelete($guid, $uid);
            User::actionFavorite($uid, true);
            //MutualAttractions::unlike($uid);
            MutualAttractions::remove($uid);
            CStatsTools::count('user_blocks');

            $where = '`user_from` = ' . to_sql($uid, 'Number') . ' AND `user_to` = ' . to_sql($guid, 'Number');
            if ($deleteVisitors) {
                DB::delete('users_view', $where);
            }
            DB::delete('users_interest', $where);
            $responseData = true;
        }
        return $responseData;
    }


	static function isAccountBan()
	{
		if(guser('ban_global')==1){
			User::logout();
            redirect('/');
		}
	}

    static function getLocationFiltersMobile($uid = null, $filters = null, $byCountry = false)
    {
        if ($uid === null) {
            $uid = guid();
        }
        $locationFilter = '';
        if (!$uid) {
            return '';
        }
        if ($filters === null) {
            $userInfo = User::getInfoFull($uid);
            $filters = $userInfo['user_search_filters_mobile'];
        }
        if ($filters) {
            $filters = json_decode($filters, true);
            if ($filters['city']['value'] && $byCountry === false){
                $locationFilter = l(Common::getLocationTitle('city', $filters['city']['value']));
            } else if ($filters['state']['value'] && $byCountry === false){
                $locationFilter = l(Common::getLocationTitle('state', $filters['state']['value']));
            } else if ($filters['country']['value']){
                $locationFilter = l(Common::getLocationTitle('country', $filters['country']['value']));
            } else {
                $locationFilter = l('all_countries');
            }
        } else {
            $locationFilter = l(UsersFilter::getLocationTitleDb());
        }
        return $locationFilter;
    }

    // Mobile Urbana is not used
    static function getNumberNewEventsProfile()
    {
        $uid = guid();
        if (!$uid) {
            return false;
        }
        $numberNewMsg = CIm::getCountNewMessages(null, get_param('user_id'));
        $numberNewMutual = MutualAttractions::getNumberMutualAttractions('mutual',true);
        $numberNewWanted = MutualAttractions::getNumberMutualAttractions('wanted',true);
        $numberNewUsersView = DB::count('users_view', "`new` = 'Y' AND `user_to` = " . to_sql($uid));

        return  $numberNewMsg + $numberNewMutual + $numberNewWanted + $numberNewUsersView;
    }

    static function isBlockedMeSetvar(&$html, $uid)
    {
        $guid = guid();

        $varUserBlockedMe = 'var_is_blocked_me';
        if ($html->varExists($varUserBlockedMe)) {
            $valueUserBlockedMe = $guid ? 0 : 1; //If you are not logged always blocked
            if ($guid && $guid != $uid) {
                $valueUserBlockedMe = User::isEntryBlocked($uid, $guid);
            }
            $html->setvar($varUserBlockedMe, $valueUserBlockedMe);
        }
    }

    static function accessCheckToProfile($alwaysСheck = false)
    {
        global $p;
        $guid = guid();

        if (Common::isOptionActive('access_check_to_profile', 'template_options')) {
            $isMobile = Common::isMobile();
            $groupId = Groups::getParamId();
            $paramUid = 'uid';
            if ($isMobile) {
                $paramUid = 'user_id';
            }
            $uid = intval(User::getRequestUserId($paramUid));
            $display = get_param('display');
            $isAllowConditions = $display == 'profile';
            if ($isMobile) {
                $isAllowConditions = $uid;
            }
            if ($alwaysСheck && User::getParamUid(0)) {//EDGE
                $isAllowConditions = true;
            }
            if ($uid != $guid && $isAllowConditions) {
                $urlProfile = 'profile_view.php';
                if (Common::isOptionActiveTemplate('redirect_from_profile_to_home_page')) {
                    $urlProfile = Common::getHomePage();
                }
                $redirectPageList = array('user_does_not_exist' => $urlProfile,
                                          'you_are_in_block_list' => 'user_block_list.php',
                                          'this_user_has_blocked_you' => $urlProfile,
                                          'this_page_has_blocked_you' => $urlProfile,
                                          'this_group_has_blocked_you' => $urlProfile,
                                          'profile_is_hidden' => $urlProfile,
                                          'user_in_admin_block_list' => $urlProfile,
                );
                if ($isMobile) {
                    $redirectPageList['you_are_in_block_list'] = "profile_view.php?user_id={$uid}";
                }
                $key = '';
                $setSession = true;
                if (Common::isOptionActive('users_ban_hide_in_the_search_results') && User::getInfoBasic($uid, 'ban_global')) {
                    $key = 'user_in_admin_block_list';
                } elseif (!User::isExistsByUid($uid)) {
                    $key = 'user_does_not_exist';
                } elseif (Common::isOptionActive('redirect_user_blocked', 'template_options') && User::isEntryBlocked($guid, $uid)) {
                    //Urban, Impact mobile, Urban mobile
                    $key = 'you_are_in_block_list';
                    $setSession = false;
                    if ($isMobile && $display != 'profile_info') {
                        $key = '';
                    }
                } elseif ((!$groupId && User::isEntryBlocked($uid, $guid))||
                            ($groupId && Groups::isEntryBlocked($groupId, $guid)) ) {
                    if ($groupId) {
                        $key = Groups::isPage() ? 'this_page_has_blocked_you' : 'this_group_has_blocked_you';
                    } else {
                        $key = 'this_user_has_blocked_you';
                    }
                } elseif (!$groupId && User::getInfoBasic($uid, 'set_hide_my_presence') == 1 && User::isSettingEnabled('set_hide_my_presence')) {
                    $key = 'profile_is_hidden';
                    if (User::isFriend($guid, $uid)) {
                        $key = '';
                    }
                }
                if ($key) {
                    if ($setSession) {
                        set_session('error_accessing_user', $key);
                    }
                    //echo $key;
                    //echo $redirectPageList[$key];
                    //exit();
                    if (!Moderator::isAllowedViewingUsers($key)) {
                       redirect($redirectPageList[$key]);
                    }

                }
            }
        }
    }

    static function sendReport($pid = null)
    {
        if ($pid === null) {
            $pid = get_param('photo_id');
        }
        $groupId = Groups::getParamId();
        if ($pid) {
            if(strpos($pid, 'v_') !== false) {
                return self::sendReportVideo(str_replace('v_', '', $pid));
            } else {
                return self::sendReportPhoto($pid);
            }
        } elseif ($groupId) {
            return Groups::sendReport();

        } else {
            return self::sendReportUser();
        }
    }

    static function sendReportUser()
    {
        $uid = guid();
        $userTo = get_param('user_to');
        $responseData = false;
        if ($uid && $userTo) {
            $usersToReport = User::getInfoBasic($userTo, 'users_reports');
            $updateUsersToReport = $uid;
            if ($usersToReport) {
                $updateUsersToReport = $usersToReport;
                $listUsersToReport = explode(',', $usersToReport);
                if (!in_array($uid, $listUsersToReport)) {
                    $updateUsersToReport = $usersToReport . ',' . $uid;
                }
            }
            if ($usersToReport != $updateUsersToReport) {
                $data = array('user_from' => $uid,
                              'user_to' => $userTo,
                              'msg' => get_param('msg'),
                              'photo_id' => 0);
                DB::insert('users_reports', $data);
                User::update(array('users_reports' => $updateUsersToReport), $userTo);
            }
            $responseData = true;
        }
        return $responseData;
    }

    static function addCreditsByUserId($userId,$credits)
    {
        $sql='UPDATE `user` SET `credits`=`credits`+'.to_sql($credits, 'Number').
            ' WHERE `user_id`='.to_sql($userId, 'Number');
        DB::execute($sql);
    }

    static function sendReportPhoto($pid = null)
    {
        $uid = guid();
        if ($pid === null) {
            $pid = get_param('photo_id');
        }
        $userTo = get_param('user_to');
        $responseData = false;
        if ($uid && $userTo) {
            $where = '`photo_id` = ' . to_sql($pid);
            $photoInfo = DB::one('photo',  $where);
            $groupId = $photoInfo['group_id'];
            $usersToReport = $photoInfo['users_reports'];
            $updateUsersToReport = $uid;
            if ($usersToReport && isset($usersToReport[0]) && $usersToReport[0]) {
                $usersToReport = $usersToReport[0];
                $updateUsersToReport = $usersToReport;
                $listUsersToReport = explode(',', $usersToReport);
                if (!in_array($uid, $listUsersToReport)) {
                    $updateUsersToReport = $usersToReport . ',' . $uid;
                }
            }
            if ($usersToReport != $updateUsersToReport) {
                $data = array('user_from' => $uid,
                              'user_to' => $userTo,
                              'msg' => get_param('msg'),
                              'photo_id' => get_param('photo_id'),
                              'group_id' => $groupId);
                DB::insert('users_reports', $data);
                DB::update('photo', array('users_reports' => $updateUsersToReport), $where);
            }
            $responseData = true;
        }
        return $responseData;
    }

    static function sendReportVideo($pid = null)
    {
        $uid = guid();
        if ($pid === null) {
            $pid = get_param('video_id');
        }
        $userTo = get_param('user_to');
        $responseData = false;
        if ($uid && $userTo) {
            $where = '`id` = ' . to_sql($pid);
            $videoInfo = DB::one('vids_video', $where);
            $groupId = $videoInfo['group_id'];
            $usersToReport = $videoInfo['users_reports'];
            $updateUsersToReport = $uid;
            if ($usersToReport && isset($usersToReport[0]) && $usersToReport[0]) {
                $usersToReport = $usersToReport[0];
                $updateUsersToReport = $usersToReport;
                $listUsersToReport = explode(',', $usersToReport);
                if (!in_array($uid, $listUsersToReport)) {
                    $updateUsersToReport = $usersToReport . ',' . $uid;
                }
            }
            if ($usersToReport != $updateUsersToReport) {
                $data = array('user_from' => $uid,
                              'user_to' => $userTo,
                              'msg' => get_param('msg'),
                              'video' => 1,
                              'photo_id' => $pid,
                              'group_id' => $groupId);
                DB::insert('users_reports', $data);
                DB::update('vids_video', array('users_reports' => $updateUsersToReport), $where);
            }
            $responseData = true;
        }
        return $responseData;
    }

    static function isDemoUser($uid)
    {
        $lastIp = User::getInfoBasic($uid, 'last_ip');
        if (in_array($lastIp, array('127.0.0.1', '::1'))){
            return true;
        }
        return false;
    }

    static function getActiveProfileTabsAlias()
    {
        $defaultProfileTab = Common::getOption('set_default_profile_tab', 'edge');
        $profileTabUrl = false;
        if ($defaultProfileTab == 'menu_inner_videos_edge') {
            $profileTabUrl = 'user_vids_list';
        } elseif ($defaultProfileTab == 'menu_inner_photos_edge') {
            $profileTabUrl = 'user_photos_list';
        } elseif ($defaultProfileTab == 'menu_inner_pages_edge') {
            $profileTabUrl = 'user_pages_list';
        } elseif ($defaultProfileTab == 'menu_inner_groups_edge') {
            $profileTabUrl = 'user_groups_list';
        } elseif ($defaultProfileTab == 'menu_inner_friends_edge') {
            $profileTabUrl = 'user_friends_list';
        } elseif ($defaultProfileTab == 'menu_inner_blogs_edge') {
            $profileTabUrl = 'user_blogs_list';
        } elseif ($defaultProfileTab == 'menu_inner_songs_edge') {
            $profileTabUrl = 'user_songs_list';
        }
        return $profileTabUrl;
    }

    static function url($uid, $userinfo = null, $params = null, $isCache = true, $default = false)
    {
        $paramsAddSymbol = Common::isOptionActive('seo_friendly_urls') ? '?' : '&';
        $optionTmplName = Common::getTmplName();
        if ($optionTmplName == 'edge' && !$default) {
            $profileTabUrl = self::getActiveProfileTabsAlias();
            if ($profileTabUrl) {
                $key = 'user_seo_friendly_url_profile_tab_' . $uid;
                $url = null;
                if ($isCache) {
                    $url = Cache::get($key);
                }
                if ($url === null) {
                    $url = Common::pageUrl($profileTabUrl, $uid);
                    Cache::add($key, $url);
                }
                $url .= $params ? $paramsAddSymbol . http_build_query($params) : '';
                return $url;
            }
        }

        if (Common::isOptionActive('seo_friendly_urls')) {
            $key = 'user_seo_friendly_url_' . $uid;
            $url = null;
            if ($isCache) {
                $url = Cache::get($key);
            }
            if ($url === null) {
                if ($userinfo === null || !isset($userinfo['name_seo'])) {
                    $userinfo = self::getInfoBasic($uid, false, DB_MAX_INDEX);
                }
                //$name = mb_strtolower(str_replace(array(' ', '?'), '_', $userinfo['name']), 'utf-8');
                $name = Router::prepareNameSeo($userinfo['name']);
                $nameSeo = '';
                if ($userinfo['name_seo']) {
                    $nameSeo = explode('-', $userinfo['name_seo']);
                    $nameSeo = $nameSeo[0];
                }
                if ($name != $nameSeo) {
                    $name = Router::getNameSeo($userinfo['name'], $uid, 'user');
                    /*$sql = 'SELECT user_id FROM user WHERE name_seo = ' . to_sql($name) . ' AND user_id != ' . to_sql($uid);
                    $isNameExists = DB::result($sql, 0, DB_MAX_INDEX);
                    if ($isNameExists) {
                        $name .= '-' . $uid;
                    }*/
                    User::update(array('name_seo' => $name), $uid);
                }
                $url = $name;
                Cache::add($key, $url);
            }
        } else {
            $url = 'search_results.php?display=profile&uid=' . $uid;
        }

        $url .= $params ? $paramsAddSymbol . http_build_query($params) : '';

        return $url;
    }

    static function photoApproval($pid, $access = '')
    {
        global $g_user;

        DB::execute("UPDATE photo SET visible='Y' WHERE photo_id=" . to_sql($pid, 'Number'));
		DB::query("SELECT * FROM photo WHERE photo_id=" . to_sql($pid, 'Number'), 2);

        $uid = 0;
		if ($row = DB::fetch_row(2)) {
            $uid = $row['user_id'];
            Moderator::prepareNotificationInfo($row['user_id'], $row);
            $g_user['user_id'] = $row['user_id'];
			DB::execute("UPDATE user SET is_photo='Y' WHERE user_id=" . $row['user_id'] . "");
            User::setAvailabilityPublicPhoto($row['user_id']);
		}
        $wallId = DB::result('SELECT `wall_id` FROM `photo` WHERE photo_id = ' . to_sql($pid, 'Number'));
        if ($wallId) {
            DB::update('wall', array('params' => 1), '`id` = ' . to_sql($wallId));
        }
        if ($access == 'access') {
            CProfilePhoto::setPhotoPrivate($pid);
        /* Fix set photo default public */
        } elseif ($uid) {
            if(!User::getPhotoDefault($uid, '', true)
                || !CProfilePhoto::isPhotoDefaultPublic($uid, true)){
                User::photoToDefault($pid);
            }
        }
        /* Fix set photo default public */
    }

    static function getParamUid($default = null, $param = 'uid')
    {
        if ($default === null) {
            $default = guid();
        }
        $key = 'User_getParamUid_' . $param . '_' . intval($default);
        $uid = Cache::get($key);
        if($uid !== null) {
            return $uid;
        }

        $uidParam = strval(get_param($param));
        $uid = intval($uidParam);

        if (!$uid || $uidParam !== strval($uid)){
            $uid = 0;
        }

        if (!$uid) {
            $nameSeo = get_param('name_seo');
            if ($nameSeo) {
                $uid = self::getUidFromNameSeo($nameSeo);
            } else {
                $uid = $default;
            }
		}
        Cache::add($key, $uid);

        return $uid;
    }

    static function getUidFromNameSeo($nameSeo)
    {
        $key = 'user_id_from_name_seo_' . $nameSeo;
        $uid = Cache::get($key);
        if($uid === null) {
            $sql = 'SELECT `user_id` FROM `user` WHERE `name_seo` = ' . to_sql($nameSeo);
            $uid = DB::result($sql, 0, DB_MAX_INDEX);
            Cache::add($key, $uid);
        }

        return $uid;
    }

    static function isHiddenSql($table = 'u.')
    {
        $sqlSetWhoViewProfile = '';
        $delimiter = '';
        if (User::isSettingEnabled('set_who_view_profile') && !guid()) {
            $sqlSetWhoViewProfile = $table . '`set_who_view_profile` = "anyone"';
            $delimiter = ' AND ';
        }
        $sql = $sqlSetWhoViewProfile;
        if (Common::getOption('set', 'template_options') == 'urban') {
            $isAllowedHideMyPresence = User::isSettingEnabled('set_hide_my_presence');
            if ($isAllowedHideMyPresence) {
                $sql .= $delimiter . "({$table}set_hide_my_presence = 2 OR ({$table}set_hide_my_presence = 1 AND {$table}user_id=" . guid() . "))";
            } elseif (!$sql) {
                $sql = ' 1 ';
            }
        } else {
            $sql .= $delimiter . "({$table}hide_time = 0 OR ({$table}hide_time != 0 AND {$table}user_id=" . guid() . "))";
        }
        return $sql;
    }

    static function getRatePhotoWhereOnBigBase($where, $fromAdd, $user, $whereLocation, $order)
    {
        $order =  str_replace('u.', '', $order);
        $orderTypes = explode(',', $order);

        $sqls = array();
        $countForSearch = 1000;

        $whereEncountersAddons = array();
        $whereEncountersAddonsEmpty = true;

        foreach($orderTypes as $orderType) {
            if(strpos(trim($orderType), 'i_am_here_to') === 0) {
                $orderType = str_replace('DESC', '', $orderType);
                $whereEncountersAddons[] = $orderType;
                $whereEncountersAddons[] = str_replace('=', '!=', $orderType);
                $whereEncountersAddonsEmpty = false;
                break;
            } elseif (trim($orderType) == 'RAND()') {
                continue;
            }
        }

        if($whereEncountersAddonsEmpty) {
            $whereEncountersAddons[] = 1;
        }

        foreach($whereEncountersAddons as $whereEncountersAddon) {

            $sqls[] = '(SELECT u.user_id, u.i_am_here_to, up.votes
                FROM user AS u ' . $fromAdd . '
                WHERE ' . $where . ' ' . $whereLocation . ' AND ' . $whereEncountersAddon . '
                GROUP BY u.user_id
                ORDER BY u.user_id DESC
                LIMIT ' . $countForSearch . ')';

            /*$sqls[] = '(SELECT u.user_id, u.i_am_here_to, up.votes
                FROM user AS u ' . $fromAdd . '
                WHERE ' . $where . ' ' . $whereLocation . ' AND ' . $whereEncountersAddon . '
                ORDER BY up.photo_id DESC
                LIMIT ' . $countForSearch . ')';*/
        }

        $sql = implode(" UNION ", $sqls) . ' ORDER BY ' . $order . ' LIMIT 1';

        $result = DB::row($sql);

        $whereUid = isset($result['user_id']) ? $result['user_id'] : 0;

        // OLD CODE

//        $whereRate = $where;
//        $whereIAmHereTo = false;
//
//        if($user['i_am_here_to']) {
//            $whereRate .= ' AND i_am_here_to = ' . to_sql($user['i_am_here_to'], 'Number') . ' ';
//            $whereIAmHereTo = true;
//        }
//
//        $sql = 'SELECT DISTINCT u.user_id
//            FROM user AS u ' . $fromAdd . '
//            WHERE ' . $whereRate . ' ' . $whereLocation . '
//            ORDER BY RAND() LIMIT 1';
//
//        // For extra large base no ORDER BY RAND():
//        $limit = 100;
//
//        // it will show always old profiles only
//        $sql = 'SELECT u.user_id
//            FROM user AS u ' . $fromAdd . '
//            WHERE ' . $whereRate . ' ' . $whereLocation . '
//            GROUP BY u.user_id
//            LIMIT ' . $limit;
//
//        //echo $sql;
//
//        // add union for different sorts
//
//
//        $users = DB::rows($sql);
//
//        if(!$users && $whereIAmHereTo) {
//            $sql = 'SELECT DISTINCT u.user_id
//                FROM user AS u ' . $fromAdd . '
//                WHERE ' . $where . ' ' . $whereLocation . '
//                LIMIT 1000';
//            $users = DB::rows($sql);
//            shuffle($users);
//        }
//
//        $whereUid = isset($users[0]['user_id']) ? $users[0]['user_id'] : 0;



        $where = ' u.user_id = ' . $whereUid . '
            AND upr.photo_id IS NULL';

        return $where;
    }

    static function isSearchNearMe($uid = null)
    {
        if ($uid === null) {
            $uid = guid();
        }
        $key = 'search_near_me_' . $uid;
        $is = Cache::get($key);
        if ($is === null) {
            $nearMeRadius = Common::getOption('near_me_radius', 'template_options');
            $radius = intval(Common::getOption('default_search_distance'));
            if ($uid) {
                $userInfo = User::getInfoFull($uid, DB_MAX_INDEX, true);
                $filter = User::getParamsFilter('user_search_filters', $userInfo['user_search_filters']);
                $filter = json_decode($filter, true);
                if (isset($filter['radius'])) {
                    $radius = intval($filter['radius']['value']);
                }
            }
            $is = $radius <= $nearMeRadius;
            Cache::add($key, $is);
        }
        return $is;
    }


    static function getTitleSearchNearMe($uid = null)
    {
        if ($uid === null) {
            $uid = guid();
        }
        $key = 'search_near_me_title_' . $uid;
        $title = Cache::get($key);
        if ($title === null) {
            $title = l('distance_does_not_matter');
            if (self::isSearchNearMe($uid)) {
                $title = l('near_me');
            }
        }
        return $title;
    }


    static function getValueFilterField($field, $default = null, $uid = null,  $isCache = true)
    {
        if ($uid === null) {
            $uid = guid();
        }
        $fieldValue = null;
        $key = 'user_filter_field_' . $field . '_' . $uid;
        if ($isCache) {
            $fieldValue = Cache::get($key);
        }
        if ($fieldValue === null) {
            $userInfo = User::getInfoFull($uid, DB_MAX_INDEX, true);
            $filter = json_decode($userInfo['user_search_filters'], true);
            $fieldValue = $default;

            if (isset($filter[$field])) {
                $fieldValue = $filter[$field]['value'];
            }
            Cache::add($key, $fieldValue);
        }
        return $fieldValue;
    }

    static function autologinByParam()
    {
        if(get_session('user_id') == 0) {
            $param = get_param('email_auth_key');
            if($param) {
                $paramParts = explode('_', trim($param));
                if(count($paramParts) == 2) {
                    $uid = $paramParts[0];
                    $authKey = trim($paramParts[1]);

                    if($uid && $authKey) {
                        $whereManualApproval = '';
                        if(Common::isOptionActive('manual_user_approval')) {
                            $whereManualApproval = ' AND `active` = 1';
                        }
                        $sql = 'SELECT user_id FROM user WHERE user_id = ' . to_sql($uid) . '
                            AND auth_key = ' . to_sql($authKey) . $whereManualApproval;
                        $uid = DB::result($sql);
                        if($uid) {
                            set_session('user_id', $uid);
                            set_session('user_id_verify', $uid);
                        }
                    }
                }
            }
        }
    }

    static function urlAddAutologin($url, $user)
    {
        if(isset($user['user_id']) && isset($user['auth_key'])) {
            $addParam = '?';
            if(strpos($url, '?')) {
                $addParam = '&';
            }
            $url .= $addParam . 'email_auth_key=' . $user['user_id'] . '_' . $user['auth_key'];
        }
        return $url;
    }

    static function isReportUser($uid, $row = null)
    {
        $guid = guid();
        if($guid == $uid) {
            return 0;
        }

        if ($row !== null && isset($row['users_reports'])) {
            $is = in_array($guid, explode(',', $row['users_reports']));
            return intval($is);
        }

        $whereReport = '`user_from` = ' . to_sql($guid)
                     . ' AND `user_to` = ' . to_sql($uid) . ' AND `photo_id` = 0';
        return DB::count('users_reports', $whereReport, '', '', '', DB_MAX_INDEX, true);
    }

    static function setUserVisitor($uidFrom, $uidTo)
    {
        return false;
        if ($uidTo != $uidFrom) {
            $rows = array('last_visit' => date('Y-m-d H:i:s'));
            $where = '`user_from` = ' . to_sql($uidFrom) . ' AND `user_to` = ' . to_sql($uidTo);
            if (DB::count('users_visitors', $where)) {
                DB::update('users_visitors', $rows, $where);
            } else {
                $rows['user_from'] = $uidFrom;
                $rows['user_to'] = $uidTo;
                DB::insert('users_visitors', $rows);
            }
        }
    }

    static function getUsersVisitors()
    {
        $where = '`user_to` = ' . to_sql(guid());// .
                 //' AND `last_visit` > ' . to_sql((date('Y-m-d H:i:s', time() - self::$onlineTimeBroadcast)));
        return DB::field('users_visitors', 'user_from', $where);
    }

    static function setLastBroadcast($date = null)
    {
        if (!guid()) {
            return false;
        }
        if ($date === null) {
            $date = date('Y-m-d H:i:s');
        }
        $rows = array('last_broadcast' => $date);
        DB::update('user', $rows, '`user_id` = ' . to_sql(guid()));
        return true;
    }

    static function isUserBroadcast($uid)
    {
        if ($uid == guid()) {
            return 0;
        }
        $where = '`user_id` = ' . to_sql($uid) .
                 ' AND `last_broadcast` > ' . to_sql((date('Y-m-d H:i:s', time() - self::$onlineTimeBroadcast)));
        return DB::count('user', $where);
    }

    static function parseItemBasicList(&$html, $row, $parseChart = false)
    {
        $html->setvar('item_id', $row['id']);
        $html->setvar('photo_m', User::getPhotoDefault($row['user_id'], 'm', false, $row['gender']));
        $html->setvar('user_profile_link', User::url($row['user_id']));
        $html->setvar('name_one_letter_short', User::nameOneLetterShort($row['name']));
        $html->setvar('age', $row['age']);
        $html->setvar('city', l($row['city']));
        $html->setvar('user_gender', $row['gender'] == 'M' ? l('man') : l('woman'));

        if ($html->varExists('user_orientation')) {
            $orientationTitle = '';

            $orientationInfo = self::getOrientationInfo($row['orientation']);
            if(isset($orientationInfo['title'])) {
                $orientationTitle = l($orientationInfo['title']);
            }
            $html->setvar('user_orientation', $orientationTitle);
        }

        if ($parseChart) {
            User::parseCharts($html, $row['user_id'], 'list');
        }
    }

    static function parseCharts(&$html, $uid, $typeBlock = 'profile')//Impact
    {
        global $g;
        if (!$html->blockExists('chart_item')) {
            return false;
        }

        $guid = guid();
        if (!$guid) {
            return false;
        }
        $graphicsItems = array(1 => array(), 2 => array(), 3 => array());
        foreach ($g['user_var'] as $key => $field) {
            $data = UserFields::checkFiledQuestion($key, false, true);
            if ($data) {
                if (isset($field['chart']) && $field['chart']) {
                    $graphicsItems[$field['chart']][] = $data;
                }
            }
        }
        $guserInfo = self::getInfoFull($guid, DB_MAX_INDEX);
        $userInfo = self::getInfoFull($uid, DB_MAX_INDEX);

        $block = 'chart_item';
        $html->clean($block);
        $minMatchPercent = intval(Common::getOption('minimum_match_percent_on_graphs'));
        if ($minMatchPercent > 100) {
            $minMatchPercent = 100;
        }
        $graphics = array(1 => 'physics', 2 => 'intellect', 3 => 'hobbies');
        $chartsRandomValue = null;
        foreach ($graphicsItems as $key => $items) {
            $percent = null;
            if ($items) {
                $count = 0;
                $matches = 0;
                foreach ($items as $k => $field) {

                    $userValues = array();
                    $guserValues = array();
                    $type = $field['type_field'];
                    $name = $field['name'];
                    if ($type == 'checkbox') {
                        if (isset($guserInfo['checkbox'][$field['id']])) {
                            $guserValues = $guserInfo['checkbox'][$field['id']];
                        }
                        if (isset($userInfo['checkbox'][$field['id']])) {
                            $userValues = $userInfo['checkbox'][$field['id']];
                        }
                    } elseif ($type == 'selection' || $type == 'checks') {
                        if ($name == 'star_sign') {
                            $guserValues = array($guserInfo['horoscope']);
                            $userValues = array($userInfo['horoscope']);
                        } else {
                            if (isset($guserInfo[$name]) && $guserInfo[$name]) {
                                $guserValues = array($guserInfo[$name]);
                            }
                            if(isset($userInfo[$name]) && $userInfo[$name]) {
                                $userValues = array($userInfo[$name]);
                            }
                        }
                    }
                    if ($userValues && $guserValues) {
                        $matches += count(array_intersect($userValues, $guserValues));
                        $count += count($guserValues);
                    }
                }
                if ($matches) {
                    $percentMatches = round($matches * 100/$count);
                    if ($minMatchPercent && $minMatchPercent > $percentMatches) {
                        if ($minMatchPercent < 100) {
                            $delta = 100 - $minMatchPercent;
                            $percent = $minMatchPercent + intval($percentMatches*(100 - $minMatchPercent)/100);
                        } else {
                            $percent = 100;
                        }
                    } else {
                        $percent = $percentMatches;
                    }
                    //echo 'Math Uid:' . $uid . ' - совпало% ' .  $percentMatches . ' - c учётом мини знач: ' . $percent . '<br>';
                }
            }

            $where =  '`user_from` = ' . to_sql($guid) .
                      ' AND `user_to` = ' . to_sql($uid);

            //var_dump_pre($percent);
            //break;
            if ($percent === null) {
                if (!$minMatchPercent) {
                    $percent = 0;
                } else {
                    if ($chartsRandomValue === null) {
                        $chartsRandomValue = array();
                        $chartsRandom = DB::select('user_chart_random_value', $where, '', '', '`chart`, `value`');
                        foreach ($chartsRandom as $row) {
                            $chartsRandomValue[$row['chart']] = $row['value'];
                        }
                        //print_r_pre($chartsRandomValue);
                    }

                    if ($chartsRandomValue && isset($chartsRandomValue[$key])) {
                        $percent = $chartsRandomValue[$key];
                    } else {
                        $percent = $minMatchPercent;
                        if ($minMatchPercent < 100) {
                            $delta = intval((100 - $minMatchPercent)/3);
                            if ($delta > 5) {
                                $deltaMax = $minMatchPercent + $delta;
                                $percent = mt_rand($minMatchPercent, $minMatchPercent + $delta) + mt_rand(1, $delta) + mt_rand(1, $delta);
                            } else {
                                $percent = mt_rand($minMatchPercent, 100);
                            }
                        }
                        $data = array('user_from' => $guid, 'user_to' => $uid, 'chart' => $key, 'value' => $percent);
                        DB::insert('user_chart_random_value', $data);
                    }
                }
            } else {
                $where .= ' AND `chart` = ' . to_sql($key);
                DB::delete('user_chart_random_value', $where);
            }
            if ($key == 3) {
                $html->parse("{$block}_last", false);
            } else {
                $html->clean("{$block}_last");
            }
            $html->setvar("{$block}_num", $key);
            $sizeChartsTemplate = Common::getOption('profile_visitor_charts_size', 'template_options');
            if (is_array($sizeChartsTemplate)) {
                $lTitle = 'chart_title_short';
                $size = $sizeChartsTemplate[$key];
            } else {
                $size = 146;
                $lTitle = 'chart_title';
                if ($typeBlock == 'list') {
                    $size = 103;
                    $lTitle = 'chart_title_short';
                }
            }
            $html->setvar("{$block}_w", $size);
            $html->setvar("{$block}_title", lSetVars($lTitle, array('name' => l('chart_' . $graphics[$key]))));
            $html->setvar("{$block}_description", lSetVars('chart_description', array('percent' => $percent)));
            if ($percent > 100) {
                $percent = 100;
            }
            $html->setvar("{$block}_pr", $percent);
            $html->setvar("{$block}_name", $graphics[$key]);
            $html->setvar("{$block}_id", $graphics[$key] . $uid);
            $html->parse($block, true);
        }
    }

    static function parseRefererBackUrl(&$html, $uid, $block = 'go_referer_back')
    {
        $guid = guid();
        if (!$html->blockExists($block)) {// || $uid == $guid
            return;
        }
        $ref = get_param('ref');
        $referes = array('people_nearby', 'encounters', 'rate_people', 'wall',
                         'one_chat');
        if (in_array($ref, $referes)) {
            $paramsLink = array();
            $backUrl = Common::pageUrl('login');
            if ($guid) {
                $param = '';
                $setTab = '';
                $refItem = get_param('ref_item');
                $refUid = get_param('ref_uid');
                if ($ref == 'wall') {
                    $backUrl = Common::pageUrl('wall');
                    if ($refUid) {
                        $backUrl = User::url($refUid);
                        $setTab = '#tabs-3';
                    }
                    if ($refItem) {
                        $paramsLink = array('wall_item' => $refItem);
                        $param = 'wall_item=' . $refItem;
                    }
                } elseif ($ref == 'people_nearby') {
                    $offset = intval(get_param('ref_offset'));
                    if ($offset > 1) {
                        $offset = '?offset=' . $offset;
                    } else {
                        $offset = '';
                    }
                    $backUrl = Common::pageUrl('search_results') . $offset;
                    $paramsLink = array('back' => 1);
                    $param = 'back=1';
                } elseif ($ref == 'encounters') {
                    $backUrl = Common::pageUrl('encounters');
                    $paramsLink = array('uid' => $uid);
                    $param = 'uid=' . $uid;
                } elseif ($ref == 'rate_people') {
                    $backUrl = Common::pageUrl('rate_people');
                    $paramsLink = array('uid' => $uid);
                    $param = 'uid=' . $uid;
                } elseif ($ref == 'one_chat') {
                    $backUrl = Common::pageUrl('messages') . '?display=one_chat&user_id=' . $uid;
                }
                if ($param && !Common::isOptionActive('seo_friendly_urls')) {
                    $pagerUrlDelimiter = '?';
                    if (mb_strpos($backUrl, '?', 0, 'UTF-8') !== false) {
                        $pagerUrlDelimiter = '&';
                }
                    $backUrl .= $pagerUrlDelimiter . $param;
                }
                if ($setTab) {
                    $backUrl .= $setTab;
                }
            }
            $html->setvar('url_back_params', http_build_query($paramsLink));
            $html->setvar('url_back', $backUrl);
            $html->parse($block, false);
        }
    }

    static public function getCountBlocked($uid = false, $dbIndex = DB_MAX_INDEX)
    {
        if($uid === false) {
            $uid = guid();
        }

        $sql = 'SELECT COUNT(*)
            FROM `user_block_list`
            WHERE `user_from` = ' . to_sql($uid);
        return DB::result($sql, 0, $dbIndex);
    }

    static public function isVisiblePlugPrivatePhotoFromId($uid, $pid)
    {
        $key = "no_vis_private_photo_{$uid}_{$pid}";
        return Cache::get($key);
    }

    static public function checkAccessToSiteWithMinNumberUploadPhotos($getMsg = false)
    {
        $keyAlert = '';
        $minNumberPhotosToUseSite = Common::getOption('min_number_photos_to_use_site');
        if ($minNumberPhotosToUseSite) {
            $numberPhotos = CProfilePhoto::getNumberPhotos();
            if ($minNumberPhotosToUseSite > $numberPhotos['Y']) {
                $keyAlert = 'site_available_after_uploading_photos';
                if ($minNumberPhotosToUseSite <= $numberPhotos['all']) {
                    $keyAlert = 'photos_are_approved_by_the_administrator';
                }
            }
        }
        if ($getMsg && $keyAlert) {
            $keyAlert = lSetVars($keyAlert, array('param' => $minNumberPhotosToUseSite), 'toJsL');
        }
        return $keyAlert;
    }

    static public function isInvisibleModeOptionActive($option)
    {
        $result = false;
        if (User::isSettingEnabled($option) && self::isAllowedInvisibleMode() && self::isOptionSettings($option)) {
            $result = true;
        }
        return $result;
    }

    static public function isAllowedInvisibleMode($default = null)
    {
        $isAllowedInvisibleMode = true;
        if (!Common::isOptionActive('free_site') && Common::isActiveFeatureSuperPowers('invisible_mode')) {
            $isAllowedInvisibleMode = $default === null ? User::isSuperPowers() : $default;
        }
        return $isAllowedInvisibleMode;
    }

    static public function resetOptionsInvisibleMode()
    {
        $where = '(`set_hide_my_presence` = 1 OR `set_do_not_show_me_visitors` = 1) AND `gold_days` = 0';
        $orientations = DB::rows('SELECT * FROM const_orientation', DB_MAX_INDEX, true);
        if($orientations) {
            $sqlOrientations = array();
            foreach ($orientations as $orientation) {
                if (isset($orientation['free']) && $orientation['free'] != 'none') {
                    $sqlOrientations[] = $orientation['id'];
                }
            }

            if ($sqlOrientations) {
                $where .= ' AND `orientation` NOT IN("' . implode('","', $sqlOrientations) . '")';
            }
        }
        $data = array('set_hide_my_presence' => 2, 'set_do_not_show_me_visitors' => 2);
        DB::update('user', $data, $where);
    }

    static public function isYourOrientationSearch()
    {
        return UserFields::isActive('orientation') && Common::isOptionActive('your_orientation');
    }

    static public function noYourOrientationSearch()
    {
        return UserFields::isActive('orientation') && !Common::isOptionActive('your_orientation');
    }

    static function listUsers($type = 'order_near_me', $limit = 5, $onlyWithPublicPhoto = true)
    {
        global $g;

        $rows = array();
        $where = '';
        $cityInfo = getDemoCapitalCountry();
        $orderBy = '';
        if ($type == 'order_near_me') {
            $sql = Common::sqlUsersNearCity($cityInfo, $limit, $onlyWithPublicPhoto);
            $rows = DB::rows($sql);
            shuffle($rows);
            return $rows;
        } elseif ($type == 'order_new') {
            $where = ' AND register > ' . to_sql(date('Y-m-d H:00:00', (time() - Common::getOptionInt('new_time') * 3600 * 24)), 'Text');
        } elseif ($type == 'order_random') {
            $orderBy = ' RAND(), ';
        } elseif ($type == 'order_online') {
            //$orderBy = ' RAND(), ';
            $time = date('Y-m-d H:i:s', time() - Common::getOptionInt('online_time') * 60);
            $where = ' AND last_visit> ' . to_sql($time, 'Text');
        }
        if ($onlyWithPublicPhoto) {
            $where .= ' AND is_photo_public = "Y" ';
        }

        $sqlHide = User::isHiddenSql('');
        $sql = 'SELECT *, IF(city_id = ' . $cityInfo['city_id'] . ', 1, 0) +
                          IF(state_id = ' . $cityInfo['state_id'] . ', 1, 0) +
                          IF(country_id = ' . $cityInfo['country_id'] . ', 1, 0) AS near,
                        DATE_FORMAT(NOW(), "%Y") - DATE_FORMAT(birth, "%Y") - (DATE_FORMAT(NOW(), "00-%m-%d") < DATE_FORMAT(birth, "00-%m-%d")) AS age
                  FROM `user`
                 WHERE `is_photo` = "Y"
                   AND ' . $sqlHide . '
                   AND `set_who_view_profile` = "anyone"'
                . $where .
               ' ORDER BY' . $orderBy . ' near DESC,  user_id DESC
                 LIMIT ' . $limit;
        $rows = DB::rows($sql);

        return $rows;
    }

    static public function getUserFilterFields($filter = '')
	{
        $fields = array(
            'type_order',
            'tags',
            'only_friends',
            //'search_text'
        );
        if ($filter == 'videos_filters') {
            $fields[] = 'only_live';
        }
        return $fields;
    }

    static public function setUserFilterParam($filter, $typeOrderDefault)
	{
		global $p;

        $guid = guid();
        $filters = array();
        if ($guid) {
            $userinfo = User::getInfoFull($guid);
            $filters = $userinfo[$filter];
            $filters = json_decode($filters, true);
            //$filters = DB::one('userinfo', '`user_id` = ' . to_sql($guid), '', $filter);
            //$filters = json_decode($filters[$filter], true);
            if (is_array($filters)) {
                foreach ($filters as $key => $value) {
                    if ($key == 'tags') {
                        $value = get_param('tags', $value);
                    }
					$setGet = true;
					if ($key == 'only_live' && $p == 'live_list_finished.php') {
						$setGet = false;
					}
					if ($setGet) {
						$_GET[$key] = $value;
					}
                }
            }
        }
        if (!isset($filters['type_order'])) {
            $_GET['type_order'] = $typeOrderDefault;
        }
    }

    static public function updateUserFilter($filter = 'blogs_filters', $fields = null)
	{
        $guid = guid();
        $userinfo = User::getInfoFull($guid);
        $filters = $userinfo[$filter];
        $filters = json_decode($filters, true);
        if (!is_array($filters)) {
            $filters = array();
        }

        if ($fields === null) {
            $fields = self::getUserFilterFields($filter);
        }
        foreach($fields as $field) {
            $fieldValue = get_param($field);
            if (!isset($filters[$field]) || $filters[$field] != $fieldValue) {
                $filters[$field] = $fieldValue;
            }
        }
        if ($filters) {
            $filters = json_encode($filters);
            $userinfo[$filter] = $filters;
            self::getInfoFull($guid, 0, true, $userinfo);
            User::update(array($filter => $filters), null, 'userinfo');
        }
    }

    static public function isUploadPhotoToSeePhotos($uid)
	{
        $guid = guid();
        $isUploadPhotoToSeePhotos = 0;
        if ($guid == $uid) {
            return $isUploadPhotoToSeePhotos;
        }
        if (Common::isOptionActive('forced_profile_picture_upload')) {
            $isUploadPhotoToSeePhotos = 1;
            if ($guid) {
                $where = '`visible`="Y" AND `private` = "N" AND `group_id` = 0 AND `user_id` = ' . to_sql($guid);
                $isUploadPhotoToSeePhotos = !DB::count('photo', $where);
            }
        }
        return $isUploadPhotoToSeePhotos;
    }

    static public function prepareGeoPosition($data)
	{
        $result = array();
        $fields = array('lat', 'long', 'city_id', 'state_id', 'country_id', 'city_title');
        foreach ($fields as $key => $field) {
            if (isset($data[$field])) {
                $keyField = $field;
                if ($field == 'city_title') {
                    $keyField = 'city';
                }
                $result["geo_position_{$keyField}"] = $data[$field];
            }
        }
        return $result;
    }

    static public function getGeoPositionFromCity($cityId)
	{
        $result = array();
        $sql = 'SELECT *
                  FROM `geo_city`
                 WHERE `city_id` = ' . to_sql($cityId, 'Number');
        $geoPosition = DB::row($sql, DB_MAX_INDEX);
        if ($geoPosition) {
            $result = self::prepareGeoPosition($geoPosition);
            $result['geo_position_age'] = '0000-00-00 00:00:00';
        }

        return $result;
    }

    static public function getGeoPosition($cityId = 0)
	{
        $result = false;
        $geoPosition = get_param_array('geo_position');
        if (!IS_DEMO && isset($geoPosition['lat']) && isset($geoPosition['long']) && $geoPosition['lat'] && $geoPosition['long']) {
            $cityInfo = IP::geoInfoCityFindInRadius($geoPosition['lat'], $geoPosition['long']);
            if (!$cityInfo) {
                $cityInfo = array();
            }
            $geoPosition['lat'] *= IP::MULTIPLICATOR;
            $geoPosition['long'] *= IP::MULTIPLICATOR;
            $geoPosition = array_merge($cityInfo, $geoPosition);
            $result = self::prepareGeoPosition($geoPosition);
            $result['geo_position_age'] = date('Y-m-d H:i:s');
        } elseif ($cityId) {
            $result = self::getGeoPositionFromCity($cityId);
        }
        if ($result) {
            if (isset($result['geo_position_state_id'])) {
                $sql = 'SELECT `state_title` FROM `geo_state`
                         WHERE `state_id` = ' . to_sql($result['geo_position_state_id']);
                $result['geo_position_state'] = DB::result($sql);
            } else {
                $result['geo_position_state'] = '';
            }
            if (isset($result['geo_position_country_id'])) {
                $sql = 'SELECT `country_title` FROM `geo_country`
                         WHERE `country_id` = ' . to_sql($result['geo_position_country_id']);
                $result['geo_position_country'] = DB::result($sql);
            } else {
                $result['geo_position_country'] = '';
            }
        }


        return $result;
    }

    static public function updateGeoPosition($cityId = 0, $geoPosition = null, $uid = null)
	{
        if ($uid === null) {
            $uid = guid();
        }
        if (!$uid) {
            return false;
        }
        if ($geoPosition === null) {
            $geoPosition = self::getGeoPosition($cityId);

        }
        if (!$geoPosition) {
            return false;
        }

        $result = self::getGeoPositionData();
        $userLat = guser('geo_position_lat');
        $userLong = guser('geo_position_long');
        $userPositionAge = guser('geo_position_age');
        if ($userLat != strval($geoPosition['geo_position_lat']) || $userLong != strval($geoPosition['geo_position_long'])) {
            $isUpdate = true;
            if ($userPositionAge != '0000-00-00 00:00:00' && $geoPosition['geo_position_age'] == '0000-00-00 00:00:00') {
                $userPositionAge = time_mysql_dt2u(guser('geo_position_age'));
                $isUpdate = !((time() - $userPositionAge) / 60 < Common::getOptionInt('geo_position_max_age'));
            }
            if ($isUpdate) {
                self::update($geoPosition);
                $data = array('country' => $geoPosition['geo_position_country'],
                              'city' => $geoPosition['geo_position_city']
                );
                $result = self::getGeoPositionData($data);
            }
        }
        return $result;
    }

    static public function getGeoPositionData($data = null)
	{
        if ($data === null) {
            $data = array('country' => guser('geo_position_country'),
                          'city' => guser('geo_position_city')
                    );
        }
        foreach ($data as $key => $value) {
            $data[$key] = l($value);
        }

        return json_encode($data);
    }

    static function actionFavorite($uid = null, $remove = false)
    {
        if ($uid === null) {
            $uid = get_param_int('user_id');
        }
        $guid = guid();
        if (!$guid || !$uid) {
            return false;
        }
        $result = 'empty';
        if (self::isFavoriteExists($guid, $uid)) {
            DB::delete('users_favorite', '`user_from` = ' . to_sql($guid) . ' AND ' . '`user_to` = ' . to_sql($uid));
            $result = 'remove';
        } elseif (!$remove) {
            $data = array('user_from' => $guid,
                          'user_to' => $uid);
            DB::insert('users_favorite', $data);
            CStatsTools::count('added_to_favourites');
            $result = 'add';
        }

        return $result;
    }

    static function isShowAge($user)
    {
        $isShow = Common::isOptionActive('show_age_profile', 'edge_member_settings');
        if ($isShow) {
            $isShow = isset($user['set_notif_show_my_age']) && $user['set_notif_show_my_age'] == 1;
        }
        return $isShow;
    }

    static function getNumberGlobalEvents($all = false, $groupId = 0)
    {
        $whereGroupLike = ' AND group_id = 0';
        $whereGroupComment = ' group_id = 0 AND ';

        $whereGroupLike_video = ' AND VL.group_id = 0';
        $whereGroupComment_video = ' CO.group_id = 0 AND ';

        if ($groupId) {
            $whereGroupLike = ' AND group_id = ' . to_sql($groupId);
            $whereGroupComment = ' group_id = ' . to_sql($groupId) . ' AND ';

            $whereGroupLike_video = ' AND VL.group_id = ' . to_sql($groupId);
            $whereGroupComment_video = ' CO.group_id = ' . to_sql($groupId) . ' AND ';
        }

        $whereGroupLike = '';
        $whereGroupComment = '';

        $whereGroupLike_video = '';
        $whereGroupComment_video = '';

        $guid = guid();
        $guidSql = to_sql($guid);

        $sql = "SELECT
                (SELECT COUNT(*)
                   FROM `vids_comments_likes` AS VL
                   LEFT JOIN `vids_video` AS VV ON VV.id = VL.video_id
                  WHERE " . ($all ? '' : 'VL.is_new = 1 AND ') . "VV.active = 1 AND VL.comment_user_id = {$guidSql} AND VL.user_id != {$guidSql} {$whereGroupLike_video})

                +
                (SELECT COUNT(*)
                   FROM `photo_comments_likes`
                  WHERE " . ($all ? '' : 'is_new = 1 AND ') . "comment_user_id = {$guidSql} AND user_id != {$guidSql} {$whereGroupLike})

                +
                (SELECT COUNT(*)
                   FROM `wall_comments_likes`
                  WHERE " . ($all ? '' : 'is_new = 1 AND ') . "comment_user_id = {$guidSql} AND user_id != {$guidSql} {$whereGroupLike})

                +
                (SELECT COUNT(*)
                   FROM `photo_comments`
                  WHERE " . ($all ? '' : 'is_new = 1 AND ') . " {$whereGroupComment}
                        (
                         (photo_user_id = {$guidSql} AND user_id != {$guidSql} AND parent_user_id = 0)
                        OR
                         (photo_user_id = {$guidSql} AND user_id != {$guidSql} AND parent_user_id = {$guidSql})
						OR
						 (photo_user_id != {$guidSql} AND user_id != {$guidSql} AND parent_user_id = {$guidSql})
                        )
                )
                +

                (SELECT COUNT(*)
                   FROM `vids_comment` AS CO
                   LEFT JOIN `vids_video` AS VV ON VV.id = video_id
                  WHERE " . ($all ? '' : 'CO.is_new = 1 AND ') . " {$whereGroupComment_video}
                         VV.active = 1 AND (
                         (CO.video_user_id = {$guidSql} AND CO.user_id != {$guidSql} AND CO.parent_user_id = 0)
                        OR
                         (CO.video_user_id = {$guidSql} AND CO.user_id != {$guidSql} AND CO.parent_user_id = {$guidSql})
						OR
						 (CO.video_user_id != {$guidSql} AND CO.user_id != {$guidSql} AND CO.parent_user_id = {$guidSql})
                        )
                )

                +
                (SELECT COUNT(*)
                   FROM `wall_comments`
                  WHERE " . ($all ? '' : 'is_new = 1 AND ') . "  {$whereGroupComment}
                        (
                         (wall_item_user_id = {$guidSql} AND user_id != {$guidSql} AND parent_user_id = 0)
                        OR
                         (wall_item_user_id = {$guidSql} AND user_id != {$guidSql} AND parent_user_id = {$guidSql})
						OR
						 (wall_item_user_id != {$guidSql} AND user_id != {$guidSql} AND parent_user_id = {$guidSql})
                        )
                )";

        $sqlList = array();
        $sqlList[] = "+
                    (SELECT COUNT(*)
                       FROM `events_event`
                      WHERE " . ($all ? '' : 'done_new = 1 AND ') . "
                           ((user_to = {$guidSql} AND done_user = user_id AND user_id != {$guidSql})
                         OR (user_id = {$guidSql} AND done_user = user_to AND user_to != {$guidSql}))
                        AND done_user != 0)";

        //New group members
        $sqlList[] = "+
                    (SELECT COUNT(*)
                       FROM `groups_social_subscribers`
                      WHERE " . ($all ? '' : 'is_new = 1 AND ') . "user_id != {$guidSql} AND group_user_id = {$guidSql} AND group_private = 'N' AND accepted = 1)";


        //You are now a member of
        $sqlList[] = "+
                    (SELECT COUNT(*)
                       FROM `groups_social_subscribers`
                      WHERE " . ($all ? '' : 'is_new = 1 AND ') . "user_id = {$guidSql} AND group_user_id != {$guidSql} AND group_private = 'Y' AND accepted = 1)";


        //Wall like post
        $sqlList[] = "+
                    (SELECT COUNT(*)
                       FROM `wall_likes`
                      WHERE " . ($all ? '' : 'is_new = 1 AND ') . "wall_item_user_id = {$guidSql} AND user_id != {$guidSql} {$whereGroupLike})";

        //Photo like post
        $sqlList[] = "+
                    (SELECT COUNT(*)
                       FROM `photo_likes`
                      WHERE " . ($all ? '' : 'is_new = 1 AND ') . "photo_user_id = {$guidSql} AND user_id != {$guidSql} {$whereGroupLike})";


        //Video like post
        $sqlList[] = "+
                    (SELECT COUNT(*)
                       FROM `vids_likes` AS VL
                       LEFT JOIN `vids_video` AS VV ON VV.id = VL.video_id
                      WHERE " . ($all ? '' : 'VL.is_new = 1 AND ') . "VV.active = 1 AND VL.video_user_id = {$guidSql} AND VL.user_id != {$guidSql} {$whereGroupLike})";


        foreach ($sqlList as $key => $sqlItem) {
            $sql .= $sqlItem;
        }


		return DB::result($sql);
    }

    static function getListGlobalEvents($loadLimit = null, $order = 'DESC', $groupId = 0, $isUpdateOldEvent = false, $isUpdateOldEventAll = false)
    {
        global $g;

        $guid = guid();
        $guidSql = to_sql($guid);

        $whereGroupLike = ' AND CL.group_id = 0';
        $whereGroupComment = ' AND CO.group_id = 0';
        if ($groupId) {
            $whereGroupLike = ' AND CL.group_id = ' . to_sql($groupId);
            $whereGroupComment = ' AND CO.group_id = ' . to_sql($groupId);
        }

        $whereGroupLike = '';
        $whereGroupComment = '';

        $cmd = get_param('cmd');

        if(!function_exists('getWhereSql')) {
            function getWhereSql($field, $isUpdateOldEvent = false){
                $whereSql = '';
                $cmd = get_param('cmd');
                if (!$isUpdateOldEvent && $cmd == 'update_im') {//Server update
                    $date = get_param('event_first_date');
                    $whereSql = " AND {$field} > " . to_sql($date);
                }
                return $whereSql;
            }
        }

        $sql = "(SELECT IF(true, 'vids_comments_likes', 'vids_comments_likes') AS type,
						IF(true, 'vids_comments_likes', 'vids_comments_likes') AS tb,
                        IF(true, 'vcl', 'vcl') AS type_short,
                        CL.id AS id,
                        CL.group_id AS group_id,
                        CL.is_new AS new,
                        CL.user_id AS user_id, CL.date AS date,
                        CL.video_id AS event_id,
						VV.live_id AS live_id,
                        CL.video_user_id AS event_user_id,
                        CL.user_id AS event_who_user_id,
                        CL.cid AS event_item_id,
                        IF(true, 0, 0) AS event_item_parent_id,
                        IF(true, 0, 0) AS event_item_parent_id_real,
                        CU.name, CU.name_seo, CU.gender
                   FROM `vids_comments_likes` AS CL
                   LEFT JOIN `vids_video` AS VV ON VV.id = CL.video_id
                   LEFT JOIN `user` AS CU ON CU.user_id = CL.user_id
                  WHERE VV.active = 1 AND CL.comment_user_id = {$guidSql} AND CL.user_id != {$guidSql} {$whereGroupLike} " . getWhereSql('CL.date', $isUpdateOldEvent) . ")

                UNION
                (SELECT IF(true, 'photo_comments_likes', 'photo_comments_likes') AS type,
						IF(true, 'photo_comments_likes', 'photo_comments_likes') AS tb,
                        IF(true, 'pcl', 'pcl') AS type_short,
                        CL.id AS id,
                        CL.group_id AS group_id,
                        CL.is_new AS new,
                        CL.user_id AS user_id, CL.date AS date,
                        CL.photo_id AS event_id,
						IF(true, 0, 0) AS live_id,
                        CL.photo_user_id AS event_user_id,
                        CL.user_id AS event_who_user_id,
                        CL.cid AS event_item_id,
                        IF(true, 0, 0) AS event_item_parent_id,
                        IF(true, 0, 0) AS event_item_parent_id_real,
                        CU.name, CU.name_seo, CU.gender
                   FROM `photo_comments_likes` AS CL
                   LEFT JOIN `user` AS CU ON CU.user_id = CL.user_id
                  WHERE CL.comment_user_id = {$guidSql} AND CL.user_id != {$guidSql} {$whereGroupLike} " . getWhereSql('CL.date', $isUpdateOldEvent) . ")

                UNION
                (SELECT IF(true, 'wall_comments_likes', 'wall_comments_likes') AS type,
						IF(true, 'wall_comments_likes', 'wall_comments_likes') AS tb,
                        IF(true, 'wcl', 'wcl') AS type_short,
                        CL.id AS id,
                        CL.group_id AS group_id,
                        CL.is_new AS new,
                        CL.user_id AS user_id, CL.date AS date,
                        CL.wall_item_id AS event_id,
						IF(true, 0, 0) AS live_id,
                        CL.wall_item_user_id AS event_user_id,
                        CL.user_id AS event_who_user_id,
                        CL.cid AS event_item_id,
                        IF(true, 0, 0) AS event_item_parent_id,
                        CL.parent_id AS event_item_parent_id_real,
                        CU.name, CU.name_seo, CU.gender
                   FROM `wall_comments_likes` AS CL
                   LEFT JOIN `user` AS CU ON CU.user_id = CL.user_id
                  WHERE CL.comment_user_id = {$guidSql} AND CL.user_id != {$guidSql} {$whereGroupLike} " . getWhereSql('CL.date', $isUpdateOldEvent) . ")

                UNION
                (SELECT IF(true, 'photo_comments', 'photo_comments') AS type,
						IF(true, 'photo_comments', 'photo_comments') AS tb,
                        IF(true, 'pc', 'pc') AS type_short,
                        CO.id AS id,
                        CO.group_id AS group_id,
                        CO.is_new AS new,
                        CO.user_id AS user_id, CO.date AS date,
                        CO.photo_id AS event_id,
						IF(true, 0, 0) AS live_id,
                        CO.photo_user_id AS event_user_id,
                        CO.user_id AS event_who_user_id,
                        CO.id AS event_item_id,
                        CO.parent_id AS event_item_parent_id,
                        CO.parent_id AS event_item_parent_id_real,
                        CU.name, CU.name_seo, CU.gender
                   FROM `photo_comments` AS CO
                   LEFT JOIN `user` AS CU ON CU.user_id = CO.user_id
                  WHERE
						(
                         (CO.photo_user_id = {$guidSql} AND CO.user_id != {$guidSql} AND CO.parent_user_id = 0)
                        OR
                         (CO.photo_user_id = {$guidSql} AND CO.user_id != {$guidSql} AND CO.parent_user_id = {$guidSql})
						OR
						 (CO.photo_user_id != {$guidSql} AND  CO.user_id != {$guidSql} AND CO.parent_user_id = {$guidSql})
                        ) {$whereGroupComment} " . getWhereSql('CO.date', $isUpdateOldEvent) . "
                )
                UNION
                (SELECT IF(true, 'vids_comment', 'vids_comment') AS type,
						IF(true, 'vids_comment', 'vids_comment') AS tb,
                        IF(true, 'vc', 'vc') AS type_short,
                        CO.id AS id,
                        CO.group_id AS group_id,
                        CO.is_new AS new,
                        CO.user_id AS user_id, CO.dt AS date,
                        CO.video_id AS event_id,
						VV.live_id AS live_id,
                        CO.video_user_id AS event_user_id,
                        CO.user_id AS event_who_user_id,
                        CO.id AS event_item_id,
                        CO.parent_id AS event_item_parent_id,
                        CO.parent_id AS event_item_parent_id_real,
                        CU.name, CU.name_seo, CU.gender
                   FROM `vids_comment` AS CO
                   LEFT JOIN `vids_video` AS VV ON VV.id = CO.video_id
                   LEFT JOIN `user` AS CU ON CU.user_id = CO.user_id
                  WHERE VV.active = 1 AND
                        (
                         (CO.video_user_id = {$guidSql} AND CO.user_id != {$guidSql} AND CO.parent_user_id = 0)
                        OR
                         (CO.video_user_id = {$guidSql} AND CO.user_id != {$guidSql} AND CO.parent_user_id = {$guidSql})
						OR
						 (CO.video_user_id != {$guidSql} AND CO.user_id != {$guidSql} AND CO.parent_user_id = {$guidSql})
                        ) {$whereGroupComment} " . getWhereSql('CO.dt', $isUpdateOldEvent) . "
                )
                UNION
                (SELECT IF(true, 'wall_comments', 'wall_comments') AS type,
						IF(true, 'wall_comments', 'wall_comments') AS tb,
                        IF(true, 'wc', 'wc') AS type_short,
                        CO.id AS id,
                        CO.group_id AS group_id,
                        CO.is_new AS new,
                        CO.user_id AS user_id, CO.date AS date,
                        CO.wall_item_id AS event_id,
						IF(true, 0, 0) AS live_id,
                        CO.wall_item_user_id AS event_user_id,
                        CO.user_id AS event_who_user_id,
                        CO.id AS event_item_id,
                        CO.parent_id AS event_item_parent_id,
                        CO.parent_id AS event_item_parent_id_real,
                        CU.name, CU.name_seo, CU.gender
                   FROM `wall_comments` AS CO
                   LEFT JOIN `user` AS CU ON CU.user_id = CO.user_id
                  WHERE
                        (
                         (CO.wall_item_user_id = {$guidSql} AND CO.user_id != {$guidSql} AND CO.parent_user_id = 0)
                        OR
                         (CO.wall_item_user_id = {$guidSql} AND CO.user_id != {$guidSql} AND CO.parent_user_id = {$guidSql})
						OR
						 (CO.wall_item_user_id != {$guidSql} AND CO.user_id != {$guidSql} AND  CO.parent_user_id = {$guidSql})
                        ) {$whereGroupComment} " . getWhereSql('CO.date', $isUpdateOldEvent) . "
                )";

        $sqlList = array();
        $sqlList[] = "UNION
                (SELECT IF(true, 'task', 'task') AS type,
						IF(true, 'events_event', 'events_event') AS tb,
                        IF(true, 'tsk', 'tsk') AS type_short,
                        CO.event_id AS id,
                        IF(true, 0, 0) AS group_id,
                        CO.done_new AS new,
                        CO.done_user AS user_id,
                        CO.event_datetime AS date,
                        IF(true, 0, 0) AS event_id,
						IF(true, 0, 0) AS live_id,
                        CO.user_id AS event_user_id,
                        CO.user_to AS event_who_user_id,
                        CO.event_id AS event_item_id,
                        CO.event_id AS event_item_parent_id,
                        CO.event_id AS event_item_parent_id_real,
                        CU.name, CU.name_seo, CU.gender
                   FROM `events_event` AS CO
                   LEFT JOIN `user` AS CU ON CU.user_id = CO.done_user
                  WHERE ((CO.user_to = {$guidSql} AND CO.done_user = CO.user_id AND CO.user_id != {$guidSql})
                      OR (CO.user_id = {$guidSql} AND CO.done_user = CO.user_to AND CO.user_to != {$guidSql}))
                    AND CO.done_user != 0" . getWhereSql('CO.event_datetime', $isUpdateOldEvent) .
                ")";

        //New group members
        $sqlList[] = "UNION
                (SELECT IF(GRS.page, 'groups_subscribers_page_new', 'groups_subscribers_new') AS type,
						IF(true, 'groups_social_subscribers', 'groups_social_subscribers') AS tb,
                        IF(true, 'gr_sb_new', 'gr_sb_new') AS type_short,
                        GRS.id AS id,
                        GRS.group_id AS group_id,
                        GRS.is_new AS new,
                        GRS.user_id AS user_id,
                        GRS.created_at AS date,
                        IF(true, 0, 0) AS event_id,
						IF(true, 0, 0) AS live_id,
                        GRS.user_id AS event_user_id,
                        GRS.group_user_id AS event_who_user_id,
                        GRS.id AS event_item_id,
                        GRS.id AS event_item_parent_id,
                        GRS.id AS event_item_parent_id_real,
                        CU.name, CU.name_seo, CU.gender
                   FROM `groups_social_subscribers` AS GRS
                   LEFT JOIN `user` AS CU ON CU.user_id = GRS.user_id
                  WHERE GRS.user_id != {$guidSql} AND GRS.group_user_id = {$guidSql} AND GRS.group_private = 'N'
                    AND GRS.accepted = 1" . getWhereSql('GRS.approve_at', $isUpdateOldEvent) .
                ")";

        //You are now a member of
        $sqlList[] = "UNION
                (SELECT IF(true, 'groups_social_subscribers', 'groups_social_subscribers') AS type,
						IF(true, 'groups_social_subscribers', 'groups_social_subscribers') AS tb,
                        IF(true, 'gsb', 'gsb') AS type_short,
                        CL.id AS id,
                        CL.group_id AS group_id,
                        CL.is_new AS new,
                        CL.user_id AS user_id, CL.approve_at AS date,
                        CL.group_id AS event_id,
						IF(true, 0, 0) AS live_id,
                        CL.group_user_id AS event_user_id,
                        CL.user_id AS event_who_user_id,
                        CL.id AS event_item_id,
                        IF(true, 0, 0) AS event_item_parent_id,
                        IF(true, 0, 0) AS event_item_parent_id_real,
                        CU.name, CU.name_seo, CU.gender

                   FROM `groups_social_subscribers` AS CL
                   LEFT JOIN `user` AS CU ON CU.user_id = CL.user_id

                  WHERE CL.user_id = {$guidSql} AND CL.group_user_id != {$guidSql}
                    AND CL.group_private = 'Y' AND CL.accepted = 1" . getWhereSql('CL.approve_at', $isUpdateOldEvent) . ")";

        //Wall like post
        $sqlList[] = "UNION
                (SELECT IF(true, 'wall_likes', 'wall_likes') AS type,
						IF(true, 'wall_likes', 'wall_likes') AS tb,
                        IF(true, 'wlp', 'wlp') AS type_short,
                        CL.id AS id,
                        CL.group_id AS group_id,
                        CL.is_new AS new,
                        CL.user_id AS user_id, CL.date AS date,
                        CL.wall_item_id AS event_id,
						IF(true, 0, 0) AS live_id,
                        CL.wall_item_user_id AS event_user_id,
                        CL.user_id AS event_who_user_id,
                        CL.wall_item_id AS event_item_id,
                        IF(true, 0, 0) AS event_item_parent_id,
                        IF(true, 0, 0) AS event_item_parent_id_real,
                        CU.name, CU.name_seo, CU.gender
                   FROM `wall_likes` AS CL
                   LEFT JOIN `user` AS CU ON CU.user_id = CL.user_id
                  WHERE CL.wall_item_user_id = {$guidSql} AND CL.user_id != {$guidSql} {$whereGroupLike} " . getWhereSql('CL.date', $isUpdateOldEvent) . ")";

        //Photo like post
        $sqlList[] = "UNION
                (SELECT IF(true, 'photo_likes', 'photo_likes') AS type,
						IF(true, 'photo_likes', 'photo_likes') AS tb,
                        IF(true, 'phl', 'phl') AS type_short,
                        CL.id AS id,
                        CL.group_id AS group_id,
                        CL.is_new AS new,
                        CL.user_id AS user_id, CL.date AS date,
                        CL.photo_id AS event_id,
						IF(true, 0, 0) AS live_id,
                        CL.photo_user_id AS event_user_id,
                        CL.user_id AS event_who_user_id,
                        CL.photo_id AS event_item_id,
                        IF(true, 0, 0) AS event_item_parent_id,
                        IF(true, 0, 0) AS event_item_parent_id_real,
                        CU.name, CU.name_seo, CU.gender
                   FROM `photo_likes` AS CL
                   LEFT JOIN `user` AS CU ON CU.user_id = CL.user_id
                  WHERE CL.photo_user_id = {$guidSql} AND CL.user_id != {$guidSql} {$whereGroupLike} " . getWhereSql('CL.date', $isUpdateOldEvent) . ")";

        //Video like post
        $sqlList[] = "UNION
                (SELECT IF(true, 'vids_likes', 'vids_likes') AS type,
						IF(true, 'vids_likes', 'vids_likes') AS tb,
                        IF(true, 'vidsl', 'vidsl') AS type_short,
                        CL.id AS id,
                        CL.group_id AS group_id,
                        CL.is_new AS new,
                        CL.user_id AS user_id, CL.date AS date,
                        CL.video_id AS event_id,
						VV.live_id AS live_id,
                        CL.video_user_id AS event_user_id,
                        CL.user_id AS event_who_user_id,
                        CL.video_id AS event_item_id,
                        IF(true, 0, 0) AS event_item_parent_id,
                        IF(true, 0, 0) AS event_item_parent_id_real,
                        CU.name, CU.name_seo, CU.gender
                   FROM `vids_likes` AS CL
                   LEFT JOIN `vids_video` AS VV ON VV.id = CL.video_id
                   LEFT JOIN `user` AS CU ON CU.user_id = CL.user_id
                  WHERE VV.active = 1 AND CL.video_user_id = {$guidSql} AND CL.user_id != {$guidSql} {$whereGroupLike} " . getWhereSql('CL.date', $isUpdateOldEvent) . ")";

        foreach ($sqlList as $key => $sqlItem) {
            $sql .= $sqlItem;
        }

		if ($isUpdateOldEvent) {
			$sqlUpdate = "SELECT * FROM ({$sql}) TBU WHERE TBU.`new` = 1";
			if (!$isUpdateOldEventAll) {
				$sqlUpdate .= ' AND TBU.`date` < NOW() - INTERVAL 1 WEEK';
			}
			$itemsUpdate = DB::rows($sqlUpdate);
			if ($itemsUpdate) {
				$itemsUpdateValue = array();
				foreach ($itemsUpdate as $k => $item) {
					$itemsUpdate[$k]['alias'] = "events_notification_{$item['type_short']}_{$item['event_item_id']}_{$item['event_who_user_id']}";
					if (!isset($itemsUpdateValue[$item['tb']])) {
						$itemsUpdateValue[$item['tb']] = array();
					}
					$itemsUpdateValue[$item['tb']][] = $item['id'];
				}

				foreach ($itemsUpdateValue as $tb => $item) {
					$id = $tb == 'events_event' ? 'event_id' : 'id';
					$prepareData = implode(",", $item);
					DB::update($tb, array('is_new' => 0), "{$id} IN (" . to_sql($prepareData, 'Plain') . ')');
				}
			}
			return  $itemsUpdate;
		}

        if ($loadLimit === null) {
            $loadLimit = Common::getOptionInt('number_show_notif_events', 'edge_member_settings');
            if (!$loadLimit) {
                $loadLimit = 1;
            }
        }


        //if ($cmd == 'update_im') {
            //$sql = "{$sql} ORDER BY date {$order}";
        //} else {
            $customWhere = '';
            if ($cmd == 'get_more_event') {
                $rank = get_param_int('rank');
                $customWhere = ' WHERE `rank` > ' . to_sql($rank);
            }
            if ($cmd != 'update_im') {
                $customWhere .= ' LIMIT ' . to_sql($loadLimit, 'Number');
            }

            $sql = "SELECT * FROM(
                           SELECT EVT.*, @rownum := @rownum + 1 AS `rank`
                             FROM ({$sql} ORDER BY date {$order}, id DESC) EVT,
                          (SELECT @rownum := 0) R) AEVT {$customWhere}";
        //}


        $result = DB::rows($sql);
        $events = array();
        //var_dump_pre($result, true);
        foreach ($result as $key => $item) {

            $gender = strtolower($item['gender']);

            $isNotifPage = 0;
            $groupUserId = 0;
            if ($item['group_id']) {
                $groupInfo = Groups::getInfoBasic($item['group_id']);
                $isNotifPage = $groupInfo['page'];
                $groupUserId = $groupInfo['user_id'];
            }
            $isNotifComments = in_array($item['type'], array('photo_comments', 'vids_comment','wall_comments'));
            $isNotifCommentsLikes = in_array($item['type'], array('photo_comments_likes', 'vids_comments_likes','wall_comments_likes'));

            if ($isNotifComments && $item['event_item_parent_id']) {
                $lTitle = "event_{$item['type']}_reply_{$gender}";
            } else {
                if ($item['type'] == 'groups_social_subscribers') {
                    $lTitle = "event_{$item['type']}";
                } else {
                    $lTitle = "event_{$item['type']}_{$gender}";
                }
            }

            $urlPage = '';
            if ($item['type'] == 'task') {
                $dateEvent = explode(' ', $item['date']);
                $urlPage = Common::pageUrl('user_calendar', $item['event_who_user_id'], $dateEvent[0]);
                $paramsAddSymbol = mb_strpos($urlPage, '?', 0, 'UTF-8') === false ? '?' : '&';
                $urlPage .= $paramsAddSymbol . 'neid=' . $item['id'];
            }
            if ($item['type'] == 'groups_social_subscribers'
                    || ($isNotifPage && $item['event_who_user_id'] == $groupUserId && ($isNotifComments || $isNotifCommentsLikes))) {
                $groupInfo = Groups::getInfoBasic($item['group_id']);
                $urlUser = Groups::url($item['group_id'], $groupInfo);
                $userName = $groupInfo['title'];
                $photo = $g['path']['url_files'] . GroupsPhoto::getPhotoDefault($item['event_user_id'], $item['group_id']);
            } else {
                $urlUser = User::url($item['user_id'], array('name' => $item['name'], 'name_seo' => $item['name_seo']));
                $userName = User::nameOneLetterFull($item['name']);
                $photo = $g['path']['url_files'] . User::getPhotoDefault($item['user_id']);
            }

            $vars = array('name' => $userName,
                          'url'  => $urlUser);

            $title = Common::lSetLink($lTitle, $vars);

            if ($item['type'] == 'groups_subscribers_new' || $item['type'] == 'groups_subscribers_page_new') {
                $groupInfo = Groups::getInfoBasic($item['group_id']);
                $vars = array('group_title' => $groupInfo['title'],
                              'url'         => Groups::url($item['group_id'], $groupInfo));
                $title = Common::lSetLink($title, $vars, false, '_group');
            }

            $events[] = array(
                'rank'       => $item['rank'],
                'alias'      => "events_notification_{$item['type_short']}_{$item['event_item_id']}_{$item['event_who_user_id']}",
                'type'       => $item['type'],
                'type_short' => $item['type_short'],
                'group_id'   => $item['group_id'],
                'group_type' => $item['group_id'] ? ($isNotifPage ? 'group_page' : 'group') : '',
                'event_id'   => $item['event_id'],
                'event_user_id'   => $item['event_user_id'],
                'event_item_id'   => $item['event_item_id'],
                'event_item_parent_id'   => $item['event_item_parent_id_real'],
				'live_id'    => $item['live_id'],
                'id'         => $item['id'],
                'title'      => $title,//"events_notification_{$item['type_short']}_{$item['event_item_id']}/" . $title
                'date'       => $item['date'],
                'time_ago'   => timeAgo($item['date'], 'now', 'string', 60, 'second'),
                'user_id'    => $item['user_id'],
                'url'        => $urlUser,
                'url_page'   => $urlPage,
                'photo'      => $photo,
                'new'        => $item['new']
            );
        }

        return $events;
    }

    static function markSeenEvent($type = null,  $id = null)
    {
        if ($type === null) {
            $type = get_param('type');
        }
        if ($id === null) {
            $id = get_param_int('id');
        }
        if ($type && $id) {
            if ($type == 'task') {
                TaskCalendar::markSeen($id);
            } else {
                if ($type == 'groups_subscribers_new' || $type == 'groups_subscribers_page_new') {
                    $type = 'groups_social_subscribers';
                }
                DB::update($type, array('is_new' => 0), '`id` = ' . to_sql($id));
            }
        }
        $groupIdEvent = Groups::getEventId();
        $count = self::getNumberGlobalEvents(false, $groupIdEvent);

        return $count;
    }

    static function getTitleLikeUsersComment($cid, $countLikes, $type = '')
    {
        if (!$countLikes || (!Common::isOptionActiveTemplate('gallery_comment_like') &&  !Common::isOptionActiveTemplate('gallery_comment_like_template'))) {
            return '';
        }

        if ($type == 'video' || $type == 'vids' || $type == 'live') {
            $table = 'vids_comments_likes';
        } elseif($type == 'photo') {
            $table = 'photo_comments_likes';
        } elseif($type == 'blogs_post') {
            $table = 'blogs_comments_likes';
        } else {
            $table = 'wall_comments_likes';
        }

        $guid = guid();
        $number = 4;

        $titleLikesUsers = '';
        $sql = 'SELECT COUNT(*)
                  FROM `' . $table . '`
                 WHERE cid = ' . to_sql($cid) .
                 ' AND user_id = ' . to_sql($guid);
        $isMyLike = DB::result($sql, 0, DB_MAX_INDEX);
        if ($isMyLike) {
            $countLikes--;
            $titleLikesUsers = l('you');
        }

        if (!$countLikes) {
            return $titleLikesUsers;
        }

        $delimiter = ', ';
        $countLikesUsers = 0;
        $sql = 'SELECT U.name, U.user_id, LC.group_id, LC.group_user_id
                  FROM `' . $table . '` AS LC
                  JOIN `user` AS U ON U.user_id = LC.user_id
                 WHERE LC.cid = ' . to_sql($cid) .
                 ' AND LC.user_id != ' . to_sql($guid) .
               ' ORDER BY LC.id DESC
                 LIMIT ' . $number;
        $likeUsers = DB::rows($sql, DB_MAX_INDEX);
        if ($likeUsers) {
            foreach ($likeUsers as $key => $user) {
                $userName = $user['name'];
                if ($user['group_id'] && $user['group_user_id'] == $user['user_id']) {
                    $isPageGroup = Groups::getInfoBasic($user['group_id'], 'page');
                    if ($isPageGroup) {
                        $userName = Groups::getInfoBasic($user['group_id'], 'title');
                    }
                }
                //self::nameOneLetterShort($user['name'])
                $titleLikesUsers .= ($titleLikesUsers ? $delimiter : '') . $userName;
            }
            $countLikesUsers = count($likeUsers);
        }
        if ($titleLikesUsers && $countLikes > $countLikesUsers) {
            $titleLikesUsers .= lSetVars('title_and_more_people_like_this', array('count' => $countLikes - $countLikesUsers));
        }
        return $titleLikesUsers;

    }

    static function getNameSeoFromUid($uid)
    {
        $key = 'user_name_seo_from_uid_' . $uid;
        $nameSeo = Cache::get($key);
        if($nameSeo === null) {
            $sql = 'SELECT `name_seo` FROM `user` WHERE `user_id` = ' . to_sql($uid);
            $nameSeo = DB::result($sql, 0, DB_MAX_INDEX);
            Cache::add($key, $nameSeo);
        }
        return $nameSeo;
    }

    static function getDataUserOrGroup($uid, $groupId, $userInfo = null, $groupInfo = null, $cache = true, $checkPage = true)
    {
        $key = 'User_getDataUserOrGroup_' . $uid . '_' . $groupId;
        $data = null;
        if ($cache) {
            $data = Cache::get($key);
        }

        if($data !== null) {
            return $data;
        }

        $data = array(
            'name'  => '',
            'url'   => '',
            'photo' => '',
            'photo_s' => '',
            'photo_id' => ''
        );
        $isSetUserData = true;
        if ($groupId) {
            if ($groupInfo === null) {
                $groupInfo = Groups::getInfoBasic($groupId);
            }
            $checkPageInfo = true;
            if ($checkPage) {
                $checkPageInfo = $groupInfo['page'];
            }
            if ($groupInfo && $uid == $groupInfo['user_id'] && $checkPageInfo) {
                $data = array(
                    'group_id' => $groupId,
                    'name'  => $groupInfo['title'],
                    'name_short'  => $groupInfo['title'],
                    'url'   => Groups::url($groupId, $groupInfo),
                    'photo' => GroupsPhoto::getPhotoDefault($uid, $groupId, 'r'),
                    'photo_s' => GroupsPhoto::getPhotoDefault($uid, $groupId, 's'),
                    'photo_id' => GroupsPhoto::getPhotoDefault($uid, $groupId, 'r', true),
                    'user_group_owner' => $groupId
                );
                $isSetUserData = false;
            }
        }
        if ($isSetUserData) {
            if ($userInfo === null) {
                $userInfo = self::getInfoBasic($uid);
            }
            $data = array(
                'group_id' => 0,
                'name'  => $userInfo['name'],
                'name_short'  => User::nameShort($userInfo['name']),
                'url'   => User::url($uid),
                'photo' => User::getPhotoDefault($uid, 'r', false, $userInfo['gender']),
                'photo_s' => User::getPhotoDefault($uid, 's', false, $userInfo['gender']),
                'photo_id' => User::getPhotoDefault($uid, 'r', true),
                'user_group_owner' => 0
            );
        }

        Cache::add($key, $data);

        return $data;
    }

    static function getProfileVerificationData($row)
    {
        $result = array('system' => array(), 'data' => array());
        if (!isset($row['user_id']) || !Common::isOptionActive('profile_verification_enabled')) {
            return $result;
        }
        $key = 'profile_verification_data_' . $row['user_id'];
        $info = Cache::get($key);
        if ($info) {
            return $info;
        }

        $verificationSystems = Social::getActiveItems();
        $verifiedSystems = array();
        $verificationSystemsData = array();
        foreach($verificationSystems as $verificationSystemKey => $verificationSystemValue) {
            $profileSystemKey = $verificationSystemKey . '_id';

            $verificationSystemTitle = l($verificationSystemKey);

            if(isset($row[$profileSystemKey]) && $row[$profileSystemKey]) {
                $verifiedSystems[] = $verificationSystemTitle;
            }
            if(guser($profileSystemKey)) {
            //if($row[$profileSystemKey]) {
                continue;
            }

            $verificationSystem = Social::$socialArr[$verificationSystemKey];
            $verificationSystemsData[urlencode($verificationSystem->loginRedirectUrl())] = $verificationSystemTitle;
        }

        $result = array('system' => $verifiedSystems, 'data' => $verificationSystemsData);
        Cache::add($key, $result);

        return $result;
    }

    static function isAllowProfileVerification($row = null, $verifiedSystemsUser = null)
    {
        if (!Common::isOptionActive('profile_verification_enabled')) {
            return false;
        }

        if ($row === null) {
            $row = self::getInfoBasic(guid());
        }

        if ($verifiedSystemsUser === null) {
            $verifiedSystemsUser = self::getProfileVerificationData($row);
        }

        $verifiedSystems = $verifiedSystemsUser['system'];
        $verificationSystemsData = $verifiedSystemsUser['data'];

        if($row['user_id'] == guid()) {
            if(!count($verifiedSystems) && count($verificationSystemsData)) {
                return true;
            }
        }
        return false;
    }

    static function parseProfileVerification(&$html, $row = null, $block = 'profile_verification_verified')
    {
        if ($html->blockExists($block) && Common::isOptionActive('profile_verification_enabled') && count(Social::getActiveItems())) {
            if ($row === null) {
                $row = self::getInfoBasic(guid());
            }

            $verifiedSystemsUser = self::getProfileVerificationData($row);
            $verifiedSystems = $verifiedSystemsUser['system'];
            $verificationSystemsData = $verifiedSystemsUser['data'];

            if($verificationSystemsData) {
                $html->setvar('profile_verification_system_options', h_options($verificationSystemsData, ''));
            }

            if(self::isAllowProfileVerification($row, $verifiedSystemsUser)) {
                $html->parse('profile_verification_unverified_my');
            }

            if($verifiedSystems) {
                $titleVerified = implode(l('profile_verified_systems_delimiter'), $verifiedSystems);
                if (Common::isOptionActiveTemplate('verified_account_title_list')) {
                    $titleVerified = lSetVars('verified_account_list_system', array('list_sysytem' => $titleVerified));
                }
                $html->setvar('profile_verification_verified', toAttr($titleVerified));

                // parse link only if I not verified yet
                if(count($verificationSystemsData) && $row['user_id'] == guid()) {
                    $html->setvar('profile_verification_show_class', 'profile_verification_show');
                } else {
                    $html->setvar('profile_verification_off_class', 'profile_verification_off');
                }

                $html->parse($block);
            }
        }
    }

    static function isDisabledBirthday()
    {
        $optionTmplName = Common::getTmplName();
        $isActiveBirthday = Common::isOptionActive('birthday_enabled', "{$optionTmplName}_join_page_settings");

        return Common::isOptionActiveTemplate('join_birthday_disabled') && !$isActiveBirthday;
    }

    static function getUserByLoginAndPassword($login, $password)
    {
        $user = DB::one('user', '`name` = ' . to_sql($login, 'Text') . ' OR `mail` = ' . to_sql($login, 'Text'));

        if($user && !self::passwordVerify($password, $user['password'])) {
            $user = null;
        }

        return $user;
    }

    static function preparePasswordForDatabase($password)
    {
        $isEncryptionEnabled = Common::isOptionActive('md5');

        if($isEncryptionEnabled) {
            $password = password_hash($password, PASSWORD_DEFAULT);
        }

        return $password;
    }

    static function passwordVerify($password, $realPassword)
    {
        $isCorrect = true;
        // Possible values: source, md5 or hash
        if($realPassword != $password && $realPassword != md5($password) && !password_verify($password, $realPassword)) {
            $isCorrect = false;
        }

        return $isCorrect;
    }

    public static function stopAppSubscriptions($uid)
    {
        $subscriptions = DB::select('payment_before', '`user_id` = ' . to_sql($uid) . ' AND `subscription_expiry_time` > ' . to_sql(time()));
        if($subscriptions) {
            foreach($subscriptions as $subscription) {
                if($subscription['system'] == 'iapgoogle payed') {
                    PayIapGoogle::subscriptionCancel($subscription['app_package_name'], $subscription['app_product_id'], $subscription['subscription_id']);
                }
            }
        }
    }

	public static function getDefaultOrientation()
	{
		$defaultOrientation = DB::result('SELECT `id` FROM `const_orientation` ORDER BY `default` DESC, `id` ASC LIMIT 1');
		return $defaultOrientation;
	}

}