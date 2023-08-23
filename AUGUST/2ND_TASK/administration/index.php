<?php
/* (C) Websplosion LLC, 2001-2021

IMPORTANT: This is a commercial software product
and any kind of using it must agree to the Websplosion's license agreement.
It can be found at http://www.chameleonsocial.com/license.doc

This notice may not be removed from the source code. */

include("../_include/core/administration_start.php");

class CAdminLogin extends CHtmlBlock
{
	var $message = "";
	var $login = "";
	function action()
	{
		global $g;
		global $l;
		$cmd = get_param("cmd", "");
		$cmd_ajax = get_param("cmd_ajax", "");

		if ($cmd == "logout")
		{
			set_session('replier_auth', '');
            set_session('replier_id', '');
			set_session('replier_name', '');

			set_session('admin_auth', '');
            set_session('admin_last_login', false);

            /*set_session("groupAdmin_auth", '');
            set_session("groupAdmin_id", '');
            set_session("groupAdmin_name", '');*/
			redirect("index.php");
		}
		elseif ($cmd == "login")
		{
			$pass = get_param("password", "");
			$login = get_param("login", "");

            $sql="SELECT * FROM `admin_replier` WHERE username=".to_sql($login, 'Text') ."
                AND password=".to_sql(md5($pass), 'Text')." LIMIT 1";

            $user=DB::row($sql);
            if($user){
                set_session("replier_auth", "Y");
                set_session("replier_id", $user['id']);
                set_session("replier_name", $user['username']);
                if(!$cmd_ajax) redirect("index.php");
            } elseif($login == 'admin') {
                $sql = 'SELECT COUNT(*) FROM admin_login WHERE
               success="N"
                AND  time >  DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                AND ip = ' . to_sql(IP::getIp(), 'Text') .'';

                $count = DB::result($sql);
                if($count < 5) {
                    if ($pass == $g['main']['admin_password'] and $login == 'admin') {
                        $sql = 'INSERT INTO admin_login
                            SET ip = ' . to_sql(IP::getIp(), 'Text') .
                            ',success = "Y"';
                        DB::execute($sql);
                        $sql = "DELETE FROM `admin_login` WHERE ip =".to_sql(IP::getIp(), 'Text') ."
                            AND `success` ='N'";
                        DB::execute($sql);
                        set_session("admin_auth", "Y");
                        if(!$cmd_ajax) redirect("index.php");
                    } else {
                        $sql = 'INSERT INTO admin_login
                            SET ip = ' . to_sql(IP::getIp(), 'Text') .
                            ',success = "N"';
                        DB::execute($sql);
                        if(!$cmd_ajax) redirect("index.php?login=error");
                    }
                }
            } else { // group admin
                /*$sql = 'SELECT user_id FROM user WHERE ban_global = 0 AND role = '.to_sql("group_admin").' AND mail = '.to_sql($login, 'Text');
                $groupUser=DB::row($sql);
                if(isset($groupUser)) {
                    $user=User::getUserByLoginAndPassword($login, $pass);

                    if($user){
                        set_session("groupAdmin_auth", "Y");
                        set_session("groupAdmin_id", $user['user_id']);
                        set_session("groupAdmin_name", $user['name']);
                        if(!$cmd_ajax) redirect("index.php");
                    }
                }*/
            }
		}
	}

	function parseBlock(&$html)
	{
		global $l;

        $cmd_ajax = get_param("cmd_ajax", "");
		if (!$cmd_ajax) {
			if ($html->varExists('page_url_logo')) {
				$html->setvar('page_url_logo', Common::getUrlLogo('logo', 'administration'));
			}
		}

        $sql = 'SELECT COUNT(*) FROM admin_login WHERE
           success="N"
            AND  time >  DATE_SUB(NOW(), INTERVAL 10 MINUTE)
            AND ip = ' . to_sql(IP::getIp(), 'Text') .'';
        $count = DB::result($sql);
        if($count >= 5 && $cmd_ajax) {
            $html->parse('admin_page_auth_time_error');
        }
        elseif ($cmd_ajax && (get_session("admin_auth") == "Y" || get_session("replier_auth") == "Y" || get_session("groupAdmin_auth") == "Y")){
            $html->parse("admin_page_auth");
        }
        elseif ($cmd_ajax) {
            $html->setvar("prevent_cache",time().rand(0,1000));
            $html->parse("admin_page_auth_error");
        } else {
            if(IS_DEMO) {
                $html->parse('demo');
            }
            $html->parse("admin_page");
        }

        if(get_param("login","")=="error") {
            $html->parse("message",true);
        }

        parent::parseBlock($html);
	}
}



$page = new CAdminLogin("", $g['tmpl']['dir_tmpl_administration'] . "index.html");

$cmd_ajax = get_param("cmd_ajax", "");

if(!$cmd_ajax) {
$header = new CAdminHeader("header", $g['tmpl']['dir_tmpl_administration'] . "_header.html");
$page->add($header);
$footer = new CAdminFooter("footer", $g['tmpl']['dir_tmpl_administration'] . "_footer.html");
$page->add($footer);
}

include("../_include/core/administration_close.php");