<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 3/11/20
 * @Time: 2:09 PM
 */

defined("_VALID_ACCESS") || die('Direct access forbidden');

class Apps_DialerCommon extends ModuleCommon
{
    public static function menu()
    {
        if (Base_AclCommon::check_permission('Dialer'))
            return array(_M('Dialer') => array());
        return array();
    }

    public static function get_dialing_methods()
    {
        return array('none' => __('None'),
                'callto' => __('Web "callto" Protocol'),
                'tel' => __('Web "tel" Protocol'),
                'skype' => __('Skype Protocol'),)
            + ModuleManager::call_common_methods('dialer_description');
    }

    public static function get_dial_code_js($title, $method = 'none')
    {
        switch ($method) {
            case 'none':
                return $title;
            case 'callto':
                return 'window.location.href = "callto:' . $title . '"';
            case 'tel':
                return 'window.location.href = "tel:' . $title . '"';
            case 'skype':
                return 'window.location.href = "skype:' . $title . '?call"';
            default:
                $dialer = array($method . 'Common', 'dialer_js');
                if (is_callable($dialer))
                    return call_user_func($dialer, $title);
                return $title;
        }
    }

    /**
     * @param $campaign
     * @return array|mixed
     * @throws Exception
     */
    public static function get_next_record($campaign)
    {
        $settings = Telemarketing_CallCampaignsCommon::get_settings($campaign['id']);
        $record = array();
        if ($settings['prioritize_call_backs'] == 1) {
            $query = self::build_query($campaign, false, false, true);
            $record = DB::GetRow($query);
        }
        if (!$record || empty($record)) {
            $query = self::build_query($campaign, false, false);
            $record = DB::GetRow($query);
        }
        return $record;
    }

    /**
     * @param $campaign
     * @return mixed
     * @throws Exception
     */
    public static function count_remaining_records($campaign)
    {
        $query = self::build_query($campaign, false, true);
        return DB::GetOne($query);
    }

