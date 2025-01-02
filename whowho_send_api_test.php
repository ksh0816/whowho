<?php
	header('Content-Type: application/json; charset=UTF-8');
	
	$isDev = true;
	$test = $isDev == true ? "_test" : "";

	include_once "./inc_func.php";

	$logFile = log_exist($test);
	log_write($logFile, date("Y-m-d H:i:s")." [Request Start] \n");

	// token 발급 5회까지 요청
	for ($i=1; $i <= 5; $i++) {

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

	// $token_val = "eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJzbWFydGVsIiwiZXhwIjoxNzM1Mjk1NjYwLCJpYXQiOjE3MzUyNzc2NjB9.aVs1Eqp24psjrsSNlB2lFga-jUxGtvnhLPvym1dQAXk";
	echo $token_val;
	exit;
	
	if ($token_val != "") {
	
		//스마텔 사용자 연동
		$url = $domain."/apis/smartel/user/whowho";	
		$headers = rest_header2($token_val);
			
		$timestamp = strtotime("Now");
		$timestamp = strtotime("-1 days");
		$today = date('Ymd', $timestamp);
		$today = "20241227";
		$rateNm	= "후후";
		$rateNm2 = "안심거래";
		$divisionCode = "";

		//가입,해지,정지,정지해제 데이터 추출(JOIN, CANCEL, STOP, STOP_CLEAR)
		//SKT =======================================================================================================
		
		$telecom = "SKT";
		log_write($logFile, date("Y-m-d H:i:s")." [carrier]: $telecom \n");
		
		//JOIN
		$sql = "SELECT service_acct, service_num, NAME, rate, TO_CHAR(open_date, 'yyyymmdd') change_date, 'JOIN' status FROM CUSTOMER
				WHERE TO_CHAR(open_date, 'yyyymmdd') = '$today' AND (rate LIKE '%$rateNm%' or rate LIKE '%$rateNm2%')
				order by service_num";
		
		$result = oci_parse($conn, iconv("UTF-8", "EUC-KR", $sql));
		oci_execute($result);
		
		while($row = oci_fetch_array($result, OCI_ASSOC)){
			
			$divisionCode = strpos($row['RATE'], $rateNm2) === false ? "" : "SMT_ANSIM";

			$data_arr2 = array(
				'userPhone'=>AesEncrypt2($key, $row['SERVICE_NUM']),
				'status'=>$row['STATUS'],
				'changeDate'=>$row['CHANGE_DATE'],
				'rateNm'=>iconv("EUC-KR", "UTF-8", $row['RATE']),
				'divisionCode'=>$divisionCode
			);

			log_write($logFile, date("Y-m-d H:i:s")." [SERVICE_NUM]: ".$row['SERVICE_NUM']." \n");
			$data_json2 = json_encode2($data_arr2);
			log_write($logFile, date("Y-m-d H:i:s")." [API send]: $data_json2 \n");
			// print_r($data_json2);
			// exit;
			
			$output = rest_post($url, $headers, $data_json2);
			log_write($logFile, date("Y-m-d H:i:s")." [API return]: $output \n\n");
			// print_r($output);
			// echo "<br>";
			
			// 전송내역 저장		
			Save_Rslt($conn, $sql, $row['SERVICE_ACCT'], $row['SERVICE_NUM'], $row['STATUS'], $row['RATE'], $telecom, $test);
		}

		// CANCEL
		$sql = "SELECT service_acct, service_num, NAME, rate, open_date,  replace(close_date, '-') change_date, 'CANCEL' status FROM CUSTOMER
				WHERE replace(close_date, '-') = '$today' AND (rate LIKE '%$rateNm%' or rate LIKE '%$rateNm2%')
				order by service_num";

		$result = oci_parse($conn, iconv("UTF-8", "EUC-KR", $sql));
		oci_execute($result);
		
		while($row = oci_fetch_array($result, OCI_ASSOC)){
			
			$divisionCode = strpos($row['RATE'], $rateNm2) === false ? "" : "SMT_ANSIM";

			$data_arr2 = array(
				'userPhone'=>AesEncrypt2($key, $row['SERVICE_NUM']),
				'status'=>$row['STATUS'],
				'changeDate'=>$row['CHANGE_DATE'],
				'rateNm'=>iconv("EUC-KR", "UTF-8", $row['RATE']),
				'divisionCode'=>$divisionCode
			);

			log_write($logFile, date("Y-m-d H:i:s")." [SERVICE_NUM]: ".$row['SERVICE_NUM']." \n");
			$data_json2 = json_encode2($data_arr2);
			log_write($logFile, date("Y-m-d H:i:s")." [API send]: $data_json2 \n");
			// print_r($data_json2);
			// exit;
			
			$output = rest_post($url, $headers, $data_json2);
			log_write($logFile, date("Y-m-d H:i:s")." [API return]: $output \n\n");
			// print_r($output);
			
			// 전송내역 저장		
			Save_Rslt($conn, $sql, $row['SERVICE_ACCT'], $row['SERVICE_NUM'], $row['STATUS'], $row['RATE'], $telecom, $test);
		}
		
		// JOIN, CANCEL(요금제 변경)
		$sql = "SELECT service_acct, svc_num service_num, old_rate, new_rate, TO_CHAR(change_date, 'yyyymmdd') change_date FROM CUSTOMER_CHANGE
				WHERE change_type = '요금제변경' AND (old_rate like '%$rateNm%' OR new_rate LIKE '%$rateNm%' or old_rate like '%$rateNm2%' OR new_rate LIKE '%$rateNm2%')
				AND TO_CHAR(change_date, 'yyyymmdd') = '$today'
				ORDER BY svc_num";

		$result = oci_parse($conn, iconv("UTF-8", "EUC-KR", $sql));
		oci_execute($result);
		
		while($row = oci_fetch_array($result, OCI_ASSOC)){
			
			$old_rate = iconv("EUC-KR", "UTF-8", $row['OLD_RATE']);
			$new_rate = iconv("EUC-KR", "UTF-8", $row['NEW_RATE']);
			
			$status = "";
			
			// JOIN인 경우, rateNm
			if(strpos($old_rate, "$rateNm") === false && strpos($new_rate, "$rateNm") !== false){
				$status = "JOIN";
			}
			
			// CANCEL 경우
			if(strpos($old_rate, "$rateNm") !== false && strpos($new_rate, "$rateNm") === false){
				$status = "CANCEL";
			}

			// JOIN인 경우, rateNm2
			if(strpos($old_rate, "$rateNm2") === false && strpos($new_rate, "$rateNm2") !== false){
				$status = "JOIN";
			}
			
			// CANCEL 경우
			if(strpos($old_rate, "$rateNm2") !== false && strpos($new_rate, "$rateNm2") === false){
				$status = "CANCEL";
			}

			$divisionCode = strpos($row['RATE'], $rateNm2) === false ? "" : "SMT_ANSIM";
			
			if($status) {
				$data_arr2 = array(
					'userPhone'=>AesEncrypt2($key, $row['SERVICE_NUM']),
					'status'=>$status,
					'changeDate'=>$row['CHANGE_DATE'],
					'rateNm'=>$new_rate,
					'divisionCode'=>$divisionCode
				);

				log_write($logFile, date("Y-m-d H:i:s")." [SERVICE_NUM]: ".$row['SERVICE_NUM']." \n");
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
				AND (b.rate LIKE '%$rateNm%' or b.rate LIKE '%$rateNm2%')
				ORDER BY b.service_num";

		$result = oci_parse($conn, iconv("UTF-8", "EUC-KR", $sql));
		oci_execute($result);
		
		while($row = oci_fetch_array($result, OCI_ASSOC)){

			$divisionCode = strpos($row['RATE'], $rateNm2) === false ? "" : "SMT_ANSIM";
			
			$data_arr2 = array(
				'userPhone'=>AesEncrypt2($key, $row['SERVICE_NUM']),
				'status'=>$row['STATUS'],
				'changeDate'=>$row['CHANGE_DATE'],
				'rateNm'=>iconv("EUC-KR", "UTF-8", $row['RATE']),
				'divisionCode'=>$divisionCode
			);

			log_write($logFile, date("Y-m-d H:i:s")." [SERVICE_NUM]: ".$row['SERVICE_NUM']." \n");
			$data_json2 = json_encode2($data_arr2);
			log_write($logFile, date("Y-m-d H:i:s")." [API send]: $data_json2 \n");
			// print_r($data_json2);
			// exit;
			
			$output = rest_post($url, $headers, $data_json2);
			log_write($logFile, date("Y-m-d H:i:s")." [API return]: $output \n\n");
			// print_r($output);
			
			// 전송내역 저장		
			Save_Rslt($conn, $sql, $row['SERVICE_ACCT'], $row['SERVICE_NUM'], $row['STATUS'], $row['RATE'], $telecom, $test);
		}
		
		// STOP_CLEAR
		$sql = "SELECT a.service_acct, b.service_num, b.rate, TO_CHAR(a.change_date, 'yyyymmdd') change_date, 'STOP_CLEAR' status FROM CUSTOMER_CHANGE a
				inner JOIN CUSTOMER b ON a.service_acct = b.service_acct
				WHERE CHANGE_TYPE = '이용정지' AND SUSP_CL_CD IN ('F0', 'F2', 'F6', 'F7', 'F8', 'F9') AND  TO_CHAR(change_date, 'yyyymmdd') = '$today'
				AND (b.rate LIKE '%$rateNm%' or b.rate LIKE '%$rateNm2%')
				ORDER BY b.service_num";

		$result = oci_parse($conn, iconv("UTF-8", "EUC-KR", $sql));
		oci_execute($result);
		
		while($row = oci_fetch_array($result, OCI_ASSOC)){
			
			$divisionCode = strpos($row['RATE'], $rateNm2) === false ? "" : "SMT_ANSIM";

			$data_arr2 = array(
				'userPhone'=>AesEncrypt2($key, $row['SERVICE_NUM']),
				'status'=>$row['STATUS'],
				'changeDate'=>$row['CHANGE_DATE'],
				'rateNm'=>iconv("EUC-KR", "UTF-8", $row['RATE']),
				'divisionCode'=>$divisionCode
			);

			log_write($logFile, date("Y-m-d H:i:s")." [SERVICE_NUM]: ".$row['SERVICE_NUM']." \n");
			$data_json2 = json_encode2($data_arr2);
			log_write($logFile, date("Y-m-d H:i:s")." [API send]: $data_json2 \n");
			// print_r($data_json2);
			// exit;
			
			$output = rest_post($url, $headers, $data_json2);
			log_write($logFile, date("Y-m-d H:i:s")." [API return]: $output \n\n");
			// print_r($output);
			
			// 전송내역 저장		
			Save_Rslt($conn, $sql, $row['SERVICE_ACCT'], $row['SERVICE_NUM'], $row['STATUS'], $row['RATE'], $telecom, $test);
		}
		
		//LGT =======================================================================================================
			
		$telecom = "LGT";
		$divisionCode = "";
		log_write($logFile, date("Y-m-d H:i:s")." [carrier]: $telecom \n");
		
		//JOIN
		$sql = "SELECT entrno service_acct, '010'||substr(PRODNO,5,LENGTH(PRODNO)) service_num, CUSTNM, PRSSDT CHANGE_DATE, 'JOIN' status, ratenm rate from CUST_LOG a
		left outer JOIN CODE_RATECD b ON a.SVCCD = b.RATECD
		WHERE PRSSDT = '$today' AND EVNTCD = 'NAC' AND (RATENM LIKE '%$rateNm%' or RATENM LIKE '%$rateNm2%')
		ORDER BY PRODNO";
		
		$result = oci_parse($conn_lg, iconv("UTF-8", "EUC-KR", $sql));
		oci_execute($result);

		while($row = oci_fetch_array($result, OCI_ASSOC)){

			// echo $row['RATE']." / ".iconv("EUC-KR", "UTF-8", $row['RATE'])."\n";
			// $rate = iconv("EUC-KR", "UTF-8", $row['RATE']);
			// echo $rate."\n";
			// $pos = strpos($rate, $rateNm2);

			$divisionCode = strpos(iconv("EUC-KR", "UTF-8", $row['RATE']), $rateNm2) === false ? "" : "SMT_ANSIM";
			// echo "divisionCode : ".$divisionCode."\n";

			$data_arr2 = array(
				'userPhone'=>AesEncrypt2($key, $row['SERVICE_NUM']),
				'status'=>$row['STATUS'],
				'changeDate'=>$row['CHANGE_DATE'],
				'rateNm'=>iconv("EUC-KR", "UTF-8", $row['RATE']),
				'divisionCode'=>$divisionCode
			);
			
			log_write($logFile, date("Y-m-d H:i:s")." [SERVICE_NUM]: ".$row['SERVICE_NUM']." \n");
			$data_json2 = json_encode2($data_arr2);
			// print_r($data_json2);
					
			log_write($logFile, date("Y-m-d H:i:s")." [API send]: $data_json2 \n");
			// print_r($data_json2);
			
			$output = rest_post($url, $headers, $data_json2);
			log_write($logFile, date("Y-m-d H:i:s")." [API return]: $output \n\n");
			// print_r($output);
			// echo "<br>";
			
			// 전송내역 저장		
			Save_Rslt($conn, $sql, $row['SERVICE_ACCT'], $row['SERVICE_NUM'], $row['STATUS'], $row['RATE'], $telecom, $test);
			
			// 고객정보 업데이트 - 후후 요금제 가입대상 표시
			$sql = "update cust$test set whowho = 'Y' where ENTRNO = '".$row['SERVICE_ACCT']."'";
			$result3 = oci_parse($conn_lg, $sql);
			oci_execute($result3);
		}

		//CANCEL
		$sql = "SELECT entrno service_acct, '010'||substr(PRODNO,5,LENGTH(PRODNO)) service_num, CUSTNM, PRSSDT change_date, 'CANCEL' status, ratenm rate from CUST_LOG a
		left outer JOIN CODE_RATECD b ON a.SVCCD = b.RATECD
		WHERE PRSSDT = '$today' AND EVNTCD = 'CAN' AND (RATENM LIKE '%$rateNm%' or RATENM LIKE '%$rateNm2%')
		ORDER BY PRODNO";

		$result = oci_parse($conn_lg, iconv("UTF-8", "EUC-KR", $sql));
		oci_execute($result);
		
		while($row = oci_fetch_array($result, OCI_ASSOC)){
			
			$divisionCode = strpos(iconv("EUC-KR", "UTF-8", $row['RATE']), $rateNm2) === false ? "" : "SMT_ANSIM";
			
			$data_arr2 = array(
				'userPhone'=>AesEncrypt2($key, $row['SERVICE_NUM']),
				'status'=>$row['STATUS'],
				'changeDate'=>$row['CHANGE_DATE'],
				'rateNm'=>iconv("EUC-KR", "UTF-8", $row['RATE']),
				'divisionCode'=>$divisionCode
			);
			
			log_write($logFile, date("Y-m-d H:i:s")." [SERVICE_NUM]: ".$row['SERVICE_NUM']." \n");
			$data_json2 = json_encode2($data_arr2);
			log_write($logFile, date("Y-m-d H:i:s")." [API send]: $data_json2 \n");
			// print_r($data_json2);
			
			$output = rest_post($url, $headers, $data_json2);
			log_write($logFile, date("Y-m-d H:i:s")." [API return]: $output \n\n");
			// print_r($output);
			// echo "<br>";
			
			// 전송내역 저장		
			Save_Rslt($conn, $sql, $row['SERVICE_ACCT'], $row['SERVICE_NUM'], $row['STATUS'], $row['RATE'], $telecom, $test);
			
			// 고객정보 업데이트 - 후후 요금제 가입대상 표시 제거
			$sql = "update cust$test set whowho = '' where ENTRNO = '".$row['SERVICE_ACCT']."'";
			$result3 = oci_parse($conn_lg, $sql);
			oci_execute($result3);
		}

	//	/*
		//요금제 변경(JOIN)
		$sql = "SELECT entrno service_acct, '010'||substr(PRODNO,5,LENGTH(PRODNO)) service_num, CUSTNM, PRSSDT change_date, 'JOIN' status, ratenm rate from CUST_LOG a
		left outer JOIN CODE_RATECD b ON a.SVCCD = b.RATECD
		WHERE PRSSDT = '$today' AND EVNTCD = 'C11' AND (RATENM LIKE '%$rateNm%' or RATENM LIKE '%$rateNm2%')
		ORDER BY PRODNO";

		$result = oci_parse($conn_lg, iconv("UTF-8", "EUC-KR", $sql));
		oci_execute($result);
		
		while($row = oci_fetch_array($result, OCI_ASSOC)){
			
			$divisionCode = strpos(iconv("EUC-KR", "UTF-8", $row['RATE']), $rateNm2) === false ? "" : "SMT_ANSIM";
			
			$data_arr2 = array(
				'userPhone'=>AesEncrypt2($key, $row['SERVICE_NUM']),
				'status'=>$row['STATUS'],
				'changeDate'=>$row['CHANGE_DATE'],
				'rateNm'=>iconv("EUC-KR", "UTF-8", $row['RATE']),
				'divisionCode'=>$divisionCode
			);

			log_write($logFile, date("Y-m-d H:i:s")." [SERVICE_NUM]: ".$row['SERVICE_NUM']." \n");
			$data_json2 = json_encode2($data_arr2);
			log_write($logFile, date("Y-m-d H:i:s")." [API send]: $data_json2 \n");
			//print_r($data_json2);
			//exit;
			
			$output = rest_post($url, $headers, $data_json2);
			log_write($logFile, date("Y-m-d H:i:s")." [API return]: $output \n\n");
			// print_r($output);
			// echo "<br>";
			
			// 전송내역 저장		
			Save_Rslt($conn, $sql, $row['SERVICE_ACCT'], $row['SERVICE_NUM'], $row['STATUS'], $row['RATE'], $telecom, $test);
			
			// 고객정보 업데이트 - 후후 요금제 가입대상 표시
			$sql = "update cust$test set whowho = 'Y' where ENTRNO = '".$row['SERVICE_ACCT']."'";
			$result3 = oci_parse($conn_lg, $sql);
			oci_execute($result3);
		}
		
		//요금제 변경(CANCEL)
		$sql = "select a.entrno service_acct, '010'||substr(a.PRODNO,5,LENGTH(a.PRODNO)) service_num, a.CUSTNM, PRSSDT change_date, 'CANCEL' status, ratenm rate FROM cust a
		left outer JOIN cust_log b ON a.ENTRNO = b.ENTRNO
		left outer JOIN CODE_RATECD c ON a.SVCCD = c.RATECD
		WHERE PRSSDT = '$today' AND EVNTCD = 'C11' AND (RATENM NOT LIKE '%$rateNm%' or RATENM NOT LIKE '%$rateNm2%') AND NVL(whowho, '') = 'Y'
		ORDER BY a.PRODNO";

		$result = oci_parse($conn_lg, iconv("UTF-8", "EUC-KR", $sql));
		oci_execute($result);
		
		while($row = oci_fetch_array($result, OCI_ASSOC)){
			
			$divisionCode = strpos(iconv("EUC-KR", "UTF-8", $row['RATE']), $rateNm2) === false ? "" : "SMT_ANSIM";
			
			$data_arr2 = array(
				'userPhone'=>AesEncrypt2($key, $row['SERVICE_NUM']),
				'status'=>$row['STATUS'],
				'changeDate'=>$row['CHANGE_DATE'],
				'rateNm'=>iconv("EUC-KR", "UTF-8", $row['RATE']),
				'divisionCode'=>$divisionCode
			);

			log_write($logFile, date("Y-m-d H:i:s")." [SERVICE_NUM]: ".$row['SERVICE_NUM']." \n");
			$data_json2 = json_encode2($data_arr2);
			log_write($logFile, date("Y-m-d H:i:s")." [API send]: $data_json2 \n");
			//print_r($data_json2);
			//exit;
			
			$output = rest_post($url, $headers, $data_json2);
			log_write($logFile, date("Y-m-d H:i:s")." [API return]: $output \n\n");
			// print_r($output);
			// echo "<br>";
			
			// 전송내역 저장		
			Save_Rslt($conn, $sql, $row['SERVICE_ACCT'], $row['SERVICE_NUM'], $row['STATUS'], $row['RATE'], $telecom, $test);
			
			// 고객정보 업데이트 - 후후 요금제 가입대상 표시 제거
			$sql = "update cust$test set whowho = '' where ENTRNO = '".$row['SERVICE_ACCT']."'";
			$result3 = oci_parse($conn_lg, $sql);
			oci_execute($result3);		
		}

		//STOP
		$sql = "SELECT entrno service_acct, '010'||substr(PRODNO,5,LENGTH(PRODNO)) service_num, CUSTNM, PRSSDT change_date, 'STOP' status, ratenm rate from CUST_LOG a
		left outer JOIN CODE_RATECD b ON a.SVCCD = b.RATECD
		WHERE PRSSDT = '$today' AND EVNTCD = 'SUS' AND (RATENM LIKE '%$rateNm%' or RATENM LIKE '%$rateNm2%')
		ORDER BY PRODNO";

		$result = oci_parse($conn_lg, iconv("UTF-8", "EUC-KR", $sql));
		oci_execute($result);
		
		while($row = oci_fetch_array($result, OCI_ASSOC)){
			
			$divisionCode = strpos(iconv("EUC-KR", "UTF-8", $row['RATE']), $rateNm2) === false ? "" : "SMT_ANSIM";
			
			$data_arr2 = array(
				'userPhone'=>AesEncrypt2($key, $row['SERVICE_NUM']),
				'status'=>$row['STATUS'],
				'changeDate'=>$row['CHANGE_DATE'],
				'rateNm'=>iconv("EUC-KR", "UTF-8", $row['RATE']),
				'divisionCode'=>$divisionCode
			);

			log_write($logFile, date("Y-m-d H:i:s")." [SERVICE_NUM]: ".$row['SERVICE_NUM']." \n");
			$data_json2 = json_encode2($data_arr2);
			log_write($logFile, date("Y-m-d H:i:s")." [API send]: $data_json2 \n");
			//print_r($data_json2);
			//exit;
			
			$output = rest_post($url, $headers, $data_json2);
			log_write($logFile, date("Y-m-d H:i:s")." [API return]: $output \n\n");
			// print_r($output);
			// echo "<br>";
			
			// 전송내역 저장		
			Save_Rslt($conn, $sql, $row['SERVICE_ACCT'], $row['SERVICE_NUM'], $row['STATUS'], $row['RATE'], $telecom, $test);
		}
		
		//STOP_CLEAR
		$sql = "SELECT entrno service_acct, '010'||substr(PRODNO,5,LENGTH(PRODNO)) service_num, CUSTNM, PRSSDT change_date, 'STOP_CLEAR' status, ratenm rate from CUST_LOG a
		left outer JOIN CODE_RATECD b ON a.SVCCD = b.RATECD
		WHERE PRSSDT = '$today' AND EVNTCD = 'RSP' AND (RATENM LIKE '%$rateNm%' or RATENM LIKE '%$rateNm2%')
		ORDER BY PRODNO";

		$result = oci_parse($conn_lg, iconv("UTF-8", "EUC-KR", $sql));
		oci_execute($result);
		
		while($row = oci_fetch_array($result, OCI_ASSOC)){

			$divisionCode = strpos(iconv("EUC-KR", "UTF-8", $row['RATE']), $rateNm2) === false ? "" : "SMT_ANSIM";
			
			$data_arr2 = array(
				'userPhone'=>AesEncrypt2($key, $row['SERVICE_NUM']),
				'status'=>$row['STATUS'],
				'changeDate'=>$row['CHANGE_DATE'],
				'rateNm'=>iconv("EUC-KR", "UTF-8", $row['RATE']),
				'divisionCode'=>$divisionCode
			);

			log_write($logFile, date("Y-m-d H:i:s")." [SERVICE_NUM]: ".$row['SERVICE_NUM']." \n");
			$data_json2 = json_encode2($data_arr2);
			log_write($logFile, date("Y-m-d H:i:s")." [API send]: $data_json2 \n");
			//print_r($data_json2);
			//exit;
			
			$output = rest_post($url, $headers, $data_json2);
			log_write($logFile, date("Y-m-d H:i:s")." [API return]: $output \n\n");
			// print_r($output);
			// echo "<br>";
			
			// 전송내역 저장		
			Save_Rslt($conn, $sql, $row['SERVICE_ACCT'], $row['SERVICE_NUM'], $row['STATUS'], $row['RATE'], $telecom, $test);		
		}

	//*/	
		oci_free_statement($result);
		// echo "send complete";
	} else {
		log_write($logFile, date("Y-m-d H:i:s")." [error]: token 값이 없습니다. \n\n");
	}

function Save_Rslt($conn, $sql, $service_acct, $service_num, $status, $rateNm, $telecom, $test){

	$sql = "insert into cs_whowho_send_list$test values ('".$service_acct."', '".$service_num."', '".$status."', '".$rateNm."', '".$telecom."', SYSDATE)";
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
