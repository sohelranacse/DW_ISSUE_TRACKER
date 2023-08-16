<?php

include('./_include/core/main_start.php');

checkByAuth();
if($g_user['role'] == "user")
	redirect('search_results');

$gadmin_previllage = explode(',', $g_user['gadmin_previllage']);
$name_seo = get_param('name', 'Text');
if(isset($name_seo) && $gadmin_previllage[1]) { // edit
	$c_user_id = DB::result("SELECT user_id FROM user WHERE under_admin = ".guid()." AND name_seo = ".to_sql($name_seo, 'Text'));
	if(!$c_user_id)
		redirect('search_results');
} else
	redirect('search_results');

// MODIFY

include("./_include/core/main_start.php");
include("./_include/current/menu_section.class.php");

$_GET['display'] = get_param('display', User::displayProfile());

$where = ' u.user_id = ' . to_sql($c_user_id, 'Number');
$order = '';
$page = Users_List::show($where, $order);

include('./_include/core/main_close.php');