<?php
class CProfilePhoto extends CHtmlBlock
{
    static $privatePhoto = array();
    static $publicPhoto = array();
    static $allPhoto = array();
    static $allPhotoInfo = array();
    static $allPhotoCount = 0;
    static $stopPreloadPhoto = false;
    static $preloadPhotoLimit = array();
	static $displayPhotoInfo = array();
    static $photoId = array();

    static $privateVideo = array();
    static $publicVideo = array();
    static $allVideo = array();
    static $allVideoInfo = array();
    static $displayVideoInfo = array();
    static $videoId = array();
    static $videoAddView = false;

    static $tablePhoto = 'photo';
    static $tablePhotoComments = 'photo_comments';
    static $tableVideo = 'vids_video';
    static $tableVideoComments = 'vids_comment';

    static $isGetDataWithFilter = false;

    static $sizes = array(
        'bm', 'b', 'm', 'mm', 's', 'r', 'src',
    );

    static $sizesPreviews = array(
        //'b' => 'big',
        'm' => 'medium',
        'mm' => 'medium_mobile',
        's' => 'small',
        'r' => 'root',
    );

    static $saveSizeInDatabase = 'b';

    static $sizeMaximumParams = array(
        'size' => 'bm',
        'config' => 'big_mobile',
    );

    static $sizesBasePhoto = array(
        'bm' => 'big_mobile',
        'b' => 'big',
    );

    static $addRatingToInfo = false;

    function action()
    {
        $cmd = get_param('cmd');
    }

    static function photoUpload($dir = '', $inputFile = '', $isCity = false)
    {
        global $g, $g_user;

        $guid = get_param('e_user_id') ? get_param('e_user_id') : $g_user['user_id'];


        $type = get_param('type', 'public');
        $isPrivate = ($type == 'private') ? true : false;
        $pending = get_param('pending', 'P');

        $id = uploadphoto($guid, '', '', $pending, $dir, false, $inputFile, $isPrivate, true, $isCity);

        CStatsTools::count('photos_uploaded');
        $response = array();
		$photo = array();

		$photo['photo_id'] = $id;
		$photo['user_id'] = $guid;
		$photo['private'] = ($type == 'private') ? 'Y' : 'N';
		$photo['version'] = 0;
		$photo['visible'] = $pending;

        $size = get_param('size', 'r');
		$file = User::getPhotoFile($photo, $size, '', DB_MAX_INDEX, $isCity && City::isVisitorUser());
        return array('id' => $id, 'src_r' => $file);
    }

    static function photoRotate($c_user_id = 0)
    {
        set_time_limit(3600);
        global $g, $g_user;

        if($c_user_id)
            $uid = $c_user_id;
        else
            $uid = guid();

        if (!$uid && get_session('admin_auth') != 'Y' && $g_user['moderator_photo']!=1) {
            return false;
        }

        if(get_session('admin_auth') == 'Y' || $g_user['moderator_photo']==1){
            $uid=get_param('user_id',$uid);
        }

        $responseData = false;
        $pid = get_param('photo_id');
        $angle = get_param('angle');

        if (!$pid) {
            return false;
        }
        $photo = array('photo_id' => $pid,
                       'user_id' => $uid,
                       'visible' => 'Y',
            );
        $fileSrc = $g['path']['dir_files'] . User::photoFileCheck($photo, 'src', '', false);

        Common::saveFileSize($fileSrc, false);

        $isRotatedSrcFileSaved = true;

        $im = new Image();
        if ($im->loadImage($fileSrc)) {
            $im->transformation = false;
            $im->image = imagerotate($im->image, -$angle, 0);
            if(!$im->saveImage($fileSrc, $g['image']['quality'])) {
                $isRotatedSrcFileSaved = false;
            }
        }

        $responseData = false;

        if ($isRotatedSrcFileSaved && $im->image) {
            //Обьеденить с uploadphoto()
            $sFile_ = $g['path']['dir_files'] . "photo/" . $uid . "_" . $pid . "_";

            self::subtractPhotoFileSizes($sFile_);

            //$im->saveImageResourceCopy = true;
            //$im->resizeWH($g['image']['big_x'], $g['image']['big_y'], false, $g['image']['logo'], $g['image']['logo_size']);
            //if($im->saveImage($sFile_ . "b.jpg", $g['image']['quality'])) {
            if(self::createBasePhotoFile($im, $sFile_, $fileSrc)) {

                $sql = 'UPDATE `photo`
                    SET `version` = `version` + 1,
                        `width` = ' . to_sql($im->width) . ',
                        `height` = ' . to_sql($im->height) . '
                    WHERE photo_id = ' . to_sql($pid) . ' AND user_id = ' . to_sql($uid);
                DB::execute($sql);

                self::createPhotoSizesPreviews($sFile_, $im->imageCopy, $pid);

                self::addPhotoFileSizes($sFile_);

                $responseData = true;
            }
        }

        return $responseData;
    }

    static function updateFromString($param = 'image')
    {
        global $g;

        $uid = guid();
        $pid = get_param('photo_id');
        $imageString = str_replace(' ', '+', get_param($param));

        if($imageString) {
            $imageString = base64_decode($imageString);
        }

        if($imageString && $uid && $pid) {

            $photo = array(
                'photo_id' => $pid,
                'user_id' => $uid,
                'visible' => 'Y',
            );

            $fileSrc = $g['path']['dir_files'] . User::photoFileCheck($photo, 'src', '', false);

            Common::saveFileSize($fileSrc, false);

            if(file_put_contents($fileSrc, $imageString) === strlen($imageString)) {

                $im = new Image();
                if ($im->loadImage($fileSrc)) {
                    if(!$im->saveImage($fileSrc, $g['image']['quality'])) {
                        return false;
                    }
                }

                if ($im->image) {
                    $sFile_ = $g['path']['dir_files'] . 'photo/' . $uid . '_' . $pid . '_';

                    self::subtractPhotoFileSizes($sFile_);

                    if(self::createBasePhotoFile($im, $sFile_, $fileSrc)) {

                        $sql = 'UPDATE `photo`
                            SET `version` = `version` + 1,
                                `width` = ' . to_sql($im->width) . ',
                                `height` = ' . to_sql($im->height) . '
                            WHERE photo_id = ' . to_sql($pid) . ' AND user_id = ' . to_sql($uid);
                        DB::execute($sql);

                        self::createPhotoSizesPreviews($sFile_, $im->imageCopy, $pid);

                        self::addPhotoFileSizes($sFile_);
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }

                return true;
            }

        }

        return false;
    }

    static function validate($name, $set = 'urban')
    {
        $responseData = array();
        if (isset($_FILES[$name]) && is_uploaded_file($_FILES[$name]['tmp_name'])) {
            $errors = array(
                1 => 'upload_max_filesize_php',
                2 => 'max_file_size_html',
                3 => 'file_uploaded_partially',
                4 => 'no_file_uploaded',
                6 => 'temporary_folder',
                7 => 'failed_write_file_disk',
                8 => 'php_stopped_upload',
            );
            $maxFileSize = Common::getOption('photo_size');
            $error = $_FILES[$name]['error'];
            if ($error) {
                $responseData = array('error' => l($errors[$error]));
            } else if ($_FILES[$name]['size'] > mb_to_bytes($maxFileSize)){
                $responseData = array('error' => lSetVars('max_file_size', array('size'=>$maxFileSize)));
            } elseif (!Image::isValid($_FILES[$name]['tmp_name'])) {
                $responseData = array('error' => l('accept_file_types'));
            } elseif ($set != '') {
                $info = getimagesize($_FILES[$name]['tmp_name']);
                $minW = intval(Common::getOption("min_photo_width_{$set}", 'image'));
                $minH = intval(Common::getOption("min_photo_height_{$set}", 'image'));
                if ($info[0] < $minW || $info[1] < $minH) {
                    $vars = array('width' => $minW,
                                  'height' => $minH);
                    $responseData = array('error' => lSetVars('photo_file_upload_small_width_height', $vars));
                }
            }
        } else {
            $responseData = array('error' => l('photo_file_upload_failed'));
        }

        return $responseData;
    }

    static function getPhotoListMobile($uid, $order = '`photo_id` DESC', $whereSql = '', $limit = '', $groupId = 0)
    {
        global $g_user;

        $photosInfo = self::preparePhotoList($uid, $order, $whereSql, $limit, false, false, false, $groupId);

        $is = false;
        $privatePhoto = self::$privatePhoto;
        $photosNumber = count($photosInfo);
        $numberPrivate = count($privatePhoto);

        $seePrivatePhotoId = 0;
        $photoCurId = get_param('photo_cur_id',0);
        $isFriend = User::isFriendForPhoto($g_user['user_id'], $uid);
        if ($uid != $g_user['user_id']
            && !$isFriend
            && !empty($privatePhoto)) {
            arsort($privatePhoto);
            foreach ($privatePhoto as $id => $item) {
                if ($is) {
                    $photo = $photosInfo[$id];
                    $prev_id = $photo['prev_id'];
                    $prev = $photo['prev'];
                    $next_id = $photo['next_id'];
                    $next = $photo['next'];
                    $photosInfo[$prev_id]['next_id'] = $next_id;
                    $photosInfo[$prev_id]['next'] = $next;
                    $photosInfo[$next_id]['prev_id'] = $prev_id;
                    $photosInfo[$next_id]['prev'] = $prev;

                    $photosInfo[$prev_id]['next_title'] = $photo['next_title'];
                    $photosInfo[$next_id]['prev_title'] = $photo['prev_title'];

                    unset($photosInfo[$id]);
                } elseif ($photoCurId && isset($privatePhoto[$photoCurId])) {
                    $seePrivatePhotoId = $id;
                }
                $is = true;
            }
            $photosNumber = $photosNumber - $numberPrivate + 1;
            $i = $photosNumber;
            foreach ($photosInfo as $id => $item) {
                $photosInfo[$id]['offset'] = --$i;
            }
            if ($seePrivatePhotoId) {
                $photoCurId = $seePrivatePhotoId;
            }
        }

        if(!$photoCurId || !isset($photosInfo[$photoCurId])) {
            $photoCurId = key($photosInfo);
        }
        return array('list_photos' => $photosInfo, 'private' => $numberPrivate, 'number_photos' => $photosNumber, 'cur_photo' => $photoCurId);
    }

    static function preparePhotoList($uid, $order = '`private` DESC, `default` ASC, `photo_id` DESC', $whereSql = '', $limit = '', $isVisible = false, $isCity = false, $onlyPublic = false, $groupId = 0, $showAllMyPhoto = false)
    {
        global $g, $g_user;

        Groups::setTypeContentList();

        self::$privatePhoto = array();
        self::$publicPhoto = array();
        self::$allPhoto = array();
        self::$allPhotoInfo = array();

        $result = array();

        if ($uid === null) {
            $uid = $g_user['user_id'];
        }

        $display = get_param('display');
        $isCityVisitor = City::isVisitorUser();
        $optionTemplateName = Common::getTmplName();
        $isEdge = $optionTemplateName == 'edge' && !$isCityVisitor;
        $isEdgeGetData = get_param_int('get_data_edge');
        $guid = guid();

        if ($isEdge) {
            $whereTags = '';

            if (self::$isGetDataWithFilter) {
                $whereTags = self::getWhereTags('PTR.');
                if ($whereTags == 'no_tags') {
                    return $result;
                }
            }

            if ($order === null) {
                $order = 'PH.`date` DESC, PH.`photo_id` DESC';//'PH.private DESC, PH.default ASC, PH.photo_id DESC';
            }
            //$orderOffset = '`private` DESC, `default` ASC, `photo_id` DESC';
            $orderOffset = str_replace('PH.', '', $order);

            $where = self::getWherePhotosList('PH.', $onlyPublic, $uid, $groupId, $showAllMyPhoto);
            if ($whereSql) {
                $where .= $whereSql;
            }

            if (!$guid) {
                $where .= ($where ? " AND " : " ") . " U.set_who_view_profile != 'members'";
            }

            if ($limit) {
                $limit = ' LIMIT ' . $limit;
            }
            if ($order) {
                $order = ' ORDER BY ' . $order;
            }
            if ($whereTags) {
                $sql = 'SELECT PH.*, PH.count_comments AS comments_count, U.name, U.name_seo, U.country, U.city, U.gender
                          FROM `photo_tags_relations` AS PTR
                          JOIN `photo` AS PH  ON PH.photo_id = PTR.photo_id
                          JOIN `user` AS U ON U.user_id = PH.user_id
                         WHERE ' . $where
                                 . $whereTags
                                 . ' GROUP BY PH.photo_id '
                                 . $order;
                $sqlCount = 'SELECT COUNT(PH.photo_id)
                               FROM `photo_tags_relations` AS PTR
                               JOIN `photo` AS PH  ON PH.photo_id = PTR.photo_id
                              WHERE ' . $where
                                      . ' GROUP BY PH.photo_id '
                                      . $whereTags;

            } else {
                $sql = 'SELECT PH.*, PH.count_comments AS comments_count, U.name, U.name_seo, U.country, U.city, U.gender
                          FROM `photo` AS PH
                          JOIN `user` AS U ON U.user_id = PH.user_id
                         WHERE ' . $where . $order;

                $sqlCount = 'SELECT COUNT(PH.photo_id) FROM `photo` AS PH WHERE ' . $where;

                /*if(strpos($whereSql,'wall_id')!==false){
                    echo $sql;
                    echo '<br>';
                }*/
            }


            if ($isEdgeGetData) {
                self::$allPhotoCount = intval(DB::result($sqlCount, 0, DB_MAX_INDEX));
                $countPhoto = self::$allPhotoCount;

                $limitPreloadData = Common::getOptionTemplateInt('gallery_preload_data');
                $checkStopPreloadPhoto = false;

                $pagePreloadDirect = get_param('page_preload_direct');
                if ($pagePreloadDirect) {
                    $limitNextSql = '';
                    $limitPrevSql = '';
                    $pagePreloadLimit = get_param('page_preload_limit');
                    self::$preloadPhotoLimit = $pagePreloadLimit;
                    //left - next
                    //right - prev
                    $prev0 = 0;
                    if (isset($pagePreloadLimit['prev'][0])) {
                        $prev0 = $pagePreloadLimit['prev'][0];
                    }
                    $prev1 = 1;
                    if (isset($pagePreloadLimit['prev'][1])) {
                        $prev1 = $pagePreloadLimit['prev'][1];
                    }
                    $next0 = 0;
                    if (isset($pagePreloadLimit['next'][0])) {
                        $next0 = $pagePreloadLimit['next'][0];
                    }
                    $next1 = 0;
                    if (isset($pagePreloadLimit['next'][1])) {
                        $next1 = $pagePreloadLimit['next'][1];
                    }

                    if ($pagePreloadDirect == 'left') {
                        $nextL = $next0 + $next1;
                        $endL = $limitPreloadData;
                        $nextLd = $nextL + $limitPreloadData;
                        if ($nextLd >= $countPhoto) {
                            $endLa = abs($nextLd - $countPhoto);
                            $endL = $limitPreloadData - $endLa;
                            $prev = 0;
                            if ($prev0 && $prev0 < $endLa) {
                                $endLa = abs($endLa - $prev0);
                            }
                            $limitPrevSql = ' LIMIT ' . $prev . ', ' . $endLa;
                            self::$preloadPhotoLimit['prev'][0] = $prev1;
                            self::$preloadPhotoLimit['prev'][1] = $endLa;
                        } elseif ($prev0 && $nextLd >= $prev0) {
                            $endL = $limitPreloadData - ($nextLd - $prev0);
                            //echo $endL;
                        }

                        $limitNextSql = ' LIMIT ' . $nextL . ', ' . $endL;
                        self::$preloadPhotoLimit['next'][1] += $endL;
                    } else {
                        if (!$prev0 && !$prev1) {
                            $prevL = $next0 - $limitPreloadData;
                            $endL = $limitPreloadData;
                            if ($prevL < 0) {
                                $prevLa = abs($prevL);
                                $endL = $limitPreloadData - $prevLa;
                                $prevL = 0;
                                $prevL1 = $countPhoto - $prevLa + 1;
                                $endL1 = $prevLa - 1;
                                $limitPrevSql = ' LIMIT ' . $prevL1 . ', ' . $endL1;
                                self::$preloadPhotoLimit['prev'][0] = $prevL1;
                                self::$preloadPhotoLimit['prev'][1] = $endL1;
                            }
                            self::$preloadPhotoLimit['next'][0] = $prevL;
                            self::$preloadPhotoLimit['next'][1] += $endL;
                            $limitNextSql = ' LIMIT ' . to_sql($prevL, 'Plain') . ', ' . $endL;
                        } else {
                            $prevL = $prev0 - $limitPreloadData;
                            $endL = $limitPreloadData;
                            if ($prevL < 0) {
                                $prevL = 0;
                                $endL = $limitPreloadData - $prevL;
                            }
                            if ($next1 > $prevL) {
                                $prevL = $next1;
                            }
                            $limitPrevSql = ' LIMIT ' . to_sql($prevL, 'Plain') . ', ' . $endL;
                            self::$preloadPhotoLimit['prev'][0] = $prevL;
                        }

                    }
                    if ($limitPrevSql && $limitNextSql) {
                        if ($pagePreloadDirect == 'left') {
                            $sql = '(' . $sql . $limitNextSql . ') UNION (' . $sql . $limitPrevSql . ')';// ORDER BY ' . $orderOffset;
                        } else {
                            $sql = '(' . $sql . $limitPrevSql . ') UNION (' . $sql . $limitNextSql . ')';// ORDER BY ' . $orderOffset;
                        }

                    } else {
                        if ($limitPrevSql) {
                            $sql .= $limitPrevSql;
                        } else {
                            $sql .= $limitNextSql;
                        }
                    }

                    //var_dump_pre(self::$preloadPhotoLimit);
                    //echo $sql;
                    //var_dump_pre($pagePreloadDirect);
                    //var_dump_pre($pagePreloadLimit);
                } else {
                    $checkStopPreloadPhoto = true;

                    $d = round($limitPreloadData/2);
                    $limitPreloadData2 = $limitPreloadData*2;
                    if (($limitPreloadData + $d) >= $countPhoto) {
                        //var_dump_pre(0);
                        //$sql .= $limit;
                        self::$stopPreloadPhoto = true;
                    } else {
                        $offsetMedia = get_param('offset_media');
                        //var_dump_pre($offsetMedia);
                        $pidCur = get_param_int('photo_cur_id');
                        if ($pidCur) {
                            $sqlExistsPhoto = $sqlCount . ' AND PH.photo_id = ' . to_sql($pidCur);
                            if (DB::result($sqlExistsPhoto, 0, DB_MAX_INDEX)) {//Photo delete - does not exist
                                if ($offsetMedia === 'false') {
                                    $orderMedia = '';
                                    if ($orderOffset) {
                                        $orderMedia = ' ORDER BY ' . $orderOffset;
                                    }

                                    $sqlPhotoCurIndex =
                                    "SELECT PHR.`rank_1` FROM(
                                    SELECT @rownum := @rownum + 1 AS rank_1, PHD.photo_id FROM
                                    (
                                        SELECT PHC.*
                                            FROM
                                            (" . $sql . ") PHC,
                                            (SELECT @rownum := 0) R
                                    ) PHD " . $orderMedia . ") AS PHR WHERE PHR.photo_id = " . to_sql($pidCur);
                                    $offsetMedia = DB::result($sqlPhotoCurIndex, 0, DB_MAX_INDEX);
                                    if ($offsetMedia) {
                                        $offsetMedia--;
                                    }
                                }
                            } else {
                                $offsetMedia = 0;
                            }
                        }

                        if ($offsetMedia === 'false') {
                            $offsetMedia = 0;
                        }
                        $offsetMedia = intval($offsetMedia);

                        $limitPrevSql = '';
                        $limitNextSql = $limit;

                        $prevLimit = $offsetMedia - $limitPreloadData;
                        $nextLimit = $offsetMedia + $limitPreloadData + 1;

                        //echo '$offsetMedia';
                        //var_dump_pre($offsetMedia);
                        //echo 'prev';
                        //var_dump_pre($prevLimit);
                        //echo 'next';
                        //var_dump_pre($nextLimit);

                        self::$preloadPhotoLimit = array('prev' => array(0,0), 'next' => array(0,0));
                    if ($prevLimit >= 0 && $countPhoto >= $nextLimit) {
                        //var_dump_pre(1);
                        $limitPrevSql = '';
                        $limitNextSql = ' LIMIT ' . $prevLimit . ', ' . ($limitPreloadData2 + 1);
                        self::$preloadPhotoLimit['next'] = array($prevLimit, $limitPreloadData2 + 1);
                    } elseif ($prevLimit >= 0 && $countPhoto < $nextLimit) {
                        //var_dump_pre(2);
                        $limitPrevSql = ' LIMIT 0, ' . ($nextLimit - $countPhoto);
                        self::$preloadPhotoLimit['next'] = array(0, $nextLimit - $countPhoto);
                        $limitNextSql = ' LIMIT ' . $prevLimit . ', ' . $countPhoto;
                        self::$preloadPhotoLimit['prev'] = array($prevLimit, $countPhoto);
                    } elseif ($prevLimit < 0 && $countPhoto >= $nextLimit) {
                        //var_dump_pre(3);
                        $limitPrevSql = ' LIMIT ' . ($countPhoto - abs($prevLimit)) .', ' . $countPhoto;
                        self::$preloadPhotoLimit['prev'] = array($countPhoto - abs($prevLimit), $countPhoto);
                        $limitNextSql = ' LIMIT 0, ' . $nextLimit;
                        self::$preloadPhotoLimit['next'] = array(0, $nextLimit);
                    } else {
                        //var_dump_pre(4);
                    }
                    /* elseif ($prevLimit < 0 && $countPhoto < $nextLimit) {
                        var_dump_pre(5);
                        $limitNextSql = $limit;
                    }*/

                    if ($limitPrevSql) {
                        $sql = '(' . $sql . $limitPrevSql . ') UNION (' . $sql . $limitNextSql . ') ORDER BY ' . $orderOffset;
                    } else {
                        $sql .= $limitNextSql;
                    }

                    //echo  $sql;
                    //var_dump_pre($pidCur, true);

                    }
                }
            } else {
                $sql .= $limit;
            }

            $profilePhoto = DB::all($sql, DB_MAX_INDEX);

            if ($isEdgeGetData && $checkStopPreloadPhoto) {
                self::$stopPreloadPhoto = self::$allPhotoCount == count($profilePhoto);
            }
        } else {
            $orderOffset = null;
            $vis = '';
            if ($isVisible || $uid != $g_user['user_id']) {
                $vis = $g['sql']['photo_vis'];
            }

            $isEncountersMobile = Common::isOptionActive('encounters_only_public_photos', 'template_options') && $display == 'encounters';
            if ($isEncountersMobile) {
                $vis .= " AND private = 'N' ";
                User::setNoPhotoPprivateInOffset();
            }
            $where = '`user_id` = ' . to_sql($uid, 'Number') . $vis . " AND visible!='P' AND group_id = 0" . $whereSql;
            $table = 'photo';
            if ($isCityVisitor) {
                $table = City::getTable('city_photo');
            }
            $profilePhoto = DB::select($table, $where, $order, $limit);
        }

        $nextStepRated = intval(Common::getOption('rate_see_my_photo_rating'));
        $isDefaultPhoto = false;
        if (!empty($profilePhoto)) {
            if (!$isEdge) {
                if ($isCityVisitor) {
                    $isFriend = 0;
                    $gender = $g_user['gender'];
                    $isUserReport = 0;
                } else {
                    $isFriend = User::isFriend($uid, $g_user['user_id']);
                    $gender = User::getInfoBasic($uid, 'gender');
                    $isUserReport = User::isReportUser($uid);
                }
            }

            $numPhoto = count($profilePhoto);

            $photoIds = array();

            foreach ($profilePhoto as $item) {
                $uid = $item['user_id'];
                if ($isEdge) {
                    $isFriend = User::isFriendForPhoto($uid, $g_user['user_id']);
                    $gender = $item['gender'];
                    $isUserReport = User::isReportUser($uid);
                }
                $pid = $item['photo_id'];
                if ($item['private'] == 'Y') {
                    self::$privatePhoto[$pid] = array('private'=> 'Y', 'description'=> $item['description'], 'version' => $item['version'], 'visible' => $item['visible']);
                } else {
                    self::$publicPhoto[$pid] =  array('private'=> 'N', 'description'=> $item['description'], 'version' => $item['version'], 'visible' => $item['visible']);
                }
                if ($item['default'] == 'Y') {
                    $isDefaultPhoto = true;
                }

                $item['visible'] = self::isPhotoOnVerification($item['visible']) ? $item['visible'] : 'Y';

                self::$allPhotoInfo[$pid]['photo_id'] = $pid;
                self::$allPhotoInfo[$pid]['user_id'] = $uid;

                if (isset($item['name'])) {
                    $userData = User::getDataUserOrGroup($item['user_id'], $item['group_id']);
                    self::$allPhotoInfo[$pid]['user_name'] = $userData['name'];
                    self::$allPhotoInfo[$pid]['user_name_short'] = $userData['name_short'];
                    self::$allPhotoInfo[$pid]['user_url'] = $userData['url'];
                    self::$allPhotoInfo[$pid]['user_photo_r'] = $userData['photo'];

                    $userData = User::getDataUserOrGroup($guid, $item['group_id']);
                    self::$allPhotoInfo[$pid]['responding_user'] = $guid . '_' . $item['group_id'];
                    self::$allPhotoInfo[$pid]['responding_user_name'] = $userData['name'];
                    self::$allPhotoInfo[$pid]['responding_user_name_short'] = $userData['name_short'];
                    self::$allPhotoInfo[$pid]['responding_user_url'] = $userData['url'];
                    self::$allPhotoInfo[$pid]['responding_user_photo_r'] = $userData['photo'];
                }

                if (isset($item['comments_count'])) {
                    self::$allPhotoInfo[$pid]['comments_count'] = $item['comments_count'];
                }

                if (Common::isOptionActiveTemplate('gallery_tags')) {
                    $tags = self::getTagsPhoto($pid);
                    self::$allPhotoInfo[$pid]['tags'] = $tags;

                    $tagsData = self::getTagsMedia($tags, $item['group_id'], $item['group_page']);
                    self::$allPhotoInfo[$pid]['tags_title'] = $tagsData['title'];
                    self::$allPhotoInfo[$pid]['tags_html'] = $tagsData['html'];
                }

                self::$allPhotoInfo[$pid]['description'] = $item['description'];
                self::$allPhotoInfo[$pid]['private'] = $item['private'];
                self::$allPhotoInfo[$pid]['is_friend'] = intval($isFriend);
                self::$allPhotoInfo[$pid]['default'] = $item['default'];
                self::$allPhotoInfo[$pid]['default_group'] = $item['default_group'];
                self::$allPhotoInfo[$pid]['group_id'] = $item['group_id'];
				self::$allPhotoInfo[$pid]['visible'] = $item['visible'];
                self::$allPhotoInfo[$pid]['reports'] = $item['users_reports'];
                self::$allPhotoInfo[$pid]['version'] = $item['version'];

                self::$allPhotoInfo[$pid]['report_user'] = $isUserReport;
                self::$allPhotoInfo[$pid]['src_b'] = User::getPhotoFile($item, 'b', $gender);

                if (Common::getOption('profile_photo_main_size', 'template_options') == 'bm') {
                    self::$allPhotoInfo[$pid]['src_bm'] = User::getPhotoFile($item, 'bm', $gender);
                }

				self::$allPhotoInfo[$pid]['src_m'] = User::getPhotoFile($item, 'm', $gender);
                self::$allPhotoInfo[$pid]['src_r'] = User::getPhotoFile($item, 'r', $gender);
				self::$allPhotoInfo[$pid]['src_s'] = User::getPhotoFile($item, 's', $gender);

                /* Edge */
                $isShortTimeAgo = Common::isOptionActiveTemplate('photo_time_ago_short');
                self::$allPhotoInfo[$pid]['time_ago'] = timeAgo($item['date'], 'now', 'string', 60, 'second', $isShortTimeAgo);

                self::$allPhotoInfo[$pid]['date'] = Common::dateFormat($item['date'], 'photo_date');

                self::$allPhotoInfo[$pid]['like'] = $item['like'];
                self::$allPhotoInfo[$pid]['dislike'] = $item['dislike'];

                self::$allPhotoInfo[$pid]['my_like'] = '';
                $like = DB::one('photo_likes', 'photo_id = ' . to_sql($pid) . ' AND `user_id` = ' . to_sql($guid));
                if ($like) {
                    self::$allPhotoInfo[$pid]['my_like'] = $like['like'] ? 'Y' : 'N';
                }
                /* Edge */

                /* Rating */
                self::$allPhotoInfo[$pid]['votes'] = $item['votes'];
                self::$allPhotoInfo[$pid]['rating'] = $item['rating'];
                self::$allPhotoInfo[$pid]['average'] = $item['average'];

                self::$allPhotoInfo[$pid]['my_rating'] = 0;
                self::$allPhotoInfo[$pid]['visible_rating'] = 0;
                if (!$isCity) {
                    if ($uid != $g_user['user_id']) {
                        $myRating = 0;
                        if($g_user['user_id'] && self::$addRatingToInfo) {
                            $where = '`photo_id` = ' . to_sql($pid, 'Number') .
                                 ' AND `user_id` = ' . to_sql($g_user['user_id'], 'Number');
                            $userRatePhoto = DB::field('photo_rate', 'rating' ,$where);
                            if (!empty($userRatePhoto) && isset($userRatePhoto[0])){
                                $myRating = $userRatePhoto[0];
                            }
                        }
                        self::$allPhotoInfo[$pid]['my_rating'] = $myRating;
                    } else {
                        if (!$nextStepRated || $item['photo_id'] <= $g_user['last_photo_visible_rated']) {
                            self::$allPhotoInfo[$pid]['visible_rating'] = 1;
                        }
                    }
                }
                /* Rating */
                if(User::getNoPhotoPprivateInOffset() && $numPhoto > 1) {
                    $offsetInfo = User::paramsPhotoOffset($uid, '-1', $pid, $numPhoto, $orderOffset);
                    foreach ($offsetInfo as $key => $value) {
                        self::$allPhotoInfo[$pid][$key] = $value;
                    }
                } else {
                    $photoIds[] = $pid;
                }

                if($numPhoto == 1) {
                    self::$allPhotoInfo[$pid]['offset'] = 0;
                    self::$allPhotoInfo[$pid]['next'] = 0;
                    self::$allPhotoInfo[$pid]['prev'] = 0;
                    self::$allPhotoInfo[$pid]['next_id'] = $pid;
                    self::$allPhotoInfo[$pid]['prev_id'] = $pid;

                    $private = self::$allPhotoInfo[$pid]['private'];
                    if($private == 'Y' && !$isFriend && $uid != $g_user['user_id']) {
                        $title = '';
                    } else {
                        $title = self::$allPhotoInfo[$pid]['description'];
                    }
                    self::$allPhotoInfo[$pid]['prev_title'] = self::$allPhotoInfo[$pid]['next_title'] = $title;
                }

                self::$allPhotoInfo[$pid]['is_video'] = 0;
                self::$allPhotoInfo[$pid]['hide_header'] = $item['hide_header']*1;
            }

            if(!User::getNoPhotoPprivateInOffset()) {
                if($numPhoto > 1) {
                    if (!$isEdge) {
                        sort($photoIds);
                    }
                    foreach($photoIds as $photoIdkey => $pid) {

                        if ($photoIdkey == 0) {
                            $next = $photoIdkey + 1;
                            $prev = $numPhoto - 1;
                        } elseif ($photoIdkey == $numPhoto - 1) {
                            $next = 0;
                            $prev = $photoIdkey - 1;
                        } else {
                            $next = $photoIdkey + 1;
                            $prev = $photoIdkey - 1;
                        }

                        self::$allPhotoInfo[$pid]['offset'] = $photoIdkey;
                        self::$allPhotoInfo[$pid]['next'] = $next;
                        self::$allPhotoInfo[$pid]['prev'] = $prev;
                        self::$allPhotoInfo[$pid]['next_id'] = $photoIds[$next];
                        self::$allPhotoInfo[$pid]['prev_id'] = $photoIds[$prev];

                        $private = self::$allPhotoInfo[$photoIds[$prev]]['private'];
                        if($private == 'Y' && !$isFriend && $uid != $g_user['user_id']) {
                            $title = '';
                        } else {
                            $title = self::$allPhotoInfo[$photoIds[$prev]]['description'];
                        }

                        self::$allPhotoInfo[$pid]['prev_title'] = $title;


                        $private = self::$allPhotoInfo[$photoIds[$next]]['private'];
                        if($private == 'Y' && !$isFriend && $uid != $g_user['user_id']) {
                            $title = '';
                        } else {
                            $title = self::$allPhotoInfo[$photoIds[$next]]['description'];
                        }

                        self::$allPhotoInfo[$pid]['next_title'] = $title;

                        /*
                        foreach ($offsetInfo as $key => $value) {

                            self::$allPhotoInfo[$pid][$key] = $value;

                            $valueCorrect = self::$allPhotoInfo[$pid][$key];
                            if($valueCorrect != $value) {
                                echo "INCORRECT $pid : $key > $value >>> $valueCorrect<br>";
                            }

                        }
                        */

                    }
                }

            }

            //sets the default picture for your profile only on the profile page
            if (!$isDefaultPhoto && $uid == guid() && $limit == ''
                && ($display == 'profile' || get_param('get_photo_info'))) {
                $pidDefault = User::getPhotoDefault($uid, 'r', true);
                if (isset(self::$allPhotoInfo[$pidDefault])) {
                    User::photoToDefault($pidDefault);
                    self::$allPhotoInfo[$pidDefault]['default'] = 'Y';
                }
            }
            self::$allPhoto = self::$publicPhoto + self::$privatePhoto;

            $result = self::$allPhotoInfo;
        }
        //if ($isEncountersMobile) {
        //    User::setNoPhotoPprivateInOffset(false);
        //}
        User::setNoPhotoPprivateInOffset(false);

        return $result;
    }

