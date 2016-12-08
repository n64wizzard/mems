<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Exception.php");
	require_once("lib/Database.php");
	require_once("lib/Security/Security.php");
	require_once("lib/page/Page.php");

	class ListingInfo{
		private $listingID_, $pageName_, $maxItems_, $newEntryPageName_, $pageTitle_,
				$removable_, $moduleID_, $description_, $forceLogin_, $moduleName_;

		public function __construct($listingID, $pageName, $maxItems, $newEntryPageName, $pageTitle,
				$removable, $moduleID, $description, $forceLogin, $moduleName){
			$this->listingID_ = $listingID;
			$this->pageName_ = $pageName;
			$this->maxItems_ = $maxItems;
			$this->newEntryPageName_ = $newEntryPageName;
			$this->pageTitle_ = $pageTitle;
			$this->removable_ = $removable;
			$this->moduleID_ = $moduleID;
			$this->description_ = $description;
			$this->forceLogin_ = $forceLogin;
			$this->moduleName_ = $moduleName;
		}
		public function listingID(){ return $this->listingID_; }
		public function pageName(){ return $this->pageName_; }
		public function maxItems(){ return $this->maxItems_; }
		public function newEntryPageName(){ return $this->newEntryPageName_; }
		public function pageTitle(){ return $this->pageTitle_; }
		public function removable(){ return $this->removable_; }
		public function moduleID(){ return $this->moduleID_; }
		public function description(){ return $this->description_; }
		public function forceLogin(){ return $this->forceLogin_; }
		public function moduleName(){ return $this->moduleName_; }
	}

	class ListingEditorPage {
		private static function listings(){
			$listingQuery = sprintf(
				"SELECT ListingID, Listing.PageName, MaxItems, NewEntryPageName,
					Page.PageTitle, Page.Removable, Page.ModuleID, Page.Description, Page.ForceLogin,
					Module.Name AS ModuleName
				FROM Listing JOIN Page
					ON Listing.PageName=Page.PageName
				JOIN Module
					ON Module.ModuleID=Page.ModuleID");
			$listingResult = Database::getInstance()->query($listingQuery);

			$listings = array();
			while($listingObj = $listingResult->fetch_object()){
				$listings[] = new ListingInfo($listingObj->ListingID,
														$listingObj->PageName,
														$listingObj->MaxItems,
														$listingObj->NewEntryPageName,
														$listingObj->PageTitle,
														$listingObj->Removable,
														$listingObj->ModuleID,
														$listingObj->Description,
														$listingObj->ForceLogin,
														$listingObj->ModuleName);
			}
			return $listings;
		}
		public static function listingSelectPage(){
			$pageContents = "<div id='listingSelectPage'>";

			$pageContents .= "<select id='listingSelect' >\n";
			foreach(self::listings() as $listing){
				$pageContents .= "<option value='{$listing->listingID()}' >{$listing->PageTitle()}</option>";
			}
			$pageContents .= "</select>";

			$pageContents .= "\n<form onsubmit='editListing(); return false;'>";
			$pageContents .= "<br><input class='EditListingButton' type='Submit' value='Edit Listing' /></form>";

			$pageContents .= "\n<form onsubmit='return false;'>";
			$pageContents .= "</br><input class='CreateListingButton' type='Submit' value='Create New Listing' /></form>";

			$pageContents .= "<script type='text/javascript'>listingEditorScript();</script>";
			$newPage = new Page("Listing Editor", "listingEditor", $pageContents, 600);
			return $newPage;
		}

		public static function listingEditor($listingID){
			$pageNameQuery = sprintf(
				"SELECT PageName
					FROM Listing
					WHERE ListingID='%s'",
					mysql_real_escape_string($listingID));
			$pageNameResult = Database::getInstance()->query($pageNameQuery);
			$pageName = $pageNameResult ? $pageNameResult->fetch_object()->PageName : "";

			$moduleIDQuery = sprintf(
				"SELECT ModuleID
					FROM Page
					WHERE PageName='%s'",
					mysql_real_escape_string($pageName));
			$moduleIDResult = Database::getInstance()->query($moduleIDQuery);
			$moduleID = $moduleIDResult->fetch_object()->ModuleID;

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

			$pageQuery = sprintf(
				"SELECT PageName
					FROM Page");
			$pageResult = Database::getInstance()->query($pageQuery);

			$pageNames = array();
			while($pageNameObject = $pageResult->fetch_object()){
				$pageNames[] = $pageNameObject->PageName;
			}

			$pageContents = "";
			$pageContents .= <<<EOD
<a onclick='showHideOptions(500); return false;' href='#' id='view_options_link'>View Options</a><br/>
<div id='list_options'>
<div id='list_options_by_label'>Sort By</div>
<div id='list_options_filter_label'>Filters</div>
<div style='clear:both;'></div>
<form method="GET" id="list_options_form">
<div id='list_options_by' style='float:left;width:50%;'>
EOD;
			$listByQuery = sprintf(
				"SELECT ListByID, ModuleFieldID
					FROM ListBy
					WHERE ListingID='%s'
					ORDER BY Rank ASC",
					mysql_real_escape_string($listingID));
			$listByResult = Database::getInstance()->query($listByQuery);

			while($listByObject = $listByResult->fetch_object()){
				$listBy = ListBy::createListBy($listByObject->ListByID);
				$pageContents .= "<div>";
				$pageContents .= $listBy->toHTML($moduleFields);
				$pageContents .= "</div>";
			}
			$pageContents .= "</div>\n";

			$listFilterQuery = sprintf(
				"SELECT ListFilterID
					FROM ListFilter
					WHERE ListingID='%s'",
					mysql_real_escape_string($listingID));
			$listFilterResult = Database::getInstance()->query($listFilterQuery);

			$i = 0;
			$pageContents .= "<div id='list_options_filter' style='float:right;width:50%;text-align:right;'>\n";
			while($listFilterObject = $listFilterResult->fetch_object()){
				$listFilter = ListFilter::createListFilter($listFilterObject->ListFilterID, $i);
				$pageContents .= "<div id='filter_div_" . $i . "'>";
				$pageContents .= $listFilter->toHTML($moduleFields);
				$pageContents .= "</div>\n";
				$i++;
			}
			$pageContents .= <<<EOD
</div>
<div style='clear:both;'></div>
<div class='list_sort_buttons' style='float:left;' >
<input id='NewListByButton' type='submit' value='Add new sorting' onclick='newListBy({$moduleID}); return false;' />
</div>
<div class='list_sort_buttons' style='float:left;'>
<input id='DeleteListByButton' type='submit' value='Remove last sorting' onclick='removeLastListBy({$listingID}); return false;' />
</div>
<div id='list_options_update'>
<input name="Update" type="submit" value="Update" class="ListOptionUpdate"  onclick='submitListSortsAndFilters({$listingID}); return false;'/>
</div>
<div class='list_sort_buttons' style='float:right;'>
<input id='DeleteFilterButton' type='submit' value='Remove last filter' onclick='removeLastFilter({$listingID}); return false;' />
</div>
<div class='list_sort_buttons' style='float:right;' >
<input id='NewFilterButton' type='submit' value='Add new filter' onclick='newListFilter({$moduleID}); return false;' />
</div>
</form>
<div style='clear:both;'></div>
</div>
<br/>
EOD;
			$pageContents .= <<<EOD
<div id='list_fields'>
<form onsubmit='submitListFields({$listingID}); return false;' method="GET" id="list_fields_form">
EOD;
			$i = 0;
			foreach($moduleFields as $moduleField) {
				$listFilterQuery = sprintf(
					"SELECT *
						FROM ListField
						WHERE ListingID='%s'
						AND ModuleFieldID='%s'",
						mysql_real_escape_string($listingID),
						mysql_real_escape_string($moduleField->moduleFieldID()));
				$listFilterResult = Database::getInstance()->query($listFilterQuery);

				$listFilterObject = $listFilterResult->fetch_object();
				$position = $listFilterObject ? $listFilterObject->Position : 1;
				$width = $listFilterObject ? $listFilterObject->Width : 1;
				$checkedField = $listFilterObject ? "checked='1'" : "";
				$checkedLabel = $listFilterObject && $listFilterObject->IncludeLabel ? "checked='1'" : "";

				$linkPageQuery = sprintf(
					"SELECT LinkPageName
						FROM ListField
						WHERE ListingID='%s'
						AND ModuleFieldID='%s'",
						mysql_real_escape_string($listingID),
						mysql_real_escape_string($moduleField->moduleFieldID()));
				$linkPageResult = Database::getInstance()->query($linkPageQuery);
				$linkPageObject = $linkPageResult->fetch_object();

				$pageOptions = "<select name='link'>";
				$pageOptions .= "<option>None</option>";
				foreach($pageNames as $pageNameOption) {
					$selected = 
							$linkPageObject &&
								$linkPageObject->LinkPageName === $pageNameOption
							? "selected='selected' "
							: "";
					$pageOptions .= "<option {$selected}>{$pageNameOption}</option>";
				}
				$pageOptions .= "</select>";

				$pageContents .= <<<EOD
<div name='listing_field_div_{$i}' class="columnOptions">
<h4>{$moduleField->label()}</h4>
<div class='FieldLabel'><label style='padding-right:5px' >Position</label></div>
<div class='FieldContent' style='padding-right:5px' ><input type='Text' name='position' value='{$position}' ></div>
<div class='FieldLabel'><label style='padding-right:5px' >Width</label></div>
<div class='FieldContent' style='padding-right:5px' ><input type='Text' name='width' value='{$width}' ></div>
<div class='FieldLabel'><label style='padding-right:5px' >Include Field</label></div>
<div class='FieldContent' style='padding-right:5px' ><input type='Checkbox' name='includeField' {$checkedField} ></div>
<div class='FieldLabel'><label style='padding-right:5px' >Include Label</label></div>
<div class='FieldContent' style='padding-right:5px' ><input type='Checkbox' name='includeLabel' {$checkedLabel} ></div>
<div class='FieldLabel'><label style='padding-right:5px' >Link to page</label></div>
<div class='FieldContent' style='padding-right:5px' >{$pageOptions}</div>
<input type='Hidden' name='moduleFieldID' value='{$moduleField->moduleFieldID()}' >
<div style='clear:both;'></div></div>
EOD;
				$i++;
			}

			$pageContents .= <<<EOD
</br><input type='Submit' id='listFieldSubmit' value='Save field settings' >
</form>
</div>
<div style='clear:both;'></div>

<script type="text/javascript">
	jQuery.data(document.body, "moduleID", '{$moduleID}');
</script>
EOD;

			$page = new Page("Edit: " . $pageName, $pageName, $pageContents, 36000);
			return $page;
		}
	}
?>
