<?php
	include_once "./inc_func_test.php";

	$logFile = log_exist("test");
	log_write($logFile, date("Y-m-d H:i:s")." [Request Start] \n");


	//토큰 발급 url
	$url_token = $domain."/apis/v1/auth/authenticate";

	$data_arr = array(
		'username'=>$username,
		'password'=>$password
	);
	
	$headers = rest_header();
	$data_json  = json_encode2($data_arr);
	
	//토큰
	$token = rest_post($url_token, $headers, $data_json);
	log_write($logFile, date("Y-m-d H:i:s")." [API return]: $token \n");
	
	$token_decode = json_decode($token);
	$token_val = $token_decode->authToken;
	
	// echo $token_val;
	// exit;
	
	//스마텔 사용자 연동
	$url = $domain."/apis/smartel/user/whowho";	
	$headers = rest_header2($token_val);
		
	// $today = "20211025";
	$timestamp = strtotime("Now");
	$timestamp = strtotime("-1 days");
	$today = date(Ymd, $timestamp);
	$rateNm	= "후후";

	//가입,해지,정지,정지해제 데이터 추출(JOIN, CANCEL, STOP, STOP_CLEAR)
	//SKT =======================================================================================================
	
	$telecom = "SKT";
	log_write($logFile, date("Y-m-d H:i:s")." [carrier]: $telecom \n");
	
	//JOIN
	$sql = "SELECT service_acct, service_num, NAME, rate, TO_CHAR(open_date, 'yyyymmdd') change_date, 'JOIN' status FROM CUSTOMER
			WHERE TO_CHAR(open_date, 'yyyymmdd') = '$today' AND rate LIKE '%$rateNm%' 
			order by service_num";
	
	$result = oci_parse($conn, iconv("UTF-8", "EUC-KR", $sql));
	oci_execute($result);
	
	$cnt = oci_fetch_all($result, $row);	// 행 갯수 확인
	log_write($logFile, date("Y-m-d H:i:s")." [cnt]: $cnt \n\n");
	
	while($row = oci_fetch_array($result, OCI_ASSOC)){
		
		$data_arr2 = array(
			'userPhone'=>AesEncrypt2($key, $row['SERVICE_NUM']),
			'status'=>$row['STATUS'],
			'changeDate'=>$row['CHANGE_DATE'],
			'rateNm'=>iconv("EUC-KR", "UTF-8", $row['RATE'])
		);

		$data_json2 = json_encode2($data_arr2);
		log_write($logFile, date("Y-m-d H:i:s")." [API send]: $data_json2 \n");
		// print_r($data_json2);
		// exit;
		
		$output = rest_post($url, $headers, $data_json2);
		log_write($logFile, date("Y-m-d H:i:s")." [API return]: $output \n\n");
		// print_r($output);
		// echo "<br>";
		
		// 전송내역 저장		
		Save_Rslt($conn, $sql, $row['SERVICE_ACCT'], $row['SERVICE_NUM'], $row['STATUS'], $row['RATE'], $telecom);
	}

	// CANCEL
	$sql = "SELECT service_acct, service_num, NAME, rate, open_date,  replace(close_date, '-') change_date, 'CANCEL' status FROM CUSTOMER
			WHERE replace(close_date, '-') = '$today' AND rate LIKE '%$rateNm%' 
			order by service_num";

	$result = oci_parse($conn, iconv("UTF-8", "EUC-KR", $sql));
	oci_execute($result);
	
	while($row = oci_fetch_array($result, OCI_ASSOC)){
		
		$data_arr2 = array(
			'userPhone'=>AesEncrypt2($key, $row['SERVICE_NUM']),
			'status'=>$row['STATUS'],
			'changeDate'=>$row['CHANGE_DATE'],
			'rateNm'=>iconv("EUC-KR", "UTF-8", $row['RATE'])
		);

		$data_json2 = json_encode2($data_arr2);
		log_write($logFile, date("Y-m-d H:i:s")." [API send]: $data_json2 \n");
		// print_r($data_json2);
		// exit;
		
		$output = rest_post($url, $headers, $data_json2);
		log_write($logFile, date("Y-m-d H:i:s")." [API return]: $output \n\n");
		// print_r($output);
		
		// 전송내역 저장		
		Save_Rslt($conn, $sql, $row['SERVICE_ACCT'], $row['SERVICE_NUM'], $row['STATUS'], $row['RATE'], $telecom);
	}
	
	// JOIN, CANCEL(요금제 변경)
	$sql = "SELECT service_acct, svc_num service_num, old_rate, new_rate, TO_CHAR(change_date, 'yyyymmdd') change_date FROM CUSTOMER_CHANGE
			WHERE change_type = '요금제변경' AND (old_rate like '%$rateNm%' OR new_rate LIKE '%$rateNm%')
			AND TO_CHAR(change_date, 'yyyymmdd') = '$today'
			ORDER BY svc_num";

	$result = oci_parse($conn, iconv("UTF-8", "EUC-KR", $sql));
	oci_execute($result);
	
	while($row = oci_fetch_array($result, OCI_ASSOC)){
		
		$old_rate = iconv("EUC-KR", "UTF-8", $row['OLD_RATE']);
		$new_rate = iconv("EUC-KR", "UTF-8", $row['NEW_RATE']);
		
		$status = "";
		
		// JOIN인 경우
		if(strpos($old_rate, "$rateNm") === false && strpos($new_rate, "$rateNm") !== false){
			$status = "JOIN";
		}
		
		// CANCEL 경우
		if(strpos($old_rate, "$rateNm") !== false && strpos($new_rate, "$rateNm") === false){
			$status = "CANCEL";
		}
		
		if($status) {
			$data_arr2 = array(
				'userPhone'=>AesEncrypt2($key, $row['SERVICE_NUM']),
				'status'=>$status,
				'changeDate'=>$row['CHANGE_DATE'],
				'rateNm'=>$new_rate
			);

			$data_json2 = json_encode2($data_arr2);
			log_write($logFile, date("Y-m-d H:i:s")." [API send]: $data_json2 \n");
			// print_r($data_json2);
			// exit;
			
			$output = rest_post($url, $headers, $data_json2);
			log_write($logFile, date("Y-m-d H:i:s")." [API return]: $output \n\n");
			// print_r($output);
		}
		
		// 전송내역 저장		
		Save_Rslt($conn, $sql, $row['SERVICE_ACCT'], $row['SERVICE_NUM'], $status, $row['NEW_RATE'], $telecom);
	}
	
	// STOP
	$sql = "SELECT a.service_acct, b.service_num, b.rate, TO_CHAR(a.change_date, 'yyyymmdd') change_date, 'STOP' status FROM CUSTOMER_CHANGE a
			inner JOIN CUSTOMER b ON a.service_acct = b.service_acct
			WHERE CHANGE_TYPE = '이용정지' AND SUSP_CL_CD IN ('F1', 'F3', 'F4', 'F5') AND  TO_CHAR(change_date, 'yyyymmdd') = '$today'
			AND b.rate LIKE '%$rateNm%'
			ORDER BY b.service_num";

	$result = oci_parse($conn, iconv("UTF-8", "EUC-KR", $sql));
	oci_execute($result);
	
	while($row = oci_fetch_array($result, OCI_ASSOC)){
		
		$data_arr2 = array(
			'userPhone'=>AesEncrypt2($key, $row['SERVICE_NUM']),
			'status'=>$row['STATUS'],
			'changeDate'=>$row['CHANGE_DATE'],
			'rateNm'=>iconv("EUC-KR", "UTF-8", $row['RATE'])
		);

		$data_json2 = json_encode2($data_arr2);
		log_write($logFile, date("Y-m-d H:i:s")." [API send]: $data_json2 \n");
		// print_r($data_json2);
		// exit;
		
		$output = rest_post($url, $headers, $data_json2);
		log_write($logFile, date("Y-m-d H:i:s")." [API return]: $output \n\n");
		// print_r($output);
		
		// 전송내역 저장		
		Save_Rslt($conn, $sql, $row['SERVICE_ACCT'], $row['SERVICE_NUM'], $row['STATUS'], $row['RATE'], $telecom);
	}
	
	// STOP_CLEAR
	$sql = "SELECT a.service_acct, b.service_num, b.rate, TO_CHAR(a.change_date, 'yyyymmdd') change_date, 'STOP_CLEAR' status FROM CUSTOMER_CHANGE a
			inner JOIN CUSTOMER b ON a.service_acct = b.service_acct
			WHERE CHANGE_TYPE = '이용정지' AND SUSP_CL_CD IN ('F0', 'F2', 'F6', 'F7', 'F8', 'F9') AND  TO_CHAR(change_date, 'yyyymmdd') = '$today'
			AND b.rate LIKE '%$rateNm%'
			ORDER BY b.service_num";

	$result = oci_parse($conn, iconv("UTF-8", "EUC-KR", $sql));
	oci_execute($result);
	
	while($row = oci_fetch_array($result, OCI_ASSOC)){
		
		$data_arr2 = array(
			'userPhone'=>AesEncrypt2($key, $row['SERVICE_NUM']),
			'status'=>$row['STATUS'],
			'changeDate'=>$row['CHANGE_DATE'],
			'rateNm'=>iconv("EUC-KR", "UTF-8", $row['RATE'])
		);

		$data_json2 = json_encode2($data_arr2);
		log_write($logFile, date("Y-m-d H:i:s")." [API send]: $data_json2 \n");
		// print_r($data_json2);
		// exit;
		
		$output = rest_post($url, $headers, $data_json2);
		log_write($logFile, date("Y-m-d H:i:s")." [API return]: $output \n\n");
		// print_r($output);
		
		// 전송내역 저장		
		Save_Rslt($conn, $sql, $row['SERVICE_ACCT'], $row['SERVICE_NUM'], $row['STATUS'], $row['RATE'], $telecom);
	}
	//LGT =======================================================================================================
		
	$telecom = "LGT";
	log_write($logFile, date("Y-m-d H:i:s")." [carrier]: $telecom \n");
	
	//JOIN
	$sql = "SELECT entrno service_acct, '010'||substr(PRODNO,5,LENGTH(PRODNO)) service_num, CUSTNM, PRSSDT CHANGE_DATE, 'JOIN' status, ratenm rate from CUST_LOG a
	left outer JOIN CODE_RATECD b ON a.SVCCD = b.RATECD
	WHERE PRSSDT = '$today' AND EVNTCD = 'NAC' AND RATENM LIKE '%$rateNm%'
	ORDER BY PRODNO";
	
	$result = oci_parse($conn_lg, iconv("UTF-8", "EUC-KR", $sql));
	oci_execute($result);

	while($row = oci_fetch_array($result, OCI_ASSOC)){
		
		$data_arr2 = array(
			'userPhone'=>AesEncrypt2($key, $row['SERVICE_NUM']),
			'status'=>$row['STATUS'],
			'changeDate'=>$row['CHANGE_DATE'],
			'rateNm'=>iconv("EUC-KR", "UTF-8", $row['RATE'])
		);
		
		$data_json2 = json_encode2($data_arr2);
		// print_r($data_json2);
				
		log_write($logFile, date("Y-m-d H:i:s")." [API send]: $data_json2 \n");
		// print_r($data_json2);
		
		$output = rest_post($url, $headers, $data_json2);
		log_write($logFile, date("Y-m-d H:i:s")." [API return]: $output \n\n");
		// print_r($output);
		// echo "<br>";
		
		// 전송내역 저장		
		Save_Rslt($conn, $sql, $row['SERVICE_ACCT'], $row['SERVICE_NUM'], $row['STATUS'], $row['RATE'], $telecom);
		
		// 고객정보 업데이트 - 후후 요금제 가입대상 표시
		$sql = "update cust set whowho = 'Y' where ENTRNO = '".$row['SERVICE_ACCT']."'";
		$result3 = oci_parse($conn_lg, $sql);
		oci_execute($result3);
	}
	//CANCEL
	$sql = "SELECT entrno service_acct, '010'||substr(PRODNO,5,LENGTH(PRODNO)) service_num, CUSTNM, PRSSDT change_date, 'CANCEL' status, ratenm rate from CUST_LOG a
	left outer JOIN CODE_RATECD b ON a.SVCCD = b.RATECD
	WHERE PRSSDT = '$today' AND EVNTCD = 'CAN' AND RATENM LIKE '%$rateNm%'
	ORDER BY PRODNO";

	$result = oci_parse($conn_lg, iconv("UTF-8", "EUC-KR", $sql));
	oci_execute($result);
	
	while($row = oci_fetch_array($result, OCI_ASSOC)){
		
		$data_arr2 = array(
			'userPhone'=>AesEncrypt2($key, $row['SERVICE_NUM']),
			'status'=>$row['STATUS'],
			'changeDate'=>$row['CHANGE_DATE'],
			'rateNm'=>iconv("EUC-KR", "UTF-8", $row['RATE'])
		);
		
		$data_json2 = json_encode2($data_arr2);
		log_write($logFile, date("Y-m-d H:i:s")." [API send]: $data_json2 \n");
		// print_r($data_json2);
		
		$output = rest_post($url, $headers, $data_json2);
		log_write($logFile, date("Y-m-d H:i:s")." [API return]: $output \n\n");
		// print_r($output);
		// echo "<br>";
		
		// 전송내역 저장		
		Save_Rslt($conn, $sql, $row['SERVICE_ACCT'], $row['SERVICE_NUM'], $row['STATUS'], $row['RATE'], $telecom);
		
		// 고객정보 업데이트 - 후후 요금제 가입대상 표시 제거
		$sql = "update cust set whowho = '' where ENTRNO = '".$row['SERVICE_ACCT']."'";
		$result3 = oci_parse($conn_lg, $sql);
		oci_execute($result3);
	}

