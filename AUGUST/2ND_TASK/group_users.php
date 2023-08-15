<?php

include('./_include/core/main_start.php');

checkByAuth();
if($g_user['role'] == "user")
	redirect('search_results');

class CGroupUsers extends CHtmlBlock
{
	function action() {

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
		}
	}
	function parseBlock(&$html)
	{
		global $g_user;
		$html->setvar("page_title", l('group_users'));
		$html->setvar("gadmin_previllage", $g_user['gadmin_previllage']);
		parent::parseBlock($html);
	}
}
$dirTmpl = $g['tmpl']['dir_tmpl_main'];

$page = new CGroupUsers("", getPageCustomTemplate('group_users.html', 'custom_page_template'));
$header = new CHeader("header", $dirTmpl . "_header.html");
$page->add($header);

$footer = new CFooter("footer", $dirTmpl . "_footer.html");
$page->add($footer);

include("./_include/core/main_close.php");