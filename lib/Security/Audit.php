<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}
	require_once("lib/page/Page.php");
	$GLOBALS['DEBUG'] = true;

	final class Audit{
		/// Catches all uncaught exceptions, saves their message, and then prints
		///  a generic error message to the window.
		static public function exception_handler($exception) {
			// PENDING: Remove the following line
			echo "Uncaught exception: " , $exception, "\n";
			
			self::logError($exception);
			$pageContents = "<p class='ErrorMessagePage'>An unexpected error has occured.  We are sorry for the inconvenience.  Please contact your site administrator if this problem continues.</p>";
			$page = new Page("Unexpected Error", "Error", $pageContents, 0);
			print($page->toHTML());
		}

		/// Saves the contents of an exception to the database, or alternatively,
		///  to the file system.
		static public function logError($exception){
			try{
				$logErrorQuery = sprintf(
						"INSERT INTO ErrorLog
						(`ErrorLogID`, `CallStack`, `Message`, `Type`) VALUES
						(NULL, '%s', '%s', '%s')",
						mysql_real_escape_string($exception->getTraceAsString()),
						mysql_real_escape_string($exception->getMessage()),
						mysql_real_escape_string(get_class($exception)));
				Database::getInstance()->query($logErrorQuery, 2, 1);
			}
			catch(MySQLException $e){
				// TODO: Write error to a file
			}
		}
	}

	set_exception_handler('Audit::exception_handler');
?>
