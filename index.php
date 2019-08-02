<?php 
// Download the library from GitHub: URL: https://github.com/google/google-api-php-client
require 'vendor/autoload.php';
session_start();
//$youtube_api_key = 'MY_KEY';
$oauth_client_id = 'xxxxxxxxxxxxxxxxxxxxxxxxxx.apps.googleusercontent.com';
$oauth_client_secret = 'xxxxxxxxxxxxxxxxxxxxxxx';
define('SCOPES', implode(' ', array(
  Google_Service_Gmail::GMAIL_READONLY,Google_Service_Gmail::GMAIL_COMPOSE,'https://mail.google.com','https://www.googleapis.com/auth/gmail.compose','https://www.googleapis.com/auth/gmail.modify','https://www.googleapis.com/auth/gmail.send')
));
//$client = new \Google_Client();
//$client->setDeveloperKey($youtube_api_key);
$client = new Google_Client();
$client->setClientId($oauth_client_id); 
$client->setClientSecret($oauth_client_secret);
$client->setScopes(SCOPES);
$redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'], FILTER_SANITIZE_URL);
$client->setRedirectUri($redirect);
$client->setAccessType('offline');  
$tokenSessionKey = 'token-' . $client->prepareScopes();
if (isset($_GET['code'])) {
  if (strval($_SESSION['state']) !== strval($_GET['state'])) {
    die('The session state did not match.');
  }
  $client->authenticate($_GET['code']);
  $_SESSION[$tokenSessionKey] = $client->getAccessToken();
  header('Location: ' . $redirect);
}
if (isset($_SESSION[$tokenSessionKey])) {
  $client->setAccessToken($_SESSION[$tokenSessionKey]);
}
$message  = "MIME-Version: 1.0\r\n";
$message .= "From: FromName <frommail@gmail.com>\r\n";
$message .= "To: Pavan <pavan9212@gmail.com>\r\n";
$message .= "Subject: =?utf-8?B?".base64_encode('Sample Subject Which Contains Non-Latin Characters'.date(DATE_RFC2822))."?=\r\n";
$message .= "Date: ".date(DATE_RFC2822)."\r\n"; 
$message .= "Content-Type: multipart/alternative; boundary=test\r\n\r\n";
$message .= "--test\r\n";
$message .= "Content-Type: text/plain; charset=UTF-8\r\n";
$message .= "Content-Transfer-Encoding: base64\r\n\r\n";
$message .= base64_encode('Sample email message which contains non-latin chcracters')."\r\n";
// The message needs to be encoded in Base64URL
$mime = rtrim(strtr(base64_encode($message), '+/', '-_'), '=');
$service = new \Google_Service_Gmail($client);
$msg = new \Google_Service_Gmail_Message();
$msg->setRaw($mime);
try {
    if ($client->getAccessToken()) {
        $results = $service->users_messages->send("me", $msg);
  print 'Message with ID: ' . $results->id . ' sent.';
    } else {
        // If the user hasn't authorized the app, initiate the OAuth flow
        $state = mt_rand();
        $client->setState($state);
        $_SESSION['state'] = $state;
        $authUrl = $client->createAuthUrl();
        $htmlBody = <<<END
<h3>Authorization Required</h3>
<p>You need to <a href="$authUrl">authorize access</a> before proceeding.<p>
END;
        echo $htmlBody;
    }
} catch (\Google_Service_Exception $e) {
    $gse_errors = $e->getErrors();
    echo '<h1>error!</h1>';
    echo '<pre>'.print_r($gse_errors, true).'</pre>';
}
?>