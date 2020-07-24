<?php

if (!isset($_POST['index']) || !isset($_POST['rule']) || !isset($_POST['cid']) || !is_numeric($_POST['cid']) || !is_numeric($_POST['index'])
) {
    die('Invalid request');
}
$rule = json_decode($_POST['rule'], true);
if (!isset($rule['type']) || !isset($rule['action'])) {
    die('Invalid request');
}

define('JS_OUTPUT', 1);
define('CID', $_POST['cid']);
define('READ_ONLY_SESSION', true);

require_once('../../../../../include.php');

ModuleManager::load_modules();

$rules = ModuleManager::create_root()->init_module(Premium_Telemarketing_CallCampaigns_Rules::module_name());
if (isset($_POST['call_campaign']) && $_POST['call_campaign'] > 0) {
    $rules->mode = 'record';
    $rules->campaign = Utils_RecordBrowserCommon::get_record(Premium_Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME, $_POST['call_campaign']);
} else {
    $rules->mode = 'admin';
}
die($rules->route_template($_POST['index'], $rule));
