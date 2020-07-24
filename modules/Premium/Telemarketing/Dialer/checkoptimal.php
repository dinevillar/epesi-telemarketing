<?php
if (!isset($_POST['campaign']) || !isset($_POST['record']) || !isset($_POST['record_type'])) {
    die('Invalid usage.');
}
$cid = $_POST['cid'];
define('CID', $cid);
require_once('../../../../include.php');
ModuleManager::load_modules();

$campaign = $_POST['campaign'];
$type = $_POST['record_type'];
$record = Utils_RecordBrowserCommon::get_record($type, $_POST['record']);

if (!$record) {
    die('Invalid record.');
}

if (!$record['local_time']) {
    $timeZone = Premium_Telemarketing_ContactLocalTimeCommon::query_local_timezone($record, true, $type);
    $record['local_time'] = $timeZone;
}

$optimal_call_time_start_f = Premium_Telemarketing_CallCampaignsCommon::get_settings(
    $campaign,
    'optimal_call_time_start'
);
$optimal_call_time_end_f = Premium_Telemarketing_CallCampaignsCommon::get_settings(
    $campaign,
    'optimal_call_time_end'
);
if (!$optimal_call_time_start_f || !$optimal_call_time_end_f) {
    die('Invalid campaign');
}
$optimal_call_time_start = new DateTime('now', new DateTimeZone($record['local_time']));
$optimal_call_time_start = $optimal_call_time_start->setTime(
    $optimal_call_time_start_f['H'],
    $optimal_call_time_start_f['i']
);
$optimal_call_time_end = new DateTime('now', new DateTimeZone($record['local_time']));
$optimal_call_time_end = $optimal_call_time_end->setTime(
    $optimal_call_time_end_f['H'],
    $optimal_call_time_end_f['i']
);

/* @var $record_local_time DateTime */
$record_local_time = Premium_Telemarketing_ContactLocalTimeCommon::local_time($record, $type, false, true);

if (Base_RegionalSettingsCommon::time_12h()) {
    $format = 'g:i A T';
} else {
    $format = 'H:i T';
}

$ret = array(
    'record_local_time' => $record_local_time->format($format),
    'optimal_call_time_start' => $optimal_call_time_start->format($format),
    'optimal_call_time_end' => $optimal_call_time_end->format($format),
);
if ($record_local_time >= $optimal_call_time_start && $record_local_time <= $optimal_call_time_end) {
    $ret['optimal'] = true;
} else {
    $ret['optimal'] = false;
}
header('Content-type: application/json');
echo json_encode($ret);
