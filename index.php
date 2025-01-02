<?php
	header('Content-Type: text/html; charset=utf-8');
	
	
	$now = date("YmdHis").gettimeofday()["usec"];
	$timestamp = strtotime("Now");
	$today = date('Ymd', $timestamp);
	
	echo "현재시간 : ".$now. '<br>';
	//echo "변경일자 : ".$today. '<br>';

	$token = $_REQUEST['token']
?>
	
	<br>
	* whowho API 테스트
	<br>
	<br>

	<a href="test_token.php">1.연결 테스트 - 토큰 발급</a><br>
	<?=$token?><br>
	<!--<a href="whowho_send_api.php">1.연결 테스트 - 토큰 발급</a>-->
			
	<br>
	<br>
	2.암복호화 테스트</a><br><br>
	- 암호화
	<form name="form1" method="POST" action="test_aes.php">
		<input type="text" name="plainText">
		<input type="submit" value="조회" style="cursor:hand;">
	</form><br>
	- 복호화
	<form name="form1" method="POST" action="test_dec.php">
		<input type="text" name="plainText">
		<input type="submit" value="조회" style="cursor:hand;">
	</form>
	<br>
	<br>
	3.데이터 전송</a>
	<form name="form1" method="POST" action="whowho_send_api_test.php">
		통신사 : <input type="radio" name="telecom" value="SKT" checked> SKT &nbsp;&nbsp;
				 <input type="radio" name="telecom" value="LGT"> LGU+<br>
		구분 : 
		<input type="radio" name="status" value="JOIN" checked> 가입 &nbsp;&nbsp;
		<input type="radio" name="status" value="CANCEL"> 해지 &nbsp;&nbsp;
		<input type="radio" name="status" value="CHANGE_JOIN"> 요금제변경(가입) &nbsp;&nbsp;
		<input type="radio" name="status" value="CHANGE_CANCEL"> 요금제변경(해지) &nbsp;&nbsp;
		<input type="radio" name="status" value="STOP"> 정지&nbsp;&nbsp;
		<input type="radio" name="status" value="STOP_CLEAR"> 정지해제 &nbsp;&nbsp;<br>
		회선번호 : <input type="text" name="serviceNum"><br>
		변경일자 :  <input type="text" name="openDate" value="<?=$today?>"><br>
		<input type="submit" value="전송" style="cursor:hand;">
	</form>
	
	<br>
	
	
