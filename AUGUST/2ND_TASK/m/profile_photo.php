<?php
/* (C) Websplosion LLC, 2001-2021

IMPORTANT: This is a commercial software product
and any kind of using it must agree to the Websplosion's license agreement.
It can be found at http://www.chameleonsocial.com/license.doc

This notice may not be removed from the source code. */

$area = "login";
include("./_include/core/pony_start.php");


$g['to_head'][] = '<link rel="stylesheet" href="'.$g['tmpl']['url_tmpl_mobile'].'css/profile.css" type="text/css" media="all"/>';

class CPhoto extends CHtmlBlock
{

	var $sMessage = "";

	function action()
	{
		global $g;
		global $g_user;

		$cmd = get_param("cmd", "");
        $isAjaxRequest = get_param('ajax', 0);

        $optionTmplName = Common::getOption('name', 'template_options');
        if ($isAjaxRequest && $cmd != 'insert') {
            $responseData = false;
            if ($cmd == 'publish_one_photo') {
                $guid = get_param('e_user_id') ? get_param('e_user_id') : $g_user['user_id'];
                $pid = get_param('photo_id');
                if ($g_user['user_id'] && $pid) {
                    $responseData = CProfilePhoto::publishOnePhoto($pid);
                    if (get_param('get_photo_info')) {
                        $responseData = CProfilePhoto::preparePhotoList($guid, '`photo_id` ASC') + CProfilePhoto::prepareVideoList($guid);
                        if ($responseData && $optionTmplName == 'impact_mobile') {
                            $responseData = array(
                                'gallery_info' => $responseData,
                                'min_number_upload_photos' => User::checkAccessToSiteWithMinNumberUploadPhotos(true)
                            );
                        }
                    }
                }
            } elseif($cmd == 'publish_one_video'){
                $pid = get_param('photo_id');
                if ($g_user['user_id'] && $pid) {
                    CProfilePhoto::publishOneVideo($pid);
                    $responseData = true;
                    if (get_param('get_photo_info')) {
                        $responseData = CProfilePhoto::preparePhotoList($g_user['user_id'], '`photo_id` ASC') + CProfilePhoto::prepareVideoList($g_user['user_id']);
                    }
                }
            }elseif ($cmd == "set_photo_default"){
                $responseData = CProfilePhoto::setPhotoDefault();
            }elseif ($cmd == "set_photo_description"){
                $responseData = CProfilePhoto::savePhotoDescription();
            }elseif ($cmd == "set_photo_private"){
                $responseData = CProfilePhoto::setPhotoPrivate(get_param('photo_id'));
            }elseif ($cmd == "photo_delete"){
                $guid = get_param('e_user_id') ? get_param('e_user_id') : $g_user['user_id'];
                $getPhotoDefaultId = intval(get_param('get_photo_id', 1));
                $responseData = CProfilePhoto::deletePhoto(get_param('photo_id'), $getPhotoDefaultId, $guid);
                if ($responseData && $optionTmplName == 'impact_mobile') {
                    $responseData = array(
                        'gallery_info' => $responseData,
                        'min_number_upload_photos' => User::checkAccessToSiteWithMinNumberUploadPhotos(true)
                    );
                }
            }elseif ($cmd == "photo_rotate"){
                $guid = get_param('e_user_id') ? get_param('e_user_id') : $g_user['user_id'];
                $responseData = CProfilePhoto::photoRotate($guid);
            }
            die(getResponseDataAjaxByAuth($responseData));
        }

        if ($cmd == "insert"){
            $guid = get_param('e_user_id') ? get_param('e_user_id') : $g_user['user_id'];

            $isVideo=get_param("is_video", 0);
            if($isVideo){
                if(isset($_FILES["file_add_public"])){
                    $_FILES["file_add_public_bind"]=$_FILES["file_add_public"];
                }
                $upload = isset($_FILES["file_add_public_bind"]) ? $_FILES["file_add_public_bind"] : null;
                if (is_array($upload)){
                    include_once($g['path']['dir_main'] . '_include/current/vids/tools.php');
                    $defCats = CVidsTools::getDefaultCats();
                    setses('video_upload_subject', '');
                    setses('video_upload_text'   , '');
                    setses('video_upload_tags'   , '');
                    setses('video_upload_cat'    , implode(',',$defCats));
                    setses('video_upload_private', '0');
                    setses('video_no_validate', true);

                    $resps = array();
                    $processes = array();
                 //   foreach ($upload['tmp_name'] as $index => $value) {
                     $index=0;
                        if (isValidMimeType($upload['tmp_name'])) {
                            $fileTemp = $guid . '_' . time() . '_' . mt_rand();
                            move_uploaded_file($upload['tmp_name'], $g['path']['dir_files'] . "video/" . $fileTemp . ".txt");
                            $r = VideoUpload::upload(0, $fileTemp, 'mp4');

                            if($r === true) {
                                $id = CVidsTools::insertVideo($fileTemp, 2, false, 1);

                                $previewSize = get_param('size', '');
                                if($previewSize) {
                                    $previewSize = '_' . $previewSize;
                                }

                                $resp['data'][$index] = array(
                                    'name' => get_param('file_name'),
                                    'size' => $upload['size'],
                                    'type' => $upload['type'],
                                    'file_name' => $index,
                                    //'url' => $upload['tmp_name'],
                                    'deleteUrl' => '',
                                    'deleteType' => 'DELETE',
                                    'id' => 'v_' . $id,
                                    'src_r' => 'video/' . $id . $previewSize . '.jpg',
                                    'id_upload' => get_param('id_upload'),
                                );

                                if(!$id) {
                                    $resp['file_video'][$index]['error'] = l('error_converting_video');
                                }

                            } else {
                                $resp['data'][$index]['error'] = $r;
                            }
                        } else {
                            $resp['data'][$index]['error'] = l('accept_file_types');
                        }
                 //   }
                    $resp['status'] = 1;
                    die(json_encode($resp));
                } else {
                    die('error');
                }

            } else {

                $this->message = "";

                if ($isAjaxRequest) {
                    $name = get_param('file');
                    $responseData = CProfilePhoto::validate($name);
                    if (!empty($responseData)) {
                        $responseData['id_upload'] = get_param('id_upload');
                        die(getResponseDataAjaxByAuth($responseData));
                    }
                } else {
                    $this->message = validatephoto("photo_file");
                }

                $photo_name = get_param("photo_name", "");
                $description = get_param("description", "");

                if (strlen($photo_name) > 10 or !preg_match("/^[a-zA-Z\-_\.0-9\s]{0,50}$/", $photo_name))
                {
                    #$this->message .= "The photo Name incorrect.\\n";
                }

                if (strlen($description) > 100 or !preg_match("/^[!?%&*+=@\$\(\)\[\]\{\}a-zA-Z\-_\.0-9\r\n\s]{0,100}$/", $description))
                {
                    #$this->message .= "The Description incorrect.\\n";
                }

                $num_photos = CProfilePhoto::countPhoto($guid);//DB::result("SELECT COUNT(photo_id) FROM photo WHERE user_id=" . $g_user['user_id'] . ";");
                $maxUploadCount = Common::getOption('upload_limit_photo_count');
                if (!Common::isOptionActive('free_site') && !User::isSuperPowers() && $num_photos >= $maxUploadCount)
                {
                    if ($isAjaxRequest) {
                        $responseData = array('error' => lSetVars('you_need_to_upgrade_to_upload_more_photos', array('count' => $maxUploadCount)));
                        $responseData['id_upload'] = get_param('id_upload');
                        die(getResponseDataAjaxByAuth($responseData));
                    }else{
                        $this->message .= lSetVars('you_need_to_upgrade_to_upload_more_photos', array('count'=>$maxUploadCount)) . "\\n";
                    }
                }

                if ($this->message == "") {
                    if ($isAjaxRequest) {
                        $name = get_param('file');
                        $responseData = CProfilePhoto::photoUpload('', $name);
                        $responseData['id_upload'] = get_param('id_upload');

                        die(getResponseDataAjaxByAuth($responseData));
                    } else {
                        CStatsTools::count('photos_uploaded');
                        uploadphoto($guid, $photo_name, $description, 0);
                        redirect($p . '?t=' . time());
                    }
                } else {
                    if ($isAjaxRequest) {
                        die(getResponseDataAjaxByAuth($this->message));
                    } else {
                        redirect($p . '?t=' . time());
                    }
                }
            }
		}

		if ($cmd == "update"){
			$photo_id = get_param("id", 0);
			if ($photo_id == 0){
				return;
			}
			$this->message = "";

			$photo_name = get_param("photo_name", "");
			$description = get_param("description", "");

			if (strlen($photo_name) > 10 or !preg_match("/^[a-zA-Z\-_\.0-9\s]{0,50}$/", $photo_name) and $photo_name != "")
			{
				#$this->message .= "The photo Name incorrect.\\n";
			}

			if (strlen($description) > 100 or !preg_match("/^[a-zA-Z\-_\.0-9\r\n\s]{0,100}$/", $description) and $description != "")
			{
				#$this->message .= "The Description incorrect.\\n";
			}

			if ($this->message != ""){
				return;
			}else{
				DB::execute("UPDATE photo SET
					photo_name=" . to_sql($photo_name) . ", description=" . to_sql($description) . "
					WHERE photo_id=" . to_sql($photo_id, "Number") . " AND user_id=" . $guid . ";"
				);
			}
		}

		if ($cmd == "delete"){
			$photo_id = get_param("id", 0);
			if ($photo_id)	{
                CProfilePhoto::deletePhoto($photo_id);
			}
			$this->message = "Photo deleted.\\n";
		}

		/*if ($cmd == "main"){
			$photo_id = get_param("id", 0);
			if ($photo_id == 0){
				return;
			}
			deletephoto($g_user['user_id'], $photo_id);
			$this->message = "Photo deleted.\\n";
		}*/
        photoDefaultCheck();
	}