    static function prepareMobilePhotoList($uid = null)//Impact mobile
    {
        global $g_user;

        $response = array();
        if (!$g_user['user_id']) {
            return $response;
        }
        if ($uid === null) {
            $uid = get_param('uid');
        }
        if (!$uid) {
            return $response;
        }
        $photosInfo = self::preparePhotoList($uid, '`photo_id` DESC');
        $is = false;
        $privatePhoto = self::$privatePhoto;
        $photosNumber = count($photosInfo);
        $numberPrivate = count($privatePhoto);

        if ($uid != $g_user['user_id']
            && !User::isFriend($g_user['user_id'], $row['user_id'])
            && !empty($privatePhoto)) {
                arsort($privatePhoto);
            foreach ($privatePhoto as $id => $item) {
                if ($is) {
                    $photo = $photosInfo[$id];
                    $prev_id = $photo['prev_id'];
                    $prev = $photo['prev'];
                    $next_id = $photo['next_id'];
                    $next = $photo['next'];
                    $photosInfo[$prev_id]['next_id'] = $next_id;
                    $photosInfo[$prev_id]['next'] = $next;
                    $photosInfo[$next_id]['prev_id'] = $prev_id;
                    $photosInfo[$next_id]['prev'] = $prev;
                    unset($photosInfo[$id]);
                }
                $is = true;
            }
            $photosNumber = $photosNumber - $numberPrivate + 1;
            $i = $photosNumber;
            foreach ($photosInfo as $id => $item) {
                $photosInfo[$id]['offset'] = --$i;
            }
        }

        $response = array('list' => $photosInfo,
                          'number' => $photosNumber,
                          'number_private' => $numberPrivate);
        return $response;
    }

    static function clearPhotoList()
    {
        self::$privatePhoto = array();
        self::$publicPhoto = array();
        self::$allPhoto = array();
        self::$allPhotoInfo = array();
    }

    static function getPhotoInfo($uid)
    {
		if (empty(self::$allPhotoInfo)) {
            self::preparePhotoList($uid);
        }
		return self::$allPhotoInfo;
	}

    static function getVideoInfo($uid)
    {
		if (empty(self::$allVideoInfo)) {
            self::prepareVideoList($uid);
        }
		return self::$allVideoInfo;
	}

	static function countPhoto($uid)
    {
		if (empty(self::$allPhotoInfo)) {
            self::preparePhotoList($uid);
        }
		return count(self::$allPhotoInfo);
	}

	static function getIdFirstPhotoNoRandom($uid)
    {
		if (empty(self::$allPhotoInfo)) {
            self::preparePhotoList($uid);
        }
		$allPhoto = self::$allPhotoInfo;
		$photoId = 0;
		if (!empty($allPhoto)) {
			foreach (self::$displayPhotoInfo as $pid => $value) {
				unset($allPhoto[$pid]);
			}
			$photo = array_shift($allPhoto);
			$photoId = $photo['photo_id'];
		}

		return $photoId;
	}

    static function parsePhotoRand(&$html, $uid = null, $forciblyPrepare = false, $gender = false)
    {
        global $g;
        global $g_user;

        if ($uid === null) {
            $uid = guid();
        }
        /*if (empty(self::$privatePhoto) || empty(self::$publicPhoto)
            || empty(self::$allPhoto) || $forciblyPrepare) {
            self::preparePhotoList($uid);
        }

        $privatePhoto = self::$privatePhoto;
        $publicPhoto = self::$publicPhoto;
        $allPhoto = self::$allPhoto;*/


        if (empty(self::$publicPhoto) || $forciblyPrepare) {
            self::preparePhotoList($uid);
        }
        $publicPhoto = self::$publicPhoto;

        $numDisplay = 3;
        $photoDefault = User::getPhotoDefault($uid, '', true, $gender);

        $displayPhoto = array();
        // If one then immediately select all photos
        /*if (guid() == $uid
            || User::isFriend($g_user['user_id'], $uid)) {
            if (!empty($allPhoto)) {
                unset($allPhoto[$photoDefault]);
                $numPhoto = count($allPhoto);
                if ($numPhoto <= $numDisplay) {
                    $displayPhoto = $allPhoto;
                } else {
                    shuffle_assoc($allPhoto);
                    $randPhoto = array_rand($allPhoto, $numDisplay);
                    foreach ($randPhoto as $key => $photo_id) {
                        $displayPhoto[$photo_id] = 'N';
                    }
                }
           }
           if ($g_user['user_id'] == $uid) {
               $html->parse('photo_add');
           }
        } else {
            $numPhoto = count($publicPhoto);
            if (!empty($publicPhoto)) {
                unset($publicPhoto[$photoDefault]);
                if ($numPhoto <= $numDisplay) {
                    $displayPhoto = $publicPhoto;
                } else {
                    shuffle_assoc($publicPhoto);
                    $randPhoto = array_rand($publicPhoto, $numDisplay);
                    foreach ($randPhoto as $key => $photo_id) {
                        $displayPhoto[$photo_id] = $publicPhoto[$photo_id];
                    }
                }
            }
            $numPhoto = count($displayPhoto);
            if ($numPhoto < $numDisplay && !empty($privatePhoto)) {
                $numRand = $numDisplay - $numPhoto;
                unset($privatePhoto[$photoDefault]);
                if (count($privatePhoto) <= $numRand) {
                    $displayPhoto += $privatePhoto;
                } else {
                    shuffle_assoc($privatePhoto);
                    $randPhoto = array_rand($privatePhoto, $numRand);
                    if ($numRand == 1) {
                        $displayPhoto[$randPhoto] = $privatePhoto[$randPhoto];
                    } else {
                        foreach ($randPhoto as $key => $photo_id) {
                            $displayPhoto[$photo_id] = $privatePhoto[$photo_id];
                        }
                    }
                }
            }
        }*/

        if ($g_user['user_id'] == $uid) {
            $html->parse('photo_add');
        }

        if (!empty($publicPhoto)) {
            $numPhoto = count($publicPhoto);
            unset($publicPhoto[$photoDefault]);
            if ($numPhoto <= $numDisplay) {
                $displayPhoto = $publicPhoto;
            } else {
                $randPhoto = array_rand($publicPhoto, $numDisplay);
                foreach ($randPhoto as $key => $photo_id) {
                    $displayPhoto[$photo_id] = $publicPhoto[$photo_id];
                }
            }
        }

        $num = 1;
        $sizePhoto = array('m', 's', 'r');
        $isParsePhoto = false;
		self::$displayPhotoInfo = $displayPhoto;
        $blockPhotoRand = 'photo_rand';
        if (!empty($displayPhoto)) {
            foreach ($displayPhoto as $photo_id => $photoItem) {

                $photo['photo_id'] = $photo_id;
                $photo['user_id'] = $uid;
                $photo['private'] = $photoItem['private'];
                $photo['version'] = $photoItem['version'];
                $photo['visible'] = $photoItem['visible'];
                $size = $sizePhoto[$num-1];
                $urlPhoto = User::getPhotoFile($photo, $size, '');
                $photoOffset = User::photoOffset($uid, $photo_id, false);
                $html->setvar('photo_rand_offset', $photoOffset);
                $html->setvar('photo_id', $photo_id);
                $html->setvar('pic_num', $num);
                $html->setvar('photo_rand_title',he($photoItem['description']));
                $html->setvar($blockPhotoRand, $urlPhoto);
				$html->parse('photo_scale', false);
                if (self::isPhotoOnVerification(self::$allPhotoInfo[$photo_id]['visible'])){
                    $html->parse($blockPhotoRand . '_not_checked', false);
                } else {
                    $html->clean($blockPhotoRand . '_not_checked');
                }
                $html->parse($blockPhotoRand, true);
                $num++;
            }
            $isParsePhoto = true;
            //$html->parse('photo_rand_box');
        }

        if (count($displayPhoto) < $numDisplay && $uid == guid()) {
            for ($i = $num; $i <= $numDisplay; $i++) {
                $html->setvar('photo_id', 0);
                $html->setvar('pic_num', $i);
                $html->setvar($blockPhotoRand, "urban_nophoto_{$sizePhoto[$i-1]}.jpg");
				$html->parse('cursor_def', false);
				$html->clean('photo_scale');
                $html->clean($blockPhotoRand . '_not_checked');
                $html->parse($blockPhotoRand, true);
            }
            $isParsePhoto = true;
        }
        if ($isParsePhoto) {
            $html->parse('photo_rand_box');
        }
    }

    static function isPhotoDefaultPublic($uid = null, $checkApprovalPhoto = false, $gender = false)
    {
        $responseData = false;
        if ($uid === null) {
            $uid = guid();
            $gender = guser('gender');
        }
        if ($uid) {
            $numbersPhoto = self::getNumberPhotosUser($uid);
            if (!$numbersPhoto) {
                return false;
            }
            $pid = User::getPhotoDefault($uid, 'r', true, $gender);
            $where = '`photo_id` = ' . to_sql($pid) . ' AND `private` = "N"';
            if ($checkApprovalPhoto) {
                $where .= ' AND `visible` = "Y"';
            }
            $responseData = DB::count('photo', $where);
        }
        return $responseData;
    }

    static function isPhotoOnVerification($visible)
    {
        if ($visible == 'N') {
            return Common::isOptionActive('photo_approval');
        }
        if ($visible == 'Nudity') {
            return (Common::isOptionActive('photo_approval') || Common::isOptionActive('nudity_filter_enabled'));
        }
        return false;
        //return Common::isOptionActive('photo_approval') && $visible == 'N';
    }

    // there is User::getPhotoDefault
    static function getPhotoIdDefault($uid = null)
    {
        global $g;

        if ($uid === null) {
            $uid = guid();
        }
		//$g['sql']['photo_vis']
        $sql = 'SELECT `photo_id`
                  FROM `photo`
                 WHERE `user_id` = ' . to_sql($uid, 'Number') .
                 " AND visible='Y'
                 ORDER BY `default` ASC, `photo_id` ASC";
        return DB::result($sql);
    }

    static function parsePhotoProfile(&$html, $type, $uid = null, $forciblyPrepare = false, $imgSize = 's', $isCity = false, $limit = '', $whereSql = '', $notPhotoId = 0)
    {
        global $g;
        global $g_user;

        if ($uid === null) {
            $uid = guid();
        }

        $typeBlock = "photo_{$type}";
        $block = "{$typeBlock}_block";
        $typeList = "{$type}Photo";
        if (empty(self::$$typeList) || $forciblyPrepare) {
            if ($isCity) {
                $whereSql .= " AND visible='Y' ";
            }
            $photoInfo = self::preparePhotoList($uid, '`private` DESC, `default` ASC, `photo_id` DESC', $whereSql, $limit, false, $isCity);
        }
        $userPhoto = self::$$typeList;
        if (!empty($userPhoto)) {
            $html->setvar("{$typeBlock}_counter", lSetVars('count_photos', array('number' => count($userPhoto))));
            $html->setvar('type', $type);
            $html->setvar('count', count($userPhoto));
            $html->parse('counter', true);
            $html->setvar("uid", $uid);
            foreach ($userPhoto as $photo_id => $private) {
				if ($notPhotoId && $notPhotoId == $photo_id) {
					$html->setvar("gallery_{$typeBlock}_not_photo_info", json_encode(self::$allPhotoInfo[$photo_id]));
					continue;
				}

                $photo['photo_id'] = $photo_id;
                $photo['user_id'] = $uid;
                $photo['private'] = $private['private'];
                $photo['version'] = $private['version'];
                $photo['visible'] = $private['visible'];

                $urlPhoto = User::getPhotoFile($photo, $imgSize, '', DB_MAX_INDEX, City::isVisitorUser());//gender not

                if ($type == 'private'){
                    $isFriend = User::isFriend($g_user['user_id'], $uid);
                    $isFriendRequestExists = User::isFriendRequestExists($uid, $g_user['user_id']);
                    if (!$isFriend && !$isFriendRequestExists && (guid() != $photo['user_id']))
                    {
                        $html->setvar("{$typeBlock}_title", ' ');
                    } else if (($isFriend) or (guid() == $photo['user_id'])) {
                        $html->setvar("{$typeBlock}_title", he($private['description']));
                    }
                } else {
                    $html->setvar("{$typeBlock}_title", he($private['description']));
                }

                $html->setvar("url_{$typeBlock}", $urlPhoto);
                $html->setvar("photo_id", $photo_id);

                /* Gallery */
                //$offset = User::photoOffset($uid, $photo_id);
                $html->setvar("gallery_{$typeBlock}_info", json_encode(self::$allPhotoInfo[$photo_id]));
                /* Gallery */
                /* City */
                if ($isCity) {
                    $cityUId = City::getUidInCity();
                    $where = '`photo_id` = ' . to_sql($photo_id) .
                             ' AND `user_id` = ' . to_sql($cityUId);
                    $photoEditor = DB::select(City::getTable('city_avatar_face'), $where);
                    if ($photoEditor && isset($photoEditor[0])) {
                        $html->setvar("{$typeBlock}_face_params", $photoEditor[0]['params']);
                        $fileFace = "city/users/{$cityUId}_{$photo_id}.jpg";
                        $fileFaceUrl = "{$g['path']['url_files_city']}{$fileFace}?v={$photoEditor[0]['hash']}";
                        $html->setvar("{$typeBlock}_face_url", $fileFaceUrl);
                        $html->setvar("{$typeBlock}_face_color", $photoEditor[0]['head_color']);
                    } else {
                        $html->setvar("{$typeBlock}_face_url", '');
                        $html->setvar("{$typeBlock}_face_color", '');
                        $html->setvar("{$typeBlock}_face_params", '[]');
                    }
                }
                /* City */

                $html->setvar("{$typeBlock}_id", $photo_id);
                if (guid() == $uid) {
                    //$html->setvar("{$typeBlock}_offset", $offset);
                    $html->parse("{$typeBlock}_action", false);
                }
                if (self::isPhotoOnVerification(self::$allPhotoInfo[$photo_id]['visible'])) {
                    $html->parse("{$typeBlock}_not_checked", false);
                } else {
                    $html->clean("{$typeBlock}_not_checked");
                }
                $html->parse("{$typeBlock}", true);
            }
        }

        $isFriend = User::isFriend($g_user['user_id'], $uid);
        $isFriendRequestExists = User::isFriendRequestExists($uid, $g_user['user_id']);
        if ($g_user['user_id'] == $uid) {
			$html->parse("{$typeBlock}_more");
            $html->parse("{$typeBlock}_add");
        } else if ($type == 'private'
                   && !$isFriend
                   && !$isFriendRequestExists) {
            $html->parse('request_access_private_photo');
        } else if ($isFriend) {
            $html->parse("{$typeBlock}_can_view");
        }

		if (!empty($userPhoto) || $g_user['user_id'] == $uid) {
			$html->parse($block);
		}
    }