    /**
     * @param $campaign
     * @param bool $type
     * @param bool $count
     * @param bool $callback
     * @return string
     * @throws Exception
     */
    private static function build_query($campaign, $type = false, $count = false, $callback = false)
    {
        $settings = Telemarketing_CallCampaignsCommon::get_settings($campaign['id']);
        $disp_table = Telemarketing_CallCampaigns_Dispositions_RBO_Status::TABLE_NAME;
        $type = $type ? $type : $campaign['list_type'];
        $optimal_tz = false;

        if (!$count && $settings['filter_not_optimal_call_time'] == 1) {
            $optimal_tz_arr = array();
            $optimal_call_start = $settings['optimal_call_time_start'];
            $optimal_call_end = $settings['optimal_call_time_end'];
            $no_diff = $optimal_call_start["H"] === $optimal_call_end["H"] &&
                $optimal_call_start["i"] === $optimal_call_end["i"];
            if (!$no_diff) {
                $tzlist = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
                foreach ($tzlist as $tz) {
                    $optimal_start_dt = new DateTime('now', new DateTimeZone($tz));
                    $optimal_start_dt->setTime($optimal_call_start['H'], $optimal_call_start['i']);
                    $optimal_end_dt = new DateTime('now', new DateTimeZone($tz));
                    $optimal_end_dt->setTime($optimal_call_end['H'], $optimal_call_end['i']);
                    $now = new DateTime("now", new DateTimeZone($tz));
                    if ($now >= $optimal_start_dt && $now < $optimal_end_dt) {
                        $optimal_tz_arr[] = '\'' . strtolower($tz) . '\'';
                    }
                }
                $optimal_tz = implode(",", $optimal_tz_arr);
            }
        }

        if ($type == 'APC') {
            $contact_query = self::build_query($campaign, 'AP');
            $company_query = self::build_query($campaign, 'AC');
            if ($count) {
                $query = "SELECT COUNT(*) FROM (({$contact_query}) UNION ({$company_query})) x";
            } else {
                $query =
                    "({$contact_query}) UNION ({$company_query}) ORDER BY f_skip_date ASC, f_timestamp ASC";
                if ($settings['newest_records_first'] == 1) {
                    $query .= ", created_on DESC";
                } else {
                    $query .= ", created_on ASC";
                }
            }
            return $query;
        }

        $cols = array(
            "disposition.f_disposition",
            "disposition.f_talk_time",
            "disposition.f_skip_date",
            "disposition.f_locked_to",
            "disposition.f_timestamp",
            "disposition.f_call_campaign",
        );
        $from = array();
        $crits = array();
        if ($callback) {
            $crits[] = "disposition.f_call_back_time IS NOT NULL AND disposition.f_call_back_time <= NOW() " .
                "AND disposition.f_timestamp < disposition.f_call_back_time";
        }
        $my = CRM_ContactsCommon::get_my_record();
        if (!$count) {
            $crits[] =
                "(disposition.f_locked_to IS NULL OR disposition.f_locked_to = 0 OR disposition.f_locked_to = {$my['id']})";
        }
        $order = array();
        if ($callback) {
            $order[] = "disposition.f_call_back_time ASC";
        }
        $order[] = "disposition.f_skip_date ASC";
        $order[] = "disposition.f_timestamp ASC";

        $origType = $type;
        $criteria = null;
        if ($type === 'crm_criteria' && $campaign['lead_list']) {
            $criteria = Utils_RecordBrowserCommon::get_record('crm_criteria', $campaign['lead_list']);
            if ($criteria['recordset'] === 'company') {
                $type = 'AC';
            } else {
                $type = 'AP';
            }
        }
        switch ($type) {
            case 'premium_listmanager':
                $list = $campaign['list'];

                $cols[] = "listmanager.f_record_id";
                $cols[] = "listmanager.f_record_type";
                $cols[] = "listmanager.created_on";

                $from[] = "`premium_listmanager_element_data_1` AS listmanager ";
                //LEFT JOIN dispositions
                $from[] = "`{$disp_table}_data_1` AS disposition ON " .
                    "listmanager.f_record_id = disposition.f_record_id AND " .
                    "listmanager.f_record_type = disposition.f_record_type AND " .
                    "disposition.f_call_campaign = {$campaign['id']}";
                //LEFT JOIN company
                $from[] = "`company_data_1` AS company ON listmanager.f_record_id = company.id " .
                    "AND listmanager.f_record_type = 'company' ";
                //LEFT JOIN contact
                $from[] = "`contact_data_1` AS contact ON listmanager.f_record_id = contact.id " .
                    "AND listmanager.f_record_type = 'contact' ";

                $crits[] = "((contact.active = 1 AND listmanager.f_record_type = 'contact') OR (company.active = 1 AND " .
                    "listmanager.f_record_type = 'company'))";
                $crits[] = "listmanager.active = 1";
                $crits[] = "listmanager.f_active = 1";
                $crits[] = "listmanager.f_list_name = {$list}";
                if ($settings['filter_inv_phone'] == 1) { //Filter out records w/o phone number
                    $crits[] = "((listmanager.f_record_type = 'contact' AND (contact.f_work_phone IS NOT NULL " .
                        "OR contact.f_mobile_phone  IS NOT NULL OR contact.f_home_phone IS NOT NULL)) OR " .
                        "(listmanager.f_record_type = 'company' AND company.f_phone IS NOT NULL))";
                }
                //Filter out contacts not in optimal time
                if (!$count && $settings['filter_not_optimal_call_time'] == 1 && $optimal_tz) {
                    $crits[] = "(listmanager.f_record_type = 'contact' AND (contact.f_local_time IS NULL OR LOWER(contact.f_local_time) IN ({$optimal_tz}))) OR (listmanager.f_record_type = 'company' AND (company.f_local_time IS NULL OR LOWER(company.f_local_time) IN ({$optimal_tz})))";
                }
                //Remove:RecordFromAllCampaigns
                $crits[] = "((listmanager.f_record_type = 'contact' AND listmanager.f_record_id NOT IN " .
                    "(SELECT rr.f_record_id FROM `callcampaigns_blacklist_data_1` AS rr WHERE rr.f_record_type = 'contact' AND rr.active = 1)) OR " .
                    "(listmanager.f_record_type = 'company' AND listmanager.f_record_id NOT IN " .
                    "(SELECT rr.f_record_id FROM `callcampaigns_blacklist_data_1` AS rr WHERE rr.f_record_type ='company' AND rr.active = 1)))";

                if ($settings['newest_records_first'] == 1) {
                    $order[] = "listmanager.created_on DESC";
                    $order[] = "listmanager.f_record_id DESC";
                } else {
                    $order[] = "listmanager.created_on ASC";
                    $order[] = "listmanager.f_record_id ASC";
                }

                break;
            case 'AP':
                $cols[] = "contact.id as 'f_record_id'";
                $cols[] = "'contact' as 'f_record_type'";
                $cols[] = 'contact.created_on';

                $from[] = "`contact_data_1` AS contact";
                $from[] = "`{$disp_table}_data_1` AS disposition ON contact.id = disposition.f_record_id AND " .
                    "disposition.f_record_type = 'contact' AND disposition.f_call_campaign = {$campaign['id']}";

                $crits[] = "contact.active = 1";
                $crits[] = "contact.f_login IS NULL";
                if ($settings['filter_inv_phone'] == 1) { //Filter out records w/o phone number
                    $crits[] = "(contact.f_work_phone IS NOT NULL OR contact.f_mobile_phone IS NOT NULL OR " .
                        "contact.f_home_phone IS NOT NULL)";
                }
                //Filter out contacts not in optimal time
                if (!$count && $settings['filter_not_optimal_call_time'] == 1 && $optimal_tz) {
                    $crits[] = "(contact.f_local_time IS NULL OR LOWER(contact.f_local_time) IN ({$optimal_tz}))";
                }
                //Remove:RecordFromAllCampaigns
                $crits[] = "contact.id NOT IN " .
                    "(SELECT rr.f_record_id FROM `callcampaigns_blacklist_data_1` AS rr WHERE rr.f_record_type = 'contact' AND rr.active = 1)";

                if ($settings['newest_records_first'] == 1) {
                    $order[] = "contact.created_on DESC";
                    $order[] = "contact.id DESC";
                } else {
                    $order[] = "contact.created_on ASC";
                    $order[] = "contact.id ASC";
                }
                break;
            case 'AC':
                $cols[] = "company.id as 'f_record_id'";
                $cols[] = "'company' as 'f_record_type'";
                $cols[] = 'company.created_on';

                $main_company = CRM_ContactsCommon::get_main_company();

                $from[] = "`company_data_1` AS company";
                $from[] = "`{$disp_table}_data_1` AS disposition ON company.id = disposition.f_record_id AND " .
                    "disposition.f_record_type = 'company' AND disposition.f_call_campaign = {$campaign['id']}";

                $crits[] = "company.active = 1";
                $crits[] = "company.id <> {$main_company['id']}";
                if ($settings['filter_inv_phone'] == 1) { //Filter out records w/o phone number
                    $crits[] = "company.f_phone IS NOT NULL";
                }
                //Filter out contacts not in optimal time
                if (!$count && $settings['filter_not_optimal_call_time'] == 1 && $optimal_tz) {
                    $crits[] = "(company.f_local_time IS NULL OR LOWER(company.f_local_time) IN ({$optimal_tz}))";
                }
                //Remove:RecordFromAllCampaigns
                $crits[] = "company.id NOT IN " .
                    "(SELECT rr.f_record_id FROM `callcampaigns_blacklist_data_1` AS rr WHERE rr.f_record_type = 'company' AND rr.active = 1)";

                if ($settings['newest_records_first'] == 1) {
                    $order[] = "company.created_on DESC";
                    $order[] = "company.id DESC";
                } else {
                    $order[] = "company.created_on ASC";
                    $order[] = "company.id ASC";
                }
                break;
        }

        //Remove Record From Queue
        $matchedRules = Telemarketing_CallCampaigns_RulesCommon::match_rules($campaign, 'Record', 'Remove:RecordFromQueue');
        if (!empty($matchedRules)) {
            $r_crits = array();
            foreach ($matchedRules as $matchedRule) {
                $condition = $matchedRule['condition'];
                if ($condition) {
                    $condition = explode(':', $condition);
                    if ($condition[0] == 'Flagged' && isset($condition[1])) {
                        $r_crits[] = "disposition.f_disposition <> '{$condition[1]}'";
                    }
                }
            }
            if (!empty($r_crits)) {
                $crits[] = "(disposition.f_disposition IS NULL OR (" . implode(' AND ', $r_crits) . '))';
            }
        }

        if ($origType === 'criteria' && $criteria) {
            $criteria_raw = CRM_CriteriaCommon::get_raw_crits($criteria, $criteria['recordset']);
            $criteria_params = $criteria_raw[1] ? $criteria_raw[1] : array();
            if ($criteria_raw[0]) {
                $crits[] = DB::replace_values($criteria_raw[0], $criteria_params);
            }
        }

        if ($count) {
            $cols_str = "COUNT(*)";
        } else {
            $cols_str = implode(', ', $cols);
        }
        $from_str = implode(' LEFT JOIN ', $from);
        $crits_str = implode(' AND ', $crits);
        $order_str = implode(', ', $order);

        $query = "SELECT {$cols_str} FROM {$from_str} WHERE {$crits_str} ORDER BY {$order_str}";
        return $query;
    }

