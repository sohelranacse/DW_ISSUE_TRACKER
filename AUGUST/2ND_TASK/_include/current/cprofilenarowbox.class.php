<?php
class CProfileNarowBox extends CHtmlBlock
{
    static private $notFree = false;

    static function Popularity(&$html)
    {
        global $g_user;

        $block = 'popularity';
        if ($html->blockexists($block)) {

            $level = User::getLevelOfPopularity();
            $html->setvar('level_decor', $level);
            $html->setvar('level', l($level));
            $isHighLevel = $level == 'very_high';
            $isActivityAllServices = User::isActivityAllServices();
            if ($isHighLevel && $isActivityAllServices) {
                $html->parse($block . '_decor_star');
            } else {
                if (Common::isCreditsEnabled()) {
                    $html->parse($block . '_increase');
                } else {
                    $html->parse($block . '_free_site');
                }
            }

            CustomPage::parseMenu($html);

            $html->parse($block);
        }
    }

    static function Customization(&$html)
    {
        global $g_user;

        if ($html->blockexists('customization')) {
            $isCustom = true;
            $userId = User::getParamUid();
            $userName = get_param('name', $g_user['name']);
            if (($userId == $g_user['user_id'] && $userName == $g_user['name']) || get_param('display') == 'encounters') {
                if (Common::isOptionActive('youtube_video_background_users_urban')
                    && (get_param('display') == 'profile' || Common::isOptionActive('youtube_video_background_users_all_pages_urban'))) {
                    $html->parse('profile_customization_video_js');
                    $html->parse('profile_customization_video');
                    $html->parse('profile_customization_video_add_tip');
                }
                $dir = Common::getOption('dir_tmpl_main', 'tmpl') . 'images/patterns';
                $patterns = array_flip(readAllFileArrayOfDir($dir, '', SORT_NUMERIC, '', '', 'png'));
                foreach ($patterns as $key => $pattern) {
                    $html->setvar('pattern', $pattern);
                    $html->setvar('num', trim($key));
                    if ($key > 0) {
                        $html->parse('profile_customization_item');
                    }
                }
                $html->parse('customization');
            }
        }
    }

    static function Activate_super_powers(&$html)
    {
        global $g_user;
        global $p;

        if ($html->blockexists('activate_super_powers')
            && self::$notFree) {//&& Common::isAvailableFeaturesSuperPowers()
            if (!User::isSuperPowers() && $p != 'upgrade.php') {
                $html->parse('activate_super_powers');
            }
        }
    }

    static function Refine_interests(&$html)
    {
        global $g_user;

        $block = 'refine_interests';
        if ($html->blockexists($block) && UserFields::isActive('interests')) {

            $guidInterests = User::getInterestsArray();
            $filterInterest = get_param('interest');
            if($filterInterest){
                $f_i=Interests::getInterestById($filterInterest);
                if($f_i){
                    if(!isset($guidInterests[$f_i['id']])){
                        $f_i['notInMyList']=1;
                        $guidInterests=array_merge(array($f_i['id']=>$f_i),$guidInterests);
                    } else {
                        $guidInterests[$f_i['id']]=$f_i;
                    }
                }
            }

            foreach ($guidInterests as $item) {
                $html->setvar('int_id', $item['id']);
                $html->setvar('cat_id', $item['category']);
                $html->setvar('interest_class', UserFields::getArrayNameIcoField('interests', $item['category'], 'normal'));
                $html->setvar('interest', mb_ucfirst($item['interest']));
                if(isset($item['notInMyList']) && $item['notInMyList']==1){
                    $html->parse('no_in_my_list_item', true);
                }
                $html->parse('list_interest_user_item', true);
            }
            if (!empty($guidInterests)) {
                $height = 126;
                if ($filterInterest) {
                    $height = 22;
                    $html->setvar($block . '_set_value', $filterInterest);
                    $html->parse($block . '_set');
                }
                $html->setvar($block . '_search_height', $height);
                $html->parse($block);
            }
        }
    }

    static function Search_by_username(&$html)
    {
        global $g_user;
        global $p;
        $display = get_param('display');

        $block = 'search_by_username';
        if ($p == 'search_results.php' && $display=='' && $html->blockexists($block)) {


            $html->parse($block);
        }
    }


    static function Rating_photos(&$html)
    {
        global $g_user;
        global $p;

        if (!Common::isOptionActive('photo_rating_enabled')) {
            return false;
        }
        $block = 'rating_photos';
        $display = get_param('display');
        if ($p == 'search_results.php' && $display == 'rate_people') {
            return false;
        }
        if ($html->blockexists($block)) {
            $html->setvar('url_page_rate_people', Common::pageUrl('rate_people'));
            /*if (!CProfilePhoto::getNumberPhotosUser()) {
                return false;
            }*/
            $average = CProfilePhoto::getAveragePhotosUser();
            if (!$average) {
                return false;
            }
            if (CProfilePhoto::getPhotoIdUnavailableRated()) {
                $slider = 50;
                $average = l('rating_empty');
                $html->parse($block . '_noslider');
                $html->parse($block . '_average_empty');
            } else {
                $slider = $average*10;
                $average = ratingFloatToStrTwoDecimalPoint($average);
                $html->parse($block . '_average');
            }

            $html->setvar($block . '_slider', $slider);
            $html->setvar($block . '_average', $average);
            $html->parse($block);
        }
    }