    static function prepareVideoList($uid, $order = '`private` DESC, `id` DESC')
    {
        global $g, $g_user;

        $result = array();

        if ($uid === null) {
            $uid = $g_user['user_id'];
        }

        $vis = " AND active!='2' ";
        $isVideoApproval = Common::isOptionActive('video_approval');
        if ($isVideoApproval && $uid != $g_user['user_id']) {
            $vis = " AND active='1' ";
        }

        /*$isEncountersMobile = Common::getOption('name', 'template_options') == 'urban_mobile'
                              && get_param('display') == 'encounters';
        if ($isEncountersMobile) {
            User::setNoPhotoPprivateInOffset();
        }*/
        $vis .= " AND private = 0 AND group_id = 0 ";//so far, only public albums
        $where = '`user_id` = ' . to_sql($uid, 'Number') . " AND `is_uploaded` = 1 " . $vis ;
        $profileVideo = DB::select('vids_video', $where, $order, '');

        $photoIds = array();
        $numPhoto = count($profileVideo);

        if (!empty($profileVideo)) {
            include_once(self::includePath() . '_include/current/video_hosts.php');

            $autoPlayDefault = VideoHosts::getAutoplay();
            VideoHosts::setAutoplay(Common::isOptionActive('video_autoplay'));

            $gender = User::getInfoBasic($uid, 'gender');
            foreach ($profileVideo as $item) {
                $pid = 'v_'.$item['id'];
                $pidNum=$item['id'];
                $pidV='v_'.$item['id'];
                if ($item['private'] == 1) {
                    self::$privateVideo[$pid] = array('id' => $item['id'],
													  'user_id' => $uid,
													  'live_id' => intval($item['live_id']),
													  'private' => 'Y',
													  'description' => $item['subject']);
                } else {
                    self::$publicVideo[$pid] =  array('id' => $item['id'],
													  'user_id' => $uid,
													  'live_id' => intval($item['live_id']),
													  'private' => 'N',
													  'description' => $item['subject']);
                }

                self::$allVideoInfo[$pid]['video_id'] = $item['id'];
                self::$allVideoInfo[$pid]['photo_id'] = 'v_' . $item['id'];
                self::$allVideoInfo[$pid]['user_id'] = $uid;
                self::$allVideoInfo[$pid]['description'] = $item['subject'];
                self::$allVideoInfo[$pid]['private'] = $item['private'];
                self::$allVideoInfo[$pid]['default'] = 0;

                self::$allVideoInfo[$pid]['default_group'] = 0;
                self::$allVideoInfo[$pid]['group_id'] = $item['group_id'];

                self::$allVideoInfo[$pid]['visible'] = $item['active']==1?'Y':'N';
                self::$allVideoInfo[$pid]['reports'] = '';
                self::$allVideoInfo[$pid]['src_b'] = User::getVideoFile($item, 'b', $gender);
                self::$allVideoInfo[$pid]['src_src'] = User::getVideoFile($item, 'src', $gender);
                $allSrc = User::getVideoFile($item, '', $gender);
                self::$allVideoInfo[$pid]['src_m'] = $allSrc;
                self::$allVideoInfo[$pid]['src_r'] = $allSrc;
                self::$allVideoInfo[$pid]['src_s'] = $allSrc;
                self::$allVideoInfo[$pid]['src_v'] = User::getVideoFile($item, 'video_src', $gender);
                $clearUrl = explode('?',self::$allVideoInfo[$pid]['src_v']);
                self::$allVideoInfo[$pid]['format'] = mb_strtolower(pathinfo($clearUrl[0], PATHINFO_EXTENSION));

                VideoHosts::$items[$pidNum] = $item;
                self::$allVideoInfo[$pid]['html_code'] = VideoHosts::getHtmlCodeOneFromSite($item['id'], 807, 454, true, 'auto', '_gallery');
                /* Rating */
                self::$allVideoInfo[$pid]['votes'] = $item['rating'];
                self::$allVideoInfo[$pid]['rating'] = $item['rating'];
                self::$allVideoInfo[$pid]['average'] = $item['rating'];

                self::$allVideoInfo[$pid]['my_rating'] = 0;
                self::$allVideoInfo[$pid]['visible_rating'] = 0;
                self::$allVideoInfo[$pid]['is_video'] = 1;

                self::$allVideoInfo[$pid]['reports'] = $item['users_reports'];

                self::$allVideoInfo[$pid]['hide_header'] = $item['hide_header']*1;

				self::$allVideoInfo[$pid]['live_id'] = intval($item['live_id']);

                $photoIds[] = self::$allVideoInfo[$pid]['photo_id'];

                /* $offsetInfo = User::paramsVideoOffset($uid, '-1', $pidNum);
            //     $offsetInfo = array('offset' => '', 'next' => '', 'prev' => '', 'next_id' => '', 'prev_id' => '');
                foreach ($offsetInfo as $key => $value) {
                    self::$allVideoInfo[$pid][$key] = $value;
                }

                */
                //echo "{$item['id']} > " . self::$allVideoInfo[$pid]['offset'] . " > " . self::$allVideoInfo[$pid]['prev'] . " > " . self::$allVideoInfo[$pid]['next'] . " <br>";

            }
            //var_dump(self::$allVideoInfo);exit;
            self::$allVideo = self::$publicVideo + self::$privateVideo;

            $result = self::$allVideoInfo;
            VideoHosts::setAutoplay($autoPlayDefault);
        }

        if($numPhoto > 0) {

            sort($photoIds);

            foreach($photoIds as $photoIdkey => $pid) {

                if($numPhoto == 1) {
                    $next = 0;
                    $prev = 0;
                } else {

                    if ($photoIdkey == 0) {
                        $next = $photoIdkey + 1;
                        $prev = $numPhoto - 1;
                    } elseif ($photoIdkey == $numPhoto - 1) {
                        $next = 0;
                        $prev = $photoIdkey - 1;
                    } else {
                        $next = $photoIdkey + 1;
                        $prev = $photoIdkey - 1;
                    }

                }


                self::$allVideoInfo[$pid]['offset'] = $photoIdkey;
                self::$allVideoInfo[$pid]['next'] = $next;
                self::$allVideoInfo[$pid]['prev'] = $prev;
                self::$allVideoInfo[$pid]['next_id'] = $photoIds[$next];
                self::$allVideoInfo[$pid]['prev_id'] = $photoIds[$prev];

                $private = self::$allVideoInfo[$photoIds[$prev]]['private'];
                if($private == '1' && !$isFriend && $uid != $g_user['user_id']) {
                    $title = '';
                } else {
                    $title = self::$allVideoInfo[$photoIds[$prev]]['description'];
                }

                self::$allVideoInfo[$pid]['prev_title'] = $title;


                $private = self::$allVideoInfo[$photoIds[$next]]['private'];
                if($private == '1' && !$isFriend && $uid != $g_user['user_id']) {
                    $title = '';
                } else {
                    $title = self::$allVideoInfo[$photoIds[$next]]['description'];
                }

                self::$allVideoInfo[$pid]['next_title'] = $title;

                //echo "NEW<br>{$pid} > " . self::$allVideoInfo[$pid]['offset'] . " > " . self::$allVideoInfo[$pid]['prev'] . " > " . self::$allVideoInfo[$pid]['next'] . " <br>";

            }
        }

        //if ($isEncountersMobile) {
//            User::setNoPhotoPprivateInOffset(false);
        //}

        return $result;
    }


    static function parseVideoProfile(&$html, $type, $uid = null, $forciblyPrepare = false, $imgSize = '')
    {
        global $g;
        global $g_user;

        if ($uid === null) {
            $uid = guid();
        }

        $typeBlock = "video_{$type}";
        $block = "{$typeBlock}_block";
        $typeList = "{$type}Video";
        if (empty(self::$$typeList) || $forciblyPrepare) {
            $videoInfo = self::prepareVideoList($uid);
        }

        $userVideo = self::$$typeList;
        //var_dump($userVideo);exit;
       // var_dump(self::$allVideoInfo);exit;
        if (!empty($userVideo)) {
            $html->setvar("{$typeBlock}_counter", lSetVars('count_photos', array('number' => count($userVideo))));
            $html->setvar('type', 'video');
            $html->setvar('count', count($userVideo));
            $html->parse('counter', true);

            foreach ($userVideo as $video_id => $item) {
                $video['id'] = $item['id'];//$video_id;
                $video['user_id'] = $uid;
                $video['private'] = $item['private'];
                $urlVideo = User::getVideoFile($video, $imgSize, '');//gender not
                if ($type == 'private'){
                    $isFriend = User::isFriend($g_user['user_id'], $uid);
                    $isFriendRequestExists = User::isFriendRequestExists($uid, $g_user['user_id']);
                    if (!$isFriend && !$isFriendRequestExists && (guid() != $video['user_id']))
                    {
                        $html->setvar("{$typeBlock}_title", ' ');
                    } else if (($isFriend) or (guid() == $video['user_id'])) {
                        $html->setvar("{$typeBlock}_title", toAttrL($item['text']));
                    }
                } else {
                    $html->setvar("{$typeBlock}_title", toAttrL($item['description']));
                }

                $html->setvar("url_{$typeBlock}", $urlVideo);
                $html->setvar("video_id", $item['id']);
				if ($html->varExists('video_live_id')) {
					$html->setvar('video_live_id', $item['live_id']);
				}
				if ($html->varExists('video_my')) {
					$html->setvar('video_my', intval($item['user_id'] == guid()));
				}

                /* Gallery */
                //$offset = User::photoOffset($uid, $photo_id);
                $html->setvar("gallery_{$typeBlock}_info", json_encode(self::$allVideoInfo[$video_id]));
                /* Gallery */
                /* City */
                /*
                if ($isCity) {
                    $where = '`photo_id` = ' . to_sql($photo_id) .
                             ' AND `user_id` = ' . to_sql($g_user['user_id']);
                    $photoEditor = DB::select('city_avatar_face', $where);
                    if ($photoEditor && isset($photoEditor[0])) {
                        $html->setvar("{$typeBlock}_face_params", $photoEditor[0]['params']);
                        $fileFace = "city/users/{$g_user['user_id']}_{$photo_id}.jpg";
                        $fileFaceUrl = "{$g['path']['url_files_city']}{$fileFace}?v={$photoEditor[0]['hash']}";
                        $html->setvar("{$typeBlock}_face_url", $fileFaceUrl);
                        $html->setvar("{$typeBlock}_face_color", $photoEditor[0]['head_color']);
                    } else {
                        $html->setvar("{$typeBlock}_face_url", '');
                        $html->setvar("{$typeBlock}_face_color", '');
                        $html->setvar("{$typeBlock}_face_params", '[]');
                    }
                }*/
                /* City */

                $html->setvar("{$typeBlock}_id", $video_id);
                if (guid() == $uid) {
                    //$html->setvar("{$typeBlock}_offset", $offset);
                    $html->parse("{$typeBlock}_action", false);
                }
                if (self::isVideoOnVerification($uid, self::$allVideoInfo[$video_id]['visible'])) {
                    $html->parse("{$typeBlock}_not_checked", false);
                } else {
                    $html->clean("{$typeBlock}_not_checked");
                }
                $html->parse("{$typeBlock}", true);
            }
        }

        $isFriend = User::isFriend($g_user['user_id'], $uid);
        $isFriendRequestExists = User::isFriendRequestExists($uid, $g_user['user_id']);
        if ($g_user['user_id'] == $uid) {
			$html->parse("{$typeBlock}_more");
            $html->parse("{$typeBlock}_add");
        } else if ($type == 'private'
                   && !$isFriend
                   && !$isFriendRequestExists) {
            $html->parse('request_access_private_photo');
        } else if ($isFriend) {
            $html->parse("{$typeBlock}_can_view");
        }

		if (!empty($userVideo) || $g_user['user_id'] == $uid) {
			$html->parse($block);
		}
    }

    static function isVideoOnVerification($uid, $visible)
    {
        if ($visible == 'N') {
            return Common::isOptionActive('video_approval');
        }
        return false;
        //return Common::isOptionActive('photo_approval') && $visible == 'N';
    }



    static function setPhotoPrivate($id, $isAdmin = false, $e_user_id=0)
    {
        global $g_user;

        $responseData = false;

        if($e_user_id)
            $guid = $e_user_id;
        else
            $guid = $g_user['user_id'];

        if ($guid && $id) {

            $sql = 'SELECT `private`, `visible`, `nudity`
                      FROM `photo`
                     WHERE `photo_id` = ' . to_sql($id, 'Number') . '
                       AND `user_id` = ' . to_sql($guid, 'Number');
            $photo = DB::row($sql);
            if (!$photo) {
                return $responseData;
            }
            $private = $photo['private'];

            $default = '`default`';

            if ($private == 'Y') {
                $private = 'N';
                //$response = 'public';
                $access = 'public';
            } else {
                $private = 'Y';
                //$response = 'private';
                $access = 'friends';
                $default = '"N"';
            }

            $sql = "UPDATE `photo`
                       SET `private` = '$private', `default` = $default
                     WHERE `photo_id` = " . to_sql($id, 'Number') . '
                       AND `user_id` = ' .  to_sql($guid, 'Number');
            DB::execute($sql);

            User::setAvailabilityPublicPhoto($g_user['user_id']);
            Wall::UpdateAccessPhoto($id, $access);

            $responseData = 'update';
            if ($private == 'N') {
                $isNudityFilter = Common::isOptionActive('nudity_filter_enabled');
                $isApprovalPhoto = Common::isOptionActive('photo_approval');
                if (!$isAdmin && $photo['visible'] == 'Y' && ($isApprovalPhoto || $isNudityFilter)) {
                    $visible = 'N';
                    $setApprovalPhoto = $isApprovalPhoto;
                    if (!$setApprovalPhoto && $isNudityFilter && $photo['nudity']) {
                        $visible = 'Nudity';
                        $setApprovalPhoto = true;
                    }

                    if ($setApprovalPhoto) {
                        DB::update('photo', array('visible' => $visible), '`photo_id` = ' . to_sql($id, 'Number'));
                        User::setAvailabilityPublicPhoto($guid);
                        $responseData = 'photo_approval';
                        if(Common::isEnabledAutoMail('approve_image_admin')) {
                            $vars = array(
                                'name'  => User::getInfoBasic($guid, 'name'),
                            );
                            Common::sendAutomail(Common::getOption('administration', 'lang_value'), Common::getOption('info_mail', 'main'), 'approve_image_admin', $vars);
                        }
                    }
                }

                if ($photo['visible'] == 'Y' && !self::isPhotoDefaultPublic()) {
                    User::photoToDefault($id);
                    $responseData .= '_set_default';
                }

            }
        }
        return $responseData;
    }

    static function savePhotoDescription()
    {
        $guid = guid();
        if (!$guid) {
            return false;
        }

        $pid = get_param('pid', '');
        $desc = strip_tags(get_param('desc'));
        if (strpos($pid, 'v_') === false) {
            $sql = "UPDATE `photo`
                       SET `description` = " . to_sql($desc, 'Text')
                 . " WHERE `photo_id` = " . to_sql($pid, 'Number')
                   . " AND `user_id` = ".to_sql($guid,'Number');;
            DB::execute($sql);
            return true;
        } else {
            $pidN = str_replace('v_', '', $pid);
            $sql = "UPDATE `vids_video`
                       SET `subject` = " . to_sql($desc, 'Text')
                 . " WHERE `id` = " . to_sql($pidN, 'Number')
                   . " AND user_id = ".to_sql($guid,'Number');
            DB::execute($sql);
            return true;
        }
    }


    static function deletePhoto($id, $getPhotoDefaultId = false, $uid = null, $admin = false)
    {
        global $g, $g_user;

        $responseData = false;
        $guid = guid();
        if ($uid === null) {
            $uid = $guid;
        }

        if (!$uid || !$id) {
            return false;
        }

        if($uid) // added by sohel
            $guid = $uid;

        $groupsPhotoList = get_param('view_list');

        if(strpos($id,'v_') === 0){
            $id = str_replace('v_','', $id);


            $where = '`id` = ' . to_sql($id) . ($admin ? '' : ' AND `user_id` =' . $guid);
            $videoInfo = DB::one('vids_video', $where);
            if (!$videoInfo) {
                return false;
            }

            include_once($g['path']['dir_main'] . '_include/current/vids/tools.php');
            CVidsTools::delVideoById($id);

            if (get_param_int('get_data_edge')) {
                $groupId = $videoInfo['group_id'];
                //$uid = get_param_int('uid');

                $numberVids = CProfileVideo::getTotalVideos($guid, $groupId);
                $numberVidsCount = $numberVids;
                //Fix for page groups photos
                if ($groupsPhotoList) {
                    $numberVids = CProfileVideo::getTotalVideos($guid, 0);
                }

                $responseData = array('photos_info' => array(),
                                      'count_title' => lSetVars('edge_column_videos_title', array('count' => $numberVidsCount)),
                                      'count' => $numberVids);
            } else {
                self::prepareVideoList($uid, '`id` ASC');
                $responseData = self::preparePhotoList($uid, '`photo_id` ASC')+self::$allVideoInfo;
            }
        } else {
            $where = '`photo_id` = ' . to_sql($id) . ($admin ? '' : ' AND `user_id` =' . $guid);
            $photoInfo = DB::one('photo', $where);
            if (!$photoInfo) {
                return false;
            }

            deletephoto($uid, $id);

            $groupId = $photoInfo['group_id'];
            if ($groupId) {
                $pidDefault = GroupsPhoto::getPhotoDefault($uid, $groupId, '', true);
                GroupsPhoto::photoToDefault($pidDefault, $groupId);
            } else {
                $pidDefault = User::getPhotoDefault($uid, 'r', true);
                User::photoToDefault($pidDefault);
            }

            if (get_param_int('get_data_edge')) {
                //$uid = get_param_int('uid');

                $numberPhoto = self::getTotalPhotos($guid, false, $groupId);
                $numberPhotoCount = $numberPhoto;
                //Fix for page groups photos
                if ($groupsPhotoList) {
                    $numberPhoto = self::getTotalPhotos($guid, false, 0);
                }

                $responseData = array('photos_info' => array(),
                                      'photo_default' => $pidDefault,
                                      'count_title' => lSetVars('edge_column_photos_title', array('count' => $numberPhotoCount)),
                                      'count' => $numberPhoto,
                                      'group_id' => $groupId);
            } elseif (!$admin) {
                if ($getPhotoDefaultId) {
                    $responseData = $pidDefault;
                } else {
                    $responseData = self::preparePhotoList($uid, '`photo_id` ASC')+
                                    self::prepareVideoList($uid, '`id` ASC');
                }
            }
        }
        return $responseData;
    }

    static function deleteComment($comment_id = 0, $photo_id = 0)
    {
        global $g_user;

        $responseData = false;
        if ($g_user['user_id']) {
            $cid = get_param('cid', $comment_id);
            $pid = get_param('pid', $photo_id);
            if ($cid) {
                $sql = 'SELECT PC.*, P.user_id AS pu
                          FROM `photo_comments` as PC,
                               `photo` as P
                        WHERE PC.id = ' . to_sql($cid, 'Number') .
                      ' AND PC.photo_id = P.photo_id';
                $photo = DB::row($sql);
                if ($photo) {
                    $isMyComment = $g_user['user_id'] == $photo['user_id'];
                    if ($isMyComment || $g_user['user_id'] == $photo['pu']) {
                        $responseData = array();
                        $noRatingPhoto =  Common::isOptionActiveTemplate('no_rating_photos');
                        if (!$noRatingPhoto) {
                            //If you delete a comment with the rating and the rating is removed
                            if ($isMyComment && $photo['system']) {
                                $responseData = self::deleteRated($pid);
                            }
                        }

                        Wall::remove('photo_comment', $cid, $photo['user_id']);
                        DB::delete('photo_comments', '`id` = ' . to_sql($cid));

                        DB::delete('photo_comments_likes', '`cid` = ' . to_sql($cid));

                        if ($photo['parent_id']) {
                            self::updateCountCommentReplies($photo['parent_id']);
                        } else {
                            $parentComments = DB::select('photo_comments', '`parent_id` = ' . to_sql($cid));
                            foreach ($parentComments as $key => $comment) {
                                Wall::remove('photo_comment', $comment['id'], $comment['user_id']);
                                DB::delete('photo_comments_likes', '`cid` = ' . to_sql($comment['id']));
                            }
                            DB::delete('photo_comments', '`parent_id` = ' . to_sql($cid));
                        }
                        if (!$photo['system']) {
                            Wall::updateCountCommentsCustomItem($pid);
                        }
                        self::updateCountComment($pid, $photo['system']);
                        if ($noRatingPhoto) {//EDGE
                            if ($photo['parent_id']) {
                                $responseData = self::getCountCommentReplies($photo['parent_id']);
                            } else {
                                $responseData = self::getCountComment($pid);
                            }
                        }
                    }
                }
            }
        }
        return $responseData;
    }

