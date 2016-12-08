<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Database.php");
	require_once("lib/Listing/ListEntry.php");

	/// A group/collection of List Entries (may be nested in other ListContainers)
	/// Each container is associated with a ListBy object that determines how
	///  the HTML is constructed.
	class ListContainer{
		private $listEntries, $listBy_;
		function  __construct() {
			$this->listEntries_ = array();
		}
		
		public function listEntryIs($listEntry, $listBys){
			$this->listBy_ = array_pop($listBys);
			$moduleFieldValue = $listEntry->moduleFieldValue($this->listBy_->moduleFieldID());
			if(count($listBys) == 0){
				$this->listEntries_[$moduleFieldValue] = $listEntry;
			}
			else{
				if(array_key_exists($moduleFieldValue, $this->listEntries_) === false){
					$newListEntry = new ListContainer();
					$this->listEntries_[$moduleFieldValue] = $newListEntry;
				}
				$this->listEntries_[$moduleFieldValue]->listEntryIs($listEntry, $listBys);
			}
		}
		protected function listBy(){ return $this->listBy_; }

		/// @return A string of HTML code with this and all contained fields
		public function toHTML($totalWidth){
			$label = $this->listBy()->moduleFieldLabel();
			$orientation = $this->listBy()->orientation();

			$output = "";
			foreach($this->listEntries_ as $moduleFieldValue => $listEntry){
				if($orientation)
					$output .= "<div class='ListGroup'>\n";
				else
					$output .= "<div style='width:100%;padding:1pt;'>\n";

				if($this->listBy()->type())	// If the type is a grouping
					$output .= $moduleFieldValue . "</br>";

				$output .= $listEntry->toHTML($totalWidth, !$orientation);
				$output .= "</div>\n";
			}
			if($orientation)
				$output .= "<div style='clear:both;'></div>";

			return $output;
		}

		/// @return the number of list entries in the list container
		public function size(){ return count($this->listEntries_); }
	}
?>
