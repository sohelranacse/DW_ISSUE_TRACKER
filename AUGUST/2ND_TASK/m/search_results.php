<?php
/* (C) Websplosion LLC, 2001-2021

IMPORTANT: This is a commercial software product
and any kind of using it must agree to the Websplosion's license agreement.
It can be found at http://www.chameleonsocial.com/license.doc

This notice may not be removed from the source code. */

$area = "login";
include("./_include/core/pony_start.php");

$addWhereLocation = true;

$optionTmplName = Common::getTmplName();
$display = get_param('display');
if ($display == 'rate_people' && !Common::isOptionActive('photo_rating_enabled')) {
    Common::toHomePage();
}
if ($display == 'encounters') {
    $section = 'mobile_user_menu';
    $page = 'encounters';
    if ($optionTmplName == 'impact_mobile') {
        $section = 'mobile_user_menu_impact';
        $page = 'hot_or_not';
    }
    $isShow = CUserMenu::checkItemShow($page, $section);
    if (!$isShow) {
        Common::toHomePage();
    }
}

$paramUid = false;
if (get_param('uid') != '') {
    $paramUid = true;
}

CStatsTools::count('user_search_used');
$g['to_head'][] = '<link rel="stylesheet" href="'.$g['tmpl']['url_tmpl_mobile'].'css/search.css" type="text/css" media="all"/>';
$g['tmpl']['header_title_link'] = $g['path']['url_main'] . 'search.php';

//if (defined('IS_DEMO')) {
//	DB::query("SELECT * FROM im_open WHERE to_user=12 AND from_user=" . to_sql($g_user['user_id'], "Number") . "");
//	if (DB::num_rows() == 0) DB::execute("INSERT INTO im_open SET to_user=12, from_user=" . to_sql($g_user['user_id'], "Number") . "");
//}

$isUrban = Common::getOption('set', 'template_options') == 'urban';
$isFreeSite = Common::isOptionActive('free_site');

$name = trim(get_param('name'));
$uidRequest = User::getRequestUserId();
if(($display == 'profile' || $display == 'profile_info' || $display == 'wall') && $uidRequest) {
    $patch = '';
    if ($display == 'profile' || $display == 'profile_info') {
        $patch = 'profile_view.php?user_id=' . $uidRequest . '&display=' . $display;
    } elseif ($display == 'wall') {
        $patch = 'wall.php?uid=' . $uidRequest;
    }
    if ($patch) {
        redirect($patch);
    }
}

class CSearchFilter extends UserFields
{
	function parseBlock(&$html)
	{
        $this->parseMobileAdvancedFilter($html);

		parent::parseBlock($html);
	}
}

class CSearchResults extends CUsersInfo
{
	var $parsed_item_n;
	var $m_city_postfix = ",<br/>";

	function onItem(&$html, $row, $i, $last)
	{
        if ($html->varExists('from_page')) {
            $html->setvar('from_page', 'search');
        }
        if ($html->varExists('ref_link')) {
            $html->setvar('ref_link', '&ref=people_nearby');
        }

		parent::onItem($html, $row, $i, $last);
        $html->parse('users_list_item_url', false);

		++$this->parsed_item_n;
	}

