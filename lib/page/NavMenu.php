<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Database.php");
	require_once("lib/page/NavLink.php");

	/// The navigation menu that is displayed across the top of each page
	class NavMenu{
		private $navLinks_, $navMenuName_, $navGroups_;
		public function __construct($navMenuName){
			$navLinksQuery = sprintf(
				"SELECT NavLinkID
				FROM NavLink
				WHERE NavMenuName='%s'
				ORDER BY Position",
				mysql_real_escape_string($navMenuName));
			$navLinksResult = Database::getInstance()->query($navLinksQuery);

			$this->navGroups_ = array();
			$this->navLinks_ = array();
			while($navLinkObj = $navLinksResult->fetch_object()){
				$newNavLink = new NavLink($navLinkObj->NavLinkID);
				if($newNavLink->groupName() != NULL){
					if(array_key_exists($newNavLink->groupName(), $this->navGroups_) !== false)
						$this->navLinks_[$this->navGroups_[$newNavLink->groupName()] ]->navLinkIs($newNavLink);
					else{
						$navLinkGroup = new NavLinkGroup($newNavLink->groupName());
						$navLinkGroup->navLinkIs($newNavLink);
						$this->navLinks_[] = $navLinkGroup;
						$this->navGroups_[$newNavLink->groupName()] = count($this->navLinks_) - 1;
					}
				}
				else
					$this->navLinks_[] = $newNavLink;
			}
		}
		public function toHTML(){
			$output = "";
			$currGroup = NULL;
			$output .= "<ul id='navBar'>";
			foreach($this->navLinks_ as $navLink)
				$output .= $navLink->toHTML();
			$output .= "</ul>";

			return $output;
		}
	}
?>
