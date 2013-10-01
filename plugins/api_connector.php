<?php

class api_connector{

	private $location = 'http://localhost/~daniel/phylobyte/api.php';

	function getResponse($for, $do, $token, $with){
	
		$fields = array(
			'do' => urlencode($do),
			'for' => urlencode($for),
			'token' => urlencode($token),
			'with' => urlencode(json_encode($with))
		);

		$post_string = null;
		foreach($fields as $key => $value){
			$post_string.= $key.'='.$value.'&';
		}

		rtrim($post_string, '&');

		$curlObj = curl_init();

		curl_setopt($curlObj,CURLOPT_URL, $this->location);
		curl_setopt($curlObj,CURLOPT_POST, count($fields));
		curl_setopt($curlObj,CURLOPT_POSTFIELDS, $post_string);
		curl_setopt($curlObj,CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($curlObj);
		curl_close($curlObj);

		return json_decode($response, false);
	}

}

?>
