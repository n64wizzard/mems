<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/Security/Audit.php");
	require_once("lib/Exception.php");

	class DownloadException extends CustomException {}

	/// @return A web page that will allow the user to download the requesed file
	function downloadFile($moduleID, $moduleFieldID, $moduleInstanceID, $fileIndex, $fileName){
		$fullPath = "uploads/" . $moduleID .
					"/" . $moduleFieldID .
					"/" . $moduleInstanceID .
					"_" . $fileIndex;
		// Must be fresh start
		if(headers_sent())
			throw new DownloadException("Headers already sent");

		// Required for some browsers
		if(ini_get('zlib.output_compression'))
			ini_set('zlib.output_compression', 'Off');

		echo getcwd() . $fullPath;
		if(is_file($fullPath)){
			// Parse Info / Get Extension
			$fsize = filesize($fullPath);
			$path_parts = pathinfo($fileName);
			$ext = strtolower($path_parts["extension"]);

			// Determine Content Type
			switch($ext){
				case "pdf": $ctype="application/pdf"; break;
				case "exe": $ctype="application/octet-stream"; break;
				case "zip": $ctype="application/zip"; break;
				case "doc": $ctype="application/msword"; break;
				case "xls": $ctype="application/vnd.ms-excel"; break;
				case "ppt": $ctype="application/vnd.ms-powerpoint"; break;
				case "gif": $ctype="image/gif"; break;
				case "png": $ctype="image/png"; break;
				case "jpeg":
				case "jpg": $ctype="image/jpg"; break;
				default: $ctype="application/force-download";
			}
			header("Pragma: public"); // required
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: private",false); // required for certain browsers
			header("Content-Type: $ctype");
			header("Content-Disposition: attachment; filename=\"" . $fileName . "\";" );
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: " . $fsize);
			ob_clean();
			flush();
			readfile($fullPath);
		}
		else
			throw new DownloadException("File '" . $fullPath . "' not found");
	}

	$command = isset($_GET["command"]) && $_GET["command"] != "" ? $_GET["command"] : NULL;
	$moduleID = isset($_GET["moduleID"]) && $_GET["moduleID"] != "" ? $_GET["moduleID"] : NULL;
	$moduleFieldID = isset($_GET["moduleFieldID"]) && $_GET["moduleFieldID"] != "" ? $_GET["moduleFieldID"] : NULL;
	$moduleInstanceID = isset($_GET["moduleInstanceID"]) && $_GET["moduleInstanceID"] != "" ? $_GET["moduleInstanceID"] : NULL;
	$fileIndex = isset($_GET["fileIndex"]) && $_GET["fileIndex"] != "" ? $_GET["fileIndex"] : NULL;
	$fileName = isset($_GET["fileName"]) && $_GET["fileName"] != "" ? $_GET["fileName"] : NULL;
	if(isset($_GET["command"])){
		$command = $_GET["command"];
		if($command == "image")
			readImage();
		elseif($command == "download")
			downloadFile($moduleID, $moduleFieldID, $moduleInstanceID, $fileIndex, $fileName);
	}
?>
