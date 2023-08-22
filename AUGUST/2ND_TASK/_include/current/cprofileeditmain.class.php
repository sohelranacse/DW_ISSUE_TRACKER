<?php
class CProfileEditMain extends UserFields
{
    static $nameCustomField = '';
    public $response = array();

    static function setError($error, $type = ''){
        if (Common::isOptionActive('profile_edit_main_location', 'template_options') && $error) {
            return '<span id="' . $type . '">' . $error . '</span>';
        } else {
            return $error;
        }
    }

    static function UpdateBasicInfo($type = '')
    {
        global $g_user;

        $e_user_id = get_param('e_user_id', 0);
        if($e_user_id) // added by sohel
            $g_user = DB::row("SELECT * FROM user WHERE user_id = ".to_sql($e_user_id, 'Number'));

        $message = '';
        $responseData = false;
        $optionTmplName = Common::getOption('name', 'template_options');
        if ($g_user) {
            $isActiveLoacation = false;
            if ($type == '') {
                $name = trim(get_param('nickname'));
                if (Common::isOptionActive('allow_users_to_change_their_logins')) {
                    $message .= self::setError(User::validateName($name), 'nickname');
                } else {
                    $name = $g_user['name'];
                }

                $isActiveLoacation = false;
                if (Common::isOptionActive('profile_edit_main_location', 'template_options')) {
                    if (Common::isOptionActiveTemplate('join_location_allow_disabled')) {
                        $isActiveLoacation = Common::isOptionActive('location_enabled', "{$optionTmplName}_join_page_settings");
                    } else {
                        $isActiveLoacation = self::isActive('location');
                    }
                }

                if ($isActiveLoacation) {
                    $countryId = get_param('country');
                    $stateId = get_param('state');
                    $cityId = get_param('city');
                    $message .= self::setError(User::validateLocation($countryId, $stateId, $cityId, true), 'location');
                }
            }

            if (User::isDisabledBirthday()) {
                $userBirth = explode('-', $g_user['birth']);
                $month = intval($userBirth[1]);
                $day   = intval($userBirth[2]);
                $year  = intval($userBirth[0]);

                $birth = $g_user['birth'];
                $sqlBirthday = '';
            } else {
                $month = (int)get_param('month', 1);
                $day   = (int)get_param('day', 1);
                $year  = (int)get_param('year', 1980);

                $message .= self::setError(User::validateBirthday($month, $day, $year), 'birthday');

                $birth = $year . '-' . $month . '-' . $day;
                $sqlBirthday = ', `birth` = ' . to_sql($birth);
            }

            $zodiac = zodiac($birth);
            if ($message == '') {
                $sqlName = '';
                if ($type == '') {
                    $countryTitle = '';
                    $cityTitle = '';
                    $sqlName = ', `name` = ' . to_sql($name);
                    if ($isActiveLoacation) {
                        $city = Common::getLocationTitle('city', $cityId);
                        $state = Common::getLocationTitle('state', $stateId);
                        $country = Common::getLocationTitle('country', $countryId);
                        $countryTitle = $country;
                        $cityTitle = $city;
                        $sqlName .= ", country_id=" . to_sql($countryId) . ",
                                       state_id=" . to_sql($stateId) . ",
                                       city_id=" . to_sql($cityId) . ",
                                       country=" . to_sql($country) . ",
                                       state=" . to_sql($state) . ",
                                       city=" . to_sql($city);
                    }
                }
                $sql = "UPDATE `user` SET
                        `horoscope` = " . to_sql($zodiac, 'Number') .
                        $sqlName .
                        $sqlBirthday .
						" WHERE `user_id` = " . to_sql($g_user['user_id'], 'Number');
                DB::execute($sql);
                if ($isActiveLoacation) {
                    User::updateFilterAll();
                    User::updateGeoPosition($cityId);
                }
                $orientationTitle = '';
                $gender = $g_user['gender'];
                $data = User::setOrientation($g_user);
                if ($data) {
                    $orientationTitle = l($data['title']);
                    $gender = $data['gender'];
                }

                $titleStarSign = '';
                $starSign = DB::field('var_star_sign', 'title', '`id` = ' . to_sql($zodiac));
                if (isset($starSign[0])) {
                    $titleStarSign = $starSign[0];
                }
                $responseData = true;

                if ($type == '') {
                    $nameSeo = '';
                    $userInfo = array('name' => $name,
                                      'name_seo' => '',
                                      'age' => User::getAge($year, $month, $day),
                                      'city' => $cityTitle ? $cityTitle : $g_user['city']);
                    if (Common::isOptionActive('seo_friendly_urls')) {
                        $nameSeo = User::url($g_user['user_id'], $userInfo, null, false);
                    }

                    $birthdayTitle = '';
                    $userName = '';
                    if ($optionTmplName == 'edge') {
                        $optionDate = 'profile_birth_edge';
                        if (User::isShowAge($g_user)) {
                            $optionDate = 'profile_birth_full_edge';
                        }
                        $birthday = new DateTime("$year-$month-$day");
                        $birth = $birthday->format('Y-m-d');
                        $isChangeAge = false;
                        if ($birth != $g_user['birth']) {
                            $birthdayTitle = Common::dateFormat($birth, $optionDate, false);
                            $isChangeAge = true;
                        }
                        if (Common::isOptionActive('location_enabled', 'edge_join_page_settings')) {
                            if ($cityId == $g_user['city_id']) {
                                $cityTitle = '';
                            }
                        }
                        if ($isChangeAge || $name != $g_user['name']) {
                            $userInfo['set_notif_show_my_age'] = $g_user['set_notif_show_my_age'];
                            $userName = TemplateEdge::getUserName($userInfo);
                        }
                    }
                    $responseData = array('title_name' => $name,
                                          'name_seo' => $nameSeo,
                                          'user_name' => $userName,
                                          'orientation' => $orientationTitle,
                                          'gender' => $gender,
                                          'age' => $userInfo['age'],
                                          'birthday' => $birthdayTitle,
                                          'star_sign' => l($titleStarSign),
                                          'country' => $countryTitle,
                                          'city' => $cityTitle,
                                          'seo' => Common::getSeoSite('profile', $g_user['user_id'], $userInfo));
                }
            } elseif ($type == '') {
                $responseData = '<error>'.$message.'</error>';
            }
        }

        return $responseData;
    }

