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

if(!empty($_FILES["file"]["name"])){ 

    $e_user_id = get_param('e_user_id', 0);
    if($e_user_id)
        $g_user['user_id'] = $e_user_id;

    $fileType = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION); 
    $fileName =  $g_user['user_id'].'.'.$fileType;
    $targetFilePath = '_files/pdf/'.$fileName;

    if(move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)){ 
        //$uploadedFile = $fileName;
        $sql = "UPDATE userinfo SET profile_pdf = '".$fileName."' WHERE user_id = ".$g_user['user_id'];
        DB::execute($sql);
        $_data = 1;
    }
} 

echo $_data;
die;
?>