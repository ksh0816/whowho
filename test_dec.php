<?php
	include_once "./inc_func.php";
	
	$aes_num = $_REQUEST['plainText'];
	
	$dec_num = AesDecrypt2($key, $aes_num);
	
	echo "복호화 : ".$dec_num."<br>";
	
?>
