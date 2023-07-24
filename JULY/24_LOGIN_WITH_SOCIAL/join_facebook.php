<?php
/* (C) Websplosion LLC, 2001-2021

IMPORTANT: This is a commercial software product
and any kind of using it must agree to the Websplosion's license agreement.
It can be found at http://www.chameleonsocial.com/license.doc

This notice may not be removed from the source code. */

include("./_include/core/main_start.php");

/*$isPwa = PWA::isModePwa();
$pwaSocialCallback =  get_cookie('pwa_social_callback', true);
set_cookie('pwa_social_callback', '', -1, true);
if ($isPwa && $pwaSocialCallback) {
    $page = new CHtmlBlock("", $g['tmpl']['dir_tmpl_main'] . 'social_callback.html');
    include("./_include/core/main_close.php");
    return;
}*/

$l[$p] = $l['join.php'];

$cmd = get_param('cmd');
$currentSocial='';
if ($cmd == 'fb_login') {
    $currentSocial='facebook';
}elseif($cmd == 'gl_login') {
    $currentSocial='google_plus';
}elseif($cmd == 'ln_login') {
    $currentSocial='linkedin';
}

if($currentSocial!=''){
    Social::setActive($currentSocial);
    Social::login();
}

if(guid()) {
    $redirect = get_session('social_login_page_from', Common::getHomePage());
    Social::connect($redirect);
}

$tmpl = 'join_facebook.html';
if (Common::getOption('set', 'template_options') == 'urban') {
    // $tmpl = Common::getOption('register_page_template', 'template_options');
    $tmpl = "register_2.html";
}
$page = new CHtmlBlock("", preparePageTemplate($tmpl));
$header = new CHeader("header", $g['tmpl']['dir_tmpl_main'] . "_header.html");
$page->add($header);
$footer = new CFooter("footer", $g['tmpl']['dir_tmpl_main'] . "_footer.html");
$page->add($footer);

$register = new CJoinForm("join", null);

$page->add($register);

include("./_include/core/main_close.php");