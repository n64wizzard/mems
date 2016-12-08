<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Database.php");
	require_once("lib/Listing/ListContainer.php");
	require_once("lib/Listing/ListEntry.php");
	require_once("lib/Listing/ListField.php");
	require_once("lib/Listing/ListBy.php");
	require_once("lib/Listing/ListOption.php");
	require_once("lib/Listing/ListFilter.php");
	
	class Listing{
		private	$pageName_,
				$moduleID_,
				$moduleInstanceID_,

				$listEntryContainer_,	/// The "Base" container
				$listFields_,	/// Array of the list fields for this Listing
				$listBy_,	/// Array of the ListBys for this Listing
				$listFilters_,	/// Array of the filters for this listing
				$listOptions_,	/// Array of the options for this listing
				$newEntryPageName_,	/// The form to load if the user wants to create a new instance

				$pageNumber_,	/// The current page number (if # entries > maxitems)
				$totalPages_,	/// Total pages (should be # entries / maxitems)
				$maxItems_,		/// Maximum number of items we want to show per listing page
				$createText_,	/// The text of the create new button
				$totalWidth_;	/// The sum of the widths of all ListFields

		/// @param $miidFilter If not null, a ModuleInstanceID to filter the results by
		function __construct($listingID, $miidFilter=NULL){
			$iniArray = Utils::iniSettings();
			
			$moduleQuery = sprintf(
					"SELECT ModuleID,Listing.PageName
					FROM Page JOIN Listing ON Page.PageName=Listing.PageName
					WHERE ListingID='%s'",
					mysql_real_escape_string($listingID));
			$moduleObj = Database::getInstance()->query($moduleQuery, 1, 1)->fetch_object();
			$moduleID = $moduleObj->ModuleID;
			$this->pageName_ = $moduleObj->PageName;
			$this->moduleInstanceID_ = $miidFilter;
			$this->optionsHidden_ = true;

			// Eg. If the user wants to see all the crews associated with an event, they may want to filter-
			//	by the Event's ModuleInstanceID.  If it is included in the URL, then the filtering will occur here
//			$midFilter = NULL;
//			if(isset($miidFilter)){
//				$moduleIDQuery = sprintf(
//						"SELECT ModuleID
//						FROM ModuleInstance
//						WHERE ModuleInstanceID='%s'",
//						mysql_real_escape_string($miidFilter));
//				$moduleObj = Database::getInstance()->query($moduleIDQuery, 1, 1)->fetch_object();
//				$midFilter = $moduleObj->ModuleID;
//			}

			// Load the general Listing settings
			$listingQuery = sprintf(
				"SELECT *
				FROM Listing
				WHERE ListingID='%s'",
				mysql_real_escape_string($listingID));
			$listingResultObj = Database::getInstance()->query($listingQuery, 1, 1)->fetch_object();
			$this->maxItems_ = $listingResultObj->MaxItems;
			$this->newEntryPageName_ = $listingResultObj->NewEntryPageName;
			$this->moduleID_ = $moduleID;
			$this->createText_ = $listingResultObj->CreateText;

			// Load all of the ListFields (ie. Which ModuleFields) that will be displayed
			//  in this Listing
			$this->listFields_ = array();
			$this->totalWidth_ = 0;
			$listFieldsQuery = sprintf(
				"SELECT ListFieldID
				FROM ListField
				WHERE ListingID='%s'",
				mysql_real_escape_string($listingID));
			$listFieldsResult = Database::getInstance()->query($listFieldsQuery);
			while($listFieldObj = $listFieldsResult->fetch_object()){
				$newListField = new ListField($listFieldObj->ListFieldID);
				$this->listFields_[$newListField->position()] = $newListField;
				$this->totalWidth_ += $newListField->width();
			}

			// Each ListEntry may have a drop-down menu of links to related pages.
			//	Which pages are considered related are specified here
			$this->listOptions_ = array();
			$listOptionsQuery = sprintf(
				"SELECT Title,PageName
				FROM ListOption
				WHERE ListingID='%s'",
				mysql_real_escape_string($listingID));
			$listOptionsResult = Database::getInstance()->query($listOptionsQuery);
			while($listOptionObj = $listOptionsResult->fetch_object())
				$this->listOptions_[] = new ListOption($listOptionObj->Title, $listOptionObj->PageName);

			// Add sorting and grouping elements
			$this->listBy_ = array();
			$orderByStr = "";
			$listByQuery = sprintf(
				"SELECT ListByID
				FROM ListBy
				WHERE ListingID='%s'
				ORDER BY Rank",
				mysql_real_escape_string($listingID));
			$listByResult = Database::getInstance()->query($listByQuery);
			while($listByObj = $listByResult->fetch_object()){
				$newListBy = ListBy::createListBy($listByObj->ListByID);
				$this->listBy_[] = $newListBy;
				
				//  Update $newListBy by user values in GET
				$rank = $newListBy->rank();
				$field = Utils::get("by_{$rank}_field");
				$orientation = Utils::get("by_{$rank}_or");
				$type = Utils::get("by_{$rank}_type");
				$direction = Utils::get("by_{$rank}_dir");
				if(isset($direction) && preg_match("/[01]{1}/s", $direction))
					$newListBy->directionIs($direction);
				if(isset($field) && preg_match("/[0-9]{1,5}/s", $field)){
					$newListBy->moduleFieldIDIs($field);
					$newListBy->typeIs(0);	// Since the checkbox will not be transmitted unless checked
				}
				if(isset($orientation) && preg_match("/[01]{1}/s", $orientation))
					$newListBy->orientationIs ($orientation);
				if(isset($type) && preg_match("/1/s", $type))
					$newListBy->typeIs(1);
	
				$orderByStr .= $newListBy->toSQL() . ",";
			}
			$orderByStr = substr($orderByStr, 0, -1);	// Remove the trailing ','
			
			// Add filter elements
			$this->listFilters_ = array();
			$filterStr = "";
			$listFiltersQuery = sprintf(
				"SELECT ListFilterID
				FROM ListFilter
				WHERE ListingID='%s'",
				mysql_real_escape_string($listingID));
			$listFiltersResult = Database::getInstance()->query($listFiltersQuery);
			$filterCount = 0;
			while($listFilterObj = $listFiltersResult->fetch_object()){
				$newListFilter = NULL;
				
				// Update $newListBy by user values in GET
				$filterValues = Utils::arrayKeySearch("filter_{$filterCount}_", $_GET);

				if(isset($filterValues["field"]))
					$newListFilter = new ListFilter($filterValues["field"], $filterCount, $filterValues);
				else
					$newListFilter = ListFilter::createListFilter($listFilterObj->ListFilterID, $filterCount);

				$this->listFilters_[] = $newListFilter;
				$filterStr .= "AND " . $newListFilter->filterSQL() . " ";
				$filterCount++;
			}

			// If the MIID is set, ignore all of the other filters, and only accept
			// list entries if at least one of their fields has the MIID in it
			// PENDING: Possible bug: if MII='1', then it will accept '10', '21', etc.
			if(isset($_GET["MIID"]) && $_GET["MIID"] != "")
				$filterStr = $pageQuery = sprintf("AND (CONVERT(AES_DECRYPT(MFI.Value, '%s'), CHAR) REGEXP '%s')",
					$iniArray["passCode"],
					mysql_real_escape_string($_GET["MIID"]));

			// We only want to load the data for all the listings that will
			//  actually be shown on this page (determined by maxitmes).  So here
			//  we will determine the IDs of the first and last of those.
			$this->pageNumber_ = isset($_GET["PN"]) && preg_match('/^[0-9]{0,5}$/', $_GET["PN"]) == 1 ? $_GET["PN"] : 1;
			$pageQuery = sprintf(
				"SELECT AES_DECRYPT(MFI.Value, '%s') AS Value, MFI.ModuleFieldID, count(DISTINCT MFI.ModuleInstanceID) As Count
				FROM ModuleInstance AS MI
					NATURAL JOIN ModuleFieldInstance AS MFI
				WHERE ModuleInstanceID = ANY(
					SELECT MI.ModuleInstanceID
					FROM ModuleInstance AS MI
					NATURAL JOIN ModuleFieldInstance AS MFI
					WHERE TRUE %s
				)
				GROUP BY IF(MFI.ModuleFieldID='%s', AES_DECRYPT(MFI.Value, '%s'), 0)
				HAVING MFI.ModuleFieldID='%s'",
				mysql_real_escape_string($iniArray["passCode"]),
				$filterStr,
				$this->listBy_[0]->moduleFieldID(),
				mysql_real_escape_string($iniArray["passCode"]),
				$this->listBy_[0]->moduleFieldID());
			$pageQueryResult = Database::getInstance()->query($pageQuery);

			$totalGroups = 0;
			$totalEntries = 0;
			$startGroupValue = NULL;
			$endGroupValue = NULL;
			while($pageObj = $pageQueryResult->fetch_object()){
				$totalEntries += $pageObj->Count;

				if($totalGroups / $this->maxItems() == $this->pageNumber_ - 1)
					$startGroupValue = $pageObj->Value;
				if($totalGroups / $this->maxItems() < $this->pageNumber_)
					$endGroupValue = $pageObj->Value;

				$totalGroups++;
			}
			$this->totalPages_ = ceil($totalGroups / $this->maxItems());

			// Now we actually determine all of the MIIDs we want
			// PENDING: Try and merge this query with the previous one
			$overallOrderStr = "AND IF(MFI.ModuleFieldID='{$this->listBy_[0]->moduleFieldID()}', AES_DECRYPT(MFI.Value, '{$iniArray["passCode"]}') BETWEEN '$startGroupValue' AND '$endGroupValue', 0)";
			$moduleInstancesQuery = sprintf(
				"SELECT DISTINCT MFI.ModuleInstanceID
				FROM ModuleInstance AS MI
					JOIN ModuleFieldInstance AS MFI ON MFI.ModuleInstanceID = MI.ModuleInstanceID
				WHERE MI.ModuleID='%s'
					AND MI.ModuleInstanceID = ANY(
						SELECT MI.ModuleInstanceID
						FROM ModuleInstance AS MI
						NATURAL JOIN ModuleFieldInstance AS MFI
						WHERE TRUE %s
					)
					%s
				ORDER BY %s",
				mysql_real_escape_string($moduleID),
				$filterStr,	// We do not use escape_string here, as the sub-elements of $filterStr have already been escaped (and we get an error if we do so)
				$startGroupValue != "" ? $overallOrderStr : "",
				$orderByStr,	// Same reason here
				mysql_real_escape_string($this->pageNumber_));
			$moduleInstancesResult = Database::getInstance()->query($moduleInstancesQuery);
			$numberOfResults = $moduleInstancesResult->num_rows;

			$this->listEntryContainer_ = new ListContainer();
			$displayCount = 0;
			while($moduleInstanceObj = $moduleInstancesResult->fetch_object()){
				$newModuleInstance = ModuleInstance::createModuleInstance($moduleInstanceObj->ModuleInstanceID, $moduleID);
				if($newModuleInstance->hasFields()){
					$listEntry = new ListEntry($newModuleInstance, $this->listFields_, $this->listOptions_);
					$this->listEntryContainer_->listEntryIs($listEntry, $this->listBy_);
					$displayCount++;
				}
			}
		}

		protected function orientation(){ return $this->orientation_; }
		protected function maxItems(){ return $this->maxItems_; }
		protected function totalWidth(){ return $this->totalWidth_; }
		protected function newEntryPageName(){ return $this->newEntryPageName_; }
		protected function listEntryContainer(){ return $this->listEntryContainer_; }
		protected function moduleInstanceID(){ return $this->moduleInstanceID_; }
		protected function pageName(){ return $this->pageName_; }
		protected function moduleID(){ return $this->moduleID_; }
		public function toHTML(){
			$output = "";
			
			// If the user previously had the pane open, it should stay open
			$optionsStyle = "style='display: none;'";
			if(count($_GET) > 2)
				$optionsStyle = "style='display: block;'";

			$output .= <<<EOD
<a onclick='showHideOptions(500); return false;' href='#' id='view_options_link'>View Options</a><br/>
<div id='list_options' $optionsStyle>
<div id='list_options_by_label'>Sort By</div>
<div id='list_options_filter_label'>Filters</div>
<div style='clear:both;'></div>
<form onsubmit='submitListOptions(); return false;' method="GET" id="list_options_form">
<input type='hidden' name='Page' value='{$this->pageName()}' />
<input type='hidden' name='MIID' value='{$this->moduleInstanceID()}' />
<div name='list_options_by' style='float:left;width:50%;'>
EOD;
			$module = Module::createModule($this->moduleID());
			$moduleFields = array();
			foreach($module->moduleFields() as $moduleField)
				$moduleFields[] = $moduleField;

			foreach($this->listBy_ as $listBy){
				$output .= $listBy->toHTML($moduleFields);
			}
			$output .= "</div>\n";
			
			$output .= "<div id='listOptionsFilter'>\n";
			if(isset($_GET["MIID"]) && $_GET["MIID"] != "")
				$output .= "<div>Module Instance ID: " . $_GET["MIID"] . "</div>\n";

			else	// For the time-being, if we the MIID is set, we don't want to have to deal with other filters
				foreach($this->listFilters_ as $listFilter){
					$output .= "<div id='filter_div_{$listFilter->filterCount()}'>";
					$output .= $listFilter->toHTML($moduleFields);
					$output .= "</div>\n";
				}

			$output .= <<<EOD
</div>
<div style='clear:both;'></div>
<div id='list_options_update'>
<input name="Update" type="submit" value="Update" class="ListOptionUpdate"/>
</div>
</form>
<div style='clear:both;'></div>
</div>
<br/>
EOD;
			
			$allVertical = true;
			foreach($this->listBy_ as $listBy)
				$allVertical &= !$listBy->orientation();
			// Print out the column titles in their own DIV
			if($allVertical){
				$module = Module::createModule($this->moduleID_);
				$output .= "<div style='float: left; width:100%;'>";
				foreach($this->listFields_ as $listField){
					$moduleFieldID = $listField->moduleFieldID();
					if(!isset($moduleFieldID) || !Security::privilege(new ModuleFieldPrivilege("Read", $moduleFieldID))) continue;
					$output .= "<div class='listHeader' style='width:" . ($listField->width() / $this->totalWidth())*100 . "%;'>";
					$output .= $module->moduleField($moduleFieldID)->label();
					$output .= "</div>\n";
				}
				$output .= "</div>\n";
				$output .= "<div style='clear:both;'></div>";
			}

			// Now, if we have any data to display, do so
			if($this->listEntryContainer()->size() == 0)
				$output .= "No data found";
			else{
				$output .= "<div id='Listing' style='border-width:thin; border-style:solid'>\n";
				$output .= $this->listEntryContainer()->toHTML($this->totalWidth());
				$output .= "</div>\n";
			}

			// Create the "Create New" button
			$output .= "<div id='below_listing'>";
			if(Security::privilege(new ModulePrivilege("CreateInstance", $this->moduleID()))){
				$output .= "<div id='create_new_listing_button' style='float: left; width:33%'>";
				$newEntryPageName = $this->newEntryPageName();
				if(isset($newEntryPageName))
					$output .= "<a href='index.php?Page=$newEntryPageName' id='list_create_link'>{$this->createText_}</a>";
				$output .= "</div>";
			}

			// Create the links to progress to the next or previous pages of list entries
			$output .= "<div id='listingPageLinks'>";
			$count = 3;	// Number of pages in either direct in addition to first/last) we want to have buttons to
			if($this->pageNumber_ > $count)	// Place link for "first" (if not the current page)
				$output .= "<a class='listingPageLink' href='" . Utils::modify_url(array("PN" => 0)) . "'>First</a>";
			for($i = ($this->pageNumber_ - $count > 1 ? $this->pageNumber_ - $count : 1);
				$i < $this->pageNumber_ + $count && $i <= $this->totalPages_; $i++){
				if($i == $this->pageNumber_)
					$output .= "<span class='listingCurrentPage'>$i</span>";
				else
					$output .= "<a class='listingPageLink' href='" . Utils::modify_url(array("PN" => $i)) . "'>$i</a>";
			}
			if($this->pageNumber_ + $count < $this->totalPages_)	// Place a link for last (if not the current page)
				$output .= "<a class='listingPageLink' href='" . Utils::modify_url(array("PN" => $this->totalPages_)) . "'>Last</a>";
			
			$output .= <<<EOD
</div>
<div style='clear:both;'></div>
</div>
<script type="text/javascript">
	jQuery.data(document.body, "moduleID", '{$this->moduleID()}');
</script>
EOD;

			return $output;
		}
	}
?>