	function parseBlock(&$html)
	{
        $isAjaxRequest = get_param('ajax', 0);
        $display = get_param('display');
		$this->parsed_item_n = 1;

		//if ($GLOBALS['isUrban']) $html->setvar('offset_real', max(1, (int) $this->m_offset));
        if ($html->varExists('offset_real')) {
            $html->setvar('offset_real', max(1, intval($this->m_offset)));
        }
        if (!$isAjaxRequest) {
            /*if ($html->blockExists('users_list_loader')) {
                $html->parse('users_list_loader', false);
            }
            if ($html->blockExists('users_list_scroll') && get_param('back_offset')) {
                $html->parse('users_list_scroll', false);
            }*/
            if ($html->varExists('on_page')) {
                $html->setvar('on_page', $this->m_on_page);
                $html->parse('users_list_on_page', false);
            }
            if ($html->varExists('found_title')) {
                $foundTitle = l('found');
                if ($display != '') {
                    $foundTitle = l('found_' . $display);
                }
                $html->setvar('found_title', $foundTitle);
            }
            if ($html->varExists('found_info')) {
                if ($display == '') {
                    $uid = guid();
                    $dataLokingFor = User::getInfoBasic($uid);
                    /*$location = guser('user_search_filters_mobile');
                    if ($location) {
                        $location = json_decode($location, true);
                        if ($location['city']['value']){
                            $dataLokingFor['city'] = l(Common::getLocationTitle('city', $location['city']['value']));
                        } else if ($location['state']['value']){
                            $dataLokingFor['city'] = l(Common::getLocationTitle('state', $location['state']['value']));
                        } else if ($location['country']['value']){
                            $dataLokingFor['city'] = l(Common::getLocationTitle('country', $location['country']['value']));
                        }
                    } else {
                        $dataLokingFor['city'] = l(UsersFilter::getLocationTitleDb());
                    }*/

                    $guserLocation = guser('user_search_filters_mobile');
                    $guserLocation = User::checkLocationFilter($guserLocation, true);
                    $location = json_decode($guserLocation, true);
                    $isPeopleNearBy = false;
                    if (isset($location['people_nearby'])) {
                        $isPeopleNearBy = intval($location['people_nearby']['value']);
                    }

                    $dataLokingFor['radius'] = get_param('radius');

                    if ($isPeopleNearBy) {
                        $dataLokingFor['city'] = l(guser('geo_position_city'));
                        $dataLokingFor['all_items_select'] = 0;
                    } else {
                        if(isset($location['city']['value']) && $location['city']['value'] == 0){
                            $dataLokingFor['all_items_select'] = 1;
                        } else {
                            $dataLokingFor['all_items_select'] = 0;
                        }
                        $dataLokingFor['city'] = User::getLocationFiltersMobile($uid, $guserLocation);
                    }

                    $searchByRadiusAllCountry = (isset($location['radius']) && ($location['radius'] > intval(Common::getOption('max_search_distance'))));
                    if($searchByRadiusAllCountry) {
                        if ($isPeopleNearBy) {
                            $dataLokingFor['country'] = l(guser('geo_position_country'));
                        } else {
                            $dataLokingFor['country'] = User::getLocationFiltersMobile($uid, $guserLocation, $searchByRadiusAllCountry);
                        }
                    }

                    $foundInfo = User::getLookingFor(guid(), $dataLokingFor, 'search');
                } else {
                    $foundInfo = l('found_info_' . $display);
                }
                $html->setvar('found_info', $foundInfo);
            }
            if ($html->varExists('found_no_one')) {
                $foundNoOne = l('found_no_one');
                if ($display != '') {
                    $foundNoOne = l('found_no_one_' . $display);
                }
                $html->setvar('found_no_one', $foundNoOne);
            }
            if ((Common::isOptionActive('free_site') || !Common::isOptionActive('credits_enabled')) && $html->blockExists('class_indent')) {
                $html->parse('class_indent');
            }
        }

		parent::parseBlock($html);
	}

	function onPostParse(&$html,$row=array())
	{
		for($i = $this->parsed_item_n; $i <= 5; ++$i)
		{
			if($this->row_breaks && !($i % $this->row_breaks_n_cols))
				$html->setvar('row_break', '</tr><tr>');
			else
				$html->setvar('row_break', '');
			$html->parse('users_list_no_item');
		}
	}
}


$isAjaxRequest = get_param('ajax', 0);

$tmpl = 'search_results.html';
if ($isAjaxRequest) {
    $tmpl = 'search_results_ajax.html';
}

class CPage extends CHtmlBlock
{
	function parseBlock(&$html)
	{
        $display = get_param('display');
        if ($html->varExists('url_page_history')) {
            $urlPage = Common::pageUrl('search_results');
            if ($display == 'encounters') {
                $urlPage = Common::pageUrl('hot_or_not');
            }
            $html->setvar('url_page_history', $urlPage);
            $html->parse('url_page_history_set_hash', false);
            $html->setvar('upload_page_content_ajax', intval(get_param('upload_page_content_ajax')));
        }

		parent::parseBlock($html);
	}
}

$optionTmplName = Common::getOption('name', 'template_options');

