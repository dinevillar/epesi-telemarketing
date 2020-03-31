<?php
$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
if (!isset($_POST['epesi_cid']) || !isset($_POST['action'])) {
    http_response_code(400);
    die('Invalid Request');
}

define('CID', $_POST['epesi_cid']);
define('READ_ONLY_SESSION', true);
require_once('../../../include.php');
require_once('twilio-lib/Twilio/autoload.php');
ModuleManager::load_modules();
Base_AclCommon::set_sa_user();

$action = $_POST['action'];
$fail = false;
file_put_contents("test.txt", $_POST);
$pm = new Libs_Twilio_RBO_PhoneMappings();
$pm_rec = $pm->get_pm_rec($_POST['epesi_user']);
$twiml = new \Twilio\Twiml();
if (!$fail = !$pm_rec) {
    switch ($action) {
        case "call":
            if (!$fail = !isset($_POST['phone_number']) || !trim($_POST['phone_number'])) {
                $phoneNumberToDial = htmlspecialchars($_POST['phone_number']);
                $callerIdNumber = $pm_rec['phone_number'];
                $justNumber = preg_replace('/[^\da-z]/i', '', $phoneNumberToDial);
                for ($i = 0; $i < strlen($justNumber); $i++) {
                    $twiml->play(['digits' => "w" . $justNumber[$i]]);
                }
                $dial = $twiml->dial(['callerId' => $callerIdNumber]);
                $twiml->play(get_epesi_url() . '/modules/' . Libs_Twilio::module_name() . '/theme/outgoing.mp3', ['loop' => 0]);
                $dial->number($phoneNumberToDial);
            }
            break;
        default:
            $twiml->say("Thanks for calling!");
            break;
    }
}

header('Content-Type: application/xml');
print $twiml;
exit;
