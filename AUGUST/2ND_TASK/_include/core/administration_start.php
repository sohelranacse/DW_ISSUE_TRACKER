<?php
/* (C) Websplosion LLC, 2001-2021

IMPORTANT: This is a commercial software product
and any kind of using it must agree to the Websplosion's license agreement.
It can be found at http://www.chameleonsocial.com/license.doc

This notice may not be removed from the source code. */

include(__DIR__ . '/starter.php');

$g['path']['url_main'] = '../';
$sitePart = 'administration';
include(__DIR__ . '/start.php');

if (get_session('admin_auth') == 'Y') {
	$area = 'login';
	if ($p == 'index.php' and get_param('cmd') != 'logout') {
        Common::toHomePage();//redirect('home.php');
    }
} elseif (get_param('cmd') == 'login_token') {
    $token = get_param('token');
    $tokenAdmin = Config::getOptionsAll('token_admin', 'option');
    $isToken = false;
    foreach ($tokenAdmin as $key => $value) {
        if ($key == $token) {
            $isToken = true;
            break;
        }
    }
    if ($isToken) {
        Config::remove('token_admin');
        set_session('admin_auth', 'Y');
        Common::toHomePage();
    } else {
        Common::toLoginPage();
    }
}elseif(get_session('replier_auth') == 'Y'){
	$area = 'login';
	if ($p == 'index.php' and get_param('cmd') != 'logout') {
        //Common::toHomePage();//redirect('home.php');
        redirect("fakes_reply_mails.php");
    }
	$pages_zone = array('index.php', 'fakes_reply_mails.php','fakes_reply_im.php','fakes_reply_winks.php', 'fakes_friend_requests.php');
}
/*elseif(get_session('groupAdmin_auth') == 'Y'){
    $area = 'login';
    if ($p == 'index.php' and get_param('cmd') != 'logout') {
        redirect("group_admin_panel.php");
    }
    $pages_zone = array('index.php', 'ajax.php', 'group_admin_panel.php', 'add_group_user.php', 'users_edit.php');
}*/

else {
	$area = 'public';
	$pages_zone = array('index.php', 'forget_password.php', 'js.php');
}

if(isset($pages_zone)){
	$access = 'N';
	foreach ($pages_zone as $k => $v) {
        if ($p == $v) {
            $access = 'Y';
        }
    }
	if ($access == 'N') {
        Common::toLoginPage();//redirect('index.php');
    }
}

$tmplParam = get_param('tmpl');
if ($p == 'template_settings.php' && $tmplParam) {
    $g['tmpl']['main'] = $tmplParam;
    $g['tmpl']['mobile'] = "{$tmplParam}_mobile";
    $g['tmpl']['dir_tmpl_main'] = "{$g['path']['dir_tmpl']}main/{$tmplParam}/";
    $g['tmpl']['url_tmpl_main'] = "{$g['path']['url_tmpl']}main/{$tmplParam}/";
}

$g['template_options'] = loadTemplateSettings('main', Common::getOption('main', 'tmpl'));
$g['template_options_mobile'] = loadTemplateSettings('mobile', Common::getOption('mobile', 'tmpl'));

include_once($g['path']['dir_main'] . "_include/current/common_admin.php");