    /**
     * @param $r
     * @param $id
     * @param bool $long
     * @return string
     */
    public static function format_phone($r, $id, $long = true)
    {
        $num = $r[$id];
        if ($num && strpos($num, '+') === false && substr(preg_replace('/[^0-9]/', '', $num), 0, 2) !== '00') {
            if (isset($r['country']) && $r['country']) {
                $calling_code = Utils_CommonDataCommon::get_value('Calling_Codes/' . $r['country']);
                if ($calling_code)
                    $num = $calling_code . $num;
            }
        }
        if (!$long)
            return strtoupper($id[0]) . ':' . $num;
        else
            return trim(str_replace('Phone', '', ucwords(str_replace("_", " ", $id)))) . ':' . $num;
    }

    /**
     * @param $disposition
     * @param $log
     */
    public static function add_call_log($disposition, $log)
    {
        if (is_numeric($disposition)) {
            $disposition = Utils_RecordBrowserCommon::get_record(
                Telemarketing_CallCampaigns_Dispositions_RBO_Status::TABLE_NAME,
                $disposition
            );
        }
        $tab = disposition['record_type'];
        $id = disposition['record_id'];
        Utils_AttachmentCommon::add("$tab/$id", 0, Acl::get_user(), $log);
    }

    public static function fix_phone_number($phone, $record)
    {
        if ($phone && strpos($phone, '+') === false && substr(preg_replace('/[^0-9]/', '', $phone), 0, 2) !== '00') {
            if (isset($record['country']) && $record['country']) {
                $calling_code = Utils_CommonDataCommon::get_value('Calling_Codes/' . $record['country']);
                if ($calling_code)
                    $phone = $calling_code . $phone;
            }
        }
        return $phone;
    }

