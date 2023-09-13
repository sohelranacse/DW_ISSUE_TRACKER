<?php
/* (C) Websplosion LLC, 2001-2021

IMPORTANT: This is a commercial software product
and any kind of using it must agree to the Websplosion's license agreement.
It can be found at http://www.chameleonsocial.com/license.doc

This notice may not be removed from the source code. */

#выводы списков пользователей

class CUsers extends CHtmlList {

    var $m_on_page = 6;
    var $imessage = "";
    var $m_is_me = false;
    var $m_field_default = array();
    var $u_relations = array();
    var $u_orientations = array();
    var $u_iAmHereTo = array();
    var $list_orientations = array('M' => false, 'F' => false);
    var $locationDelimiter = ',<br>';
    var $locationDelimiterOne = ', ';
    var $locationDelimiterSecond = ', ';
    static $tmplName = '';
    static $tmplSet = '';
    static $parDisplay = '';
    static $parAjax = '';
    static $first = true;
    static $photoDefaultId = 0;
    static $guid = 0;
    var $profileStatusValue = '';
    var $profileStatusVarExists = false;
    var $isParentUserChartsParserActive = true;
    var $isEncounters = false;

    var $c_user_id = false;

    static $url = array(
        'users_rated_me_show_photo' => 'search_results.php?display=profile&uid={user_id}&show=gallery&photo_id={photo_id}#tabs-2',
        'users_rated_me_redirect' => 'search_results.php?display=rate_people',
        'profile_statistics_average' => 'increase_popularity.php',
    );

