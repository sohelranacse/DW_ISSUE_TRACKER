<?php

include("./_include/core/pony_start.php");

if($g_user['role'] == "user")
    redirect('search_results');

$name_seo = get_param('name', 'Text');
if(isset($name_seo)) { // edit
    $c_user_id = DB::result("SELECT user_id FROM user WHERE under_admin = ".guid()." AND name_seo = ".to_sql($name_seo, 'Text'));
    if(!$c_user_id)
        redirect('search_results');
} else
    redirect('search_results');

$g['to_head'][] = '<link rel="stylesheet" href="'.$g['tmpl']['url_tmpl_mobile'].'css/online.css" type="text/css" media="all"/>';
class CVisitors extends CUsers
{
    function onItem(&$html, $row, $i, $last)
	{
        if ($html->varExists('from_page')) {
            $html->setvar('from_page', 'users_viewed_me');
        }

		parent::onItem($html, $row, $i, $last);
        $html->parse('users_list_item_url', false);
	}

	function parseBlock(&$html)
	{
        $isAjaxRequest = get_param('ajax', 0);

        if ($html->varExists('offset_real')) {
            $html->setvar('offset_real', max(1, intval($this->m_offset)));
        }

        if (Common::isOptionActive('free_site') && $html->blockExists('class_indent')) {
            $html->parse('class_indent');
        }

        if (!$isAjaxRequest) {
            /*if ($html->blockExists('users_list_loader')) {
                $html->parse('users_list_loader', false);
            }
            if ($html->blockExists('users_list_scroll') && get_param('back_offset_profile_view')) {
                $html->parse('users_list_scroll', false);
            }*/
            if ($html->varExists('on_page')) {
                $html->setvar('on_page', $this->m_on_page);
                $html->parse('users_list_on_page', false);
            }
            if ($html->varExists('found_title')) {
                $html->setvar('found_title', l('found'));
            }
            if ($html->varExists('found_info')) {
                $html->setvar('found_info', l('found_info'));
            }
            if ($html->varExists('found_no_one')) {
                $html->setvar('found_no_one', l('found_no_one'));
            }
        }

		parent::parseBlock($html);
	}
}
$optionTmplSet = Common::getOption('set', 'template_options');
$isAjaxRequest = get_param('ajax', 0);

$tmpl = 'users_viewed_me.html';
$isUrban = $optionTmplSet == 'urban';
if ($isUrban) {
    $tmpl = 'search_results_viewed.html'; // true
    if ($isAjaxRequest) {
        $tmpl = 'search_results_ajax.html';
    }
}
class CPage extends CHtmlBlock
{
	function parseBlock(&$html)
	{
        if ($html->varExists('url_page_history')) {
            $html->setvar('url_page_history', Common::pageUrl('visitors?name='.get_param('name', 'Text')));
        }
        if ($html->blockExists('block_target')) {
            $html->parse('block_target_main', false);
            $html->parse('block_target', false);
        }
		parent::parseBlock($html);
	}
}

$page = new CPage("", $g['tmpl']['dir_tmpl_mobile'] . $tmpl);
// _frameworks/mobile/impact_mobile/search_results_viewed.html

if (!$isAjaxRequest) {
    $header = new CHeader("header", $g['tmpl']['dir_tmpl_mobile'] . "_header.html");
    $page->add($header);
    $tmplFooter = $g['tmpl']['dir_tmpl_mobile'] . "_footer.html";
    if (Common::isOptionActive('is_allow_empty_footer', 'template_options')) {
        $tmplFooter = $g['tmpl']['dir_tmpl_mobile'] . "_footer_empty.html";
    }
    $footer = new CFooter("footer", $tmplFooter);
    $page->add($footer);
}

$type = get_param("display", "info"); // info - CUsersInfo
/*if ($isUrban) $list = new CVisitors("users_list", $g['tmpl']['dir_tmpl_mobile'] . "_list_users_info.html");
elseif ($type == "info") $list = new CUsersInfo("users_list", $g['tmpl']['dir_tmpl_mobile'] . "_list_users_info.html");
elseif ($type == "gallery") $list = new CUsersGallery("users_list", $g['tmpl']['dir_tmpl_mobile'] . "_list_users_gallery.html");
elseif ($type == "list") $list = new CUsersList("users_list", $g['tmpl']['dir_tmpl_mobile'] . "_list_users_list.html");
elseif ($type == "profile") $list = new CUsersProfile("users_list", $g['tmpl']['dir_tmpl_mobile'] . "_profile.html");
elseif ($type == "photo") $list = new CHtmlUsersPhoto("users_list", $g['tmpl']['dir_tmpl_mobile'] . "_photo.html");
else {
	redirect("users_online.php");
}*/

require_once('_include/current/myusers.php');
$list = new MyUsersInfo("users_list", $g['tmpl']['dir_tmpl_mobile'] . "_list_users_info.html");

// by sohel
$user_id = $g_user['user_id'];

$list->m_sql_where = "u.user_id != {$user_id}";
$list->m_sql_select_add = ', v.id';
$list->m_sql_order = "id DESC";
$list->m_sql_group = "u.user_id";
// $list->m_sql_select_add = ", v.*";
$list->m_last_visit_only_online = true;
#$list->m_field['created_at'] = array("created_at", null);

$list->m_on_page = 5;

if ($isUrban) {
    $onPage = getMobileOnPageSearch();
    $list->m_on_page = get_param('on_page', $onPage);
    $list->m_offset = get_param('offset', (int)get_cookie('back_offset_profile_view', 1));
    $list->m_chk = $onPage;
    $list->m_offset_real = true;
}
// $list->m_debug = 'Y';

$list->row_breaks = true;
$list->m_sql_from_add = " JOIN users_view AS v ON (u.user_id=v.user_from AND v.user_to= {$c_user_id})";

DB::execute("UPDATE users_view SET new='N' WHERE user_to = {$user_id}");
if(DB::affected_rows()) {
    DB::execute("UPDATE user SET new_views=0 WHERE user_id=" . to_sql($user_id, "Number") . "");
}

$page->add($list);


if (!$isAjaxRequest) {
    if (Common::isParseModule('user_menu')) {
        $user_menu = new CUserMenu("user_menu", $g['tmpl']['dir_tmpl_mobile'] . "_user_menu.html");
        if ($isUrban) {
            $header->add($user_menu);
        } else {
            $user_menu->setActive('search');
            $page->add($user_menu);
        }
    }
    if (Common::isParseModule('people_nearby_spotlight')) {
        $spotlight = new Spotlight('spotlight', $g['tmpl']['dir_tmpl_mobile'] . '_spotlight.html');
        $spotlight->update = false;
        $page->add($spotlight);
    }
}

loadPageContentAjax($page);

include("./_include/core/main_close.php");