    static function publishVideo($type, $groupId = 0)
    {
        $guid = guid();
    	$videos = get_param('photos');
        if (!empty($videos)) {
            $uploadCount = 0;
            foreach ($videos as $video) {
                $uploadCount++;
                $video['id']=  str_replace('v_', '', $video['id']);
                $active = 1;
                if (Common::isOptionActive('video_approval')){
                    $active = 3;
                }

                $groupPage = 0;
                $groupPrivate = 'N';
                if ($groupId) {
                    $groupInfo = Groups::getInfoBasic($groupId);
                    $groupPage = $groupInfo['page'];
                    $groupPrivate = $groupInfo['private'];
                }
                $data = array('active' => $active,
                              'subject' => strip_tags($video['desc']),
                              'group_id' => $groupId,
                              'group_page' => $groupPage,
                              'group_private' => $groupPrivate);
				DB::update('vids_video', $data, '`id` = ' . to_sql($video['id'], 'Number'));

                $data =  array('vid_videos' => 'vid_videos + 1');
                if ($groupId) {
                    $isPage = Groups::getInfoBasic($groupId, 'page');
                    if ($isPage) {
                        $data =  array('vid_videos_pages' => 'vid_videos_pages + 1');
                    } else {
                        $data =  array('vid_videos_groups' => 'vid_videos_groups + 1');
                    }
                }
                DB::update('user', $data, 'user_id = ' . to_sql($guid), '', '', true);

                $_GET['send'] = getRand($uploadCount);
                Wall::add('vids', $video['id'], false, '', false, 0, 'public', 0, '', $groupId);
                CStatsTools::count('videos_uploaded');
            }
            if (Common::isOptionActive('video_approval') && Common::isEnabledAutoMail('approve_video_admin')){
                $vars = array('name'  => User::getInfoBasic($guid, 'name'));
                Common::sendAutomail(Common::getOption('administration', 'lang_value'), Common::getOption('info_mail', 'main'), 'approve_video_admin', $vars);
            }
        }
    }

    static function deleteOldPendingVideos($type)
    {
        global $g_user;
        if ($g_user['user_id'] && $type != '') {
            include_once(self::includePath() . '_include/current/vids/tools.php');
            $sql = "SELECT id FROM vids_video
                     WHERE `active` = '2'
                       AND `live_id` = '0'
                       AND `user_id` = " . to_sql($g_user['user_id'], 'Number');
            $ids = DB::column($sql);
            foreach ($ids as $k => $id) {
                CVidsTools::delVideoById($id);
            }
            //DB::delete('vids_video', "`active` = '2' AND `user_id` = " . to_sql($g_user['user_id'], 'Number'));
        }
    }


    static function publishPhotos($photos = null, $type = null, $groupId = null, $e_user_id=0)
    {
        global $g;
        $templateName = Common::getTmplName();

        if($e_user_id)
            $guid = $e_user_id;
        else
            $guid = guid();

        if ($type === null) {
            $type = get_param('type');
        }

        if ($photos === null) {
            $photos = get_param('photos');
        }

        if ($groupId === null) {
            $groupId = get_param_int('group_id');
        }



        if ($type=='video') {
            self::publishVideo($type, $groupId);
            self::deleteOldPendingVideos($type);

            $photosList = self::preparePhotoList($guid, '`photo_id` ASC', '', '', false, false, false, $groupId);
            if ($templateName == 'edge') {
                $vidsList = CProfileVideo::getVideosList('', '', $guid, false, true, 0, '', $groupId);
                $response = array(
                                'data' => array(
                                    'count' => count($vidsList),
                                    'count_title' => lSetVars('edge_column_videos_title', array('count' => count($vidsList)))
                                )
                            );

                $response = $response + $vidsList + $photosList;
                return $response;
            }

            self::prepareVideoList($guid, '`id` ASC');
            $response = self::$allVideoInfo + $photosList;

            return $response;
        }

		$response = array();
		$vis = 'Y';
		if (Common::isOptionActive('photo_approval')) {
			$vis = 'N';
		}

        $uploadLimitPhotoCount = Common::getOption('upload_limit_photo_count');
        $currentCountPhotos = DB::count('photo', '`visible` <> "P" AND `user_id` = ' . to_sql($guid, 'Number'));
        $isNudityPhoto = false;

        if (!empty($photos) && $type != '') {
            $pid = 0;
            $date = date('Y-m-d H:i:s');
            $uploadCount = 0;
            $wallId = 0;
            $isManyPhotos = true;

            foreach ($photos as $photo) {
                $uploadCount++;
                if ($templateName != 'edge' && !Common::isOptionActive('free_site') && !User::isSuperPowers() && (($currentCountPhotos+$uploadCount) > $uploadLimitPhotoCount)){
                    break;
                }
                if (!$pid) {
                    $pid = $photo['id'];
                }

                $photoVisible = $vis;
                $nudity = 0;
                if(get_session('photo_nudity_' . $photo['id'])) {
                    $photoVisible = 'Nudity';
                    $isNudityPhoto = true;
                    $nudity = 1;
                }

                #var_dump($sql);
                #var_dump($photoVisible);
                #die();

                $access = ($type == 'private') ? 'friends' : 'public';
                $_GET['send'] = getRand($uploadCount);
                if ($isManyPhotos) {
                    if ($wallId) {
                        $isManyPhotos = false;
                        DB::update('wall', array('item_id' => 0), 'id = ' . to_sql($wallId));
                    } else {
                        $wallId = Wall::addGroupAccess('photo', $access, $wallId, $pid, $groupId);
                    }
                }
                Wall::addItemForUser($photo['id'], 'photo', $guid, false, $groupId);

                $groupPage = 0;
                $groupPrivate = 'N';
                if ($groupId) {
                    $groupInfo = Groups::getInfoBasic($groupId);
                    $groupPage = $groupInfo['page'];
                    $groupPrivate = $groupInfo['private'];
                }

                $data = array('visible' => $photoVisible,
                              'nudity' => $nudity,
                              'description' => strip_tags($photo['desc']),
                              'wall_id' => $wallId,
                              'group_id' => $groupId,
                              'group_page' => $groupPage,
                              'group_private' => $groupPrivate);
				DB::update('photo', $data, '`photo_id` = ' . to_sql($photo['id'], 'Number'));
			}

            if ($wallId) {
                $whereGroup = '';
                if ($groupId) {
                    $whereGroup = ' AND `group_id` = ' . to_sql($groupId);
                }
                if (Common::isOptionActive('photo_approval')) {
                    $wallParams = DB::count('photo', '`visible` = "Y" AND `wall_id` = ' . to_sql($wallId) . $whereGroup);
                } else {
                    if (!$groupId) {
                        DB::execute("UPDATE user SET is_photo = 'Y' WHERE user_id = " . to_sql($guid, 'Number'));
                    }
                    $wallParams = 1;
                }
                DB::update('wall', array('params' => $wallParams), '`id` = ' . to_sql($wallId));
            }

            if ($pid) {
                if ($groupId) {
                    GroupsPhoto::checkPhotoDefault($pid, $groupId);
                } else {
                    User::setAvailabilityPublicPhoto($guid);
                    if(!User::getPhotoDefault($guid, '', true)
                        || (!self::isPhotoDefaultPublic() && $type == 'public')){
                        User::photoToDefault($pid);
                    }
                }
            }

            if ($uploadCount && (Common::isOptionActive('photo_approval') || $isNudityPhoto) && Common::isEnabledAutoMail('approve_image_admin')){
                $vars = array(
                    'name'  => User::getInfoBasic($guid,'name'),
                );
                Common::sendAutomail(Common::getOption('administration', 'lang_value'), Common::getOption('info_mail', 'main'), 'approve_image_admin', $vars);
            }
        }

		self::deleteOldPendingPhotos($type);
		self::preparePhotoList($guid, '`photo_id` ASC', '', '', false, false, false, $groupId);

        if ($templateName == 'edge') {
            $vidsList = CProfileVideo::getVideosList('', '', $guid, false, true, 0, '', $groupId);

            if ($pid) {
                $pidDefault = User::getPhotoDefault($guid, '', true, false, DB_MAX_INDEX, false, false, false, false, $groupId, false);
            }
            $response = array(
                            'data' => array(
                                'photo_default' => $pidDefault,
                                'count' => count(self::$allPhotoInfo),
                                'count_title' => lSetVars('edge_column_photos_title', array('count' => count(self::$allPhotoInfo)))
                            )
                        );
            $response = $response + $vidsList + self::$allPhotoInfo;
            return $response;
        }


        self::prepareVideoList($guid, '`id` ASC');

		$response = self::$allPhotoInfo + self::$allVideoInfo;

		return $response;
    }

    static function publishOnePhoto($pid)
    {
        global $g_user;

        $responseData = false;
		if (!empty($pid)) {
            $vis = 'Y';
            if (Common::isOptionActive('photo_approval')) {
                $vis = 'N';
            }
            $isNudityPhoto = false;
            $nudity = 0;
            if(get_session('photo_nudity_' . $pid)) {
                $vis = 'Nudity';
                $isNudityPhoto = true;
                $nudity = 1;
            }
            if ($vis == 'Y') {
                DB::execute("UPDATE user SET is_photo = 'Y' WHERE user_id = " . to_sql($g_user['user_id'], 'Number'));
            }
			DB::update('photo', array('visible' => $vis, 'nudity' => $nudity), '`photo_id` = ' . to_sql($pid, 'Number'));
            User::setAvailabilityPublicPhoto($g_user['user_id']);

            if((Common::isOptionActive('photo_approval') || $isNudityPhoto)
                && Common::isEnabledAutoMail('approve_image_admin')){
                $vars = array(
                    'name'  => User::getInfoBasic($g_user['user_id'],'name'),
                );
                Common::sendAutomail(Common::getOption('administration', 'lang_value'), Common::getOption('info_mail', 'main'), 'approve_image_admin', $vars);
            }
            $responseData = $vis;
        }
        return $responseData;
    }

    static function publishOneVideo($pid)
    {
        $_GET['photos'][0] = array('id' => $pid,'desc' => '');
        self::publishVideo('video');
    }

    static function deleteOldPendingPhotos($type)
    {
		global $g_user;
		if ($g_user['user_id'] && $type != '') {
			$photos = DB::select('photo', "`visible` = 'P' AND `user_id` = " . to_sql($g_user['user_id'], 'Number'));
            if($photos) {
                foreach($photos as $photo) {
                    self::deletePhoto($photo['photo_id']);
                }
            }
		}

	}

    static function setPhotoDefault()
    {
        $responseData = false;
        $pid = get_param('photo_id', 0);
        if (guser('user_id') && $pid) {
            $groupId = get_param_int('group_id');
            if ($groupId) {
                GroupsPhoto::photoToDefault($pid, $groupId);
            } else {
                User::photoToDefault($pid);
            }
            $responseData = true;
        }
        return $responseData;
    }

    static function hideFromHeaderPicture()
    {
        $responseData = false;
        $pid = get_param('photo_id', 0);
        if (guser('user_id') && $pid) {
            $table = 'photo';
            $field = 'photo_id';
            if (self::isVideo($pid)) {
                $table = 'vids_video';
                $field = 'id';
                $pid = self::prepareVideoId($pid);
            }
            $sql = "UPDATE `{$table}`
                       SET `hide_header` = IF(`hide_header` = 1, 0, 1)
                    WHERE `{$field}` = " . to_sql($pid);
            DB::execute($sql);

            $sql = "SELECT `hide_header` FROM `{$table}`
                     WHERE `{$field}` = " . to_sql($pid);
            $responseData = DB::result($sql);
        }
        return $responseData;
    }

    static function getNumberPhotosUser($uid = null, $private = null)
    {
        global $g_user;

        if ($uid === null) {
            $uid = $g_user['user_id'];
        }
        $where = '`user_id` = ' . to_sql($uid, 'Number') . " AND visible!='P' AND `group_id` = 0";
        if ($private !== null) {
            $where .= ' AND `private` = ' . to_sql($private ? 'Y' : 'N');
        }
        return DB::count('photo', $where);
    }

    static function getAveragePhotosUser($uid = null)
    {
        global $g_user;

        if ($uid === null) {
            $uid = $g_user['user_id'];
        }
        $sql = 'SELECT AVG(average)
                  FROM `photo`
                 WHERE `user_id` = ' . to_sql($uid, 'Number') .
                 ' AND `average` > 0';

        $average = DB::result($sql);
        return  empty($average) ? 0 : round($average, 2);
    }

    static function getNumberUsersRatedMePhoto($uid = null, $isNew = false)
    {
        global $g_user;

        if ($uid === null) {
            $uid = $g_user['user_id'];
        }

        $uid = to_sql($uid, 'Number');

        if ($isNew) {
            $sql = 'SELECT pr.id FROM photo_rate AS pr
                LEFT JOIN user_block_list AS ubl1 ON (ubl1.user_to = pr.user_id AND ubl1.user_from = ' . $uid . ')
                LEFT JOIN user_block_list AS ubl2 ON (ubl2.user_from = pr.user_id AND ubl2.user_to = ' . $uid . ')
                WHERE pr.photo_user_id = ' . $uid . '
                    AND pr.visible=1
                    AND pr.is_new = 1
                    AND ubl1.id IS NULL AND ubl2.id IS NULL LIMIT 1';
        } else {
            /*
            $sql = 'SELECT COUNT(pr.id)
                      FROM photo_rate AS pr
                      LEFT JOIN user_block_list AS ubl ON (ubl.user_to = pr.user_id OR ubl.user_from = pr.user_id) AND (ubl.user_from = ' . to_sql($uid, 'Number') . ')
                     WHERE pr.photo_user_id = ' . to_sql($uid, 'Number') .
                     ' AND pr.visible=1 ' . $where .
                     ' AND ubl.id IS NULL
                     GROUP BY pr.user_id ORDER BY pr.id';
            */

            $sql = 'SELECT COUNT(DISTINCT pr.user_id) FROM photo_rate AS pr
                LEFT JOIN user_block_list AS ubl1 ON (ubl1.user_to = pr.user_id AND ubl1.user_from = ' . $uid . ')
                LEFT JOIN user_block_list AS ubl2 ON (ubl2.user_from = pr.user_id AND ubl2.user_to = ' . $uid . ')
                WHERE pr.photo_user_id = ' . $uid . '
                    AND pr.visible=1
                    AND ubl1.id IS NULL
                    AND ubl2.id IS NULL';
        }

        return DB::result($sql);
    }

    static function setNotNewUsersRatedMePhoto($uid = null)
    {
        global $g_user;

        if ($uid === null) {
            $uid = $g_user['user_id'];
        }

        $sql = 'UPDATE photo_rate AS pr
                  LEFT JOIN user_block_list AS ubl ON (ubl.user_to = pr.user_id OR ubl.user_from = pr.user_id) AND (ubl.user_from = ' . to_sql($uid, 'Number') . ')
                   SET pr.is_new=0
                 WHERE pr.photo_user_id = ' . to_sql($uid, 'Number') .
                 ' AND pr.is_new=1
                   AND ubl.id IS NULL';
        DB::execute($sql);
    }

    static function getPhotoIdUnavailableRated($lastId = null, $uid = null)
    {
        global $g_user;

        $nextStepRated = intval(Common::getOption('rate_see_my_photo_rating'));
        if (!$nextStepRated) {
           return false;
        }
        if ($uid === null) {
            $uid = $g_user['user_id'];
        }
        if ($lastId === null) {
            $lastId = $g_user['last_photo_visible_rated'];
        }

        $sql = 'SELECT MAX(photo_id)
                  FROM `photo`
                 WHERE `user_id` = ' . to_sql($uid, 'Number') .
                 ' AND `photo_id` > ' . to_sql($lastId, 'Number') .
                 ' AND `average` > 0';
        return DB::result($sql);
    }

    static function setRated()
    {
        global $g_user;

        $responseData = false;
        if ($g_user['user_id']) {
            $pid = get_param('photo_id', 0);
            $puid = get_param('photo_user_id', 0);
            $rate = intval(get_param('rate', 0));

            $photoInfo = DB::select('photo', '`photo_id` = ' . to_sql($pid, 'Number'));

            if (empty($photoInfo) || !isset($photoInfo[0])) {
                return false;
            }
            $photoInfo = $photoInfo[0];

            $where = '`photo_id` = ' . to_sql($pid, 'Number') .
                     ' AND `user_id` = ' . to_sql($g_user['user_id'], 'Number');
            $userRatePhoto = DB::field('photo_rate', 'rating' ,$where);

            $vars = array('photo_id' => $pid,
                          'photo_user_id' => $puid,
                          'user_id' => $g_user['user_id'],
                          'rating' => $rate);
            $votes = $photoInfo['votes'];
            if (!empty($userRatePhoto) && isset($userRatePhoto[0])){
                $rating = $photoInfo['rating'] - $userRatePhoto[0] + $rate;
                if ($rate) {
                    DB::update('photo_rate', $vars, '`photo_id` = ' . to_sql($pid, 'Number'));
                }
            } else {
                $rating = $photoInfo['rating'] + $rate;
                if ($rate) {
                    $votes = $votes + 1;
                    DB::insert('photo_rate', $vars, '`photo_id` = ' . to_sql($pid, 'Number'));

                    $lastRatedId = self::getPhotoIdUnavailableRated();
                    if ($lastRatedId) {
                        $nextStep = Common::getOption('rate_see_my_photo_rating');
                        $ratedPhotos = $g_user['rated_photos'] + 1;
                        if ($ratedPhotos == $nextStep) {
                            $sql = 'UPDATE `user`
                                       SET `rated_photos` = 0,
                                           `last_photo_visible_rated` = ' . to_sql($lastRatedId, 'Number') .
                                   ' WHERE `user_id` = ' . to_sql($g_user['user_id'], 'Number');

                        } else {
                            $sql = 'UPDATE `user`
                                       SET rated_photos = rated_photos + 1
                                     WHERE `user_id` = ' . to_sql($g_user['user_id'], 'Number');
                        }
                        DB::execute($sql);
                    }
                    if ($rate > 7) {
                        User::updatePopularity($puid);
                    }
                }
            }

            if ($rate && Common::isEnabledAutoMail('voted_photo')) {
                $userInfo = User::getInfoBasic($puid);
                $isNotif = User::isOptionSettings('set_notif_voted_photos', $userInfo);
                if ($isNotif) {
                    $vars = array('title' => Common::getOption('title', 'main'),
                                  'name' => $userInfo['name'],
                                  'name_sender'  => $g_user['name'],
                                  'photo_id' => $pid,
                                  'url_site' => Common::urlSite());
                    Common::sendAutomail($userInfo['lang'], $userInfo['mail'], 'voted_photo', $vars);
                }
            }
            if ($rate) {
                $average = round($rating/$votes, 2);

                $vars = array('photo_id' => $pid,
                              'votes' => $votes,
                              'rating' => $rating,
                              'average' => $average);
                DB::update('photo', $vars, '`photo_id` = ' . to_sql($pid, 'Number'));
            }
            $vars['my_rating'] =  $rate;

            $responseData = $vars;
        }

        return $responseData;
    }

    static function deleteRated($pid = null)
    {
        global $g_user;

        $responseData = false;
        if ($g_user['user_id']) {
            if ($pid === null) {
                $pid = get_param('photo_id', 0);
            }
            $where = '`photo_id` = ' . to_sql($pid, 'Number') .
                     ' AND `user_id` = ' . to_sql($g_user['user_id'], 'Number');
            $userRatePhoto = DB::field('photo_rate', 'rating' ,$where);

            if (!empty($userRatePhoto) && isset($userRatePhoto[0])){

                $photoInfo = DB::select('photo', '`photo_id` = ' . to_sql($pid, 'Number'));
                if (empty($photoInfo) || !isset($photoInfo[0])) {
                    return false;
                }
                $photoInfo = $photoInfo[0];
                $rating = $photoInfo['rating'] - $userRatePhoto[0];
                $votes = $photoInfo['votes'] - 1;
                $average = 0;
                if ($votes != 0) {
                    $average = round($rating/$votes, 2);
                }
                $vars = array('votes' => $votes,
                              'rating' => $rating,
                              'average' => $average);

                DB::update('photo', $vars, '`photo_id` = ' . to_sql($pid, 'Number'));
                DB::delete('photo_rate', $where);

                if (isset($g_user['rated_photos']) && $g_user['rated_photos']) {
                    $sql = 'UPDATE `user`
                               SET rated_photos = rated_photos - 1
                             WHERE `user_id` = ' . to_sql($g_user['user_id'], 'Number');
                    DB::execute($sql);
                }
                $sql = "SELECT `id`
                          FROM `photo_comments`
                         WHERE " . $where .
                         " AND `comment` = '{rating:" . to_sql($userRatePhoto[0], 'Number') . "}'";
                $vars['comment_id'] = 0;
                $commentDeleteId = DB::result($sql);
                if (!empty($commentDeleteId)) {
                    $vars['comment_id'] = $commentDeleteId;
                    DB::delete('photo_comments', '`id` = ' . to_sql($commentDeleteId));
                    self::updateCountComment($pid, true);
                }
                $vars['my_rating'] =  0;
                $responseData = $vars;
            }
        }
        return $responseData;
    }

    static function removeRatingsLeaveUser($uid)
    {
        $sql = 'SELECT *
                  FROM `photo_rate`
                 WHERE `user_id` = ' . to_sql($uid, 'Number');
        DB::query($sql, 0);
        while($row = DB::fetch_row(0)) {
            self::updateRated($row['photo_id'], $row['rating']);
        }
        DB::execute('DELETE FROM `photo_rate` WHERE `user_id` = ' . to_sql($uid, 'Number'));
    }

    static function updateRated($pid, $rating)
    {
        $sql = 'SELECT *
                  FROM `photo`
                 WHERE `photo_id` = ' . to_sql($pid, 'Number');
        $userPhoto = DB::row($sql, 1);
        $data = array();
        if (!empty($userPhoto)) {
            $data['votes'] = $userPhoto['votes'] - 1;
            $data['rating'] = $userPhoto['rating'] - $rating;
            $data['average'] = 0;
            if ($data['votes']) {
                $data['average'] = round($data['rating']/$data['votes'], 2);
            }
            DB::update('photo', $data, '`photo_id` = ' . to_sql($pid, 'Number'));
        }
    }

    static function hideRatedMeItem()
    {
        global $g_user;

        $responseData = false;
        $id = get_param('id');
        if ($g_user['user_id'] && $id) {
            DB::update('photo_rate', array('visible' => 0) ,'`id` = ' . to_sql($id, 'Number'));
            $responseData = self::getNumberUsersRatedMePhoto();
        }
        return $responseData;
    }

    static function includePath()
    {
        return dirname(__FILE__) . '/../../';
    }

    static function getItemWallOnePhoto($pid)
    {
        $sql = 'SELECT `id` FROM `wall` WHERE `item_id` = ' . to_sql($pid) . ' AND `section` = "photo"';
        return DB::result($sql);
    }