    function init() {
        parent::init();
        global $g;
        global $g_user;
        global $p;
        #$this->m_debug = "Y";

        self::$tmplName = Common::getOption('name', 'template_options');
        self::$tmplSet = Common::getOption('set', 'template_options');
        self::$parDisplay = get_param('display');
        self::$parAjax = get_param('ajax');
        self::$guid = guid();


        // added by sohel
        $this->c_user_id = EUsers_List::$c_user_id;
        if($this->c_user_id)
            self::$guid = $this->c_user_id;

        if(UserFields::isActive('const_relation')) {
            DB::query("SELECT * FROM const_relation");
            while ($rel = DB::fetch_row())
                $this->u_relations[$rel['id']] = $rel['title'];
        }

        $orientations = DB::rows('SELECT * FROM const_orientation', 0, true);
        if($orientations) {
            foreach ($orientations as $orientation) {
                $this->u_orientations[$orientation['id']] = $orientation;
            }
        }

        $rows = DB::rows('SELECT `id`, `title` FROM const_i_am_here_to ORDER BY id ASC', 0, true);
        foreach($rows as $row) {
            $this->u_iAmHereTo[$row['id']] = $row;
        }

        Cache::add('field_values_const_i_am_here_to', $this->u_iAmHereTo);

        $citySql = $g_user['city_id'];
        $stateSql = $g_user['state_id'];
        $countrySql = $g_user['country_id'];

        $display = get_param('display');
        $optionSet = Common::getOption('set', 'template_options');
        // Option creat template
        if (($optionSet == 'urban' || $optionSet == 'urban_moobile')  && $p == 'search_results.php' && in_array($display, array('','encounters','rate_people'))) {
            $citySql = get_param('city', $g_user['city_id']);
            $stateSql = get_param('state', $g_user['state_id']);
            $countrySql = get_param('country', $g_user['country_id']);
        }

        $this->m_sql_count = "SELECT COUNT(u.user_id) FROM user AS u " . $this->m_sql_from_add . "";
        $this->m_sql = "
      SELECT u.*, DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(birth, '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(birth, '00-%m-%d')) AS age,
      u.state AS state_title, u.country AS country_title, u.city AS city_title,
      IF(u.city_id=" . to_sql($citySql) . ", 1, 0) +
      IF(u.state_id=" . to_sql($stateSql) . ", 1, 0) +
      IF(u.country_id=" . to_sql($countrySql) . ", 1, 0) AS near
            " . to_sql($this->fieldsFromAdd, 'Plain') . "
      FROM user AS u
      " . $this->m_sql_from_add . "
    ";

        $this->m_field['user_id'] = array("user_id", null);
        $this->m_field['photo_id'] = array("photo", null);
        $this->m_field['name'] = array("name", null);
        $this->m_field['age'] = array("age", null);
        $this->m_field['relation'] = array("relation", null);
        $this->m_field['last_visit'] = array("last_visit", null);
        $this->m_field['city_title'] = array("city", null);
        $this->m_field['state_title'] = array("state", null);
        $this->m_field['country_title'] = array("country", null);
        $this->m_field['rating'] = array("rating", null, "desc");
        $this->m_field_default = $this->m_field;

        if($this->isEncounters) {
            $this->m_sql_where = Encounters::getFastSelectWhere($this->m_sql_where);
        }

    }

    function onItemCUsersImpact(&$html, $row, $i, $last) {
        $guid = self::$guid;
        if ($row['user_id'] == $guid) {

        } else {
            if($this->isParentUserChartsParserActive) {
                User::parseCharts($html, $row['user_id'], 'list');
            }
        }
    }

    function onItemCUsersEdge(&$html, $row, $i, $last) {
        if (!self::$parDisplay) {
            $profileDisplayType = Common::getOption('list_people_display_type', 'edge_general_settings');
            $numberRow = Common::getOptionInt('list_people_number_row', 'edge_general_settings');
            TemplateEdge::parseUser($html, $row, $numberRow, $profileDisplayType);
        }
    }

    function onItem(&$html, $row, $i, $last) {
        global $g;
        global $l;
        global $g_user;
        global $status_style;
        global $p;

        $guid = self::$guid;
        $optionSet = Common::getOption('set', 'template_options');
        $optionTmplName = Common::getOption('name', 'template_options');
        $isFreeSite = Common::isOptionActive('free_site');
        $display = get_param('display');

        $html->setvar('guid', self::$guid);

        if (lp('blank', 'all') == '') {
            $l['all']['blank'] = ' ';
        }
        #unset ($this->m_field);
        if(Common::isOptionActive('invite_friends')) {
            $html->parse('invite_on');
        }
        if (isset($this->m_field_default)) {
            $this->m_field = $this->m_field_default;
        }

        if ($row['user_id'] == self::$guid || $row['user_id'] == $this->c_user_id) { // update by sohel
            $this->m_is_me = true;
        } else {
            $this->m_is_me = false;
        }
        // profile status
        if($this->profileStatusVarExists || $html->varExists('status') || $html->varExists('profile_status')) {
            $this->profileStatusVarExists = true;
            $profileStatus = DB::row('SELECT * FROM profile_status WHERE user_id = ' . to_sql($row['user_id'], 'Number'), 1);

            if(isset($profileStatus['status']) && $profileStatus['status'] !== '') {
                $this->profileStatusValue = $profileStatus['status'];
                $row['status'] = $profileStatus['status'];

                if (!isset($status_style)) {
                    $status_style = 0;
                }
                $html->setvar("status", $row['status']);
                $html->setvar("status_style", $status_style++ % 6 + 1);
                if (Common::isOptionActive('profile_status')) {
                    $html->parse("user_status", false);
                }
            } else {
                $html->setblockvar("user_status", "");
            }
        }
        // profile status

        if ($this->m_is_me) {
            #print $row['user_id'].":".$row['name']."<br>";
            $cannot_self = l('cannot_self');
            $html->setvar("cannot_self_1", "onclick='alert(\"$cannot_self\"); return false;' ");
            $html->setvar("cannot_self_2", "onclick='alert(\"$cannot_self\"); return false;' ");
        } elseif (self::$guid && $html->varExists('cannot_self_1') && DB::row(
                        "SELECT * FROM friends_requests WHERE (accepted = 1 AND ((user_id=" . to_sql($row['user_id'], 'Number') . " AND friend_id=" . $g_user['user_id'] . ") " .
                        "OR (user_id=" . $g_user['user_id'] . " AND friend_id=" . to_sql($row['user_id'], 'Number') . "))) OR (accepted = 0 AND (user_id=" . $g_user['user_id'] . " AND friend_id=" . to_sql($row['user_id'], 'Number') . "))", 4)) {
            #print $row['user_id'].":".$row['name']."<br>";
            $cannot_self = l('allready_friend');
            $html->setvar("cannot_self_1", "onclick='alert(\"$cannot_self\"); return false;' ");
            $html->setvar("cannot_self_2", "");
        } else {
            $html->setvar("cannot_self_1", "");
            $html->setvar("cannot_self_2", "");
        }

        $friend_bookmark = false;
        $cannotSelf3 = '';
        $cannot_self = '';
        if ($this->m_is_me) {
            $cannot_self = l('Cannot self');
        } elseif (self::$guid) {
            if (Common::isOptionActive('bookmarks')) {
                if (User::isBookmarkExists($g_user['user_id'], $row['user_id'])) {
                    $cannot_self = l('allready_bookmarked_friend');
                }
            }
        }

        if ($cannot_self) {
            $cannotSelf3 = "onclick='alert(\"$cannot_self\"); return false;' ";
        }
        $html->setvar('cannot_self_3', $cannotSelf3);

        #p($row['last_visit']);
        #p(date('Y-m-d h:i:s'));

        if ($row['user_id'] == "") {
            $row['photo_id'] = "";
            $row['name'] = l('user_not_exists');
            $this->m_field['name'][1] = $row['name'];
        }
        $row['last_visit_date'] = $row['last_visit'];
        $row['last_visit'] = time_mysql_dt2u($row['last_visit']);

        $isShowStatus = true;
        if ($optionSet == 'urban') {
            $isShowStatus = !User::isInvisibleModeOptionActive('set_hide_my_presence');
        }

        $blockStatusOnline = 'status_online_profile';
        if ((((time() - $row['last_visit']) / 60) < $g['options']['online_time']) && $isShowStatus) {
            $this->m_field['last_visit'][1] = l('online_now');
            //$status = l('on_the_site_now');//profile_head
            $html->parse('status_online', false);
            $html->clean('status_offline');
            if($html->blockExists($blockStatusOnline)) {
                $html->setVar("{$blockStatusOnline}_title", l('on_the_site_now'));
                $html->parse($blockStatusOnline, false);
            }
        } else {
            $lastDate = Common::dateFormat($row['last_visit'], 'users_last_visit_date', false);
            $this->m_field['last_visit'][1] = $lastDate;
            //$status = lSetVars('was_online', array('date' => date("j F Y", $row['last_visit'])));//profile_head
            $html->parse('status_offline', false);
            $html->clean('status_online');
        }
        if (!$this->m_is_me && $html->blockExists("{$blockStatusOnline}_bl")) {
            $html->parse("{$blockStatusOnline}_bl", false);
        }
        // URBAN
        //$html->setvar('last_visit_urban', $status);//profile_head
        // URBAN

        $photoDefaultSize = 's';
        if (Common::isMobile()) {
            $photoDefaultSize = 'r';
        }

        $this->m_field['photo_id'][1] = User::getPhotoDefault($row['user_id'], $photoDefaultSize, false, $row['gender']);



        // URBAN
        $html->setvar('photo_m', User::getPhotoDefault($row['user_id'], 'm', false, $row['gender']));
        if($html->varExists('photo_mm')) {
            $html->setvar('photo_mm', User::getPhotoDefault($row['user_id'], 'mm', false, $row['gender']));
        }
        $html->setvar('photo_r', User::getPhotoDefault($row['user_id'], 'r', false, $row['gender']));

        $blockPhotoMain = 'photo_main';
        $sizePhotoMain = 'b';
        $sizePhotoMainTemplate = Common::getOption('profile_photo_main_size', 'template_options');
        if ($sizePhotoMainTemplate) {
            $sizePhotoMain = $sizePhotoMainTemplate;
        }
        $photoMain = User::getPhotoDefault($row['user_id'], $sizePhotoMain, false, $row['gender']);
        $photoInfo = User::getPhotoDefault($row['user_id'], 'b', true, $row['gender'], DB_MAX_INDEX , false, true);
        $photoId = User::getPhotoDefault($row['user_id'], 'b', true, $row['gender']);
        self::$photoDefaultId = $photoId;
        if (!$photoId) {
            if($display == 'encounters' && self::$tmplName != 'impact'){

            } else {
                if ($row['user_id'] == self::$guid && $html->blockExists('profile_main_photo_add')) {
                    $html->parse("{$blockPhotoMain}_photo_add");
                }
                $html->parse('main_cursor_def');
            }
        } elseif(CProfilePhoto::isPhotoOnVerification($photoInfo['visible'])) {
            $html->parse("{$blockPhotoMain}_not_checked", false);
        }
        $html->setvar($blockPhotoMain . '_id', $photoId);
        $html->setvar($blockPhotoMain, $photoMain);
        $html->setvar($blockPhotoMain . '_title',he($photoInfo['description']));
        if($html->varExists($blockPhotoMain . '_offset')) {
            $html->setvar($blockPhotoMain . '_offset', User::photoOffset($row['user_id'], $photoId, false));
        }
        $photoWidth = Common::getOption('profile_photo_w', 'template_options');
        if ($photoWidth) {

            $photoFileSizes = array($photoWidth, $photoWidth);

            if($sizePhotoMain == 'b') {
                if ($row['user_id'] != $g_user['user_id']
                        && $photoInfo['private'] == 'Y' && !User::isFriend($row['user_id'], $g_user['user_id'])) {
                    $photoPlugHeight = Common::getOption('profile_plug_photo_h', 'template_options');
                    $photoFileSizes = array($photoWidth, $photoPlugHeight);
                } else {

                    $photoFileSizes = CProfilePhoto::getAndUpdatePhotoSize($photoInfo, $photoMain, $photoWidth);
                    /*
                    if($photoInfo['width'] == 0 || $photoInfo['height'] == 0) {
                        $tmpPhotoPath = explode('?', $photoMain);
                        $filePhoto = $g['path']['dir_files'] . $tmpPhotoPath[0];
                        if(file_exists($filePhoto)) {
                            $infoPhoto = @getimagesize($filePhoto);
                            if(isset($infoPhoto[1])) {
                                $photoFileSizes = array($infoPhoto[0], $infoPhoto[1]);
                                DB::update('photo', array('width' => $infoPhoto[0], 'height' =>  $infoPhoto[1]), 'photo_id = ' . to_sql($photoInfo['photo_id']));
                            }
                        }
                    } else {
                        $photoFileSizes = array($photoInfo['width'], $photoInfo['height']);
                    }*/
                }
            } else {
                $photoFileSizes = array(Common::getOption('medium_x', 'image'), Common::getOption('medium_y', 'image'));
            }

            $photoHeight = round($photoWidth * $photoFileSizes[1] / $photoFileSizes[0]);

            $html->setvar($blockPhotoMain . '_width', $photoWidth);
            $html->setvar($blockPhotoMain . '_height', $photoHeight);
            $html->setvar($blockPhotoMain . '_line_height', $photoHeight);
        }
        // URBAN MOBILE
        /*$blockPhotoMainDescription = $blockPhotoMain . '_description';
        if($html->blockexists($blockPhotoMainDescription) && $row['user_id'] == $g_user['user_id']) {
            $photoMainDescription = DB::result("SELECT `description` FROM `photo` WHERE `photo_id` = " . to_sql($photoId));
            if (empty($photoMainDescription)) {
                $html->parse($blockPhotoMainDescription, false);
            } else {
                $html->setvar($blockPhotoMainDescription, $photoMainDescription);
            }
        }*/
        // URBAN MOBILE
        // URBAN

        /*
          if ($row['user_id'] != "" and !isset($row['photo_id'])) $row['photo_id'] = profile_photo($row['user_id']);
          if ($row['photo_id'] != "" and file_exists($g['path']['dir_files'] . "photo/" . $row['user_id'] . "_" . $row['photo_id'] . "_s.jpg")) $this->m_field['photo_id'][1] = "photo/" . $row['user_id'] . "_" . $row['photo_id'] . "_s.jpg";
          elseif (!isset($row['gender'])) $this->m_field['photo_id'][1] = "nophoto_s.jpg";
          else $this->m_field['photo_id'][1] = "nophoto_" . $row['gender'] . "_s.jpg";
         */

        $this->m_field['city_title'][1] = $row['city'] != "" ? l($row['city']) : '';//l('blank');
        $this->m_field['state_title'][1] = $row['state'] != "" ? l($row['state']) : '';//l('blank');
        $this->m_field['country_title'][1] = $row['country'] != "" ? l($row['country']) : '';// l('blank');



        $this->m_field['city_title'][1] = (($this->m_field['city_title'][1] == "" or $this->m_field['city_title'][1] == "0") ? '' : $this->m_field['city_title'][1]);
        $this->m_field['state_title'][1] = (($this->m_field['state_title'][1] == "" or $this->m_field['state_title'][1] == "0") ? '' : $this->m_field['state_title'][1]);
        $this->m_field['country_title'][1] = (($this->m_field['country_title'][1] == "" or $this->m_field['country_title'][1] == "0") ? '' : $this->m_field['country_title'][1]);

        $locationDelimiter = '';

        if (trim($this->m_field['city_title'][1]) != '') {
            $locationDelimiter = $this->locationDelimiter;
            $html->setvar('location_delimiter', $locationDelimiter);
            if ($this->m_field['country_title'][1] == ''){
                $html->setvar('location_delimiter_mobile', '');
            } else {
                $html->setvar('location_delimiter_mobile', $this->locationDelimiterOne);
            }
        } else {
            $html->setvar('location_delimiter_mobile', '');
        }

        if (trim($this->m_field['city_title'][1]) != ''
                && trim($this->m_field['state_title'][1]) != '') {
            $html->setvar('location_delimiter_one', $this->locationDelimiterOne);
        } else {
            $html->setvar('location_delimiter_one', '');
        }

        if (trim($this->m_field['country_title'][1]) != ''
                && (trim($this->m_field['state_title'][1]) != '' || trim($this->m_field['city_title'][1]) != '')) {
            $html->setvar('location_delimiter_second', $this->locationDelimiterSecond);
        } else {
            $html->setvar('location_delimiter_second', '');
        }

        if (trim($this->m_field['city_title'][1]) == ''
                && trim($this->m_field['state_title'][1]) == ''
                    && trim($this->m_field['country_title'][1]) == '') {
            $this->m_field['state_title'][1] = l('location_unknown');
        }

        if ($this->m_field['city_title'][1] != ''){
            $html->setvar('city', $this->m_field['city_title'][1]);
            $html->parse('city_title', false);
        }else{
            $html->setblockvar('city_title', '');
        }

        if ($this->m_field['country_title'][1] != ''){
            $html->setvar('country', $this->m_field['country_title'][1]);
            $html->parse('country_title', false);
        }else{
            $html->setblockvar('country_title', '');
        }

        $html->setvar('state', $this->m_field['state_title'][1]);
        if ($this->m_field['state_title'][1] != ''
                && $this->m_field['state_title'][1] != l('location_unknown')){
            $html->parse('state_title', false);
        }else{
            $html->setblockvar('state_title', '');
        }
        /*if (isset($this->u_orientations[$row['orientation']])) {
            $orientation_row = $this->u_orientations[$row['orientation']];
            if ($orientation_row['free'] != 'none' and $orientation_row['free'] != '' and $orientation_row['free'] != 'N') {
                $row['type'] = $orientation_row['free'];
                $row['gold_days'] = 1;
            } elseif ($orientation_row['free'] == 'Y') {
                $row['type'] = 'platinum';
                $row['gold_days'] = 1;
            }
            if (!isset($row['type']))
                $row['type'] = 'platinum';
        }*/
        $orientation_row = User::getOrientationInfo($row['orientation']);
        $access = User::paidLevel($row['type'], $row['gold_days'], $orientation_row['free']);
        $row['type'] = $access['type'];
        $row['gold_days'] = $access['gold_days'];

        $html->setvar("user_id", $row['user_id']);
        $html->setvar("user_profile_link", User::url($row['user_id'], $row));
        $html->setvar("name", $row['name']);
        $html->setvar("name_short", User::nameShort($row['name']));
        $html->setvar("name_one_letter", User::nameOneLetterFull($row['name']));
        $html->setvar("name_one_letter_short", User::nameOneLetterShort($row['name']));
        $html->setvar("age", $row['age']);
        $orientation = isset($orientation_row['title']) ? $orientation_row['title'] : '';
        $html->setvar("orientation", l($orientation));
        if (UserFields::isActive('orientation')){
            $html->setvar("i_am", l($orientation));
            $html->parse('orientation', FALSE);
        }
        $this->m_field['relation'][1] = isset($this->u_relations[$row['relation']]) ? l($this->u_relations[$row['relation']]) : l('blank');
        //exclude Relationship type: blank
        if ($this->m_field['relation'][1] != l('blank') && UserFields::isActive('relation')){
            $html->setvar('relation', $this->m_field['relation'][1]);
            $html->parse('relationship', FALSE);
        }else{
            $html->setblockvar('relationship', '');
        }

        //echo '<pre>'; print_r($row); die;

        $icon_badge='<span></span>';
        if($row['is_verified']=='Y'){
            $icon_badge = '<span class="icon_badge"></span>';
        }
        $html->setvar('icon_badge', $icon_badge);

        #echo ((!$this->m_is_me and $g_user['user_id'] > 0 and ((time() - $row['last_visit']) / 60) < $g['options']['online_time'] and (payment_check_return('im') and payment_check_return('im', $row['type'], $row['gold_days']))) ? 'yes' : 'no');

        $block = 'status_3dcity_profile';
        if($html->blockexists($block) && Common::isModuleCityActive() && City::isUserOnline($row['user_id'])) {
            $html->parse($block, false);
        }


        if (Common::isOptionActive('postcard')) {
            $html->parse('postcard_module', false);
        }

        if ($row['gold_days'] > 0 and $row['type'] != 'none')
            $html->parse("gold", false);
        else
            $html->setblockvar("gold", "");

        if ($row['gold_days'] == 0)
            $html->parse("nogold", false);
        else
            $html->setblockvar("nogold", "");

        if ($row['gender'] == 'M')
            $html->parse("male", false);
        else
            $html->setblockvar("male", "");

        if ($row['gender'] == 'F')
            $html->parse("female", false);
        else
            $html->setblockvar("female", "");

        //Orientation in the list of permissible
        $this->list_orientations[$row['gender']] = true;

        if ($this->m_is_me) {
            $html->parse("name_buttons_inactive", false);

            if (Common::isOptionActive('bookmarks')) {
                $html->setblockvar("name_buttons", "");
            }
            $html->parse("wink_inactive", false);
        } else {
            if (Common::isOptionActive('bookmarks')) {
                $html->parse("name_buttons", false);
            }
            $html->setblockvar("name_buttons_inactive", "");

            $html->setblockvar("wink_inactive", "");
        }

        if (isset($row['gender']) && UserFields::isActive('orientation')) {
            if ($row['gender'] == 'M') {
                $html->parse("man", false);
                $html->setblockvar("woman", "");
            } elseif ($row['gender'] == 'F') {
                $html->parse("woman", false);
                $html->setblockvar("man", "");
            }
        }

        if (Common::isOptionActive('couples') && $html->blockexists("couple")) {
            if (isset($row['couple']) and $row['couple'] == 'Y' and $html->blockexists("couple")) {
                $row['couple_name'] = DB::result("SELECT name FROM user WHERE user_id=" . $row['couple_id'] . "", 0, 2);
                if ($row['couple_name'] != "") {
                    $html->setvar("couple_name", $row['couple_name']);
                    $html->parse("couple", false);
                } else
                    $html->setblockvar("couple", "");
            } else
                $html->setblockvar("couple", "");
        }

        if (self::$guid && !$this->m_is_me) {
            $blockedOptions = null;
            if(Common::getOptionSetTmpl() !== 'urban') {
                $blockedOptions = User::blockedOptions($row['user_id'], self::$guid);
            }
        }
        $interactiveOptionsCount = 0;
        $interactiveOptions = array('games', 'videochat', 'audiochat', 'im');
        foreach ($interactiveOptions as $interactiveOption) {
            if (self::$guid and !$this->m_is_me and Common::isOptionActive($interactiveOption) and (!isset($blockedOptions[$interactiveOption]) || $blockedOptions[$interactiveOption] == 0)) {
                $online = ((time() - $row['last_visit']) / 60) < $g['options']['online_time'];
                if ($interactiveOption == 'im') {
                    $online = true;
                }
                if ($online == true && payment_check_return($interactiveOption) && payment_check_return($interactiveOption, $row['type'], $row['gold_days'])) {
                    $html->parse($interactiveOption, false);
                    $interactiveOptionsCount++;
                    if ($interactiveOption == 'im') {
                        $html->setblockvar('im_off', '');
                        $interactiveOptionsCount--;
                    }
                    if ($interactiveOption == 'games')
                         $html->parse('games_on_no_photo',false);
                } else {
                    $html->setblockvar($interactiveOption, '');
                    $interactiveOptionsCount--;
                    if ($interactiveOption == 'im') {
                        $html->parse('im_off', false);
                        $html->setblockvar('im', '');
                    }
                    if ($interactiveOption == 'games')
                        $html->setblockvar('games_on_no_photo', '');
                }
            }
        }

        if (Common::isOptionActive('im')) {
            if (self::$guid && $this->m_is_me) {
                $html->setblockvar('im', '');
                $html->parse('im_off', false);
            }
            $html->parse('im_module', false);
        }
        if(Common::isOptionActive('mail')) {
            $html->parse('mail_on', false);
            $html->parse('mail_on2', false);
            $html->parse('mail_on_no_photo', false);
        }
        if(Common::isOptionActive('wink')) {
            if ($row['user_id'] != $guid && $html->blockExists('wink_on_active')
                && DB::count('users_interest', '`user_from` = ' . to_sql($guid) . ' AND `user_to` = ' . to_sql($row['user_id']))) {
                $html->parse('wink_on_active', false);
            }
            $html->parse('wink_on', false);
        }
        if (get_param('name') == guser('name')) {
            $html->parse('edit_profile_pictures', false);
        }
        if(!$this->m_is_me){
            $html->parse('is_not_me', false);
        }

        if (isset($g['options']['gallery']) and $g['options']['gallery'] == "Y" and $html->blockexists("gallery")) {
            if (DB::result("SELECT id FROM gallery_albums  WHERE user_id=" . $row['user_id'] . " LIMIT 1", 0, 2))
                $html->parse("gallery", true);
        }
        if (isset($g['options']['biorythm']) and $g['options']['biorythm'] == "Y") {
            $html->parse("biorythm", true);
        }
        if (isset($g['options']['blogs']) and $g['options']['blogs'] == "Y" and $html->blockexists("blog")) {
            if ($row['blog_posts'] > 0) {
                $html->parse('blog', true);
            }
        }
        if (isset($g['options']['recorder']) and $g['options']['recorder'] == "Y" and isset($row['record']) and $row['record'] == "Y") {
            $html->setvar("unique", str_replace('.', '_', domain()));
            $html->parse("recorder", true);
            $html->parse("recorder_swf", true);
        }

        if ($html->blockexists('friends')) {
            $sqlBase = 'FROM friends_requests AS f
                JOIN user AS u ON u.user_id = f.user_id
                JOIN user AS u2 on u2.user_id = f.friend_id
                WHERE f.accepted = 1
                    AND (f.user_id = ' . to_sql($row['user_id'], 'Number') . '
                        OR f.friend_id = ' . to_sql($row['user_id'], 'Number') . ')';

            $sql = 'SELECT COUNT(*) ' . $sqlBase;
            $friendsCount = DB::result($sql, 0, 2);
            $friendsLimit = 3;

            if ($friendsCount) {

                $sql = 'SELECT f.*, u.name AS name, u2.name AS name2 ' .
                        $sqlBase . ' ORDER BY activity DESC
                    LIMIT ' . $friendsLimit;
                DB::query($sql, 2);
                $separatorParse = false;
                while ($friend = DB::fetch_row(2)) {

                    if ($separatorParse) {
                        $html->parse('friend_separator', false);
                    }

                    $friend['fr_user_id'] = ($friend['user_id'] == $row['user_id']) ? $friend['friend_id'] : $friend['user_id'];
                    $friend['name'] = ($friend['user_id'] == $row['user_id']) ? $friend['name2'] : $friend['name'];

                    $html->setvar("fr_id", $friend['user_id']);
                    $html->setvar("fr_name", $friend['name']);

                    $separatorParse = true;

                    $html->parse('friend', true);
                }

                if ($friendsCount > $friendsLimit) {
                    $html->setvar('friends_more_view', str_replace('{count}', ($friendsCount - $friendsLimit), l('friends_more_view')));
                    $html->parse('friends_more');
                }

                $html->parse('friends', true);
            }
        }

        if (isset($row['is_photo']) && $row['is_photo'] == 'Y') {
            $html->parse($this->m_name . "_photo", false);
            $html->setblockvar($this->m_name . "_nophoto", "");
        } else {
            $html->parse($this->m_name . "_nophoto", false);
            $html->setblockvar($this->m_name . "_photo", "");
        }


        #foreach ($row as $k => $v) {
        # if (!isset($this->m_field[$k][1]) and isset($this->m_field[$k][0])) {
        #   $this->m_field[$k][1] = ($v == 0 ? ' ' : $v);
        # }
        #}


        $html->setvar('display_profile', User::displayProfile());
        $html->setvar('display_wall', User::displayWall());
        if (Wall::isActive()) {
            $html->parse('wall');
        }
        $parseFavoriteAdd = false;
        if(Common::isOptionActive('favorite_add')) {
            $isFavorited = User::isFavoriteExists(self::$guid, $row['user_id']);
            #if (empty($isFavorited) && !$this->m_is_me) {
            if (!$isFavorited) {
                $parseFavoriteAdd = true;
                $html->parse('favorite_add', false);
            } else {
                $html->setblockvar('favorite_add', "");
            }
        }
        if (isset($row['isMobile']) && $row['isMobile'] != 'true' && !$this->m_is_me) {
            $html->parse("mobile_off", false);
        } else {
            $interactiveOptionsCount = 0;
            $html->setblockvar('mobile_off', '');
        }
        if (Common::isOptionActive('contact_blocking')) {
            $html->parse('contact_blocking', false);
        }
        if (!$this->m_is_me && ($interactiveOptionsCount > 0 || $parseFavoriteAdd)) {
            $html->parse('interactive_options', false);
        }

        $blockPhotoCount = 'photo_count';
        if($html->blockexists($blockPhotoCount)) {
            $sql = 'SELECT COUNT(*) FROM photo
                WHERE user_id = ' . to_sql($row['user_id']) . '
                    AND visible = "Y"';
            $photoCount = DB::result($sql, 0, 1);
            if($photoCount) {
                $html->setvar($blockPhotoCount, $photoCount);
                $html->parse($blockPhotoCount, false);
            } else {
                $html->clean($blockPhotoCount);
            }
        }

        $blockIAmHereTo = 'i_am_here_to';
        if($html->blockexists($blockIAmHereTo)) {
            if(isset($row[$blockIAmHereTo])) {
                $id = $row[$blockIAmHereTo];
                $iAmHereToTitle = isset($this->u_iAmHereTo[$id]) ? $this->u_iAmHereTo[$id] : false ;
                if($iAmHereToTitle) {
                    $html->setvar($blockIAmHereTo . '_class', UserFields::getArrayNameIcoField($blockIAmHereTo, $id, 'normal'));
                    $html->setvar($blockIAmHereTo . '_value', $iAmHereToTitle);
                    $html->parse($blockIAmHereTo, false);
                } else {
                    $html->clean($blockIAmHereTo);
                }
            }
        }

        $blockInterests = 'interests';
        if(Common::isOptionActive('show_interests_search_results_urban') && $html->blockexists($blockInterests)) {
            $blockInterestsItem = 'interest';
            $sql = 'SELECT i.category, MAX(ui.id) AS mid
                      FROM user_interests AS ui
                      JOIN interests AS i ON i.id = ui.interest
                     WHERE ui.user_id = ' . to_sql($row['user_id']) . '
                     GROUP BY category ORDER BY mid DESC
                     LIMIT 4';
            $interests = DB::rows($sql, 1);
            if($interests) {
                foreach($interests as $interest) {
                    $html->setvar($blockInterestsItem . '_category', $interest['category']);
                    $html->setvar($blockInterestsItem . '_class', UserFields::getArrayNameIcoField($blockInterests, $interest['category'], 'search'));
                    $html->parse($blockInterestsItem);
                }
                $html->parse($blockInterests, false);
                $html->clean($blockInterestsItem);
            } else {
                $html->clean($blockInterests);
            }
        }

        if ($html->blockexists('user_lock') && Common::isOptionActive('contact_blocking')) {
            $html->parse('user_lock', false);
        }

        $blockProfileVisitorsNew = 'profile_visitors_new';
        if ($html->blockexists($blockProfileVisitorsNew)) {
            $sql = 'SELECT COUNT(*) FROM `users_view`
                     WHERE `user_from` = ' . to_sql($g_user['user_id'], 'Number') .
                     ' AND `user_to` = ' . to_sql($row['user_id'], 'Number');
            if (DB::result($sql, 0, 1)) {
                $html->clean($blockProfileVisitorsNew);
            } else {
                $html->parse($blockProfileVisitorsNew, false);
            }
        }
        if (Common::isOptionActive('list_users_custom_page', 'template_options')) {
            if (in_array($p, array('users_viewed_me.php', 'users_rated_me.php', 'mail_whos_interest.php'))) {
                if (self::$first && !get_param('ajax', 0)) {
                    $html->parse('border_none', false);
                    $html->clean('border_top');
                } else {
                    $html->parse('border_top', false);
                    $html->clean('border_none');
                }
                self::$first = false;
            }
            if ($p == 'users_viewed_me.php') {
                $refTitle = timeAgo($row['users_view_created'], 'now', 'string', 60, 'second');
                $ref = $row['users_view_ref'];
                if ($ref != '' && self::$tmplName != 'impact') {
                    $url = array('people_nearby' => 'search_results.php',
                                 'encounters' => 'search_results.php?display=encounters',
                                 'spotlight' => 'increase_popularity.php',
                                 'rate_people' => 'search_results.php?display=rate_people',
                                 'wall' => 'wall.php',
                           );
                    if (isset($url[$ref])) {
                        $vars = array('time' => $refTitle,
                                      'url' => $url[$ref],
                                      'referer' => l($ref),
                                );
                        $refTitle = Common::lSetLink('profile_visitor_referer_' . $row['gender'], $vars);
                    }
                }
                $html->setvar('profile_visitor_referer', $refTitle);
                $html->setvar('profile_visitors_id', $row['users_view_id']);
                if ($html->varExists('item_time_ago')) {
                    $html->setvar('item_time_ago', $refTitle);
                }
                if ($html->varExists('item_id')){
                    $html->setvar('item_id', $row['users_view_id']);
                }
            } elseif ($p == 'users_rated_me.php') {
                $blockUsersRated = 'photo_rated';
                $html->setvar($blockUsersRated . '_id', $row['photo_rated_id']);

                if ($row['photo_rated_photo_id'] <= $g_user['last_photo_visible_rated']) {
                    $vars = array('url' => self::$url['users_rated_me_show_photo'],
                                  'rating' => $row['photo_rated_rating'],
                                  'user_id' => $g_user['user_id'],
                                  'photo_id' => $row['photo_rated_photo_id']);
                    $gave = Common::lSetLink('gave_your_photo_a', $vars);
                } else {
                    $gave = Common::lSetLink('rate_other_people_to_see_ratings', array('url' =>self::$url['users_rated_me_redirect']));
                }
                $html->setvar($blockUsersRated . '_gave_your_photo_a', $gave);
            }
        }

        User::isBlockedMeSetvar($html, $row['user_id']);

        $blockGifts = 'gifts_enabled';
        if ($html->blockExists($blockGifts)) {
            if (Common::isOptionActive($blockGifts)) {
                $html->parse($blockGifts, false);
            } else {
                $html->parse('gifts_disabled', false);//??? not used
            }
        }

        Common::parseErrorAccessingUser($html);

        $isBlockedUser = 0;
        if ($html->varExists('is_blocked_user')) {
            if (self::$guid != $row['user_id']) {
                $isBlockedUser = User::isEntryBlocked($g_user['user_id'], $row['user_id']);
            }
            $html->setvar('is_blocked_user', $isBlockedUser);
        }
        if ($html->varExists('sign_blocked_user_hide') && !$isBlockedUser) {
            $html->setvar('sign_blocked_user_hide', 'sign_blocked_user_hide');
        }

        if (self::$guid != $row['user_id']) {

            $isFriendRequested = null;
            if ($p == 'my_friends.php' && $html->blockExists('friend_approve')) {
                $isFriendRequested = User::isFriendRequestExists($row['user_id'], $g_user['user_id']);
                if ($isFriendRequested && $isFriendRequested != $g_user['user_id']) {
                    $html->parse('friend_approve', false);
                } else {
                    $html->clean('friend_approve');
                }
            }

            $block = 'block_report';
            if ($html->blockExists($block)) {
                if (Common::isOptionActive('friends_enabled') && $p == 'profile_view.php') {
                    $isFriend = User::isFriend($row['user_id'], $g_user['user_id']);
                    $title = l('add_to_friends');
                    $action = 'request';

                    if ($isFriend) {
                        $action = 'remove';
                        $title = l('remove_from_friends');
                    } else {
                        if($isFriendRequested === null) {
                            $isFriendRequested = User::isFriendRequestExists($row['user_id'], $g_user['user_id']);
                        }
                        if($isFriendRequested) {
                            if ($isFriendRequested == $g_user['user_id']) {
                                $action = 'remove';
                                $title = l('remove_request');
                            } else {
                                $title = l('approve_friend_requests');
                            }
                        }
                    }

                    $html->setvar("{$block}_friend_action", $action);
                    $html->setvar("{$block}_friend_title", $title);
                    $html->parse("{$block}_friend_add", false);
                }

                if (Common::isOptionActive('contact_blocking')) {
                    $titleBlocked = l('tip_report_block');
                    if (User::isEntryBlocked(self::$guid, $row['user_id'])) {
                        $titleBlocked = l('tip_report_unblock');
                    }
                    $html->setvar("{$block}_blocked_title", $titleBlocked);
                    $html->parse("{$block}_blocked", false);
                }
                if (!in_array(self::$guid, explode(',', $row['users_reports']))) {
                    $html->parse("{$block}_user", false);
                }
                $html->parse($block, false);
            }
        }

        if($html->varExists('url_profile')) {
            if ($html->varExists('url_profile_params')) {
                $paramsLink = array();
                if ($p == 'search_results.php' && !$display) {
                    $paramsLink = array('ref' => 'people_nearby',
                                        'ref_offset' => get_param('offset', 1));
                }
                $html->setvar('url_profile_params', http_build_query($paramsLink));
            }
            // $html->setvar('url_profile', User::url($row['user_id'], $row));
            $html->setvar('url_profile', $row['name_seo']); // added by sohel
        }

        // PDF FILE
        $row_user = User::getInfoFull($row['user_id'], 2);
        if($row_user['profile_pdf']){

            if($row_user['user_id'] == guid() || $this->c_user_id)
                $html->parse('delete_my_pdf', false);

            
            $html->setvar("uploaded_btn_style", "display:none");

            $html->setvar("view_pdf_class", "yes_profile_pdf");
            $html->setvar("pdf_file_name", $row_user['profile_pdf']);

            $html->parse('view_pdf', false);
        } else {
            $html->setvar("uploaded_btn_style", "");
            $html->setvar("view_pdf_class", "no_profile_pdf");

            if($row_user['user_id'] == guid() || $this->c_user_id)
                $html->parse('upload_my_pdf', false);
        }

        /*if($row_user['user_id'] == guid() && ($row_user['facebook_id'] == '' || $row_user['google_plus_id'] == '' || $row_user['linkedin_id'] == '')) { // personal profile
            $html->parse('verify_social_login', false);
        }*/

        // added by sohel

        if($row_user['user_id'] == guid() || $this->c_user_id) {
            $blFooterMember = 'footer_member';
            if ($html->blockExists($blFooterMember)) {
                if($row_user['user_id'] == guid())
                    User::parseProfileVerification($html, null, 'profile_verification_unverified_my');

                if($row_user['user_id'] == guid() || $this->c_user_id)
                    if (Common::isCreditsEnabled())
                        $html->parse($blFooterMember . '_increase');

                // nid
                if($row_user['nid_verify_status'] == 0) { // default
                    $html->setvar('nid_verify_text', "");
                    $html->parse('nid_upload_button', false);
                }

                elseif($row_user['nid_verify_status'] == 1) { // verified
                    $html->setvar('nid_verify_text', "<span style='color: green'>".l('verified')."</span>");
                    $html->setvar('nid_data', $row_user['nid_data']);
                    $html->parse('view_verified_nid', false);
                }

                elseif($row_user['nid_verify_status'] == 2 || $row_user['nid_verify_status'] == 3) { // uploaded, reuploaded
                    $html->setvar('nid_verify_text', "<span>".l('verification_pending')."</span>");
                    $html->setvar('nid_data', $row_user['nid_data']);
                    $html->parse('uploaded_nid', false);
                }

                elseif($row_user['nid_verify_status'] == 4) { // rejeted, then can reupload
                    $html->setvar('nid_data', $row_user['nid_data']);
                    $html->setvar('nid_verify_text', "<span style='color: red'>".l('verification_failed')."</span>");
                    $html->parse('rejected_nid', false);
                }

                    
                $html->parse($blFooterMember, false);
            }
        }
        // added by sohel => end


        if ($html->blockExists('users_list_item_hide') && get_param('upload_search_page')) {
            $html->parse('users_list_item_hide', false);
        }

        if ($html->varExists('user_gender')) {
            $html->setvar('user_gender', $row['gender'] == 'M' ? l('man') : l('woman'));
        }

        if ($html->varExists('user_orientation')) {
            $orientationTitle = '';
            if (isset($this->u_orientations[$row['orientation']])) {
                $orientationTitle = l($this->u_orientations[$row['orientation']]['title']);
            }
            $html->setvar('user_orientation', $orientationTitle);
        }

        $block = 'mutual_attraction_active';
        if ($html->blockExists($block) && self::$guid != $row['user_id']) {
            if (Encounters::isWantsToMeet($row['user_id'])) {
                $html->setvar("{$block}_title", l('unlike'));
                $html->setvar("{$block}_name", l('profile_liked'));
                $html->parse($block, false);
            } else {
                $html->setvar("{$block}_title", l('like'));
                $html->setvar("{$block}_name", l('profile_like'));
                $html->clean($block);
            }
        }

        $onItemTemplateMethod = 'onItemCUsers' . $optionTmplName;
        if (method_exists('CUsers', $onItemTemplateMethod)) {
            $this->$onItemTemplateMethod($html, $row, $i, $last);
        }

        $block = 'profile_verification_icon';

        if ($html->blockExists($block) && Common::isOptionActive('profile_verification_enabled') && count(Social::getActiveItems())) {

            $verificationSystems = Social::getActiveItems();
            $verifiedSystems = array();

            $parseProfileVerificationIcon = false;

            $verificationSystemsData = array();
            foreach($verificationSystems as $verificationSystemKey => $verificationSystemValue) {
                $profileSystemKey = $verificationSystemKey . '_id';
                if(isset($row[$profileSystemKey]) && $row[$profileSystemKey]) {
                    $parseProfileVerificationIcon = true;
                    break;
                }
            }

            if($parseProfileVerificationIcon) {
                $html->parse($block, false);
            } else {
                $html->clean($block);
            }
        }

        $block = 'group_user_icon';
        if ($html->blockExists($block)) {
            if($row['under_admin'])
                $html->parse($block, false);
            else
                $html->clean($block);
        }


        parent::onItem($html, $row, $i, $last);
    }

    function onPostParse(&$html,$row=array()) {
        if (Common::getTmplSet() == 'old' && User::isGenderViewActive()) {

            $gender = 'all';

            $isSearch = User::isListOrientationsSearch();
            if ($isSearch === false) {
                if ($this->list_orientations['M'] && $this->list_orientations['F']) {
                    $gender = 'all';
                } elseif ($this->list_orientations['M']) {
                    $gender = 'M';
                } elseif ($this->list_orientations['F']) {
                    $gender = 'F';
                }
            } else {
                $sql = 'SELECT gender FROM const_orientation GROUP BY gender';
                DB::query($sql);
                $genders = array();
                while ($row = DB::fetch_row()) {
                    $genders[] = $row['gender'];
                }
                $gender = get_param('gender', guser('default_online_view'));

                if (!in_array($gender, $genders)) {
                    $gender = 'all';
                }
            }
            $html->setvar('search_gender_active_' . strtolower($gender), 'active');

            $this->m_params = get_params_string();
            $this->m_params = del_param("cmd", $this->m_params);
            $this->m_params = del_param("delete", $this->m_params);
            $this->m_params = del_param("edit", $this->m_params);
            $this->m_params = del_param("visible", $this->m_params);
            $this->m_params = del_param("id", $this->m_params);
            $this->m_params = del_param("PHPSESSID", $this->m_params);

            $html->setvar('params', $this->m_params);

            if ($this->m_total) {
                $paramsNoOrder = del_param('order', $this->m_params);
                $paramsNoOrder = del_param('sort', $paramsNoOrder);
                $html->setvar('params_no_order', $paramsNoOrder);
                $html->parse('sort_by_default');
            }
            if (UserFields::isActive('orientation') && Common::isOptionActive('user_choose_default_profile_view')) {
                $html->parse('search_by_gender');
            }
        }

        if($html->varExists('find_new_friends_in_city_now')) {
            $html->setvar('find_new_friends_in_city_now', UsersFilter::getLocationTitle());
        }
    }

    function parseBlockImpact(&$html) {
        global $p;
        global $g_user;
        if (in_array($p, array('users_viewed_me.php'))) {
            $classes = array('users_viewed_me.php' => 'visitors');
            if (isset($classes[$p])) {
                $html->setvar('page_class', $classes[$p]);
            }
        }
    }

    function parseBlockEdge(&$html) {
        $profileDisplayType = Common::getOption('list_people_display_type', 'edge_general_settings');
        $block = "list_people_{$profileDisplayType}";
        if ($html->blockExists($block)) {
            $html->parse($block, false);
        }
        if (!self::$parDisplay) {
            TemplateEdge::parseColumn($html);
        }
    }

    function parseBlock(&$html) {
        global $p;
        global $g_user;

        Common::parseErrorForNotLoginUserNotExist($html);

        $display = get_param('display');
        $optionSet = Common::getOption('set', 'template_options');
        if ($optionSet == 'urban' && $p == 'search_results.php' && in_array($display, array('','encounters','rate_people'))) {
            $countryId = get_param('country', $g_user['country_id']);
            $sql = 'SELECT country_title FROM geo_country
                     WHERE country_id = ' . to_sql($countryId);
            $html->setvar('counter_title', toJsL(DB::result($sql)));
        }
        if (get_param('order', '') == 'rating') {
            $html->parse('rating_none_order_active');
            $html->parse('rating_asc_order_active');
            $html->parse('rating_desc_order_active');
        } else {
            $html->parse('default_order_active');
        }

        $paramShow = get_param('show');
        if ($paramShow && $html->blockExists('js_show_' . $paramShow)) {
            $html->parse('js_show_' . $paramShow, false);
        }

        if ($html->blockExists('class_indent')&& (Common::isOptionActive('free_site') || !Common::isOptionActive('credits_enabled'))) {//Urban mobile
            $html->parse('class_indent');
        }

        $tmplMethod = 'parseBlock' . self::$tmplName;
        if (method_exists('CUsers', $tmplMethod)) {
            $this->$tmplMethod($html);
        }

        parent::parseBlock($html);
    }

}

class CUsersInfo extends CUsers {

