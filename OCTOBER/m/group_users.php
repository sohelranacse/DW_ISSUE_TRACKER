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
                (SELECT photo_id FROM photo WHERE user_id = a.user_id AND `default` = 'Y') AS photo,
                (SELECT COUNT(id) FROM users_view WHERE user_to = a.user_id AND user_from != {$group_admin_id}) AS total_visitors
                FROM user a WHERE a.under_admin = '".$group_admin_id."' ORDER BY a.register DESC
            ");
            // dd($group_admin_id);exit();
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

        $result = DB::row("
            SELECT COUNT(id) AS total_visitors
            FROM users_view
            WHERE  (
                user_to = {$g_user['user_id']}
                OR user_to IN (SELECT user_id FROM user WHERE under_admin = {$g_user['user_id']})
            )
            AND user_from != {$g_user['user_id']}
        ");
        $html->setvar("total_visitors", "<i class='fa fa-user'></i><i class='fa fa-user'></i>&nbsp; ".$result['total_visitors']);
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