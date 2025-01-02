<?php
	include_once "./inc_func.php";
	
	//토큰 발급 url
	$url_token = $domain."/apis/v1/auth/authenticate";

	$data_arr = array(
		'username'=>$username,
		'password'=>$password
	);
	
	$headers = rest_header();
	$data_json  = json_encode($data_arr);
	
	//토큰
	$token = rest_post($url_token, $headers, $data_json);
	$token_decode = json_decode($token);
	$token_val = $token_decode->authToken;
	
	//echo $token_val;

    echo("
        <script type='text/javascript'>
            location.href='index.php?token=$token_val';
        </script>	
    ");	

	// exit;
	

?>
