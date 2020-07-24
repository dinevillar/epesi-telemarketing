<?php

$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
if (!isset($_POST['cid']) || !isset($_POST['call_sid']) || !isset($_POST['user'])) {
    http_response_code(400);
    die('Invalid Request');
}

define('CID', $_POST['cid']);
define('READ_ONLY_SESSION', true);
require_once('../../../../include.php');
require_once('twilio-lib/Twilio/autoload.php');
ModuleManager::load_modules();
Base_AclCommon::set_sa_user();

$pm = new Premium_WebPhone_Twilio_RBO_PhoneMappings();
$response = [];
$twilio = $pm->get_twilio_service($_POST['user']);
$response = $twilio->calls($_POST['call_sid'])->fetch();
echo json_encode($response);



