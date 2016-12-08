<?php
	ob_start('ob_gzhandler');

	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Exception.php");
	require_once("lib/Database.php");
	require_once("lib/Security/Security.php");
	require_once("lib/Security/PermissionPage.php");
	require_once("lib/sessions/UserSession.php");
	require_once("lib/sessions/ProxyCheck.php");

	if(count(ProxyCheck::checkHeaders($_SERVER)) >= 1)
		throw new LogonException("Attempting to access site behind a proxy: Permissions Page MID: ($moduleID)");

	$roleID = isset($_GET["roleID"]) && $_GET["roleID"] != "" ? $_GET["roleID"] : NULL;
	if(isset($roleID) && preg_match('/^[0-9]{1,10}$/', $roleID) != 1)
		throw new InvalidArgumentException("Invalid roleID: $roleID");

	$page = NULL;
	if(isset($roleID))
		$page = PermissionPage::permissionsPage($roleID);
	else
		$page = PermissionPage::roleListPage();

	$userSession = new User();
	if($page->forceLogin() != 0 && ($userSession->timestamp() + $page->forceLogin() < time() || !$userSession->logged())){
		$userSession->logOut();
		$page = Page::createPage("Logon");
	}

	print($page->toHTML());

	ob_end_flush();
?>
