<?php

include('./_include/core/main_start.php');

checkByAuth();
if($g_user['role'] == "user")
	redirect('search_results');

$gadmin_previllage = explode(',', $g_user['gadmin_previllage']);
if($gadmin_previllage[0] == 0)
    redirect('group_users');

class CGroupUsersAdd extends CHtmlBlock
{
	var $message = "";

	function action() {
        global $g, $g_user;

		$cmd = get_param('cmd', '');
		if ($cmd == 'get_group_user_data') {
			$group_admin_id = guid();
			$result = DB::all("
				SELECT a.user_id, a.name, a.name_seo, a.mail, a.phone, a.register,
				(DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(a.birth, '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(a.birth, '00-%m-%d'))
				) AS age,
				(SELECT title FROM const_orientation WHERE id = a.orientation) AS gender
                FROM user a WHERE a.under_admin = '".$group_admin_id."' ORDER BY a.register
            ");
			echo json_encode($result);
            die();
		} elseif ($cmd == 'insert') {

            $this->message = "";
            $orientation = get_param('orientation', 'Number');
            
            $name = trim(get_param('username'));

            $phone = trim(get_param('phone'));

            $mail = get_param('email', '');

            $month  = (int) get_param('month', 1);
            $day    = (int) get_param('day', 1);
            $year   = (int) get_param('year', 1980);

            $country = get_param('country', '');
            $state   = get_param('state', '');
            $city    = get_param('city', '');

            // $this->message .= User::validateName($name);
            // $this->message .= Common::validateField('mail', $mail) ? l('exists_email') . '<br>' : '';
            // $this->message .= Common::validateField('phone', $phone) ? l('phone_email') . '<br>' : '';

            $fileTemp = $g['path']['dir_files'] . 'temp/admin_upload_user_profile_' . time();
            Common::uploadDataImageFromSetData($fileTemp, 'photo_file');
            $this->message .= User::validatePhoto("photo_file");

            if ($this->message == '')
            {
                $register = date("Y-m-d H:i:s");
                $name_seo = Common::uniqueNameSEO($name);

                $date = strtotime($year . '-' . $month . '-' .  $day);
                $birth = date('Y-m-d', $date);
                $h = zodiac($birth);

                $query = "
                    INSERT INTO user (
                        role, under_admin, name, name_seo, password, mail, country_id, state_id, city_id, country, state, city, birth, orientation, horoscope, register, last_ip, active, use_as_online, phone
                    )
                    VALUES (
                        ".to_sql('user', 'Text').",
                        ".$g_user['user_id'].",
                        ".to_sql($name, 'Text').",
                        ".to_sql($name_seo, 'Text').",
                        ".to_sql($g_user['password'], 'Text').",
                        ".to_sql($mail, "Text").",
                        ".to_sql($country, "Number").",
                        ".to_sql($state, "Number").",
                        ".to_sql($city, "Number").",
                        ".to_sql(Common::getLocationTitle('country', $country), 'Text').",
                        ".to_sql(Common::getLocationTitle('state', $state), 'Text').",
                        ".to_sql(Common::getLocationTitle('city', $city), 'Text').",
                        '". $birth . "',
                        ".to_sql($orientation, 'Number').",
                        ".$h.",
                        '".$register."',
                        ".to_sql(IP::getIp()).",
                        0,
                        1,
                        ".to_sql($phone, "Text")."
                    )
                ";
                DB::execute($query);

                $q = DB::row("SELECT user_id FROM user WHERE register = '".$register."' ORDER BY register DESC LIMIT 1");
                $newUserID = $q['user_id'];
                DB::execute("INSERT INTO userinfo (user_id) VALUES (".$newUserID.")");
                DB::execute("INSERT INTO userpartner (user_id) VALUES (".$newUserID.")");

                // upload photo
                $g['options']['photo_approval'] = 'N';
                $g['options']['nudity_filter_enabled'] = 'N';
                uploadphoto($newUserID, '', 'upload', 1, '../', false, 'photo_file', get_param('private'));

                $this->message = 'success';
            }
            
            echo $this->message;
            die();
        } elseif($cmd == 'location') {
            $param  = get_param('param');
            $method = 'list' . get_param('method');
            echo Common::$method($param, -1);
            die();
        }
	}
	function parseBlock(&$html)
	{
		global $g;
        global $l;
        global $p;
        global $g_user;

		$html->setvar("page_title", l('add_group_user'));
		$html->setvar("gadmin_previllage", $g_user['gadmin_previllage']);


        $cmd = get_param('cmd');
        $optionTmplName = Common::getTmplName();

        $isIos = Common::isAppIos();

        $formatDateMonths = 'F';
        $optionFormatDateMonths = Common::getOption('format_date_months_join', 'template_options');
        if ($optionFormatDateMonths) {
            $formatDateMonths = $optionFormatDateMonths;
        }

        $defaultBirthday = Common::getDefaultBirthday();
        $defaultDay = $defaultBirthday['day'];
        $defaultMonth = $defaultBirthday['month'];
        $defaultYear = $defaultBirthday['year'];
        if ($isIos) {
            $defaultDay = 0;
            $defaultMonth = 0;
            $defaultYear = 0;
        }
        $vars = array(
            'autocomplete' => autocomplete_off(),
            'join_message' => $this->message,
            'month_options' => h_options(Common::plListMonths($formatDateMonths, $isIos), get_param('month', $defaultMonth)),
            'day_options' => n_options(1, 31, get_param('day', $defaultDay), $isIos),
            'year_options' => n_options(date('Y') - $g['options']['users_age_max'], date("Y") - $g['options']['users_age'], get_param("year", $defaultYear), $isIos),
            //'orientation_options' => DB::db_options("SELECT id, title FROM const_orientation", get_param("orientation", "")),
            'looking_options' => DB::db_options("SELECT id, IF(title!='',CONCAT('join_',title),title)  FROM const_looking", get_param("looking", '')),
            'language_value' => $g['lang_loaded'],
            'orientation_class' => ''
        );

        $isParseBlockIAm = false;
        if (UserFields::isActive('orientation')) {
            $vars['orientation_class'] = 'orientation_bl';
            $default = 0;
            $selectedOrientation = 0;
            if (!$isIos) {
                $default = DB::result('SELECT `id` FROM `const_orientation` WHERE `default` = 1', 0, 1);
                $selectedOrientation = get_param("orientation", $default);
            }
            $vars['orientation_options'] = '';
            if (!$default){
                $lPleaseChoose = l('please_choose');
                if ($optionTmplName == 'edge') {
                    $lPleaseChoose = l('i_am');
                }
                $vars['orientation_options'] = '<option value="0" selected="selected">' . $lPleaseChoose . '</option>';
            }
            $vars['orientation_options'] .= DB::db_options("SELECT id, title FROM const_orientation ORDER BY id ASC", $selectedOrientation);
            $isParseBlockIAm = true;
        }

        $defaultCountry = 0;
        $defaultState = 0;
        $defaultCity = 0;
        //$geoInfo = IP::geoInfoCity();
        /*$geoInfo = getDemoCapitalCountry();
        if ($geoInfo) {
            $selectedCountry = $geoInfo['country_id'];
            $selectedState = $geoInfo['state_id'];
            $selectedCity = $geoInfo['city_id'];
        }*/

        /*if (!$isIos) {
            $defaultCountry = $selectedCountry;
            $defaultState = $selectedState;
            if ($cmd != 'fb_login') {
                $defaultState = get_param('state', $defaultState);
            }
            $defaultCity = get_param('city', $selectedCity);
        }*/

        $isSetDefaultJoin = false;
        /*
        if ($optionTmplName == 'impact_mobile' && $isUploadPageAjax) {//Set default locations retry join frm
            $selectedJoinCountry = get_cookie('impact_mobile_join_country_default', true);
            if ($selectedJoinCountry != '') {
                $isSetDefaultJoin = true;
                $selectedCountry = $selectedJoinCountry;
                $defaultCountry = $selectedJoinCountry;

                $selectedState = get_cookie('impact_mobile_join_state_default', true);
                $defaultState = $selectedState;

                $defaultCity = get_cookie('impact_mobile_join_city_default', true);

                $isIos = true;
            }
        }*/

        if($html->varexists('country_options')) {
            Common::setPleaseChoose(l('choose_a_country'));
            $vars['country_options'] = Common::listCountries($defaultCountry, true, false, $isIos);
        }

        if($html->varexists('state_options')) {
            $vars['state_options'] = Common::listStates(19, $defaultState, false, $isIos);
        }

        /*if($html->varexists('city_options')) {
            //$citySelected = get_param('city', $geoInfo['city_id']);
            if(($isIos && !$defaultState) || ($isSetDefaultJoin && !$defaultState && !$defaultCity)){
                $vars['city_options'] = "<option value=\"0\" selected=\"selected\">" . l('choose_a_city') . "</option>";
            } else {
                Common::setPleaseChoose(l('choose_a_city'));
                $vars['city_options'] = Common::listCities($selectedState, $defaultCity, false, $isIos);
            }
        }*/

        $vars['username_length'] = $g['options']['username_length'];
        $vars['username_length_min'] = $g['options']['username_length_min'];
        $vars['max_min_length_username'] = sprintf(toJsL('max_min_length_username'), $g['options']['username_length_min'], $g['options']['username_length']);

        $vars['mail_length_max'] = $g['options']['mail_length_max'];

        htmlSetVars($html, $vars);
		parent::parseBlock($html);
	}
}
$dirTmpl = $g['tmpl']['dir_tmpl_main'];
Common::mainPageSetRandomImage();
$page = new CGroupUsersAdd("", getPageCustomTemplate('group_users_add.html', 'custom_page_template'));
$header = new CHeader("header", $dirTmpl . "_header.html");
$page->add($header);

$footer = new CFooter("footer", $dirTmpl . "_footer.html");
$page->add($footer);

include("./_include/core/main_close.php");