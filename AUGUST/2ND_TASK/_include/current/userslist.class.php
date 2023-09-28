<?php
/* (C) Websplosion LLC, 2001-2021

IMPORTANT: This is a commercial software product
and any kind of using it must agree to the Websplosion's license agreement.
It can be found at http://www.chameleonsocial.com/license.doc

This notice may not be removed from the source code. */

class Users_List {

    static $usersFilterModule = false;
    static $fotterBanner = false;

    static function show($where = '', $order = '', $from_add = '', $template = '', $from_group = '', $redirectIfSingle=false)
    {
        global $g;
        global $g_user;
        global $p;
        global $g_info;

        if ($template == '') {
            $template = substr($p, 0, -3) . 'html';
        }

        $optionSet = Common::getOption('set', 'template_options');
		$optionTmplName = Common::getOption('name', 'template_options');
        if ($optionSet == 'urban') {
            $display = get_param('display', 'info');
            if (in_array($display, array('gallery', 'list'))) {
                $display = 'info';
            }
        } else {
            $display = get_param('display', 'empty');
            // general gender param
            //$gender = get_param('gender', guser('default_online_view'));
            $gender = get_param('gender', true);
            $reviewLink = array('info', 'gallery', 'list', 'empty');

            // Initial entry search
            $isSearch = User::isListOrientationsSearch();
            if ($gender === true && $isSearch === true) {
                $gender = guser('default_online_view');
            }

            if ($gender != NULL
                && $isSearch === true
                && $gender != 'B'
                && in_array($display, $reviewLink)
                && UserFields::isActive('orientation')
                && Common::isOptionActive('user_choose_default_profile_view')) {
                $whereGender = 'gender = ' . to_sql($gender, 'Text');
                if ($where != '') {
                    $where .= ' AND ' . $whereGender;
                } else {
                    $where = $whereGender;
                }
            }

            if (guid() && $isSearch) {
                $g_info['users_online'] = User::howOnline(($gender == 'B') ? '' : $gender);
                $defaultGender = ($gender == '') ? 'B' : $gender;
                if ($defaultGender != $g_user['default_online_view']) {
                    $sql = array('default_online_view' => $defaultGender);
                    DB::update('user', $sql, 'user_id = '.guid());
                }
            }

            if (in_array($display, $reviewLink)) {
                if (guid()) {
                    $review = $g_user['review_link'];
                    if ($display == 'empty') {
                        $display = $review;
                    } elseif ($review != $display) {
                        $sql = 'UPDATE `user`
                                   SET `review_link` = ' . to_sql($display, 'Text')
                             . ' WHERE `user_id` = ' . to_sql(guid(), 'Number');
                        DB::execute($sql);
                    }
                } else {
                    $review = get_cookie('review_link');
                    if ($display == 'empty') {
                        $display = ($review == '') ? 'info' : $review;
                    } elseif ($review != $display) {
                        set_cookie('review_link', $display);
                    }
                }
            }
        }

        $userId = get_param('uid', guid());
        $show = get_param('show');
        //var_dump(Wall::isProfileWall($display));
        if (Wall::isProfileWall($display)
            && ((Wall::isOnlySeeFriends($userId) && $display == 'profile') || $display == 'wall')) {
            if ($optionSet == 'urban') {
                redirect('search_results.php?display=profile&uid=' . User::getRequestUserId());
            }
            // use params to find profile
            if ($where) {
                $where = ' WHERE ' . $where;
            }
            if ($order) {
                $order = ' ORDER BY ' . $order;
            }

            $sqlCount = 'SELECT COUNT(u.user_id) FROM user AS u ' . $from_add;
            $sql = $sqlCount . $where;
            $total = DB::result($sql);
            //echo $sql . '<br>';exit;

            if ($total > 0) {
                $offset = (int) get_param("offset", 1);
                if (abs($offset) % $total == 0 and $offset <= 0) {
                    $offset = 1;
                    #echo 'OFFSET 1<br>';
                } elseif ($offset < 1) {
                    $offset = $total - abs($offset) % $total;
                    #echo 'OFFSET 2<br>';
                } elseif ($offset > $total) {
                    $offset = $offset % $total;
                    #echo 'OFFSET 3<br>';
                }

                $on_page = 1;

                $this_page = ceil($offset / $on_page);

                $offsetReal = ($this_page - 1) * $on_page + 1;
                if ($offsetReal < 1) {
                    $offsetReal = 1;
                }

                $sql = "SELECT u.*, DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(birth, '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(birth, '00-%m-%d')) AS age,
                u.state AS state_title, u.country AS country_title, u.city AS city_title,
                IF(u.city_id=" . $g_user['city_id'] . ", 1, 0) +
                IF(u.state_id=" . $g_user['state_id'] . ", 1, 0) +
                IF(u.country_id=" . $g_user['country_id'] . ", 1, 0) AS near
                FROM user AS u
                " . $from_add . $where . $order . ' LIMIT ' . intval($offsetReal - 1) . ',1';

                //echo $sql;exit;

                DB::query($sql);
                if (DB::num_rows() > 0) {
                    $user = DB::fetch_row();
                    $_GET['uid'] = $user['user_id'];
                    $page = Wall_Page::show();
                } else {
                    redirect($p);
                }
            } else {
                redirect($p);
            }
        } else {

            if ($display == 'photo') {
                $template = 'user_profile_photo.html';
            }
            if ($display == 'friends') {
                $template = 'user_profile_photo.html';
            }

            $pIsTextTemplate = false;
            $pTextTemplate = false;

            $isAjaxRequest = (get_param('ajax') && Common::isOptionActive('list_users_info_ajax', 'template_options'));
            /* EDGE */
            $isLikedShowList = in_array($show, array('wall_liked', 'wall_shared', 'wall_liked_comment',
                                                     'photo_liked', 'photo_liked_comment',
                                                     'video_liked', 'video_liked_comment',
                                                     'blogs_post_liked', 'blogs_post_liked_comment'));

            if(!$isAjaxRequest) {
                $page = new CProfilesPageBase('', $g['tmpl']['dir_tmpl_main'] . $template);
            }

            $joinSearchPage = get_param('join_search_page');

            if ($display == 'info') {

                self::$usersFilterModule = true;

                $listTmpl = $g['tmpl']['dir_tmpl_main'] . '_list_users_info.html';

                if (Common::isOptionActive('list_users_rated_me_tmpl_parts', 'template_options')
                    && $p == 'users_rated_me.php') {
                    $listTmpl = array(
                        'main' => $g['tmpl']['dir_tmpl_main'] . '_list_users_rated_me.html',
                        'items' => $g['tmpl']['dir_tmpl_main'] . '_list_users_rated_me_items.html',
                        'pages' => $g['tmpl']['dir_tmpl_main'] . '_list_users_rated_me_pages.html',
                    );

                    if($isAjaxRequest) {
                        $listTmpl['main'] = $g['tmpl']['dir_tmpl_main'] . 'search_results_ajax.html';
                    }
                } elseif (Common::isOptionActive('list_users_viewed_me_tmpl_parts', 'template_options')
                    && $p == 'users_viewed_me.php') {
                    $listTmpl = array(
                        'main' => $g['tmpl']['dir_tmpl_main'] . '_list_users_viewed_me.html',
                        'items' => $g['tmpl']['dir_tmpl_main'] . '_list_users_viewed_me_items.html',
                        'pages' => $g['tmpl']['dir_tmpl_main'] . '_list_users_viewed_me_pages.html',
                    );

                    if($isAjaxRequest) {
                        $listTmpl['main'] = $g['tmpl']['dir_tmpl_main'] . 'search_results_ajax.html';
                    }
                } elseif (Common::isOptionActive('list_users_info_tmpl_base_parts', 'template_options')
                          && in_array($p, array('users_viewed_me.php'))) {
                    $listTmpl = array(
                        'main' => $g['tmpl']['dir_tmpl_main'] . '_list_users_base.html',
                        'items' => $g['tmpl']['dir_tmpl_main'] . '_list_users_base_items.html',
                        'item_charts' => $g['tmpl']['dir_tmpl_main'] . '_list_users_base_item_charts.html',
                        'pages' => $g['tmpl']['dir_tmpl_main'] . '_list_users_base_pages.html',
                    );
                    if($isAjaxRequest) {
                        $listTmpl['main'] = $g['tmpl']['dir_tmpl_main'] . 'search_results_ajax.html';
                    }
                } elseif (Common::isOptionActive('list_users_info_tmpl_parts', 'template_options')) {

                    $listTmpl = array(
                        'main' => $g['tmpl']['dir_tmpl_main'] . '_list_users_info.html',
                        'items' => $g['tmpl']['dir_tmpl_main'] . '_list_users_info_items.html',
                        'pages' => $g['tmpl']['dir_tmpl_main'] . '_list_users_info_pages.html',
                    );
                    if (Common::isOptionActive('list_users_info_tmpl_parts_item', 'template_options')) {
                        $listTmpl['item'] = $g['tmpl']['dir_tmpl_main'] . '_list_users_info_item.html';
                    }

                    if ($joinSearchPage) {
                        $listTmpl = array(
                            'items' => $g['tmpl']['dir_tmpl_main'] . '_list_users_join_items.html',
                        );
                    }

                    if($isAjaxRequest) {
                        $listTmpl['main'] = $g['tmpl']['dir_tmpl_main'] . 'search_results_ajax.html';
                    } else {
                        if (TemplateEdge::isTemplateColums()) {
                            $listTmpl['main'] = $g['tmpl']['dir_tmpl_main'] . '_list_users_info_columns.html';
                        }
                    }

                    mergeCustomTemplate($listTmpl, 'columns_template');

                    self::$fotterBanner = true;
                }

                $list = new CUsersInfo('users_list', $listTmpl);
                if ($isLikedShowList) {
                    if ($show == 'wall_liked' || $show == 'wall_shared') {
                        $wallLikesItemId = get_param_int('wall_item_id');
                        if ($show == 'wall_shared') {
                            $wallLikesItemId = get_param_int('wall_shared_item_id');
                        }
                        $wallItemInfo = DB::one('wall', '`id` = ' . to_sql($wallLikesItemId) . ' AND `group_id` != 0');
                        if ($wallItemInfo) {
                            $isPageGroup = Groups::getInfoBasic($wallItemInfo['group_id'], 'page');
                            if ($isPageGroup) {
                                TemplateEdge::$listUserGroupId = $wallItemInfo['group_id'];
                                TemplateEdge::$listUserGroupUid = $wallItemInfo['user_id'];
                            }
                        }
                    } elseif(in_array($show, array('photo_liked', 'video_liked', 'wall_liked_comment', 'photo_liked_comment', 'video_liked_comment'))){
                        $dataInfo = array();
                        if ($show == 'wall_liked_comment' || $show == 'photo_liked_comment' || $show == 'video_liked_comment') {
                            $commentId = get_param_int('comment_id');
                            $table = 'wall_comments';
                            if ($show == 'photo_liked_comment') {
                                $table = 'photo_comments';
                            } elseif($show == 'video_liked_comment'){
                                $table = 'vids_comment';
                            }
                            if ($commentId) {
                                $dataInfo = DB::one($table, '`id` = ' . to_sql($commentId) . ' AND `group_id` != 0');
                            }
                        } elseif ($show == 'photo_liked') {
                            $photoId = get_param_int('photo_id');
                            if ($photoId) {
                                $dataInfo = DB::one('photo_likes', '`photo_id` = ' . to_sql($photoId) . ' AND `group_id` != 0');
                            }
                        } elseif ($show == 'video_liked') {
                            $videoId = get_param_int('video_id');
                            if ($videoId) {
                                $dataInfo = DB::one('vids_likes', '`video_id` = ' . to_sql($videoId) . ' AND `group_id` != 0');
                            }
                        }
                        if ($dataInfo) {
                            $isPageGroup = Groups::getInfoBasic($dataInfo['group_id'], 'page');
                            if ($isPageGroup) {
                                TemplateEdge::$listUserGroupId = $dataInfo['group_id'];
                                TemplateEdge::$listUserGroupUid = $dataInfo['group_user_id'];
                            }
                        }
                    }
                }

                if ($p == 'users_viewed_me.php') {
                    if (Common::getOption('viewed_me_custom_settings', 'template_options')) {
                        $list->fieldsFromAdd = ', v.id as users_view_id, v.ref as users_view_ref, v.created_at as users_view_created, (SELECT name FROM user WHERE user_id = v.user_to) AS view_to';
                    }
                } elseif($p == 'users_rated_me.php') {
                    if (Common::getOption('rated_me_custom_settings', 'template_options')) {
                        $list->fieldsFromAdd = ', pr.id as photo_rated_id, pr.photo_id as photo_rated_photo_id, pr.rating as photo_rated_rating';
                    }
                }

                $allowCustomizationPages = array('users_viewed_me.php', 'users_rated_me.php');
                if (in_array($p, $allowCustomizationPages)) {
                    $mOnPage = Common::getOption('user_custom_per_page', 'template_options');
                    if ($mOnPage) {
                        $list->m_on_page = $mOnPage;
                        if($isAjaxRequest && $optionTmplName != 'edge') {
                            $list->m_on_page = get_param('on_page');
                        }
                    }
                } else {
                    $mOnPage = Common::getOption('usersinfo_per_page', 'template_options');
                    if($mOnPage === 'number_of_profiles_in_the_search_results') {
                        $mOnPage = intval(Common::getOption('number_of_profiles_in_the_search_results'));
                    }
                    if ($joinSearchPage) {
                        $mOnPage = Common::getOption('usersinfo_pages_per_join', 'template_options');
                    }
                    if ($mOnPage) {
                        $list->m_on_page = $mOnPage;
                    }
                }

                $mOnBar = Common::getOption('usersinfo_pages_per_list', 'template_options');
                if($mOnBar) {
                    $list->m_on_bar = $mOnBar;
                }
            } elseif ($display == "encounters" || $display == "rate_people") {

                self::$usersFilterModule = true;

                $isEncounters = false;

                if ($display == "encounters") {
                    if ($isAjaxRequest) {
                        Encounters::likeToMeet();
					}
                    $listTmpl = array(
                        'main' => $g['tmpl']['dir_tmpl_main'] . '_list_encounters.html',
                        'photo' => $g['tmpl']['dir_tmpl_main'] . '_list_encounters_photo.html',
                        'carousel' => $g['tmpl']['dir_tmpl_main'] . '_list_encounters_carousel.html',
                    );
					if ($optionTmplName == 'impact') {

						$listTmpl = array(
							'main' => $g['tmpl']['dir_tmpl_main'] . '_profile_encounters.html',
							'profile_photos_init' => $g['tmpl']['dir_tmpl_main'] . '_profile_photos_init.html',
                            'profile_charts' => $g['tmpl']['dir_tmpl_main'] . '_profile_charts.html'
						);
					}

                    $isEncounters = true;
                }elseif($display == "rate_people") {
                    $listTmpl = array(
                        'main' => $g['tmpl']['dir_tmpl_main'] . '_list_rate_people.html',
                        'photo' => $g['tmpl']['dir_tmpl_main'] . '_list_rate_people_photo.html',
                    );
                }
                if($isAjaxRequest && $optionTmplName != 'impact') {
                    $listTmpl['main'] = $g['tmpl']['dir_tmpl_main'] . 'search_results_ajax.html';
                }
				if ($optionTmplName == 'impact') {
					$list = new CUsersProfile("users_list", $listTmpl);
				} else {
					$list = new CHtmlUsersPhoto('users_list', $listTmpl);
				}

                $list->isEncounters = $isEncounters;

                $list->m_on_page = 1;
                if($display == "rate_people") {
                    $list->fieldsFromAdd = ', up.photo_id as photo_rate_id, (SELECT SUM(votes) FROM photo WHERE user_id=u.user_id) AS votes';
                }
            } elseif ($display == "gallery")
                $list = new CUsersGallery("users_list", $g['tmpl']['dir_tmpl_main'] . "_list_users_gallery.html");
            elseif ($display == "list")
                $list = new CUsersList("users_list", $g['tmpl']['dir_tmpl_main'] . "_list_users_list.html");
            elseif ($display == "profile" || $display == "profile_info") {
                $listTmpl = array(
                    'main' => $g['tmpl']['dir_tmpl_main'] . '_profile.html'
                );
                if (Common::isParseModule('profile_head')){
                    $listTmpl['profile_head'] = $g['tmpl']['dir_tmpl_main'] . '_profile_head.html';
                }
                if (Common::isParseModule('profile_photos_init')){
                    $listTmpl['profile_photos_init'] = $g['tmpl']['dir_tmpl_main'] . '_profile_photos_init.html';
                }
                if (Common::isParseModule('profile_charts') && guid() != User::getRequestUserId()){
                    $listTmpl['profile_charts'] = $g['tmpl']['dir_tmpl_main'] . '_profile_charts.html';
                }

                mergeCustomTemplate($listTmpl, 'columns_template');

                $list = new CUsersProfile("users_list", $listTmpl);
            }
            elseif ($display == "photo")
                $list = new CHtmlUsersPhoto("users_list", $g['tmpl']['dir_tmpl_main'] . "_photo.html");
            elseif ($display == "friends")
                $list = new CUsersFriends("users_list", $g['tmpl']['dir_tmpl_main'] . "_friends.html");
            else {
                redirect($p);
            }

            if($display == '' || $display == 'info' || $display == 'gallery' || $display == 'list') {
                CStatsTools::count('user_search_used');
            }

            //$list->m_debug = 'Y';

            if(!Common::isOptionActive('list_users_filter', 'template_options')) {
                self::$usersFilterModule = false;
            }

            if(!$isAjaxRequest && $p == 'search_results.php' && self::$usersFilterModule) {
                if (!Common::isOptionActive('list_users_filter_head_hide', 'template_options')) {
                    $usersFilterHead = new UsersFilterHead('users_filter_head', $g['tmpl']['dir_tmpl_main'] . '_list_users_filter_head.html');
                    $list->add($usersFilterHead);
                }
                $tmplFilter = '_list_users_filter.html';
                if ($isLikedShowList) {
                    $tmplFilter = '_list_users_filter_empty.html';
                    //$list->fieldsFromAdd = ', wl.date';
                }
                $usersFilter = new UsersFilter('users_filter', $g['tmpl']['dir_tmpl_main'] . $tmplFilter);
                $usersFilter->setUser(guid());
                $list->add($usersFilter);
            }

            if (!$isAjaxRequest && $p == 'search_results.php' && self::$fotterBanner) {
                $banner = new CBanner('banner', null);
                $banner->setType('footer');
                $list->add($banner);
            }


            if (Common::isOptionActive('login_form_on', 'template_options') && !guid()) {
                $loginForm = new CLoginForm('login_form', $g['tmpl']['dir_tmpl_main'] . '_login_form.html');
                $list->add($loginForm);
            }

            if (Common::isParseModule('profile_colum_narrow')  && guid()){
                $column_narrow = new CProfileNarowBox('profile_column_narrow', $g['tmpl']['dir_tmpl_main'] . '_profile_column_narrow.html');
                $list->add($column_narrow);
            }

            if ($where) {
                $list->m_sql_where = $where;
            }
            if ($order) {
                $list->m_sql_order = $order;
            }
            if ($from_add) {
                $list->m_sql_from_add = $from_add;
            }

            if ($from_add) {
                $list->m_sql_group = $from_group;
            }

            if (Common::isOptionActiveTemplate('redirect_if_single_only_member') && !guid()) {
                $redirectIfSingle = false;
            }
            if ($redirectIfSingle) {
                $list->redirectIfSingle=true;
            }

            if($isAjaxRequest) {
                return $list;
            }

            #$page->add($list);
            $pageAdd = array($list);
            if (Common::isParseModule('search')) {
                $search = new CSearch("search", $g['tmpl']['dir_tmpl_main'] . "_search.html");
                $pageAdd[] = $search;
            }
            #$page->add($search);
            #Page::addParts($page, array($list, $search));
            if (Common::isParseModule('users_list_header_buttons')) {
                $buttons = new CUsersListHeaderButtons('users_list_header_buttons', $g['tmpl']['dir_tmpl_main'] . '_list_users_header_buttons.html');
                $pageAdd[] = $buttons;
            }
            $partsHeader = false;
            /* URBAN WALL */
            $wallUid = User::getParamUid();//get_param('uid', guid());
            $isWall =  guid()
                        && Common::isWallActive()
                        && $display == 'profile'
                        && in_array($p, array('profile_view.php', 'search_results.php'));
            if ($isWall
                && Common::getOption('custom_wall_show', 'template_options')
                && Menu::isActiveSubmenuItem('profile_tabs', 'header_menu_wall')
                //&& Wall::isOnlySeeFriends($wallUid)
                ) {
                $_GET['uid'] = $wallUid;
                $wall = new CWallPage('wall_content', $g['tmpl']['dir_tmpl_main'] . '_wall_content.html');
                $wallItems = new CWallItems('wall_items', $g['tmpl']['dir_tmpl_main'] . '_wall_items.html');
                $wall->add($wallItems);
                $list->add($wall);
                $partsHeader = new CHtmlBlock('custom_head', $g['tmpl']['dir_tmpl_main'] . '_wall_custom_head.html');
            } elseif ($optionTmplName == 'edge' && $isWall) {
                $_GET['uid'] = $wallUid;

                $isAccessGroup = Groups::isAccessGroup();
                if ($isAccessGroup !== 'no_group' && !$isAccessGroup) {
                    $tmplList = getPageCustomTemplate(array('main' => 'wall.html'), 'wall_template_no_access_group');
                    $wall = new Groupsnoaccess('wall_content', $tmplList);
                } else {
                    $tmplList = getPageCustomTemplate(array('main' => 'wall.html'), 'wall_template');
                    $wall = new CWallPage('wall_content', $tmplList);
                    $tmplList = getPageCustomTemplate('_wall_items.html', 'wall_items');
                    $wallItems = new CWallItems("wall_items", $tmplList);
                    $wall->add($wallItems);
                }

                $list->add($wall);
            }
            /* URBAN WALL */

            $page = Page::addParts($page, $pageAdd, $partsHeader);

        }

        return $page;
    }

    static function isBigBase()
    {
        global $g_info;

        if($g_info['users_total']>5000){
            return true;
        }

        return false;

    }
}
