<?php

include('./_include/core/main_start.php');

checkByAuth();
if($g_user['role'] == "user")
	redirect('search_results');

class CGroupUsers extends CHtmlBlock
{
	function action() {
		global $g_user, $p, $g;

		$del = get_param('delete');
        $banned = intval(get_param('ban'));
        $isRedirect = false;

        $gadmin_previllage = explode(',', $g_user['gadmin_previllage']);

		$cmd = get_param('cmd', '');
		if ($cmd == 'get_group_user_data') {
			$group_admin_id = guid();
			$result = DB::all("
				SELECT a.user_id, a.name, a.name_seo, a.mail, a.phone, a.register,
				(DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(a.birth, '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(a.birth, '00-%m-%d'))
				) AS age,
				(SELECT title FROM const_orientation WHERE id = a.orientation) AS gender, a.ban_global
                FROM user a WHERE a.under_admin = '".$group_admin_id."' ORDER BY a.register
            ");
			echo json_encode($result);
            die();
		} elseif ($del && $gadmin_previllage[1]) {
            $user =  explode(',', $del);
            foreach ($user as $userId) {
                if (Common::isEnabledAutoMail('admin_delete')) {
                    DB::query('SELECT * FROM user WHERE under_admin = '.guid().' AND user_id = ' . to_sql($userId, 'Number'));
                    $row = DB::fetch_row();
                    $vars = array(
                        'title' => $g['main']['title'],
                        'name' => $row['name'],
                    );
                    Common::sendAutomail($row['lang'], $row['mail'], 'admin_delete', $vars);
                }
                delete_user($userId);
            }
			$isRedirect = true;
		} elseif ($banned) {
			$sql='UPDATE user SET ban_global=1-ban_global WHERE under_admin = '.guid().' AND user_id='. to_sql($banned, 'Number');
			DB::execute($sql);
            $isRedirect = true;
		}
        if ($isRedirect)
            redirect('group_users');
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