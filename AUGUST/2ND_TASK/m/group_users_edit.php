<?php

$area = "login";
include("./_include/core/pony_start.php");

if($g_user['role'] == "user")
    redirect('search_results');

$gadmin_previllage = explode(',', $g_user['gadmin_previllage']);
$name_seo = get_param('name', 'Text');
if(isset($name_seo) && $gadmin_previllage[1]) { // edit
    $user_id = DB::result("SELECT user_id FROM user WHERE under_admin = ".guid()." AND name_seo = ".to_sql($name_seo, 'Text'));
    if(!$user_id)
        redirect('search_results');
} else
    redirect('search_results');

User::accessCheckToProfile();


$g['to_head'][] = '<link rel="stylesheet" href="'.$g['tmpl']['url_tmpl_mobile'].'css/home.css" type="text/css" media="all"/>';

$optionTmplSet = Common::getOption('set', 'template_options');

$page = new CHtmlBlock("", $g['tmpl']['dir_tmpl_mobile'] . "profile_view.html");

$type = get_param("display", "profile");
$display = get_param('display', 'profile');

$tmpl = $g['tmpl']['dir_tmpl_mobile'] . '_profile.html';
$tmpl = array('main' => $g['tmpl']['dir_tmpl_mobile'] . '_edit_profile.html');
$tmplProfileInfo = Common::getOption('display_info_page_template', 'template_options');

$g['c_user_id'] = $user_id;
$list = new CUsersProfile("users_list", $tmpl);

$list->m_city_prefix = "| ";

$list->m_sql_where = "u.user_id=" . to_sql($user_id, 'Number') . "";
$list->m_is_me = false;

if (Common::isOptionActive('profile_menu_the_same_user_menu', 'template_options')) {
    $userMenu = new CUserMenu('profile_user_menu', $g['tmpl']['dir_tmpl_mobile'] . '_profile_view_menu.html');
    $userMenu::setType('profile_view');
    $list->add($userMenu);
}

$page->add($list);

$header = new CHeader("header", $g['tmpl']['dir_tmpl_mobile'] . "_header.html");
$page->add($header);
$footer = new CFooter("footer", $g['tmpl']['dir_tmpl_mobile'] . "_footer.html");
$page->add($footer);

if (Common::isParseModule('friends_list')) {
    $friends_list = new CFriendsList("friends_list", $g['tmpl']['dir_tmpl_mobile'] . "_friends_list.html");
    $friends_list->user_id = $user_id;
    $page->add($friends_list);
}

if (Common::isParseModule('profile_view_menu')) { // false
    $isParseViewMenu = true;
    if ($optionTmplSet == 'urban' && $type == "profile_info") {
        $isParseViewMenu = false;
    }dd($isParseViewMenu);
    if ($isParseViewMenu) {
        include("./_include/current/profile_view_menu.php");
        $profile_view_menu = new CProfileViewMenu("profile_view_menu", $g['tmpl']['dir_tmpl_mobile'] . "_profile_view_menu.html");
        $profile_view_menu->user_id = $user_id;
        $page->add($profile_view_menu);
    }
}

if (Common::isParseModule('user_menu')) { // false
    $user_menu = new CUserMenu("user_menu", $g['tmpl']['dir_tmpl_mobile'] . "_user_menu.html");
    if ($optionTmplSet == 'urban') {
        $header->add($user_menu);
    } else {
        $page->add($user_menu);
    }
}

loadPageContentAjax($page);

include("./_include/core/main_close.php");