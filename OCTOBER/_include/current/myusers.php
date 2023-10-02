<?php

class MyUsers extends CHtmlList {
    var $m_on_page = 6;
    static $tmplName = '';
    static $tmplSet = '';
    static $parDisplay = '';
    static $parAjax = '';
    static $guid = 0;

    var $c_user_id = false;

    function init() {
        parent::init();
        global $g;
        global $g_user;
        global $p;

        self::$tmplName = Common::getOption('name', 'template_options');
        self::$tmplSet = Common::getOption('set', 'template_options');
        self::$parDisplay = get_param('display');
        self::$parAjax = get_param('ajax');
        self::$guid = guid();

        $user_id = self::$guid;

        $this->m_sql_count = "SELECT COUNT(u.user_id) FROM user AS u " . $this->m_sql_from_add . "";

        if($p == 'users_viewed_me.php') {
            $this->m_sql = "
    	        SELECT * FROM (
    				SELECT u.*, DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(birth, '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(birth, '00-%m-%d')) AS age,
                    v.user_to, (SELECT name FROM user WHERE user_id = v.user_to) AS view_to
                    ".$this->m_sql_select_add."
    				FROM user AS u			
    				JOIN users_view AS v ON (u.user_id=v.user_from)
                    WHERE (
                        v.user_to= {$user_id}
                        OR v.user_to IN (SELECT user_id FROM user WHERE under_admin = {$user_id})
                    )
    			) u
    	    ";

            $this->m_field['user_id'] = array("user_id", null);
            $this->m_field['photo_id'] = array("photo", null);
            $this->m_field['name'] = array("name", null);
            $this->m_field['age'] = array("age", null);
            if($g_user['role'] == 'group_admin')
                $this->m_field['view_to'] = array("view_to", null);
            $this->m_field_default = $this->m_field;
        } else {
            $this->c_user_id = $this->c_user_id;
            $this->m_sql = "
                SELECT * FROM (
                    SELECT u.*, DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(birth, '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(birth, '00-%m-%d')) AS age,
                    v.user_to, (SELECT name FROM user WHERE user_id = v.user_to) AS view_to
                    ".$this->m_sql_select_add."
                    FROM user AS u          
                    " . $this->m_sql_from_add . "
                ) u
            ";
            $this->m_field['user_id'] = array("user_id", null);
            $this->m_field['photo_id'] = array("photo", null);
            $this->m_field['name'] = array("name", null);
            $this->m_field['age'] = array("age", null);
            $this->m_field_default = $this->m_field;
        }

        // $this->m_debug = "Y";

        
    }

    function onItem(&$html, $row, $i, $last) {
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
    function parseBlock(&$html)
    {
        global $p;

        if($p !== 'users_viewed_me.php') {
            $c_user_id_info = User::getInfoFull($this->c_user_id);
            $html->setvar('visitor_of', $c_user_id_info['name']);
            $html->setvar('visitor_of_name_seo', $c_user_id_info['name_seo']);
            $html->parse('visitor_of', false);
        }
        parent::parseBlock($html);
    }

}
class MyUsersInfo extends MyUsers {

    var $m_on_page = 10;

}
?>