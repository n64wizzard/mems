<?php
	/// This class does several simple checks, whose purpose is to reduce attacks,
	///  spam, etc..
	final class ProxyCheck{
		static private $proxyHeaders_ = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED',
				'HTTP_FORWARDED', 'HTTP_PROXY_AGENT', 'HTTP_VIA', 'HTTP_PROXY_CONNECTION',
				'HTTP_CLIENT_IP');
		static private $ports_ = array(3128,8080,80);

		/// Check typical proxy headers
		static function checkHeaders($serverVar) {
			$output = array();

			foreach(self::$proxyHeaders_ as $proxyHeader)
				if(isset($serverVar[$proxyHeader]))
					$output[$proxyHeader] = $serverVar[$proxyHeader];

			return $output;
		}
		
		/// Check common web-hosting ports
		static function checkPort($serverVar, $ip) {
			$output = array();
			$timeout = 5;

			if(empty($ip))
				$ip = $serverVar['REMOTE_ADDR'];

			foreach(self::$ports_ as $port){
					$fp = @fsockopen($ip, $port, $errno, $errstr, $timeout);
					if(!empty($fp)) 
						$output[] = $port;
					@fclose($fp);
			}

			return $output;
		}
	}
?>