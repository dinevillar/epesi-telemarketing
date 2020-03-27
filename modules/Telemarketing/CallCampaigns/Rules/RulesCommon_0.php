<?php


class Telemarketing_CallCampaigns_RulesCommon extends ModuleCommon
{

    public static function rules_addon_label($campaign)
    {
        if (
            $campaign['created_on'] === Acl::get_user() ||
            Base_AclCommon::check_permission(Telemarketing_CallCampaignsInstall::manage_permission)
        ) {
            return array('label' => __('Rules'));
        }
        return array('show' => false);
    }


    public static function submit_call_campaign($record, $mode)
    {
        if ($mode == 'added') {
            self::update_rules($record['id']);
        }
    }

    public static function get_call_campaign_merge_fields($disps = true)
    {
        $call_campaign_merge_fields = Utils_MergeFieldsCommon::get_fields(
            Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME,
            array(
                'status',
                'permission',
                'list_type',
                'lead_list',
                'product'
            ),
            'call_campaign'
        );
        if ($disps) {
            $call_campaign_merge_fields['call_campaign_disposition'] = __("Disposition");
            $call_campaign_merge_fields['call_campaign_called_phone'] = __("Called Phone Number");
            $call_campaign_merge_fields['call_campaign_called_phone_type'] = __("Called Phone Number Type");
            $call_campaign_merge_fields['call_back_date_time'] = __("Callback Date and Time");
            $call_campaign_merge_fields['call_campaign_talk_time'] = __("Talk Time");
        }
        return $call_campaign_merge_fields;
    }

    public static function get_product_merge_fields()
    {
        $product_merge_fields = Utils_MergeFieldsCommon::get_fields(
            Telemarketing_Products_RBO_Products::TABLE_NAME,
            array(),
            'product'
        );
        return $product_merge_fields;
    }

    public static function parse_rule_merge($text, $campaign, $record, $record_type = 'contact', $product = false, $disposition = false, $values = false, $replace_all = true)
    {
        $text = Utils_MergeFieldsCommon::parse_fields(
            Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME,
            $campaign,
            $text,
            'call_campaign'
        );
        $text = Utils_MergeFieldsCommon::parse_fields(
            $record_type,
            $record,
            $text
        );
        $text = Utils_MergeFieldsCommon::parse_fields(
            'contact',
            CRM_ContactsCommon::get_my_record(),
            $text,
            'emp'
        );
        if ($product) {
            $text = Utils_MergeFieldsCommon::parse_fields(
                Telemarketing_Products_RBO_Products::TABLE_NAME,
                $product,
                $text,
                'product'
            );
        }
        if ($disposition) {
            if (isset($disposition['disposition'])) {
                $dispositions = Utils_CommonDataCommon::get_array('CallCampaign/Dispositions');
                $text = str_replace(Utils_MergeFieldsCommon::MERGE_OPEN_ID .
                    'call_campaign_disposition' . Utils_MergeFieldsCommon::MERGE_CLOSE_ID,
                    $dispositions[$disposition['disposition']], $text);
            }
            if (isset($disposition['talk_time'])) {
                $text = str_replace(Utils_MergeFieldsCommon::MERGE_OPEN_ID .
                    'call_campaign_talk_time' . Utils_MergeFieldsCommon::MERGE_CLOSE_ID,
                    $disposition['talk_time'], $text);
            }
            if (isset($disposition['call_back_time'])) {
                $cbt = Base_RegionalSettingsCommon::time2reg($disposition['call_back_time']) . ' ' .
                    Base_User_SettingsCommon::get('Base_RegionalSettings', 'tz');
                $text = str_replace(Utils_MergeFieldsCommon::MERGE_OPEN_ID .
                    'call_back_date_time' . Utils_MergeFieldsCommon::MERGE_CLOSE_ID,
                    $cbt, $text);
            }
        }
        if ($values) {
            if (isset($values['phone'])) {
                $text = str_replace(Utils_MergeFieldsCommon::MERGE_OPEN_ID .
                    'call_campaign_called_phone' . Utils_MergeFieldsCommon::MERGE_CLOSE_ID,
                    $record[$values['phone']], $text);

                $text = str_replace(Utils_MergeFieldsCommon::MERGE_OPEN_ID .
                    'call_campaign_called_phone_type' . Utils_MergeFieldsCommon::MERGE_CLOSE_ID,
                    ucwords(str_replace('_', ' ', $values['phone'])), $text);
            }
        }

        if ($replace_all) {
            $text = preg_replace('/\\' . Utils_MergeFieldsCommon::MERGE_OPEN_ID . '.*?' . '\\' . Utils_MergeFieldsCommon::MERGE_CLOSE_ID . '/', '', $text);
        }
        return $text;
    }