    var $m_on_page = 10;

}

class CUsersList extends CUsers {

    var $m_on_page = 20;

}

class CUsersGallery extends CUsers {

    var $m_on_page = 16;
    var $m_on_line = 4;

    function onItem(&$html, $row, $i, $last) {
        parent::onItem($html, $row, $i, $last);

        if ($i % $this->m_on_line == 0 and $i != 0 and $i != $this->m_on_page)
            $html->parse($this->m_name . "_item_after_line", false);
        else
            $html->setblockvar($this->m_name . "_item_after_line", "");
        if ($i == $last)
            for ($j = $this->m_on_line - ($i % $this->m_on_line); $j < $this->m_on_line; $j++)
                $html->parse($this->m_name . "_noitem", true);
    }

}

#выводы подробностей пользователя

class CUsersProfile extends CUsers {

    var $m_on_page = 1;
    var $m_view = 1;
    var $row_user = array();
    var $m_reset_sql = 0;
    var $isParentUserChartsParserActive = false;

    function action() {

        global $g_user, $g;

        if(isset($g['c_user_id'])) // added by sohel
            self::$guid = $g['c_user_id'];

        $cmd = get_param('cmd');
        if ($cmd == 'lang') {
            $game = get_param('game', false);

            header('Content-Type: text/xml; charset=UTF-8');
            header('Cache-Control: no-cache, must-revalidate');

            $words = array(
                'Upload',
                'Browse',
                'Tools',
                'Color',
                'LineType',
                'Shapes',
                'Fonts',
                'Add_Photo',
                'Background',
                'Sunday',
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday',
                'January',
                'February',
                'March',
                'April',
                'May',
                'June',
                'July',
                'August',
                'September',
                'October',
                'November',
                'December',
                'event_s',
                'yes',
                'no',
                'Yes',
                'No',
                'my_text',
                'Loading_Please_wait',
                'Delete_everything',
                'Camera_not_found',
                'OK',
                'Close_the_Mirror',
                'Activate_Mirror',
                'Enter_gallery_s_title',
                'Enter_window_s_title',
                'Close_the_Comments',
                'Close_the_Friends',
                'Enter_RSS_address',
                'Checking_the_feed',
                'Wrong_RSS_feed',
                'more',
                'back',
                'size',
                'Select_the_clock_s_color',
                'Remove_the_clock',
                'Black',
                'White',
                'Activate_the_map',
                'Deactivate_the_map',
                'Choose_background_color',
                'autostart',
                'watch_again',
                'close',
                'Sample',
                'All_Done',
                'my_music',
                'my_video',
                'Cancel_was_selected',
            );
            $lang = '<lang>';
            foreach ($words as $wordKey) {
                $lang .= "<word name='$wordKey'>" . l($wordKey, false, $game) . '</word>';
            }
            $lang .= '</lang>';

            echo $lang;
            die();
        } elseif (!isset($g_user['user_id']) and $g_user['user_id'] <= 0) {
            Common::toLoginPage();
        }
    }

