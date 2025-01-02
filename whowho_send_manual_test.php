<?php
    header('Content-Type: application/json; charset=UTF-8');
	
	// set_error_handler("customError");

	include_once "./inc_func.php";

	$logFile = log_exist("manual_test");
	log_write($logFile, date("Y-m-d H:i:s")." [Request Start] \n");

	// token 발급 5회까지 요청
	for ($i=1; $i <= 5; $i++) { 

		// log_write($logFile, date("Y-m-d H:i:s")." [request token]: $i 차 요청 \n");
		//토큰 발급 url
		$url_token = $domain."/apis/v1/auth/authenticate";

		$data_arr = array(
			'username'=>$username,
			'password'=>$password
		);
		
		$headers = rest_header();
		$data_json  = json_encode($data_arr);
		log_write($logFile, date("Y-m-d H:i:s")." [API send]: $data_json \n");
		
		//토큰
		$token = rest_post($url_token, $headers, $data_json);
		log_write($logFile, date("Y-m-d H:i:s")." [API return]: $token \n");
		
		$token_decode = json_decode($token);
		$token_val = $token_decode->authToken;

		if ($token_val != "") break;
		if ($token_val == "" && $i == 5) {
			// log_write($logFile, date("Y-m-d H:i:s")." [error]: token 발급 실패");
			$subject = date("Y-m-d H:i:s")." whowho token request error";
			$reqData = "API sended: ".date("Y-m-d H:i:s")."\nheader: ".json_encode($headers)."\ndata : $data_json \n";
			customError($subject, $reqData, "ksh2@smartel.co.kr");
			exit;
		}
	}

function Save_Rslt($conn, $sql, $service_acct, $service_num, $status, $rateNm, $telecom){

	$sql = "insert into cs_whowho_send_list values ('".$service_acct."', '".$service_num."', '".$status."', '".$rateNm."', '".$telecom."', SYSDATE)";
	// echo $sql;
	$result2 = oci_parse($conn, $sql);
	oci_execute($result2);
	oci_free_statement($result2);	
}

function customError($subject, $reqData, $to) {	
	
	$headers = "From: smt.mvno.inc@gmail.com";
  
	// PHP의 내장 mail() 함수를 사용
	$success = mail($to, $subject, $reqData, $headers);

	// if ($success) {
	// 	echo "Email successfully sent";
	// } else {
	// 	echo "Email sending failed";
	// }
}
  
  

?>
