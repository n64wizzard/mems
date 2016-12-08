<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Database.php");

	/// Represents a single link in the navigation bar
	class NavLink{
		private $navMenuName_, /// The name of the menu this link belongs to
				$text_,	/// The label of the link
				$moduleInstanceID_,	/// If not NULL, the instance we want to visit
				$position_,	/// Where in the order of navlinks is this entry
				$group_,	/// The name of the drop-down group, NULL if not applicable
				$pageName_;	/// The page the user will be brought to after click

		public function __construct($navLinkID){
			$navLinkQuery = sprintf(
				"SELECT *
				FROM NavLink
				WHERE NavLinkID='%s'",
				mysql_real_escape_string($navLinkID));
			$navLinkObj = Database::getInstance()->query($navLinkQuery)->fetch_object();

			$this->navMenuName_ = $navLinkObj->NavMenuName;
			$this->text_ = $navLinkObj->Text;
			$this->moduleInstanceID_ = $navLinkObj->ModuleInstanceID;
			$this->position_ = $navLinkObj->Position;
			$this->group_ = $navLinkObj->Group;
			$this->pageName_ = $navLinkObj->PageName;

		}
		public function position(){ return $this->position_; }
		public function groupName(){ return $this->group_; }
		public function toHTML(){
			$output = "";
			$title = ($this->group_ == NULL ? "" : $this->group_ . " > ") . $this->text_;
			$linkURL = "";
			if(substr($this->pageName_, 0, 2) == "##")
				$linkURL = substr($this->pageName_, 2);
			else
				$linkURL = "index.php?Page=" . $this->pageName_ . "&MIID=" . $this->moduleInstanceID_;
			$output .= "<li class=''><a href='{$linkURL}' title='{$title}' >{$this->text_}</a></li>\n";

			return $output;
		}
	}

	/// A group of navlinks when they all belong to the same drop-down menu group
	class NavLinkGroup{
		private $navLinks_, $groupName_;
		public function __construct($groupName){
			$this->groupName_ = $groupName;
			$this->navLinks_ = array();
		}
		public function toHTML(){
			$output = "";
			$output .= "<li class='drop'><a href='#' title='{$this->groupName()}' >{$this->groupName()}</a>\n";
			$output .= "<ul>";
			foreach($this->navLinks_ as $navLink)
				$output .= $navLink->toHTML();
			$output .= "</ul></li>";
			return $output;
		}
		public function groupName(){ return $this->groupName_; }
		public function navLinkIs($newNavLink){ $this->navLinks_[$newNavLink->position()] = $newNavLink; }
	}
?>
