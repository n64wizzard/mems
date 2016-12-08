<?php
	while(!file_exists(getcwd()."/index.php")){chdir('..');}

// TODO: Create a global delineator string, instead of using "##" all over the place
	
	final class Utils{
		/// @param $varName String of the POST variable name we want
		/// @param $negative Whether we want to accept negative values
		/// @param $length How many characters we will accept
		/// @return The value requested, or NULL if it does not exist
		static public function getPostInt($varName, $negative=false, $length=10){
			$varValue = isset($_POST[$varName]) && $_POST[$varName] != "" ? $_POST[$varName] : NULL;
			if(isset($varValue) && preg_match("/^" . ($negative ? "-?" : "") . "[0-9]{1,$length}$/", $varValue) != 1)
				throw new InvalidArgumentException("Invalid $varName: $varValue");

			return $varValue;
		}

		/// @returns a random date between the two inputs
		/// Usage: makeRandomDateInclusive('2009-04-01','2009-04-03');
		static public function makeRandomDateInclusive($startDate,$endDate){
			$days = round((strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24));
			$n = rand(0,$days);
			return date("m/d/Y",strtotime("$startDate + $n days"));
		}

		/// @return A randomly generated letter
		static public function makeRandomLetter(){
			$abc= array("a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z");
			return $abc[rand(0,25)];
		}

		/// Finds all of the subclasses of a given parent class
		/// @return An array of strings, where each string is a name of a class
		static public function getSubclassesOf($parent) {
			$result = array();
			foreach (get_declared_classes() as $class)
				if (is_subclass_of($class, $parent))
					$result[] = $class;

			return $result;
		}

		/// @return The value, if it is set, otherwise the string "NULL"
		static public function valueIfSet($value){
			if(isset($value))
				return $value;
			else
				return "NULL";
		}

		/// @return A string containing the URL of the current page
		static public function curPageURL() {
			$pageURL = 'http';
			if(!empty($_SERVER['HTTPS']))
				$pageURL .= "s";
			
			$pageURL .= "://";
			if ($_SERVER["SERVER_PORT"] != "80")
				$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
			else
				$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];

			return $pageURL;
		}

		/// Modified the current URL given a list of parameters
		static public function modify_url($mod){
			$url = Utils::curPageURL();
			$query = explode("&", $_SERVER['QUERY_STRING']);
			if (!$_SERVER['QUERY_STRING'])
				$queryStart = "?";
			else
				$queryStart = "&";
			// modify/delete data
			foreach($query as $q){
				list($key, $value) = explode("=", $q);
				if(array_key_exists($key, $mod)){
					if($mod[$key])
						$url = preg_replace('/'.$key.'='.$value.'/', $key.'='.$mod[$key], $url);
					else
						$url = preg_replace('/&?'.$key.'='.$value.'/', '', $url);
				}
			}
			// add new data
			foreach($mod as $key => $value)
				if($value && !preg_match('/'.$key.'=/', $url))
				$url .= $queryStart . $key . '=' . $value;

			return $url;
		}

		/// @return The GET value for a given key
		static public function get($key){
			if(isset($_GET[$key]))
				return $_GET[$key];
			else
				return NULL;
		}

		/// @param $pattern A REGEX pattern to match against every element in the array
		/// @return An array where the key is what has been matched by $pattern,
		///  and the value is the value of that entry in the array
		static public function arrayKeySearch($pattern, array $array){
			$output = array();
			foreach($array as $key => $value)
				if(preg_match("/^$pattern(.*)/", $key, $matches) > 0)
					$output[$matches[1]] = $value;

			return $output;
		}

		/// @param $string A string where entries are separated by $entryDelimiter,
		///  and each entry has a key-value pair, separated by $keyValueDelimiter
		/// @return An 2-dimension array
		static public function explodeWithKeys($entryDelimiter, $keyValueDelimiter, $string){
			$keyValues = explode($entryDelimiter, $string);
			$output = array();

			if($string != "")
				foreach($keyValues as $keyValue){
					$keyValueArray = explode($keyValueDelimiter, $keyValue);
					$output[$keyValueArray[0]] = $keyValueArray[1];
				}

			return $output;
		}

		/// Edits the given INI file and modifies the values in $newValues
		///  if they exist in the INI file
		/// @param $newValues: Name => Value
		static public function editINIFile($filePath, $newValues){
			$myfile = file($filePath);

			foreach($myfile as $lineno => $line){
				if(preg_match("/^([a-zA-Z]*)( *)?=( *)?\"?([a-zA-Z ]*)\"?$/s", $line, $matches))
					if(array_search($matches[1], array_keys($newValues)) !== false)
						$myfile[$lineno] = $matches[1] . " = " . $newValues[$matches[1]] . "\n";
			}
			$savefile = implode("", $myfile);

			$fp = fopen($filePath, 'w');
			fwrite($fp, $savefile);
			fclose($fp);
		}
		static private $initSettings = NULL;
		static public function iniSettings($fileName="config.ini.php"){
			if(!isset(self::$initSettings)){
				self::$initSettings = array();
				if((self::$initSettings = parse_ini_file($fileName)) === false){
					Audit::logError(new RuntimeException("INI file not found"));
					self::$initSettings = array();
				}
			}
			return self::$initSettings;
		}

		/// Not Used
		/// @return An encrypted version of the input
		static public function encrypt($value){
			$iniArray = Utils::iniSettings();

			if($iniArray["passCode"] != "")
				$value = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, mhash(MHASH_SHA1, $iniArray["passCode"]), $value, MCRYPT_MODE_ECB);
			return $value;
		}

		/// Not Used
		/// @return A decrypted version of the input
		static public function decrypt($value){
			$iniArray = Utils::iniSettings();

			if($iniArray["passCode"] != "")
				$value = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, mhash(MHASH_SHA1, $iniArray["passCode"]), $value, MCRYPT_MODE_ECB));
			return $value;
		}

		/// @return The hash of the input
		public static function hashPassword($password){
			return hash('whirlpool', "MEMS" . $password . "MEMS");
		}
	}
	
	require_once("lib/Database.php");
	class UserData{
		private static $userNames_; // userMIID => UserName

		/// @return The username associated with a user ID
		public static function userName($userMIID){
			if(isset(self::$userNames_[$userMIID]))
				return self::$userNames_[$userMIID];

			$iniArray = Utils::iniSettings();
			$userNameQuery = sprintf(
					"SELECT AES_DECRYPT(MFI.Value, '%s') AS UserName
					FROM ModuleInstance AS MI JOIN ModuleFieldInstance AS MFI ON MI.ModuleInstanceID=MFI.ModuleInstanceID
					JOIN ModuleField AS MF ON MFI.ModuleFieldID=MF.ModuleFieldID
					WHERE MF.Name='UserName' AND MFI.ModuleInstanceID='%s'",
					mysql_real_escape_string($iniArray["passCode"]),
					mysql_real_escape_string($userMIID));
			try{ $userNameObj = Database::getInstance()->query($userNameQuery, 2, 1)->fetch_object(); }
			catch(MySQLException $e){
				Audit::logError($e);
				return NULL;
			}

			self::$userNames_[$userMIID] = $userNameObj->UserName;
			return $userNameObj->UserName;
		}
	}
?>