$page = new CPage("", $g['tmpl']['dir_tmpl_mobile'] . $tmpl);

if ($display == 'encounters') {
    $listTmpl = array('main' => $g['tmpl']['dir_tmpl_mobile'] . '_encounters_info.html',
                      'items' => $g['tmpl']['dir_tmpl_mobile'] . '_encounters_items.html'
                );
    if (!Common::isOptionActive('profile_visitor_no_block_report', 'template_options')) {
        $listTmpl['user_block_report'] = $g['tmpl']['dir_tmpl_mobile'] . '_user_block_report.html';
    }
    if ($isAjaxRequest) {
        $listTmpl = $g['tmpl']['dir_tmpl_mobile'] . '_encounters_items.html';
        Encounters::likeToMeet(null, null, null, 'N');
    }
    $list = new CUsersProfile('users_list', $listTmpl);
} elseif ($display == 'rate_people') {
    $listTmpl = array('main' => $g['tmpl']['dir_tmpl_mobile'] . '_rate_people_info.html',
                      'items' => $g['tmpl']['dir_tmpl_mobile'] . '_rate_people_items.html',
                      'user_block_report' => $g['tmpl']['dir_tmpl_mobile'] . '_user_block_report.html',
                );
    if ($isAjaxRequest) {
        $listTmpl = $g['tmpl']['dir_tmpl_mobile'] . '_rate_people_items.html';
    }
    $list = new CHtmlUsersPhoto('users_list', $listTmpl);

} else {
    $list = new CSearchResults('users_list', $g['tmpl']['dir_tmpl_mobile'] . '_list_users_info.html');
}

if (!isset($g_user['user_id'])) $g_user['user_id'] = 0;
$list->m_params = get_params_string();

if ($g_user['user_id'] != 0)
{
	if (get_param("save_search", 0) == 1 and trim(get_param("search_name", "")) != "")
	{
		$name = to_sql(get_param("search_name", "Search name"), "Text");
		$id = DB::result("SELECT id FROM search_save WHERE user_id=" . $g_user['user_id'] . " AND name=" . $name . "");

		if ($id == 0)
		{
			$num_save = DB::result("SELECT COUNT(id) FROM search_save WHERE user_id=" . $g_user['user_id'] . "");
			if ($num_save >= 10)
			{
				$page->m_html->setvar("save_message", "The maximum number of saved searches has been reached.");
			}
			else
			{
				$query = to_sql($list->m_params, "Text");
				DB::execute("INSERT INTO search_save (name, user_id, query) VALUES (" . $name . ", " . $g_user['user_id'] . ", " . $query . ")");
			}
		}
		else
		{
			$query = to_sql($list->m_params, "Text");
			DB::execute("UPDATE search_save SET query=" . $query . " WHERE id=" . $id . "");
		}
	}
}

$whereCore = "1=1 ";
$where = "";
$order = '';

$orderNear = Common::getSearchOrderNear();
$withPhoto = false;
if ($isUrban && ($display == '' || $display == 'encounters' || $display == 'rate_people')) {
    if (guid()) {
        $userInfo = null;
        $typeFilter = 'user_search_filters_mobile';
        if(get_param('set_filter')) {
            $_GET['with_photo'] = intval(get_param('with_photo') != '');
            $_GET['offset'] = get_param('offset', 1);
            User::updateParamsFilterUser();
            //$userinfo = User::updateParamsFilterUserInfo($typeFilter);
            $userinfo = User::updateFilterAll(null, array('status', 'with_photo', 'country', 'state', 'city', 'radius', 'people_nearby'));
			/*
            if (get_param('city')) {
                User::updateParamsFilterUserInfo('user_search_filters', $userinfo);
            }*/
        }
		$filtersInfo = User::setGetParamsFilter($typeFilter, $userInfo);
        if(json_encode($filtersInfo) != guser('user_search_filters_mobile')){
            User::updateFilterAll();
        }
    } else {
        if (UserFields::isActive('i_am_here_to')) {
            $sql = 'SELECT `id` FROM `const_i_am_here_to`';
            $iAmHereTo = DB::result($sql);
            if ($iAmHereTo) {
                $_GET['i_am_here_to'] = $iAmHereTo;
            }
        }
        $geoInfo = getDemoCapitalCountry();//IP::geoInfoCity();
        $_GET['country'] = get_param('country', $geoInfo['country_id']);
        $_GET['state'] = get_param('state', $geoInfo['state_id']);
        $_GET['city'] = get_param('city', $geoInfo['city_id']);
    }
    $withPhoto = intval(get_param('with_photo', 1));
    if ($withPhoto && $display != 'encounters' && $display != 'rate_people') {
        $order = "is_photo DESC, ";
    }
}