    /*
     * count_comments_all = photo commets + replies comments  + system comments(rating)
     * count_comments =  photo commets + replies comments
     * count_comments_replies = replies comments
     */
    static function updateCountComment($pid, $system = false)
    {
        $countCommentsAll = '(SELECT COUNT(*)
                                FROM `photo_comments`
                               WHERE `photo_id` = ' . to_sql($pid) . ')';
        $countComments = 'count_comments';
        $countCommentsReplies = 'count_comments_replies';
        if (!$system) {
            $countComments = '(SELECT COUNT(*)
                                 FROM `photo_comments`
                                WHERE `system` != 1
                                  AND `parent_id` = 0
                                  AND `photo_id` = ' . to_sql($pid) . ')';
            $countCommentsReplies = '(SELECT COUNT(*)
                                 FROM `photo_comments`
                                WHERE `system` != 1
                                  AND `parent_id` != 0
                                  AND `photo_id` = ' . to_sql($pid) . ')';
        }

        $sql = 'UPDATE `photo` SET
                `count_comments_all` = ' . $countCommentsAll . ',
                `count_comments` = ' . $countComments . ',
                `count_comments_replies` = ' . $countCommentsReplies . '
                 WHERE `photo_id` = ' . to_sql($pid);

        DB::execute($sql);
    }

    static function updateCountCommentReplies($cid)
    {
        $sql = 'SELECT COUNT(*)
                  FROM `photo_comments`
                 WHERE `parent_id` = ' . to_sql($cid);
        $countCommentsReplies = DB::result($sql);
        $sql = 'UPDATE `photo_comments` SET
                `replies` = ' . $countCommentsReplies . '
                WHERE `id` = ' . to_sql($cid);
        DB::execute($sql);
    }

    static function getCountCommentReplies($cid)
    {
        $sql = 'SELECT COUNT(*)
                  FROM `photo_comments`
                 WHERE `system` != 1
                   AND `parent_id` = ' . to_sql($cid);
        return DB::result($sql);
    }

    static function getCountComment($pid)
    {
        $sql = 'SELECT `count_comments`
                  FROM `photo`
                 WHERE `photo_id` = ' . to_sql($pid);
        return DB::result($sql, 0, DB_MAX_INDEX);
    }

    static function addComment($isNotifComments = true)
    {
        global $g_user;

        $msg = trim(get_param('comment'));
        $photoId = get_param('photo_id', 0);

        $guid = guid();
        $commentInfo = array();
        $path = self::includePath();

        $msg = censured($msg);

        $isVideo = self::isVideo($photoId);
        if ($isVideo){
            $photoId =  str_replace('v_', '', $photoId);
            $photoInfo = DB::row('SELECT * FROM `vids_video` WHERE `id` = ' . to_sql($photoId));
        } else {
            $photoInfo = DB::row('SELECT * FROM `photo` WHERE `photo_id` = ' . to_sql($photoId));
        }

        $groupId = 0;
        $groupUserId = 0;
        $photoUserId = 0;
        if ($photoInfo) {
            $photoUserId = $photoInfo['user_id'];
            $groupId = $photoInfo['group_id'];
            if ($groupId) {
                $groupUserId = $photoInfo['user_id'];
            }
        }

        if (!$photoUserId) {
            return $commentInfo;
        }

        if ($photoId && $msg != '') {
            $send = get_param('send', time());
            $date = date('Y-m-d H:i:s');
            $parentId = get_param_int('reply_id');
            if ($isVideo) {
                include_once($path . '_include/current/vids/tools.php');
                $autoMail = 'new_comment_video';
                CStatsTools::count('videos_comments');
                $info = CVidsTools::insertCommentByVideoId($photoId, $msg, $date, true, $photoUserId, true, $groupId, $groupUserId);
                $cid = 0;
                if (is_array($info)) {
                    $cid = $info['cid'];
                    $msg = $info['text'];
                }
            } else {
                $autoMail = 'new_comment_photo';
                $system = get_param('system', 0);

                if (Common::isOptionActiveTemplate('gallery_comment_parse_media')) {
                    $msg = OutsideImages::filter_to_db($msg);
                    $msg = VideoHosts::textUrlToVideoCode($msg);
                }
                $commentUserId = 0;
                if ($parentId) {
                    $sql = "SELECT `user_id` FROM `" . self::$tablePhotoComments . "` WHERE `id` = " . to_sql($parentId);
                    $commentUserId = DB::result($sql);
                    $isNew = intval($commentUserId != $guid);
                } else {
                    $isNew = intval($photoUserId != $guid);
                }
                $sql = "INSERT INTO `photo_comments` (`id`, `user_id`, `photo_id`, `photo_user_id`, `date`, `comment`, `system`, `parent_id`, `parent_user_id`, `is_new`, `send`, `group_id`, `group_user_id`)
                        VALUES (NULL, " . to_sql($guid, 'Number') . ', ' .
                        to_sql($photoId, 'Number') . ","
                      . to_sql($photoUserId, 'Number') . ",'"
                      . $date . "',"
                      . to_sql($msg) . ","
                      . to_sql($system, 'Number') . ","
                      . to_sql($parentId, 'Number') . ","
                      . to_sql($commentUserId, 'Number') . ","
                      . to_sql($isNew, 'Number') . ","
                      . to_sql($send) . ","
                      . to_sql($groupId) . ","
                      . to_sql($groupUserId) . ")";
                DB::execute($sql);
                $cid = DB::insert_id();

                if ($parentId) {
                    self::updateCountCommentReplies($parentId);
                }
                if (!$system) {
                    Wall::updateCountCommentsCustomItem($photoId);
                    self::addCommentToWall($cid, $photoId, $photoUserId, get_param('private'));
                }
                self::updateCountComment($photoId, $system);
            }

            if ($photoUserId != $guid) {
                User::updatePopularity($photoUserId);
            }

            /* EDGE */
            $commentInfo['count_comments'] = 0;
            $commentInfo['count_comments_replies'] = 0;
            if ($parentId) {
                $table = $isVideo ? self::$tableVideoComments : self::$tablePhotoComments;
                $sql = "SELECT `replies` FROM `{$table}` WHERE `id` = " . to_sql($parentId);
                $commentInfo['count_comments_replies'] = DB::result($sql);
            } else {
                if ($isVideo) {
                    $dataInfo = DB::one(self::$tableVideo, '`id` = ' . to_sql($photoId));
                } else {
                    $dataInfo = DB::one(self::$tablePhoto, '`photo_id` = ' . to_sql($photoId));
                }

                if ($dataInfo) {
                    $commentInfo['count_comments'] = $dataInfo['count_comments'];
                }
            }
            /* EDGE */

            $commentInfo['id'] = $cid;
            $commentInfo['parent_id'] = $parentId;
            $commentInfo['comment'] = $msg;
            $commentInfo['date'] = $date;
            $commentInfo['user_id'] = $guid;
            $commentInfo['display_profile'] = User::displayProfile();
            $commentInfo['send'] = $send;

            $user = User::getInfoBasic($guid, false, 2);
            $commentInfo['user_name'] = $user['name'];
            $commentInfo['user_photo'] = User::getPhotoDefault($guid, 'r', false, $user['gender']);
			$commentInfo['user_photo_id'] = User::getPhotoDefault($guid, 'r', true);

            /* !!!!!!!!!!!!!!!!!! */


            if ($photoUserId != $guid && $isNotifComments && Common::isEnabledAutoMail($autoMail)) {
                $userInfo = User::getInfoBasic($photoUserId);
                $isNotif = User::isOptionSettings('set_notif_new_comments', $userInfo);
                if ($isNotif) {
                    $vars = array('title' => Common::getOption('title', 'main'),
                                  'name' => $userInfo['name'],
                                  'name_sender'  => $g_user['name'],
                                  'id' => $photoId,
                                  'url_site' => Common::urlSite(),
                                  'cid' => $cid);
                    Common::sendAutomail($userInfo['lang'], $userInfo['mail'], $autoMail, $vars);
                }
            }
        }

        return $commentInfo;
    }

    static function isExistsComment($cid, $type)
    {
        $isExists = true;

        $table = 'vids_comment';
        if ($type == 'photo') {
            $table = 'photo_comments';
        }

        $sql = "SELECT `id` FROM {$table}
                 WHERE `id` = " . to_sql($cid);
        if(DB::result($sql) != $cid) {
            $isExists = false;
        }

        return $isExists;
    }

    static function updateLikeComment($type = null, $id = null)
    {
        global $g_user;

        $cid = get_param_int('cid');
        $like = get_param_int('like');
        $userId = get_param_int('user_id');
        if ($type === null) {
            $id = get_param('id');
        } else {
            if ($type == 'photo') {
                $id = DB::result('SELECT `photo_id` FROM `photo_comments` WHERE `id` = ' . to_sql($cid));
            } else {
                $id = DB::result('SELECT `video_id` FROM `vids_comment` WHERE `id` = ' . to_sql($cid));
            }
        }

        $id = self::prepareVideoId($id);
        $guid = guid();

        if (!$cid || !$guid) {
            return false;
        }

        if ($type === null) {
            $type = get_param('type', 'photo');
        }

        $tableBase = 'vids_video';
        $table = 'vids_comment';
        $tableLike = 'vids_comments_likes';
        if ($type == 'photo') {
            $tableBase = 'photo';
            $table = 'photo_comments';
            $tableLike = 'photo_comments_likes';
        }

        $groupUserId = 0;
        if ($type == 'photo') {
            $groupId = DB::result('SELECT `group_id` FROM `photo_comments` WHERE `id` = ' . to_sql($cid));
        } else {
            $groupId = DB::result('SELECT `group_id` FROM `vids_comment` WHERE `id` = ' . to_sql($cid));
        }

        if ($groupId) {
            $groupUserId = Groups::getInfoBasic($groupId, 'user_id');
            if (!$groupUserId) {
                $groupId = 0;
            }
        }

        $commentUid = DB::field($table, 'user_id', '`id` = ' . to_sql($cid));
        if (!isset($commentUid[0])) {
            return false;
        }
        $commentUid = $commentUid[0];

        $date = date('Y-m-d H:i:s');
        if ($like) {
            if ($type == 'photo') {
                $fieldId = 'photo_id';
                $fieldUserId = 'photo_user_id';
                $mUserId = DB::result('SELECT `user_id` FROM `photo` WHERE `photo_id` = ' . to_sql($id));
            } else {
                $fieldId = 'video_id';
                $fieldUserId = 'video_user_id';
                $mUserId = DB::result('SELECT `user_id` FROM `vids_video` WHERE `id` = ' . to_sql($id));
            }
            $isNew = intval($commentUid != $guid);

            $sql = "INSERT IGNORE INTO `{$tableLike}`
                       SET `user_id` = " . to_sql($guid) . ',
                           `' . $fieldId . '` = ' . to_sql($id) . ',
                           `' . $fieldUserId . '` = ' . to_sql($mUserId) . ',
                           `date` = ' . to_sql($date) . ',
                           `is_new` = ' . to_sql($isNew) . ',
                           `comment_user_id` = ' . to_sql($commentUid) . ',
                           `cid` = ' . to_sql($cid)  . ',
                           `group_user_id` = ' . to_sql($groupUserId)  . ',
                           `group_id` = ' . to_sql($groupId);
            DB::execute($sql);
            $isNewLikes = 1;

            $countLikes = DB::count($tableLike, '`cid` = ' . to_sql($cid));
        } else {
            $where = '`user_id` = '  . to_sql($guid) .
                     ' AND `cid` = '   . to_sql($cid);
            DB::delete($tableLike, $where);

            $countLikes = DB::count($tableLike, '`cid` = ' . to_sql($cid));
            /* Fix live */
            $isNewLikes = $countLikes ? 1 : 0;
            /* Fix live */
        }

        $data = array('likes' => $countLikes, 'last_action_like' => $date);
        if ($type != 'photo') {
            $data['is_new_like'] = $isNewLikes;
        }
        DB::update($table, $data, '`id` = ' . to_sql($cid));


        if ($type == 'photo') {
            $wallId = DB::result('SELECT `wall_id` FROM `photo` WHERE `photo_id` = ' . to_sql($id));
            DB::update('wall', array('last_action_comment_like' => $date), '`id` = ' . to_sql($wallId));
        } else {

            /* Fix live */
            DB::update($tableBase, array('last_action_comment_like' => $date), '`id` = ' . to_sql($id));
            /* Fix live */

            DB::update('wall', array('last_action_comment_like' => $date), '`section` = "vids" AND `item_id` = ' . to_sql($id));
        }

        $autoMail = "like_comment_{$type}";
        $isNotif = true;//User::isOptionSettings('set_notif_new_comments', $userInfo);
        if ($like && $userId != $guid && $isNotif && Common::isEnabledAutoMail($autoMail)) {
            $userInfo = User::getInfoBasic($commentUid);
            $vars = array('title' => Common::getOption('title', 'main'),
                          'name' => $userInfo['name'],
                          'uid_gallery' => $userId,
                          'user_id' => $commentUid,
                          'name_sender'  => $g_user['name'],
                          'id' => $id,
                          'cid' => $cid,
                          'url_site' => Common::urlSite());
            //Common::sendAutomail($userInfo['lang'], $userInfo['mail'], $autoMail, $vars);
        }

        return array('likes' => $countLikes, 'date' => $date, 'likes_users' => User::getTitleLikeUsersComment($cid, $countLikes, $type));
    }


    static function addLike($type = null, $id = null, $like = null, $remove = false) {

        if ($type === null) {
            $type = get_param('type', 'photo');
        }

        if ($like === null) {
            $like = get_param_int('like');
        }

        $table = 'vids_video';
        $tableLikes = 'vids_likes';
        $field = 'video_id';
        $fieldU = 'video_user_id';
        $fieldT = 'id';
        $section = 'vids';
        if ($type == 'photo') {
            $table = 'photo';
            $tableLikes = 'photo_likes';
            $field = 'photo_id';
            $fieldU = 'photo_user_id';
            $fieldT = $field;
            $section = 'photo';
        }

        $uid = guid();

        if ($id === null) {
            $id = get_param('photo_id');
        }

        $groupUserId = 0;
        if ($type == 'photo') {
            $photoInfo = DB::one('photo', 'photo_id = ' . to_sql($id));
            if (!$photoInfo) {
                return false;
            }
            $groupId = $photoInfo['group_id'];
            $mUid = $photoInfo['user_id'];
        } else {
            $videoInfo = DB::one('vids_video', 'id = ' . to_sql($id));
            if (!$videoInfo) {
                return false;
            }
            $groupId = $videoInfo['group_id'];
            $mUid = $videoInfo['user_id'];
        }
        if ($groupId) {
            $groupUserId = Groups::getInfoBasic($groupId, 'user_id');
            if (!$groupUserId) {
                $groupId = 0;
            }
        }

        $currentLike = false;
        $where = '`user_id` = ' . to_sql($uid) . ' AND `' . $field . '` = ' . to_sql($id);
        $sql = 'SELECT `like` FROM ' . $tableLikes . '
                 WHERE ' . $where;
        if (!$remove) {
            $currentLike = DB::one($tableLikes, $where);
        }

        $date = date('Y-m-d H:i:s');
        if ($remove || ($currentLike && $currentLike['like'] == $like)) {
            DB::delete($tableLikes, $where);
            $like = '';
        } else {
            $sql = 'INSERT IGNORE INTO ' . $tableLikes . '
                       SET `user_id` = ' . to_sql($uid) . ',
                           `' . $field . '` = ' . to_sql($id) . ',
                           `' . $fieldU . '` = ' . to_sql($mUid) . ',
                           `group_id` = ' . to_sql($groupId) . ',
                           `group_user_id` = ' . to_sql($groupUserId) . ',
                           `like` = ' . to_sql($like) . ',
                           `date` = ' . to_sql($date) .
                      ' ON DUPLICATE KEY UPDATE
                          `like` = ' . to_sql($like);
            DB::execute($sql);
            $like = $like ? 'Y' : 'N';
        }

        $sql = 'SELECT `id`
                  FROM `wall`
                 WHERE `section` = ' . to_sql($section) .
                 ' AND `item_id` = ' . to_sql($id);
        $wallId = DB::result($sql);

        self::updateWallItemLikes($wallId, $tableLikes, $field);

        $sql = 'SELECT COUNT(*) FROM `' . $tableLikes .  '`
                 WHERE `like` = 1 AND `' . $field . '` = ' . to_sql($id);
        $countLike = DB::result($sql);

        $sql = 'SELECT COUNT(*) FROM `' . $tableLikes .  '`
                 WHERE `like` = 0 AND `' . $field . '` = ' . to_sql($id);
        $dislike = DB::result($sql);

        $data = array('like' => $countLike, 'dislike' => $dislike);
        if ($type != 'photo') {
            $data['last_action_like'] = $date;
        }
        DB::update($table, $data, "{$fieldT} = " . to_sql($id));

        $data['id'] = $type == 'photo' ? $id : 'v_' . $id;
        $data['my_like'] = $like;

        return $data;
    }

    static function updateWallItemLikes($wallId, $tableLikes, $field){
        if (!$wallId) {
            return;
        }
        $likes = '(SELECT COUNT(*) FROM `' . $tableLikes . '` AS l
                    WHERE l.' . $field . ' = w.item_id AND l.like = 1)';
        $likeLastAction = to_sql(date('Y-m-d H:i:s'));

        $sql = 'UPDATE wall AS w SET
                 likes_media = ' . $likes . ',
                 last_action_like_media = ' . $likeLastAction .  '
                 WHERE id = ' . to_sql($wallId);
        DB::execute($sql);
    }

    static function addLikeWall($id, $like){
        $tmplWallType = Common::getOptionTemplate('wall_type');
        if ($tmplWallType != 'edge') {
            return false;
        }

        $likes = false;
        $infoItem = DB::row('SELECT * FROM `wall` WHERE `id` = ' . to_sql($id));
        if ($infoItem) {
            if ($infoItem['section'] == 'vids' || ($infoItem['section'] == 'photo' && $infoItem['item_id'])) {
                $type = $infoItem['section'] == 'vids' ? 'video' : 'photo';
                $likes = self::addLike($type, $infoItem['item_id'], $like, !$like);
                if ($like) {
                    Wall::sendAlert('like', $id, guid());
                }
            }
        }

        return $likes;
    }

    static function getLikeWallData($type, $id){

        $table = 'vids_video';
        $tableLikes = 'vids_likes';
        $field = 'video_id';
        $fieldT = 'id';
        if ($type == 'photo') {
            $table = 'photo';
            $tableLikes = 'photo_likes';
            $field = 'photo_id';
            $fieldT = $field;
        }

        $data = array();
        $info = DB::one($table, $fieldT . ' = ' . to_sql($id));
        if ($info) {
            $where = $field . ' = ' . to_sql($id) . ' AND `user_id` = ' . to_sql(guid());
            $likeInfo = DB::one($tableLikes, $where);
            $like = '';
            if ($likeInfo) {
                $like = $likeInfo['like'] ? 'Y' : 'N';
            }
            $data = array(
                'id' => $type == 'photo' ? $id : 'v_' . $id,
                'like' => $info['like'],
                'dislike' => $info['dislike'],
                'my_like' => $like
            );
        }

        return $data;
    }

    static function parseLikeWallData(&$html, $mediaData){
        if ($mediaData) {
            $mediaData = array(
                'id' => $mediaData['id'],
                'data' => json_encode($mediaData));
            $html->assign('wall_like_media_item', $mediaData);
            $html->parse('wall_like_media_item_script', true);
        }
    }

    static function addCommentToWall($cid, $pid, $photoUid = null, $private = null, $isVideo=false)
    {
        $section = 'photo_comment';
        $sql = 'SELECT `user_id`, `private`
                  FROM `photo`
                 WHERE `photo_id` = ' . to_sql($pid, 'Number');
        if (!$photoUid || !$private) {
            $row = DB::row($sql);
            $photoUid = $row['user_id'];
            $private = $row['private'];
        }
        $hideFromUser = 0;
        if (guid() == $photoUid) {
            $hideFromUser = guid();
        }
        $access = $private == 'Y' ? 'friends' : 'public';
        Wall::setSiteSectionItemId($pid);
        Wall::add($section, $cid, false, '', false, $hideFromUser, $access, $photoUid);
    }

    static function prepareDataComment($comment, $type = '', $isWall = false)
    {
        $user = User::getInfoBasic($comment['user_id'], false, 2);
        if (!$user) {
            return false;
        }
        $comment['name'] = $user['name'];
        Wall::prepareCommentInfo($comment);

        $commentInfo = array();
        $prfId = Wall::getPrfMediaId($type, $isWall);
        $commentInfo['id'] = $comment['id']. $prfId;
        $commentInfo['parent_id'] = $comment['parent_id']. $prfId;
        $commentInfo['user_id'] = $comment['user_id'];
        $commentInfo['group_id'] = isset($comment['group_id']) ? $comment['group_id'] : 0;
        $commentInfo['item_group_id'] = isset($comment['item_group_id']) ? $comment['item_group_id'] : 0;

        $photoUserId = 0;
        if (isset($comment['photo_user_id'])) {
            $photoUserId = $comment['photo_user_id'];
        }elseif (isset($comment['video_user_id'])) {
            $photoUserId = $comment['video_user_id'];
        }

        $commentInfo['photo_user_id'] = $photoUserId;

        $commentInfo['comment'] = $comment['comment'];
        $commentInfo['date'] = $comment['date'];
        $commentInfo['display_profile'] = User::displayProfile();

        if ($isWall) {
            $commentInfo['wall_user_id'] = $photoUserId;
            $commentInfo['comment_user_id'] = $comment['user_id'];
        }

        //$commentInfo['user_name'] = $user['name'];
        //$commentInfo['user_photo'] = User::getPhotoDefault($comment['user_id'], "r", false, $user['gender']);
        //$commentInfo['user_photo_id'] = User::getPhotoDefault($comment['user_id'], "r", true);

        $commentInfo['user_name'] = $comment['comm_user_name'];
        $commentInfo['user_photo'] = $comment['comm_user_photo_r'];
        $commentInfo['user_photo_id'] = $comment['comm_user_photo_id'];
        $commentInfo['user_url'] = $comment['comm_user_url'];
        $commentInfo['user_group_owner'] = $comment['comm_user_group_owner'];

        $commentInfo['send'] = $comment['send'];

        if (!isset($comment['likes'])) {
            $comment['likes'] = 0;
        }
        $commentInfo['count_likes'] = $comment['likes'];
        $commentInfo['count_likes_users'] = User::getTitleLikeUsersComment($comment['id'], $comment['likes'], $type);

        if (isset($comment['count_comments'])) {
            $commentInfo['count_comments'] = $comment['count_comments'];
        }
        if (isset($comment['count_comments_replies'])) {
            $commentInfo['count_comments_replies'] = $comment['count_comments_replies'];
        }

        return $commentInfo;
    }

    static function parseRepliesComments(&$html, $cid, $numberReplies, $alwaysView = false, $commentsLikes = null, $type = 'photo', $isWall = false, $showEventReplyCommentId = false)
    {
        $cmd = get_param('cmd');
        $paramCid = get_param_int('cid');

        $blockItem = 'comments_reply_item';
        $blockReplyLoadWall = "{$blockItem}_load_wall";
        $html->clean($blockReplyLoadWall);
        $html->clean($blockItem);

        $block = 'comments_reply_list';
        $html->clean("{$block}_load");
        $html->clean("{$block}_load_number");
        $html->clean($block);

        if (!$numberReplies) {
            return false;
        }

        $html->setvar("{$block}_comments_number", $numberReplies);

        if ($type == 'video' || $type == 'live') {
            $table = 'vids_comment';
            $tableG = 'vids_video';
            $field = 'id';
            $fieldId = 'video_id';
        } elseif($type == 'blogs_post') {
            $table = 'blogs_comment';
            $tableG = 'blogs_post';
            $field = 'id';
            $fieldId = 'post_id';
        } else {
            $table = 'photo_comments';
            $tableG = 'photo';
            $field = 'photo_id';
            $fieldId = 'photo_id';
        }

        if ($isWall) {
            $numberVisibleCommentReplyList = Wall::getNumberShowCommentsReplies();
        } else {
            $numberVisibleCommentReplyList = self::getNumberShowComments(true, $type);
        }

        if (!$numberVisibleCommentReplyList && !$alwaysView) {
            if ($isWall) {
                $prf = Wall::getPrfMediaId($type, true);
                $html->setvar("{$blockReplyLoadWall}_id", $cid . $prf);
                $sql = "SELECT `id` FROM `{$table}` WHERE `parent_id` = " . to_sql($cid) .
                       " ORDER BY id DESC LIMIT 1";
                $lastIdReply = DB::result($sql, 0, DB_MAX_INDEX);
                $html->setvar("{$blockReplyLoadWall}_last_id", $lastIdReply);
                $html->parse($blockReplyLoadWall, false);
            }

            $vars = array(
                'view_number' => 0,
                'all_number' => $numberReplies
            );
            $html->setvar("{$block}_load_title", lSetVars('view_previous_replies_number_all', $vars));
            $html->setvar("{$block}_load_number", lSetVars('view_previous_replies_number', $vars));
            $html->parse("{$block}_load", false);
            return false;
        }

        if ($isWall) {
            if ($cmd == 'comments_load' && $paramCid) {
                $numberVisibleCommentReplyList = Wall::getNumberShowCommentsRepliesLoadMore();
            }
        } elseif ($alwaysView) {
            $numberVisibleCommentReplyList = self::getNumberShowCommentsLoadMore(true, $type);
        }

        $where = '';
        $loadMore = get_param_int('load_more');
        $lastId = get_param_int('last_id');

        if ($loadMore && $lastId && ($cmd == 'get_comment_replies' || $cmd == 'get_live_stream_comment_replies')) {
            $where = ' AND c.id < ' . to_sql($lastId);
        }
        $limit = $numberVisibleCommentReplyList;
        $limitParam = get_param_int('limit');
        if ($limitParam) {
            $limit = $limitParam;
        }

        if ($isWall) {
            if (($cmd == 'comment' || $cmd == 'update')
                    && Wall::$commentCustomClass != 'comment_attach'
                    && Wall::$commentCustomClass != 'comment_attach_reply_add') {
                $where = ' AND c.id > ' . to_sql($lastId);
                $limit = 0;
            } elseif($cmd == 'comment_delete' && get_param_int('cid_parent')){
                $where = ' AND c.id < ' . to_sql($lastId);
                $limit = 1;
            } elseif ($cmd == 'comments_load') {
                $where = '';
                if ($lastId && $paramCid) {
                    $where = ' AND c.id < ' . to_sql($lastId);
                }
            }
        } elseif ($type == 'live') {
            if ($cmd == 'update_im' && Wall::$commentCustomClass != 'comment_attach'
                    && Wall::$commentCustomClass != 'comment_attach_reply_add') {
                $where = ' AND c.id > ' . to_sql($lastId);
                $limit = 0;
            } elseif($cmd == 'live_stream_comment_delete' && get_param_int('cid_parent')){
                $where = ' AND c.id < ' . to_sql($lastId);
                $limit = 1;
            }
        }

        $sqlLimit = '';
        $lastIdReply = 0;
        if ($limit) {
            if (Wall::$commentCustomClass != 'comment_attach_reply_add' && $cmd != 'live_stream_comment_delete') {
                $limit = $limit + 1;
                $sql = "SELECT `id` FROM `{$table}` WHERE `parent_id` = " . to_sql($cid) .
                       " ORDER BY id ASC LIMIT 1";
                $lastIdReply = DB::result($sql, 0, DB_MAX_INDEX);
            }
            $sqlLimit = ' LIMIT ' . to_sql($limit, 'Number');
        }

        $fieldMGroup = 'm.group_id AS item_group_id';
        if ($type == 'blogs_post') {
            $fieldMGroup = 'IF(true, 0, 0) AS item_group_id';
        }
        $sql = "SELECT c.*, {$fieldMGroup}
                  FROM `{$table}` AS c
                  LEFT JOIN `{$tableG}` AS m ON m.{$field} = c.{$fieldId}
                 WHERE c.parent_id = " . to_sql($cid) . $where .
               " ORDER BY c.id DESC " . $sqlLimit;
        $replies = DB::all($sql);
        if ($cid == 3151) {
           // var_dump_pre($_GET['last_id']);
            //var_dump_pre(get_param('last_id'));
            //echo $sql;
            //exit();
        }


        if ($showEventReplyCommentId) {
            $isUpdateListReplies = true;
            $numReplies = count($replies);
            $i = 0;
            foreach ($replies as $key => $comment) {
                $i++;
                if ($comment['id'] == $showEventReplyCommentId) {
                    $isUpdateListReplies = false;
                    if ($numReplies == $i) {
                        $lastIdReply = 0;
                        $numberVisibleCommentReplyList++;
                    }
                    break;
                }
            }
            if ($isUpdateListReplies) {
                /*$sql = "SELECT *
                          FROM `{$table}`
                         WHERE `parent_id` = " . to_sql($cid) .
                         " AND `id` >= " . to_sql($showEventReplyCommentId) .
                       " ORDER BY id DESC ";
                $replies = DB::all($sql);*/

                $sql = "SELECT c.*, m.group_id AS item_group_id
                          FROM `{$table}` AS c
                          LEFT JOIN `{$tableG}` AS m ON m.{$field} = c.{$fieldId}
                         WHERE c.parent_id = " . to_sql($cid) .
                         " AND c.id >= " . to_sql($showEventReplyCommentId) .
                       " ORDER BY c.id DESC ";
                $replies = DB::all($sql);
                $lastIdReply = 0;
            }
        }

        if ($lastIdReply && $replies) {
            $isUnset = true;
            foreach ($replies as $key => $comment) {
                if ($comment['id'] == $lastIdReply) {
                    $isUnset = false;
                    break;
                }
            }
            if ($isUnset) {
                array_pop($replies);
            } else {
                $numberVisibleCommentReplyList++;
            }
        }


        $isCommentLike = Common::isOptionActiveTemplate('gallery_comment_like');
        if ($commentsLikes === null) {
            $commentsLikes = array();
            if ($isCommentLike) {
                $commentsLikes = self::getAllLikesCommentsFromUser($type);
            }
        }

        if ($replies) {
            $replies = array_reverse($replies);

            foreach ($replies as $key => $comment) {
                if ($type == 'video' || $type == 'live' || $type == 'blogs_post') {
                    $comment['comment'] = $comment['text'];
                    $comment['date'] = $comment['dt'];
                }
                $commentInfoReply = self::prepareDataComment($comment, $type, $isWall);
                if ($isCommentLike && isset($commentsLikes[$comment['id']])) {
                    $commentInfoReply['like'] = 1;
                }
                self::parseComment($html, $commentInfoReply, 'comments_reply_item', $type, $isWall);
            }
            $html->parse($block, false);
        }

        if ($numberReplies > $numberVisibleCommentReplyList) {
            $html->setvar("{$block}_load_title", l('view_previous_replies'));
            $vars = array(
                'view_number' => count($replies),
                'all_number' => $numberReplies
            );
            $html->setvar("{$block}_load_number", lSetVars('view_previous_replies_number', $vars));
            $html->parse("{$block}_load_number", false);
            $html->parse("{$block}_load", false);
        }
        return true;
    }

    static function parseCommentsReplies(&$html, $cid = null, $type = null)
    {
        global $g_user;

        if ($cid === null) {
            $cid = get_param_int('comment_id');
        }
        if (!$cid) {
            return;
        }

        if ($type === null) {
            $type = get_param('type', 'photo');
        }

        if ($type == 'video') {
            $table = 'vids_comment';
        } elseif ($type == 'blogs_post') {
            $table = 'blogs_comment';
        } else {
            $table = 'photo_comments';
        }

        $comment = DB::one($table, '`id` = ' . to_sql($cid));
        if (!$comment) {
            return;
        }

        if (self::parseRepliesComments($html, $cid, $comment['replies'], true, null, $type)){
            $html->parse('comment');
        }
    }

    static function getAllLikesCommentsFromUser($type = 'photo')
    {
        $uid = guid();
        if ($type == 'video' || $type == 'live') {
            $table = 'vids_comments_likes';
        } elseif ($type == 'blogs_post') {
            $table = 'blogs_comments_likes';
        } else {
            $table = 'photo_comments_likes';
        }
        $commentsLikes = DB::field($table, 'cid', '`user_id` = ' . to_sql($uid));
        return array_flip($commentsLikes);
    }

    static function getNumberShowComments($replies = false, $typeComments = '')
    {
        $optionTemplateName = Common::getTmplName();
        $numberComments = 20;
        $numberCommentsTemplate = Common::getOptionTemplateInt('gallery_comment_list_number');
        if ($numberCommentsTemplate) {
            $numberComments = $numberCommentsTemplate;
        }
        /* Settings admin */
        $type = '';
        if ($replies) {
           $type = '_replies';
        }
        if ($typeComments == 'blogs_post') {
            $numberCommentsTemplate = Common::getOption("blogs_show_comments{$type}", "{$optionTemplateName}_blogs_settings");
        } elseif ($typeComments == 'live') {
            $numberCommentsTemplate = Common::getOption("show_comments{$type}", "{$optionTemplateName}_live_settings");
			if ($numberCommentsTemplate === null) {
				$numberCommentsTemplate = Common::getOptionTemplate("show_comments_live_template");
			}
        } else {
            $numberCommentsTemplate = Common::getOption("gallery_show_comments{$type}", "{$optionTemplateName}_gallery_settings");
        }

        if ($numberCommentsTemplate !== null) {
            $numberCommentsTemplate = intval($numberCommentsTemplate);
            if (!$numberCommentsTemplate && !$replies) {
                $numberCommentsTemplate = 1;
            }
            $numberComments = $numberCommentsTemplate;
        }
        return $numberComments;
    }

    static function getNumberShowCommentsLoadMore($replies = false, $typeComments = '')
    {
        $optionTemplateName = Common::getTmplName();
        $numberComments = self::getNumberShowComments();
        /* Settings admin */
        $type = '';
        if ($replies) {
           $type = '_replies';
        }

        if ($typeComments == 'blogs_post') {
            $numberCommentsTemplate = Common::getOption("blogs_show_comments{$type}_load", "{$optionTemplateName}_blogs_settings");
        } elseif ($typeComments == 'live') {
            $numberCommentsTemplate = Common::getOption("show_comments{$type}_load", "{$optionTemplateName}_live_settings");
        } else {
            $numberCommentsTemplate = Common::getOption("gallery_show_comments{$type}_load", "{$optionTemplateName}_gallery_settings");
        }

        if ($numberCommentsTemplate !== null) {
            $numberCommentsTemplate = intval($numberCommentsTemplate);
            if (!$numberCommentsTemplate) {
                $numberCommentsTemplate = 1;
            }
            $numberComments = $numberCommentsTemplate;
        }

        return $numberComments;
    }

    static function markReadCommentsAndLikes($id, $uid, $type)
    {
        $guid = guid();

        if ($type == 'video') {
            $table = 'vids_comment';
            $tableLikeMedia = 'vids_likes';
            $tableLike = 'vids_comments_likes';
            $fieldId = 'video_id';
            $id = self::prepareVideoId($id);
        } else {
            $table = 'photo_comments';
            $tableLikeMedia = 'photo_likes';
            $tableLike = 'photo_comments_likes';
            $fieldId = 'photo_id';
        }

        $where = $fieldId . ' = ' .to_sql($id) . ' AND `is_new` = 1';
        if ($guid == $uid) {
            DB::update($tableLikeMedia, array('is_new' => 0), $where);
            $where .= ' AND (`parent_id` = 0 OR `parent_user_id` = ' . to_sql($guid) . ')';
        } else {
            $where .= ' AND `parent_user_id` = ' . to_sql($guid);
        }

        DB::update($table, array('is_new' => 0), $where);

        $where = $fieldId . ' = ' .to_sql($id) . ' AND `is_new` = 1 AND `comment_user_id` = ' . to_sql($guid);
        DB::update($tableLike, array('is_new' => 0), $where);

    }

    static function parseComments(&$html, $pid, $type = 'photo')
    {
        global $g_user;
        $guid = $g_user['user_id'];
        $cmd = get_param('cmd');
        $isGetLoadComments = in_array($cmd, array('get_photo_comment', 'get_video_comment', 'get_live_stream_comment'));

        if ($type == 'video') {
            //$tableG = 'vids_video';
            //$fieldIdG = 'id';
            $table = 'vids_comment';
            $fieldId = 'video_id';
            $pid = str_replace('v_', '', $pid);
            $row = DB::one('vids_video', '`id` = ' . to_sql($pid, 'Number'));
        } else {
            //$tableG = 'photo';
            //$fieldIdG = 'photo_id';
            $table = 'photo_comments';
            $fieldId = 'photo_id';
            $row = DB::one('photo', '`photo_id` = ' . to_sql($pid, 'Number'));
        }
        if ($row) {
            $typeGallery = Common::getOptionTemplate('gallery_type');
            $groupId = 0;
            if ($typeGallery == 'edge'){
                $groupId = $row['group_id'];
            }

            self::markReadCommentsAndLikes($pid, $row['user_id'], $type);

            $isCommentReply = Common::isOptionActiveTemplate('gallery_comment_replies');
            $isCommentLike = Common::isOptionActiveTemplate('gallery_comment_like');
            $numberCommentReplyList = Common::getOptionTemplateInt('gallery_comment_replies_load_number');
            $lastId = get_param('last_id', false);
            $limit = '';
            $where = '';
            $whereSql = '';
            $whereEventSql = '';
            if ($type == 'photo' && Common::isOptionActiveTemplate('no_rating_photos')) {
                $where = " AND `comment` NOT LIKE '{rating:%'";
                $whereSql = $where;
                $whereEventSql = $where;
            }

            /* Edge */
            $numberComments = self::getNumberShowComments(false, $type);
            $loadMore = false;
            if ($lastId !== false) {
                $loadMore = get_param_int('load_more');
                if ($loadMore) {
                    $numberComments = self::getNumberShowCommentsLoadMore(false, $type);
                    $where .= ' AND `id` < ' . to_sql($lastId);
                } else {
                    $where .= ' AND `id` > ' . to_sql($lastId);
                }
                $limitParam = get_param_int('limit');
                if ($limitParam) {
                    $numberComments = $limitParam;
                }
                $limit = ' LIMIT ' . $numberComments;
            }
            /* Edge */

            if ($type == 'video') {
                $isParseComments = true;//For Urban and Impact only public albums - check is not needed
                /*$isParseComments = $video['private'] == '0'
                                    || User::isFriend(guid(), $video['user_id'])
                                    || $video['user_id'] == $guid;*/
            } else {
                $isParseComments = $row['private'] == 'N'
                                    || User::isFriendForPhoto($guid, $row['user_id'])
                                    || $row['user_id'] == $guid;
            }
            $var = "{$fieldId}_comments";
            if ($html->varExists($var)) {
                $html->setvar($var, $pid);
            }

            $showEventReplyCommentId = 0;
            $showCommentParentId = 0;
            if ($isParseComments) {
                    if ($isCommentReply) {
                        $where .= ' AND `parent_id` = 0';
                        $whereEventSql .= ' AND `parent_id` = 0';
                    }
                    if ($type == 'video') {
                        CStatsTools::count('videos_viewed');
                        CVidsTools::viewVideoByIdAndUserId($pid, $row['user_id']);
                    } elseif($type == 'photo' && $cmd != 'get_data_photos_gallery') {
                        self::setMediaViews($pid);
                    }

                    $sql = "SELECT *
                              FROM `{$table}`
                             WHERE `{$fieldId}` = " . to_sql($pid, 'Number') . $where .
                           " ORDER BY id DESC" . $limit;

                    $comments = DB::all($sql);
                    /* Show comment from event */
                    $showCommentId = get_param_int('show_comment_id');
                    if ($typeGallery == 'edge' && $comments && $showCommentId && $isGetLoadComments) {
                        $sql = "SELECT `parent_id`
                                  FROM `{$table}`
                                 WHERE `id` = " . to_sql($showCommentId) .
                                " LIMIT 1";
                        $showCommentParentId = DB::result($sql);
                        if ($showCommentParentId) {
                            $showEventReplyCommentId = $showCommentId;
                        } else {
                            $showCommentParentId = $showCommentId;
                        }
                        $isExistsShowComment = false;
                        foreach ($comments as $key => $comment) {
                            if ($comment['id'] == $showCommentParentId) {
                                $isExistsShowComment = true;
                                break;
                            }
                        }

                        if (!$isExistsShowComment) {
                            $sql = "SELECT *
                                      FROM `{$table}`
                                     WHERE `{$fieldId}` = " . to_sql($pid, 'Number') . $whereEventSql .
                                     ' AND `id` >= ' . to_sql($showCommentParentId) .
                                   " ORDER BY id DESC";
                            $comments = DB::all($sql);
                            $numberComments = count($comments);
                        }
                        /* Show comment from event */
                    }

                    if ($typeGallery == 'edge' && !$loadMore) {
                        krsort($comments);
                    }
                    $count = count($comments);
                    $countRows = $count;

                    $commentsLikes = array();
                    if ($isCommentLike) {
                        $commentsLikes = self::getAllLikesCommentsFromUser($type);
                    }

                    /*$groupId = 0;
                    $groupUserId = 0;
                    if ($typeGallery == 'edge'){
                        $sql = "SELECT *
                                  FROM `{$tableG}`
                                 WHERE `{$fieldIdG}` = " . to_sql($pid);
                        $mediaInfo = DB::row($sql, 0, true);
                        if ($mediaInfo) {
                            $groupId = $mediaInfo['group_id'];
                            $groupUserId = $mediaInfo['user_id'];
                        }
                    }*/

					$html->parse('comment_block_start');
                    if ($count > 0) {
                        $i = 0;
                        foreach ($comments as $key => $comment) {
                            $comment['item_group_id'] = $groupId;
                            //for ($i = 0; $i < $count; $i++) {
                            if ($lastId !== false && $i == $numberComments) {
                                break;
                            }
                            if ($type == 'video') {
                                $comment['comment'] = $comment['text'];
                                $comment['date'] = $comment['dt'];
                            }
                            $commentInfo = self::prepareDataComment($comment, $type);
                            if (!$commentInfo){
                               continue;
                            }
                            $commentInfo['photo_user_id'] = $row['user_id'];

                            if ($isCommentReply) {
                                $parseShowEventReplyCommentId = 0;
                                if ($showEventReplyCommentId && $showCommentParentId == $comment['id']) {
                                    $parseShowEventReplyCommentId = $showEventReplyCommentId;
                                }
                                self::parseRepliesComments($html, $comment['id'], $comment['replies'], false, $commentsLikes, $type, false, $parseShowEventReplyCommentId);
                            }

                            if ($i > ($numberComments - 1)) {
                                $html->parse('comment_hide', false);
                            }
                            if ($isCommentLike && isset($commentsLikes[$comment['id']])) {
                                $commentInfo['like'] = 1;
                            }

                            self::parseComment($html, $commentInfo, 'comment', $type);
                            //}
                            $i++;
                            //}
                        }
                        if ($loadMore === 0) {
                            if ($isCommentReply) {
                                $whereSql .= ' AND `parent_id` = 0';
                            }
                            $count = DB::count($table, "`{$fieldId}` = " . to_sql($pid) . $whereSql);
                        }
                    }

					$html->parse('comment_block_end');
                    if ($count > $numberComments) {
                        $blockLoadMoreNumber = 'load_more_comments_number';
                        if ($html->blockExists($blockLoadMoreNumber)) {
                            $html->setvar("{$blockLoadMoreNumber}_title", l('view_previous_comments'));
                            $vars = array(
                                'view_number' => $numberComments,
                                'all_number' => $count
                            );
                            $html->setvar($blockLoadMoreNumber, lSetVars('view_previous_comments_number', $vars));
                            $html->parse($blockLoadMoreNumber, false);
                        }

                        $html->setvar('load_more_comments_count', lSetVars('load_more_comments', array('count' => $count - $numberComments)));
                        $html->parse('load_more_comments');
                    } else {
                        $html->parse('items_comment_no_border');
                    }
            } else {
                $html->parse('items_comment_no_border');
                if ($html->blockExists('frm_comments_hide')) {
                    $html->parse('frm_comments_hide', false);
                }
            }
        }
    }


    static function parseCommentsVideo(&$html, $pid)
    {
        global $g_user;
        $video = DB::one('vids_video', '`id` = ' . to_sql($pid, 'Number'));
        if ($video) {
            if ($html->varExists('video_id_comments')) {
                $html->setvar('video_id_comments', $pid);
            }

            if ($video['private'] == '0'
                || User::isFriend(guid(), $video['user_id'])
                || $video['user_id'] == $g_user['user_id']) {//For Urban and Impact only public albums - check is not needed
                    //if(self::$videoAddView) {//self::$videoAddView - not used
                        CStatsTools::count('videos_viewed');
                        CVidsTools::viewVideoByIdAndUserId($pid, $video['user_id']);
                    //}

                    DB::query("SELECT * FROM `vids_comment` WHERE `video_id` = " . to_sql($pid, 'Number') . " ORDER BY id DESC");
                    $count = DB::num_rows();
					$html->parse('comment_block_start');
                    if ($count > 0) {
                        $commentInfo = array();
                        for ($i = 0; $i < $count; $i++) {
                            if ($comment = DB::fetch_row()) {
                                $user = User::getInfoBasic($comment['user_id'], false, 2);
                                if (!$user) {
                                    continue;
                                }
                                $commentInfo['id'] = $comment['id'];
                                $commentInfo['user_id'] = $comment['user_id'];
                                $commentInfo['photo_user_id'] = $video['user_id'];
                                $commentInfo['send'] = $comment['send'];

                                $commentInfo['comment'] = $comment['text'];
                                //$commentInfo['date'] = pl_date('j F Y', $comment['date']);
                                $commentInfo['date'] = $comment['dt'];
                                $commentInfo['display_profile'] = User::displayProfile();
                                $commentInfo['user_name'] = $user['name'];
                                $commentInfo['user_photo'] = User::getPhotoDefault($comment['user_id'], "r", false, $user['gender']);
								$commentInfo['user_photo_id'] = User::getPhotoDefault($comment['user_id'], "r", true);

                                if ($i > 19) {
                                    $html->parse('comment_hide', false);
                                }
                                self::parseComment($html, $commentInfo, 'comment', 'video');
                            }
                        }
                    }
					$html->parse('comment_block_end');
                    if ($count > 20) {
                        $html->setvar('load_more_comments_count', lSetVars('load_more_comments', array('count' => $count - 20)));
                        $html->parse('load_more_comments');
                    } else {
                        $html->parse('items_comment_no_border');
                    }
            } else {
                $html->parse('items_comment_no_border');
            }

            $html->setvar('comment_current_user_profile_link', User::url(guid()));
        }
    }

    static function parseComment(&$html, $comment, $block = 'comment', $type = 'photo', $isWall = false)
    {
        global $g_user;

        if (!empty($comment)) {
            $cmd = get_param('cmd');

            if ($block == 'comments_reply_item' && Wall::$commentReplyCustomClass == 'comment_attach_reply_one_add') {//Update wall - download instead of remote
                if (isset(Wall::$commentsReplyParse[$comment['id']])) {
                    return;
                }
            }

            $commentMsg = $comment['comment'];

            if (!Common::isOptionActiveTemplate('no_rating_photos')) {
                $rating = grabs($commentMsg, '{rating:', '}');
                if (!empty($rating) && isset($rating[0])) {
                    $ratingInfo = explode(':', $rating[0]);
                    $commentMsg = lSetVars('left_rating', array('rating' => $ratingInfo[0]));
                }
            }

            if ($html->varExists("{$block}_current_user_id")) {
                $guid = guid();
                $groupId = 0;
                if (isset($comment['item_group_id']) && $comment['item_group_id']) {
                    $groupId = $comment['item_group_id'];
                }
                $userData = User::getDataUserOrGroup($guid, $groupId, $g_user);
                $vars = array(
                    'current_user_id'    => $guid,
                    'current_group_id'   => $userData['group_id'],
                    'current_user_name'  => $userData['name'],
                    'current_user_url'   => $userData['url'],
                    'current_user_photo' => $userData['photo']
                );
                $html->assign($block, $vars);
            }

            $html->setvar("{$block}_id", $comment['id']);
            $parentId = isset($comment['parent_id']) ? $comment['parent_id'] : 0;
            $html->setvar("{$block}_parent_id", $parentId);

            $isPrepareCommentGallery = true;
            if ($isWall) {
                $isPrepareCommentGallery = false;
            }
            $text = Wall::prepareComment($commentMsg, $isPrepareCommentGallery);
            //$text = nl2br(Common::parseLinksSmile($commentMsg));

            $html->setvar("{$block}_text", $text);
            $html->setvar("{$block}_display_profile", $comment['display_profile']);
            $html->setvar("{$block}_user_id", $comment['user_id']);
            $html->setvar("{$block}_user_id_group", isset($comment['group_id']) ? $comment['user_id'] . '_' . $comment['group_id'] : $comment['user_id']);
            $html->setvar("{$block}_user_name", $comment['user_name']);
            $html->setvar("{$block}_user_photo", $comment['user_photo']);

            if (isset($comment['user_group_owner'])) {
                $html->setvar("{$block}_user_group_owner", $comment['user_group_owner']);
            }

            $html->setvar("{$block}_send", $comment['send']);

            if ($block == 'comments_reply_item') {
                $html->setvar("{$block}_custom_class", Wall::$commentReplyCustomClass);
            }


            if ($type == 'video' || $type == 'live' || self::isVideo($comment['id']) || self::isVideoEdge($comment['id'])) {
                if ($type == 'live') {
                    $html->setvar("{$block}_live_id", LiveStreaming::$liveId);
                }
                $type = 'video';
            }

            $html->setvar("{$block}_url_page_liked", Common::getOption('url_main', 'path') . Common::pageUrl("{$type}_liked_comment", null, $comment['id']));

            $blockUpdater = "{$block}_update_counter";
            if (in_array($cmd, array('photo_comment_add', 'blogs_post_comment_add', 'live_stream_comment_add')) && $html->blockExists($blockUpdater)) {//EDGE
                $isParse = false;
                if (isset($comment['count_comments'])) {
                    $html->setvar("{$block}_count_comments", $comment['count_comments']);
                    $isParse = true;
                }
                if (isset($comment['count_comments_replies'])) {
                    $html->setvar("{$block}_count_comments_replies", $comment['count_comments_replies']);
                    $isParse = true;
                }
                if ($isParse) {
                    $html->parse($blockUpdater, false);
                }
            }

            if (Common::isOptionActiveTemplate('gallery_comment_time_ago')) {
                $date = timeAgo($comment['date'], 'now', 'string', 60, 'second');
            } else {
                $date = Common::dateFormat($comment['date'], 'photo_comment_date');
            }
            $html->setvar("{$block}_time_full", toAttr(Common::dateFormat($comment['date'], 'photo_date')));
            $html->setvar("{$block}_date", $date);

            if ($html->blockExists('comment_plug_private_photos')) {
                if (User::isVisiblePlugPrivatePhotoFromId($comment['user_id'], $comment['user_photo_id'])) {
                    $html->parse('comment_plug_private_photos', false);
                } else {
                    $html->clean('comment_plug_private_photos');
                }
            }

            $html->setvar("{$block}_user_url", isset($comment['user_url']) ? $comment['user_url'] : User::url($comment['user_id']));
            if($comment['display_profile']!='profile'){
				$html->parse('old_url');
				$html->clean('seo_url');
            } else {
                $html->setvar("{$block}_user_profile_link", User::url($comment['user_id']));
				$html->parse('seo_url');
				$html->clean('old_url');
            }

            if (Common::isOptionActiveTemplate('gallery_comment_like')
					|| Common::isOptionActiveTemplate('gallery_comment_like_template')) {
                $likeTitle = l('like');
                $likeTitleAlt = '';
                $likeValue = 1;
                if (isset($comment['like'])) {
                    $likeTitle = l('liked');
                    $likeTitleAlt = l('unlike');
                    $likeValue = 0;
                }
                $html->setvar("{$block}_like_title", $likeTitle);
                $html->setvar("{$block}_like_title_alt", $likeTitleAlt);
                $html->setvar("{$block}_like", $likeValue);

                $countLikes = 0;
                if (isset($comment['count_likes'])) {
                    $countLikes = $comment['count_likes'];
                }
                $html->subcond(!$countLikes, "{$block}_likes_hide");
                $html->setvar("{$block}_count_like", $countLikes);

                $countLikesUsers = '';
                if (isset($comment['count_likes_users'])) {
                    $countLikesUsers = $comment['count_likes_users'];
                }
                $html->setvar("{$block}_count_like_users", $countLikesUsers);
            }

            $isMyComment = $g_user['user_id'] == $comment['user_id'];
			if ($isMyComment) {
				$html->setvar("{$block}_user_photo_id", $comment['user_photo_id']);
				$html->parse('data_my_photo');
			} else {
				$html->clean('data_my_photo');
			}

            if ($isWall) {
                Wall::parseCommentDelete($html, $comment, "{$block}_delete");
            } else {
                if ($isMyComment || $comment['photo_user_id'] == $g_user['user_id']) {
                    $html->parse("{$block}_delete", false);
                } else {
                    $html->clean("{$block}_delete");
                }
            }

            if ($block == 'comments_reply_item' && Wall::$commentReplyCustomClass == 'comment_attach_reply_one') {//Update wall
                Wall::$commentsReplyParse[$comment['id']] = 1;
            }
            $html->parse($block, true);
        }
    }

    static function getNumberPhotos($uid = null, $byOfVisible = true, $byTypeOfAccess = false)
    {
        if ($uid === null) {
            $uid = guid();
        }

        $filed = '`private`';
        $group = '';
        $where = '';
        if ($byOfVisible) {
            $filed = '`visible`';
            $group = ' GROUP BY `visible`';
        } elseif ($byTypeOfAccess) {
            $group = ' GROUP BY `private`';
        } else {
            $where = ' AND `visible` != "P"';
        }

        $numberPhotos = array();
        $sql = 'SELECT COUNT(*) as count, ' . $filed . ' as params
                  FROM `photo`
                 WHERE `user_id` = ' . to_sql($uid) . ' AND `group_id` = 0'
                     . $where
                     . $group;
        $items =  DB::rows($sql);
        if ($byOfVisible) {
            $numberPhotos = array('Y' => 0, 'N' => 0, 'P' => 0, 'Nudity' => 0);
            foreach ($items as $item) {
                $numberPhotos[$item['params']] = $item['count'];
            }
            $numberPhotos['all'] = $numberPhotos['Y'] + $numberPhotos['N'] + $numberPhotos['Nudity'];
            if (!Common::isOptionActive('photo_approval')) {
                $numberPhotos['Y'] += $numberPhotos['N'];
                $numberPhotos['N'] = 0;
            }
        } elseif ($byTypeOfAccess) {
            foreach ($items as $item) {
                $access = $item[1] == 'Y' ? 'private' : 'public';
                $numberPhotos[$access] = $item[0];
            }
        } else {
            $numberPhotos['all'] = 0;
            if (isset($items[0])) {
                $numberPhotos['all'] = $items[0]['count'];
            }
        }
        return $numberPhotos;
    }

    function parseBlock(&$html)
	{
        global $g, $g_user;

        $cmd = get_param('cmd');

        if ($cmd == 'photo_comment_add'  || $cmd == 'set_rate_photo') {
            $comment = self::addComment($cmd != 'set_rate_photo');
            if (!empty($comment)) {
                $block = 'comment';
                $isReply = get_param_int('reply_id');
                if ($isReply) {
                    $block = 'comments_reply_item';
                }

                $photoId = get_param('photo_id');
                $isVideo = self::isVideo($photoId);
                $type = $isVideo ? 'video' : 'photo';

                $typeGallery = Common::getOptionTemplate('gallery_type');
                if ($typeGallery == 'edge'){
                    $pid = get_param('photo_id');
                    if ($type == 'video') {
                        $pidV = str_replace('v_', '', $pid);
                        $row = DB::one('vids_video', '`id` = ' . to_sql($pidV));
                    } else {
                        $row = DB::one('photo', '`photo_id` = ' . to_sql($pid));
                    }
                    $groupId = $row['group_id'];
                    $comment['item_group_id'] = $groupId;
                    $comment = self::prepareDataComment($comment, $type);
                }

                self::parseComment($html, $comment, $block, $type);
                if ($isReply) {
                    $html->parse('comments_reply_list');
                }
            }
        } elseif ($cmd == 'get_photo_comment') {
            $pid = get_param('photo_id', 0);
            if (get_param_int('get_data_edge')) {
                $guid = guid();
                $uid = get_param_int('uid');
                $groupId = get_param_int('group_id');
                $typeOrderDefault = Common::getOption('list_photos_type_order', 'edge_general_settings');
                $order = self::getOrderList($typeOrderDefault);
                $numberPhotoProfile = 0;
                if ($uid) {
                    $photos = self::getPhotoListMobile($uid, $order, '', '', $groupId);
                    $list = $photos['list_photos'];
                    $curPhoto = $photos['cur_photo'];
                    $numberPhoto = self::$allPhotoCount;
                    $numberPhotoProfile = $numberPhoto;
                } else {
                    User::setUserFilterParam(self::getNameFilter(), $typeOrderDefault);
                    $orderFilter = get_param('type_order');
                    if ($orderFilter) {
                        $order = self::getOrderList($orderFilter);
                    }
                    self::$isGetDataWithFilter = true;

                    $photos = self::preparePhotoList(0, $order, '', '', false, false, false, 0, true);
                    self::$isGetDataWithFilter = false;
                    $list = $photos;
                    $curPhoto = $pid;
                    if (!isset($list[$pid])) {
                        $curPhoto = key($list);
                        $numberPhotoProfile = CProfilePhoto::getTotalPhotos($guid);
                    }
                    $numberPhoto = self::$allPhotoCount;
                }

                $isDelete = $pid != $curPhoto;
                $photoDefault = 0;
                if ($isDelete) {
                    $photoDefault = User::getPhotoDefault($guid, 'r', true);
                }
                $html->setvar('photos_info', json_encode($list));
                $html->setvar('cur_photo', $curPhoto);
                $html->setvar('count', $numberPhoto);
                $html->setvar('count_profile', $numberPhotoProfile);
                $html->setvar('count_profile_title', lSetVars('edge_column_photos_title', array('count' => $numberPhotoProfile)));
                $html->setvar('photo_default', $photoDefault);
                $html->setvar('is_delete', intval($isDelete));

                $html->setvar('page_preload_stop', intval(self::$stopPreloadPhoto));
                $html->setvar('page_preload_limit', json_encode(self::$preloadPhotoLimit));
                $html->setvar('page_preload_update', get_param('page_preload_direct'));
            }

            if ($pid) {
                self::parseComments($html, $pid);
            }
        }  elseif ($cmd == 'get_video_comment') {
            $pid = get_param('photo_id', 0);
            $guid = guid();
            if (get_param_int('get_data_edge')) {
                $uid = get_param_int('uid');
                $groupId = get_param_int('group_id');
                /*$vids = 0;
                if ($uid) {
                    $vids = CProfileVideo::getVideosList('', '', $uid, true);
                } else {
                    $vids = array();
                    $order = get_param('type_order', Common::getOption('list_videos_type_order', 'edge_general_settings'));
                    User::setUserFilterParam('videos_filters', $order);
                    CProfileVideo::$isGetDataWithFilter = true;
                    $vids = CProfileVideo::getVideosList($order, '', 0, true);
                    CProfileVideo::$isGetDataWithFilter = false;
                    /*$numberPhoto = count($photos);
                    $curPhoto = $pid;*/
                /*}*/
                $defaultIsYourVideoBrowse = Common::getOption('show_your_video_browse_videos', 'edge_member_settings');
                $g['edge_member_settings']['show_your_video_browse_videos'] = 'Y';
                $isShowMyVideo = Common::isOptionActive('show_your_video_browse_videos', 'edge_member_settings');

                $showAllMyVideo = false;
                if (!$uid) {
                   $showAllMyVideo = true;
                }
                $vids = CProfileVideo::getVideosList('', 1, $uid, false, false, $pid, '', $groupId, $showAllMyVideo);
                $g['edge_member_settings']['show_your_video_browse_videos'] = $defaultIsYourVideoBrowse;

                $numberVids = 0;

                $pidList = 'v_' . $pid;
                $isDelete = 0;
                if(!isset($vids[$pidList])) {
                    $pidList = key($vids);
                    $pid = str_replace('v_', '', $pidList);
                    $isDelete = 1;
                    if ($uid) {
                        $numberVids = CProfileVideo::getTotalVideos($uid, $groupId);
                    } else {
                        $numberVids = CProfileVideo::getTotalVideos($guid);
                    }
                }

                $html->setvar('photos_info', json_encode($vids));
                $html->setvar('cur_photo', $pidList);
                $html->setvar('count', $numberVids);
                $html->setvar('count_profile', $numberVids);
                $html->setvar('count_profile_title', lSetVars('edge_column_videos_title', array('count' => $numberVids)));
                $html->setvar('is_delete', $isDelete);

                $html->setvar('page_preload_stop', 1);
                $html->setvar('page_preload_limit', json_encode(array()));
            }

            if ($pid) {
                if (Common::isOptionActiveTemplate('gallery_comment_replies')) {
                    self::parseComments($html, $pid, 'video');
                } else {
                    self::parseCommentsVideo($html, $pid);
                }
            }
        } elseif ($cmd == 'get_comment_replies') {
            self::parseCommentsReplies($html);
        } elseif($cmd == 'pp_profile_gallery_video') {

            $uid = get_param('uid', 0);
            $offset = get_param('photo_offset');//not used
            $photoId = 'v_'.get_param('photo_id',0);
            self::prepareVideoList($uid, '`id` DESC');
            self::preparePhotoList($uid, '`photo_id` DESC');

//            var_dump(self::$allVideoInfo);exit;
            //echo $photoId.'  '.$uid.'  '.count(self::$allVideoInfo);
            if ($uid && $photoId && (!empty(self::$allVideoInfo) || !empty(self::$allPhotoInfo))) {
                $html->setvar('cur_user_id', $g_user['user_id']);
                $html->setvar('friend_id', '0');
                $html->setvar('user_id', $uid);
                $html->setvar('photos_info', json_encode(self::$allVideoInfo+self::$allPhotoInfo));
                $html->setvar('display_profile', User::displayProfile());
                if ($uid == $g_user['user_id']) {
                    $whosePhotos = l('your_videos');
                    $html->parse('photo_edit_desc');
                    $html->parse('photo_edit_desc_frm');
                    $html->parse('photo_edit_desc_js');
                } else {
                    $userName = User::getInfoBasic($uid, 'name');
                    $whosePhotos = lSetVars('whose_videos', array('name' => $userName));
                }

                $html->setvar('whose_videos', $whosePhotos);

                $count = count(self::$allVideoInfo);
                $html->setvar('count', $count);
                $html->setvar('count_public', count(self::$publicVideo));

                if (isset(self::$allVideoInfo[$photoId]['offset'])) {
                    $offset = self::$allVideoInfo[$photoId]['offset'];
                    $isDelete = false;
                }else{
                    $firstPhoto = current(self::$allVideoInfo);
                    $photoId = $firstPhoto['photo_id'];
                    $offset = $firstPhoto['offset'];
                    $isDelete = true;
                }
                $html->setvar('is_delete', $isDelete);
                $vars = array('offset' => $count - $offset, 'num' => $count);
                $html->setvar('position_photo', lSetVars('position_photo', $vars));
                $i = 0;
                if ($count > 0) {
                    $html->setvar('photo_id', $photoId);
                    $blockCarousel = 'photo_carousel_item';
                    foreach (self::$allVideoInfo as $id => $photo) {
                        $idN = str_replace('v_', '', $id);
                        $idV =  'v_' . $id;
                        $isFriend = User::isFriend(guid(), $photo['user_id']);
                        $isMyPhoto = $photo['user_id'] == $g_user['user_id'];
                        $html->setvar('carousel_title', $photo['description']);
                        if ($photoId == $id) {
                            if ($g_user['user_id'] == $uid) {
								if ($photo['visible'] == 'Y') {
									$html->parse('photo_not_checked_hide');
								}
								$html->parse('photo_not_checked');

                                if ($photo['private'] == 'Y'
                                     || $photo['visible'] == 'N' || $g_user['video_greeting']==$idN) {
                                    $html->parse('make_profile_video_hide');
                                }
                                $html->parse('make_profile_video');
                            }
                            $html->setvar('photo_main_id', $id);


                            $isAccessPrivate = $photo['private'] == 0 || $isFriend || $isMyPhoto;
                            if ($isAccessPrivate)
                            {
                                if (empty($photo['description']) && $g_user['user_id'] == $uid) {
                                    $description = l('click_here_to_add_a_photo_caption');
                                } else {
                                    $description = $photo['description'];
                                    $html->parse('photo_desc_color');
                                }
								$html->setvar('photo_description_attr',  toAttrL($description));
                                $html->setvar('photo_description', $description);
                            } else {
                                $html->parse('comment_post_frm');
                            }

                            $html->setvar('photo_b', $photo['src_b']);
                            self::parseCommentsVideo($html, $idN);

                            $html->setvar($blockCarousel . '_set', 'active');
                        } else {
                            $html->setvar($blockCarousel . '_set', '');
                        }

                        $html->setvar('photo_id', $id);
                        $html->setvar('photo_r', $photo['src_r']);


                        $html->setvar($blockCarousel . '_status', ($photo['private'] == 'Y') ? 'private' : 'public');
                        $html->parse($blockCarousel, true);
                        $i++;
                    }
                    if ($g_user['user_id']) {
                        $html->setvar('current_user_photo', User::getPhotoDefault($g_user['user_id'], 'r', false, $g_user['gender']));
                        $html->setvar('current_user_photo_id', User::getPhotoDefault($g_user['user_id'], 'r', true));
                    }


                    if ($count == 1) {
                        $html->parse('photo_carousel_arrows_hide'); // Not ???
                    } elseif ($count < 8) {
                        for ($j = 0; $j < (8 - $i); $j++) {
                            $html->parse("photo_empty_carousel_item", true);
                        }
                    }
                }

            }

        } elseif ($cmd == 'pp_profile_gallery_photo') {
            $uid = get_param('uid', 0);
            $offset = get_param('photo_offset');//not used
            $photoId = get_param('photo_id',0);
            self::preparePhotoList($uid, '`photo_id` DESC');
            self::prepareVideoList($uid, '`id` DESC');
            if ($uid && $photoId && (!empty(self::$allPhotoInfo) || !empty(self::$allVideoInfo))) {
                $html->setvar('cur_user_id', $g_user['user_id']);
                /* Access to private */
                $isFriend = User::isFriend($uid, $g_user['user_id']);
                $html->setvar('friend_id', $isFriend);
                $html->setvar('user_id', $uid);
                if (!$isFriend) {
                    $isParseOr = false;
                    if (!empty(self::$publicPhoto)) {
                        $html->parse('scip_private');
                        $isParseOr = true;
                    }
                    if (!User::isFriendRequestExists($uid, $g_user['user_id'])) {
                        $mode = 1;
                        if (!$isParseOr) {
                            $mode = 0;
                            $html->parse('request_private_center');
                        }
                        $html->setvar('modeAnimate', $mode);
                        $html->parse('request_private');
                    } else {
                        $isParseOr = false;
                    }
                    if ($isParseOr) {
                        $html->parse('request_private_or');
                    }
                    $userGender = User::getInfoBasic($uid, 'gender');
                    $markedPhotosPrivate = lSetVars('the_user_has_marked_of_his_photos_as_private_' . $userGender, array('count' => count(self::$privatePhoto)), 'toJsL');
                    $html->setvar('marked_photos_private_set', $markedPhotosPrivate);
                    $html->parse('request_access_title');
                    $html->parse('request_access_action');
                }
                /* Access to private */

				$html->setvar('photos_info', json_encode(self::$allPhotoInfo + self::$allVideoInfo));
                $html->setvar('display_profile', User::displayProfile());
                if ($uid == $g_user['user_id']) {
                    $whosePhotos = l('your_photos');
                    $html->parse('photo_edit_desc');
                    $html->parse('photo_edit_desc_frm');
                    $html->parse('photo_edit_desc_js');
                } else {
                    $userName = User::getInfoBasic($uid, 'name');
                    $whosePhotos = lSetVars('whose_photos', array('name' => $userName));
                }

                $html->setvar('whose_photos', $whosePhotos);

                $count = count(self::$allPhotoInfo);
                $html->setvar('count', $count);
                $html->setvar('count_private', count(self::$privatePhoto));
                $html->setvar('count_public', count(self::$publicPhoto));

                if (isset(self::$allPhotoInfo[$photoId]['offset'])) {
                    $offset = self::$allPhotoInfo[$photoId]['offset'];
                    $isDelete = false;
                }else{
                    $firstPhoto = current(self::$allPhotoInfo);
                    $photoId = $firstPhoto['photo_id'];
                    $offset = $firstPhoto['offset'];
                    $isDelete = true;
                }
                $html->setvar('is_delete', $isDelete);


                $vars = array('offset' => $count - $offset, 'num' => $count);
                $html->setvar('position_photo', lSetVars('position_photo', $vars));

                $i = 0;
                if ($count > 0) {
                    $html->setvar('photo_id', $photoId);
                    $blockCarousel = 'photo_carousel_item';
                    foreach (self::$allPhotoInfo as $id => $photo) {

                        $isFriend = User::isFriend(guid(), $photo['user_id']);
                        $isMyPhoto = $photo['user_id'] == $g_user['user_id'];

                        if ($photoId == $id) {
                            if ($g_user['user_id'] == $uid) {
								if ($photo['visible'] == 'Y') {
									$html->parse('photo_not_checked_hide');
								}
								$html->parse('photo_not_checked');

                                if ($count == 1 || $photo['private'] == 'Y'
                                    || $photo['default'] == 'Y' || (self::isPhotoOnVerification($photo['visible']))) {
                                    $html->parse('make_profile_photo_hide');
                                }
                                $html->parse('make_profile_photo');
                            }
                            $html->setvar('photo_main_id', $id);

                            if ($g_user['user_id']) {
                                $html->setvar('current_user_photo', User::getPhotoDefault($g_user['user_id'], 'r', false, $g_user['gender']));
                                $html->setvar('current_user_photo_id', User::getPhotoDefault($g_user['user_id'], 'r', true));
                            }

                            $isAccessPrivate = $photo['private'] == 'N' || $isFriend || $isMyPhoto;
                            if ($isAccessPrivate)
                            {
                                if (empty($photo['description']) && $g_user['user_id'] == $uid) {
                                    $description = l('click_here_to_add_a_photo_caption');
                                } else {
                                    $description = $photo['description'];
									$html->parse('photo_desc_color');
                                }
								$html->setvar('photo_description_attr', toAttrL($description));
                                $html->setvar('photo_description', $description);
                            } else {
                                $html->parse('comment_post_frm');
                            }

                            $html->setvar('photo_b', $photo['src_b']);
                            self::parseComments($html, $id);

                            $html->setvar($blockCarousel . '_set', 'active');
                        } else {
                            $html->setvar($blockCarousel . '_set', '');
                        }

                        $html->setvar('photo_id', $id);
                        $html->setvar('photo_r', $photo['src_r']);

                        /*Lock on the pictures private*/
                        if ($photo['private'] == 'Y' && ($isFriend || $isMyPhoto)) {
                            $html->parse($blockCarousel . '_private', false);
                        } else {
                            $html->clean($blockCarousel . '_private');
                        }
                        /*Lock on the pictures private*/

                        $html->setvar($blockCarousel . '_status', ($photo['private'] == 'Y') ? 'private' : 'public');
                        $html->parse($blockCarousel, true);
                        $i++;
                    }

                    if ($count == 1) {
                        $html->parse('photo_carousel_arrows_hide'); // Not ???
                    }
                    if ($count < 12) {
                        for ($j = 0; $j < (12 - $i); $j++) {
                            $html->parse("photo_empty_carousel_item", true);
                        }
                    }
                }
            }
        }
        parent::parseBlock($html);

	}

    static function isVideo($pid)
    {
        return (strpos($pid,'v_')!==false);
    }

    static function isVideoEdge($pid)
    {
        return (strpos($pid,'_v')!==false);
    }

    static function getKeyVideoId($pid)
    {
        return 'v_' . $pid;
    }

    static function prepareVideoId($pid)
    {
        return str_replace('v_', '', $pid);
    }

    public static function moderatorVisibleFilter()
    {
        $filter = '`visible` = "N"';
        if(Common::isOptionActive('nudity_filter_enabled')) {
            $filter = '`visible` IN ("N", "Nudity")';
        }

        return $filter;
    }

    public static function wherePhotoIsVisible($table = 'photo', $condition = 'AND')
    {
        if($table) {
            $table .= '.';
        }

        $where = $table . '`visible` != "P"';

        if(Common::isOptionActive('photo_approval')) {
            $where = $table . '`visible` = "Y"';
        } elseif(Common::isOptionActive('nudity_filter_enabled')) {
            $where = $table . '`visible` NOT IN ("P", "Nudity")';
        }

        return $condition . ' ' . $where;
    }

    public static function setVideoAddView($videoAddView)
    {
        self::$videoAddView = $videoAddView;
    }

    public static function subtractPhotoFileSizes($baseFileName)
    {
        Common::saveFileSize(self::getFileNamesList($baseFileName), false);
    }

    public static function addPhotoFileSizes($baseFileName)
    {
        Common::saveFileSize(self::getFileNamesList($baseFileName));
    }

    public static function getFileNamesList($baseFileName)
    {
        $fileNames = array();
        foreach(self::$sizes as $size) {
            $fileNames[] = $baseFileName . $size . '.jpg';
        }

        return $fileNames;
    }

    public static function getSizes()
    {
        return self::$sizes;
    }

    public static function createPhotoSizesPreviews($baseFileName, $imageSource, $photoId = 0)
    {
        global $g;

        $im = new Image();

        $sizesCount = count(self::$sizesPreviews);

        foreach(self::$sizesPreviews as $size => $sizeConfigOption) {
            if($sizesCount == 1) {
                $im->image = $imageSource;
            } else {
                $im->image = Image::copyImageResource($imageSource);
            }
            if ($im->image) {

                if($size == 'b') {
                    $watermarkParams = self::watermarkParams();
                    $im->resizeWH($g['image'][$sizeConfigOption . '_x'], $g['image'][$sizeConfigOption . '_y'], false, $g['image']['logo'], $watermarkParams['font_size'], $watermarkParams['file'], $watermarkParams['position']);
                } else {
                    $im->resizeCropped($g['image'][$sizeConfigOption . '_x'], $g['image'][$sizeConfigOption . '_y'], $g['image']['logo'], 0);
                }
                $fileName = $baseFileName . $size . '.jpg';
                $im->saveImage($fileName, $g['image']['quality']);
                @chmod($fileName, 0777);

                if($photoId && ($size == self::$saveSizeInDatabase)) {
                    self::updatePhotoSizeInDatabase($photoId, $im->width, $im->height);
                }

                $im->clearImage();
            }
        }
    }

    public static function deleteFiles($baseFileName)
    {
        $files = self::getFileNamesList($baseFileName);
        foreach($files as $file) {
            @unlink($file);
        }
    }

    public static function createBasePhotoFile($image, $baseFileName, $sourceFile)
    {
        global $g;

        $watermarkParams = self::watermarkParams();

        // make copy of source file if enough memory or load from file

        $first = true;

        foreach(self::$sizesBasePhoto as $size => $sizeConfig) {

            if($first) {

                if((memory_get_usage() * 2 + 1024 * 1024 * 16) < getMemoryLimitInBytes()) {
                    $image->imageCopy = Image::copyImageResource($image->image);
                } else {
                    // use only if need prevent loading image from drive when low memory
                    //$image->saveImageResourceCopy = true;
                }

            } else {
                if($image->imageCopy) {
                    $image->image = $image->imageCopy;
                } else {
                    $image->loadImage($sourceFile);
                }
                $image->saveImageResourceCopy = true;
            }

            $image->resizeWH($g['image'][$sizeConfig . '_x'], $g['image'][$sizeConfig . '_y'], false, $g['image']['logo'], $watermarkParams['font_size'], $watermarkParams['file'], $watermarkParams['position']);
            $fileName = $baseFileName . $size . '.jpg';
            if(!$image->saveImage($fileName, $g['image']['quality'])) {
                return false;
            }
            $image->clearImage();

            @chmod($fileName, 0777);

            $first = false;
        }

        return true;
    }

    public static function updatePhotoSizeInDatabase($photoId, $width, $height)
    {
        $sql = 'UPDATE `photo`
            SET `version` = `version` + 1,
                `width` = ' . to_sql($width) . ',
                `height` = ' . to_sql($height) . '
            WHERE photo_id = ' . to_sql($photoId);
        DB::execute($sql);
    }

    public static function watermarkParams()
    {
        global $g;

        $watermarkFontSize = $g['image']['logo_size'];
        $watermarkFile = '';
        $watermarkPosition = '';
        if (Common::getOption('watermark_type', 'image') == 'image') {
            $watermarkFontSize = 0;
            $watermarkFile = $g['path']['dir_files'] . 'watermark.png';
            if (!file_exists($watermarkFile)) {
                $watermarkFile = '';
            } else {
                $watermarkPosition = Common::getOption('watermark_position', 'image');
            }
        }

        $watermarkParams = array(
            'font_size' => $watermarkFontSize,
            'position' => $watermarkPosition,
            'file' => $watermarkFile,
        );

        return $watermarkParams;
    }

    public static function getAndUpdatePhotoSize($photoInfo, $photoMain, $defaultWidth)
    {
        global $g;

        $photoFileSizes = array($defaultWidth, $defaultWidth);

        if($photoInfo['width'] == 0 || $photoInfo['height'] == 0) {
            $tmpPhotoPath = explode('?', $photoMain);
            $filePhoto = $g['path']['dir_files'] . $tmpPhotoPath[0];
            if(file_exists($filePhoto)) {
                $infoPhoto = @getimagesize($filePhoto);
                if(isset($infoPhoto[1])) {
                    $photoFileSizes = array($infoPhoto[0], $infoPhoto[1]);
                    DB::update('photo', array('width' => $infoPhoto[0], 'height' =>  $infoPhoto[1]), 'photo_id = ' . to_sql($photoInfo['photo_id']));
                }
            }
        } else {
            $photoFileSizes = array($photoInfo['width'], $photoInfo['height']);
        }

        return $photoFileSizes;
    }

    /* All photos site */
    static function getTotalPhotos($uid = 0, $onlyPublic = false, $groupId = 0)
    {

        $whereTags = '';
        if (self::$isGetDataWithFilter) {
            $whereTags = self::getWhereTags('PTR.');
            if ($whereTags == 'no_tags') {
                return 0;
            }
        }

        if ($whereTags) {
            $where = self::getWherePhotosList('PH.', $onlyPublic, $uid, $groupId);
            $sql = 'SELECT COUNT(*) FROM (
                            SELECT COUNT(*)
                              FROM `photo_tags_relations` AS PTR
                              JOIN `photo` AS PH ON PH.photo_id = PTR.photo_id
                             WHERE ' . $where
                                     . $whereTags
                         . ' GROUP BY PH.photo_id) AS PHT';
            return DB::result($sql);
        } else {
            $where = self::getWherePhotosList('', $onlyPublic, $uid, $groupId);
            return DB::count('photo', $where);
        }
    }

    static function getWherePhotosList($table = '', $onlyPublic = false, $uid = 0, $groupId = 0, $showAllMyPhoto = false)
    {
        $guid = guid();
        $where = " {$table}visible != 'P' ";
        $whereGroup = " AND {$table}group_id = " . to_sql($groupId);

        $groupsPhotoList = Groups::getTypeContentList();
        if ($groupsPhotoList) {
            if (!$uid) {
                $showAllMyPhoto = true;
            }
            $whereGroup = " AND {$table}group_id != 0";
            if ($groupsPhotoList == 'group_page') {
                $whereGroup .= " AND {$table}group_page = 1";
            } else {
                $listGroups = Groups::getUserListGroupsSubscribers();
                $whereGroupPrivate = "({$table}group_private = 'Y' AND {$table}group_id IN (" . $listGroups . '))';

                $whereGroup .= " AND {$table}group_page = 0 AND ({$table}group_private = 'N' OR " . $whereGroupPrivate . ")";
                //var_dump_pre($whereGroup);
            }
        }
        $where .= $whereGroup;

        if (!$guid || $uid != $guid) {
            $whereApprove = '';
            if (Common::isOptionActive('photo_approval')) {
                $whereApprove = "{$table}visible = 'Y' ";
            } elseif (Common::isOptionActive('nudity_filter_enabled')) {
                $whereApprove = "{$table}visible IN ('Y', 'N') ";
            }
            if ($whereApprove) {
                if ($showAllMyPhoto) {
                    $whereApprove = "({$whereApprove} OR {$table}user_id = " . to_sql($guid) . ")";
                }
                $where .= ' AND ' . $whereApprove;
            }
        }
        $noPrivatePhoto = Common::isOptionActiveTemplate('no_private_photos');
        if (($onlyPublic && !$noPrivatePhoto) || self::isHidePrivatePhoto()) {
            $where .= " AND {$table}private = 'N' ";
            //User::setNoPhotoPprivateInOffset();
        }
        if ($uid) {
            $where .= " AND {$table}user_id = " . to_sql($uid);
        } elseif ($guid) {
            $isShowMyPhoto = Common::isOptionActive('show_your_photo_browse_photos', 'edge_member_settings');
            $onlyFriends = false;
            if (self::$isGetDataWithFilter) {
                $onlyFriends = get_param_int('only_friends', false);
                if ($onlyFriends) {
                    $friends = User::friendsList($guid, $isShowMyPhoto);
                    if ($friends) {
                        $where .= " AND {$table}user_id IN ({$friends})";
                    }
                }
            }
            if (!$onlyFriends && !$isShowMyPhoto) {
                $where .= " AND {$table}user_id != " . to_sql($guid);
            }
        }
        if (!$uid) {
            $searchQuery = trim(get_param('search_query'));
            if ($searchQuery) {
                $searchQuery = urldecode($searchQuery);
                $where .= " AND {$table}description  LIKE '%" . to_sql($searchQuery, 'Plain') . "%'";
            }
        }

        return $where;
    }

    static function getOrderList($typeOrder = '')
    {
        //$orderBy = 'PH.`private` DESC, PH.`default` ASC, PH.`photo_id` DESC';
        $orderBy = 'PH.`date` DESC, PH.`photo_id` DESC';
        if ($typeOrder == 'order_random') {
            $orderBy = 'RAND()';
        }else if ($typeOrder == 'order_most_commented') {
            $orderBy = 'PH.`count_comments` DESC, PH.`photo_id` DESC';
        }else if ($typeOrder == 'order_most_viewed') {
            $orderBy = 'PH.`count_views` DESC, PH.`photo_id` DESC';
        }

        return $orderBy;
    }


    static function getPhotosList($typeOrder, $onlyPublic = true, $limit = '0, 4', $uid = null, $groupId = 0, $showAllMyPhoto = false)
    {
        global $g;

        $result = array();
        if ($uid === null) {
            $uid = User::getParamUid(0);
        }

        $orderBy = self::getOrderList($typeOrder);
        $photos = self::preparePhotoList($uid, $orderBy, '', $limit, false, false, $onlyPublic, $groupId, $showAllMyPhoto);

        return $photos;
    }

    static public function getTypeOrderPhotosList($notRandom = false)
	{
        global $p;

        $list = array(
            'order_new'      => l('order_new'),
            'order_most_commented' => l('order_most_commented'),
            'order_most_viewed'    => l('order_most_viewed'),
            'order_random'   => l('order_random')
        );
        if ($notRandom) {
            unset($list['order_random']);
        }
        return $list;
    }

    static public function getTagsPhoto($pid)
	{
        $sql = 'SELECT TR.tag_id, T.tag
                  FROM `photo_tags_relations` as TR
                  LEFT JOIN `photo_tags` as T ON TR.tag_id = T.id
                 WHERE TR.photo_id = ' . to_sql($pid) . ' ORDER BY T.id';
        $tagsPhoto = DB::all($sql);
        $tags = array();
        if ($tagsPhoto) {
            foreach ($tagsPhoto as $key => $tag) {
                $tags[$tag['tag_id']] = $tag['tag'];
            }
        }
        return $tags;
    }

    static public function getTagInfo($id)
	{
        if (!$id) {
            return false;
        }
        $tag = DB::one('photo_tags', '`id` = ' . to_sql($id));

        return $tag;
    }


    static public function getWhereTags($table = '', $tags = null)
	{
        if ($tags === null) {
            $tags = trim(get_param('tags'));
        }

        if (!$tags) {
            return '';
        }

        $tags =  explode(',', trim($tags));
        if (!is_array($tags)) {
            return '';
        }

        $whereSql = 'no_tags';
        $where = '';
        $i = 0;
        foreach ($tags as $k => $tag) {
            $tag = trim($tag);
            if ($tag) {
                if ($i) {
                   $where .= ' OR ';
                }
                $where .= '`tag` LIKE "%' . DB::esc_like($tag) . '%"';
            }
            $i++;
        }
        if ($where) {
            $sql = "SELECT `id` FROM `photo_tags` WHERE ({$where})";
            $tagsId = DB::rows($sql);
            $tags = array();
            if ($tagsId) {
                foreach ($tagsId as $k => $tag) {
                    $tags[] = $tag['id'];
                }
                $whereSql = implode(',', $tags);
                $whereSql = " AND {$table}tag_id IN({$whereSql})";
            }
        }

        return $whereSql;
    }

    static public function setMediaViews($id = null, $cmd = null)
	{
        if ($id === null) {
            $id = get_param_int('photo_id');
        }
        if ($cmd === null) {
            $cmd = get_param('cmd');
        }
        if (!$id) {
            return false;
        }
        $type = get_param('type');

        $table = 'photo';
        $where = 'photo_id = ' . to_sql($id);
        if ($cmd == 'get_video_comment' || $type == 'video') {
            $table = 'vids_video';
            $where = 'id = ' . to_sql($id);
        }
        $uid = DB::result('SELECT `user_id` FROM ' . $table . ' WHERE ' . $where);
        if ($uid && $uid != guid()) {
            DB::execute('UPDATE ' . $table . ' SET count_views=count_views+1 WHERE ' . $where);
        }

        return true;
    }

    static public function deleteTags($id, $type = 'photo')
	{
        Common::deleteTags($id, $type);
    }

    static public function updateTags()
	{
        $guid = guid();

        if (!$guid) {
            return false;
        }

        $pid = get_param_int('photo_id');

        $type = get_param('type', 'photo');

        $fieldsSql = "";
        if ($type == 'video') {
            $fieldsSql = ", `live_id`";
            $table = 'vids_video';
            $fieldId = 'id';
            $tableTags = 'vids_tags';
            $tableTagsRelations = 'vids_tags_relations';
            $fieldRelationsId = 'video_id';
            $page = 'vids_list';
        } else {
            $table = 'photo';
            $fieldId = 'photo_id';
            $tableTags = 'photo_tags';
            $tableTagsRelations = 'photo_tags_relations';
            $fieldRelationsId = 'photo_id';
            $page = 'photos_list';
        }


        $sql = "SELECT `{$fieldId}`, `group_id`, `group_page`{$fieldsSql}
                  FROM `{$table}`
                 WHERE `{$fieldId}` = " . to_sql($pid)
               . ' AND `user_id` = ' . to_sql($guid);
        $mediaData = DB::row($sql);
        if (!$mediaData) {
            return false;
        }

        $result = array();
        $tags = trim(get_param('tags'));
        $result['tags_title'] = $tags;
        $tags = explode(',', $tags);
        $tags = array_map('trim', $tags);
        $result['tags'] = $tags;
        $tagsSql = array_map('to_sql', $tags);

        $tagsTemp = array();
        $tagsDelete = array();

        $tagsExists = DB::select($tableTags, '`tag` IN (' . implode(',', $tagsSql) . ')');
        $tagsExistsCount = array();
        foreach ($tagsExists as $key => $item) {
            $tagsTemp[$item['id']] = $item['tag'];
            $tagsExistsCount[$item['id']] = $item['counter'];
        }
        $tagsExists = $tagsTemp;

        $sql = "SELECT TR.tag_id, T.counter
                  FROM `{$tableTagsRelations}` as TR
                  LEFT JOIN `{$tableTags}` as T ON TR.tag_id = T.id
                 WHERE TR.{$fieldRelationsId} = " . to_sql($pid);
        $tagsPhoto = DB::all($sql);
        if ($tagsPhoto) {
            $tagsTemp = array();
            foreach ($tagsPhoto as $key => $item) {
                $tagsTemp[$item['tag_id']] = $item['counter'];
            }
            $tagsPhoto = $tagsTemp;

            foreach ($tagsPhoto as $id => $count) {
                if (!isset($tagsExists[$id])) {
                    $tagsDelete[$id] = $count;
                }
            }
        }

        $tagsUpdate = array();
        foreach ($tags as $key => $tag) {
            if (!$tag) {
                unset($tags[$key]);
                continue;
            }
            $id = array_search($tag, $tagsExists);
            if ($id) {
                unset($tags[$key]);
                if (!isset($tagsPhoto[$id])) {
                    $tagsUpdate[$id] = 1;
                }
            }
        }

        if ($tags) {
            //$rows = array();
            foreach ($tags as $key => $value) {
                DB::insert($tableTags, array('tag' => $value, 'counter' => 1));
                $id = DB::insert_id();

                $data = array("{$fieldRelationsId}" => $pid, 'tag_id' => $id);
                if ($type == 'video') {
                    $data['live_id'] = $mediaData['live_id'];
                }
                DB::insert($tableTagsRelations, $data);
                //$rows[] = array('tag' => $value, 'counter' => 1);
            }
            //DB::insertRows('photo_tags', $rows);
        }

        if ($tagsDelete) {
            foreach ($tagsDelete as $id => $count) {
                DB::delete($tableTagsRelations, "`{$fieldRelationsId}` = " . to_sql($pid) . ' AND `tag_id` = ' . to_sql($id));
                if (intval($count) > 1) {
                    DB::execute("UPDATE {$tableTags} SET counter = counter - 1 WHERE id=" . to_sql($id));
                } else {
                    DB::delete($tableTags, '`id` = ' . to_sql($id));
                }
            }
        }

        if ($tagsUpdate) {
            foreach ($tagsUpdate as $id => $count) {
                $data = array("{$fieldRelationsId}" => $pid, 'tag_id' => $id);
                if ($type == 'video') {
                    $data['live_id'] = $mediaData['live_id'];
                }
                DB::insert($tableTagsRelations, $data);
                DB::execute("UPDATE {$tableTags} SET counter = counter + 1 WHERE id=" . to_sql($id));
            }
        }

        if ($type == 'video') {
            $tags = CProfileVideo::getTags($pid);
        } else {
            $tags = self::getTagsPhoto($pid);
        }

        $tagsData = self::getTagsMedia($tags, $mediaData['group_id'], $mediaData['group_page'], $type == 'video' ? 'vids' : 'photos');
        $result['tags_html'] = $tagsData['html'];

        return $result;
    }
    /* All photos site */

    static public function isHidePrivatePhoto()
    {
        if (!Common::isOptionActiveTemplate('no_private_photos')) {
            return false;
        }
        $optionTemplateName = Common::getTmplName();
        $hidePrivatePhoto = Common::isOptionActive('hide_private_photos', "{$optionTemplateName}_general_settings");
        if ($optionTemplateName == 'edge') {
            $hidePrivatePhoto = $hidePrivatePhoto || IS_DEMO;
        }

        return $hidePrivatePhoto;
    }

    static public function getNameFilter($prf = 'photos')
    {
        $filter = '_filters';
        $groupsPhotoList = Groups::getParamTypeContentList();
        if($groupsPhotoList) {
            $filter = $groupsPhotoList == 'group_page' ? '_pages_filters' : '_groups_filters';
        }

        $filter = $prf . $filter;

        return $filter;
    }

    static public function getTagsMedia($tags, $groupId, $groupPage, $type = 'photos')
    {

        $tagsTitle = '';
        $tagsHtml = '';
        if ($tags) {
            $urlPage = Common::pageUrl("{$type}_list");
            if ($groupId) {
                $urlPage = $groupPage ? Common::pageUrl("pages_{$type}_list") : Common::pageUrl("groups_{$type}_list");
            }
            foreach ($tags as $id => $tag) {
                $tagsHtml .= ', <a href="' . $urlPage . '?tag=' . $id . '">' . $tag . '</a>';
                $tagsTitle .= ', ' . $tag;
            }
            $tagsHtml = substr($tagsHtml, 1);
            $tagsTitle = substr($tagsTitle, 1);
        }

        return array('html' => trim($tagsHtml), 'title' => trim($tagsTitle));
    }
}