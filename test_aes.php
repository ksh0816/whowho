<?php
	include_once "./inc_func.php";
	
	$plainText = $_REQUEST['plainText'];
	
	echo "전화번호 : ".$plainText."<br>";
	
	$aes_num = AesEncrypt2($key, $plainText);
	
	echo "암호화 : ".$aes_num."<br>";
	
?>
