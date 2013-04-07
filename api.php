<?php
session_start();

require_once('api.class.php');

$API = new phylobyteAPI;

include_once('plugins/EmailAddressValidator.php');

if(isset($_SESSION['post_session_read']) && $_SESSION['post_session_read'] == true){
	if(isset($_SESSION['post_for'])) $_REQUEST['for'] = $_SESSION['post_for'];
	if(isset($_SESSION['post_do'])) $_REQUEST['do'] = $_SESSION['post_do'];
	if(isset($_SESSION['post_token'])) $_REQUEST['token'] = $_SESSION['post_token'];
	if(isset($_SESSION['post_with'])) $_REQUEST['with'] = $_SESSION['post_with'];
	if(isset($_SESSION['post_info'])) $_REQUEST['info'] = $_SESSION['post_info'];
	$_SESSION['post_session_read'] = false;
}


if( isset($_REQUEST['format']) ){
	if( $_REQUEST['format'] == 'highlight' ){
		$API->set_format('tabs');
	}elseif( $_REQUEST['format'] == 'highlightnarrow' ){
		$API->set_format('pretty');
	}else{
		$API->set_format(stripslashes($_REQUEST['format']));
	}
	
}

if( isset($_REQUEST['for']) ){
	$API->set_for(stripslashes($_REQUEST['for']));
}

if( isset($_REQUEST['do']) ){
	$API->set_do(stripslashes($_REQUEST['do']));
}

if( isset($_REQUEST['with']) ){
	$API->set_with(json_decode(stripslashes($_REQUEST['with']), true));
}
//echo($_REQUEST['with']);

if( isset($_REQUEST['token']) ){
	$API->set_token(stripslashes($_REQUEST['token']));
}

if( isset($_REQUEST['info']) ){
	$API->set_info(stripslashes($_REQUEST['info']));
}

if( isset($_REQUEST['onlydata']) ){
	$API->set_onlydata(stripslashes($_REQUEST['onlydata']));
}

$API->execute();

$resultString = $API->getResultString();

if($_REQUEST['format'] != 'raw'){
	
	$commonCssStyles = '
				pre.language-javascript{
					margin: 0;
					background-color: white;
				}
				pre.language-javascript .string{
					color: #090;
				}
				pre.language-javascript .number{
					color: #009;
				}
				pre.language-javascript .punctuation{
					color: #444;
				}
				pre.language-javascript .boolean{
					color: #880;
				}
				pre.language-javascript .keyword{
					color: #088;
				}
	';

	if( $_REQUEST['format'] == 'highlight' ){
		echo('
		<html>
			<head>
			<title>API</title>
			<link href="plugins/prism/prism.css" rel="stylesheet" />
			</head>
		<body>
			<script src="plugins/prism/prism.js"></script>
			<style>
				body{
					padding: .5em;
					margin: 0;
				}
				pre.language-javascript{
					font-size: 90%;
					border: 1px solid gray;
					border-radius: .5em;
				}
				'.$commonCssStyles.'
			</style>
		');
		echo('<pre><code class="language-javascript"> '.$resultString.' </code></pre>');
		echo('
		</body>
		</html>
		');
	}elseif( $_REQUEST['format'] == 'highlightnarrow' ){
		echo('
		<html>
			<head>
			<title>API</title>
			<link href="plugins/prism/prism.css" rel="stylesheet" />
			</head>
		<body>
			<script src="plugins/prism/prism.js"></script>
			<style>
				body{
					padding: 0;
					margin: 0;
				}
				pre.language-javascript{
					font-size: 75%;
					border: 1px solid gray;
					padding: .5em;
				}
				'.$commonCssStyles.'
			</style>
		');
		echo('<pre><code class="language-javascript"> '.$resultString.' </code></pre>');
		echo('
		</body>
		</html>
		');
	}else{
		header('Cache-Control: no-cache, must-revalidate');
		header('Content-type: application/json');
		echo($resultString);
	}
}else{
	header('Content-Type: text/plain');
	echo($resultString);
}




?>