/*$user["p_orientation"] = (int) get_checks_param("p_orientation");
if ($user["p_orientation"] > 0){
	$where .= " AND " . $user["p_orientation"] . " & (1 << (cast(u.orientation AS signed) - 1))";
}*/

$user["p_relation"] = (int) get_checks_param("p_relation");
if ($user["p_relation"] != "0") {
	$where .= " AND " . $user["p_relation"] . " & (1 << (relation - 1))";
}

$user['name'] = get_param("name_key", "");
if ($user['name'] != "") {
	$where .= " AND name LIKE '%" . to_sql($user['name'], "Plain") . "%'";
}

$user['name'] = get_param("name", "");
if ($user['name'] != "") {
	$where .= " AND u.name=" . to_sql($user['name']) . "";
}

$user['p_age_from'] = (int) get_param("p_age_from", 0);
$user['p_age_to'] = (int) get_param("p_age_to", 0);
$userAgeToSrc = $user['p_age_to'];
if ($user['p_age_from'] == $g['options']['users_age']) $user['p_age_from'] = 0;
if ($user['p_age_to'] == $g['options']['users_age_max']) $user['p_age_to'] = 10000;
if ($user['p_age_from'] != 0)
{
	$where .= " AND u.birth <= " . to_sql(Common::ageToDate($user['p_age_from']));
}

if ($userAgeToSrc && $userAgeToSrc != $g['options']['users_age_max'])
{
    $where .= " AND u.birth >= " . to_sql(Common::ageToDate($userAgeToSrc, true));
}

/*foreach ($g['user_var'] as $k => $v)
{
	$user[$k] = get_param($k, "");
}
foreach ($g['user_var'] as $k => $v)
{
	if (substr($k, 0, 2) == "p_" and $user[$k] != "")
	{
		if ($v[0] == "from_table")
		{
			if ($v[1] == "int")
			{
				if (isset($v[5]) and $v[5] == "from")
				{
					$first = DB::result("SELECT id FROM " . $v[2] . " ORDER BY id ASC LIMIT 1");
					$last = DB::result("SELECT id FROM " . $v[2] . " ORDER BY id DESC LIMIT 1");
					if ($first == $user[$k])
					{
						$user[$k] = "0";
					}
					if ($user[substr($k, 0, strlen($k) - 4) . "to"])
					{

					}
					$key = $k;
					if (substr($key, 0, 2) == "p_") $key = substr($key, 2);
					if (substr($key, -5) == "_from") $key = substr($key, 0, strlen($key) - 5);
					$where .= " AND (" . $key . ">=" . $user[$k] . " AND " . $key . "<=" . $user[substr($k, 0, strlen($k) - 4) . "to"] . ") ";
				}
				elseif (isset($v[5]) and $v[5] == "to")
				{

				}
				else
				{
					if ($user[$k] != 0)
					{
						$key = $k;
						if (substr($key, 0, 2) == "p_") $key = substr($key, 2);
						$where .= " AND " . $key . "=" . $user[$k] . "";
					}
				}
			}
			elseif ($v[1] == "checks")
			{
				$user[$k] = get_checks_param($k);
				if ($user[$k] != 0)
				{
					$key = $k;
					if (substr($key, 0, 2) == "p_") $key = substr($key, 2);
					$where .= " AND " . $user[$k] . " & (1 << (" . $key . " - 1))";
				}
			}
		}
	}
}*/

