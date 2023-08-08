<?php
/* (C) Websplosion LLC, 2001-2021

IMPORTANT: This is a commercial software product
and any kind of using it must agree to the Websplosion's license agreement.
It can be found at http://www.chameleonsocial.com/license.doc

This notice may not be removed from the source code. */

include("../_include/core/administration_start.php");

class CAdminFields extends CHtmlBlock
{
	public $message;

    private $table;
    private $table_name;
    private $field_name;
    private $is_table;
    private $allowTypeImage = array('png', 'gif', 'jpg', 'jpeg');
	private static $lastInserId = 0;


	/*function del_from_users($w)
	{
		global $g;
		$w = strtolower($w);

        Config::remove('user_var', $w);
        Config::remove('user_var', 'p_' . $w);
        Config::remove('user_var', 'p_' . $w . '_to');
        Config::remove('user_var', 'p_' . $w . '_from');
	}*/

    function uploadIcon($fields, $isMobile = false)
	{
        global $g;

        if (!$this->isAllowedUploadIcon($fields, $isMobile)) {
            return;
        }
        $height = 16;
        $width = 16;
        if ($isMobile && ($fields == 'i_am_here_to' || $fields == 'orientation')) {
            $height = 51;
            $width = 70;
        }
        $saveUrl = $this->getUrlTmplMobile($isMobile);
        $typeUploadIco = UserFields::getTypeUploadIcoField($isMobile);

        foreach ($typeUploadIco[$fields] as $type) {
            $typeFiles = $type . ($isMobile ? '_mobile' : '');
            if (isset($_FILES[$typeFiles])) {
                    $icons = $_FILES[$typeFiles];
                    foreach ($icons['tmp_name'] as $id => $icon) {
                        if (empty($icon) && $id == -1 && DB::insert_id()) {
                            $icon = $g['path']['dir_tmpl'].'common/images/1px.png';
                        }
                        if (!empty($icon) && Image::isValid($icon)) {
                            if ($id == -1) {
                                $id = self::$lastInserId;//DB::insert_id();
                            }
                            $image = new uploadImage($icon);
                            if ($image->uploaded) {
                                $image->file_safe_name = false;
                                $image->image_resize = true;
                                $image->image_ratio = true;
                                $image->image_convert = 'png';
                                $image->png_compression = 0;
                                $image->image_y = $height;
                                $image->image_x = $width;
                                $image->file_new_name_body = UserFields::getArrayNameIcoField($fields, $id, $type, $isMobile);
                                $image->file_new_name_ext = 'png';
                                $image->Process($saveUrl);
                                if (!$image->processed) {
                                    #echo '  Error: ' . $image->error . '';
                                }
                                unset($image);
                            }
                        }
                    }
            }
        }
    }

    function uploadIconAll($fields)
	{
        $this->uploadIcon($fields);
        $this->uploadIcon($fields, true);
    }

    function deleteIcon($field, $id, $isMobile = false)
	{
        if ($this->isAllowedUploadIcon($field, $isMobile)) {
            $url = $this->getUrlTmplMobile($isMobile);
            $icoNames = UserFields::getArrayNameIcoField($field, $id, '', $isMobile);
            foreach ($icoNames as $name) {
                @unlink("{$url}{$name}.png");
            }
        }
    }

    function deleteIconAll($fields, $id)
	{
        $this->deleteIcon($fields, $id);
        $this->deleteIcon($fields, $id, true);
    }

