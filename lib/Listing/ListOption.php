<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Database.php");

	/// A listOption is simply an entry on the drop-down list that can be shown
	///  along with each entry in a Listing.
	class ListOption{
		private $title_, /// The label of the select option
				$pageName_;	/// The page to relocate to

		public function __construct($title, $pageName){
			$this->title_ = $title;
			$this->pageName_ = $pageName;
		}
		public function pageName(){ return $this->pageName_; }
		public function title(){ return $this->title_; }
	}
?>
