<?php
$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
if (!isset($_POST['cid']) && !isset($_POST['user'])) {
    http_response_code(400);
    die('Invalid Request');
}

define('CID', $_POST['cid']);
define('READ_ONLY_SESSION', true);
require_once('../../../../include.php');
ModuleManager::load_modules();
Base_AclCommon::set_sa_user();

$pm = new Premium_WebPhone_Twilio_RBO_PhoneMappings();
try {
    $tok = $pm->get_capability_token($_POST['user']);
    echo json_encode(['status' => 1, 'message' => $tok]);
} catch (Exception $e) {
    echo json_encode(['status' => 0, 'message' => $e->getMessage()]);
}