    public static function has_phonecall($disposition, $phone)
    {
        if (count($disposition['phonecall'])) {
            foreach ($disposition['phonecall'] as $phonecall_id) {
                $phonecall = Utils_RecordBrowserCommon::get_record('phonecall', $phonecall_id);
                if ($phonecall['phone'] == $phone) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function process_rule_action(
        $rule,
        $campaign,
        $record,
        $record_type = 'contact',
        $product = false,
        $disposition = false,
        $values = false
    )
    {
        $customer = ($record_type == 'contact' ? 'P:' : 'C:') . $record['id'];
        $phone = 0;
        if (isset($values['phone'])) {
            $phone = $values['phone'] == 'home_phone' ? 3 : $values['phone'] == 'mobile_phone' ? 1 : 2;
        }
        $return = false;
        $details = $rule['details'];
        $me = CRM_ContactsCommon::get_my_record();
        $action = explode(':', $rule['action']);

        $dispositions = Utils_CommonDataCommon::get_array('CallCampaign/Dispositions');
        $disposition_text = $dispositions[$disposition['disposition']];
        switch ($action[0]) {
            case 'Send':
                $mergeOpen = Utils_MergeFieldsCommon::MERGE_OPEN_ID;
                $mergeClose = Utils_MergeFieldsCommon::MERGE_CLOSE_ID;
                switch ($action[1]) {
                    case 'E-mail':
                        $to = explode(';', $details['send_mail_to']);
                        $real_recipients = array();
                        foreach ($to as $recipient) {
                            $r = trim($recipient);
                            $r = str_replace(
                                $mergeOpen . 'email' . $mergeClose,
                                $record['email'],
                                $r
                            );
                            $r = str_replace(
                                $mergeOpen . 'emp_email' . $mergeClose,
                                $me['email'],
                                $r
                            );
                            if ($r && filter_var($r, FILTER_VALIDATE_EMAIL)) {
                                $real_recipients[] = $r;
                            }
                        }
                        //TODO: SEND MAIL
                        break;
                }
                break;
            case 'Add':
                switch ($action[1]) {
                    case 'Phonecall':
                        $defaults = array(
                            'subject' => Telemarketing_CallCampaigns_RulesCommon::parse_rule_merge(
                                $details['add_phonecall_subject'],
                                $campaign,
                                $record,
                                $record_type,
                                $product,
                                $disposition,
                                $values
                            ),
                            'status' => $details['add_phonecall_status'],
                            'permission' => $details['add_phonecall_permission'],
                            'priority' => $details['add_phonecall_priority'],
                            'customer' => $customer,
                            'phone' => $phone,
                            'employees' => Telemarketing_CallCampaigns_RulesCommon::parse_multi_employees_merge(
                                $details['add_phonecall_employees']),
                            'description' => Telemarketing_CallCampaigns_RulesCommon::parse_rule_merge(
                                $details['add_phonecall_description'],
                                $campaign,
                                $record,
                                $record_type,
                                $product,
                                $disposition,
                                $values
                            ),
                            'date_and_time' => Telemarketing_CallCampaigns_RulesCommon::parse_date_merge(
                                array(
                                    'time' => $details['add_phonecall_time']['__date'],
                                    'choice' => $details['add_phonecall_date_choice'],
                                    'specific_date_val' => isset($details['add_phonecall_specific_date_val']) ?
                                        $details['add_phonecall_specific_date_val'] : false,
                                    'dynamic_date_num' => isset($details['add_phonecall_dynamic_date_num']) ?
                                        $details['add_phonecall_dynamic_date_num'] : false,
                                    'dynamic_date_denom' => isset($details['add_phonecall_dynamic_date_num']) ?
                                        $details['add_phonecall_dynamic_date_denom'] : false
                                )
                            ),
                            'email_employees' => isset($details['add_phonecall_email_employees']) ?
                                $details['add_phonecall_email_employees'] : 0,
                            'related' => array(
                                Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME .
                                '/' . $campaign['id']
                            )
                        );
                        $return = Base_BoxCommon::push_module('Utils/RecordBrowser', 'view_entry',
                            array(
                                'add',
                                NULL,
                                $defaults,
                            ), array('phonecall'));
                        break;
                    case 'Task':
                        $defaults = array(
                            'title' => Telemarketing_CallCampaigns_RulesCommon::parse_rule_merge(
                                $details['add_task_title'],
                                $campaign,
                                $record,
                                $record_type,
                                $product,
                                $disposition,
                                $values
                            ),
                            'status' => $details['add_task_status'],
                            'permission' => $details['add_task_permission'],
                            'priority' => $details['add_task_priority'],
                            'longterm' => isset($details['add_task_longterm']) ? $details['add_task_longterm'] : 0,
                            'deadline' => Telemarketing_CallCampaigns_RulesCommon::parse_date_merge(
                                array(
                                    'choice' => $details['add_task_deadline'],
                                    'specific_date_val' => isset($details['add_task_specific_date_val']) ?
                                        $details['add_task_specific_date_val'] : false,
                                    'dynamic_date_num' => isset($details['add_task_dynamic_date_num']) ?
                                        $details['add_task_dynamic_date_num'] : false,
                                    'dynamic_date_denom' => isset($details['add_task_dynamic_date_denom']) ?
                                        $details['add_task_dynamic_date_denom'] : false
                                ), false
                            ),
                            'description' => Telemarketing_CallCampaigns_RulesCommon::parse_rule_merge(
                                $details['add_task_description'],
                                $campaign,
                                $record,
                                $record_type,
                                $product,
                                $disposition,
                                $values
                            ),
                            'employees' => Telemarketing_CallCampaigns_RulesCommon::parse_multi_employees_merge($details['add_task_employees']),
                            'customers' => $customer,
                        );
                        $return = Base_BoxCommon::push_module('Utils/RecordBrowser', 'view_entry',
                            array(
                                'add',
                                NULL,
                                $defaults,
                            ), array('task'));
                        break;
                    case 'Meeting':
                        $defaults = array(
                            'title' => Telemarketing_CallCampaigns_RulesCommon::parse_rule_merge(
                                $details['add_meeting_title'],
                                $campaign,
                                $record,
                                $record_type,
                                $product,
                                $disposition,
                                $values
                            ),
                            'status' => $details['add_meeting_status'],
                            'permission' => $details['add_meeting_permission'],
                            'priority' => $details['add_meeting_priority'],
                            'time' => Base_RegionalSettingsCommon::reg2time(Telemarketing_CallCampaigns_RulesCommon::parse_date_merge(
                                array(
                                    'time' => $details['add_meeting_time']['__date'],
                                    'choice' => $details['add_meeting_date_choice'],
                                    'specific_date_val' => isset($details['add_meeting_specific_date_val']) ?
                                        $details['add_meeting_specific_date_val'] : false,
                                    'dynamic_date_num' => isset($details['add_meeting_dynamic_date_num']) ?
                                        $details['add_meeting_dynamic_date_num'] : false,
                                    'dynamic_date_denom' => isset($details['add_meeting_dynamic_date_denom']) ?
                                        $details['add_meeting_dynamic_date_denom'] : false
                                )
                            ), true, null),
                            'date' => Telemarketing_CallCampaigns_RulesCommon::parse_date_merge(
                                array(
                                    'choice' => $details['add_meeting_date_choice'],
                                    'specific_date_val' => isset($details['add_meeting_specific_date_val']) ?
                                        $details['add_meeting_specific_date_val'] : false,
                                    'dynamic_date_num' => isset($details['add_meeting_dynamic_date_num']) ?
                                        $details['add_meeting_dynamic_date_num'] : false,
                                    'dynamic_date_denom' => isset($details['add_meeting_dynamic_date_denom']) ?
                                        $details['add_meeting_dynamic_date_denom'] : false
                                ), false
                            ),
                            'timeless' => isset($details['add_meeting_timeless']) ? $details['add_meeting_timeless'] : 0,
                            'messenger_on' => $details['add_meeting_alert'],
                            'description' => $details['add_meeting_description'],
                            'employees' => Telemarketing_CallCampaigns_RulesCommon::parse_multi_employees_merge(
                                $details['add_meeting_employees']
                            ),
                            'customers' => $customer
                        );
                        if (!$defaults['timeless']) {
                            $type = $details['add_meeting_time_type'];
                            if ($type == 'duration') {
                                $defaults['duration'] = $details['add_meeting_duration'];
                            } else if ($type == 'end_time') {
                                if (isset($details['add_meeting_end_time']['a'])) {
                                    $defaults['end_time'] = date('Y-m-d H:i:s', strtotime(date('Y-m-d') . ' ' .
                                        $details['add_meeting_end_time']['__date']['h'] . ' ' .
                                        $details['add_meeting_end_time']['__date']['i'] . ' ' .
                                        $details['add_meeting_end_time']['__date']['a']));
                                } else {
                                    $defaults['end_time'] = date('Y-m-d') . ' ' .
                                        $details['add_meeting_end_time']['__date']['h'] . ' ' .
                                        $details['add_meeting_end_time']['__date']['i'] . ' ' .
                                        $details['add_meeting_end_time']['__date']['s'];
                                }
                            }
                        }
                        if ($defaults['messenger_on'] != 'none') {
                            $defaults['messenger_before'] = $details['add_meeting_popup_alert'];
                            $defaults['messenger_message'] = $details['add_meeting_popup_message'];
                        }
                        $return = Base_BoxCommon::push_module('Utils/RecordBrowser', 'view_entry',
                            array(
                                'add',
                                NULL,
                                $defaults,
                            ), array('crm_meeting'));
                        break;
                    case 'SalesOpportunity':
                        if (!ModuleManager::is_installed("Premium/SalesOpportunity") >= 0) {
                            return null;
                        }
                        $defaults = array(
                            'opportunity_name' => Telemarketing_CallCampaigns_RulesCommon::parse_rule_merge(
                                $details['add_opp_name'],
                                $campaign,
                                $record,
                                $record_type,
                                $product,
                                $disposition,
                                $values
                            ),
                            'opportunity_manager' => Telemarketing_CallCampaigns_RulesCommon::parse_employee_merge($details['add_opp_manager']),
                            'type' => $details['add_opp_type'],
                            'probability____' => $details['add_opp_probability'],
                            'employees' => array(Telemarketing_CallCampaigns_RulesCommon::parse_employee_merge('current')),
                            'lead_source' => $details['add_opp_lead_source'],
                            'status' => $details['add_opp_status'],
                            'contract_amount' => (strpos($details['add_opp_contract_amount'], 'product_price') != 0) ?
                                ($product ? $product['price'] : 0) : $details['add_opp_contract_amount'],
                            //TODO::Add new cb for product price,
                            'start_date' => Telemarketing_CallCampaigns_RulesCommon::parse_date_merge(
                                array(
                                    'choice' => $details['add_opp_start_date'],
                                    'specific_date_val' => isset($details['add_opp_start_specific_date_val']) ?
                                        $details['add_opp_start_specific_date_val'] : false,
                                    'dynamic_date_num' => isset($details['add_opp_start_dynamic_date_num']) ?
                                        $details['add_opp_start_dynamic_date_num'] : false,
                                    'dynamic_date_denom' => isset($details['add_opp_start_dynamic_date_denom']) ?
                                        $details['add_opp_start_dynamic_date_denom'] : false
                                ), false
                            ),
                            'follow_up_date' => Telemarketing_CallCampaigns_RulesCommon::parse_date_merge(
                                array(
                                    'choice' => $details['add_opp_followup_date'],
                                    'specific_date_val' => isset($details['add_opp_followup_specific_date_val']) ?
                                        $details['add_opp_followup_specific_date_val'] : false,
                                    'dynamic_date_num' => isset($details['add_opp_followup_dynamic_date_num']) ?
                                        $details['add_opp_followup_dynamic_date_num'] : false,
                                    'dynamic_date_denom' => isset($details['add_opp_followup_dynamic_date_denom']) ?
                                        $details['add_opp_followup_dynamic_date_denom'] : false
                                ), false
                            ),
                            'description' => Telemarketing_CallCampaigns_RulesCommon::parse_rule_merge(
                                $details['add_opp_description'],
                                $campaign,
                                $record,
                                $record_type,
                                $product,
                                $disposition,
                                $values
                            ),
                            'customers' => $customer,
                            'quantity' => 1
                        );
                        if ($product) {
                            $defaults['product'] = $campaign['product'];
                        }
                        $return = Base_BoxCommon::push_module('Utils/RecordBrowser', 'view_entry',
                            array(
                                'add',
                                NULL,
                                $defaults,
                            ), array('premium_salesopportunity'));
                        break;
                    case 'Contact':
                        $return = Base_BoxCommon::push_module('Utils/RecordBrowser', 'view_entry',
                            array(
                                'add',
                                NULL,
                                array(),
                            ), array('contact'));
                        break;
                }
                break;
            case 'AutoAdd':
                switch ($action[1]) {
                    case 'Phonecall':
                        $phonecall = array(
                            'subject' => Telemarketing_CallCampaigns_RulesCommon::parse_rule_merge(
                                $details['add_phonecall_subject'],
                                $campaign,
                                $record,
                                $record_type,
                                $product,
                                $disposition,
                                $values
                            ),
                            'permission' => $details['add_phonecall_permission'],
                            'status' => $details['add_phonecall_status'],
                            'priority' => $details['add_phonecall_priority'],
                            'customer' => $customer,
                            'phone' => $phone,
                            'employees' => Telemarketing_CallCampaigns_RulesCommon::parse_multi_employees_merge($details['add_phonecall_employees']),
                            'description' => Telemarketing_CallCampaigns_RulesCommon::parse_rule_merge
                            (
                                $details['add_phonecall_description'],
                                $campaign,
                                $record,
                                $record_type,
                                $product,
                                $disposition,
                                $values
                            ),
                            'date_and_time' => Telemarketing_CallCampaigns_RulesCommon::parse_date_merge(
                                array(
                                    'time' => $details['add_phonecall_time']['__date'],
                                    'choice' => $details['add_phonecall_date_choice'],
                                    'specific_date_val' => isset($details['add_phonecall_specific_date_val']) ?
                                        $details['add_phonecall_specific_date_val'] : false,
                                    'dynamic_date_num' => isset($details['add_phonecall_dynamic_date_num']) ?
                                        $details['add_phonecall_dynamic_date_num'] : false,
                                    'dynamic_date_denom' => isset($details['add_phonecall_dynamic_date_num']) ?
                                        $details['add_phonecall_dynamic_date_denom'] : false
                                )
                            ),
                            'email_employees' =>
                                isset($details['add_phonecall_email_employees']) ?
                                    $details['add_phonecall_email_employees'] : 0,
                            'related' => array(
                                Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME . '/' . $campaign['id']
                            )
                        );
                        $return = Utils_RecordBrowserCommon::new_record('phonecall', $phonecall);
                        break;
                    case 'Task':
                        $task = array(
                            'title' => Telemarketing_CallCampaigns_RulesCommon::parse_rule_merge(
                                $details['add_task_title'],
                                $campaign,
                                $record,
                                $record_type,
                                $product,
                                $disposition,
                                $values
                            ),
                            'status' => $details['add_task_status'],
                            'permission' => $details['add_task_permission'],
                            'priority' => $details['add_task_priority'],
                            'longterm' => isset($details['add_task_longterm']) ? $details['add_task_longterm'] : 0,
                            'deadline' => Telemarketing_CallCampaigns_RulesCommon::parse_date_merge(
                                array(
                                    'choice' => $details['add_task_deadline'],
                                    'specific_date_val' => isset($details['add_task_specific_date_val']) ?
                                        $details['add_task_specific_date_val'] : false,
                                    'dynamic_date_num' => isset($details['add_task_dynamic_date_num']) ?
                                        $details['add_task_dynamic_date_num'] : false,
                                    'dynamic_date_denom' => isset($details['add_task_dynamic_date_denom']) ?
                                        $details['add_task_dynamic_date_denom'] : false
                                ), false
                            ),
                            'description' => Telemarketing_CallCampaigns_RulesCommon::parse_rule_merge(
                                $details['add_task_description'],
                                $campaign,
                                $record,
                                $record_type,
                                $product,
                                $disposition,
                                $values
                            ),
                            'employees' => Telemarketing_CallCampaigns_RulesCommon::parse_multi_employees_merge($details['add_task_employees']),
                            'customers' => $customer,
                        );
                        $return = Utils_RecordBrowserCommon::new_record('task', $task);
                        break;
                    case 'Meeting':
                        $defaults = array(
                            'title' => Telemarketing_CallCampaigns_RulesCommon::parse_rule_merge(
                                $details['add_meeting_title'],
                                $campaign,
                                $record,
                                $record_type,
                                $product,
                                $disposition,
                                $values
                            ),
                            'status' => $details['add_meeting_status'],
                            'permission' => $details['add_meeting_permission'],
                            'priority' => $details['add_meeting_priority'],
                            'time' => Base_RegionalSettingsCommon::reg2time(Telemarketing_CallCampaigns_RulesCommon::parse_date_merge(
                                array(
                                    'time' => $details['add_meeting_time']['__date'],
                                    'choice' => $details['add_meeting_date_choice'],
                                    'specific_date_val' => isset($details['add_meeting_specific_date_val']) ?
                                        $details['add_meeting_specific_date_val'] : false,
                                    'dynamic_date_num' => isset($details['add_meeting_dynamic_date_num']) ?
                                        $details['add_meeting_dynamic_date_num'] : false,
                                    'dynamic_date_denom' => isset($details['add_meeting_dynamic_date_denom']) ?
                                        $details['add_meeting_dynamic_date_denom'] : false
                                )
                            ), true, null),
                            'date' => Telemarketing_CallCampaigns_RulesCommon::parse_date_merge(
                                array(
                                    'choice' => $details['add_meeting_date_choice'],
                                    'specific_date_val' => isset($details['add_meeting_specific_date_val']) ?
                                        $details['add_meeting_specific_date_val'] : false,
                                    'dynamic_date_num' => isset($details['add_meeting_dynamic_date_num']) ?
                                        $details['add_meeting_dynamic_date_num'] : false,
                                    'dynamic_date_denom' => isset($details['add_meeting_dynamic_date_denom']) ?
                                        $details['add_meeting_dynamic_date_denom'] : false
                                ), false
                            ),
                            'timeless' => isset($details['add_meeting_timeless']) ? $details['add_meeting_timeless'] : 0,
                            'messenger_on' => $details['add_meeting_alert'],
                            'description' => $details['add_meeting_description'],
                            'employees' => Telemarketing_CallCampaigns_RulesCommon::parse_multi_employees_merge(
                                $details['add_meeting_employees']
                            ),
                            'customers' => $customer
                        );
                        if (!$defaults['timeless']) {
                            $type = $details['add_meeting_time_type'];
                            if ($type == 'duration') {
                                $defaults['duration'] = $details['add_meeting_duration'];
                            } else if ($type == 'end_time') {
                                if (isset($details['add_meeting_end_time']['a'])) {
                                    $defaults['end_time'] = date('Y-m-d H:i:s', strtotime(date('Y-m-d') . ' ' .
                                        $details['add_meeting_end_time']['__date']['h'] . ' ' .
                                        $details['add_meeting_end_time']['__date']['i'] . ' ' .
                                        $details['add_meeting_end_time']['__date']['a']));
                                } else {
                                    $defaults['end_time'] = date('Y-m-d') . ' ' .
                                        $details['add_meeting_end_time']['__date']['h'] . ' ' .
                                        $details['add_meeting_end_time']['__date']['i'] . ' ' .
                                        $details['add_meeting_end_time']['__date']['s'];
                                }
                            }
                        }
                        if ($defaults['messenger_on'] != 'none') {
                            $defaults['messenger_before'] = $details['add_meeting_popup_alert'];
                            $defaults['messenger_message'] = $details['add_meeting_popup_message'];
                        }
                        $return = Utils_RecordBrowserCommon::new_record('crm_meeting', $defaults);
                        break;
                    case 'SalesOpportunity':
                        if (!ModuleManager::is_installed("Premium/SalesOpportunity") >= 0) {
                            return null;
                        }
                        $defaults = array(
                            'opportunity_name' => Telemarketing_CallCampaigns_RulesCommon::parse_rule_merge(
                                $details['add_opp_name'],
                                $campaign,
                                $record,
                                $record_type,
                                $product,
                                $disposition,
                                $values
                            ),
                            'opportunity_manager' => Telemarketing_CallCampaigns_RulesCommon::parse_employee_merge($details['add_opp_manager']),
                            'type' => $details['add_opp_type'],
                            'probability____' => $details['add_opp_probability'],
                            'employees' => array(Telemarketing_CallCampaigns_RulesCommon::parse_employee_merge('current')),
                            'lead_source' => $details['add_opp_lead_source'],
                            'status' => $details['add_opp_status'],
                            'contract_amount' => (strpos($details['add_opp_contract_amount'], 'product_price') != 0) ?
                                ($product ? $product['price'] : 0) : $details['add_opp_contract_amount'],
                            //TODO::Add new cb for product price,
                            'start_date' => Telemarketing_CallCampaigns_RulesCommon::parse_date_merge(
                                array(
                                    'choice' => $details['add_opp_start_date'],
                                    'specific_date_val' => isset($details['add_opp_start_specific_date_val']) ?
                                        $details['add_opp_start_specific_date_val'] : false,
                                    'dynamic_date_num' => isset($details['add_opp_start_dynamic_date_num']) ?
                                        $details['add_opp_start_dynamic_date_num'] : false,
                                    'dynamic_date_denom' => isset($details['add_opp_start_dynamic_date_denom']) ?
                                        $details['add_opp_start_dynamic_date_denom'] : false
                                ), false
                            ),
                            'follow_up_date' => Telemarketing_CallCampaigns_RulesCommon::parse_date_merge(
                                array(
                                    'choice' => $details['add_opp_followup_date'],
                                    'specific_date_val' => isset($details['add_opp_followup_specific_date_val']) ?
                                        $details['add_opp_followup_specific_date_val'] : false,
                                    'dynamic_date_num' => isset($details['add_opp_followup_dynamic_date_num']) ?
                                        $details['add_opp_followup_dynamic_date_num'] : false,
                                    'dynamic_date_denom' => isset($details['add_opp_followup_dynamic_date_denom']) ?
                                        $details['add_opp_followup_dynamic_date_denom'] : false
                                ), false
                            ),
                            'description' => Telemarketing_CallCampaigns_RulesCommon::parse_rule_merge(
                                $details['add_opp_description'],
                                $campaign,
                                $record,
                                $record_type,
                                $product,
                                $disposition,
                                $values
                            ),
                            'customers' => $customer,
                            'quantity' => 1
                        );
                        if ($product) {
                            $defaults['product'] = $campaign['product'];
                        }
                        $return = Utils_RecordBrowserCommon::new_record('premium_salesopportunity', $defaults);
                        break;
                    case 'RecordNote':
                        $return = Utils_AttachmentCommon::add(
                            $record_type . '/' . $record['id'],
                            0, Acl::get_user(),
                            Telemarketing_CallCampaigns_RulesCommon::parse_rule_merge(
                                $details['add_record_note_ck'],
                                $campaign,
                                $record,
                                $record_type,
                                $product,
                                $disposition,
                                $values
                            )
                        );
                        break;
                    case 'ToList':
                        if (ModuleManager::is_installed("Premium/ListManager") >= 0) {
                            $return = Premium_ListManagerCommon::add_record_to_list(
                                $details['add_to_list_list'],
                                $record['id'],
                                $record_type
                            );
                        }
                        break;
                }
                break;
            case 'Remove':
                switch ($action[1]) {
                    case 'RecordFromAllCallCampaigns':
                        $blacklist = new Telemarketing_CallCampaigns_Dispositions_RBO_Blacklists();
                        //TODO: Add reason in rules UI
                        $reason = "Flagged as $disposition_text in call campaign " . $campaign['name'];
                        $return = $blacklist->new_record(array(
                            'record_id' => $record['id'],
                            'record_type' => $record_type,
                            'blacklisted_by' => $me['id'],
                            'reason' => $reason,
                            'timestamp' => date('Y-m-d H:i:s')
                        ));
                        break;
                    case 'RecordPhoneNumber':
                        $record[$values['phone']] = '';
                        Utils_RecordBrowserCommon::update_record($record_type, $record['id'], $record);
                        break;
                }
                break;
            case 'SetRecordField':
                $record['status'] = $details['set_record_field_status'];
                Utils_RecordBrowserCommon::update_record($record_type, $record['id'], $record);
                break;
            case 'Enqueue':
                $disposition['skip_date'] = date("Y-m-d H:i:s");
                Utils_RecordBrowserCommon::update_record(
                    Telemarketing_CallCampaigns_Dispositions_RBO_Status::TABLE_NAME,
                    $disposition['id'],
                    $disposition
                );
                break;
        }
        return $return;
    }
}
