<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
    require_once("lib/Security/Audit.php");
	require_once("lib/Module/ModuleField.php");
	require_once("lib/fieldtypes/StaticField.php");
	
	/// A FileUploadField allows a user to upload a file to the server
	/// Options: MaxFileCount(int), ShowPreview(bit), MaxSize(int, in bytes)
    class FileUploadField extends ModuleField{
		static public function type(){ return "FileUpload"; }
		public function toHTML($width, $height, $mutable, $currentValue, $moduleInstanceID){
			$output = "\n";
			if($mutable){
				$currentFiles = explode("##", $currentValue);

				// If the user has requested a preview be shown...
				if($this->option("ShowPreview") && $this->option("MaxFileCount")->optionValue() == "1"){
					$parameters = "download.php?fileName={$currentFiles[0]}&moduleInstanceID={$moduleInstanceID}" .
								"&moduleID={$this->moduleID()}&fileIndex=0&moduleFieldID={$this->moduleFieldID()}";
					$output .= "<img src='$parameters&command=download' width='$width' height='$height' /><br/>";

					// Since we have used-up all of our space with the preview, make the TextArea much smaller
					$height = "22";
				}
				$output .= "<textarea class='ValidateField' name='" . $this->name() .
					"' id='field_" . $this->moduleFieldID() .
					"' style='width:" . $width .
					"px; height:" . $height .
					"px;' readonly>" . $currentValue . "</textarea>";

				$output .= <<<EOD
<script type="text/javascript">
	fileUploadWindow('{$this->moduleFieldID()}', '{$this->label()}', '$currentValue');
</script>
EOD;
			}
			else{
				$currentFiles = explode("##", $currentValue);
				// We have to use javascript next, as we don't know the MIID in this PHP function
				//	(but it is saved in the JS).
				foreach($currentFiles as $key => $currentFile){
					$parameters = "download.php?fileName=$currentFile&moduleInstanceID={$moduleInstanceID}" .
								"&moduleID={$this->moduleID()}&fileIndex=$key&moduleFieldID={$this->moduleFieldID()}";
					$linkText = $currentFile;
					if($this->option("ShowPreview") && 
							count($currentFiles) == 1 &&	// It would be a pain to size multiple images
							preg_match('/^.*(.PNG|.png|.JPG|.jpg|.GIF|.gif)$/s', $currentFile)){
						$linkText = "<img src='$parameters&command=download' width='$width' height='$height' />";
					}
					$linkHTML = "<a href='{$parameters}&command=download'>$linkText</a>";
					$output .= StaticField::html($width, $height, $linkHTML);
				}
			}

			return $output;
		}
		public function listingHTML($width, $currentValue, $moduleInstanceID){
			$currentOptions = explode("##", $currentValue);
			$output = "<div style='width:{$width}px;height:22px;'>";
			if($currentValue != ""){
				foreach($currentOptions as $fileName)
					$output .= $fileName . ", ";
				$output = substr($output, 0, -2);
			}
			
			$output .= "</div>\n";
			return $output;
		}
		public function showOptions() {
			$output = "";

			$maxFileCountOption = $this->option("MaxFileCount");
			$maxFileCount = isset($maxFileCountOption) ? $maxFileCountOption->optionValue() : '';
			$output .= <<<EOD
<div class='FieldLabel' style='padding-right:5px' ><label for='field_{$this->moduleFieldID()}_option1'>Max File Count: </label></div>
<div class='FieldContent' style='padding-right:5px' ><input name='MaxFileCount' id='field_{$this->moduleFieldID()}_option1' type='text' value='$maxFileCount' /></div>
<script type="text/javascript">
	$("#field_{$this->moduleFieldID()}_option1").keyup(function(){
		this.value = this.value.replace(/[^0-9]*/g,'');
	});
</script>
EOD;

			$showPreviewOption = $this->option("ShowPreview");
			$showPreview = isset($showPreviewOption) && $showPreviewOption->optionValue() == "1" ? "checked='checked'" : '';
			$output .= <<<EOD
<div class='FieldLabel' style='padding-right:5px' ><label for='field_{$this->moduleFieldID()}_option2'>Show Preview: </label></div>
<div class='FieldContent' style='padding-right:5px' ><input name='ShowPreview' id='field_{$this->moduleFieldID()}_option2' type='checkbox' $showPreview /></div>
EOD;

			$maxSizeOption = $this->option("MaxSize");
			$maxSize = isset($maxSizeOption) ? $maxSizeOption->optionValue() : '';
			$output .= <<<EOD
<div class='FieldLabel' style='padding-right:5px' ><label for='field_{$this->moduleFieldID()}_option3'>Max Size: </label></div>
<div class='FieldContent' style='padding-right:5px' ><input name='MaxSize' id='field_{$this->moduleFieldID()}_option3' type='text' value='$maxSize' /></div>
<script type="text/javascript">
	$("#field_{$this->moduleFieldID()}_option3").keyup(function(){
		this.value = this.value.replace(/[^0-9]*/g,'');
	});
</script>
EOD;

			return $output;
		}
    }
?>
