<?php
	/// This file contains AJAX functions related to the UploadFile field
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Module/ModuleInstance.php");
	require_once("lib/Module/ModuleFieldInstance.php");
	require_once("lib/Security/Audit.php");
	require_once("lib/Exception.php");

	define("UPLOADFOLDER", "uploads/");

	class UploadException extends CustomException {}

	/// This function returns the contents of the Upload Modal window
	///  Ideally this code would be included in the FileUploadField, but unfortunately it is very difficult to
	///	 properly format all of the following code to fit into a Javascript function argument,
	///  so that is why it is done here instead.
	function modalWindowHTML($moduleFieldID, $moduleInstanceID, $currentValue){
		$output = "";
		if(!isset($moduleInstanceID) || $moduleInstanceID == "")
			$output .= "Cannot upload files until the instance has been created.";
		else{
			$output .= <<<EOD
<form id="file_upload" action="return false;" method="POST" enctype="multipart/form-data">
	<input type="hidden" name="command" value="upload" />
    <input type="file" name="file" multiple />
    <button>Upload</button>
    <div>Upload files</div>
</form>
<div id="uploadTable"></div>
<div id="downloadTable"></div>
<script type="text/javascript">
	applyUploadUI('file_upload', '$moduleFieldID', '$moduleInstanceID');
	initDownloadRowEntries('$currentValue', '$moduleFieldID');
</script>
EOD;
		}
		return $output;
	}
	
	/// @return Information about a file just uploaded, and a potential error message
	function uploadResults($file, $errorMsg){
		$output = "";
		$output .= '{"error":"' . $errorMsg . '","name":"' . $file['name'] . '","type":"' . $file['type'] . '","size":"' . $file['size'] . '"}';
		return $output;
	}

	/// Uploads a new file to the server, and saves its information to the DB.
	///  File is accessed through $_FILE.
	/// @return String generated by uploadResults(), consisting of file information
	///  and possibly an error string
	function uploadNewFile($moduleFieldID, $moduleInstanceID){
		$output = "";
		
		try{ $moduleInstance = ModuleInstance::createModuleInstance($moduleInstanceID); }
		catch(MySQLException $e){
			Audit::logError($e);
			return uploadResults($file, "Unable to find Module Instance");
		}

		try{ $moduleField = $moduleInstance->moduleFieldInstance($moduleFieldID); }
		catch(InvalidArgumentException $e){
			Audit::logError($e);
			return uploadResults($file, "Unknown Module Field");
		}

		$currentFiles = explode("##", $moduleField->currentValue());
		if($currentFiles === false || $moduleField->currentValue() == "")
			$currentFiles = array();

		$file = $_FILES['file'];

		// Validate filename/extensions via regex
		$moduleField->validate($file["name"]);

		$maxSize = $moduleField->option("MaxSize")->optionValue();
		if($maxSize < $file['size'])
			return uploadResults($file, "File must be smaller than " . $maxSize . " bytes");

		// Create the new file name and directory location
		$newFileName = $moduleInstance->moduleInstanceID();
		$directory = constant("UPLOADFOLDER") .
					$moduleInstance->moduleID() . "/" .
					$moduleFieldID . "/";

		// PENDING: Check read permissions on final server
		if(!is_dir($directory) && !mkdir($directory, 0600, true))
			return uploadResults($file, "Unable to create file directory");

		// In case we allow multiple file uploads per field, append the index to the file name
		$max = 0;
		foreach(glob($directory . $newFileName . "_*") as $filename) {
			if(preg_match("/_([0-9]*)/s", $filename, $matches))
				$max = $matches[0] >= $max ?: intval($matches[0]) + 1;
		}
		$newFileName .= "_" . $max;

		if(count($currentFiles) >= $moduleField->option("MaxFileCount")->optionValue())
			return uploadResults($file, "You have uploaded too many files.  Delete one and try again");

		// Attempt to move the uploaded file to it's new place
		if(!(move_uploaded_file($file['tmp_name'], $directory . $newFileName)))
			return uploadResults($file, "An error occurred while moving the file");

		// Save the filenames to the database
		$currentFiles[] = $file["name"];
		$moduleField->currentValueIs(implode('##', $currentFiles));
		
		try{ $moduleField->saveToDB(); }
		catch(MySQLException $e){
			Audit::logError($e);
			return uploadResults($file, "An error occurred while saving the file to the DB");
		}
		
		return uploadResults($file, "");
	}

	/// Deletes a file existing on the server
	/// @return The string "Success" or an error message
	function deleteExistingFile($moduleInstanceID, $moduleFieldID, $fileName){
		try{ $moduleInstance = ModuleInstance::createModuleInstance($moduleInstanceID); }
		catch(MySQLException $e){
			Audit::logError($e);
			return "Unable to find Module Instance";
		}
		
		try{ $moduleField = $moduleInstance->moduleFieldInstance($moduleFieldID); }
		catch(InvalidArgumentException $e){
			Audit::logError($e);
			return "Unknown Module Field";
		}

		$directory = constant("UPLOADFOLDER") .
					$moduleInstance->moduleID() . "/" .
					$moduleFieldID . "/";
		$fileFormat = $directory . $moduleInstanceID . "_";
		
		$currentFiles = explode("##", $moduleField->currentValue());
		$fileFound = false;
		$count = 0;
		foreach($currentFiles as $key => $currentFile){
			if($currentFile == $fileName){
				if(!unlink($fileFormat . $count)){
					try{ throw new UploadException("Trying to delete file: " . $fileFormat . $count . ".  Unlink failed"); }
					catch(UploadException $e){ Audit::logError($e); return "Unable to find specified file"; }
				}
				unset($currentFiles[$key]);
				$fileFound = true;
			}
			elseif($fileFound){
				if(!rename($fileFormat . $count, $fileFormat . ($count - 1))){
					try{ throw new UploadException("Trying to rename file: " . $fileFormat . $count . ".  Rename failed"); }
					catch(UploadException $e){ Audit::logError($e); return "An error has occurred"; }
				}
			}
			$count++;
		}

		$moduleField->currentValueIs(implode('##', $currentFiles));
		try{ $moduleField->saveToDB(); }
		catch(MySQLException $e){
			Audit::logError($e);
			return "Error deleting file";
		}
		
		return "Success";
	}
	
	if(isset($_POST['command'])){
		$command = $_POST['command'];

		$moduleInstanceID =  Utils::getPostInt("moduleInstanceID");
		$moduleFieldID =  Utils::getPostInt("moduleFieldID");
		if($command == 'upload')
			print(uploadNewFile($moduleFieldID, $moduleInstanceID));
		elseif($command == 'modalHTML'){
			$currentValue = isset($_POST["currentValue"]) && $_POST["currentValue"] != "" ? $_POST["currentValue"] : NULL;
			print(modalWindowHTML($moduleFieldID, $moduleInstanceID, $currentValue));
		}
		elseif($command == 'delete'){
			$fileName=  isset($_POST["fileName"]) && $_POST["fileName"] != "" ? $_POST["fileName"] : NULL;
			print(deleteExistingFile($moduleInstanceID, $moduleFieldID, $fileName));
		}
	}
?>