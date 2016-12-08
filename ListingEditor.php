<?php
	ob_start('ob_gzhandler');

	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Exception.php");
	require_once("lib/Database.php");
	require_once("lib/Security/Security.php");
	require_once("lib/editors/ListingEditorPage.php");
	require_once("lib/sessions/UserSession.php");
	require_once("lib/sessions/ProxyCheck.php");

	if(count(ProxyCheck::checkHeaders($_SERVER)) >= 1)
		throw new LogonException("Attempting to access site behind a proxy. IID($pageInstanceID); Page($pageName); PID($pageID)");

	// Check for a valid session
	$userSession = new User();

	$listingID = isset($_GET["listingID"]) && $_GET["listingID"] != "" ? $_GET["listingID"] : NULL;
	if(isset($listingID) && preg_match('/^[0-9]{1,10}$/', $listingID) != 1)
		throw new InvalidArgumentException("Invalid listingID: $listingID");

	if(isset($listingID))
		$page = ListingEditorPage::listingEditor($listingID);
	else
		$page = ListingEditorPage::listingSelectPage();

	if($page->forceLogin() != 0 && ($userSession->timestamp() + $page->forceLogin() < time() || !$userSession->logged())){
		$userSession->logOut();
		$page = Page::createPage("Logon");
	}

	print($page->toHTML());

	ob_end_flush();
?>