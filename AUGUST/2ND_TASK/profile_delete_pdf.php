<?php
/* (C) Websplosion LLC, 2001-2021

IMPORTANT: This is a commercial software product
and any kind of using it must agree to the Websplosion's license agreement.
It can be found at http://www.chameleonsocial.com/license.doc

This notice may not be removed from the source code. */

$area = "login";
include("./_include/core/main_start.php");

global $g_user;
$_data = 0;

if(!empty($_POST)){
	$e_user_id = get_param('e_user_id', 0);
    if($e_user_id)
        $g_user['user_id'] = $e_user_id;
    
    $targetFilePath = '_files/pdf/'.$g_user['user_id'].'.pdf';

	$sql = "UPDATE userinfo SET profile_pdf = '' WHERE user_id = ".$g_user['user_id'];
	DB::execute($sql);

    unlink($targetFilePath);
	$_data = 1;
}

echo $_data;
die;
?>