UserFields::removeUnavailableField();
foreach ($g['user_var'] as $k => $v){
	$user[$k] = intval(get_param($k, ''));
}
$typeFields = array('from', 'checks', 'checkbox');
$numCheckbox = 0;
$from_add = '';
$from_group = '';
foreach ($g['user_var'] as $k => $v)
{
    if (in_array($v['type'], $typeFields) && $v['status'] == 'active')
    {
        if ($v['type'] == 'from'){

            $key = $k;
			if (substr($key, 0, 2) == "p_") $key = substr($key, 2);
			if (substr($key, -5) == "_from") $key = substr($key, 0, strlen($key) - 5);

            $valFieldFrom = $user[$k];

            $fieldTo = substr($k, 0, strlen($k) - 4) . 'to';
            $valFieldTo = intval($user[$fieldTo]);

            if ($valFieldTo) {
                $where .= ' AND i.' . $key . '<=' . $valFieldTo;

                if(!$valFieldFrom) {
                    $valFieldFrom = 1;
                }
            }

            if($valFieldFrom) {
                $where .= " AND i." . $key . ">=" . intval($valFieldFrom);
            }

            if($valFieldFrom || $valFieldTo) {
                // save real value for defaul select value
                $valFieldFrom = $user[$k];

                $keyFilter = $k;

                if(!$valFieldFrom) {
                    $keyFilter = $fieldTo;
                }

                $userSearchFilters[$keyFilter] = array(
                    'field' => $key,
                    'values' => array($k => $valFieldFrom, $fieldTo => $valFieldTo),
                );

                //$userSearchFilters[$k] = array($user[$k], $valFieldsTo);
            }
		} elseif ($v['type'] == 'checks' && $user[$k] != 0) {
                $user[$k] = intval(get_checks_param($k));
                if ($user[$k] != 0)
				{
					$key = $k;
					if (substr($key, 0, 2) == "p_") $key = substr($key, 2);

                    $userSearchFilters[$k] = array(
                        'field' => $key,
                        'value' => get_param_array($k),
                    );

                    if ($k != 'p_star_sign') {
                        $where .= " AND " . to_sql($user[$k], 'Number') . " & (1 << (cast(i." . $key . " AS signed) - 1))";
                    }
				}
        } elseif ($v['type'] == 'checkbox' && $user[$k] != 0) {
                $params = get_param_array($k);
                foreach ($params as $key => $value) {
                    if ($value == 0) {
                        unset($params[$key]);
                    }
                }
                if (!empty($params)){
                    $userSearchFilters[$k] = array(
                        'field' => $k,
                        'value' => $params,
                    );
                    $nameTable = 'uck' . $numCheckbox;
                    $from_add .= " LEFT JOIN users_checkbox AS " . $nameTable . " ON " . $nameTable . ".user_id = u.user_id AND " . $nameTable . ".field = " . to_sql($v['id'], 'Number') . " AND "  . $nameTable . ".value IN (" . implode(',', $params) . ")";
                    $where .=  " AND " . $nameTable . ".user_id IS NOT NULL";
                    $numCheckbox++;
                }
        }
	}
}

if ($numCheckbox) {
    $list->m_sql_group = 'u.user_id';
}

$from_add = "LEFT JOIN userinfo AS i ON u.user_id=i.user_id" . $from_add;

// IF active distance search, then exclude others
// DISTANCE
$distance = (int) get_param('radius', 0);
$user['city'] = (int) get_param("city", 0);
$user['state'] = (int) get_param("state", 0);
$user['country'] = (int) get_param("country", 0);
$peopleNearby = get_param_int('people_nearby');

$maxDistance = Common::getOption('max_search_distance');
if ($peopleNearby) {
    $userLocation = array('country' => 0, 'state' => 0, 'city' => 0);
    if($distance == 0){//In the whole city
        $whereLocation = " AND u.geo_position_city_id = " . to_sql(guser('geo_position_city_id'));
    } elseif (Common::getOption('max_filter_distance') == 'max_search_country' && $distance > $maxDistance) {//In the whole country
        $whereLocation = " AND u.geo_position_country_id = " . to_sql(guser('geo_position_country_id'));
    } else {
        $whereLocation = getInRadiusWhere($distance);
    }
} else {
    if($distance > $maxDistance && $user['city']>0) {
        $user['city'] = 0;
        $user['state'] = 0;
    }

    $allCountriesSearch = get_param('all_countries',0);

    if($allCountriesSearch==1){
        $user['city'] = 0;
        $user['state'] = 0;
        $user['country'] = 0;
    }

    // search only by distance from selected city
    $whereLocation = '';
    if($distance && $user['city'])
    {
        // find MAX geo values
        $whereLocation = inradius($user['city'], $distance);
        $from_add .= " LEFT JOIN geo_city AS gc ON gc.city_id = u.city_id";
    } else {
        $whereLocation = Common::getWhereSearchLocation($user);
    }
}