	function parseBlock(&$html)
	{
		global $g;
		global $g_user;

        if(Common::isOptionActive('photo_approval')) {
            $html->parse('photo_approval_active');
        }
        if(Common::isOptionActive('video_approval')) {
            $html->parse('video_approval_active');
        }

        DB::delete('photo', "`visible` = 'P' AND `user_id` = " . to_sql($g_user['user_id'], 'Number'));

        if ($html->varExists('photo_file_size_limit')) {
            $maxFileSize = Common::getOption('photo_size');
            $html->setvar('photo_file_size_limit', mb_to_bytes($maxFileSize));
            $html->setvar('max_photo_file_size_limit', lSetVars('max_file_size', array('size'=>$maxFileSize)));
        }
        if ($html->varExists('video_file_size_limit')) {
            $maxVideoSize = Common::getOption('video_size');
            $html->setvar('video_file_size_limit', mb_to_bytes($maxVideoSize));
            $html->setvar('max_video_file_size_limit', lSetVars('max_file_size', array('size'=>$maxVideoSize)));
        }
        if ($html->varExists('upload_limit_photo_count')) {
            $html->setvar('upload_limit_photo_count', Common::getOption('upload_limit_photo_count'));
        }

        if ($html->varExists('guid')) {
            $html->setvar('guid', $g_user['user_id']);
        }

        if ($html->varExists('i_am_in_spotlight')) {
            $html->setvar('i_am_in_spotlight', intval(Spotlight::isThere()));
        }

		if (isset($this->message)) $html->setvar("message", $this->message);



		DB::query("SELECT * FROM photo WHERE user_id=" . $g_user['user_id'] . "  AND group_id = 0 AND visible != 'P' ORDER BY photo_id DESC;");

        $num_photos = DB::num_rows();

        $html->setvar("num_photos", $num_photos);
        $html->setvar('is_super_powers', User::isSuperPowers()?'true':'false');
        $html->setvar('is_free_site', Common::isOptionActive('free_site')?'true':'false');
        $html->setvar('upload_more_than_limit', lSetVars('you_need_to_upgrade_to_upload_more_photos',array('count'=>Common::getOption('upload_limit_photo_count'))));



		$add_photo_exists = false;

		if (($num_photos + 1) % 2 == 0) {
            $num_photos++;
        } elseif($num_photos > 3) {
            $num_photos += 2;
        }

        if($num_photos < 4) {
            $num_photos = 4;
        }

		$row_index = 1;
		$row_buffer = 1;

		for ($i = 1; $i <= $num_photos; $i++)
		{
			$html->setvar("numer", $i);
			$row_index = $row_buffer;
			$html->setvar("row",$row_index);

			if ($row = DB::fetch_row())
			{
				$html->setvar("photo_id", $row['photo_id']);
				$html->setvar("user_id", "photo/" . $row['user_id']);
				if ($html->varExists('photo_url')) {
                    $html->setvar('photo_url', User::photoFileCheck($row, 'm'));
                }
				if ($html->varExists('photo_url_mm')) {
                    $html->setvar('photo_url_mm', User::photoFileCheck($row, 'mm'));
                }
				$html->setvar("photo_name", $row['photo_name']);
				$html->setvar("description", nl2br($row['description']));
                $html->setvar("description_attr", toAttr($row['description']));

                $isPhotoApproval = CProfilePhoto::isPhotoOnVerification($row['visible']);
				$html->setvar("visible", $isPhotoApproval ? l("(pending audit)") : '');

                $blockPhotoName = ($row['private'] == 'N') ? 'photo_public' : 'photo_private';

                if ($isPhotoApproval){
                    $html->parse($blockPhotoName . '_not_checked', false);
                } else {
                    $html->clean($blockPhotoName . '_not_checked');
                }

                if($isPhotoApproval && Common::getOptionTemplate('type_profile_photo') != 'urban_mobile') {
                    $row['default'] = 'Y';
                }

                if ($html->varExists('photo_approve')) {
                    $html->setvar('photo_approve', intval($isPhotoApproval));
                }

                if($row['default'] == 'N') {
                    $html->setvar('default_title', l('make_default'));
                    $html->parse("set_default", false);
                    $html->setblockvar("default", '');
                    $html->setvar("photo_default", 0);
                } else {
                    $html->setvar('default_title', l('default'));
                    $html->parse("default", false);
                    $html->setblockvar("set_default", '');
                    $html->setvar("photo_default", 1);
                }

                if($isPhotoApproval) {
                    $html->setblockvar("default", '');
                    $html->setblockvar("set_default", '');
                }


                if($row['private'] == 'N') {
                    $html->setvar('private_title', l('make_private'));
                    $html->parse("set_private", false);
                    $html->setblockvar("set_public", '');
                } else {
                    $html->setvar('private_title', l('private'));
                    $html->parse("set_public", false);
                    $html->setblockvar("set_private", '');
                }

				if (($i + 1) % 2 == 0) {
				$html->parse("photo_odd", true);
				}
				else $html->setblockvar("photo_odd", "");

				if ($i % 2 == 0) {
					$html->parse("photo_even", true);
					if($row_index==1) $row_buffer = 2;
					if($row_index==2) $row_buffer = 1;
				}
				else $html->setblockvar("photo_even", "");

				$html->parse("photo_item", true);
				$html->parse("photo", false);

                $html->cond($row['private'] == 'N', 'photo_public', 'photo_private');

			}
			else
			{
				$html->setvar("photo_id", "");
				$html->setvar("user_id", "");
				$html->setvar("photo_name", "");
				$html->setvar("description", "");

				$html->setvar("visible", "");

				$html->setvar("orientation", $g_user['orientation']);

				if (($i + 1) % 2 == 0) $html->parse("nophoto_odd", true);
				else $html->setblockvar("nophoto_odd", "");

				if ($i % 2 == 0) $html->parse("nophoto_even", true);
				else $html->setblockvar("nophoto_even", "");

                $html->parse("addphoto_item", true);

				$html->parse("nophoto_item", true);
				$html->parse("photo", false);
			}

		}


        /***********************************/

        if(Common::getOptionSetTmpl() == 'urban' && Common::isOptionActive('videogallery')) {

            DB::query("SELECT * FROM `vids_video` WHERE user_id=" . $g_user['user_id'] . " AND active != 2 ORDER BY id DESC;");

            $num_videos = DB::num_rows();

            $html->setvar("num_videos", $num_videos);
            $html->setvar('upload_more_than_limit_video', lSetVars('you_need_to_upgrade_to_upload_more_videos',array('count'=>Common::getOption('upload_limit_video_count'))));



            if (($num_videos + 1) % 2 == 0) {
                $num_videos++;
            } elseif($num_videos > 3) {
                $num_videos += 2;
            }

            if($num_videos < 4) {
                $num_videos = 4;
            }

            $row_index = 1;
            $row_buffer = 1;

            $blockVideoName = 'video';

            for ($i = 1; $i <= $num_videos; $i++)
            {
                $html->setvar("numer", $i);
                $row_index = $row_buffer;
                $html->setvar("row",$row_index);

                if ($row = DB::fetch_row())
                {
                    $html->setvar("video_id", 'v_'.$row['id']);
                    $html->setvar("user_id", $row['user_id']);
                    if ($html->varExists('video_url')) {
                        $html->setvar('video_url', User::videoFileCheck($row, 'm'));
                    }
                    $html->setvar("video_name", $row['subject']);
                    $html->setvar("description", nl2br($row['subject']));
                    $html->setvar("description_attr", toAttr($row['subject']));

                    $html->setvar("visible", ($row['active'] == 3 and $g['options']['photo_approval'] == "Y") ? "(pending audit)" : "");

                    $isVideoApproval = CProfilePhoto::isVideoOnVerification(guid(), ($row['active'] == 1 ? 'Y' : 'N'));

                    if ($isVideoApproval){
                        $html->parse($blockVideoName . '_not_checked', false);
                    } else {
                        $html->clean($blockVideoName . '_not_checked');
                    }

                    if($row['private'] == 0) {
                        $html->setvar('private_title', l('make_private'));
                        $html->parse("set_private", false);
                        $html->setblockvar("set_public", '');
                    } else {
                        $html->setvar('private_title', l('private'));
                        $html->parse("set_public", false);
                        $html->setblockvar("set_private", '');
                    }

                    if (($i + 1) % 2 == 0) {
                    $html->parse("photo_odd", true);
                    }
                    else $html->setblockvar("photo_odd", "");

                    if ($i % 2 == 0) {
                        $html->parse("photo_even", true);
                        if($row_index==1) $row_buffer = 2;
                        if($row_index==2) $row_buffer = 1;
                    }
                    else $html->setblockvar("photo_even", "");

                    $html->parse("photo_item", true);
                    $html->parse("photo", false);

                    $html->cond($row['private'] == 0, 'video_public', 'video_private');

                }
                else
                {
                    $html->setvar("video_id", "");
                    $html->setvar("user_id", "");
                    $html->setvar("video_name", "");
                    $html->setvar("description", "");

                    $html->setvar("visible", "");

                    $html->setvar("orientation", $g_user['orientation']);

                    if (($i + 1) % 2 == 0) $html->parse("nophoto_odd", true);
                    else $html->setblockvar("nophoto_odd", "");

                    if ($i % 2 == 0) $html->parse("nophoto_even", true);
                    else $html->setblockvar("nophoto_even", "");

                    $html->parse("addphoto_item", true);

                    $html->parse("nophoto_item", true);
                    $html->parse("photo", false);
                }

            }

            $html->parse('videos');
        }

        //if (($num_photos + 1) % 2 == 0) $num_photos++;
/*
        $num_photos = 4;

		for ($i = 1; $i <= $num_photos; $i++)
		{
			$html->setvar("numer", $i);

			if ($row = DB::fetch_row())
			{
				$html->setvar("photo_id", $row['photo_id']);
				$html->setvar("user_id", "photo/" . $row['user_id']);
				$html->setvar("photo_name", $row['photo_name']);
				$html->setvar("description", nl2br($row['description']));

				$html->setvar("visible", ($row['visible'] == "N" and $g['options']['photo_approval'] == "Y") ? "(pending audit)" : "");

				if($i < 3) {
                    $index = '';
                } else {
					$index = 2;
                }

                #var_dump($row);

                if($row['default'] == 'N') {
                    $html->parse("set_default$index", false);
                    $html->setblockvar("default$index", '');
                } else {
                    $html->parse("default$index", false);
                    $html->setblockvar("set_default$index", '');
                }

                if($row['private'] == 'N') {
                    $html->parse("set_private$index", false);
                    $html->setblockvar("set_public$index", '');
                } else {
                    $html->parse("set_public$index", false);
                    $html->setblockvar("set_private$index", '');
                }

                $html->parse("photo_item$index", true);


			}
			else if(!$add_photo_exists)
			{
				$html->setvar("photo_id", "");
				$html->setvar("user_id", "");
				$html->setvar("photo_name", "");
				$html->setvar("description", "");

				$html->setvar("visible", "");

				$html->setvar("orientation", $g_user['orientation']);

				if($i < 3)
					$html->parse("addphoto_item", true);
				else
					$html->parse("addphoto_item2", true);

				$add_photo_exists = false;
			}
			else
			{
				$html->setvar("photo_id", "");
				$html->setvar("user_id", "");
				$html->setvar("photo_name", "");
				$html->setvar("description", "");

				$html->setvar("visible", "");

				$html->setvar("orientation", $g_user['orientation']);

				if($i < 3)
					$html->parse("nophoto_item", true);
				else
					$html->parse("nophoto_item2", true);
			}

			$html->parse("photo", false);
		}
*/
		//$html->parse("photo_edit", true);
        Common::parseErrorAccessingUser($html);

		parent::parseBlock($html);
	}
}

g_user_full();
$isAjaxRequest = get_param('ajax', 0);
$tmpl = $g['tmpl']['dir_tmpl_mobile'] . "profile_photo.html";
if ($isAjaxRequest) {
    $tmpl = null;
}
$page = new CPhoto("", $tmpl);
$header = new CHeader("header", $g['tmpl']['dir_tmpl_mobile'] . "_header.html");
$page->add($header);
$footer = new CFooter("footer", $g['tmpl']['dir_tmpl_mobile'] . "_footer.html");
$page->add($footer);

if (Common::isParseModule('profile_menu')) {
    include("./_include/current/profile_menu.php");
    $profile_menu = new CProfileMenu("profile_menu", $g['tmpl']['dir_tmpl_mobile'] . "_profile_menu.html");
    $profile_menu->setActive('photos');
    $page->add($profile_menu);
}
if (Common::isParseModule('user_menu')) {
    $user_menu = new CUserMenu("user_menu", $g['tmpl']['dir_tmpl_mobile'] . "_user_menu.html");
    if (Common::getOption('set', 'template_options') == 'urban') {
        $header->add($user_menu);
    } else {
        $user_menu->setActive('photos');
        $page->add($user_menu);
    }
}
include("./_include/core/main_close.php");