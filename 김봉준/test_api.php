<?php
	include_once "./inc_func.php";
	

	$logFile = log_exist("test");
	log_write($logFile, date("Y-m-d H:i:s")."  Request Start \n\n");


	//토큰 발급
	$url = $domain."/apis/v1/auth/authenticate";

	$data_arr = array(
		'username'=>$username,
		'password'=>$password
	);
	
	$headers = array();
	$data_json  = json_encode2($data_arr);

	$output = rest_post($url, $headers, $data_json);		//토큰
	log_write($logFile, date("Y-m-d H:i:s")." API return: $output \n");

	
	echo $output;
	
	exit;
	

?>