//	/*
	//요금제 변경(JOIN)
	$sql = "SELECT entrno service_acct, '010'||substr(PRODNO,5,LENGTH(PRODNO)) service_num, CUSTNM, PRSSDT change_date, 'JOIN' status, ratenm rate from CUST_LOG a
	left outer JOIN CODE_RATECD b ON a.SVCCD = b.RATECD
	WHERE PRSSDT = '$today' AND EVNTCD = 'C11' AND RATENM LIKE '%$rateNm%'
	ORDER BY PRODNO";

	$result = oci_parse($conn_lg, iconv("UTF-8", "EUC-KR", $sql));
	oci_execute($result);
	
	while($row = oci_fetch_array($result, OCI_ASSOC)){
		
		$data_arr2 = array(
			'userPhone'=>AesEncrypt2($key, $row['SERVICE_NUM']),
			'status'=>$row['STATUS'],
			'changeDate'=>$row['CHANGE_DATE'],
			'rateNm'=>iconv("EUC-KR", "UTF-8", $row['RATE'])
		);

		$data_json2 = json_encode2($data_arr2);
		log_write($logFile, date("Y-m-d H:i:s")." [API send]: $data_json2 \n");
		//print_r($data_json2);
		//exit;
		
		$output = rest_post($url, $headers, $data_json2);
		log_write($logFile, date("Y-m-d H:i:s")." [API return]: $output \n\n");
		// print_r($output);
		// echo "<br>";
		
		// 전송내역 저장		
		Save_Rslt($conn, $sql, $row['SERVICE_ACCT'], $row['SERVICE_NUM'], $row['STATUS'], $row['RATE'], $telecom);
		
		// 고객정보 업데이트 - 후후 요금제 가입대상 표시
		$sql = "update cust set whowho = 'Y' where ENTRNO = '".$row['SERVICE_ACCT']."'";
		$result3 = oci_parse($conn_lg, $sql);
		oci_execute($result3);
	}
	
	//요금제 변경(CANCEL)
	$sql = "select a.entrno service_acct, '010'||substr(a.PRODNO,5,LENGTH(a.PRODNO)) service_num, a.CUSTNM, PRSSDT change_date, 'CANCEL' status, ratenm rate FROM cust a
	left outer JOIN cust_log b ON a.ENTRNO = b.ENTRNO
    left outer JOIN CODE_RATECD c ON a.SVCCD = c.RATECD
    WHERE PRSSDT = '$today' AND EVNTCD = 'C11' AND RATENM NOT LIKE '%$rateNm%' AND NVL(whowho, '') = 'Y'
	ORDER BY a.PRODNO";

	$result = oci_parse($conn_lg, iconv("UTF-8", "EUC-KR", $sql));
	oci_execute($result);
	
	while($row = oci_fetch_array($result, OCI_ASSOC)){
		
		$data_arr2 = array(
			'userPhone'=>AesEncrypt2($key, $row['SERVICE_NUM']),
			'status'=>$row['STATUS'],
			'changeDate'=>$row['CHANGE_DATE'],
			'rateNm'=>iconv("EUC-KR", "UTF-8", $row['RATE'])
		);

		$data_json2 = json_encode2($data_arr2);
		log_write($logFile, date("Y-m-d H:i:s")." [API send]: $data_json2 \n");
		//print_r($data_json2);
		//exit;
		
		$output = rest_post($url, $headers, $data_json2);
		log_write($logFile, date("Y-m-d H:i:s")." [API return]: $output \n\n");
		// print_r($output);
		// echo "<br>";
		
		// 전송내역 저장		
		Save_Rslt($conn, $sql, $row['SERVICE_ACCT'], $row['SERVICE_NUM'], $row['STATUS'], $row['RATE'], $telecom);
		
		// 고객정보 업데이트 - 후후 요금제 가입대상 표시 제거
		$sql = "update cust set whowho = '' where ENTRNO = '".$row['SERVICE_ACCT']."'";
		$result3 = oci_parse($conn_lg, $sql);
		oci_execute($result3);		
	}

	//STOP
	$sql = "SELECT entrno service_acct, '010'||substr(PRODNO,5,LENGTH(PRODNO)) service_num, CUSTNM, PRSSDT change_date, 'STOP' status, ratenm rate from CUST_LOG a
	left outer JOIN CODE_RATECD b ON a.SVCCD = b.RATECD
	WHERE PRSSDT = '$today' AND EVNTCD = 'SUS' AND RATENM LIKE '%$rateNm%'
	ORDER BY PRODNO";

	$result = oci_parse($conn_lg, iconv("UTF-8", "EUC-KR", $sql));
	oci_execute($result);
	
	while($row = oci_fetch_array($result, OCI_ASSOC)){
		
		$data_arr2 = array(
			'userPhone'=>AesEncrypt2($key, $row['SERVICE_NUM']),
			'status'=>$row['STATUS'],
			'changeDate'=>$row['CHANGE_DATE'],
			'rateNm'=>iconv("EUC-KR", "UTF-8", $row['RATE'])
		);

		$data_json2 = json_encode2($data_arr2);
		log_write($logFile, date("Y-m-d H:i:s")." [API send]: $data_json2 \n");
		//print_r($data_json2);
		//exit;
		
		$output = rest_post($url, $headers, $data_json2);
		log_write($logFile, date("Y-m-d H:i:s")." [API return]: $output \n\n");
		// print_r($output);
		// echo "<br>";
		
		// 전송내역 저장		
		Save_Rslt($conn, $sql, $row['SERVICE_ACCT'], $row['SERVICE_NUM'], $row['STATUS'], $row['RATE'], $telecom);
	}
	
	//STOP_CLEAR
	$sql = "SELECT entrno service_acct, '010'||substr(PRODNO,5,LENGTH(PRODNO)) service_num, CUSTNM, PRSSDT change_date, 'STOP_CLEAR' status, ratenm rate from CUST_LOG a
	left outer JOIN CODE_RATECD b ON a.SVCCD = b.RATECD
	WHERE PRSSDT = '$today' AND EVNTCD = 'RSP' AND RATENM LIKE '%$rateNm%'
	ORDER BY PRODNO";

	$result = oci_parse($conn_lg, iconv("UTF-8", "EUC-KR", $sql));
	oci_execute($result);
	
	while($row = oci_fetch_array($result, OCI_ASSOC)){
		
		$data_arr2 = array(
			'userPhone'=>AesEncrypt2($key, $row['SERVICE_NUM']),
			'status'=>$row['STATUS'],
			'changeDate'=>$row['CHANGE_DATE'],
			'rateNm'=>iconv("EUC-KR", "UTF-8", $row['RATE'])
		);

		$data_json2 = json_encode2($data_arr2);
		log_write($logFile, date("Y-m-d H:i:s")." [API send]: $data_json2 \n");
		//print_r($data_json2);
		//exit;
		
		$output = rest_post($url, $headers, $data_json2);
		log_write($logFile, date("Y-m-d H:i:s")." [API return]: $output \n\n");
		// print_r($output);
		// echo "<br>";
		
		// 전송내역 저장		
		Save_Rslt($conn, $sql, $row['SERVICE_ACCT'], $row['SERVICE_NUM'], $row['STATUS'], $row['RATE'], $telecom);		
	}

//*/	
	oci_free_statement($result);
	echo "send complete";

function Save_Rslt($conn, $sql, $service_acct, $service_num, $status, $rateNm, $telecom){

	$sql = "insert into cs_whowho_send_list values ('".$service_acct."', '".$service_num."', '".$status."', '".$rateNm."', '".$telecom."', SYSDATE)";
	// echo $sql;
	$result2 = oci_parse($conn, $sql);
	oci_execute($result2);
	oci_free_statement($result2);	
}

?>