$onlyPhotos = Common::isOptionActive('no_profiles_without_photos_search');
if ($onlyPhotos && !$withPhoto) {
    $onlyPhotos = false;
}
if (get_param("photo", "") == "1" || $onlyPhotos){
	$where .= " AND u.is_photo='Y'";
}

if (get_param("couple", "") == "1") {
	$where .= " AND u.couple='Y'";
}

$status = get_param('status');
if ($status == "online") {
	$where .= " AND last_visit>'" . (date("Y-m-d H:i:s", time() - $g['options']['online_time'] * 60)) . "'";
} elseif ($status == "new"){
	$where .= " AND register>" . to_sql(date('Y-m-d H:00:00', (time() - $g['options']['new_time'] * 3600 * 24)), 'Text') . "";
} elseif ($status == "birthday") {
	$where .= " AND (DAYOFMONTH(birth)=DAYOFMONTH('" . date('Y-m-d H:i:s') . "') AND MONTH(birth)=MONTH('" . date('Y-m-d H:i:s') . "'))";
}

$keyword = get_param("keyword", "");
if ($keyword != "") {
	$keyword_search_sql = "";
	$keyword = to_sql(strip_tags($keyword), "Plain");
	foreach ($g['user_var'] as $k => $v) {
        if ($v['type'] == "text" || $v['type'] == "textarea") $keyword_search_sql .= " OR i." . $k . " LIKE '%" . $keyword . "%'";
    }
	$where .= " AND (u.name LIKE '%" . $keyword . "%'" . $keyword_search_sql . ") ";
}

//$ht = get_param("name", "") == "" ? "hide_time=0" : "1 ";
$ht = User::isHiddenSql();

$wallItemId = get_param('wall_item_id', '');
if($wallItemId) {
    $where .= ' AND wl.wall_item_id = ' . to_sql($wallItemId, 'Number');
    $from_add = ' LEFT JOIN wall_likes AS wl ON wl.user_id = u.user_id ';
    // show hidden profiles in likes
    $ht = ' 1 ';
}

$whereCore .= ' AND ' . $ht;

if (Common::isOptionActive('users_ban_hide_in_the_search_results')) {
    $where .= ' AND u.ban_global = 0';
}
if(Common::isOptionActive('manual_user_approval') && Common::isOptionActive('users_no_approve_hide_in_the_search_results')) {
    $where .= ' AND `active` = 1';
}

$uidsExclude = get_param('uids_exclude', '');
if($uidsExclude) {
    if (is_array($uidsExclude)) {
        $uidsExclude = implode(',',$uidsExclude);
    }
    $where .= ' AND u.user_id NOT IN (' . to_sql($uidsExclude, 'Plain') . ') ';
}

if (get_param('uid') != '') {
    $where = "u.user_id=" . intval(get_param('uid')) . "";
} else {
	$where = $ht . " " . $where . " ";
}

if (Common::getOption('do_not_show_me_in_search', 'template_options')
    && $g_user['user_id'] != $uidRequest) {
    $where .= " AND u.user_id != " . to_sql($g_user['user_id'], 'Number');
    $whereCore .= " AND u.user_id != " . to_sql($g_user['user_id'], 'Number');
}

$uidEnc = get_param('uid');
/*if ($display == 'encounters' && !$uidEnc) {
    $order = 'enc1.user_from ASC, ';
}*/
$user['i_am_here_to'] = (int) get_param('i_am_here_to', '');
if($user['i_am_here_to']) {
    Common::prepareSearchWhereOrderByIAmHereTo($where, $order, $user['i_am_here_to']);
}

