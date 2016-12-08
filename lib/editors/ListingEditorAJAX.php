<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
    require_once("lib/Database.php");
	require_once("lib/Security/Security.php");
	require_once("lib/Utils.php");
	require_once("lib/editors/ListingEditorPage.php");
	require_once("lib/module/Module.php");

	// TODO: Ability to edit an existing listing
	/// The dialog box that appears after clicking the 'Create New Listing' button
	///  on the Listing Editor page.
	function newListingDialog($formName='', $formTitle='', $currModuleID='',
							$forceLogin='', $formDesc='', $formID='', $maxItems='', $createText=''){
		$output = "";
		$moduleOptions = "";
		$firstID = -1;
		foreach(Module::moduleNames() as $moduleID => $moduleName) {
			if($firstID < 0) {
				$firstID = $moduleID;
			}
			// If we are editing an existing form, we don't want to allow a module change
			if((Security::privilege(new ModulePrivilege("CreateList", $moduleID)) && $currModuleID=="") ||
					$currModuleID != "")
				$moduleOptions .= "<option value='$moduleID'>$moduleName</option>\n";
		}

		$pageOptions = loadOptions($firstID);
		$output .= <<<EOD
<form onsubmit="$(\'#editListingSubmitButton').click(); return false;">
<label for="listingName">Enter the new Listing Name:</label>
<input type="text" size="30" maxlength="20" id="listingName" value='$formName' /><br/><br/>
<label for="listingTitle">Listing Title:</label>
<input type="text" size="30" maxlength="20" id="listingTitle" value='$formTitle' /><br/><br/>
<label for="moduleID">Module:</label>
<select id="moduleID" onchange="loadModulePages()" >
$moduleOptions
</select>
<br/>
<label for="newEntryPageName">New Entry Creation Page:</label>
<br/>
<select id="newEntryPageName">
$pageOptions
</select>
<br/><br/>
<label for="maxItems">Maximum items per page:</label>
<input type="text" size="30" maxlength="20" id="maxItems" value='$maxItems' />
<br/><br/>
<label for="createText">Text for new item button (optional):</label>
<input type="text" size="30" maxlength="20" id="createText" value='$createText' />
<br/><br/>
<label for="forceLogin">Seconds since last logon (optional):</label>
<input type="text" size="30" maxlength="20" id="forceLogin" value='$forceLogin' />
<br/><br/>
<label for="listingDesc">Description (optional):</label>
<textarea type="text" rows="3" cols="30" id="listingDesc">$formDesc</textarea><br/><br/>
<div id="editListingSubmitButton">Submit</div>
</form>
<script type="text/javascript">
	submitListingScript("editListingSubmitButton", '$formID');
</script>
EOD;
		return $output;
	}

	function loadOptions($moduleID) {
		$pageQuery = sprintf(
			"SELECT Page.PageName,PageTitle
			FROM Page
			JOIN Form
				ON Form.PageName=Page.PageName
			WHERE ModuleID='%s'",
			mysql_real_escape_string($moduleID));
		$pageResult = Database::getInstance()->query($pageQuery);
		$options = "";
		while($pageObj = $pageResult->fetch_object()) {
			$options .= "<option value='$pageObj->PageName'>$pageObj->PageTitle</option>\n";
		}
		return $options;
	}

	function submitListingSettings($listingName, $listingTitle, $moduleID, $newEntryPageName, $maxItems,
					$createText, $listingDesc, $forceLogin) {
		$pageQuery = sprintf(
			"INSERT INTO Page
			(PageName, PageTitle, Removable, ModuleID, Description, ForceLogin) VALUES
			('%s', '%s', '1', '%s', '%s', '%s')",
			mysql_real_escape_string($listingName),
			mysql_real_escape_string($listingTitle),
			mysql_real_escape_string($moduleID),
			mysql_real_escape_string($listingDesc),
			mysql_real_escape_string($forceLogin));
		$pageResult = Database::getInstance()->query($pageQuery);
		if(Database::getInstance()->numAffectedRows() > 0) {
			$listingQuery = sprintf(
			"INSERT INTO Listing
				(PageName, MaxItems, NewEntryPageName, CreateText) VALUES
				('%s', '%s', '%s', '%s')",
				mysql_real_escape_string($listingName),
				mysql_real_escape_string($maxItems),
				mysql_real_escape_string($newEntryPageName),
				mysql_real_escape_string($forceLogin));
			$listingResult = Database::getInstance()->query($listingQuery);
		}
	}

	function newListBy($moduleID, $rank) {
		$moduleFieldQuery = sprintf(
			"SELECT ModuleFieldID
				FROM ModuleField
				WHERE ModuleID='%s'
				AND Hidden='0'",
				mysql_real_escape_string($moduleID));
		$moduleFieldResult = Database::getInstance()->query($moduleFieldQuery);

		$moduleFields = array();
		while($moduleFieldObject = $moduleFieldResult->fetch_object()){
			$moduleFields[] = FieldFactory::createModuleField($moduleFieldObject->ModuleFieldID);
		}
		
		if(count($moduleFields) < 1) {
			return "##Invalid module for listing";
		}

		$listBy = new ListBy($rank, $moduleFields[0]->moduleFieldID(), 1, 0, 0);

		return $listBy->toHTML($moduleFields);
	}

	function submitListingField($moduleFieldID, $position, $width, $includeLabel, $listingID, $linkPageName) {
		$includeLabel = $includeLabel === "true" ? 1 : 0;

		$pageNameQuery = sprintf(
			"SELECT *
				FROM Page
				WHERE PageName='%s'",
				mysql_real_escape_string($linkPageName));
		$pageNameResult = Database::getInstance()->query($pageNameQuery);
		$pageNameObject = $pageNameResult->fetch_object();
		$linkPageName = $pageNameObject ? "{$linkPageName}" : "NULL";
		$q = $pageNameObject ? "'" : "";

		$listingQuery = sprintf(
				"Select *
					FROM ListField
					WHERE ModuleFieldID='%s'
					AND ListingID='%s'",
					mysql_real_escape_string($moduleFieldID),
					mysql_real_escape_string($listingID));
		$listingResult = Database::getInstance()->query($listingQuery);
		if(!$listingResult->fetch_object()) {
			$listingQuery = sprintf(
				"INSERT INTO ListField
					(Position, ModuleFieldID, ListingID, IncludeLabel, Width, LinkPageName) VALUES
					('%s', '%s', '%s', b'%s', '%s', {$q}%s{$q})",
					mysql_real_escape_string($position),
					mysql_real_escape_string($moduleFieldID),
					mysql_real_escape_string($listingID),
					mysql_real_escape_string($includeLabel),
					mysql_real_escape_string($width),
					mysql_real_escape_string($linkPageName));
			$listingResult = Database::getInstance()->query($listingQuery);
		}
		else {
			$listingQuery = sprintf(
				"UPDATE ListField
					SET Position='%s', IncludeLabel=b'%s', Width='%s', LinkPageName={$q}%s{$q}
					WHERE ModuleFieldID='%s'
					AND ListingID='%s'",
					mysql_real_escape_string($position),
					mysql_real_escape_string($includeLabel),
					mysql_real_escape_string($width),
					mysql_real_escape_string($linkPageName),
					mysql_real_escape_string($moduleFieldID),
					mysql_real_escape_string($listingID));
			$listingResult = Database::getInstance()->query($listingQuery);
		}
	}

	function deleteFieldOptions($moduleFieldID, $listingID) {
		$listingQuery = sprintf(
			"SELECT *
				FROM ListField
				WHERE ModuleFieldID='%s'
				AND ListingID='%s'",
				mysql_real_escape_string($moduleFieldID),
				mysql_real_escape_string($listingID));
		$listingResult = Database::getInstance()->query($listingQuery);

		if ($listingResult->fetch_object()) {
			try {
				$listingQuery = sprintf(
					"DELETE FROM ListField
						WHERE ModuleFieldID='%s'
						AND ListingID='%s'",
						mysql_real_escape_string($moduleFieldID),
						mysql_real_escape_string($listingID));
				$listingResult = Database::getInstance()->query($listingQuery, 2, 1);
			}
			catch(Exception $e) {
				return "Uninclude failed";
			}
		}
	}

	function saveSortingOptions($sortField, $sortDirection, $sortType, $sortOrder, $rank, $listingID) {
		$sortType = $sortType === "true" ? 1 : 0;

		$listingQuery = sprintf(
			"Select *
				FROM ListBy
				WHERE ListingID='%s'
				AND Rank='%s'",
				mysql_real_escape_string($listingID),
				mysql_real_escape_string($rank));
		$listingResult = Database::getInstance()->query($listingQuery);
		if(!$listingResult->fetch_object()) {
			$listingQuery = sprintf(
				"INSERT INTO ListBy
					(Rank, ModuleFieldID, ListingID, Direction, Orientation, Type) VALUES
					('%s', '%s', '%s', b'%s', b'%s', b'%s')",
					mysql_real_escape_string($rank),
					mysql_real_escape_string($sortField),
					mysql_real_escape_string($listingID),
					mysql_real_escape_string($sortDirection),
					mysql_real_escape_string($sortOrder),
					mysql_real_escape_string($sortType));
			$listingResult = Database::getInstance()->query($listingQuery);
		}
		else {
			$listingQuery = sprintf(
				"UPDATE ListBy
					SET ModuleFieldID='%s', Direction=b'%s', Orientation=b'%s', Type=b'%s'
					WHERE ListingID='%s'
					AND Rank='%s'",
					mysql_real_escape_string($sortField),
					mysql_real_escape_string($sortDirection),
					mysql_real_escape_string($sortOrder),
					mysql_real_escape_string($sortType),
					mysql_real_escape_string($listingID),
					mysql_real_escape_string($rank));
			$listingResult = Database::getInstance()->query($listingQuery);
		}
	}

	function removeListBy($listingID, $rank) {
		$listByQuery = sprintf(
			"DELETE FROM ListBy
				WHERE ListingID='%s'
				AND Rank='%s'",
				mysql_real_escape_string($listingID),
				mysql_real_escape_string($rank));
		$listByResult = Database::getInstance()->query($listByQuery);
	}

	function saveFilterOptions($listingID, $filterField, $filterValue) {
		$listingQuery = sprintf(
			"Select *
				FROM ListFilter
				WHERE ListingID='%s'
				AND ModuleFieldID='%s'",
				mysql_real_escape_string($listingID),
				mysql_real_escape_string($filterField));
		$listingResult = Database::getInstance()->query($listingQuery);
		if(!$listingResult->fetch_object()) {
			$listingQuery = sprintf(
				"INSERT INTO ListFilter
					(ListingID, ModuleFieldID, Value) VALUES
					('%s', '%s', 'value:%s')",
					mysql_real_escape_string($listingID),
					mysql_real_escape_string($filterField),
					mysql_real_escape_string($filterValue));
			$listingResult = Database::getInstance()->query($listingQuery);
		}
		else {
			$listingQuery = sprintf(
				"UPDATE ListFilter
					SET Value='value:%s'
					WHERE ListingID='%s'
					AND ModuleFieldID='%s'",
					mysql_real_escape_string($filterValue),
					mysql_real_escape_string($listingID),
					mysql_real_escape_string($filterField));
			$listingResult = Database::getInstance()->query($listingQuery);
		}
	}

	function newListFilter($moduleID, $rank) {
		$moduleFieldQuery = sprintf(
			"SELECT ModuleFieldID
				FROM ModuleField
				WHERE ModuleID='%s'
				AND Hidden='0'",
				mysql_real_escape_string($moduleID));
		$moduleFieldResult = Database::getInstance()->query($moduleFieldQuery);

		$moduleFields = array();
		while($moduleFieldObject = $moduleFieldResult->fetch_object()){
			$moduleFields[] = FieldFactory::createModuleField($moduleFieldObject->ModuleFieldID);
		}

		if(count($moduleFields) < 1) {
			return "##Invalid module for listing";
		}

		$listFilter = new ListFilter($moduleFields[1]->moduleFieldID(), $rank, array());

		return $listFilter->toHTML($moduleFields);
	}

	function removeLastFilter($listingID, $filterField) {
		$listFilterQuery = sprintf(
			"DELETE FROM ListFilter
				WHERE ListingID='%s'
				AND ModuleFieldID='%s'",
				mysql_real_escape_string($listingID),
				mysql_real_escape_string($filterField));
		$listFilterResult = Database::getInstance()->query($listFilterQuery);
	}

	if(isset($_POST['command'])){
		if($_POST['command'] == 'newListingDialog'){
			print(newListingDialog());
		}
		elseif($_POST['command'] == 'loadOptions'){
			$moduleID = Utils::getPostInt("moduleID");
			print(loadOptions($moduleID));
		}
		elseif($_POST['command'] == 'saveFieldOptions'){
			$moduleFieldID = isset($_POST["moduleFieldID"]) ? $_POST["moduleFieldID"] : NULL;
			$position = isset($_POST["position"]) ? $_POST["position"] : NULL;
			$width = isset($_POST["width"]) ? $_POST["width"] : NULL;
			$includeLabel = isset($_POST["includeLabel"]) ? $_POST["includeLabel"] : NULL;
			$listingID = isset($_POST["listingID"]) ? $_POST["listingID"] : NULL;
			$linkPageName = isset($_POST["linkPageName"]) ? $_POST["linkPageName"] : NULL;
			print(submitListingField($moduleFieldID, $position, $width, $includeLabel, $listingID, $linkPageName));
		}
		elseif($_POST['command'] == 'deleteFieldOptions'){
			$moduleFieldID = isset($_POST["moduleFieldID"]) ? $_POST["moduleFieldID"] : NULL;
			$listingID = isset($_POST["listingID"]) ? $_POST["listingID"] : NULL;
			print(deleteFieldOptions($moduleFieldID, $listingID));
		}
		elseif($_POST['command'] == 'saveSortingOptions'){
			$sortField = isset($_POST["sortField"]) ? $_POST["sortField"] : NULL;
			$sortDirection = isset($_POST["sortDirection"]) ? $_POST["sortDirection"] : NULL;
			$sortType = isset($_POST["sortType"]) ? $_POST["sortType"] : NULL;
			$sortOrder = isset($_POST["sortOrder"]) ? $_POST["sortOrder"] : NULL;
			$rank = isset($_POST["rank"]) ? $_POST["rank"] : NULL;
			$listingID = isset($_POST["listingID"]) ? $_POST["listingID"] : NULL;
			print(saveSortingOptions($sortField, $sortDirection, $sortType, $sortOrder, $rank, $listingID));
		}
		elseif($_POST['command'] == 'newListBy'){
			$moduleID = isset($_POST["moduleID"]) ? $_POST["moduleID"] : NULL;
			$rank = isset($_POST["rank"]) ? $_POST["rank"] : NULL;
			print(newListBy($moduleID, $rank));
		}
		elseif($_POST['command'] == 'removeListBy'){
			$listingID = isset($_POST["listingID"]) ? $_POST["listingID"] : NULL;
			$rank = isset($_POST["rank"]) ? $_POST["rank"] : NULL;
			print(removeListBy($listingID, $rank));
		}
		elseif($_POST['command'] == 'saveFilterOptions'){
			$listingID = isset($_POST["listingID"]) ? $_POST["listingID"] : NULL;
			$filterField = isset($_POST["filterField"]) ? $_POST["filterField"] : NULL;
			$filterValue = isset($_POST["filterValue"]) ? $_POST["filterValue"] : NULL;
			print(saveFilterOptions($listingID, $filterField, $filterValue));
		}
		elseif($_POST['command'] == 'newListFilter'){
			$moduleID = isset($_POST["moduleID"]) ? $_POST["moduleID"] : NULL;
			$rank = isset($_POST["rank"]) ? $_POST["rank"] : NULL;
			print(newListFilter($moduleID, $rank));
		}
		elseif($_POST['command'] == 'removeLastFilter'){
			$listingID = isset($_POST["listingID"]) ? $_POST["listingID"] : NULL;
			$filterField = isset($_POST["filterField"]) ? $_POST["filterField"] : NULL;
			print(removeLastFilter($listingID, $filterField));
		}
	}
?>
