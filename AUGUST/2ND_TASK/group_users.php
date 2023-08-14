<?php

include('./_include/core/main_start.php');

checkByAuth();
if($g_user['role'] == "user")
	redirect('search_results');

class CGroupUsers extends CHtmlBlock
{
	function parseBlock(&$html)
	{
		global $g_user;
		$html->setvar("page_title", l('group_users'));
		parent::parseBlock($html);
	}
}
$dirTmpl = $g['tmpl']['dir_tmpl_main'];

$page = new CGroupUsers("", getPageCustomTemplate('group_users.html', 'custom_page_template'));
$header = new CHeader("header", $dirTmpl . "_header.html");
$page->add($header);

$footer = new CFooter("footer", $dirTmpl . "_footer.html");
$page->add($footer);

include("./_include/core/main_close.php");