if ($isUrban) {
    $onPage = getMobileOnPageSearch();
    $list->m_on_page = get_param('on_page', $onPage);
    if ($display != 'encounters' && $display != 'rate_people') {
        $list->m_offset = get_param('offset', (int)get_cookie('back_offset', 1));
        $list->m_chk=$onPage;
    }

    $customOrder = ($isFreeSite) ? $orderNear . ' name' : 'date_search DESC, ' . $orderNear . ' name';

    if ($g_user['user_id']) {
            $guidSql = to_sql($g_user['user_id'], 'Number');

			$from_add .= " LEFT JOIN user_block_list AS ubl1 ON (ubl1.user_to = u.user_id AND ubl1.user_from = " . $guidSql . ")
					LEFT JOIN user_block_list AS ubl2 ON (ubl2.user_from = u.user_id AND ubl2.user_to = " . $guidSql . ")";
			$where .= ' AND ubl1.id IS NULL AND ubl2.id IS NULL';
            $whereCore .= ' AND ubl1.id IS NULL AND ubl2.id IS NULL';
            if ($display == 'encounters') {
                $where .=" AND u.is_photo_public = 'Y'
                           AND u.user_id != " . $guidSql;
                $whereEnc = '';
                if (!$uidEnc) {
                    $uidEncLike = get_param('uid_enc_like', 0);
                    /*$whereEnc =' AND u.user_id NOT IN (' . $uidEncLike . ') AND (enc.id IS NULL OR (enc1.id IS NOT NULL AND enc.id IS NOT NULL))';
                    $from_add .= " LEFT JOIN encounters AS enc ON (u.user_id = enc.user_to AND enc.user_from = " . $guidSql . ")
                                                               OR (u.user_id = enc.user_from AND enc.user_to = " . $guidSql . "
                                                              AND ((enc.from_reply != 'N' AND enc.to_reply != 'P')OR(enc.from_reply = 'N')))

                                   LEFT JOIN encounters AS enc1 ON ((u.user_id = enc1.user_to AND enc1.user_from = " . $guidSql . ")
                                                                 OR (u.user_id = enc1.user_from AND enc1.user_to = " . $guidSql . "))
                                                                AND (enc1.from_reply IN ('Y', 'M') AND enc1.to_reply IN ('Y', 'M'))";*/


                    $where .=' AND enc.user_from IS NULL AND enc1.user_from IS NULL ';
                    $from_add .= " LEFT JOIN encounters AS enc ON (u.user_id = enc.user_to AND enc.user_from = " . $guidSql . ")
                               LEFT JOIN encounters AS enc1 ON (u.user_id = enc1.user_from AND enc1.user_to = " . $guidSql . "
                                                       AND ((enc1.from_reply != 'N' AND enc1.to_reply != 'P') OR (enc1.from_reply = 'N')))";

                }

                if ($isFreeSite) {
                    $customOrder = $orderNear . ' user_id DESC';
                } else {
                    $orderDate = $optionTmplName == 'impact_mobile' ? 'date_search' : 'date_encounters';
                    $customOrder = $orderDate . ' DESC, ' . $orderNear . ' user_id DESC';
                }


                /*if (!$isFreeSite) {
                    $customOrder = 'date_encounters DESC, near DESC,  user_id DESC';
                }*/

                $list->m_on_page = 1;
                $mOnPageEncounters = Common::getOption('usersinfo_encounters_list', 'template_options');
                if ($mOnPageEncounters) {
                    $list->m_on_page = $mOnPageEncounters;
                }
                if(!$paramUid && Users_List::isBigBase()) {
                    $countForResults = 1;
                    if ($mOnPageEncounters) {
                        $countForResults = $mOnPageEncounters;
                    }
                    Encounters::prepareFastSelect($where, $whereLocation, $from_add, $customOrder, $countForResults);
                    $where = Encounters::getFastSelectWhere($where, $countForResults);
                    $addWhereLocation = false;
                    $order = '';
                    $customOrder = '';
                }
            }elseif ($display == 'rate_people') {

                $where .=" AND u.is_photo_public = 'Y'
                           AND u.user_id != " . $guidSql .
                         ' AND upr.photo_id IS NULL ';
                $from_add .= ' LEFT JOIN photo AS up ON u.user_id = up.user_id AND up.private = "N" AND up.visible = "Y"
                               LEFT JOIN photo_rate AS upr ON up.photo_id = upr.photo_id AND upr.user_id = ' . $guidSql;
                $customOrder = 'votes ASC, RAND()';
                $from_group = 'u.user_id';
                $list->m_on_page = 1;
                $list->fieldsFromAdd = ', up.photo_id as photo_rate_id, (SELECT SUM(votes) FROM photo WHERE user_id=u.user_id) AS votes';

                if(!$paramUid){
                    if(Users_List::isBigBase()){
                        $where = User::getRatePhotoWhereOnBigBase($where, $from_add, $user, $whereLocation, $order . $customOrder);
                        $addWhereLocation = false;
                        $order = '';
                        $customOrder = '';
                    }
                }
            }
    }
    $order .= $customOrder;
} else {
    $list->m_on_page = 5;
    $order .= $orderNear . ' name';
}

