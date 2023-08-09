<?php
/* (C) Websplosion LLC, 2001-2021

IMPORTANT: This is a commercial software product
and any kind of using it must agree to the Websplosion's license agreement.
It can be found at http://www.chameleonsocial.com/license.doc

This notice may not be removed from the source code. */

include("../_include/core/administration_start.php");

$uid = get_param('id', 'Number');
$cmd = get_param('cmd', '');

if($cmd != 'location') {
	$g_user = User::getInfoFull(get_param('id', ''));
	if(!isset($g_user['user_id'])) {
		redirect('users_results.php');
	}

    // group admin
    $groupAdmin_id = get_session('groupAdmin_id');
    if($groupAdmin_id) {
        $my_info = DB::result("SELECT COUNT(*) FROM user WHERE under_admin = $groupAdmin_id AND user_id = ".$g_user['user_id']);
        if($my_info == 0)
            redirect('group_admin_panel.php');
    }
}

class CForm extends UserFields //CHtmlBlock
{
	var $message = "";
	function action() {
        global $g;
        global $g_user;
        global $p;

		$cmd = get_param('cmd', '');
        $optionsSet = Common::getOption('set', 'template_options');
        $optionsTmplName = Common::getOption('name', 'template_options');

        if ($cmd == 'update') {

            $this->message = "";
            $orientation = get_param('orientation', $g_user['orientation']);

            $name = trim(get_param('username'));
            $this->message .= User::validateName($name);

            $phone = trim(get_param('phone'));
            $this->message .= User::checkExistPhone($phone);

            $password = trim(get_param('password'));
            $mail = get_param('email', '');

            $month  = (int) get_param('month', 1);
            $day    = (int) get_param('day', 1);
            $year   = (int) get_param('year', 1980);

            $country = get_param('country', '');
            $state   = get_param('state', '');
            $city    = get_param('city', '');

            $this->message .= User::validatePassword($password, 4, 100);
            $this->message .= User::validate('email,birthday,country');
            $this->verification('admin');

            if ($this->message == '')
            {
                if ($optionsTmplName != 'edge') {
                    $selectionFileds = 'join';
                    if ($optionsSet == 'urban') {
                        $this->updateLookingFor($g_user['user_id']);
                        $selectionFileds = 'update_admin_urban';
                    } elseif(self::isActive('orientation') && !Common::isOptionActive('your_orientation')) {
                        User::update(array('p_orientation' => get_checks_param('p_orientation')));
                    }

                    $this->updatePartner(get_param('id', ''));
                } else {
                    $selectionFileds = 'update_admin_urban';
                }
                $this->updateInfo(get_param('id', ''), $selectionFileds);


                $h = zodiac($year . '-' . $month . '-' .  $day);

                $setSql = '';

                $goldDays = get_param('gold_days', 0);
                $type = get_param('type', 'none');

                if (($g_user['gold_days'] != $goldDays && $g_user['type'] == $type)
                        ||($g_user['gold_days'] == $goldDays && $g_user['type'] != $type)
                        ||($g_user['gold_days'] != $goldDays && $g_user['type'] != $type)){

                    if($g_user['gold_days'] != $goldDays){
                        $timeStamp=time()+3600;  //+60 minutes
                        $date=date('Y-m-d',$timeStamp);
                        $hour=intval(date('H',$timeStamp));

                        $setSql.=', payment_day='.to_sql($date).', payment_hour='.to_sql($hour).' ';
                    }
                    User::upgradeCouple($g_user['user_id'], $goldDays, $type);
                }
                if ($goldDays == 0){
                    $type = 'none';
                }

                if ($optionsSet == 'urban') {
                    $setSql .= ', credits = ' . to_sql(get_param('credits'), 'Number');
                    if ($goldDays > 0) {
                        $type = 'membership';
                        $setSql .= ", sp_sending_messages_per_day = 0";
                    } else {
                        if (!User::isAllowedInvisibleMode(false)) {
                            $setSql .= ", set_hide_my_presence = '2', set_do_not_show_me_visitors = '2'";
                        }
                    }
                }

                if($optionsTmplName == 'edge' && $goldDays) {
                    $type = 'membership';
                }

                if ($password != $g_user['password']) {
                    $password = User::preparePasswordForDatabase($password);
                }

                $isAdmin = get_param('user_admin');
                $setSql .= ', moderator_photo = ' . to_sql(get_param('moderator_photo'), 'Number') . ',
                             moderator_texts = ' . to_sql(get_param('moderator_texts'), 'Number') . ',
                             moderator_vids_video = ' . to_sql(get_param('moderator_vids_video'), 'Number') . ',
							 moderator_profiles = ' . to_sql(get_param('moderator_profiles'), 'Number') . ',
                             admin = ' . to_sql($isAdmin, 'Number') . ',
                             type = ' . to_sql($type);

                if($name != $g_user['name']) {
                    $setSql .= ', `name_seo` = ' . to_sql(Router::getNameSeo($name, $g_user['user_id'], 'user'));
                }

                if ($isAdmin) {
                    DB::update('user', array('admin' => 0));
                    $adminUserId = $g_user['user_id'];
                } else {
                    $adminUserId = DB::result('SELECT `user_id` FROM `user` WHERE `admin` = 1');
                }
                Config::update('options', 'admin_user_id', $adminUserId);

                $user_role = get_param("user_role");
                $role_hidden = get_param("user_role_hidden");

                $have_under_user = DB::result("SELECT COUNT(user_id) FROM user WHERE under_admin=".$g_user['user_id']);
                $role = get_param("user_role") || $have_under_user ? "group_admin" : "user";

                $under_admin_data = get_param("under_admin");

                $under_admin = "";
                if(!empty(get_param("under_admin"))) {
                    if($under_admin_data == "NO")
                        $under_admin = "under_admin = NULL, ";
                    else
                        $under_admin = "under_admin = ".$under_admin_data .",";
                }

                // group admin never stay under group admin
                if($role == "group_admin") {
                    $under_admin = "under_admin = NULL, ";

                    // group admin access
                    $editAccess = get_param("editAccess") ? get_param("editAccess") : 0;
                    $deleteAccess = get_param("deleteAccess") ? get_param("deleteAccess") : 0;
                    $banAccess = get_param("banAccess") ? get_param("banAccess") : 0;

                    $gadmin_data = $editAccess.','.$deleteAccess.','.$banAccess;
                    $gadmin_previllage = "gadmin_previllage = '".$gadmin_data ."',";
                } else 
                    $gadmin_previllage = "gadmin_previllage = '0,0,0',";
                
                DB::execute("UPDATE user SET
                                role = " . to_sql($role, 'Text') . ",
                                " . $under_admin . "
                                " . $gadmin_previllage . "
                                name = " . to_sql($name, 'Text') . ",
                                password = " . to_sql($password, 'Text') . ",
                                phone = " . to_sql($phone, 'Text') . ",
                                gold_days=" . to_sql($goldDays, "Number") . ",
                                type=" . to_sql($type, "Text") . ",
                                mail=" . to_sql($mail, "Text") . ",
                                country_id=" . to_sql($country, "Number") . ",
                                state_id=" . to_sql($state, "Number") . ",
                                city_id=" . to_sql($city, "Number") . ",
                                country=" . to_sql(Common::getLocationTitle('country', $country), 'Text') . ",
                                state=" . to_sql(Common::getLocationTitle('state', $state), 'Text') . ",
                                city=" . to_sql(Common::getLocationTitle('city', $city), 'Text') . ",
                                birth='" . $year . "-" . $month . "-" .  $day . "',
                                horoscope='" . $h . "',
                                relation='" . ((int) get_param("relation", $g_user["relation"]) . "'") . ",
                                use_as_online = " . to_sql(get_param('use_as_online'), 'Number') .
                                $setSql . "
                            WHERE user_id=" . $g_user['user_id'] . ";
                ");

                if(guser('city_id') != $city) {
                    User::updateGeoPosition($city);
                }

                User::emailChange(guser('mail'), $mail);

                User::setOrientation($g_user['user_id'], $orientation);

                $status = get_param('profile_status', false);
                if ($status !== false) {
                    $status = htmlentities($status, ENT_QUOTES, 'UTF-8');
                    User::updateProfileStatus($status);
                }

                redirect("$p?id=".get_param("id")."&action=saved");
            }
    	} elseif ($cmd == "insert_photo") {
    			$fileTemp = $g['path']['dir_files'] . 'temp/admin_upload_user_profile_' . time();
    			Common::uploadDataImageFromSetData($fileTemp, 'photo_file');

                $description = get_param("description", "");

                $this->message = User::validatePhoto("photo_file");

                if ($this->message == "")
                {
                    $g['options']['photo_approval'] = 'N';
                    $g['options']['nudity_filter_enabled'] = 'N';

                    uploadphoto($g_user['user_id'], '', $description, 1, '../', false, 'photo_file', get_param('private'));
                    redirect("$p?id=".get_param("id")."&action=saved");
                }
        } elseif ($cmd == "delete_photo") {

                $photo_id = get_param("photo_id", 0);
                if ($photo_id == 0)
    			{
    				return;
    			}

    			deletephoto($g_user['user_id'], $photo_id);

    			redirect("$p?id=".get_param("id")."&action=delete");
        } elseif ($cmd == "approve_photo") {
            $photo_id = intval(get_param('photo_id'));
            Moderator::setNotificationTypePhoto();
            User::photoApproval($photo_id, 'add');
            Moderator::sendNotificationApproved();
            redirect("$p?id=".get_param("id")."&action=saved");
        } elseif($cmd == 'location') {
            $param  = get_param('param');
            $method = 'list' . get_param('method');
            echo Common::$method($param, -1);
            die();
        } elseif($cmd == 'add_spotlight') {
            $uid = get_param('id');
            Spotlight::addItem($uid);
            redirect("{$p}?id={$uid}&action=saved");
        } elseif($cmd == 'remove_spotlight') {
            $uid = get_param('id');
            Spotlight::removeItem($uid);
        	redirect("{$p}?id={$uid}&action=saved");
        } elseif($cmd == 'set_photo_access') {
            $uid = get_param('id');
            CProfilePhoto::setPhotoPrivate(get_param('photo_id'), true);
            redirect("{$p}?id={$uid}&action=saved");
        }
	}
	function parseBlock(&$html)
	{
        global $g;
        global $g_user;
        global $l;

        $optionsSet = Common::getOption('set', 'template_options');
        $optionsTmplName = Common::getOption('name', 'template_options');
        $html->setvar('message', $this->message);

        $checked = 'checked';

        $html->setvar('field_physical_datails', l('physical_datails'));
        if($g_user['moderator_photo']) {
            $html->setvar("moderator_photo", $checked);
        }
        if($g_user['moderator_texts']) {
            $html->setvar("moderator_texts", $checked);
        }
        if($g_user['moderator_vids_video']) {
            $html->setvar("moderator_vids_video", $checked);
        }
		if($g_user['moderator_profiles']) {
            $html->setvar("moderator_profiles", $checked);
        }

        $html->parse('moderator');

        if($g_user['admin']) {
            $html->setvar('checked_user_admin', $checked);
        }

        $l = loadLanguageAdmin();

        //$g_user = User::getInfoFull(get_param("id", ""));

		$html->setvar('url_profile', User::url($g_user['user_id']));
        $html->setvar("user_id", $g_user['user_id']);
        $html->setvar('username_length', $g['options']['username_length']);
        $html->setvar('paid_days_length', Common::getOption('paid_days_length'));
        $html->setvar("gold_days", $g_user['gold_days']);
        $html->setvar("user_name", $g_user['name']);
        $html->setvar("password", $g_user['password']);

        $gadmin_previllage = explode(",", $g_user['gadmin_previllage']);
        if($gadmin_previllage[0])
            $html->setvar("editCheck", $checked);
        if($gadmin_previllage[1])
            $html->setvar("deleteCheck", $checked);
        if($gadmin_previllage[2])
            $html->setvar("banCheck", $checked);

		if ($html->varExists('user_photo')) {
			$html->setvar('user_photo', User::getPhotoDefault($g_user['user_id'], 'm'));
		}
		if ($html->varExists('country_title')) {
			$html->setvar('country_title', l($g_user['country']));
		}
		if ($html->varExists('state_title')) {
			$html->setvar('state_title', l($g_user['state']));
		}
		if ($html->varExists('city_title')) {
			$html->setvar('city_title', l($g_user['state']));
		}

        $optionsSet = Common::getOption('set', 'template_options');
        if ($optionsSet == 'urban') {
            $html->parse('menu_im');
            $html->setvar('field_physical_datails', l('appearance'));
            $html->setvar('credits', $g_user['credits']);
            $html->parse('user_credits');
            if (Common::isParseModule('people_nearby_spotlight')) {
                if ($g_user['is_photo_public'] != 'N') {
                    $html->parse('menu_add_spotlight');
                }
                if (Spotlight::isThere()) {
                    $html->parse('menu_remove_spotlight');
                }
            }
        } else {
            $html->parse('menu_editblog');
            $html->parse('menu_im');
            $html->parse('menu_chat');
            $html->parse('menu_mail');
        }

		if (Common::isOptionActive('videogallery')) {
			$html->parse('menu_user_video');
		}

        $html->parse('user_gold_days');

        if ($optionsTmplName != 'urban' && $optionsTmplName != 'edge') {
            $profileStatus = DB::one('profile_status', '`user_id` = '. to_sql($g_user['user_id']));
            if($profileStatus) {
                $html->setvar('profile_status', $profileStatus['status']);
            }

            $profileStatusMaxLength = Common::getOptionTemplateInt('profile_status_max_length');
            if (!$profileStatusMaxLength) {
                $profileStatusMaxLength = 25;//OLD template
            }
            $html->setvar('profile_status_max_length', $profileStatusMaxLength);
            $html->parse('profile_status');
        }
        
        $html->setvar('access_display', 'style="display: none"');

        if($g_user['use_as_online']) {
            $html->setvar("use_as_online", $checked);
        }

        $user_id = $g_user['user_id'];
        $html->setvar("role", $g_user['role']);

        if($g_user['role'] == "group_admin") { // group admin
            $html->setvar("user_role", $checked);
            $html->setvar('access_display', '');

            $have_under_user = DB::result("SELECT COUNT(user_id) FROM user WHERE under_admin=$user_id");
            if($have_under_user) {
                $html->setvar("roleDisabled", 'disabled');
            }
            
            $html->setvar("under_user", '('.$have_under_user.')');
        } else { // general user
            $html->setvar('group_admin_list', Common::listGroupAdmin($g_user['under_admin']));

            if(get_session('admin_auth'))
                $html->parse('group_admin_list', true);
        }

        if($g_user['under_admin'] == NULL) // free user => no parent
            $html->parse('not_group_user', true);

        $html->setvar("phone", $g_user['phone']);

		if (IS_DEMO)
            $html->setvar("mail", get_param("mail", 'disabled@ondemoadmin.cp'));
		else
            $html->setvar("mail", get_param("mail", $g_user['mail']));

        //$this->parseFieldsAll($html, 'admin');

        $whereNoPrivatePhoto = '';
        $noPrivatePhoto = CProfilePhoto::isHidePrivatePhoto();
        if ($noPrivatePhoto) {
            $whereNoPrivatePhoto = ' AND `private` = "N" ';
        }
		$num_photos = DB::result("SELECT COUNT(photo_id) FROM photo WHERE user_id=" . $g_user['user_id'] . "  AND `visible` != 'P' AND `group_id` = 0 " . $whereNoPrivatePhoto);

		if ($num_photos < 4) {
			$html->parse("photo_upload", true);
		}

		$html->setvar("num_photos", $num_photos);

		DB::query("SELECT *, IF(private='Y', 1, 0) AS access FROM photo WHERE user_id=" . $g_user['user_id'] . " AND `visible` != 'P' AND `group_id` = 0 " . $whereNoPrivatePhoto . " ORDER BY access, photo_id DESC;");

        $noPrivatePhoto = Common::isOptionActiveTemplate('no_private_photos');
		for ($i = 1; $i <= $num_photos; $i++)
		{
			$html->setvar("numer", $i);

			if ($row = DB::fetch_row())
			{
                $html->setvar('photo', User::getPhotoFile($row, 's', $g_user['gender']));
				$html->setvar("photo_id", $row['photo_id']);
                if (!$noPrivatePhoto) {
                    $html->setvar("photo_access", l($row['private']=='N'?'make_private':'make_public'));
                    $html->parse('photo_access', false);
                }


				$html->setvar("photo_name", $row['photo_name']);
				$html->setvar("description", nl2br($row['description']));

				$html->setvar("visible", $row['visible'] == "Y" ? "" : "(pending audit)");
                if ($row['visible'] == "Y") {
                    $html->clean('photo_approve');
                } else {
                    $html->parse('photo_approve', false);
                }

				if ($i == 1 or $i == 3) $html->parse("photo_odd", true);
				else $html->setblockvar("photo_odd", "");

				if ($i == 2) $html->parse("photo_even", true);
				else $html->setblockvar("photo_even", "");

                if($i % 4 == 0) {
                    $html->parse('photo_delimiter');
                } else {
                    $html->setblockvar('photo_delimiter', '');
                }

				$html->parse("photo_item", true);

				$html->parse("photo", false);
			}
		}

		$html->parse("photo_edit", true);
        if (!Common::isOptionActive('personal_settings')) {
            $html->parse('btn_update', false);
        }

        if (!$noPrivatePhoto) {
            $html->parse('photo_add_access', false);
        }

		parent::parseBlock($html);
	}
}

$page = new CForm('', $g['tmpl']['dir_tmpl_administration'] . 'users_edit.html', false, false, false, 'admin', get_param('id'));
$page->formatValue = 'entities';

$header = new CAdminHeader("header", $g['tmpl']['dir_tmpl_administration'] . "_header.html");
$page->add($header);
$footer = new CAdminFooter("footer", $g['tmpl']['dir_tmpl_administration'] . "_footer.html");
$page->add($footer);

if(get_session('admin_auth'))
    $page->add(new CAdminPageMenuUsers());
else
    $page->add(new CGroupAdminPageMenuUsers());
include("../_include/core/administration_close.php");