    public static function parse_employee_merge($val)
    {
        if ($val == 'current') {
            $me = CRM_ContactsCommon::get_my_record();
            return $me['id'];
        }
        if (is_numeric($val)) {
            return $val;
        }
        return false;
    }

    public static function parse_multi_employees_merge($val)
    {
        $ar_val = explode("__SEP__", $val);
        $emps = array();
        $me = CRM_ContactsCommon::get_my_record();
        foreach ($ar_val as $k => $v) {
            if ($v == 'current') {
                array_push($emps, $me['id']);
            } else if (trim($v)) {
                array_push($emps, $v);
            }
        }
        return array_unique($emps);
    }

    public static function parse_date_merge($date_args, $incl_time = true, $reference = false)
    {
        $parse_date = false;
        $format = 'Y-m-d';
        if ($incl_time) {
            $format .= ' ' . 'H:i:s';
        }
        $time_24 = false;
        if ($date_args['time']) {
            $time = $date_args['time'];
            $i = $time['i'];
            if (strlen($i) == 1) {
                $i = '0' . $i;
            }
            $h = $time['h'];
            if (strlen($h) == 1) {
                $h = '0' . $h;
            }
            if (isset($time['a'])) {
                $time_24 = date('H:i', strtotime(
                    $h . ':' . $i . ' ' . strtoupper($time['a'])
                ));
            } else {
                $time_24 = $h . ':' . $i;
            }
            $time_24 .= ':00';
        }
        $dyn_add = array(
            0 => 'days',
            1 => 'weeks',
            2 => 'months'
        );
        switch ($date_args['choice']) {
            case 'current_date':
                if ($time_24) {
                    $parse_date = date('Y-m-d') . ' ' . $time_24;
                } else {
                    $parse_date = date($format);
                }
                break;
            case 'specific_date':
                $spec_date = $date_args['specific_date_val']['Y'] . '-' . $date_args['specific_date_val']['m'] . '-' . $date_args['specific_date_val']['d'];
                if ($incl_time && $time_24) {
                    $spec_date .= ' ' . $time_24;
                }
                $parse_date = date($format, strtotime($spec_date));
                break;
            case 'dynamic_date':
                if (!$reference) {
                    $reference = time();
                }
                $dynamic_time = strtotime('+' . $date_args['dynamic_date_num'] . ' ' . $dyn_add[$date_args['dynamic_date_denom']], $reference);
                $dynamic_date = date('Y-m-d', $dynamic_time);
                if ($incl_time && $time_24) {
                    $dynamic_date .= ' ' . $time_24;
                }
                $parse_date = date($format, strtotime($dynamic_date));
                break;
        }
        return $parse_date;
    }

    public static function get_rules($campaign_id, $index = false)
    {
        $rules_table = Telemarketing_CallCampaigns_RulesInstall::RULES_TABLE;
        if ($index === false) {
            $arules = DB::GetAll("SELECT * FROM {$rules_table} WHERE call_campaign_id={$campaign_id}");
            if (empty($arules)) {
                $rules = Variable::get('telemarketing_default_rules');
                return $rules;
            } else {
                $rules = array();
                foreach ($arules as $arule) {
                    $arule['details'] = unserialize(base64_decode($arule['details']));
                    $rules[$arule['ind']] = $arule;
                }
                return $rules;
            }
        } else {
            $arule = DB::GetRow("SELECT * FROM {$rules_table} WHERE call_campaign_id={$campaign_id} AND ind={$index}");
            if (empty($arule)) {
                $rules = Variable::get('telemarketing_default_rules');
                return $rules[$index];
            } else {
                $arule['details'] = unserialize(base64_decode($arule['details']));
                return $arule;
            }
        }
    }

