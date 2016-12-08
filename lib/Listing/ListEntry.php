<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Database.php");
	require_once("lib/Security/Security.php");
	
	/// A single entry in a Listing, containing to a single module instance.
	///  Depending on the list fields, may have one or more fields displayed
	class ListEntry{
		private $moduleInstance_, $listFields_, $listOptions_;
		public function  __construct($moduleInstance, $listFields, $listOptions) {
			$this->moduleInstance_ = $moduleInstance;
			$this->listFields_ = $listFields;
			$this->listOptions_ = $listOptions;
		}
		public function toHTML($totalWidth, $orientation){
			$output = "";
			$moduleInstanceID = $this->moduleInstance_->moduleInstanceID();
			$moduleID = $this->moduleInstance_->moduleID();

			foreach($this->listFields_ as $listField){
				$width = $totalWidth;
				if(!$orientation)
					$output .= "<div align='left'>";
				else{	// If we are displaying all of the fields on one line...
					$width = $totalWidth > 0 ? ($listField->width() / $totalWidth)*100 : abs($totalWidth);
					$output .= "<div style='float:left;width:$width%;'>&nbsp;";
				}

				$moduleFieldID = $listField->moduleFieldID();
				if(isset($moduleFieldID)){
					// Note: We have to convert from a percentage wdith to a pixel width, hence...
					$currentValue = $this->moduleInstance_->moduleFieldInstance($moduleFieldID)->listingHTML($width * 9);
					$linkPageName = $listField->linkPageName();
					if(isset($linkPageName))
						$output .= "<a href='index.php?MIID=$moduleInstanceID&Page=$linkPageName'>$currentValue</a>";
					else
						$output .= $currentValue;
				}
				else{	// If listfield is the special drop-down menu...
					$output .= "\n\t<select id='mi_options_$moduleInstanceID'>\n";
					foreach($this->listOptions_ as $listOption){
						$title = $listOption->title();
						if($title == "Delete"){
							if(Security::privilege(new ModulePrivilege("DeleteInstance", $moduleID)))
								$output .= "\t\t<option id='delete' onclick='deleteConfirm($moduleInstanceID, $moduleID)'>Delete</option>\n";
						}
						else
							$output .= "\t\t<option id='view' onclick='goToPage(\"{$listOption->pageName()}\", $moduleInstanceID, $moduleID);'>$title</option>\n";
					}
					$output .= "\t</select>\n";
				}
				$output .= "</div>\n";
			}
			// Since we just finished with the single line, time to move to the next one
			if($orientation)
				$output .= "<div style='clear:both;'></div>";

			return $output;
		}
		public function moduleFieldValue($moduleFieldID){ 
			return $this->moduleInstance_->moduleFieldInstance($moduleFieldID)->currentValue();
		}
	}
?>
