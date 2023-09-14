<?php
/* (C) Websplosion LLC, 2001-2021

IMPORTANT: This is a commercial software product
and any kind of using it must agree to the Websplosion's license agreement.
It can be found at http://www.chameleonsocial.com/license.doc

This notice may not be removed from the source code. */

include('./_include/core/pony_start.php');

global $g;
global $g_user;

$dirTmpl = $g['tmpl']['dir_tmpl_mobile'];
$cmd = get_param('cmd');
$display = get_param('display');
$guid = guid();
$isAuth = $guid ? true : false;

if ($cmd == 'read_msg') {
    $responseData = CIm::setMessageAsRead();
}else if ($cmd == 'update_im') {
    $responseData = null;
    if ($guid) {
        $responseData = '';
        if (in_array(get_param('page'), array('messages.php'))) {
            if ($display == 'one_chat') {
                CIm::setWritingMobileOneChat();
                $tmpl = "{$dirTmpl}_messages_user_msg.html";
            } else {
                $tmpl = array('main' => "{$dirTmpl}_messages_list_users.html",
                              'msg_list' => "{$dirTmpl}_messages_list_msg.html"
                );
            }
            $page = new CIm('', $tmpl);
			$responseData .= getParsePageAjax($page);
        }


        $optionTmplName = Common::getOption('name', 'template_options');
        /* Counters */
        $listCounters = array();
        if (Common::isModuleCityActive()) {
            $numbersCity = null;
            if (get_param('city_counter_street_chat') && City::isActiveStreetChat()) {
                $numbersCity = City::getNumberUsersVisitors();
                $numbersCity['all_number'] -= $numbersCity[12];
                $listCounters['street_chat'] = $numbersCity[12];
            }

            if (get_param('city_counter_games') && City::isActiveGames()) {
                if ($numbersCity === null) {
                    $numbersCity = City::getNumberUsersVisitors();
                }
                $cityNumberUsersGames = City::getNumberUsersGames($numbersCity);
                $numbersCity['all_number'] -= $cityNumberUsersGames;
                $listCounters['game_choose'] = $cityNumberUsersGames;
            }

            if(get_param('city_counter')) {
                if ($numbersCity === null) {
                    $numbersCity = City::getNumberUsersVisitors();
                }
                $listCounters['3d_city'] = $numbersCity['all_number'];
            }
        }
        /* Counters */
        if ($optionTmplName != 'impact_mobile') {
            $numberNewMsg = CIm::getCountNewMessages(null, get_param('user_to'));
            $lastNewMessageInfo = CIm::getLastNewMessageInfo();
            $responseData .= "<script>messages.setNumberMessages($numberNewMsg, {$lastNewMessageInfo['uid']}, '{$lastNewMessageInfo['message']}');</script>";
            $lastNewMessageInfo = City::getLastNewMessageInfo();
            $responseData .= "<script>mobileAppCityNotification(" . json_encode($lastNewMessageInfo) . ");</script>";

            $listCounters['matches'] = MutualAttractions::getNumberMutualAttractions('mutual');
            $listCounters['who_likes_you'] = MutualAttractions::getNumberMutualAttractions('wanted');
            $listCounters['friends'] = User::getNumberFriendsAndPending();
            $listCounters['profile_visitors'] = DB::count('users_view', "`user_to` = " . to_sql($guid));
            $responseData .='<script>cprofile.setCountersUserMenu(' . json_encode($listCounters) . ');</script>';
            if ($optionTmplName == 'urban_mobile') {
                $responseData .= "<script>cprofile.updateServerMyData("
                                . User::accessСheckFeatureSuperPowersGetList() . ");</script>";
            }

        } else {
            $requestUserId = intval(get_param('request_user_id'));
            if ($requestUserId && $guid != $requestUserId) {
                $responseData .= "<script>clProfile.updateServerDataUser("
                                 . intval(User::isOnline($requestUserId, null, true)) . ','
                                 . CIm::getCountMsgIm($requestUserId) . ");</script>";

            }
            $responseData .= "<script>clProfile.updateServerMyData("
                                . User::accessСheckFeatureSuperPowersGetList() . ");</script>";

            /* Counters */
            if (Common::isModuleCityActive()) {
                if (isset($listCounters['street_chat'])) {
                    $listCounters['street_chat'] = array('count' => $listCounters['street_chat']);
                }
                if (isset($listCounters['game_choose'])) {
                    $listCounters['game_choose'] = array('count' => $listCounters['game_choose']);
                }
                if (isset($listCounters['3d_city'])) {
                    $listCounters['3d_city']     = array('count' => $listCounters['3d_city']);
                }
            }
			$listCounters['live_list'] = array('count' => LiveStreaming::getTotalLiveNow());
			$listCounters['live_list_finished'] = array('count' => LiveStreaming::getTotalLiveFinished());

            $listCountersOthers = Menu::getListCounterImpactMobile();
            $listCounters = array_merge($listCounters, $listCountersOthers);
            $responseData .= "<script>updateMenuCounterAll(" . json_encode($listCounters) . ");</script>";
            /* Counters */
        }

        $numberNewImMessagesFromAllUsers = CIm::getCountNewMessages();
        $responseData .= "<script>mobileAppSetBadgeNumber($numberNewImMessagesFromAllUsers);</script>";

        /* Chat */
        $typeChat = array('audio', 'video');
        foreach ($typeChat as $type) {
            $chatData = Chat::update($type);
            if (Chat::isAction($chatData)) {
                $responseData .= "<script>" . $type . "Chat.request(" . json_encode($chatData) . ");</script>";
            }
        }
        $chatData = CityStreetChat::update();
        if ($chatData) {
            $responseData .= "<script>cityStreetChat.request(" . json_encode($chatData) . ");</script>";
        }

		/* Live */
		$liveId = get_param_int('live_id');
		if ($liveId && Common::isOptionActiveTemplate('live_enabled')) {
			$liveViewer = get_param_int('live_viewer');
			$liveIdEnd = get_param_int('live_id_end');

			if ($liveViewer) {
				LiveStreaming::updateMyViewer();
			} else {//Presenter
				LiveStreaming::setStatusLive();
				LiveStreaming::updateViewers();
			}

			$tmplsLive = array('main' => "{$dirTmpl}_live_streaming_ajax.html",
							   'likes' => "{$dirTmpl}_live_streaming_likes.html",
							   'comments_list' => "{$dirTmpl}_live_streaming_comment_item.html");
			if (!$liveIdEnd) {
				$tmplsLive['list_viewers'] = "{$dirTmpl}_live_streaming_list_viewers.html";
			}

			$page = new LiveStreaming('', $tmplsLive);
			$responseData .= '<div class="update_comments_live">' . getParsePageAjax($page) . '</div>';
		}
		/* Live */

        /* Chat */
        User::updateGeoPosition();
        /* Update */
    }

} elseif ($cmd == 'chat_invite') {
    $responseData = Chat::invite();
} elseif ($cmd == 'chat_reject') {
    $responseData = Chat::reject();
} elseif ($cmd == 'chat_talk') {
    $responseData = Chat::talk();
} elseif ($cmd == 'chat_paid') {
    $responseData = Chat::paid();
} elseif ($cmd == 'get_available_credits') {
    $responseData = $g_user['credits'];
} elseif ($cmd == 'city_street_chat_invite') {
    $responseData = CityStreetChat::invite();
} elseif ($cmd == 'city_street_chat_reject') {
    $responseData = CityStreetChat::reject();
} elseif ($cmd == 'city_street_chat_start') {
    $responseData = CityStreetChat::start();
/* Message */
/* Impact mobile */
} elseif ($cmd == 'update_profile_status') {
    $id = intval(get_param('id', '0'));
    $data = htmlentities(get_param('data', ''), ENT_QUOTES, 'UTF-8');
    $responseData =  User::updateProfileStatus($data, $id);
} elseif ($cmd == 'set_do_not_show_me_visitors') {
    User::update(array('set_do_not_show_me_visitors' => 1));
    $responseData = true;
}
/* Impact mobile */

// URBAN
if (isset($responsePage)) {
    echo getResponsePageAjaxAuth($responsePage);
}

if (isset($responseData)) {
    echo getResponseDataAjaxByAuth($responseData);
}

DB::close();