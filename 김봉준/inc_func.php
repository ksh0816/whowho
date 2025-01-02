<?php
	error_reporting(E_ALL & ~E_NOTICE);
	header('Content-Type: text/html; charset=UTF-8');
	
//**************** 상수 ****************	
	$ip = $_SERVER['REMOTE_ADDR'];
	
	$username = "secom";
	$password = "yVouspXGMgb2zZXjO+Cz1o2n6bxr3HydGxEB9Cmlk7g=";
	
	$key = "fakecodingsecretfakecodingsecret";		//AES256-CBC 암호화 key
	
	$domain = "https://ohwm.whox2.co.kr:18259";		//개발
	//$domain = "https://openapi.whox2.co.kr:18259";		//상용
	

//**************** 로그기록 ****************
	function log_exist($add_name){
		$toYear = "./log/".date("Y");
		if(!is_dir($toYear)){
			mkdir($toYear, 0777);
		}
			
		$toMon = $toYear."/".date("m");
		if(!is_dir($toMon)){
			mkdir($toMon, 0777);
		}
				
		$toDay = date("Ymd");
		$logFile = $toMon."/".$toDay."_".$add_name.".txt";

		if (!file_exists($logFile)){
			$handle = fopen($logFile, 'w');
			fclose($handle);
		}
		
		return $logFile;
	}

	//웹실행 용
	function log_exist2($add_name){
		$toYear = "./log/".date("Y")."_w";
		if(!is_dir($toYear)){
			mkdir($toYear, 0777);
		}
			
		$toMon = $toYear."/".date("m");
		if(!is_dir($toMon)){
			mkdir($toMon, 0777);
		}
				
		$toDay = date("Ymd");
		$logFile = $toMon."/".$toDay."_".$add_name.".txt";

		if (!file_exists($logFile)){
			$handle = fopen($logFile, 'w');
			fclose($handle);
		}
		
		return $logFile;
	}
	

	function log_write($logFile, $logmsg){
		$handle = fopen($logFile, 'a');
		fwrite($handle, $logmsg);
		fclose($handle);	
	}
//***************************************


//**************** Curl ****************
//헤더
function rest_header($Authorization) { 
	$headers = array(
		'Content-Type: application/json; charset=utf-8',
		'Authorization: '.$Authorization
	);
	
	return $headers;
}

// GET 방식 함수
function rest_get($url, $headers, $params=array()) { 

    $url = $url.'?'.http_build_query($params, '', '&');

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);

    curl_close($ch);

    return $response;
}

// POST 방식 함수
function rest_post($url, $headers, $data_json) { 

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);

    curl_close($ch);

    return $response;
}

// PATCH 방식 함수
function rest_patch($url, $headers, $data_json) { 

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);

    curl_close($ch);

    return $response;
}
//***************************************


	//파일명으로 mime 타입확인
	function mimeTypeCheck($filename){
		
		$filename = strtolower($filename);
		$ext = substr($filename, strrpos($filename, '.') + 1); 
		
		switch ($ext) {
			case "bmp":
				return "image/bmp";			
			case "gif":
				return "image/gif";
			case "ico":
				return "image/x-icon";
			case "jpg":
			case "jpeg":
				return "image/jpeg";
			case "pif":
				return "application/pdf";
			case "png":
				return "image/png";
			case "tif":
			case "tiff":
				return "image/tiff";				
		}
	}



//**************** 헥사 <-> 스트링 ****************
	//스트링을 헥사코드로 인코딩하기
	function String2Hex($string){
		$hex='';
		
		for ($i=0; $i < strlen($string); $i++){
			$hex .= dechex(ord($string[$i]));
		}
		return $hex;
	}


	//헥사코드를 스트링으로 디코딩 하기
	function Hex2String($hex){
		$string='';
		
		for ($i=0; $i < strlen($hex)-1; $i+=2){
			$string .= chr(hexdec($hex[$i].$hex[$i+1]));
		}
		return $string;
	}
//***************************************


//**************** 암호화 **************** 사용안함
	//iv 값이 키값의 앞부분인 경우
	function AesEncrypt ($key, $str) {
		
		define('KEY', $key);
		define('KEY_128', substr(KEY, 0, 128/8));
		define('KEY_256', substr(KEY, 0, 256/8));
		
		return openssl_encrypt($str, "AES-256-CBC", KEY_256, 0, KEY_128);
	}

	function AesDecrypt ($key, $str) {
		
		define('KEY', $key);
		define('KEY_128', substr(KEY, 0, 128/8));
		define('KEY_256', substr(KEY, 0, 256/8));
		
		return openssl_decrypt($str, "AES-256-CBC", KEY_256, 0, KEY_128);
	} 

	//iv 값이 없는 경우
	function AesEncrypt2 ($secret_key, $plain_text) {
		
		$ivBytes = chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0);
		return base64_encode(openssl_encrypt($plain_text, "AES-256-CBC", $secret_key, true, $ivBytes));
	}

	function AesDecrypt2 ($secret_key, $encrypt_text) {
		
		$ivBytes = chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0);
		return openssl_decrypt(base64_decode($encrypt_text), "AES-256-CBC", $secret_key, true, $ivBytes);
	} 
//***************************************


//**************** 한글용 json 인코드 ****************
	function json_encode2($data) {

		switch (gettype($data)) {
			case 'boolean':
				return $data?'true':'false';
			case 'integer':
			case 'double':
				return $data;
			case 'string':
				return '"'.strtr($data, array('\\'=>'\\\\','"'=>'\\"')).'"';
			case 'array':
				$rel = false; // relative array?
				$key = array_keys($data);

				foreach ($key as $v) {
					if (!is_int($v)) {
						$rel = true;
						break;
					}
				}

				$arr = array();

				foreach ($data as $k=>$v) {
					$arr[] = ($rel?'"'.strtr($k, array('\\'=>'\\\\','"'=>'\\"')).'":':'').json_encode2($v);
				}

				return $rel?'{'.join(',', $arr).'}':'['.join(',', $arr).']';

			default:
				return '""';
		}
	}
//***************************************
?>