    public static function update_rules($campaign_id, $index = false, $rule = false)
    {
        $rules_table = Telemarketing_CallCampaigns_RulesInstall::RULES_TABLE;
        if (!$index && !$rule) {
            $def_rules = Variable::get('telemarketing_default_rules');
            foreach ($def_rules as $i => $def_rule) {
                self::update_rules($campaign_id, $i, $def_rule);
            }
            return;
        } else if ($rule === false && $index) {
            $def_rules = Variable::get('telemarketing_default_rules');
            foreach ($def_rules as $i => $def_rule) {
                if ($index == $i) {
                    self::update_rules($campaign_id, $i, $def_rule);
                    break;
                }
            }
            return;
        } else if ($rule && $index === false) {
            $max = DB::GetOne('SELECT MAX(`ind`) FROM `' . $rules_table . '` WHERE `call_campaign_id` = ' . $campaign_id);
            if ($max !== null) {
                $next = $max + 1;
                self::update_rules($campaign_id, $next, $rule);
            } else {
                self::update_rules($campaign_id, 0, $rule);
            }
            return;
        }
        $sql = 'INSERT INTO `' . $rules_table . '` ' .
            '(`call_campaign_id`, `ind`, `type`, `condition`, `action`, `details`) ' .
            'VALUES(%1$d, %2$u, \'%3$s\',\'%4$s\',\'%5$s\',\'%6$s\') ON DUPLICATE KEY UPDATE ' .
            '`type`=\'%3$s\', `condition`=\'%4$s\', `action`=\'%5$s\', `details`=\'%6$s\'';
        $details = '';
        if (isset($rule['details'])) {
            $details = base64_encode(serialize($rule['details']));
        }
        $formatted_sql = sprintf($sql,
            $campaign_id,
            $index,
            $rule['type'],
            $rule['condition'],
            $rule['action'],
            $details
        );
        DB::Execute($formatted_sql);
    }

    public static function delete_rule($campaign_id, $index)
    {
        $rules_table = Telemarketing_CallCampaigns_RulesInstall::RULES_TABLE;
        $sql = "DELETE FROM {$rules_table} WHERE call_campaign_id={$campaign_id} AND ind={$index}";
        DB::Execute($sql);
    }

    public static function match_rules($campaign, $type = false, $action = false, $condition = false)
    {
        $rules = self::get_rules($campaign['id']);
        $matched_rules = array();
        foreach ($rules as $rule) {
            $add = false;
            if ($type && $action && $condition) {
                $add = $rule['type'] == $type &&
                    starts_with($rule['action'], $action) &&
                    starts_with($rule['condition'], $condition);
            } else if (!$type && $action && $condition) {
                $add = starts_with($rule['action'], $action) &&
                    starts_with($rule['condition'], $condition);
            } else if ($type && !$action && $condition) {
                $add = $rule['type'] == $type &&
                    starts_with($rule['condition'], $condition);
            } else if ($type && $action && !$condition) {
                $add = $rule['type'] == $type &&
                    starts_with($rule['action'], $action);
            } else if (!$type && !$action && $condition) {
                $add = starts_with($rule['condition'], $condition);
            } else if (!$type && $action && !$condition) {
                $add = starts_with($rule['action'], $action);
            } else if ($type && !$action && !$condition) {
                $add = $rule['type'] == $type;
            }
            if ($add) {
                $matched_rules[] = $rule;
            }
        }
        return $matched_rules;
    }

