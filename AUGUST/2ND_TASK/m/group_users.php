<?php

include('./_include/core/pony_start.php');
checkByAuth();
if($g_user['role'] == "user")
    redirect('search_results');

class CGroupUsersMobile extends CHtmlBlock
{
    function action() {
        global $g_user, $p, $g;

        $del = get_param('delete');
        $banned = intval(get_param('ban'));

        $gadmin_previllage = explode(',', $g_user['gadmin_previllage']); // add, edit, delete

        $cmd = get_param('cmd', '');
        if ($cmd == 'get_group_user_data') {
            $group_admin_id = guid();
            $result = DB::all("
                SELECT a.user_id, a.name, a.name_seo, a.mail, a.phone, a.register,
                (DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(a.birth, '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(a.birth, '00-%m-%d'))
                ) AS age,
                (SELECT title FROM const_orientation WHERE id = a.orientation) AS gender, a.ban_global,
                (SELECT photo_id FROM photo WHERE user_id = a.user_id LIMIT 1) AS photo
                FROM user a WHERE a.under_admin = '".$group_admin_id."' ORDER BY a.register DESC
            ");
            echo json_encode($result);
            die();
        } elseif ($del) {
            if($gadmin_previllage[2]) {
                if (Common::isEnabledAutoMail('admin_delete') && Common::validateField('user_id', $del)) {
                    DB::query('SELECT * FROM user WHERE under_admin = '.guid().' AND user_id = ' . to_sql($del, 'Number'));
                    $row = DB::fetch_row();
                    $vars = array(
                        'title' => $g['main']['title'],
                        'name' => $row['name'],
                    );
                    Common::sendAutomail($row['lang'], $row['mail'], 'admin_delete', $vars);
                }
                echo json_encode('success');
                delete_user($del);
            }
            die();
        } elseif ($banned) {
            if(Common::validateField('user_id', $banned)) {
                $sql='UPDATE user SET ban_global=1-ban_global WHERE under_admin = '.guid().' AND user_id='. to_sql($banned, 'Number');
                DB::execute($sql);
                echo json_encode('success');
            }
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
$dirTmpl = $g['tmpl']['dir_tmpl_mobile'];

$page = new CGroupUsersMobile("", "{$dirTmpl}group_users.html");

$header = new CHeader('header', "{$dirTmpl}_header.html");
$page->add($header);

$footer = new CFooter("footer", "{$dirTmpl}_footer.html");
$page->add($footer);

loadPageContentAjax($page);

include('./_include/core/main_close.php');