    function init() {
        CStatsTools::count('profiles_viewved');
        parent::init();
        global $g;
        global $g_user;

        $this->m_sql_count = "SELECT COUNT(u.user_id) FROM user AS u " . $this->m_sql_from_add . "";
        $this->m_sql = "
      SELECT u.*, DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(birth, '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(birth, '00-%m-%d')) AS age,
      u.state AS state_title, u.country AS country_title, u.city AS city_title,
      IF(u.city_id=" . $g_user['city_id'] . ", 1, 0) +
      IF(u.state_id=" . $g_user['state_id'] . ", 1, 0) +
      IF(u.country_id=" . $g_user['country_id'] . ", 1, 0) AS near
      FROM user AS u
      " . $this->m_sql_from_add . "
    ";

        $this->m_field['user_id'] = array("user_id", null);
        $this->m_field['name'] = array("name", null);
        $this->m_field['age'] = array("age", null);
        $this->m_field['relation'] = array("relation", null);
        $this->m_field['last_visit'] = array("last_visit", null);
        $this->m_field['city_title'] = array("city", null);
        $this->m_field['state_title'] = array("state", null);
        $this->m_field['country_title'] = array("country", null);
        $this->m_field['rating'] = array("rating", null);
        #$this->m_field['photo'] = array("photo", null);
        $this->m_field_default = $this->m_field;
    }

    function onItemImpact_mobile(&$html, $row, $i, $last) {
        if ($row['user_id'] == self::$guid) {
            if ($html->varExists('profile_number_photos')) {
                $html->setvar('profile_number_photos', CProfilePhoto::countPhoto(self::$guid));
            }
            $html->parse('profile_main_edit', false);
        } else {
            if ($html->varExists('live_price')) {
                $html->setvar('live_price', Pay::getServicePrice('live_stream', 'credits'));
            }
            if (User::isVisiblePlugPrivatePhotoFromId($row['user_id'], self::$photoDefaultId)) {
                $html->parse('photo_main_plug_private_photos', false);
            }
            if(self::$parDisplay != 'encounters') {
                $html->setvar('is_report_user', User::isReportUser($row['user_id']));
                $html->setvar('count_msg_im', CIm::getCountMsgIm($row['user_id']));
            }
        }
        if (self::$parDisplay == 'encounters') {
            $mOnPageEncounters = Common::getOption('usersinfo_encounters_list', 'template_options');
            $html->setvar('number_list_users', intval($mOnPageEncounters));
            $html->setvar('request_ajax', intval(self::$parAjax));
            $html->setvar('users_list_item_num', $i);
            if ($i > 1) {
                $html->parse('users_list_item_hide', false);
            }
        }
        if(get_param('upload_page_content_ajax')){
            $html->parse('profile_content_load_ajax', false);
        }
        $editFieldName = get_param('edit_field_name');
        if ($editFieldName) {
            $html->setvar('edit_field_name', $editFieldName);
            $html->parse('edit_field', false);
        }
        $show = get_param('show');
        $videoShow = get_param_int('video_show');
        if ($html->blockExists('show_albums_js') && ($show == 'albums' || $videoShow)) {
            $block = 'show_albums_js';
            $pid = get_param_int('photo_id');
            if ($pid) {
                $block = 'show_photo_js';
                $html->setvar("{$block}_id", $pid);
            }

            if ($videoShow) {
                $block = 'show_video_js';
                $html->setvar("{$block}_id", $videoShow);
            }
            $html->parse($block, false);
        }
        $this->onItemImpact($html, $row, $i, $last);
    }

    function onItemImpact(&$html, $row, $i, $last) {
        global $g;

        $html->setvar('user_name', $row['name']);

        $isSuperPowers = Common::isOptionActive('free_site') || User::isSuperPowers();
        $isMyProfile = $row['user_id'] == self::$guid;
        User::getLookingForImpact($html, $row['user_id']);

        if($row['under_admin']) {
            $html->setvar("ua_user_id", $row['under_admin']);
            $under_admin_q = DB::row("SELECT name, name_seo FROM user WHERE user_id = '".to_sql($row['under_admin'], "Number")."'");
            $html->setvar('ua_name', '('.$under_admin_q['name'].')');
            $html->setvar('ua_name_seo', $under_admin_q['name_seo']);
            $html->parse('via_under_admin', false);
        } else
            $html->setvar('ua_user_id', $row['user_id']);

        if($row['role'] == 'group_admin')
            $html->setvar('ua_text', '<sup style="font-size: 12px">('.l('group_admin').')</sup>');
        else
            $html->setvar('ua_text', '');

        $orientationTitle = '';
        if (isset($this->u_orientations[$row['orientation']])) {
            $orientationTitle = l($this->u_orientations[$row['orientation']]['title']);
        }
        $html->setvar('user_orientation', $orientationTitle);
        if (!$orientationTitle || !UserFields::isActive('orientation')) {
            $html->parse('user_orientation_hide' . ($isMyProfile ? '_member' : '_visitor'), false);
        }

        if (!self::$photoDefaultId) {
            $html->parse('photo_main_no_photo', false);
        }
        $isEntryBlocked = 0;
        if ($isMyProfile) {
            $html->parse('class_profile_my', false);
            $html->parse('profile_info_member', false);
            if (!self::$photoDefaultId) {
                $html->parse('photo_main_upload_btn', false);
            }
            $html->parse('photo_main_upload', false);
        } else {
            if (City::isActiveStreetChat()){
                $html->parse('profile_menu_street_chat', false);
            }
            if (Common::isOptionActive('contact_blocking')) {
                $isEntryBlocked = intval(User::isEntryBlocked(self::$guid, $row['user_id']));
                $blockProfileBlocked = 'profile_user_blocked_bl';
                if ($isEntryBlocked) {
                    $title = l('profile_menu_user_unblock');
                    $cmd = 'user_unblock';
                    $html->parse("{$blockProfileBlocked}_show", false);
                } else {
                    $cmd = 'block_visitor_user';
                    $title = l('profile_menu_user_block');
                }
                $html->setvar('block_user_cmd', $cmd);
                $html->parse($blockProfileBlocked, false);
                $blockProfileMenuBlocked = 'profile_menu_user_block';
                $html->setvar("{$blockProfileMenuBlocked}_title", $title);
                $html->parse($blockProfileMenuBlocked, false);
            }

            if (!User::isReportUser($row['user_id'], $row)) {
                $html->parse('profile_menu_user_report', false);
            }


            User::parseCharts($html, $row['user_id']);

            if ($html->varExists('im_reply_rate_class')) {
                $imReplyOnNewContactRateLevel = CIm::replyOnNewContactRateLevel($row);
                $html->setvar('im_reply_rate_class', $imReplyOnNewContactRateLevel);
                $html->setvar('im_reply_rate_title', toAttrL('im_reply_rate_' . $imReplyOnNewContactRateLevel));
                if ($html->blockExists('profile_im_reply_rate')) {
                    $html->parse('profile_im_reply_rate', false);
                }
            }
            $blockMenuVisitor = 'profile_tabs_visitor';
            if ($html->blockExists($blockMenuVisitor)) {
                $isParseMediaJs = false;
                $chats = array('audio', 'video');
                foreach ($chats as $chat) {
                    if (Common::isOptionActive($chat . 'chat')) {
                        $isParseMediaJs = true;
                        /*Chat::parseMediaChat($html, $chat, $row['user_id']);*/
                        $html->parse($blockMenuVisitor . '_' . $chat . '_chat', false);
                    }
                }
                $html->parse($blockMenuVisitor, false);
            }

            $html->parse('profile_send_message_btn', false);
            $html->parse('profile_info_visitor', false);
        }
        $html->setvar('is_entry_blocked_user', $isEntryBlocked);
        User::parseRefererBackUrl($html, $row['user_id']);

        if(get_param('cmd_enc')) {
            $html->setvar('update_menu_counters_data', getResponseAjaxByAuth(true, MutualAttractions::getCounters()));
        }
    }

    function onItemUrban_mobile(&$html, $row, $i, $last) {

        if ($html->varExists('live_price')) {
            $html->setvar('live_price', Pay::getServicePrice('live_stream', 'credits'));
        }

        $videoShow = get_param_int('video_show');
        if ($html->blockExists('show_video_js') && $videoShow) {
            if ($videoShow) {
                $videoInfo = DB::row("SELECT * FROM `vids_video` WHERE id=" . to_sql($videoShow));
                if ($videoInfo) {
                    $block = 'show_video_js';
                    $html->setvar("{$block}_id", $videoShow);
                    $html->setvar("{$block}_live_id", $videoInfo['live_id']);
                    $html->setvar("{$block}_my_video", intval($videoInfo['user_id'] == self::$guid));
                }

            }
            $html->parse($block, false);
        }
    }

    function onItemEdge(&$html, $row, $i, $last) {
        if (self::$parDisplay == 'profile') {
            /*if (self::$guid) {
                $isMyProfile = $row['user_id'] == self::$guid;
                if (!$isMyProfile) {
                    $html->setvar('user_status_online', intval(User::isOnline($row['user_id'])));
                    $html->setvar('real_status_online', intval(User::isOnline($row['user_id'], null, true)));
                    $html->parse('init_profile_js', false);
                }
            }*/
        }
    }

    function onItem(&$html, $row, $i, $last) {
        global $g;
        global $gm;
        global $gc;
        global $p;
        global $l;
        global $g_info;
        global $g_user;

        parent::onItem($html, $row, $i, $last);

        $optionTmplSet = Common::getOption('set', 'template_options');
        $optionTmplName = Common::getOption('name', 'template_options');
        $guid = self::$guid;

        $isFreeSite = Common::isOptionActive('free_site');
        $display = get_param('display');
        $option = 'set_who_view_profile';
        if (!self::$guid && User::isSettingEnabled($option) && $row[$option] == 'members') {
            redirect(Common::pageUrl('login'));
        }

        if ($html->varExists('real_status_online')) {
            $html->setvar('user_status_online', intval(User::isOnline($row['user_id'])));
            $html->setvar('real_status_online', intval(User::isOnline($row['user_id'], null, true)));
        }

        if ($p != 'profile_view.php') {
            #$html->parse("back_to_results");
            #$html->parse("back_to_results2");
        }
        if (get_session('send_message') == true) {
            $html->parse('mail_sent');
            set_session('send_message', false);
        }


        $blockStatus = 'profile_status';
        if (Common::isOptionActive('profile_status')) {
            $row['status'] = $this->profileStatusValue;
            if ($row['status'] !== '') {
                if ($html->varExists("{$blockStatus}_html")) {
                    $html->setvar("{$blockStatus}_html", toJs(html_entity_decode($row['status'], ENT_QUOTES, 'UTF-8')));
                }
                $html->setvar($blockStatus, $row['status']);
                if ($this->m_is_me) {
                    $html->parse("{$blockStatus}_edit");
                } elseif (Common::getOption('pofile_status_visitor', 'template_options')) {
                    $html->parse("{$blockStatus}_visitor");
                }
                if (self::$tmplName != 'impact_mobile'
                    || (self::$tmplName == 'impact_mobile' && $row['user_id'] == $g_user['user_id'])){
                    $html->parse($blockStatus);
                }
            } elseif ($this->m_is_me) {
                if (Common::getOption('pofile_status_empty_title', 'template_options')) {
                    $html->setvar($blockStatus, l('your_status_here'));
                }
                $html->parse("{$blockStatus}_edit");
                $html->parse($blockStatus);
            }
        }
        // profile status

        // URBAN
        ProfileGift::parseGift($html, $row['user_id']);

        $blockLookingFor = 'profile_looking_for';
        if ($html->varExists($blockLookingFor)
            && (UserFields::isActive('i_am_here_to') || UserFields::isActive('orientation') || UserFields::isActive('age_range'))) {
            if ($row['user_id'] == $g_user['user_id']) {
                $html->setvar($blockLookingFor . '_class', 'edit');
                $html->parse($blockLookingFor . '_edit');
            }
            $lKey = '';
            if ($optionTmplName == 'urban_mobile' && $p == 'profile_view.php') {
                $lKey = 'profile_view';
            }
            $html->setvar($blockLookingFor, User::getLookingFor($row['user_id'], null, $lKey));
            $html->parse($blockLookingFor);
        }
        if (self::$guid) {
            $cmd = get_param('cmd');
            if ($cmd == 'payment_error' && $html->blockexists('payment_error')) {
                $html->parse('payment_error');
            }
            $type = get_param('type');
            if ($cmd == 'payment_thank' && !$type && $html->blockexists('payment_thank')) {
                $html->parse('payment_thank');
            }
            if ($cmd=='edit_field'){
                $fieldName=get_param('field_name');
                if($fieldName!=''){
                    $html->setvar('name_field_on_start', $fieldName);
                    $html->parse('edit_field_on_start');
                }
            }
        } else {
                $colOrder=Common::getColOrder('narrow');
                $isNoProfileBg=(!isset($colOrder['customization']) || $colOrder['customization']['status']=='N');
                if($isNoProfileBg){
                    $html->setvar('user_profile_bg', '');
                } else {
                    $html->setvar('user_profile_bg', $row['profile_bg']);
                }
            $html->parse('user_profile_bg', false);
        }

        $html->setvar('friend_id', User::isFriend(self::$guid, $row['user_id']));

        $html->setvar('country_id', $row['country_id']);
        $html->setvar('state_id', $row['state_id']);
        $html->setvar('city_id', $row['city_id']);

        // Photo
        if ($html->blockexists('photo_rand')) {
            CProfilePhoto::parsePhotoRand($html, $row['user_id'], false, $row['gender']);
        }
        $imgSize = 's';
        $imgSizeTmpl = Common::getOption('profile_photo_size', 'template_options');
        if ($imgSizeTmpl) {
            $imgSize = $imgSizeTmpl;
        }
        if ($html->blockexists('photo_public_block')) {
            $limit = '';
            $whereSql = '';
            $notPhotoId = 0;
            if ($display == 'encounters' && self::$tmplName == 'impact') {
                $limit = 5;
                $notPhotoId = self::$photoDefaultId;
                //$whereSql = ' AND `photo_id` != ' . to_sql(self::$photoDefaultId);
            }
            CProfilePhoto::parsePhotoProfile($html, 'public', $row['user_id'], false, $imgSize, false, $limit, $whereSql, $notPhotoId);
        }
        if ($html->blockexists('photo_private_block')) {
            CProfilePhoto::parsePhotoProfile($html, 'private', $row['user_id'], false, $imgSize);
        }

        if ($html->blockexists('video_public_block') && Common::isOptionActive('videogallery')) {
            $templateVideoPreviewSize = Common::getOption('profile_video_size', 'template_options');
            $imgSize = $templateVideoPreviewSize ? $templateVideoPreviewSize : '';
            CProfilePhoto::parseVideoProfile($html, 'public', $row['user_id'], false, $imgSize);
        }

        if ($html->varExists('marked_photos_private')) {
            $html->setvar('marked_photos_private', toJsL('the_user_has_marked_of_his_photos_as_private_' . $row['gender']));
        }

        if ($html->varExists('auto_play_video')) {
            $html->setvar('auto_play_video', Common::isOptionActive('video_autoplay')?'autoplay':'');
        }

        // Photo
        $paramShow = get_param('show');
        $blockShow = "show_{$paramShow}";
        if ($paramShow == 'gallery' && $html->blockexists($blockShow)) {
            $pid = get_param('photo_id',0);
            if (!isset(CProfilePhoto::$allPhoto[$pid])) {
                if (!empty(CProfilePhoto::$privatePhoto)) {
                    foreach (CProfilePhoto::$privatePhoto as $pid => $value) {
                        break;
                    }
                } elseif (!empty(CProfilePhoto::$publicPhoto)) {
                    foreach (CProfilePhoto::$publicPhoto as $pid => $value) {
                        break;
                    }
                }
            }
            if ($pid) {
                $html->setvar($blockShow . '_photo_id', $pid);
                $html->parse($blockShow, false);
            }
        } elseif ($paramShow == 'video_gallery' && $html->blockexists($blockShow)) {
            $vid = CProfilePhoto::getKeyVideoId(get_param('video_id',0));
            if (!isset(CProfilePhoto::$publicVideo[$vid]) && !empty(CProfilePhoto::$publicVideo)) {
                foreach (CProfilePhoto::$publicVideo as $vid => $value) {
                    break;
                }
            }
            if ($vid) {
                $html->setvar($blockShow . '_id', $vid);
                $html->parse($blockShow, false);
            }
        } elseif ($html->blockexists($blockShow)) {
            $html->parse($blockShow, false);
        }

        if ($html->varExists('photo_rating_enabled')) {
            $html->setvar('photo_rating_enabled', intval(Common::isOptionActive('photo_rating_enabled')));
        }

        if ($html->varExists('greeting_video_id')) {
            $html->setvar('greeting_video_id', intval(guser('video_greeting')));
        }

        if (self::$guid == $row['user_id']) {
            $html->setvar('not_locked_user', 1);
            //$html->parse('profile_edit_main');//profile_head
            $html->parse('edit_photos');
            $html->parse('profile_custom_header_js');
            $html->parse('profile_edit_js');
            $html->parse('profile_custom_member', false);
            $maxFileSize = Common::getOption('photo_size');
            $maxVideoSize = Common::getOption('video_size');
            $html->setvar('photo_file_size_limit', mb_to_bytes($maxFileSize));
            $html->setvar('video_file_size_limit', mb_to_bytes($maxVideoSize));
            $html->setvar('is_super_powers', User::isSuperPowers());
            $html->setvar('is_free_site', Common::isOptionActive('free_site'));
//            $html->setvar('is_super_powers', User::isSuperPowers()?'true':'false');
//            $html->setvar('is_free_site', Common::isOptionActive('free_site')?'true':'false');

            $html->setvar('upload_limit_photo_count', Common::getOption('upload_limit_photo_count'));
            $html->setvar('upload_more_than_limit', lSetVars('you_need_to_upgrade_to_upload_more_photos',array('count'=>Common::getOption('upload_limit_photo_count'))));

            $html->setvar('max_photo_file_size_limit', lSetVars('max_file_size', array('size'=>$maxFileSize)));
            $html->setvar('max_video_file_size_limit', lSetVars('max_file_size', array('size'=>$maxVideoSize)));
        } else {
            if ($p == 'search_results.php') {
                User::setUserVisitor($guid, $row['user_id']);
            }

            $blockGrabGift = 'grab_gift';
            if ($html->blockexists($blockGrabGift) && Common::isOptionActive('gifts_enabled')) {
                $activeSet = ProfileGift::getActiveSet();
                if (!$activeSet) {
                    $activeSet = DB::result('SELECT `id` FROM `gifts_set` ORDER BY RAND() LIMIT 1');
                }
                $html->setvar("{$blockGrabGift}_url", ProfileGift::getUrlImg($activeSet, false, false, 'set'));
                $html->setvar($blockGrabGift, l($blockGrabGift . '_' . $row['gender']));
                $html->parse($blockGrabGift);
            }

            $blockShowAllPhotos = 'show_all_photos';
            if ($html->blockexists($blockShowAllPhotos)) {
                $allPhoto = CProfilePhoto::countPhoto($row['user_id']);
                if ($allPhoto > 0) {
                    if ($allPhoto == 1) {
                        $allPhoto = '';
                    }
                    $html->setvar($blockShowAllPhotos . '_id', CProfilePhoto::getIdFirstPhotoNoRandom($row['user_id']));
                    $html->setvar($blockShowAllPhotos, lSetVars('show_all_photos', array('count'=>$allPhoto)));
                    $html->parse($blockShowAllPhotos);
                }
            }

            $notLocked = true;
            if ($g_user['user_id']) {
                $notLocked = !User::isEntryBlocked($row['user_id'], $g_user['user_id']);
                $html->setvar('not_locked_user', $notLocked*1);
            }
            if ($html->blockexists('profile_visitor_chat') && $notLocked) {
                $html->parse('profile_visitor_chat');
            }
            if ($html->blockExists('profile_custom_header_visitor_js')) {
                $html->parse('profile_custom_header_visitor_js', false);
            }
        }
        /*$blockBroadcast = 'profile_broadcast';
        if ($guid && $html->blockexists('profile_broadcast')) {
            $html->setvar('media_server', $g['media_server']);
            $html->parse("{$blockBroadcast}_js", false);
            $title = l('webcam_broadcasting_off');
            if ($row['user_id'] == $g_user['user_id']) {
                $html->parse("{$blockBroadcast}_control", false);
                $html->parse("{$blockBroadcast}_hide", false);
            } else {
                if (User::isUserBroadcast($row['user_id'])) {
                    $title = l('webcam_broadcasting_clear');
                    $html->parse("{$blockBroadcast}_connect", false);
                }
                $html->parse("{$blockBroadcast}_listener_loader", false);
            }
            $html->setvar("{$blockBroadcast}_title", $title);
            $html->parse($blockBroadcast, false);
        }*/

        ProfileHead::parseHead($html, $row);
        // URBAN


        $this->m_field['city_title'][1] = $row['city'] != "" ? (isset($l['all'][to_php_alfabet($row['city'])]) ? $l['all'][to_php_alfabet($row['city'])] : $row['city']) : "";
        $this->m_field['state_title'][1] = $row['state'] != "" ? (isset($l['all'][to_php_alfabet($row['state'])]) ? $l['all'][to_php_alfabet($row['state'])] : $row['state']) : "";
        $this->m_field['country_title'][1] = $row['country'] != "" ? (isset($l['all'][to_php_alfabet($row['country'])]) ? $l['all'][to_php_alfabet($row['country'])] : $row['country']) : "";

        $this->m_field['city_title'][1] = (($this->m_field['city_title'][1] == "" or $this->m_field['city_title'][1] == "0") ? '' : $this->m_field['city_title'][1] . ", ");
        $this->m_field['state_title'][1] = (($this->m_field['state_title'][1] == "" or $this->m_field['state_title'][1] == "0") ? '' : $this->m_field['state_title'][1] . ", ");
        $this->m_field['country_title'][1] = (($this->m_field['country_title'][1] == "" or $this->m_field['country_title'][1] == "0") ? '' : $this->m_field['country_title'][1]);

        $html->setvar("country", $this->m_field['country_title'][1]);
        $html->setvar("state", $this->m_field['state_title'][1]);
        $html->setvar("city", $this->m_field['city_title'][1]);

        //DB::query("SELECT * FROM userinfo WHERE user_id=" . $row['user_id'] . "", 2);
        //$row_user2 = DB::fetch_row(2);
        //DB::query("SELECT * FROM userpartner WHERE user_id=" . $row['user_id'] . "", 2);
        //$row_user3 = DB::fetch_row(2);
        $row_user = User::getInfoFull($row['user_id'], 2); //array_merge($row, $row_user2, $row_user3);
        $this->row_user = $row_user;
        #фото
        $num_photo = DB::result("SELECT COUNT(photo_id) FROM photo WHERE user_id=" . $row_user['user_id'] . " " . $g['sql']['photo_vis'] . "");
        $html->setvar("num_photo", $num_photo);

        $this->m_field['photo_id'][1] = User::getPhotoDefault($row['user_id'], 'm', false, $row['gender']);

        $groupId = Groups::getParamId();
        if (!$this->m_is_me and $this->m_view == 1 and self::$guid && !$groupId) {
            $ref = get_param('ref');
            $view = DB::result("SELECT user_to FROM users_view WHERE user_from=" . $g_user['user_id'] . " AND user_to=" . $row_user['user_id'] . "");
            if ($view != "0" and $p != "users_i_viewed.php") {
                DB::execute("DELETE FROM users_view WHERE user_from=" . $g_user['user_id'] . " AND user_to=" . $row_user['user_id'] . "");
            }
            if ($p != "users_i_viewed.php") {
                $isVisitors = !User::isInvisibleModeOptionActive('set_do_not_show_me_visitors');
                if ($isVisitors && !Moderator::isAllowedViewingUsers()) {
                    DB::execute("INSERT INTO users_view (user_from, user_to, new, visited, ref, created_at) VALUES(" . $g_user['user_id'] . ", " . $row_user['user_id'] . ", 'Y', 1, " . to_sql($ref) . ", ". to_sql(date('Y-m-d H:i:s')) .")");
                    DB::execute("UPDATE user SET new_views=new_views+1, total_views=total_views+1, popularity = popularity + 1 WHERE user_id=" . to_sql($row_user['user_id'], "Number") . "");

                    $option = 'set_notif_profile_visitors';
                    if (Common::isEnabledAutoMail('profile_visitors')
                            && $display != 'encounters'
                            && User::isSettingEnabled($option)
                            && User::isOptionSettings($option, $row_user)) {
                        $vars = array('title' => Common::getOption('title', 'main'),
                                      'name'  => $g_user['name'],
                                      'uid'  => $g_user['user_id']);
                        Common::sendAutomail($row['lang'], $row['mail'], 'profile_visitors', $vars);
                    }
                }
            }

            if($optionTmplSet != 'urban') {
                $views = DB::result("SELECT COUNT(*) FROM users_view WHERE user_from=" . $g_user['user_id']);
                if ($views > 40) {
                    DB::execute("DELETE FROM users_view WHERE user_from=" . $g_user['user_id'] . " ORDER BY id LIMIT 1");
                }
            }
        }



        if ($row_user['p_age_from'] != 0) {
            $html->setvar("p_age", $row_user['p_age_from'] . " - " . $row_user['p_age_to']);
        } else {
            $html->setvar("p_age", isset($l['all'][to_php_alfabet("Not Specified")]) ? $l['all'][to_php_alfabet("Not Specified")] : "Not Specified");
        }

// MUSIC

        if (isset($g['options']['music']) and $g['options']['music'] == "Y") {
            if (DB::count('music_song', 'user_id=' . $row_user['user_id']) || DB::count('music_musician', 'user_id=' . $row_user['user_id']))
                $html->parse("yes_audio", true);
        }

// MUSIC

        /* if (isset($g['options']['videogallery']) and $g['options']['videogallery'] == "Y")
          {
          DB::query("SELECT id FROM videogallery_video WHERE user_id=" . $row_user['user_id'] . " AND status='ACTIVE' ORDER BY id DESC LIMIT 1");
          if ($row = DB::fetch_row())
          {
          $html->setvar("last_vid", $row['id']);
          $html->parse("yes_vids", true);
          }
          } */

        $html->setvar('url_absolute', "http://" . str_replace("//", "/", str_replace("\\", "", $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/")));

//    if (is_dir(dirname(__FILE__) . '/../../_server/editor/xml/') and !file_exists(dirname(__FILE__) . '/../../_server/editor/xml/' . $row_user['user_id'] . '.xml')) {
//      $fp = fopen(dirname(__FILE__) . '/../../_server/editor/xml/' . $row_user['user_id'] . '.xml', 'w');
//      fclose($fp);
//      chmod(dirname(__FILE__) . '/../../_server/editor/xml/' . $row_user['user_id'] . '.xml', 0777);
//    }
        $html->setvar('xml_file', urlencode('_server/editor/thehistory.php?action=xml&uid=' . $row_user['user_id'] . '&c=' . (rand(0, 10000000))));
        $html->setvar('rand', '' . (rand(0, 10000000)) . '');

        if ($optionTmplSet !== 'urban' && !User::isSimpleProfile($row_user['user_id']) && !Common::isMobile()) {
            if ($this->m_is_me) {
                $html->setvar('flash_profile', User::flashProfile($row_user['user_id']));
            } else {
                $html->setvar('flash_profile', User::flashProfile($row_user['user_id'], 'viewer'));
            }
            $html->parse('flash_profile', false);
        } elseif (self::$tmplName != 'impact_mobile'
                    || (self::$tmplName == 'impact_mobile' && $display != 'encounters')) {
            $prf = null;
            if ($display == 'profile_info') {
                $prf = Common::getOption('custom_profile_info_html', 'template_options');
            }
            if ($prf === null) {
                $prf = Common::getOption('custom_profile_html', 'template_options');
            }
            if ($prf === null) {
                $prf = 'profile_html';
            }
            $profileHtml = new CUsersProfileHtml($prf, null, false, false, true);
            $profileHtml->formatValue = 'html';
            $profileHtml->mode = 'view';
            $this->add($profileHtml);
            $profileHtml->setUser($row_user['user_id']);
            $profileHtml->parseBlock($html);
        }

        $isFriendRequested = User::isFriendRequestExists($row['user_id'], self::$guid);
        $isFriend = User::isFriend($row['user_id'], self::$guid);

        if ($html->blockExists('is_request_friends_hide') || $html->blockExists('no_request_friends_hide')) {
            if ($isFriendRequested) {
                $html->parse('is_request_friends_hide');
            } else {
                $html->parse('no_request_friends_hide');
            }
        }

        if (empty($isFriendRequested) && empty($isFriend)) {
            $html->parse('friend_add');
        }

        if (Common::isOptionActive('bookmarks')) {
            $isBookmarded = User::isBookmarkExists(self::$guid, $row['user_id']);

            if (empty($isBookmarded)) {
                $html->parse('bookmark_add');
            }
        }

        // love_calculator
        if (Common::isOptionActive('love_calculator')) {
            $html->parse('love_calculator');
        }
        if (!$this->m_is_me) {
            $html->parse('action_button');
        }
        // couples

        /* URBAN */
        if ($html->varExists('url_redirect_wait_approval')){
            $redirectWaitApproval = Common::getOption('redirect_wait_approval', 'template_options');
            if ($redirectWaitApproval) {
                $html->setvar('url_redirect_wait_approval', $redirectWaitApproval);
            }
        }

        $cmd = get_param('cmd');
        if ($cmd == 'confirmed' && $html->blockexists('alert_email_confirmed')){
            $html->parse('alert_email_confirmed');
        }


        $notParseSubmenuItem = 'profile_tabs_wall_item';
        $submenuItemSelectedKey = 0;
        if (self::$guid) {
            $blockStatistics = 'profile_statistics';
            if ($html->blockexists($blockStatistics)) {

                $numSearch = User::getPositionInSearchResult($row['user_id']);
                $vars = array('number' => $numSearch);
                $gender = ($row['user_id'] == $g_user['user_id']) ? 'you' : $row['gender'];
                $html->setvar($blockStatistics . '_search_number', lSetVars('place_number_in_search_results_' . $gender, $vars));

                $sqlUserId = to_sql($row['user_id'], 'Number');

                /*
                $sql = 'SELECT COUNT(u.user_id)
                          FROM `user` AS u
                          JOIN `users_view` AS v ON (u.user_id=v.user_from AND v.user_to=' . $sqlUserId . ')
                         WHERE u.user_id != ' . $sqlUserId;
                */

                $sql = 'SELECT COUNT(*) AS stat_month,
                    SUM(CASE WHEN created_at >= ' . to_sql(date('Y-m-d') . ' 00:00:00') . ' THEN 1 ELSE 0 END) AS stat_day
                    FROM `users_view`
                    WHERE user_to = ' . $sqlUserId . '
                        AND created_at >= ' . to_sql(date('Y-m') . '-01 00:00:00');

                $profileStat = DB::row($sql);

                //$whereToday = ' AND created_at >= ' . to_sql(date('Y-m-d') . ' 00:00:00');
                //$numberToday = DB::result($sql . $whereToday);
                //$whereMonth = ' AND created_at >= ' . to_sql(date('Y-m') . '-01 00:00:00');
                //$numberMonth = DB::result($sql . $whereMonth);

                $numberToday = 0;
                $numberMonth = 0;

                if($profileStat) {
                    $numberToday = (int) $profileStat['stat_day'];
                    $numberMonth = $profileStat['stat_month'];
                }

                $vars = array('today' => $numberToday,
                              'month' => $numberMonth);

                $html->setvar($blockStatistics . '_visitor_number', lSetVars('profile_visitor_today_this_month', $vars));

                $popularityLevel = User::getLevelOfPopularity($row['user_id']);
                $vars = array('popularity' => mb_ucfirst(l($popularityLevel), 'UTF-8'));
                if ($row['user_id'] != self::$guid) {
                    $averagePopularity = lSetVars('profile_her_popularity_' . $row['gender'], $vars);
                } else {
                    if (Common::isCreditsEnabled()) {
                        $vars['url'] = self::$url['profile_statistics_average'];
                        $averagePopularity = Common::lSetLink('profile_her_popularity', $vars);
                    } else {
                        $averagePopularity = lSetVars('profile_her_popularity_free', $vars);
                    }
                }
                $html->setvar($blockStatistics . '_average', $averagePopularity);
                $html->parse($blockStatistics);
            }

            if (Common::isWallActive() && Menu::isActiveSubmenuItem('profile_tabs', 'header_menu_wall')) {
                $notParseSubmenuItem = '';
                $blockTabWall = 'profile_tab_wall';
                if (!Wall::isOnlySeeFriends($row['user_id'])
                    || !Wall::isOnlyPostFriends($row['user_id'])) {
                    $notParseSubmenuItem = 'profile_tabs_wall_item';
                }
                $isParamShowWall = $paramShow == 'wall';
                if ($html->varExists('position_tab_wall')){
                    $positionTabWall = Menu::getIndexItemSubmenu('profile_tabs', 'profile_tabs_wall_item');
                    $html->setvar('position_tab_wall', $positionTabWall);
                    if (((!$positionTabWall && !$notParseSubmenuItem) || $isParamShowWall) && $paramShow != 'photos') {
                        $html->parse('profile_wall_selected');
                    }
                    $html->parse('profile_wall');
                }
                if ($isParamShowWall){
                    $submenuItemSelectedKey = 'profile_tabs_wall_item';
                }
            }
            if ($paramShow == 'photos') {
                $submenuItemSelectedKey = 'profile_tabs_tab_photos_item';
            }
        }

        if ($html->varExists('is_upload_photo_to_see_photos')){
            $positionTabPhoto = Menu::getIndexItemSubmenu('profile_tabs', 'profile_tabs_tab_photos_item');
            if ($positionTabPhoto !== false) {
                $isUploadPhotoToSeePhotos = User::isUploadPhotoToSeePhotos($row['user_id']);
                $html->setvar('is_upload_photo_to_see_photos', $isUploadPhotoToSeePhotos);
                if ($isUploadPhotoToSeePhotos && !$positionTabPhoto && $submenuItemSelectedKey !== 'profile_tabs_wall_item') {
                    $submenuItemSelectedKey = 'profile_tabs_tab_profile_item';
                }
            }
        }

        if ($html->blockexists('profile_tabs')){
            Menu::parseSubmenu($html, 'profile_tabs', $submenuItemSelectedKey, $notParseSubmenuItem);
        }

        if ($html->varExists('please_upload_photo_to_see_photos')){
            $value = Common::isOptionActive('photo_approval') ? toJsL('please_upload_a_profile_photo_to_see_photos_approval') : toJsL('please_upload_a_profile_photo_to_see_photos');
            $html->setvar('please_upload_photo_to_see_photos', $value);
        }
        /* URBAN */

        /* URBAN MOBILE */
        if ($html->varExists('is_mutual_attraction_encounters')) {
            $html->setvar('is_mutual_attraction_encounters', intval(MutualAttractions::isMutualAttraction($row['user_id'])));
            $html->setvar('is_attraction_from', MutualAttractions::isAttractionFrom($row['user_id'])?1:0);
        }
        if ($html->varExists('guid_photo_m')) {
            $html->setvar('guid_photo_m', User::getPhotoDefault($g_user['user_id'], 'm', false, $g_user['gender']));
        }
        if ($optionTmplName == 'urban_mobile'
                || ($optionTmplName == 'impact_mobile' && ($cmd == 'get_data_photos_gallery' || $display == 'encounters'))) {
            if ($html->varExists('remove_like_encounters_users')) {
                $html->setvar('remove_like_encounters_users', $this->m_reset_sql);
            }
            $varPhotosInfo = 'photos_info';
            if ($html->varExists($varPhotosInfo)) {
                    if($display == 'encounters') {
                        $photoId = User::getPhotoDefault($row['user_id'], 'm', true, $row['gender']);
                        $photosInfo = CProfilePhoto::preparePhotoList($row['user_id'], '', ' AND photo_id = ' . $photoId);
                    } else {
                        $photosInfo = CProfilePhoto::preparePhotoList($row['user_id'], '`default` ASC, `photo_id` DESC');
                    }
                    $is = false;
                    //$photoIdCur = User::getPhotoDefault($row['user_id'], 'b', true);
                    $privatePhoto = CProfilePhoto::$privatePhoto;
                    $photosNumber = count($photosInfo);
                    $numberPrivate = count($privatePhoto);

                    if (!$photosNumber && $html->varExists('report_user') && $row['user_id'] != $g_user['user_id']) {
                        $html->setvar('report_user', User::isReportUser($row['user_id']));
                    }
                    //$list = [];
                    $seePrivatePhotoId = 0;
                    $photoCurId = get_param('photo_cur_id',0);
                    if ($optionTmplName == 'urban_mobile') {//redirect automail
                        $pid = get_param_int('photo_id');
                        if ($pid && get_param('show') == 'albums') {
                            $photoCurId = $pid;
                        }
                    }
                    if ($row['user_id'] != $g_user['user_id']
                        && !User::isFriend($g_user['user_id'], $row['user_id'])
                        && !empty($privatePhoto)) {
                        if ($photoCurId && isset($privatePhoto[$photoCurId])) {

                        }
                        arsort($privatePhoto);
                        foreach ($privatePhoto as $id => $item) {
                            if ($is) {
                                $photo = $photosInfo[$id];
                                $prev_id = $photo['prev_id'];
                                $prev = $photo['prev'];
                                $next_id = $photo['next_id'];
                                $next = $photo['next'];
                                //if ($photoIdCur == $id){
                                //$photoIdCur = $next_id;
                                //}

                                $photosInfo[$prev_id]['next_id'] = $next_id;
                                $photosInfo[$prev_id]['next'] = $next;
                                $photosInfo[$next_id]['prev_id'] = $prev_id;
                                $photosInfo[$next_id]['prev'] = $prev;
                                unset($photosInfo[$id]);
                            } elseif ($photoCurId && isset($privatePhoto[$photoCurId])) {
                                $seePrivatePhotoId = $id;
                            }
                            $is = true;
                        }
                         //$j=0;
                        $photosNumber = $photosNumber - $numberPrivate + 1;
                        $j = $photosNumber;
                        foreach ($photosInfo as $id => $item) {
                            $photosInfo[$id]['offset'] = --$j;
                        }

                    }
                    $html->setvar($varPhotosInfo, json_encode($photosInfo));
                    reset($photosInfo);
                    if ($optionTmplName == 'impact_mobile' && $cmd == 'get_data_photos_gallery' && $seePrivatePhotoId) {
                        $photoCurId = $seePrivatePhotoId;
                    }
                    if(!$photoCurId || !isset($photosInfo[$photoCurId])) {
                        $photoCurId = key($photosInfo);
                    }
                    $html->setvar('photo_cur_id', $photoCurId);
                    $photoCur = $photoCurId ? $g['path']['url_files'] . $photosInfo[$photoCurId]['src_bm'] :
                                'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';
                    if (self::$tmplName == 'impact_mobile') {
                        if ($cmd == 'get_data_photos_gallery' && $photoCurId) {
                            CProfilePhoto::setMediaViews($photoCurId);
                        }
                        if ((self::$parDisplay == 'encounters' && $i == 1) || self::$parDisplay != 'encounters') {
                            $html->setvar('photo_cur', $photoCur);
                        }
                    } else {
                        $html->setvar('photo_cur', $photoCur);
                    }
                    //$html->setvar('photo_cur', $photoCurId ? $g['path']['url_files'] . $photosInfo[$photoCurId]['src_bm']:
                        //'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');

                    $html->setvar('photo_number', $photosNumber);
                    $html->setvar('photo_number_private', $numberPrivate);

                    if ($row['user_id'] == $g_user['user_id']) {
                        if ($html->blockExists('set_profile_photo_default')) {
                            $html->parse('set_profile_photo_default', false);
                        }
                    } else {
                        if ($html->blockExists('set_profile_photo_report')) {
                            $html->parse('set_profile_photo_report', false);
                        }
                    }
                    if ($display == 'encounters') {
                        if ($html->blockExists('show_btn_encounters')) {
                            $html->parse('show_btn_encounters', false);
                            $html->parse('not_show_no_one_found', false);
                        }
                    }
                    if ($cmd == 'get_data_photos_gallery') {
                        if ($photoCurId) {
                            CProfilePhoto::parseComments($html, $photoCurId);
                        } else {
                            $html->parse('frm_comments_hide', false);
                        }
                    }
                }

            UserFields::parseFieldsStyle($html, array('interests'));
        }

        if ($optionTmplName == 'impact_mobile' && $cmd == 'get_data_videos_gallery') {
            $varVideosInfo = 'videos_info';
            if ($html->varExists($varVideosInfo)) {
                $videosInfo = CProfilePhoto::prepareVideoList($row['user_id']);

                $videosNumber = count($videosInfo);

                if (!$videosNumber && $html->varExists('report_user') && $row['user_id'] != $g_user['user_id']) {
                    $html->setvar('report_user', User::isReportUser($row['user_id']));
                }

                $html->setvar($varVideosInfo, json_encode($videosInfo));
                reset($videosInfo);
                $videoCurrentId = get_param('video_current_id', 0);
                if(!$videoCurrentId || !isset($videosInfo[$videoCurrentId])) {
                    $videoCurrentId = key($videosInfo);
                }

                $html->setvar('video_current_id', $videoCurrentId);
                //$html->setvar('photo_cur', $videoCurrentId ? $g['path']['url_files'] . $videosInfo[$videoCurrentId]['src_b']:
                //    'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');

                $html->setvar('videos_number', $videosNumber);
                $html->setvar('src_v', $videosInfo[$videoCurrentId]['src_v']);
                $html->setvar('src_src', $videosInfo[$videoCurrentId]['src_src']);

                if ($row['user_id'] != $g_user['user_id']) {
                    if ($html->blockExists('set_profile_photo_report')) {
                        $html->parse('set_profile_photo_report', false);
                    }
                }
                if ($cmd == 'get_data_videos_gallery') {
                    if ($videoCurrentId) {
                        CProfilePhoto::parseCommentsVideo($html, str_replace('v_', '', $videoCurrentId));
                    } else {
                        $html->parse('frm_comments_hide', false);
                    }
                }
            }

            UserFields::parseFieldsStyle($html, array('interests'));
        }

        $block = 'response_superpowers_activated';
        if ($html->blockExists($block) && self::$guid == $row['user_id']
            && User::isSuperPowers() && get_session($block)) {
            delses($block);
            $html->parse($block, false);
        }

        $block = 'user_profile_photo';
        if ($html->blockExists($block)) {
            $html->setvar($block . '_url', User::getPhotoDefault($row['user_id'], 'm', false, $row['gender']));
            $html->parse($block, false);
        }
        /* URBAN MOBILE */

        // REMOVE BLANK city/state/country
        // REMOVE BLANK city/state/country

        $onItemTemplateMethod = 'onItem' . $optionTmplName;
        if (method_exists('CUsersProfile', $onItemTemplateMethod)) {
            $this->$onItemTemplateMethod($html, $row, $i, $last);
        }

        User::parseProfileVerification($html, $row_user);

    }

}

class CHtmlUsersPhoto extends CUsers {

    var $m_on_page = 1;

    function parseBlock(&$html) {
        $_SERVER['QUERY_STRING'] = del_param("photo_offset", $_SERVER['QUERY_STRING']);
        parent::parseBlock($html);
    }

    function action() {
        global $g_user;
        global $g;
        // save comments

        $comm = trim(get_param("comment", ""));
        $photo_id_cur = get_param("photo_id_cur", 0);
        $display = get_param('display');

        /* Encounters && Rate people */
        $isAjaxRequest = get_param('ajax');
        if ($display == 'encounters' && $isAjaxRequest) {
           // Encounters::likeToMeet();
        }elseif ($display == 'rate_people' && $isAjaxRequest) {
            CProfilePhoto::setRated();
        }
        /* Encounters && Rate people */
        elseif ($g_user['user_id'] > 0 && $photo_id_cur > 0) {

            // ??? Дата в запросе была NOW()
            $photoUserId = DB::result('SELECT `user_id` FROM `photo` WHERE `photo_id` = ' . to_sql($photo_id_cur));
            $isNew = intval($photoUserId != $g_user['user_id']);
            $date = date("Y-m-d H:i:s");
            $sql = "INSERT INTO `photo_comments` (`id`, `user_id`, `photo_id`, `photo_user_id`, `is_new`, `date`, `comment`, `send`) VALUES (
                    NULL,
                    " . to_sql($g_user['user_id'], "Number") . ",
                    " . to_sql($photo_id_cur, "Number") . ","
                    . to_sql($photoUserId, 'Number') . ","
                    . to_sql($isNew, 'Number')
                    . ",'" . $date . "'," . to_sql(Common::filter_text_to_db($comm, false)) . ',' . time() . ")";
            if ($comm != "") {
                DB::execute($sql);
                $id = DB::insert_id();
                CProfilePhoto::addCommentToWall($id, $photo_id_cur);
                CProfilePhoto::updateCountComment($photo_id_cur);
                // hide comment from photo owner if posted by photo owner
                /*$hideFromUser = 0;

                $sql = 'SELECT `user_id`, `private`
                          FROM `photo`
                         WHERE `photo_id` = ' . to_sql($photo_id_cur, 'Number');
                $row = DB::row($sql);

                $access = ($row['private'] == 'Y') ? 'friends' : 'public';

                if (self::$guid == $row['user_id']) {
                    $hideFromUser = self::$guid;
                }
                Wall::setSiteSectionItemId($photo_id_cur);
                Wall::add('photo_comment', $id, false, '', false, $hideFromUser, $access, $row['user_id']);*/
                redirect(Common::urlPage());
            }
        } elseif (!empty($comm)) {
            redirect("join.php");
        }
    }

    function onItem(&$html, $row, $i, $last) {
        global $g;
        global $l;
        global $g_user;

        parent::onItem($html, $row, $i, $last);
        $row_user = $row;
        $row_user_src = $row;
        $author_photo = $row_user['user_id'];
        $optionTmplSet = Common::getOption('set', 'template_options');
        $optionTmplName = Common::getOption('name', 'template_options');

        $html->setvar('display', User::displayProfile());

        $offset = intval(get_param("photo_offset", '-1'));
        $isAjaxRequest = get_param('ajax',0);

        $num_photo = DB::result("SELECT COUNT(photo_id) FROM photo WHERE user_id = " . $row_user['user_id'] . " " . $g['sql']['photo_vis'] . "");

        $html->setvar("num_photo", $num_photo);
        if ($num_photo > 0) {
            if (($offset > $num_photo - 1) || ($offset < 0)) {
                $photo_id = User::getPhotoDefault($row_user['user_id'], "r", true, $row['gender']);
                $offsetCurrent = User::photoOffset($row_user['user_id'], $photo_id);
            } else {
                $offsetCurrent = $offset;
                $photo_id = DB::result("SELECT `photo_id` FROM `photo` WHERE `user_id` = " . $row_user['user_id'] . " "
                                . $g['sql']['photo_vis'] . " ORDER BY `photo_id` ASC LIMIT " . $offset . ' , 1');
            }

            if ($photo_id) {
                CProfilePhoto::setMediaViews($photo_id);
                /* For compatibility with new templates */
                if ($photo_id) {
                    $photoUserId = DB::result('SELECT `user_id` FROM `photo` WHERE `photo_id` = ' . to_sql($photo_id), 0, DB_MAX_INDEX);
                    CProfilePhoto::markReadCommentsAndLikes($photo_id, $photoUserId, 'photo');
                }
                /*For compatibility with new templates */
            }

            $private_photo = DB::result("SELECT `private` FROM photo WHERE photo_id = " . to_sql($photo_id, "Numeric"));

            // FIND PREV - NEXT
            if ($num_photo > 1) {
                if ($offsetCurrent == 0) {
                    $next = $offsetCurrent + 1;
                    $prev = $num_photo - 1;
                } elseif ($offsetCurrent == $num_photo - 1) {
                    $next = 0;
                    $prev = $num_photo - 2;
                } else {
                    $next = $offsetCurrent + 1;
                    $prev = $offsetCurrent - 1;
                }
            } else {
                $next = 0;
                $prev = 0;
            }

            $html->setvar("photo_id_cur", $photo_id);

            $html->setvar("photo_id_next", $next);
            $html->setvar("photo_offset_next", $next);
            $html->setvar("photo_offset_prev", $prev);
            $html->setvar("photo_id_prev", $prev);

            if ($num_photo != 1) {
                $html->parse('yes_pagination', '');
            }

            /* Encounters && Rate people */
            $displayParams = get_param('display');
            $publicWhereSql = '';
            $paramsLink = '';
            if ($displayParams == 'rate_people') {
                $paramsLink = 'ref=rate_people&uid='. $row['user_id'];
                $publicWhereSql = ' AND `photo_id` = ' . to_sql($row['photo_rate_id'], 'Number');
            } elseif ($displayParams == 'encounters') {
                $paramsLink = 'ref=encounters&uid='. $row['user_id'];;
                $publicWhereSql = " AND `private` = 'N'";
                $html->setvar('question_encounters', l('would_you_like_to_meet_' . $row['gender']));

                $html->setvar('is_mutual_attraction_encounters', intval(MutualAttractions::isMutualAttraction($row['user_id'])));
                $html->setvar('is_attraction_from', MutualAttractions::isAttractionFrom($row['user_id']));
                $html->setvar('from_gender', $row['gender']);
                if ($html->varExists('my_photo_default')) {
                    $html->setvar('my_photo_default', User::getPhotoDefault($g_user['user_id'], 'r'));
                }
            }
            /* Encounters && Rate people  */

            $sql = "SELECT * FROM photo WHERE user_id=" . $row_user['user_id'] . $publicWhereSql . " "
                    . $g['sql']['photo_vis'] . ' ORDER BY photo_id ASC ';
            DB::query($sql, 1);

            $i = 0;
            $item = 0;
            if ($html->varExists('user_profile_param')) {
                $html->setvar('user_profile_param', $paramsLink);
            }
            $html->setvar('user_profile_link', User::url($row_user['user_id']));

            if ($displayParams == 'encounters' || $displayParams == 'rate_people') {
                $isUserReport = User::isReportUser($row_user['user_id']);
            }

            while ($row = DB::fetch_row(1)) {
                if ($i == 0 && $displayParams != 'rate_people') {
                    if ($photo_id)
                        $sql = "SELECT * FROM photo WHERE photo_id=" . to_sql($photo_id, "Text") . " AND user_id=" . $row_user['user_id'] . " " . $g['sql']['photo_vis'] . "";
                    else
                        $sql = "SELECT * FROM photo WHERE user_id=" . $row_user['user_id'] . " " . $g['sql']['photo_vis'] . " ORDER BY photo_id ASC ";

                    DB::query($sql, 2);
                    $row_b = DB::fetch_row(2);
                    $html->setvar("photo_id", $row_b['photo_id']);
                    $html->setvar("main_photo_name", $row_b['photo_name']);
                    $html->setvar("main_description_short", neat_trim(strip_tags($row_b['description']), 95));
                    $html->setvar("main_description", htmlspecialchars(strip_tags($row_b['description'])));
                    $html->setvar("main_numer", 1);
                    $html->setvar("main_photo_b", User::getPhotoFile($row_b, "b", $row_user['gender']));
                    if ($g_user['user_id'] == $row_b['user_id']) {
                        $html->parse("photo_edit", true);
                    }
                }

                $html->setvar("size_x", 400);
                $html->setvar("size_y", 400);

                $item = $i % 3 + 1;
                $html->setvar("item", $item);

                $html->setvar("numer", $i);
                $html->setvar("photo_name", strip_tags($row['photo_name']));
                $html->setvar("description", strip_tags($row['description']));
                $html->setvar("photo_name_js", str_replace("'", "\'", strip_tags($row['photo_name'])));
                $html->setvar("description_js", str_replace("'", "\'", str_replace("\n", " '", str_replace("\r", "'", strip_tags($row['description'])))));
                $photoMain = User::getPhotoFile($row, "b", $row_user['gender']);
                $html->setvar("photo_b", $photoMain);
                $html->parse("photo_b", true);

                if($html->varExists('photo_bm')) {
                    $html->setvar('photo_bm', User::getPhotoFile($row, 'bm', $row_user['gender']));
                }

                $html->setvar("photo_offset", $i);

                if ($html->varExists('photo_r')) {
                    $html->setvar('photo_r', User::getPhotoFile($row, "r", $row_user['gender']));
                }
                $html->setvar("photo_s", User::getPhotoFile($row, "s", $row_user['gender']));
                $html->parse("photo_s", true);

                $i++;
                /* Encounters && Rate people */
                if ($displayParams == 'encounters' || $displayParams == 'rate_people') {
                    $html->setvar('report_user', $isUserReport);
                    $html->setvar('reports', $row['users_reports']);
                    $html->setvar('photo_item_id', $row['photo_id']);
                    $html->setvar('photo_private', $row['private']);
                    if ($i == 1) {
                        $photoWidth = Common::getOption('profile_photo_w', 'template_options');
                        if ($photoWidth) {

                            $photoFileSizes = array($photoWidth, $photoWidth);
/*
                            if($row['width'] == 0 || $row['height'] == 0) {
                                $tmpPhotoPath = explode('?', $photoMain);
                                $filePhoto = $g['path']['dir_files'] . $tmpPhotoPath[0];
                                if(file_exists($filePhoto)) {
                                    $infoPhoto = @getimagesize($filePhoto);
                                    if(isset($infoPhoto[1])) {
                                        $photoFileSizes = array($infoPhoto[0], $infoPhoto[1]);
                                        DB::update('photo', array('width' => $infoPhoto[0], 'height' =>  $infoPhoto[1]), 'photo_id = ' . to_sql($row['photo_id']));
                                    }
                                }
                            } else {
                                $photoFileSizes = array($row['width'], $row['height']);
                            }
*/
                            $photoFileSizes = CProfilePhoto::getAndUpdatePhotoSize($row, $photoMain, $photoWidth);

                            $html->setvar('photo_width', $photoFileSizes[0]);
                            $html->setvar('photo_height', $photoFileSizes[1]);
                        }
                    } else {
                        $html->parse('photo_big_item_hide', false);
                    }
                    $html->parse('photo_big_item', true);
                    if ($html->blockExists('photo_carousel_item')) {
                        $html->parse('photo_carousel_item', true);
                    }
                    if ($i == 3) {
                       break;
                    }

                }
                /* Encounters && Rate people */
            }
             /* Encounters && Rate people */
            if ($html->blockExists('photo_carousel') && $i > 1) {
                if ($isAjaxRequest) {
                    $html->parse('photo_carousel_hide');
                }
                $html->parse('photo_carousel');
            }

            if ($html->blockExists('photo_big') && $i > 0) {

                if ($isAjaxRequest && $html->blockExists('update_counter_mutual')) {
                    $html->setvar('counter_mutual', MutualAttractions::getNumberMutualAttractions());
                    $html->parse('update_counter_mutual');
                }
                $html->setvar('param_uid', get_param('uid', 0));
                $html->parse('photo_big');
                $html->parse('encounters');

                if ($displayParams == 'rate_people') {
                    if ($isAjaxRequest) {
                        $sql = 'SELECT `rated_photos`, `last_photo_visible_rated`
                                  FROM `user` WHERE `user_id` = ' . to_sql($g_user['user_id'], 'Number');
                        $userInfo = DB::row($sql);
                        $userLastVisibleRated = $userInfo['last_photo_visible_rated'];
                        $userRatedPhotos = $userInfo['rated_photos'];
                    } else {
                        $userLastVisibleRated = $g_user['last_photo_visible_rated'];
                        $userRatedPhotos = $g_user['rated_photos'];
                    }
                    $sql = 'SELECT * FROM `photo`
                             WHERE `user_id` = ' . to_sql($g_user['user_id'], 'Number') .
                             ' AND `photo_id` > ' . to_sql($userLastVisibleRated, 'Number') .
                             ' AND `average` > 0
                             ORDER BY RAND(), photo_id LIMIT 1';
                    $randPhoto = DB::row($sql);
                    $randPhotoId = 0;
                    if (!empty($randPhoto)) {
                        $randPhotoId = $randPhoto['photo_id'];
                        $randPhotoAverage = $randPhoto['average'];
                    }

                    $vars = array();
                    $nextStep = intval(Common::getOption('rate_see_my_photo_rating'));
                    if ($randPhotoId && $nextStep) {
                        $scale = 100/$nextStep;
                        $countNextSee = $nextStep - $userRatedPhotos;
                        $countNextSeeSl = $userRatedPhotos*$scale;
                        $userRatedPhotos = User::getInfoBasic($g_user['user_id'], 'rated_photos');
                        $vars = array('next_see' => $countNextSee,
                                      'next_slider' => $countNextSeeSl);
                    }
                    if ($isAjaxRequest) {
                        $html->setvar('rating_info', json_encode($vars));
                        $html->parse('rating_info');
                    } else {
                        $blockRatePeople = 'rate_people';
                        if ($randPhotoId && $nextStep) {
                            $blockRating = $blockRatePeople . '_rating';
                            $randPhotoUrl = User::getPhotoFile($randPhoto, "r", $g_user['gender']);
                            $html->setvar($blockRating . '_photo', $randPhotoUrl);
                            $html->setvar($blockRating . '_photo_id', $randPhotoId);
                            $html->setvar('hidden_average', ratingFloatToStrTwoDecimalPoint($randPhotoAverage));
                            $vars = array('count' => $countNextSee);
                            $html->setvar($blockRating . '_next_see', lSetVars('rate_more_photos_see_the_rating_on_your_photo', $vars));
                            $html->setvar($blockRating . '_next_see_slider', $countNextSeeSl);
                            $html->setvar($blockRating . '_next_see_count', $countNextSee);
                            $html->parse($blockRating);
                        }
                        $html->parse($blockRatePeople);
                    }
                }
            }
            if ($html->blockExists('show_btn_rate_people')) {
                $html->parse('show_btn_rate_people', false);
                $html->parse('not_show_no_one_found', false);
            }
             /* Encounters && Rate people */
            // SHOW NOTHING BLOCKS
            $add = (3 - $item) % 3;
            if ($add > 0) {
                for ($n = 0; $n < $add; $n++) {
                    $html->setvar("item", $item + 1 + $n);
                    $html->parse("photo_no", true);
                }
            }
            // SHOW NOTHING BLOCKS

            // COMMENTS
            if (($displayParams != 'encounters' && $displayParams != 'rate_people') && ($private_photo == 'N'
                    || User::isFriend(self::$guid, $row_user['user_id'])
                    || $row_user['user_id'] == self::$guid)) {

            $where = ($optionTmplSet == 'urban') ? '' : ' AND `system` = 0';
            DB::query("SELECT * FROM photo_comments WHERE photo_id=" . $photo_id . $where . " ORDER BY id DESC");
            $count = DB::num_rows();

            $html->setvar("num_comments", $count);
            for ($i = 0; $i < $count; $i++) {
                if ($row = DB::fetch_row()) {
                    $row['user_id'] = intval($row['user_id']);
                    $row_user = User::getInfoBasic($row['user_id'], false, 2);

                    if (!$row_user) {
                        continue;
                    }

                    $name = $row_user['name'];

                    $user_photo = User::getPhotoDefault($row['user_id'], "r", false, $row_user['gender']);
                    $html->setvar("photo", $user_photo);

                    $html->setvar("date", Common::dateFormat($row['date'], 'users_photo_date'));
                    $html->setvar("comment_text", to_html(Common::parseLinksSmile($row['comment']), true, true));
                    $html->setvar("user_name", $name);
                    $html->setvar("cid", $row['id']);
                    $html->setvar("pid", $photo_id);

                    if ((intval($row['user_id']) === intval($g_user['user_id'])) or (intval($author_photo) === intval($g_user['user_id']))){
                        $html->parse("delete_comment",False);
                    } else {
                        $html->setblockvar('delete_comment', '');
                    }
                    $html->setvar("user_photo", $user_photo);

                    if ($name == "") {
                        $html->parse("anonim_comment", false);
                        $html->setblockvar("user_comment", "");
                    } else {
                        $html->parse("user_comment", false);
                        $html->setblockvar("anonim_comment", "");
                    }

                    $html->setvar("num", $i);
                    $html->setvar("user_age", $row_user['age']);
                    $html->setvar("user_country_sub", $row_user['country']);
                    $html->parse("show_info", true);
                    $html->parse("comment", true);
                }
            }
            $html->parse("comment_form", true);
            // COMMENTS
            }
            if ($num_photo > 1) {
                $html->parse("photo_link");
                $html->parse("photo_link_2");
            }

            $fileTop = Common::getOption('main', 'tmpl') . '_top5user.png';
            if (!Common::isOptionActive('restore_upload_image_top_five_button')
                && Common::isOptionActive('top_five_button', 'template_options')
                && isUsersFileExists('tmpl', $fileTop)) {
                $html->setvar('top_five_file', $fileTop);
            } else {
                $html->setvar('top_five_file', 'top5.png');
            }

            $html->setvar('name_profile', lSetVars('name_profile', array('name' => $row_user_src['name'])));
            $html->parse("yes_photo", true);
        } else {
            if (isset($row['name'])) {
                $html->setvar('has_no_photos', lSetVars('has_no_photos', array('name' => $row_user_src['name'])));
            }
            $html->parse("no_photo", true);

            if ($row_user['user_id'] == self::$guid) {
                redirect('profile_photo.php');
            }
        }

        if ($optionTmplName == 'urban_mobile') {
            $prf = Common::getOption('custom_profile_html', 'template_options');
            $profileHtml = new CUsersProfileHtml($prf, null, false, false, true);
            $profileHtml->formatValue = 'html';
            $profileHtml->mode = 'view';
            $this->add($profileHtml);
            $profileHtml->setUser($row_user['user_id']);
            $profileHtml->parseBlock($html);
        }
    }

}

class CUsersFriends extends CUsers {

    var $m_on_page = 1;

    function parseBlock(&$html) {
        parent::parseBlock($html);
    }

    function onItem(&$html, $row, $i, $last) {
        global $g;
        global $l;
        global $g_user;

        parent::onItem($html, $row, $i, $last);

        $html->setvar('display', User::displayProfile());

        #$html->setvar('profile_name', User::nameAddPostfix(User::nameShort($row['name'])));
        $html->setvar('profile_name', $row['name']);

        // base sql
        // count sql
        // listing

        $sqlBase = 'FROM friends_requests
            WHERE ( user_id = ' . to_sql($row['user_id'], 'Number') . '
               OR friend_id = ' . to_sql($row['user_id'], 'Number') . ' )
              AND accepted = 1
            ORDER BY activity DESC';

        $sqlCount = 'SELECT COUNT(*) ' . $sqlBase;
        $count = DB::result($sqlCount, 0, 2);

        $html->setvar('num_users', $count);

        if ($count) {

            $start = get_param('start', 0);
            $limit = 10;
            $sql = 'SELECT * ' . $sqlBase . '
                LIMIT ' . to_sql($start, 'Number') . ', ' . to_sql($limit, 'Number');
            DB::query($sql, 2);

            $sep = 0;

            while ($friend = DB::fetch_row(2)) {
                $fid = isset($friend['fr_user_id']) ? $friend['fr_user_id'] : (($friend['user_id'] == $row['user_id']) ? $friend['friend_id'] : $friend['user_id']);

                $photo = User::getPhotoDefault($fid, 'r');

                $row_user = User::getInfoBasic($fid, false, 3);

                $html->setvar("user_name", $row_user['name']);
                $html->setvar("user_photo", $photo);
                $html->setvar("user_id", $row_user['user_id']);
                $html->setvar("age", $row_user['age']);

                $city = !empty($row_user['city']) ? l($row_user['city']) : '';
                $state = !empty($row_user['state']) ? l($row_user['state']) : '';
                $country = !empty($row_user['country']) ? l($row_user['country']) : '';

                $html->setvar('country', $country);
                $html->setvar('city', $city);
                $html->setvar('state', $state);
                if ($country != ''){
                    $html->parse('country_title', false);
                }else{
                    $html->setblockvar('country_title', '');
                }
                if ($city != ''){
                    $html->parse('city_title', false);
                }else{
                    $html->setblockvar('city_title', '');
                }

                if (trim($city) != '' && trim($state) != '') {
                    $html->setvar('location_delimiter_one', $this->locationDelimiterOne);
                } else {
                    $html->setvar('location_delimiter_one', '');
                }

                if (trim($country) != '' && (trim($state) != '' || trim($city) != '')) {
                    $html->setvar('location_delimiter_second', $this->locationDelimiterSecond);
                } else {
                    $html->setvar('location_delimiter_second', '');
                }



                if (self::$guid != $row['user_id'] && self::$guid != $fid && User::isFriend(self::$guid, $fid, 3)) {
                    $html->parse('mutual_friend', false);
                } else {
                    $html->setblockvar('mutual_friend', '');
                }

                $sep++;
                if ($sep == 2) {
                    $html->parse('sep_block_users', true);
                    $sep = 0;
                }
                if (is_array($row_user)) {
                    $html->parse('item_block_users', true);
                }
            }
            $html->parse('block_users', true);

            Common::parsePagesList($html, 'top', $count, $start, $limit);
            Common::parsePagesList($html, 'down', $count, $start, $limit);
        } else {
            $html->parse('no_friends', true);
        }
    }

}