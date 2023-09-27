<?php

class MyUsers extends CHtmlList {



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

    function init() {
        parent::init();
        global $g;
        global $g_user;
        global $p;

        // $this->m_debug = "Y";

        self::$tmplName = Common::getOption('name', 'template_options');
        self::$tmplSet = Common::getOption('set', 'template_options');
        self::$parDisplay = get_param('display');
        self::$parAjax = get_param('ajax');
        self::$guid = guid();

        $user_id = self::$guid;

        $this->m_sql_count = "SELECT COUNT(u.user_id) FROM user AS u " . $this->m_sql_from_add . "";
        $this->m_sql = "
	        SELECT *  FROM (
				SELECT u.*, DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(birth, '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(birth, '00-%m-%d')) AS age, v.user_to, (SELECT name FROM user WHERE user_id = v.user_to) AS view_to
				FROM user AS u			
				JOIN users_view AS v ON (u.user_id=v.user_from AND v.user_to= {$user_id})

				UNION ALL

				SELECT u.*, DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(birth, '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(birth, '00-%m-%d')) AS age, v.user_to, (SELECT name FROM user WHERE user_id = v.user_to) AS view_to
				FROM user AS u			
				JOIN users_view AS v ON (u.user_id=v.user_from AND v.user_to IN (SELECT user_id FROM user WHERE under_admin = {$user_id}))
			) u
	    ";

        $this->m_field['user_id'] = array("user_id", null);
        $this->m_field['photo_id'] = array("photo", null);
        $this->m_field['name'] = array("name", null);
        $this->m_field['age'] = array("age", null);
        if($g_user['role'] == 'group_admin')
        	$this->m_field['view_to'] = array("view_to", null);
        $this->m_field_default = $this->m_field;
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


        parent::onItem($html, $row, $i, $last);
    }

}
class MyUsersInfo extends MyUsers {

    var $m_on_page = 10;

}
?>