	function action()
	{
		global $g;
        global $DB_res;
        global $p;

		$optionTmplName = Common::getOption('name', 'template_options');
		$cmd = get_param("cmd", "");
		$table = get_param("table", "");
        $fields = get_param('fields', '');

        if ($cmd == "saveposition") {
            UserFields::updateAllPosition($fields);
            die();
        } elseif ($cmd == "visible"){
            if ($option = 'relation') {
                $value = ($g['user_var']['relation']['status'] == 'active') ? 'N' : 'Y';
                Config::update('options', 'status_relation', $value);
            }
            UserFields::updateStatus($fields);
            die();
        } elseif ($cmd == "set_status_join"){
            if (!isset($g['user_var'][$fields]['join_status'])) {
                $g['user_var'][$fields]['join_status'] = 1;
            }
            $value = !$g['user_var'][$fields]['join_status'];
            UserFields::updateRelatedItem($fields, 'join_status',  $value, true);
            die();
        } elseif ($cmd == "update") {

            $fieldsType = UserFields::getField($fields, 'type');
            $fildsId = UserFields::getField($fields, 'id');

			if ($table != "const_orientation")
			{
				$defaultId = get_param("default");
                $isCustomRegister = Common::isOptionActive('custom_user_registration', 'template_options');
                if ($isCustomRegister) {
                    $chart = get_param('chart', null);
                    if ($chart !== null) {
                        UserFields::updateRelatedItem($fields, 'chart', $chart, true);
                        if ($fields == 'star_sign') {
                           redirect("users_fields.php?action=saved&table=" . $table . '&fields=' . $fields);
                        }
                    }
                    $fieldQuestion = UserFields::checkFiledQuestion($fields);
                    if ($fieldQuestion) {
                        $type = $fieldQuestion['type_field'];
                        $answerData = array('yes' => array(), 'no' => array());
                        if ($type == 'selection' || $type == 'checkbox') {
                            $paramsYes = get_param_array('answer_yes');
                            if ($paramsYes) {
                                $answerData['yes'] = array_keys($paramsYes);
                            } else {
                                $answerData['yes'] = array();
                            }
                            $paramsNo = get_param_array('answer_no');
                            if ($paramsNo) {
                                $answerData['no'] = array_keys($paramsNo);
                            } else {
                                $answerData['no'] = array();
                            }
                        } elseif ($type == 'checks') {
                            $yesFrom = get_param_array('answer_yes_from');
                            $yesTo = get_param_array('answer_yes_to');
                            $noFrom = get_param_array('answer_no_from');
                            $noTo = get_param_array('answer_no_to');
                            $orientations = $this->getOrientationsAnswer();
                            foreach ($orientations as $key => $orientation) {
                                $id = $orientation['id'];
                                $answerData['yes'][$id] = array('from' => isset($yesFrom[$id]) ? $yesFrom[$id] : '',
                                                                'to' => isset($yesTo[$id]) ? $yesTo[$id] : '');
                                $answerData['no'][$id] = array('from' => isset($noFrom[$id]) ? $noFrom[$id] : '',
                                                                'to' => isset($noTo[$id]) ? $noTo[$id] : '');
                            }
                        } elseif ($type == 'radio') {
                            $answerData['yes'] = get_param('answer_yes');
                            $answerData['no'] = get_param('answer_no');
                        }
                        UserFields::updateRelatedItem($fields, 'answer',  json_encode($answerData), true);
                        UserFields::updateRelatedItem($fields, 'question_title',  trim(get_param('question_title')), true);
                    }
                }

                $field = get_param_array('id');
				foreach ($field as $k => $v)
				{
					if ($k != -1 and $v != "")
					{
						if ($isCustomRegister && UserFields::isColumnInTable($table, 'default')) {
							$default = intval($k == $defaultId);
							DB::execute("UPDATE " . $table . " SET `default`=" . to_sql($default) . " WHERE id=" . to_sql($k));
						}
						DB::execute("UPDATE " . $table . " SET title=" . to_sql($v, "Text") . " WHERE id=" . to_sql($k, "Number") . "");
					}
					elseif ($k != -1 and $v == "")
					{
						if ($fieldsType == 'checkbox') {
							$where = '`field` = ' . to_sql($fildsId, 'Number') . ' AND `value` = ' . to_sql($k, 'Number');
							DB::delete('users_checkbox', $where);
						}
						DB::execute("DELETE FROM " . $table . " WHERE id=" . to_sql($k, "Number") . "");
                        $this->deleteIconAll($fields, $k);
					}
					elseif ($v != "")
					{
						DB::execute("INSERT INTO " . $table . " SET title=" . to_sql($v, "Text") . "");
						if ($k == -1) {
							self::$lastInserId = DB::insert_id();
						}
					}
				}
			} else {
				$field = get_param_array("id");
				$s = get_param_array("search");
                $paramSearch = get_param_array("search_o");
                //print_r_pre($paramSearch);
                //die();
				$gs = get_param_array("gender");
				$f = get_param_array("free");
                $defaultId = get_param("default");
                //var_dump($default);
				foreach ($field as $k => $v)
				{
					if ($k != -1 and $v != "")
					{
                        $default = intval($k == $defaultId);
                        $paramSearchValue = isset($paramSearch[$k]) ? get_checks_array($paramSearch[$k]) : 0;
						DB::execute("
							UPDATE " . $table . " SET
							`title` =" . to_sql($v, "Text") . ",
							`search` =" . to_sql($paramSearchValue, "Text") . ",
							`gender` =" . to_sql($gs[$k], "Text") . ",
							`free` =" . to_sql($f[$k], "Text") . ",
                            `default` = " . to_sql($default, "Number") . "
							WHERE id=" . to_sql($k, "Number") . "
						");
					} elseif ($k != -1 and $v == ""){
                        $this->deleteIconAll($fields, $k);
						DB::execute("DELETE FROM " . $table . " WHERE id=" . to_sql($k, "Number") . "");
					} elseif ($v != "") {
						DB::execute("INSERT INTO " . $table . " SET title=" . to_sql($v, "Text") . "");#, search=" . to_sql($s[$k], "Text") . "
						if ($k == -1) {
							self::$lastInserId = DB::insert_id();
						}
					}
				}

                if ($table == "const_orientation"){
                    if (Common::getOption('paid_access_mode') != 'free_site'
                        && Common::isActiveFeatureSuperPowers('invisible_mode')) {
                        User::resetOptionsInvisibleMode();
                    }
                }
			}
            $this->uploadIconAll($fields);
            UserFields::updateNumberValue($fields);
            Config::updateSiteVersion();
            redirect("users_fields.php?action=saved&table=" . $table . '&fields=' . $fields);

		} elseif ($cmd == 'delete') {
            $fieldsType = UserFields::getField($fields, 'type');
            $fildsId = UserFields::getField($fields, 'id');
			if (substr($this->table, 0, 6) != "const_") {
                if($table == '') {
                    $table = $fields;
                }

				$field = str_replace(' ', '_', strtolower(str_replace('var_', '', $table)));
                $field = to_sql($field, 'Plain');

				if ($fieldsType == 'checkbox') {
					$where = '`field` = ' . to_sql($fildsId, 'Number');
					DB::delete('users_checkbox', $where);
				}

                Config::remove('user_var', $field);
                Config::remove('user_var', 'p_' . $field);
                Config::remove('user_var', 'p_' . $field . '_to');
                Config::remove('user_var', 'p_' . $field . '_from');

                $columns = array(
                    'userinfo' => $field,
                    'texts' => $field,
                    'userpartner' => "p_{$field}",
                    'userpartner_from' => "p_{$field}_from",
                    'userpartner_to' => "p_{$field}_to",
                );
                foreach ($columns as $table => $column) {
					$table = str_replace(array('_from', '_to'), '', $table);
                    if (UserFields::isColumnInTable($table, $column)) {
                        DB::execute("ALTER TABLE {$table} DROP {$column}", false);
                    }
                }
                DB::execute("DROP TABLE IF EXISTS var_" . $field, false);
				/*DB::execute("ALTER TABLE userinfo DROP " . $field, false);
				DB::execute("ALTER TABLE texts DROP " . $field, false);
				DB::execute("DROP TABLE var_" . $field, false);
				DB::execute("ALTER TABLE userpartner DROP p_" . $field, false);
				DB::execute("ALTER TABLE userpartner DROP p_" . $field . "_from", false);
				DB::execute("ALTER TABLE userpartner DROP p_" . $field . "_to", false);*/
			}
            redirect();
		}
	}
    function parseAllBlock($html, $items)
    {
        $isCustomRegister = Common::isOptionActive('custom_user_registration', 'template_options');
        $isFieldsSocial = Common::isOptionActiveTemplate('fields_social');
       // echo '<pre>';
        //print_r($items);
        //echo '</pre>';

        foreach ($items as $k => $v)
        {
            if ($isFieldsSocial && in_array($v['group'], array(1, 2, 3))) {
                continue;
            }
            if (substr($k, 0, 2) != "p_")
            {
                $html->setvar("table_fields_position", $k);
                $html->setvar("table_this_url", $k);

                //if ($v[0] == 'from_table' || $v[0] == 'from_array')
                //{
                    // spread the field types
                    if (in_array($v['type'], array('int', 'const', 'radio', 'selectbox', 'checkbox', 'group', 'map', 'location', 'interests', 'private_note')))
                    {

                        if ($isCustomRegister && in_array($k, array('sexuality', 'location'))) {
                            if ($k != 'sexuality'
                                || ($k == 'sexuality' && UserFields::isFieldsSelectionChecks('sexuality') == 'selection')) {
                                $v['group'] = 4;
                                $v['type'] = 'const';
                                if ($k == 'location') {
                                    $v['table'] = 'location';
                                }
                            }
                        }
                        $html->setvar("type_" . $v['group'], l('field_'.$v['type']));
                        if ($k == $this->table_name) $this->field_name = $v['title'];

                        $html->setvar("table_this_" . $v['group'], ($v['table']) ? $v['table'] : $k);
                        $html->setvar("table_title_this_" . $v['group'], l($v['title']));
                        $html->setvar("table_status_" . $v['group'], $k);
                        $html->setvar("type_fields_" . $v['group'], $v['type']);

                        if ($v['table'] == $this->table || $this->table == $v['type'])
						{
                            //$this->is_table = true;
							$html->parse("bb_" . $v['group'], false);
                            $html->parse("be_" . $v['group'], false);
						} else {
							$html->setblockvar("bb_" . $v['group'], "");
                            $html->setblockvar("be_" . $v['group'], "");
						}

                        if ($isCustomRegister && UserFields::checkFiledQuestion($k, false)) {
                            if (!isset($v['join_status'])) {
                                $v['join_status'] = 1;
                            }
                            $html->setvar('status_join_' . $v['group'], $v['join_status'] ? l('join_hide') : l('join_show'));
                            if ($v['status'] != 'active') {
                                $html->parse('table_' . $v['group'] . '_join_hide', false);
                            } else {
                                $html->clean('table_' . $v['group'] . '_join_hide');
                            }
                            $html->parse('table_' . $v['group'] . '_join', false);
                        } else {
                            $html->clean('table_' . $v['group'] . '_join');
                        }
                        $html->setvar("status_" . $v['group'], ($v['status'] == 'active') ? l('hide') : l('show'));
                        if ($v['type'] == 'const' || ($v['table'] != '' && strpos($v['table'], 'const') !== false)) {
                            if ($v['table'] == '') {// 'from_array' - Age range
                                $html->setblockvar("ba_" . $v['group'], false);
                                $html->parse("bn_" . $v['group'], '');
                            } else {

                                $html->setblockvar("bn_" . $v['group'], false);
                                $html->parse("ba_" . $v['group'], '');
                            }
                        }
                        $html->parse("table_" . $v['group'], true);
                    //}
                } elseif ($v['type'] == "text" || $v['type'] == "textarea") {
                    if ($k == $this->table_name) $this->field_name = $v['title'];
                    if ($isFieldsSocial && $k != 'about_me'){
                        continue;
                    }
                    $html->setvar("table_this_" . $v['group'], $k);
                    //$html->setvar("table_this_id_" . $v['group'], $k);//'id_' .
                    $html->setvar("table_title_this_" . $v['group'], l($v['title']));
                    $html->setvar("type_" . $v['group'], l($v['type']));
                    $html->setvar("type_fields_" . $v['group'], $v['type']);
                    if ($k == $this->table_name)
					{
                        $html->parse("bb_" . $v['group'], false);
                        $html->parse("be_" . $v['group'], false);
					} else {
                        $html->setblockvar("bb_" . $v['group'], "");
                        $html->setblockvar("be_" . $v['group'], "");
					}
                    $html->setvar("status_" . $v['group'], ($v['status'] == 'active') ? l('hide') : l('show'));
                    $html->setvar("table_status_" . $v['group'], $k);

                    if ($isCustomRegister) {//!$v['group'] &&
                        if (!isset($v['join_status'])) {
                            $v['join_status'] = 1;
                        }
                        $html->setvar('status_join_' . $v['group'], $v['join_status'] ? l('join_hide') : l('join_show'));
                        if ($v['status'] != 'active') {
                            $html->parse('table_' . $v['group'] . '_join_hide', false);
                        } else {
                            $html->clean('table_' . $v['group'] . '_join_hide');
                        }
                        $html->parse('table_' . $v['group'] . '_join', false);
                    }
                    $html->parse("table_" . $v['group'], true);
                }
            }
        }
    }

    function isAllowedUploadIcon($field, $isMobile = false)
	{
        global $g;

        if (!in_array($field, array('i_am_here_to', 'interests', 'orientation'))) {
            return false;
        }
        $option = 'upload_icon_field_' . $field;
        if (!$isMobile && !Common::isOptionActive($option, 'template_options')) {
            return false;
        }
        if ($isMobile && !isOptionActiveLoadTemplateSettings($option, null, 'mobile', $g['tmpl']['mobile'])) {
            return false;
        }

        return true;
    }

    function parseIcon(&$html, $id, $block = 'field_upload_ico', $isMobile = false)
	{
        global $g;

        if (!$this->isAllowedUploadIcon($this->table_name, $isMobile)) {
            return;
        }
        $typeUploadIco = UserFields::getTypeUploadIcoField($isMobile);

        foreach ($typeUploadIco[$this->table_name] as $type) {
            $icoName = UserFields::getArrayNameIcoField($this->table_name, $id, $type, $isMobile) . '.png';

            $url = $this->getUrlTmplMobile($isMobile) . $icoName ;
            if (file_exists($url)) {
                $html->setvar('rand', rand(0, 100000));
                $html->setvar('url_ico', $url);
            } else {
                $html->setvar('url_ico', $g['tmpl']['url_tmpl_administration'] .  'images/empty.png');
            }
            if ($type == 'search' || $type == 'category_selected') {
                $html->parse($block . '_dark', false);
            } else {
                $html->clean($block . '_dark');
            }
            $html->setvar($block . '_type', $type . ($isMobile ? '_mobile' : ''));
            $html->setvar($block . '_title', l($type . '_ico_' . ($isMobile ? 'mobile_' : '') . $this->table_name));
            $html->parse($block, true);
        }
    }

    function parseIconAll(&$html, $id, $block = 'field_upload_ico')
    {
        $html->clean($block);
        $this->parseIcon($html, $id, $block);
        $this->parseIcon($html, $id, $block, true);
    }

    function getUrlTmplMobile($isMobile)
    {
        global $g;

        $urlMobile = $g['path']['url_tmpl'] . 'mobile/' . $g['tmpl']['mobile'] . '/';
        return ($isMobile ? $urlMobile : $g['tmpl']['url_tmpl_main']) . 'images/';
    }

    function getOrientationsAnswer(){
        $orientations = array();
        if (UserFields::isActive('orientation')) {
            $orientations = DB::select('const_orientation');
            /*$orientations[] = array('id' => 0,
                                    'title' => l('for_all'));*/
        }
        if (!$orientations) {
            $orientations = array(array('id' => 'no', 'title' => ''));
        }
        return $orientations;
    }

	function parseBlock(&$html)
	{
		global $g;
		global $p;

        $isFieldsSocial = Common::isOptionActiveTemplate('fields_social');
        if ($isFieldsSocial) {
            $html->parse('fields_social_style', false);
        }

        if (get_param('action') == 'saved' && get_param('table')) {
            $html->parse('scroll_save');
            $fld = get_param('fields');
            if ($fld != '') {
                $html->setvar('id_save', $fld);
                $html->parse('action_save');
            }
        }

        $optionTmplName = Common::getOption('name', 'template_options');
        $mod = Common::getOption('fields_mode', 'template_options');
        $mod = (empty($mod)) ? '' : "_{$mod}";
        $blockJs = "sort_table_js{$mod}";
        if ($html->blockexists($blockJs)) {
            $html->parse($blockJs, true);
        } else {
            $html->parse('sort_table_js', true);
        }

        for ($i = 0; $i < 4; $i++) {
            $section = 'fields_section_' . $i;
            $html->setvar($section, l($section . $mod));
        }

        UserFields::removeUnavailableField();

        $default = UserFields::getField('orientation', 'table');
		$this->table = get_param('table', empty($default) ? 'const_looking' : 'const_orientation');//no? - ???
        /*if ($this->table == '') {
            $this->table = 'const_orientation';
        }*/

        $this->table_name = str_replace(array('const_', 'var_'), '', $this->table);

        /* Impact */
        $isCustomRegister = Common::isOptionActive('custom_user_registration', 'template_options');
        $answerData = array();
        $answerType = '';
        $chartNum = null;
        if ($isCustomRegister) {
            $answerData = UserFields::checkFiledQuestion($this->table_name);
            if ($answerData) {
                $answerType = $answerData['type_field'];
            }
            if (UserFields::checkFiledQuestion($this->table_name, false, true)) {
                $chartNum = isset($g['user_var'][$this->table_name]['chart']) ? $g['user_var'][$this->table_name]['chart'] : 0;
            }
        }
        $html->setvar('field_type', $answerType);
        /* Impact */

        $this->table = UserFields::getField($this->table_name, 'table');

        $type = UserFields::getField($this->table_name, 'type');

        if (in_array($type, array('text', 'textarea'))) {
            $this->table = $this->table_name;
        }

        if (empty($this->table)) {
            $this->table = $type;
        };

        $html->setvar('table', $this->table);
        $html->setvar('table_title_id', $this->table);
        $html->setvar('table_fields', get_param('fields', 'orientation'));


        if (isset($g['user_var']) and is_array($g['user_var'])) {
            $this->parseAllBlock($html, $g['user_var']);
            //if ($optionTmplName != 'impact') {
                $html->parse('table_3_fields', false);
            //}
        }

        if (!empty($this->table)) {

            $html->setvar('table_lang', (in_array($this->table, array('text', 'textarea', 'group', 'map', 'location', 'private_note'))) ? $this->table_name : $this->table);
            $lang = get_param('lang');
            $html->setvar('select_options_language', adminLangsSelect('main', $lang));
            $html->parse('language');

            $html->setvar('table_title', l($this->field_name));

            if (!in_array($type, array('text', 'textarea', 'map', 'location', 'group', 'private_note')) && DB::query("SELECT * FROM " . $this->table . " ORDER BY id"))
            {
                if (!in_array($this->table, array('var_star_sign'))) {
                    $this->parseIconAll($html, '', 'block_add_upload_ico');
                    $html->parse('block_add', true);
                    $html->setvar('field_input_disabled', '');
                } else {
                    $html->parse('block_btn_add', false);
                    $html->setvar('field_input_disabled', 'disabled');
                }
                if ($this->table != 'const_orientation') {
                    $isParseAnswer = false;
                    while ($row = DB::fetch_row()) {
                        $html->setvar('id', $row['id']);
                        $html->setvar('value', he($row['title']));
                        $this->parseIconAll($html, $row['id']);

						if ($isCustomRegister && UserFields::isColumnInTable($this->table, 'default')) {
							$default = DB::result('SELECT `id` FROM ' . to_sql($this->table, 'Plain') . ' WHERE`default` = 1', 0, 1);
							if ($default == $row['id']) {
								$html->setvar('default_checked', 'checked');
							} else {
								$html->setvar('default_checked', '');
							}
							$html->parse('default_value', false);
						}

                        if ($answerType == 'selection' || $answerType == 'checkbox') {
                            $answerData = UserFields::getAnswerData($this->table_name);
                            $html->setvar('answer_yes_checked', in_array($row['id'], $answerData['yes']) ? 'checked' : '');
                            $html->setvar('answer_no_checked', in_array($row['id'], $answerData['no']) ? 'checked' : '');
                            $html->parse('field_answer_selection', false);
                        } elseif ($answerType == 'radio') {
                            $answerData = UserFields::getAnswerData($this->table_name);
                            if ($row['id'] == $answerData['yes']) {
                                $html->setvar('answer_yes_checked', 'checked');
                                $html->setvar('answer_no_checked', '');
                            } elseif($row['id'] == $answerData['no']){
                                $html->setvar('answer_yes_checked', '');
                                $html->setvar('answer_no_checked', 'checked');
                            } else {
                                $html->setvar('answer_yes_checked', '');
                                $html->setvar('answer_no_checked', '');
                            }
                            $html->parse('field_answer_radio', false);
                        }
                        $html->parse('field', true);
                        $isParseAnswer = true;
                        //$html->clean('field_upload_ico');
                    }
                    if ($isParseAnswer && $answerType == 'checks') {
                        $orientations = $this->getOrientationsAnswer();
                        foreach ($orientations as $key => $orientation) {
                            $id = $orientation['id'];
                            $html->setvar('answer_orientation_id', $id);
                            $title = $orientation['title'];
                            if ($title) {
                                $title = l($orientation['title']);
                            }
                            $html->setvar('answer_orientation', $title);
                            $answerData = UserFields::getAnswerData($this->table_name);
                            //print_r_pre($answerData);
                            $paramsValue = array('yes', 'no');
                            foreach ($paramsValue as $value) {
                                $optionsFromValue = isset($answerData[$value][$id]) ? $answerData[$value][$id]['from'] : '';
                                $optionsFrom = DB::db_options("SELECT id, title FROM " . $this->table . " ORDER BY id ASC", $optionsFromValue, 5);
                                $html->setvar('answer_' . $value . '_options_from', $optionsFrom);
                                $optionsToValue = isset($answerData[$value][$id]) ? $answerData[$value][$id]['to'] : '';
                                $optionsTo = DB::db_options("SELECT id, title FROM " . $this->table . " ORDER BY id ASC", $optionsToValue, 5);
                                $html->setvar('answer_' . $value . '_options_to', $optionsTo);
                            }
                            $html->parse('field_answer_checks_item', true);
                        }
                        $html->parse('field_answer_checks', false);
                    }
                    if ($chartNum !== null) {
                        $graphics = array(l('please_choose'), l('chart_physics'), l('chart_intellect'), l('chart_hobbies'));
                        $html->setvar('select_options_chart', h_options($graphics, $chartNum));
                        $html->parse('chart', false);
                    }
                    $html->parse("fields", true);
                } else {
                    if  (Common::getOption('set', 'template_options') != 'urban'){
                    $types = array('none' => l('None'),
                                   'silver' => l('Silver'),
                                   'gold' => l('Gold'),
                                   'platinum' => l('Platinum'),);
                    } else {
                    $types = array('none' => l('None'),
                                   'platinum' => l('Super Powers'),
                                  );
                    }
                    $default = DB::result('SELECT `id` FROM `const_orientation` WHERE`default` = 1', 0, 1);
                    $setDefault = false;

                    $ors = DB::all('SELECT * FROM const_orientation', DB_MAX_INDEX - 1);
                    while ($row = DB::fetch_row()) {
                        $html->setvar("id", $row['id']);

                        $html->clean('field_o_search_item');
                        $orSearch = UserFields::checksToParamsArray('const_orientation', $row['search']);
                        if ($orSearch) {
                            $orSearch = array_flip($orSearch);
                        }
                        foreach ($ors as $or) {
                            $html->setvar('search_id', $or['id']);
                            $html->setvar('search_checked', isset($orSearch[$or['id']])?'checked':'');
                            $html->setvar('search_title', $or['title']);
                            $html->parse('field_o_search_item', true);
                        }
                        //$html->setvar('search_options', DB::db_options("SELECT `id`, `title` FROM `const_orientation` ORDER BY `id` ASC", $row['search'], 2));

                        $html->setvar("value", he($row['title']));
                        $this->parseIconAll($html, $row['id'], 'field_upload_ico_o');
                        //$html->setvar("search", $row['search']);
                        if ((!$default && $setDefault) || $default == $row['id']) {
                            $html->setvar('default_checked', 'checked');
                            $setDefault = false;
                        } else {
                            $html->setvar('default_checked', '');
                        }
                        if ($row['gender'] == 'M')
                            $html->setvar('gender_m', 'checked');
                        else
                            $html->setvar('gender_m', '');
                        if ($row['gender'] == 'F')
                            $html->setvar('gender_f', 'checked');
                        else
                            $html->setvar('gender_f', '');
                        if  ((Common::getOption('set', 'template_options') == 'urban')&&( $row['free'] != 'none')){
                            $row['free'] = 'platinum';
                        }
                        $html->setvar('select_free_access', h_options($types, $row['free']));
                        $html->parse('field_o', true);

                    }
                    $html->parse('fields', true);
                }
            } else {
                $html->parse('no_variables', true);
            }

            $varTabs = array('var_star_sign');
            if ($isCustomRegister) {
                if (UserFields::isActiveSexuality()){
                    $varTabs[] = 'var_sexuality';
                }
            }
            if ($optionTmplName == 'edge' && $this->table == 'about_me') {
                $varTabs[] = 'about_me';
            }
            if (substr($this->table, 0, 6) != 'const_'
                && !in_array($type, array('group', 'map', 'location', 'private_note'))
                && !in_array($this->table, $varTabs)) {
                $html->parse('button_delete', true);
            }
            if ($answerType) {
                $blockQuestionTitle = 'question_title';
                $questionTitle = UserFields::getField($this->table_name, 'question_title');
                if (!$questionTitle) {
                    $questionTitle = '';
                    $html->parse("{$blockQuestionTitle}_translit_disabled", false);
                }
                $html->setvar("{$blockQuestionTitle}_value", he($questionTitle));
                $html->parse($blockQuestionTitle, false);
            }
        } else {
            $this->message .= l('field_not_exists');
        }

		$html->setvar('message_fields', $this->message);
		parent::parseBlock($html);
	}
}
$ajax = get_param("ajax", "");
if ($ajax){
    die();
}
$page = new CAdminFields("", $g['tmpl']['dir_tmpl_administration'] . "users_fields.html");
$header = new CAdminHeader("header", $g['tmpl']['dir_tmpl_administration'] . "_header.html");
$page->add($header);
$footer = new CAdminFooter("footer", $g['tmpl']['dir_tmpl_administration'] . "_footer.html");
$page->add($footer);

$page->add(new CAdminPageMenuUsersFields());

include("../_include/core/administration_close.php");

?>