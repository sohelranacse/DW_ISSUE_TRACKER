<?php

include("../_include/core/administration_start.php");

class CUsersNIDverify extends CHtmlList
{
	function action()
	{
		global $g, $p, $g_user;
		$reject = get_param('reject', 0);
        $verify = get_param('verify', 0);
        $redirect = '';
		if ($reject != 0)
		{
            $user =  explode(',', $reject);dd($user);
            foreach ($user as $userId) {
                $data = array('nid_verify_status' => 4, 'nid_verify_approved_on' => date("Y-m-d H:i:s"));
                DB::update('user', $data, '`user_id` = ' . to_sql($userId, 'Number'));
            }
			redirect("{$p}{$redirect}");
		} elseif ($verify) {
            $user =  explode(',', $verify);
            if (is_array($user)) {
                foreach ($user as $userId) {
                    $data = array('nid_verify_status' => 1, 'nid_verify_approved_on' => date("Y-m-d H:i:s"));
                    DB::update('user', $data, '`user_id` = ' . to_sql($userId, 'Number'));
                }
            }
			redirect("{$p}{$redirect}");
        }
	}
	function init()
	{
		global $g;

        $this->m_on_page = 20;
		$this->m_on_bar = 10;

		$this->m_sql_count = "SELECT COUNT(u.user_id) FROM user AS u " . $this->m_sql_from_add;
		$this->m_sql = "
			SELECT u.user_id, u.mail, u.type, u.orientation, u.password, u.gold_days, u.name, (DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(birth, '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(birth, '00-%m-%d'))
            ) AS age, u.last_visit,
			u.is_photo,
			u.city_id, u.state_id, u.country_id, u.last_ip, u.register, IF(u.nid_verify_status=2, 'New Upload', 'Re Upload') AS upload_status, u.nid_data
			FROM user AS u
			" . $this->m_sql_from_add;

		$this->m_field['user_id'] = array("user_id", null);
		$this->m_field['name'] = array("name", null);
		$this->m_field['age'] = array("age", null);
		$this->m_field['last_visit'] = array("last_visit", null);
		$this->m_field['city_title'] = array("city", null);
		$this->m_field['state_title'] = array("state", null);
		$this->m_field['country_title'] = array("country", null);
		$this->m_field['mail'] = array("mail", null);
		$this->m_field['type'] = array("type", null);
		$this->m_field['gold_days'] = array("gold_days", null);
		$this->m_field['password'] = array("password", null);
		$this->m_field['orientation'] = array("orientation", null);
		$this->m_field['last_ip'] = array("last_ip", null);
		$this->m_field['register'] = array("register", null);

		$this->m_field['upload_status'] = array("upload_status", null);
		$this->m_field['nid_data'] = array("nid_data", null);

        $where = " AND `nid_verify_status` IN (2,3)";


		#$this->m_debug = "Y";
		$user["p_orientation"] = (int) get_checks_param("p_orientation");
		if ($user["p_orientation"] != "0")
		{
			$where .= " AND " . $user["p_orientation"] . " & (1 << (cast(orientation AS signed) - 1))";
		}

		$user["p_relation"] = (int) get_checks_param("p_relation");
		if ($user["p_relation"] != "0")
		{
			$where .= " AND " . $user["p_relation"] . " & (1 << (cast(relation AS signed) - 1))";
		}

		$user['name'] = get_param("name", "");
		if ($user['name'] != "")
		{
			$where .= " AND name LIKE '%" . to_sql($user['name'], "Plain") . "%'";
		}

		$user['mail'] = get_param("mail", "");
		if ($user['mail'] != "")
		{
			$where .= " AND mail LIKE '%" . to_sql($user['mail'], "Plain") . "%'";
		}

		if (get_param("gold", "") == "1")
		{
			$where .= " AND gold_days>0";
		}
		if (get_param("gold", "") == "0")
		{
			$where .= " AND gold_days=0";
		}

		$r_from = get_param("r_from", "0000-00-00");
		$r_to = get_param("r_to", "0000-00-00");
		if ($r_from != "0000-00-00" or $r_to != "0000-00-00")
		{
			$where .= " AND register>" . to_sql($r_from) . " AND register<" . to_sql($r_to) . "";
		}

		$user['p_age_from'] = (int) get_param("p_age_from", 0);
		$user['p_age_to'] = (int) get_param("p_age_to", 0);
        if ($user['p_age_to'] == $g['options']['users_age_max']) $user['p_age_to'] = 10000;

		if ($user['p_age_from'] != 0)
		{
			$where .= " AND (DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(birth, '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(birth, '00-%m-%d')) >= " . $user['p_age_from'] . ") ";
		}

		if ($user['p_age_to'] != 0)
		{
			$where .= " AND (DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(birth, '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(birth, '00-%m-%d')) <= " . $user['p_age_to'] . ") ";
		}



		$user['country'] = (int) get_param("country", 0);
		if ($user['country'] != 0 and $user['country'] != "")
		{
			$where .= " AND u.country_id=" . $user['country'] . "";
		}
		$user['state'] = (int) get_param("state", 0);
		if ($user['state'] != 0 and $user['state'] != "")
		{
			$where .= " AND u.state_id=" . $user['state'] . "";
		}
		$user['city'] = (int) get_param("city", 0);
		if ($user['city'] != 0 and $user['city'] != "")
		{
			$where .= " AND u.city_id=" . $user['city'] . "";
		}

		if (get_param("photo", "") == "1")
		{
			$where .= " AND u.is_photo='Y'";
		}

		if (get_param("status", "") == "online")
		{
			$where .= " AND last_visit>" . (time() - $g['options']['online_time'] * 60) . "";
		}
		elseif (get_param("status", "") == "new")
		{
			$where .= " AND register>" . (time() - $g['options']['new_days'] * 3600 * 24) . "";
		}
		elseif (get_param("status", "") == "birthday")
		{
			$where .= " AND (DAYOFMONTH(birth)=DAYOFMONTH('" . date('Y-m-d H:i:s') . "') AND MONTH(birth)=MONTH('" . date('Y-m-d H:i:s') . "'))";
		}

		$keyword = get_param("keyword", "");
		if ($keyword != "")
		{
			$keyword = to_sql($keyword, "Plain");
			$where .= " AND (name LIKE '%" . $keyword . "%') ";
		}

		$this->m_sql_where = "1" . $where;
		$this->m_sql_order = "user_id";
		$this->m_sql_from_add = "";
	}
	function parseBlock(&$html)
	{
		parent::parseBlock($html);
	}
    function onPostParse(&$html)
	{
        if ($this->m_total != 0) {
            $html->parse('no_delete');
            $html->parse('approval');
        }
	}
	function onItem(&$html, $row, $i, $last)
	{
		global $g;

        $html->setvar('url_profile', User::url($row['user_id']));

		$this->m_field['city_title'][1] = DB::result("SELECT city_title FROM geo_city WHERE city_id=" . $row['city_id'] . "", 0, 2);
		if ($this->m_field['city_title'][1] == "") $this->m_field['city_title'][1] = "blank";
		$this->m_field['state_title'][1] = DB::result("SELECT state_title FROM geo_state WHERE state_id=" . $row['state_id'] . "", 0, 2);
		if ($this->m_field['state_title'][1] == "") $this->m_field['state_title'][1] = "blank";
		$this->m_field['country_title'][1] = DB::result("SELECT country_title FROM geo_country WHERE country_id=" . $row['country_id'] . "", 0, 2);
		if ($this->m_field['country_title'][1] == "") $this->m_field['country_title'][1] = "blank";

		$this->m_field['orientation'][1] = DB::result("SELECT title FROM const_orientation WHERE id=" . $row['orientation'] . "", 0, 2);
		if ($this->m_field['orientation'][1] == ""){
			$this->m_field['orientation'][1] = l("Invalid orientation");
		} else {
			$this->m_field['orientation'][1] = l($this->m_field['orientation'][1]);
		}

        $this->m_field['password'][1] = hard_trim($row['password'], 7);
		if (IS_DEMO) {
			$this->m_field['mail'][1] = 'disabled@ondemoadmin.cp';
			$this->m_field['password'][1] = 'not shown in the demo';
		}
		if (Common::getOption('set', 'template_options') != 'urban') {
            if ($row['type'] == 'membership') {
                $this->m_field['type'][1] = l('platinum');
            } else {
                $this->m_field['type'][1] = l($row['type']);
            }
        } else {
            if ($row['type'] != 'none'){
                if ($row['gold_days'] > 0){
                    $this->m_field['type'][1] = l('Super Powers!');
                } else {
                    $this->m_field['type'][1] = l('none');
                }
            } else {
                $this->m_field['type'][1] = l($row['type']);
            }
        }

        if ($i % 2 == 0) {
            $html->setvar("class", 'color');
            $html->setvar("decl", '_l');
            $html->setvar("decr", '_r');
        } else {
            $html->setvar("class", '');
            $html->setvar("decl", '');
            $html->setvar("decr", '');
        }

		parent::onItem($html, $row, $i, $last);
	}
}

$page = new CUsersNIDverify("main", $g['tmpl']['dir_tmpl_administration'] . "users_nid_verify.html");
$header = new CAdminHeader("header", $g['tmpl']['dir_tmpl_administration'] . "_header.html");
$page->add($header);
$footer = new CAdminFooter("footer", $g['tmpl']['dir_tmpl_administration'] . "_footer.html");
$page->add($footer);

$page->add(new CAdminPageMenuUsers());

include("../_include/core/administration_close.php");