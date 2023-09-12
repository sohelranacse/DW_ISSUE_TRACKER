<?php
/* (C) Websplosion LLC, 2001-2021

IMPORTANT: This is a commercial software product
and any kind of using it must agree to the Websplosion's license agreement.
It can be found at http://www.chameleonsocial.com/license.doc

This notice may not be removed from the source code. */

class CJoinPage extends CHtmlBlock {

    var $message = '';

    function action()
    {
        global $g;
        global $l;

        $optionTmplSet = Common::getOption('set', 'template_options');

        $cmd = get_param('cmd', '');
        $ajax = get_param('ajax');

        if ($cmd == 'fb_login') {
            $this->message = Social::login();
        } elseif ($cmd == 'please_login') {
            $this->message = l('Please login') . '<br>';
        } elseif ($cmd == 'wait_approval') {
            $this->message = l('no_confirmation_account') . '<br>';
        }
        if ($cmd == "sent") {
            $this->message = l('The password has been sent!') . '<br>';
        } elseif ($cmd == 'login') {

            /*$loginField = 'name';
            if (Common::isOptionActive('login_by_mail', 'template_options')) {
                $loginField = 'mail';
            }*/

            $login = get_param('user', '');
            $password = get_param('password', '');
            if (!$login || !$password) {
                $user = false;
                $id = 0;
            } else {
                /*$whereLogin = to_sql($loginField, 'Plain') . ' = ' . to_sql($login, 'Text');

                if (Common::isOptionActive('login_by_name_or_mail', 'template_options')) {
                    $whereLogin = '(name = ' . to_sql($login, 'Text') . ' OR mail = ' . to_sql($login, 'Text') . ')';
                }*/

                $user = User::getUserByLoginAndPassword($login, $password);
                $id = 0;
                $userApproval = 0;
                $userBan = 0;
            }

            if ($user != false) {
                $id = $user['user_id'];
                $userApproval = $user['active'];
                $userBan = $user['ban_global'];
                $password = $user['password'];
                $role = $user['role'];
            }

            if ($id == 0) {
                if ($login !== '') {
                    $this->message = l('Incorrect username') . '<br>';
                } else {
                    if (!$ajax) {
                        redirect(Common::pageUrl('login'));
                    }
                }
                if ($ajax) {
                    $this->message = '#js:error:' . l('Wrong password or log in information');
                }
                if ($optionTmplSet != 'urban' && Common::isMobile()) {
                    redirect('index.php?cmd=login_incorrect');
                }
            } else {
                $demoLogin = get_param('demo_login', 0);
                if ($optionTmplSet != 'urban' && $demoLogin == 1 && IS_DEMO) {
                    redirect('index.php');
                }
                if (Common::isOptionActive('manual_user_approval') && !$userApproval) {
                    if ($optionTmplSet != 'urban' && Common::isMobile()) {
                        redirect('index.php?cmd=wait_approval');
                    }
                    if ($ajax) {
                        $this->message = '#js:error:' . l('no_confirmation_account');
                    } else {
                        $this->message = l('no_confirmation_account') . '<br>';
                    }
                }
                if($userBan==1){
                    if ($ajax) {
                        $this->message = '#js:error:' . l('account_has_been_banned');
                    } else {
                        $this->message = l('account_has_been_banned') . '<br>';
                    }
                    if ($optionTmplSet != 'urban' && Common::isMobile()) {
                        redirect('index.php?cmd=account_banned');
                    }
                }

            }

            if ($this->message == '') {
                if($role == 'group_admin')
                    $this->message = '#js:logged:group_users';
                else
                    $this->message = '#js:logged:' . Common::getHomePage();

                delses('logout');
                set_session('user_id', $id);
                set_session('user_id_verify', $id);
                if (get_param('remember', '') != '') {
                    $name = User::getInfoBasic($id, 'name');
                    set_cookie('c_user', $name, -1);
                    set_cookie('c_password', $password, -1);
                } else {
                    set_cookie('c_user', '', -1);
                    set_cookie('c_password', '', -1);
                }

                User::updateLastVisit($id);

                CStatsTools::count('logins', $id);                

                if (!$ajax) {
                    $redirect = urldecode(get_param('demo_redirect'));
                    if($redirect) {
                        redirect($redirect);
                    }
                    Common::toHomePage();
                }
            }
        }
    }

    function parseBlock(&$html)
    {
        global $g_info;

        $cmd = get_param('cmd');

        /* URBAN */
        if ($html->varExists('is_ios')) {
            $isIos = Common::isAppIos();
            $html->setvar('is_ios', intval($isIos));
            if ($isIos) {
                $html->parse('login_frm_terms', false);
            }
        }
        $optionTemplateName = Common::getOption('name', 'template_options');
        if ($optionTemplateName == 'urban') {
            $user = get_param('user');
            $autocompleteOffJs = array();
            if ($user != '') {
                $field = Common::validateEmail($user) ? 'email' : 'join_handle';
                $html->setvar($field, $user);
                //$autocompleteOffJs[$field] = 1;
            }
            $pass = get_param('password');
            if ($pass != '') {
                $html->setvar('join_password', $pass);
                //$autocompleteOffJs['join_password'] = 1;
            }
            // Do not need no form now, do not be auto complete
            /*if(isset($_SERVER['HTTP_USER_AGENT'])) {
                if (preg_match('/Chrome/i', $_SERVER['HTTP_USER_AGENT'])) {
                    $html->setvar('autocomplete', json_encode($autocompleteOffJs));
                    $html->parse('autocomplete_js');
                }
            }*/
        }
        /* URBAN */

        $urlFrom = get_param('url_from', Common::refererFromSite());
        $html->setvar('url_from', $urlFrom);

        foreach ($g_info as $k => $v) {
            $html->setvar($k, $v);
        }

        $html->setvar("login_message", $this->message);

        $cmd = get_param('cmd');

        if($this->message && $cmd == 'sent') {
            $html->parse('alert');
        }
        if ($this->message && $cmd == 'wait_approval') {
            $html->parse('wait_approval');
        }

        if(IS_DEMO) {
            $html->setvar('login_user', demoLogin());
            $html->setvar('login_password', '1234567');
            $html->parse('demo');
        }

        $blockAlert = 'alert_msg';
        if ($html->blockExists($blockAlert)) {
            $alertMsg = get_session($blockAlert);
            if ($alertMsg) {
                delses($blockAlert);
                $html->setvar($blockAlert, l($alertMsg));
                $html->parse($blockAlert, false);
            }
        }
        $block = 'social_login';
        if ($optionTemplateName == 'edge') {
            $block = 'log_in_social';
        }
        Social::parse($html, $block);

        if ($cmd == 'please_login') {
            $html->parse('bl_link_join', false);
        }

        parent::parseBlock($html);
    }

}