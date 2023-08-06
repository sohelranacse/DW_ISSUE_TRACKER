<?php

include("../_include/core/administration_start.php");

class CAddUser extends UserFields //CHtmlBlock
{
	function parseBlock(&$html)
	{
        global $g;
        global $l;
		parent::parseBlock($html);
	}
}

$page = new CAddUser('', $g['tmpl']['dir_tmpl_administration'] . 'add_group_user.html');
$page->formatValue = 'entities';

$header = new CAdminHeader("header", $g['tmpl']['dir_tmpl_administration'] . "_header.html");
$page->add($header);
$footer = new CAdminFooter("footer", $g['tmpl']['dir_tmpl_administration'] . "_footer.html");
$page->add($footer);

$page->add(new CGroupAdminPageMenuUsers());
include("../_include/core/administration_close.php");