if($isUrban && $display == 'encounters') {
    if(Encounters::$fastSelectUid) {
        $total = 1;
    } else {
        $total = DB::result('SELECT COUNT(u.user_id) FROM user AS u ' . $from_add . ' WHERE ' . $where . $whereEnc);
    }
    if (!$total) {
        if (!$uidEnc) {
            $whereEnc =' AND (enc.id IS NULL OR (enc1.id IS NOT NULL AND enc.id IS NOT NULL))';
        }
        $list->m_reset_sql = 1;
    }
    $where .= $whereEnc;
}

if (($display == 'encounters' || $display == 'rate_people') && $paramUid) {
    $addWhereLocation = false;
}
if ($addWhereLocation) {
    $where .= $whereLocation;
}

$global_username_search = get_param('global_search_by_username');
if(trim($global_username_search) != ''){
    // $where = $whereCore . ' AND u.name LIKE "%'.to_sql($global_username_search,'Plain').'%"';
    $where = $whereCore. " AND (u.name LIKE '%".to_sql($global_username_search, "Plain")."%' OR u.name_seo LIKE '%" . to_sql($global_username_search, "Plain") . "%')";
}
// dd($order);
$list->m_sql_from_add = $from_add;
$list->m_sql_where = $where;
$list->m_sql_order = $order;

$list->m_last_visit_only_online = true;
$list->row_breaks = true;
$list->m_offset_real=$isUrban;

//$list->m_debug = "Y";
$page->add($list);

$isAjaxRequestPage = get_param('ajax_page');
if ($isAjaxRequestPage) {
    die(getResponsePageAjaxAuth($page));
}

if (!$isAjaxRequest) {
    if (Common::isParseModule('search_filter')) {
        $filter = new CSearchFilter("search_filter", $g['tmpl']['dir_tmpl_mobile'] . "search.html");
        $page->add($filter);
    }
    $header = new CHeader("header", $g['tmpl']['dir_tmpl_mobile'] . "_header.html");
    $page->add($header);


    $tmplFooter = $g['tmpl']['dir_tmpl_mobile'] . "_footer.html";
    if (Common::isOptionActive('is_allow_empty_footer', 'template_options')) {
        $tmplFooter = $g['tmpl']['dir_tmpl_mobile'] . "_footer_empty.html";
    }
    $footer = new CFooter("footer", $tmplFooter);
    $page->add($footer);
    //$display == 'encounters'

    if (Common::isParseModule('user_menu')) {
        $user_menu = new CUserMenu("user_menu", $g['tmpl']['dir_tmpl_mobile'] . "_user_menu.html");
        if ($isUrban) {
            $header->add($user_menu);
        } else {
            $user_menu->setActive('search');
            $page->add($user_menu);
        }
    }
    if (Common::isParseModule('people_nearby_spotlight')
        && $display != 'encounters' && $display != 'rate_people') {
        $spotlight = new Spotlight('spotlight', $g['tmpl']['dir_tmpl_mobile'] . '_spotlight.html');
        $spotlight->update = false;
        $page->add($spotlight);
    }
}

loadPageContentAjax($page);

include("./_include/core/main_close.php");