<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Database.php");
	require_once("lib/Position.php");

	/// Defines a ListField type that will be contained within a Listing,
	///		but does not contain an actual FormField
	class ListField{
		private $position_, /// Position of the ListField within a single entry
				$moduleFieldID_,
				$includeLabel_,
				$linkPageName_,
				$width_;

		function  __construct($listFieldID) {
			$listFieldQuery = sprintf(
				"SELECT *
				FROM ListField
				WHERE ListFieldID='%s'",
				mysql_real_escape_string($listFieldID));
			$listFieldObj = Database::getInstance()->query($listFieldQuery, 1, 1)->fetch_object();

			$this->moduleFieldID_ = $listFieldObj->ModuleFieldID;
			$this->includeLabel_ = $listFieldObj->IncludeLabel;
			$this->linkPageName_ = $listFieldObj->LinkPageName;
			$this->position_ = $listFieldObj->Position;
			$this->width_ = $listFieldObj->Width;
		}
		public function position(){ return $this->position_; }
		public function moduleFieldID(){ return $this->moduleFieldID_; }
		public function width(){ return $this->width_; }
		public function linkPageName(){ return $this->linkPageName_; }
	}
?>