    public static function process_rule_action($rule, $campaign, $record, $record_type = 'contact', $product = false, $disposition = false, $values = false)
    {
        $customer = ($record_type == 'contact' ? 'P:' : 'C:') . $record['id'];
        if (isset($values['phone'])) {
            $phone = $values['phone'] == 'home_phone' ? 3 : $values['phone'] == 'mobile_phone' ? 1 : 2;
        }
        $return = false;
        $details = $rule['details'];
        $me = CRM_ContactsCommon::get_my_record();
        switch ($rule['action']) {
            //Send
            case 0:
                switch ($rule['action_send']) {
                    //Email
                    case 0:
                        $to = explode(';', $details['send_mail_to']);
                        $real_recipients = array();
                        foreach ($to as $recipient) {
                            $r = trim($recipient);
                            $r = str_replace(Utils_MergeFieldsCommon::MERGE_OPEN_ID . 'email' . Utils_MergeFieldsCommon::MERGE_CLOSE_ID,
                                $record['email'], $r);
                            $r = str_replace(Utils_MergeFieldsCommon::MERGE_OPEN_ID . 'emp_email' . Utils_MergeFieldsCommon::MERGE_CLOSE_ID,
                                $me['email'], $r);
                            if ($r && filter_var($r, FILTER_VALIDATE_EMAIL)) {
                                $real_recipients[] = $r;
                            }
                        }
                        //TODO: Send Email
                        break;
                }
                break;
            //Add
            case 1:
                switch ($rule['action_add']) {
                    //Phonecall
                    case 0:
                        $defaults = array(
                            'subject' => self::parse_rule_merge(
                                $details['add_phonecall_subject'], $campaign, $record, $record_type, $product, $disposition
                            ),
                            'status' => $details['add_phonecall_status'],
                            'permission' => $details['add_phonecall_permission'],
                            'priority' => $details['add_phonecall_priority'],
                            'customer' => $customer,
                            'phone' => $phone,
                            'employees' => self::parse_multi_employees_merge(
                                $details['add_phonecall_employees']),
                            'description' => self::parse_rule_merge(
                                $details['add_phonecall_description'], $campaign, $record, $record_type, $product, $disposition
                            ),
                            'email_employees' => isset($details['add_phonecall_email_employees']) ? $details['add_phonecall_email_employees'] : 0,
                            'related' => array(Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME . '/' . $campaign['id'])
                        );
                        if ($disposition['disposition'] == 'CL' && $disposition['call_back_time']) {
                            $defaults['date_and_time'] = $disposition['call_back_time'];
                        } else {

                            $defaults['date_and_time'] = self::parse_date_merge(
                                array(
                                    'time' => $details['add_phonecall_time']['__date'],
                                    'choice' => $details['add_phonecall_date_choice'],
                                    'specific_date_val' => isset($details['add_phonecall_specific_date_val']) ? $details['add_phonecall_specific_date_val'] : false,
                                    'dynamic_date_num' => isset($details['add_phonecall_dynamic_date_num']) ? $details['add_phonecall_dynamic_date_num'] : false,
                                    'dynamic_date_denom' => isset($details['add_phonecall_dynamic_date_num']) ? $details['add_phonecall_dynamic_date_denom'] : false
                                )
                            );
                        }
                        $return = Base_BoxCommon::push_module('Utils/RecordBrowser', 'view_entry',
                            array(
                                'add',
                                NULL,
                                $defaults,
                            ), array('phonecall'));
                        break;
                    //Task
                    case 1:
                        $defaults = array(
                            'title' => self::parse_rule_merge(
                                $details['add_task_title'], $campaign, $record, $record_type, $product, $disposition
                            ),
                            'status' => $details['add_task_status'],
                            'permission' => $details['add_task_permission'],
                            'priority' => $details['add_task_priority'],
                            'longterm' => isset($details['add_task_longterm']) ? $details['add_task_longterm'] : 0,
                            'deadline' => self::parse_date_merge(
                                array(
                                    'choice' => $details['add_task_deadline'],
                                    'specific_date_val' => isset($details['add_task_specific_date_val']) ? $details['add_task_specific_date_val'] : false,
                                    'dynamic_date_num' => isset($details['add_task_dynamic_date_num']) ? $details['add_task_dynamic_date_num'] : false,
                                    'dynamic_date_denom' => isset($details['add_task_dynamic_date_denom']) ? $details['add_task_dynamic_date_denom'] : false
                                ), false
                            ),
                            'description' => self::parse_rule_merge(
                                $details['add_task_description'], $campaign, $record, $record_type, $product, $disposition
                            ),
                            'employees' => self::parse_multi_employees_merge($details['add_task_employees']),
                            'customers' => $customer,
                        );
                        $return = Base_BoxCommon::push_module('Utils/RecordBrowser', 'view_entry',
                            array(
                                'add',
                                NULL,
                                $defaults,
                            ), array('task'));
                        break;
                    //Meeting
                    case 2:
                        $defaults = array(
                            'title' => self::parse_rule_merge(
                                $details['add_meeting_title'], $campaign, $record, $record_type, $product, $disposition
                            ),
                            'status' => $details['add_meeting_status'],
                            'permission' => $details['add_meeting_permission'],
                            'priority' => $details['add_meeting_priority'],
                            'time' => Base_RegionalSettingsCommon::reg2time(self::parse_date_merge(
                                array(
                                    'time' => $details['add_meeting_time']['__date'],
                                    'choice' => $details['add_meeting_date_choice'],
                                    'specific_date_val' => isset($details['add_meeting_specific_date_val']) ? $details['add_meeting_specific_date_val'] : false,
                                    'dynamic_date_num' => isset($details['add_meeting_dynamic_date_num']) ? $details['add_meeting_dynamic_date_num'] : false,
                                    'dynamic_date_denom' => isset($details['add_meeting_dynamic_date_denom']) ? $details['add_meeting_dynamic_date_denom'] : false
                                )
                            ), true, true, true, false),
                            'date' => self::parse_date_merge(
                                array(
                                    'choice' => $details['add_meeting_date_choice'],
                                    'specific_date_val' => isset($details['add_meeting_specific_date_val']) ? $details['add_meeting_specific_date_val'] : false,
                                    'dynamic_date_num' => isset($details['add_meeting_dynamic_date_num']) ? $details['add_meeting_dynamic_date_num'] : false,
                                    'dynamic_date_denom' => isset($details['add_meeting_dynamic_date_denom']) ? $details['add_meeting_dynamic_date_denom'] : false
                                ), false
                            ),
                            'timeless' => isset($details['add_meeting_timeless']) ? $details['add_meeting_timeless'] : 0,
                            'messenger_on' => $details['add_meeting_alert'],
                            'description' => $details['add_meeting_description'],
                            'employees' => self::parse_multi_employees_merge(
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
                                    $defaults['end_time'] = date('Y-m-d H:i:s', strtotime(date('Y-m-d') . ' ' . $details['add_meeting_end_time']['__date']['h'] . ' ' . $details['add_meeting_end_time']['__date']['i'] . ' ' . $details['add_meeting_end_time']['__date']['a']));
                                } else {
                                    $defaults['end_time'] = date('Y-m-d') . ' ' . $details['add_meeting_end_time']['__date']['h'] . ' ' . $details['add_meeting_end_time']['__date']['i'] . ' ' . $details['add_meeting_end_time']['__date']['s'];
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
                    //Sales Opp
                    case 3:
                        $defaults = array(
                            'opportunity_name' => self::parse_rule_merge(
                                $details['add_opp_name'], $campaign, $record, $record_type, $product, $disposition
                            ),
                            'opportunity_manager' => self::parse_employee_merge($details['add_opp_manager']),
                            'type' => $details['add_opp_type'],
                            'probability____' => $details['add_opp_probability'],
                            'employees' => array(self::parse_employee_merge('current')),
                            'lead_source' => $details['add_opp_lead_source'],
                            'status' => $details['add_opp_status'],
                            'contract_amount' => (strpos($details['add_opp_contract_amount'], 'product_price') != 0) ? ($product ? $product['price'] : 0) : $details['add_opp_contract_amount'], //TODO::Add new cb for product price,
                            'start_date' => self::parse_date_merge(
                                array(
                                    'choice' => $details['add_opp_start_date'],
                                    'specific_date_val' => isset($details['add_opp_start_specific_date_val']) ? $details['add_opp_start_specific_date_val'] : false,
                                    'dynamic_date_num' => isset($details['add_opp_start_dynamic_date_num']) ? $details['add_opp_start_dynamic_date_num'] : false,
                                    'dynamic_date_denom' => isset($details['add_opp_start_dynamic_date_denom']) ? $details['add_opp_start_dynamic_date_denom'] : false
                                ), false
                            ),
                            'follow_up_date' => self::parse_date_merge(
                                array(
                                    'choice' => $details['add_opp_followup_date'],
                                    'specific_date_val' => isset($details['add_opp_followup_specific_date_val']) ? $details['add_opp_followup_specific_date_val'] : false,
                                    'dynamic_date_num' => isset($details['add_opp_followup_dynamic_date_num']) ? $details['add_opp_followup_dynamic_date_num'] : false,
                                    'dynamic_date_denom' => isset($details['add_opp_followup_dynamic_date_denom']) ? $details['add_opp_followup_dynamic_date_denom'] : false
                                ), false
                            ),
                            'description' => self::parse_rule_merge(
                                $details['add_opp_description'], $campaign, $record, $record_type, $product, $disposition
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
                }
                break;
            //Auto Add
            case 2:
                switch ($rule['action_auto_add']) {
                    //Phonecall
                    case 0:
                        $phonecall = array(
                            'subject' => self::parse_rule_merge(
                                $details['add_phonecall_subject'], $campaign, $record, $record_type, $product, $disposition
                            ),
                            'permission' => $details['add_phonecall_permission'],
                            'status' => $details['add_phonecall_status'],
                            'priority' => $details['add_phonecall_priority'],
                            'customer' => $customer,
                            'phone' => $phone,
                            'employees' => self::parse_multi_employees_merge(
                                $details['add_phonecall_employees']),
                            'description' => self::parse_rule_merge(
                                $details['add_phonecall_description'], $campaign, $record, $record_type, $product, $disposition
                            ),
                            'email_employees' => isset($details['add_phonecall_email_employees']) ? $details['add_phonecall_email_employees'] : 0,
                            'related' => array(Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME . '/' . $campaign['id'])
                        );
                        if ($disposition['disposition'] == 'CL' && $disposition['call_back_time']) {
                            $phonecall['date_and_time'] = $disposition['call_back_time'];
                        } else {
                            $phonecall['date_and_time'] = self::parse_date_merge(
                                array(
                                    'time' => $details['add_phonecall_time']['__date'],
                                    'choice' => $details['add_phonecall_date_choice'],
                                    'specific_date_val' => isset($details['add_phonecall_specific_date_val']) ? $details['add_phonecall_specific_date_val'] : false,
                                    'dynamic_date_num' => isset($details['add_phonecall_dynamic_date_num']) ? $details['add_phonecall_dynamic_date_num'] : false,
                                    'dynamic_date_denom' => isset($details['add_phonecall_dynamic_date_num']) ? $details['add_phonecall_dynamic_date_denom'] : false
                                )
                            );
                        }
                        $return = Utils_RecordBrowserCommon::new_record('phonecall', $phonecall);
                        break;
                    //Task
                    case 1:
                        $task = array(
                            'title' => self::parse_rule_merge(
                                $details['add_task_title'], $campaign, $record, $record_type, $product, $disposition
                            ),
                            'status' => $details['add_task_status'],
                            'permission' => $details['add_task_permission'],
                            'priority' => $details['add_task_priority'],
                            'longterm' => isset($details['add_task_longterm']) ? $details['add_task_longterm'] : 0,
                            'deadline' => self::parse_date_merge(
                                array(
                                    'choice' => $details['add_task_deadline'],
                                    'specific_date_val' => isset($details['add_task_specific_date_val']) ? $details['add_task_specific_date_val'] : false,
                                    'dynamic_date_num' => isset($details['add_task_dynamic_date_num']) ? $details['add_task_dynamic_date_num'] : false,
                                    'dynamic_date_denom' => isset($details['add_task_dynamic_date_denom']) ? $details['add_task_dynamic_date_denom'] : false
                                ), false
                            ),
                            'description' => self::parse_rule_merge(
                                $details['add_task_description'], $campaign, $record, $record_type, $product, $disposition
                            ),
                            'employees' => self::parse_multi_employees_merge($details['add_task_employees']),
                            'customers' => $customer,
                        );
                        $return = Utils_RecordBrowserCommon::new_record('task', $task);
                        break;
                    //Meeting
                    case 2:
                        $defaults = array(
                            'title' => self::parse_rule_merge(
                                $details['add_meeting_title'], $campaign, $record, $record_type, $product, $disposition
                            ),
                            'status' => $details['add_meeting_status'],
                            'permission' => $details['add_meeting_permission'],
                            'priority' => $details['add_meeting_priority'],
                            'time' => Base_RegionalSettingsCommon::reg2time(self::parse_date_merge(
                                array(
                                    'time' => $details['add_meeting_time']['__date'],
                                    'choice' => $details['add_meeting_date_choice'],
                                    'specific_date_val' => isset($details['add_meeting_specific_date_val']) ? $details['add_meeting_specific_date_val'] : false,
                                    'dynamic_date_num' => isset($details['add_meeting_dynamic_date_num']) ? $details['add_meeting_dynamic_date_num'] : false,
                                    'dynamic_date_denom' => isset($details['add_meeting_dynamic_date_denom']) ? $details['add_meeting_dynamic_date_denom'] : false
                                )
                            ), true, true, true, false),
                            'date' => self::parse_date_merge(
                                array(
                                    'choice' => $details['add_meeting_date_choice'],
                                    'specific_date_val' => isset($details['add_meeting_specific_date_val']) ? $details['add_meeting_specific_date_val'] : false,
                                    'dynamic_date_num' => isset($details['add_meeting_dynamic_date_num']) ? $details['add_meeting_dynamic_date_num'] : false,
                                    'dynamic_date_denom' => isset($details['add_meeting_dynamic_date_denom']) ? $details['add_meeting_dynamic_date_denom'] : false
                                ), false
                            ),
                            'timeless' => isset($details['add_meeting_timeless']) ? $details['add_meeting_timeless'] : 0,
                            'messenger_on' => $details['add_meeting_alert'],
                            'description' => $details['add_meeting_description'],
                            'employees' => self::parse_multi_employees_merge(
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
                                    $defaults['end_time'] = date('Y-m-d H:i:s', strtotime(date('Y-m-d') . ' ' . $details['add_meeting_end_time']['__date']['h'] . ' ' . $details['add_meeting_end_time']['__date']['i'] . ' ' . $details['add_meeting_end_time']['__date']['a']));
                                } else {
                                    $defaults['end_time'] = date('Y-m-d') . ' ' . $details['add_meeting_end_time']['__date']['h'] . ' ' . $details['add_meeting_end_time']['__date']['i'] . ' ' . $details['add_meeting_end_time']['__date']['s'];
                                }
                            }
                        }
                        if ($defaults['messenger_on'] != 'none') {
                            $defaults['messenger_before'] = $details['add_meeting_popup_alert'];
                            $defaults['messenger_message'] = $details['add_meeting_popup_message'];
                        }
                        $return = Utils_RecordBrowserCommon::new_record('crm_meeting', $defaults);
                        break;
                    //Sales Opp
                    case 3:
                        $defaults = array(
                            'opportunity_name' => self::parse_rule_merge(
                                $details['add_opp_name'], $campaign, $record, $record_type, $product, $disposition
                            ),
                            'opportunity_manager' => self::parse_employee_merge($details['add_opp_manager']),
                            'type' => $details['add_opp_type'],
                            'probability____' => $details['add_opp_probability'],
                            'employees' => array(self::parse_employee_merge('current')),
                            'lead_source' => $details['add_opp_lead_source'],
                            'status' => $details['add_opp_status'],
                            'contract_amount' => (strpos($details['add_opp_contract_amount'], 'product_price') != 0) ? ($product ? $product['price'] : 0) : $details['add_opp_contract_amount'], //TODO::Add new cb for product price,
                            'start_date' => self::parse_date_merge(
                                array(
                                    'choice' => $details['add_opp_start_date'],
                                    'specific_date_val' => isset($details['add_opp_start_specific_date_val']) ? $details['add_opp_start_specific_date_val'] : false,
                                    'dynamic_date_num' => isset($details['add_opp_start_dynamic_date_num']) ? $details['add_opp_start_dynamic_date_num'] : false,
                                    'dynamic_date_denom' => isset($details['add_opp_start_dynamic_date_denom']) ? $details['add_opp_start_dynamic_date_denom'] : false
                                ), false
                            ),
                            'follow_up_date' => self::parse_date_merge(
                                array(
                                    'choice' => $details['add_opp_followup_date'],
                                    'specific_date_val' => isset($details['add_opp_followup_specific_date_val']) ? $details['add_opp_followup_specific_date_val'] : false,
                                    'dynamic_date_num' => isset($details['add_opp_followup_dynamic_date_num']) ? $details['add_opp_followup_dynamic_date_num'] : false,
                                    'dynamic_date_denom' => isset($details['add_opp_followup_dynamic_date_denom']) ? $details['add_opp_followup_dynamic_date_denom'] : false
                                ), false
                            ),
                            'description' => self::parse_rule_merge(
                                $details['add_opp_description'], $campaign, $record, $record_type, $product, $disposition
                            ),
                            'customers' => $customer,
                            'quantity' => 1
                        );
                        if ($product) {
                            $defaults['product'] = $campaign['product'];
                        }
                        $return = Utils_RecordBrowserCommon::new_record('premium_salesopportunity', $defaults);
                        break;

                }
                break;
        }
        return $return;
    }


}