    public function action()
    {
        $cmd = get_param('cmd');
        $ajax = get_param('ajax');
        $guid = guid();

        $e_user_id = get_param('e_user_id', 0);
        if($e_user_id) // added by sohel
            $guid = $e_user_id;
        
        $tmplName = Common::getTmplName();

        $responseData = false;
        $isUpdateAbout = $cmd == 'update_about_field';
        $isEdge = $tmplName == 'edge';
        if ($isEdge) {
            $isUpdateAbout = $isUpdateAbout || $cmd == 'profile_edit_main_save';
        }
        if ($isUpdateAbout && $guid) {
            $name = get_param('name');
            $this->updateTextsApproval();
            $value = get_param($name);

            if (empty($value) && !$isEdge) {
                $lVal = 'field_description_' . $name;
                $desc = l($lVal);
                if ($desc != $lVal){
                    $value = $desc;
                }
            }
            if (get_param('no_format')) {
                $responseData = heEmoji($value, true);
            } else {
                $responseData = nl2br($value);
            }
            if ($isEdge) {
                $responseData = $this->response;
                $responseData[$name] = heEmoji($value, true);
            }
        } elseif ($cmd == 'update_personal_field' && $guid) {
			$this->message = '';
            $this->updateTextsApproval('update_text');
            $this->updateInfo($guid, 'update_personal_urban');
            $ajax = 0;
			g_user_full();
        }

        if ($ajax) {
            die(getResponseDataAjaxByAuth($responseData));
        }
    }

    function parseBlock(&$html)
	{
        global $g_user;
        $cmd = get_param('cmd');

        $e_user_id = get_param('e_user_id', 0);
        if($e_user_id) // added by sohel
            $g_user = DB::row("SELECT * FROM user WHERE user_id = ".to_sql($e_user_id, 'Number'));

        $optionTmplName = Common::getOption('name', 'template_options');
        if ($this->typeParse == 'birthday') {
            $html->setvar('e_user_id', $g_user['user_id']);
            $html->setvar('nickname', $g_user['name']);
            $this->parseOrientationForAction($html);
            /*if (self::isActiveOrientation()) {
                $html->setvar('orientation_options', DB::db_options("SELECT id, title FROM const_orientation", $g_user['orientation']));
                $html->parse('field_orientation_edit_on', false);
            } else {
                $orientation = DB::result("SELECT title FROM const_orientation WHERE id = " . to_sql($g_user['orientation']));
                $html->setvar('field_orientation_value', l($orientation));
                $html->parse('field_orientation_edit_off', false);
            }*/
            if (Common::isOptionActive('allow_users_to_change_their_logins')) {
                $maxLength = Common::getOption('username_length');
                $minLength = Common::getOption('username_length_min');
                $html->setvar('nickname_title', sprintf(l("max_min_length_username"), $minLength, $maxLength));
                $html->setvar('minLength', $minLength);
                $html->setvar('maxLength', $maxLength);
                $html->parse('name_edit_on');
            } else {
                $html->parse('name_edit_off');
            }

            $blockLocation = 'location';
            if ($html->blockExists($blockLocation)) {
                if (Common::isOptionActiveTemplate('join_location_allow_disabled')) {
                    $isActiveLoacation = Common::isOptionActive('location_enabled', "{$optionTmplName}_join_page_settings");
                } else {
                    $isActiveLoacation = self::isActive('location');
                }
                if ($isActiveLoacation) {
                    $this->parseLocation($html);
                    $html->parse($blockLocation, false);
                }
            }
        } elseif ($this->typeParse == 'profile_about_urban') {
            $name = get_param('name', self::$nameCustomField);
            $this->setCustomFields(array($name));
            $this->formatValue = 'entities';
        } elseif ($this->typeParse == 'interests_category_urban') {
            $interest = get_param('value');
            $html->setvar('interest', $interest);
        } elseif ($this->typeParse == 'edit_looking_for_urban') {
            $this->parseLookingFor($html, false);
        }
        if ($html->varExists('users_age')) {
            $html->setvar('users_age', Common::getOption('users_age'));
        }

		parent::parseBlock($html);
	}
}
