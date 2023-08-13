<?php

include("../_include/core/administration_start.php");

class CAddUser extends UserFields //CHtmlBlock
{
    var $message = "";
    function action() {
        global $g;
        global $p;

        $cmd = get_param('cmd', '');
        $optionsSet = Common::getOption('set', 'template_options');
        $optionsTmplName = Common::getOption('name', 'template_options');

        if ($cmd == 'insert') {

            $this->message = "";
            $orientation = get_param('orientation', 'Number');
            
            $name = trim(get_param('username'));
            $this->message .= User::validateName($name);

            $phone = trim(get_param('phone'));
            $this->message .= User::checkExistPhone($phone);

            $password = trim('deshiwedding');
            $password = User::preparePasswordForDatabase($password);

            $mail = get_param('email', '');

            $month  = (int) get_param('month', 1);
            $day    = (int) get_param('day', 1);
            $year   = (int) get_param('year', 1980);

            $country = get_param('country', '');
            $state   = get_param('state', '');
            $city    = get_param('city', '');

            $this->message .= User::validatePassword($password, 4, 100);
            $this->message .= User::validate('email,birthday,country');

            $fileTemp = $g['path']['dir_files'] . 'temp/admin_upload_user_profile_' . time();
            Common::uploadDataImageFromSetData($fileTemp, 'photo_file');
            $this->message = User::validatePhoto("photo_file");

            if ($this->message == '')
            {
                $register = date("Y-m-d H:i:s");
                $under_admin = get_session('groupAdmin_id') ? get_session('groupAdmin_id') : "NULL";

                $name_seo = to_sql(Router::prepareNameSeo($name));
                $h = zodiac($year . '-' . $month . '-' .  $day);
                $query = "
                    INSERT INTO user (
                        role, under_admin, name, name_seo, password, mail, country_id, state_id, city_id, country, state, city, birth, orientation, horoscope, register, last_ip, active, use_as_online, phone
                    )
                    VALUES (
                        ".to_sql('user', 'Text').",
                        ".$under_admin.",
                        ".to_sql($name, 'Text').",
                        ".to_sql($name_seo, 'Text').",
                        ".to_sql($password, 'Text').",
                        ".to_sql($mail, "Text").",
                        ".to_sql($country, "Number").",
                        ".to_sql($state, "Number").",
                        ".to_sql($city, "Number").",
                        ".to_sql(Common::getLocationTitle('country', $country), 'Text').",
                        ".to_sql(Common::getLocationTitle('state', $state), 'Text').",
                        ".to_sql(Common::getLocationTitle('city', $city), 'Text').",
                        ". $year . "-" . $month . "-" .  $day . ",
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
        $optionsSet = Common::getOption('set', 'template_options');
        $optionsTmplName = Common::getOption('name', 'template_options');
        $html->setvar('message', $this->message);


		parent::parseBlock($html);
	}
}

$page = new CAddUser('', $g['tmpl']['dir_tmpl_administration'] . 'add_group_user.html', false, false, false, 'profile');
$page->formatValue = 'entities';

$header = new CAdminHeader("header", $g['tmpl']['dir_tmpl_administration'] . "_header.html");
$page->add($header);
$footer = new CAdminFooter("footer", $g['tmpl']['dir_tmpl_administration'] . "_footer.html");
$page->add($footer);

$page->add(new CGroupAdminPageMenuUsers());
include("../_include/core/administration_close.php");