    static function See_my_video_greeting(&$html)
    {
        $block = 'see_my_video_greeting';

        $uid = User::getParamUid(false);
        if($uid && $html->blockexists($block)){

            $video_id = User::getInfoBasic($uid, 'video_greeting');

            if($video_id || $uid==guid()){
                if($uid!=guid()){
                    $html->setvar('video_greeting', $video_id);
                } else {
                    $html->setvar('video_greeting', 'Photo.greetingVideoId');
                    if(!$video_id){
                        $html->parse('see_video_greeting_hide');
                    }else {
                        $html->clean('see_video_greeting_hide');
                    }
                }
                $html->setvar('uid', $uid);
                $html->parse($block);
            }
        }
    }

    static function Left_menu(&$html)
    {
        $block = 'left_menu';
        if ($html->blockexists($block)) {
            CustomPage::parseMenu($html);
            $html->parse($block);
        }
    }

    static function Left_recently_visited(&$html)
    {
        $block = 'left_recently_visited';
        if ($html->blockexists($block)) {
            $isParse = false;
            $users = DB::select('users_view', 'user_from=' . to_sql(guid()), 'id DESC', 8);
            foreach ($users as $user) {
				$html->setvar("{$block}_name", User::getInfoBasic($user['user_to'], 'name'));
                $html->setvar("{$block}_url", User::url($user['user_to']));
                $keyPhoto = 'user_left_recently_visited_' . $user['user_to'];
                $userPhotoUrl = Cache::get($keyPhoto);
                if ($userPhotoUrl === null) {
                    $userPhotoUrl = User::getPhotoDefault($user['user_to'], 's');
                    Cache::add($keyPhoto, $userPhotoUrl);
                }
                $html->setvar("{$block}_photo", $userPhotoUrl);
                $html->parse("{$block}_item", true);
                $isParse = true;
            }
            if ($isParse) {
                $html->parse($block);
            }
        }
    }
    static function Right_recently_visited(&$html)
    {
        $block = 'right_recently_visited';
        if ($html->blockexists($block)) {
            $isParse = false;
            $users = DB::select('users_view', 'user_from=' . to_sql(guid()), 'id DESC', 8);
            foreach ($users as $user) {
                $html->setvar("{$block}_name", User::getInfoBasic($user['user_to'], 'name'));
                $html->setvar("{$block}_url", User::getInfoBasic($user['user_to'], 'name_seo'));
                $keyPhoto = 'user_left_recently_visited_' . $user['user_to'];
                $userPhotoUrl = Cache::get($keyPhoto);
                if ($userPhotoUrl === null) {
                    $userPhotoUrl = User::getPhotoDefault($user['user_to'], 's');
                    Cache::add($keyPhoto, $userPhotoUrl);
                }
                $html->setvar("{$block}_photo", $userPhotoUrl);
                $html->parse("{$block}_item", true);
                $isParse = true;
            }
            if ($isParse) {
                $html->parse($block);
            }
        }
    }

    static function Left_banner(&$html)
    {
        $block = 'left_banner';
        if ($html->blockexists($block)) {
            CBanner::getBlock($html, 'left_column');
            $html->parse($block);
        }
    }

    static function parseItems(&$html)
	{
        global $g_user, $p;

        $optionTmplName = Common::getOption('name', 'template_options');
        if (guid()) {
            $section = 'narrow';
            if ($optionTmplName == 'impact') {
                $section = 'impact_left_column';
            }

            self::$notFree = !Common::isOptionActive('free_site');

            $sql = "SELECT *
                      FROM `col_order`
                     WHERE `section` = '{$section}'
                       AND `status` = 'Y'
                     ORDER BY `position`";
            DB::query($sql, 1);
            $display = get_param('display');
            while ($row = DB::fetch_row(1)) {
                if ($optionTmplName != 'impact') {
                    if ($row['name'] == 'refine_interests'
                        && ($p != 'search_results.php'
                            || ($p == 'search_results.php'
                                && !in_array($display, array('rate_people', 'encounters', ''))))) {
                        continue;
                    }
                }
                $method = $row['name'];
                self::$method($html);
                $html->parse('column_narow_item', true);
                $html->clean($row['name']);
            }
        }

        if ($optionTmplName != 'impact') {
            CBanner::getBlock($html, 'right_column');
        }
        if (Common::isOptionActive('top_select') && Common::parseDropDownListLanguage($html)) {
            $html->parse('column_narow_select_lang');
        }
	}

	function parseBlock(&$html)
	{
        self::parseItems($html);

		parent::parseBlock($html);
	}

}