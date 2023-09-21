<?php
/* (C) Websplosion LLC, 2001-2021

IMPORTANT: This is a commercial software product
and any kind of using it must agree to the Websplosion's license agreement.
It can be found at http://www.chameleonsocial.com/license.doc

This notice may not be removed from the source code. */

$area = "login";
include("./_include/core/main_start.php");


if (!User::accessCheckFeatureSuperPowers('profile_visitors_paid')) {
    redirect(Common::pageUrl('upgrade'));
}

CustomPage::setSelectedMenuItemByTitle('column_narrow_profile_visitors');

if (Common::isOptionActive('column_narrow_menu', 'template_options')) {
    $optionTmplName = Common::getOption('name', 'template_options');
    $sql = 'SELECT `status`
              FROM `pages`
             WHERE `menu_title` = "column_narrow_profile_visitors"
               AND `set` = ' . to_sql($optionTmplName) . '
               AND `lang` = "default"';
    if (!DB::result($sql)) {
        Common::toHomePage();
    }
}

$where = "u.user_id!=" . to_sql($g_user['user_id'], "Number") . "";
$order = "v.id DESC";

/* URBAN */
$fromAddCustom = '';
if (Common::getOption('viewed_me_custom_settings', 'template_options')) {
    $isAjaxRequest = get_param('ajax');
    $id = get_param('id');
    if ($isAjaxRequest && $id) {
        $fromAddCustom = ' AND v.id < ' . to_sql($id, 'Number');
    }
} elseif(UserFields::isActive('orientation') && Common::isOptionActive('user_choose_default_profile_view')){
    $gender = get_param('gender');
    $isSearch = User::isListOrientationsSearch();
    if (!$gender && $isSearch) {
        $gender = guser('default_online_view');
    }
    if ($gender && $gender != 'B') {
        $where .= ' AND u.gender = ' . to_sql($gender);
    }
}
/* URBAN */

// $from_add = " JOIN users_view AS v ON (u.user_id=v.user_from AND v.user_to=" . to_sql($g_user['user_id'], "Number") . $fromAddCustom . ")";
$from_add = " JOIN users_view AS v ON (u.user_id=v.user_from AND (v.user_to= {$g_user['user_id']} OR v.user_to IN (SELECT user_id FROM user WHERE under_admin = {$g_user['user_id']})))";

DB::execute("UPDATE users_view SET new='N' WHERE user_to=" . to_sql($g_user['user_id'], "Number") . "");
if(DB::affected_rows()) {
    DB::execute("UPDATE user SET new_views=0 WHERE user_id=" . to_sql($g_user['user_id'], "Number") . "");
}

$template = 'users_list_base.html';
$templateTmpl = Common::getOptionTemplate('user_list_template');
if ($templateTmpl) {
    $template =  $templateTmpl;
}
// $group = 'u.user_id';
$page = Users_List::show($where, $order, $from_add, $template);

include("./_include/core/main_close.php");