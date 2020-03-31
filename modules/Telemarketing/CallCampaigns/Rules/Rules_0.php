<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 3/02/20
 * @Time: 8:23 AM
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Telemarketing_CallCampaigns_Rules extends Module
{
    public $theme;
    public $form;
    public $mode;
    public $campaign;

    public function rules_addon($campaign)
    {
        $this->mode = 'record';
        $this->campaign = $campaign;
        $this->campaign_rules();
    }

    public function campaign_rules()
    {
        if (!$this->mode) {
            $this->mode = "admin";
        }
        if ($this->mode == 'admin') {
            Base_ThemeCommon::load_css('Utils_RecordBrowser', 'View_entry');
        }
        load_js('modules/' . self::module_name() . '/js/rules.js');
        load_js('modules/' . Libs_QuickForm::module_name() . '/FieldTypes/multiselect/multiselect.js');
        load_js('modules/Libs/CKEditor/ckeditor/ckeditor.js');
        $ckmodpath = get_epesi_url() . '/modules/Libs/CKEditor/ckeditor/';
        eval_js('window.CKEDITOR_BASEPATH="' . $ckmodpath . '";');
        eval_js('CKEDITOR.basePath="' . $ckmodpath . '";');

        $this->theme = $this->init_module(Base_Theme::module_name());
        $this->form = $this->init_module(Libs_QuickForm::module_name());
        $rule_mode = $this->form->addElement('hidden', 'mode', '', array('id' => "rules_hidden_mode"));
        if ($this->form->validate()) {
            $spec_values = $this->form->exportValues();
            $values = $this->form->getSubmitValues();
            foreach ($spec_values as $k => $v) {
                if (isset($values[$k])) {
                    unset($values[$k]);
                }
            }
            if ($spec_values['mode'] == 'add') {
                $this->add_rule();
            } else if (strpos($spec_values['mode'], 'delete') === 0) {
                $val = explode("_", $spec_values['mode']);
                $this->delete_rule($val[1]);
            } else {
                $this->save_rules($values);
            }
        }
        $rule_mode->setValue("");
        $theme_rules = array();
        if ($this->mode == 'admin') {
            $rules = Variable::get('telemarketing_default_rules');
            foreach ($rules as $i => $rule) {
                $theme_rules[$i] = $this->get_campaign_rule_template($i, $rule);
            }
        } else {
            $rules = Telemarketing_CallCampaigns_RulesCommon::get_rules($this->campaign['id']);
            foreach ($rules as $i => $rule) {
                $theme_rules[$i] = $this->get_campaign_rule_template($i, $rule, '');
            }
        }
        $this->theme->assign('rules', $theme_rules);
        $this->form->assign_theme('form', $this->theme);
        eval_js("CallCampaignRules.submit_form=function(){" . $this->form->get_submit_form_js() . "}");
        eval_js("CallCampaignRules.init();");
        if ($this->mode == 'record') {
            eval_js("CallCampaignRules.callCampaign = " . $this->campaign['id'] . ';');
        } else {
            eval_js("CallCampaignRules.callCampaign = 0;");
        }
        if ($this->mode == 'admin') {
            Base_ActionBarCommon::add("save", __("Save"), $this->form->get_submit_form_href());
        } else {
            $this->theme->assign('submit_href', $this->form->get_submit_form_href());
        }
        $static_text = array(
            "title" => __("Default Call Campaign Rules"),
            "desc" => __("<p>The rules specified here are the default rules used when a user adds a new call campaign record.</p><p>
                Specific rules for each call campaign that override these general rules can be set on its corresponding view screen.</p>"),
            "add_rule" => __("Add Rule"),
            'merge_fields' => __('Merge Fields'),
            "no_rules" => __("There are no rules added."),
            'save' => __("Save")
        );
        $this->theme->assign('static_text', $static_text);
        $this->theme->assign('cc_mode', $this->mode);
        $lb_id = 'call_campaign_rules_merge_fields';
        Libs_LeightboxCommon::display($lb_id, $this->get_merge_field_content($lb_id), __('Available Merge Fields'));
        $this->theme->assign('lb_id', $lb_id);
        $this->theme->display('Rules');
    }

    private function get_merge_field_content($lb_id = false)
    {
        $theme = $this->init_module(Base_Theme::module_name());
        $theme->assign('lb_id', $lb_id);

        $theme->assign('call_campaign_merge_fields',
            Telemarketing_CallCampaigns_RulesCommon::get_call_campaign_merge_fields());

        $theme->assign('product_merge_fields',
            Telemarketing_CallCampaigns_RulesCommon::get_product_merge_fields());

        $contact_merge_fields = Utils_MergeFieldsCommon::get_fields("contact",
            Telemarketing_CallScriptsCommon::get_excluded_contact_fields());
        $theme->assign('contact_merge_fields', $contact_merge_fields);

        $emp_merge_fields = Utils_MergeFieldsCommon::get_fields("contact",
            Telemarketing_CallScriptsCommon::get_excluded_contact_fields(), 'emp');
        $theme->assign('emp_merge_fields', $emp_merge_fields);

        $theme->assign('static_texts', array(
            'call_campaign' => __('Call Campaigns'),
            'product' => __('Product'),
            'target_contact' => __('Target Contact'),
            'employee' => __('Employee'),
            'merge_fields' => __('Merge Fields'),
            'merge_open' => Utils_MergeFieldsCommon::MERGE_OPEN_ID,
            'merge_close' => Utils_MergeFieldsCommon::MERGE_CLOSE_ID,
            'copy' => __('Copy')
        ));
        return $theme->get_html('MergeFields');
    }

    private function recurse_common_add_to_form_rules($path, &$form, $index)
    {
        $rule_paths = Utils_CommonDataCommon::get_array($path);
        if ($rule_paths && !empty($rule_paths)) {
            $class = 'rule_item_field rule_item_field_change';
            $level = substr_count($path, '/');
            $name = "";
            switch ($level) {
                case 1:
                    $label = __('When a');
                    $class .= ' rule_item_field_root';
                    $name = 'type';
                    break;
                case 2:
                    $label = __('is/has');
                    $name = 'condition_0';
                    break;
                case 3:
                    $label = __(', ');
                    $name = 'action_0';
                    break;
                case 4:
                    $label = __('a/an/the');
                    $name = 'action_1';
                    break;
            }
            foreach ($rule_paths as $key => $val) {
                $rule_paths[$key] = __($val);
            }
            $rel = strtolower(str_replace('/', '_', $path)) . ':' . $index;
            $elem = $form->createElement("select", $index, null,
                array_merge(array("" => ""), $rule_paths), array('style' => 'font-size:10pt;', 'rel' => $rel, 'class' => $class));
            $form->addGroup(array($elem), strtolower(str_replace('/', '_', $path) . '_' . $name), $label);
            $base = basename($path);
            if ($base == 'Record') {
                $rel = strtolower(str_replace('/', '_', $path)) . '_flagged:' . $index;
                $disposition = $form->createElement("select", $index, __("Disposition"),
                    Utils_CommonDataCommon::get_array('CallCampaign/Dispositions'),
                    array('style' => 'font-size:10pt;', 'rel' => $rel, 'class' => 'rule_item_field'));
                $form->addGroup(array($disposition), 'callcampaign_rules_record_flagged_disposition_condition_1', __('as'));
            } else if ($base == 'Campaign') {
                $rel = strtolower(str_replace('/', '_', $path)) . '_reached:' . $index;
                $num = $form->createElement("text", $index, __("Number"),
                    array('style' => 'font-size:10pt;', 'rel' => $rel, 'class' => 'rule_item_field'));
                $form->addGroup(array($num), 'callcampaign_rules_campaign_reached_num_condition_1');
                $disposition = $form->createElement("select", $index, __("Disposition"),
                    Utils_CommonDataCommon::get_array('CallCampaign/Dispositions'),
                    array('style' => 'font-size:10pt;', 'rel' => $rel, 'class' => 'rule_item_field'));
                $form->addGroup(array($disposition), 'callcampaign_rules_campaign_reached_disposition_condition_2');
            }
            foreach ($rule_paths as $key => $val) {
                $this->recurse_common_add_to_form_rules($path . '/' . $key, $form, $index);
            }
        }
    }

    public function get_campaign_rule_template($index, $rule)
    {
        $condition = explode(':', trim($rule['condition']));
        $action = explode(':', trim($rule['action']));

        $theme = $this->init_module(Base_Theme::module_name());
        $form = $this->init_module(Libs_QuickForm::module_name());
        $this->recurse_common_add_to_form_rules('CallCampaign/Rules', $form, $index);

        $defaults = array(
            'callcampaign_rules_type[' . $index . ']' => $rule['type'],
            'callcampaign_rules_' . strtolower($rule['type']) . '_condition_0[' . $index . ']' => $condition[0],
            'callcampaign_rules_' . strtolower($rule['type']) . '_' . strtolower($condition[0]) . '_action_0[' . $index . ']' => $action[0]
        );

        if (isset($action[1])) {
            $defaults['callcampaign_rules_' . strtolower($rule['type']) . '_' . strtolower($condition[0]) . '_' . strtolower($action[0]) . '_action_1[' . $index . ']'] = $action[1];
        }

        if ($condition[0] == 'Reaches') {
            $defaults['callcampaign_rules_campaign_reached_num_condition_1[' . $index . ']'] = $condition[1];
            $defaults['callcampaign_rules_campaign_reached_disposition_condition_2[' . $index . ']'] = $condition[2];
        }

        if ($condition[0] == 'Flagged') {
            $defaults['callcampaign_rules_record_flagged_disposition_condition_1[' . $index . ']'] = $condition[1];
        }

        $form->setDefaults($defaults);
        $static_texts = array(
            "delete" => __('Delete'),
            "show" => __('Show'),
            "hide" => __('Hide'),
            "details" => __('Details'),
        );

        $form->assign_theme('form', $theme);
        $theme->assign('static_texts', $static_texts);
        $theme->assign('index', $index);
        $theme->assign('cc_mode', $this->mode);
        $theme->assign('details', $this->route_template($index, $rule));

        return $theme->get_html("RuleTemplate");
    }

    public function route_template($index, $rule)
    {
        $details = array();
        if (isset($rule['details'])) {
            $details = $rule['details'];
        } else {
            $vr = Variable::get('telemarketing_default_rules');
            $vr_rule = $vr[$index];
            if ($vr_rule && isset($vr_rule['details'])) {
                $details = $vr_rule['details'];
            }
        }
        $action = explode(':', trim($rule['action']));
        $condition = explode(':', trim($rule['condition']));
        $template = "";
        if ($rule['type'] === 'Campaign') {
            switch ($condition[0]) {
                case "Done":
                    switch ($action[0]) {
                        case "Send":
                            switch ($action[1]) {
                                case "E-mail":
                                    $template = $this->send_email_template($details, $index);
                                    break;
                            }
                            break;
                        case "SetCampaignField":
                            $template = $this->set_campaign_field_template($details, $index);
                            break;
                    }
                    break;
                case "Reached":
                    switch ($action[0]) {
                        case "Send":
                            switch ($action[1]) {
                                case "E-mail":
                                    $template = $this->send_email_template($details, $index);
                                    break;
                            }
                            break;
                        case "SetCampaignField":
                            $template = $this->set_campaign_field_template($details, $index);
                            break;
                    }
                    break;
            }
        } else if ($rule['type'] === 'Record') {
            switch ($condition[0]) {
                case "Called":
                    switch ($action[0]) {
                        case "Add":
                            switch ($action[1]) {
                                case "SalesOpportunity":
                                    $template = $this->add_salesopp_template($details, $index, $action[1]);
                                    break;
                                case "Phonecall":
                                    $template = $this->add_phonecall_template($details, $index, $action[1]);
                                    break;
                                case "Task":
                                    $template = $this->add_task_template($details, $index, $action[1]);
                                    break;
                                case "Meeting":
                                    $template = $this->add_meeting_template($details, $index, $action[1]);
                                    break;
                            }
                            break;
                        case "AutoAdd":
                            switch ($action[1]) {
                                case "SalesOpportunity":
                                    $template = $this->add_salesopp_template($details, $index, $action[1]);
                                    break;
                                case "Phonecall":
                                    $template = $this->add_phonecall_template($details, $index, $action[1]);
                                    break;
                                case "Task":
                                    $template = $this->add_task_template($details, $index, $action[1]);
                                    break;
                                case "Meeting":
                                    $template = $this->add_meeting_template($details, $index, $action[1]);
                                    break;
                                case "RecordNote":
                                    $template = $this->add_record_note_template($details, $index);
                                    break;
                                case "ToList":
                                    $template = $this->add_to_list_template($details, $index);
                                    break;
                            }
                            break;
                        case "Send":
                            switch ($action[1]) {
                                case "E-mail":
                                    $template = $this->send_email_template($details, $index);
                                    break;
                            }
                            break;
                        case "SetRecordField":
                            $template = $this->set_record_field_template($details, $index);
                            break;
                    }
                    break;
                case "Flagged":
                    switch ($action[0]) {
                        case "Add":
                            switch ($action[1]) {
                                case "SalesOpportunity":
                                    $template = $this->add_salesopp_template($details, $index, $action[1]);
                                    break;
                                case "Phonecall":
                                    $template = $this->add_phonecall_template($details, $index, $action[1]);
                                    break;
                                case "Task":
                                    $template = $this->add_task_template($details, $index, $action[1]);
                                    break;
                                case "Meeting":
                                    $template = $this->add_meeting_template($details, $index, $action[1]);
                                    break;
                            }
                            break;
                        case "AutoAdd":
                            switch ($action[1]) {
                                case "SalesOpportunity":
                                    $template = $this->add_salesopp_template($details, $index, $action[1]);
                                    break;
                                case "Phonecall":
                                    $template = $this->add_phonecall_template($details, $index, $action[1]);
                                    break;
                                case "Task":
                                    $template = $this->add_task_template($details, $index, $action[1]);
                                    break;
                                case "Meeting":
                                    $template = $this->add_meeting_template($details, $index, $action[1]);
                                    break;
                                case "RecordNote":
                                    $template = $this->add_record_note_template($details, $index);
                                    break;
                                case "ToList":
                                    $template = $this->add_to_list_template($details, $index);
                                    break;
                            }
                            break;
                        case "Send":
                            switch ($action[1]) {
                                case "E-mail":
                                    $template = $this->send_email_template($details, $index);
                                    break;
                            }
                            break;
                        case "Remove":
                            $template = false;
                            break;
                        case "SetRecordField":
                            $template = $this->set_record_field_template($details, $index);
                            break;
                        case "Enqueue":
                            $template = false;
                            break;
                        case "Callback":
                            $template = false;
                            break;
                    }
                    break;
            }
        }
        return $template;
    }

    private function set_campaign_field_template($details, $index)
    {
        $form = $this->init_module(Libs_QuickForm::module_name());
        $theme = $this->init_module(Base_Theme::module_name());

        $status_field = $form->createElement('select', $index, __('Status'),
            Utils_CommonDataCommon::get_array('CRM/Status'));
        $status_field->setValue($details['set_campaign_field_status']);
        $form->addGroup(array($status_field), 'set_campaign_field_status', __('Status'));

        $form->assign_theme('form', $theme);
        return $theme->get_html('SetCampaignField');
    }

    private function set_record_field_template($details, $index)
    {
        $form = $this->init_module(Libs_QuickForm::module_name());
        $theme = $this->init_module(Base_Theme::module_name());

        $status_field = $form->createElement('select', $index, __('Status'),
            Utils_CommonDataCommon::get_array('CRM/Status'));
        $status_field->setValue($details['set_record_field_status']);
        $form->addGroup(array($status_field), 'set_record_field_status', __('Status'));

        $form->assign_theme('form', $theme);
        return $theme->get_html('SetRecordField');
    }

    private function add_to_list_template($details, $index)
    {
        $form = $this->init_module(Libs_QuickForm::module_name());
        $theme = $this->init_module(Base_Theme::module_name());

        $mailing_list = Utils_RecordBrowserCommon::get_records('premium_listmanager', array(), array('id', 'list_name'));
        $mailing_list_opts = array();
        foreach ($mailing_list as $ml) {
            $mailing_list_opts[$ml['id']] = $ml['list_name'];
        }
        $add_to_list_field = $form->createElement('multiselect', $index, __('Mailing List'), $mailing_list_opts);
        $add_to_list_field->setValue($details['add_to_list_list']);
        $form->addGroup(array($add_to_list_field), 'add_to_list_list', __('Mailing List'));

        $form->assign_theme('form', $theme);
        return $theme->get_html('AddToList');
    }

    private function add_record_note_template($details, $index)
    {
        $form = $this->init_module(Libs_QuickForm::module_name());
        $theme = $this->init_module(Base_Theme::module_name());

        $add_record_note = $form->createElement('textarea', $index, __("Note"), array('class' => 'rule_ck_field', 'rel' => 'toolbar:Basic', 'id' => 'add_record_note_ck_' . $index));
        $add_record_note->setValue($details['add_record_note_ck']);
        $form->addGroup(array($add_record_note), 'add_record_note_ck', __('Note'));

        $form->assign_theme('form', $theme);
        return $theme->get_html('AddRecordNote');
    }

    private function send_email_template($details, $index)
    {
        $form = $this->init_module(Libs_QuickForm::module_name());
        $theme = $this->init_module(Base_Theme::module_name());

        $send_mail_message = $form->createElement('textarea', $index, __('Recipient(s)'),
            array('placeholder' => __('Separate recipients by a semicolon') . ' (;) i.e. john.doe@gmail.com;smithjohn@yahoo.com'));
        $send_mail_message->setValue($details['send_mail_to']);
        $form->addGroup(array($send_mail_message), 'send_mail_to', __('Recipient(s)'));

        //TODO:BOdy

        $form->assign_theme('form', $theme);
        return $theme->get_html('SendEmail');
    }

    private function add_phonecall_template($details, $index, $action)
    {
        $form = $this->init_module(Libs_QuickForm::module_name());
        $theme = $this->init_module(Base_Theme::module_name());

        $col1 = array();
        $col2 = array();
        $longfields = array();

        $subject = $form->createElement('text', $index, __('Default Subject'));
        $subject->setValue($details['add_phonecall_subject']);
        $form->addGroup(array($subject), 'add_phonecall_subject', __('Default Subject'));
        $col1[] = 'add_phonecall_subject';
        if ($action == "AutoAdd") {
            $form->addRule('add_phonecall_subject', __('Fields is required'), 'required');
        }

        $description = $form->createElement('textarea', $index, __('Default Description:'), array('cols' => 35, 'rows' => 5));
        $description->setValue($details['add_phonecall_description']);
        $form->addGroup(array($description), 'add_phonecall_description', __('Default Description'));
        $longfields[] = 'add_phonecall_description';

        $permission = $form->createElement('select', $index, __('Default Permission'),
            Utils_CommonDataCommon::get_translated_array('CRM/Access', FALSE));
        if ($details['add_phonecall_permission']) {
            $permission->setSelected($details['add_phonecall_permission']);
        } else {
            $permission->setSelected(0);
        }
        $form->addGroup(array($permission), 'add_phonecall_permission', __('Default Permission'));
        $col1[] = 'add_phonecall_permission';

        $status = $form->createElement('select', $index, __('Default Status'),
            Utils_CommonDataCommon::get_translated_array('CRM/Status', FALSE));
        if ($details['add_phonecall_status']) {
            $status->setSelected($details['add_phonecall_status']);
        } else {
            $status->setSelected(0);
        }
        $form->addGroup(array($status), 'add_phonecall_status', __('Default Status'));
        $col2[] = 'add_phonecall_status';

        $priority = $form->createElement('select', $index, __('Default Priority'),
            Utils_CommonDataCommon::get_translated_array('CRM/Priority', FALSE));
        if ($details['add_phonecall_priority']) {
            $priority->setSelected($details['add_phonecall_priority']);
        } else {
            $priority->setSelected(1);
        }
        $form->addGroup(array($priority), 'add_phonecall_priority', __('Default Priority'));
        $col2[] = 'add_phonecall_priority';

        $current_date = $form->createElement('radio', $index, __('Default Date'), __('Current Date'), 'current_date',
            array('onchange' => "if(jQuery(this).is(':checked')){CallCampaignRules.updateDisabled('#rule_item_" . $index . " .ap_dd_date_controls', '#rule_item_" . $index . " .ap_dd_current_date');}"));
        $specific_date = $form->createElement('radio', $index, __('Default Date'), __('Specific Date'), 'specific_date',
            array('onchange' => "if(jQuery(this).is(':checked')){CallCampaignRules.updateDisabled('#rule_item_" . $index . " .ap_dd_date_controls', '#rule_item_" . $index . " .ap_dd_specific_date');}"));
        $dynamic_date = $form->createElement('radio', $index, __('Default Date'), __('Dynamic Date'), 'dynamic_date',
            array('onchange' => "if(jQuery(this).is(':checked')){CallCampaignRules.updateDisabled('#rule_item_" . $index . " .ap_dd_date_controls', '#rule_item_" . $index . " .ap_dd_dynamic_date');}"));
        $add_phonecall_date_choice = $form->addGroup(array($current_date, $specific_date, $dynamic_date), 'add_phonecall_date_choice', __('Default Date'));
        if ($details['add_phonecall_date_choice']) {
            $add_phonecall_date_choice->setValue($details['add_phonecall_date_choice']);
        } else {
            $add_phonecall_date_choice->setValue('current_date');
        }
        if ($action == "AutoAdd") {
            $form->addRule('add_phonecall_date_choice', __('Fields is required'), 'required');
        }

        $specific_date_val_ops = array('class' => 'ap_dd_date_controls ap_dd_specific_date');
        if (array_pop($add_phonecall_date_choice->getValue()) != 'specific_date') {
            $specific_date_val_ops['disabled'] = true;
        }
        $specific_date_val = $form->createElement('date', $index, NULL, array('format' => 'Y-m-d', 'minYear' => date('Y'), 'maxYear' => date('Y', strtotime('+10 years'))),
            $specific_date_val_ops);
        if ($details['add_phonecall_specific_date_val']) {
            $specific_date_val->setValue($details['add_phonecall_specific_date_val']);
        }
        $form->addGroup(array($specific_date_val), 'add_phonecall_specific_date_val', NULL);

        $dynamic_date_num_ops = array('style' => 'width:50px;', 'placeholder' => '0',
            'class' => 'ap_dd_date_controls ap_dd_dynamic_date');
        if (array_pop($add_phonecall_date_choice->getValue()) != 'dynamic_date') {
            $dynamic_date_num_ops['disabled'] = true;
        }
        $dynamic_date_num = $form->createElement('text', $index, NULL, $dynamic_date_num_ops);
        $dynamic_date_num->setValue($details['add_phonecall_dynamic_date_num']);
        $form->addGroup(array($dynamic_date_num), 'add_phonecall_dynamic_date_num', NULL);

        $dynamic_date_denom_ops = array('class' => 'ap_dd_date_controls ap_dd_dynamic_date');
        if (array_pop($add_phonecall_date_choice->getValue()) != 'dynamic_date') {
            $dynamic_date_denom_ops['disabled'] = true;
        }
        $denominations = array(
            0 => __('Day') . '(s)', 1 => __('Week') . '(s)', 2 => __('Month') . '(s)'
        );
        $dynamic_date_denom = $form->createElement('select', $index, NULL, $denominations, $dynamic_date_denom_ops);
        $dynamic_date_denom->setValue($details['add_phonecall_dynamic_date_denom']);
        $form->addGroup(array($dynamic_date_denom), 'add_phonecall_dynamic_date_denom', NULL);

        $t_emps = Utils_RecordBrowserCommon::get_records('contact', CRM_Contacts_RBO_Employee::employee_crits());
        $employees = array('current' => '[emp_last_name] [emp_first_name]');
        foreach ($t_emps as $t_emp) {
            $employees[$t_emp['id']] = CRM_ContactsCommon::contact_format_no_company($t_emp, true);
        }
        $employees_multi = $form->addElement('multiselect', $index, __('Employees'), $employees);
        if ($details['add_phonecall_employees']) {
            $employees_multi->setValue($details['add_phonecall_employees']);
        } else {
            $me = CRM_ContactsCommon::get_my_record();
            $employees_multi->setValue('__SEP__current');
        }
        $form->addGroup(array($employees_multi), 'add_phonecall_employees', __('Default Employees'));
        if ($action == "AutoAdd") {
            $form->addRule('add_phonecall_employees', __('Fields is required'), 'required');
        }
        $longfields[] = 'add_phonecall_employees';

        $time_format = Base_RegionalSettingsCommon::time_12h() ? 'h:i a' : 'H:i';
        $lang_code = Base_LangCommon::get_lang_code();
        $phonecall_time_class = array('class' => 'ap_dd_date_controls ap_dd_dynamic_date ap_dd_specific_date');
        if (array_pop($add_phonecall_date_choice->getValue()) == 'current_date') {
            $phonecall_time_class['disabled'] = true;
        }
        $phonecall_time = $form->createElement('timestamp', $index, NULL, array(
            'date' => FALSE,
            'format' => $time_format,
            'optionIncrement' => array('i' => 5),
            'language' => $lang_code,
        ), $phonecall_time_class);
        if ($details['add_phonecall_time']) {
            $phonecall_time->setValue($details['add_phonecall_time']);
        } else {
            $phonecall_time->setValue(array('__date' => array('h' => 12, 'i' => 00, 'a' => 'am')));
        }
        $form->addGroup(array($phonecall_time), 'add_phonecall_time', __("Default Time"));
        $col1[] = 'add_phonecall_time';

        $email_employees = $form->createElement('checkbox', $index, NULL);
        if ($details['add_phonecall_email_employees']) {
            $email_employees->setChecked($details['add_phonecall_email_employees']);
        }
        $form->addGroup(array($email_employees), 'add_phonecall_email_employees', __('Email Employees'));
        $col2[] = 'add_phonecall_email_employees';

        $form->assign_theme('form', $theme);
        $theme->assign('index', $index);
        $theme->assign('static_texts', array('after' => __('after'), 'current_date' => __('current date')));
        $theme->assign('col1', $col1);
        $theme->assign('col2', $col2);
        $theme->assign('longfields', $longfields);
        return $theme->get_html('Phonecall');
    }

    private function add_meeting_template($details, $index, $action)
    {
        $form = $this->init_module(Libs_QuickForm::module_name());
        $theme = $this->init_module(Base_Theme::module_name());

        $col1 = array();
        $col2 = array();
        $longfields = array();

        $title = $form->createElement('text', $index, __('Default Title'));
        $title->setValue($details['add_meeting_title']);
        $form->addGroup(array($title), 'add_meeting_title', __('Default Title'));
        $col1[] = 'add_meeting_title';
        if ($action == "AutoAdd") {
            $form->addRule('add_meeting_title', __('Fields is required'), 'required');
        }

        $description = $form->createElement('textarea', $index, __('Default Description:'), array('cols' => 35, 'rows' => 5));
        $description->setValue($details['add_meeting_description']);
        $form->addGroup(array($description), 'add_meeting_description', __('Default Description'));
        $longfields[] = 'add_meeting_description';

        $permission = $form->createElement('select', $index, __('Default Permission'),
            Utils_CommonDataCommon::get_translated_array('CRM/Access', FALSE));
        if ($details['add_meeting_permission']) {
            $permission->setSelected($details['add_meeting_permission']);
        } else {
            $permission->setSelected(0);
        }
        $form->addGroup(array($permission), 'add_meeting_permission', __('Default Permission'));
        $col1[] = 'add_meeting_permission';

        $status = $form->createElement('select', $index, __('Default Status'),
            Utils_CommonDataCommon::get_translated_array('CRM/Status', FALSE));
        if ($details['add_meeting_status']) {
            $status->setSelected($details['add_meeting_status']);
        } else {
            $status->setSelected(0);
        }
        $form->addGroup(array($status), 'add_meeting_status', __('Default Status'));
        $col2[] = 'add_meeting_status';

        $priority = $form->createElement('select', $index, __('Default Priority'),
            Utils_CommonDataCommon::get_translated_array('CRM/Priority', FALSE));
        if ($details['add_meeting_priority']) {
            $priority->setSelected($details['add_meeting_priority']);
        } else {
            $priority->setSelected(1);
        }
        $form->addGroup(array($priority), 'add_meeting_priority', __('Default Priority'));
        $col2[] = 'add_meeting_priority';

        $current_date = $form->createElement('radio', $index, __('Default Meeting Date'), __('Current Date'), 'current_date',
            array('onchange' => "if(jQuery(this).is(':checked')){CallCampaignRules.updateDisabled('#rule_item_" . $index . " .am_dmd_date_controls', '#rule_item_" . $index . " .am_dmd_current_date');}"));
        $specific_date = $form->createElement('radio', $index, __('Default Meeting Date'), __('Specific Date'), 'specific_date',
            array('onchange' => "if(jQuery(this).is(':checked')){CallCampaignRules.updateDisabled('#rule_item_" . $index . " .am_dmd_date_controls', '#rule_item_" . $index . " .am_dmd_specific_date');}"));
        $dynamic_date = $form->createElement('radio', $index, __('Default Meeting Date'), __('Dynamic Date'), 'dynamic_date',
            array('onchange' => "if(jQuery(this).is(':checked')){CallCampaignRules.updateDisabled('#rule_item_" . $index . " .am_dmd_date_controls', '#rule_item_" . $index . " .am_dmd_dynamic_date');}"));
        $add_meeting_date_choice = $form->addGroup(array($current_date, $specific_date, $dynamic_date), 'add_meeting_date_choice', __('Default Meeting Date'));
        if ($details['add_meeting_date_choice']) {
            $add_meeting_date_choice->setValue($details['add_meeting_date_choice']);
        } else {
            $add_meeting_date_choice->setValue('current_date');
        }
        if ($action == "AutoAdd") {
            $form->addRule('add_meeting_date_choice', __('Fields is required'), 'required');
        }

        $specific_date_val_ops = array('class' => 'am_dmd_date_controls am_dmd_specific_date');
        if (array_pop($add_meeting_date_choice->getValue()) != 'specific_date') {
            $specific_date_val_ops['disabled'] = true;
        }
        $specific_date_val = $form->createElement('date', $index, NULL, array('format' => 'Y-m-d', 'minYear' => date('Y'), 'maxYear' => date('Y', strtotime('+10 years'))),
            $specific_date_val_ops);
        if ($details['add_meeting_specific_date_val']) {
            $specific_date_val->setValue($details['add_meeting_specific_date_val']);
        }
        $form->addGroup(array($specific_date_val), 'add_meeting_specific_date_val', NULL);

        $dynamic_date_num_ops = array('style' => 'width:50px;', 'placeholder' => '0',
            'class' => 'am_dmd_date_controls am_dmd_dynamic_date');
        if (array_pop($add_meeting_date_choice->getValue()) != 'dynamic_date') {
            $dynamic_date_num_ops['disabled'] = true;
        }
        $dynamic_date_num = $form->createElement('text', $index, NULL, $dynamic_date_num_ops);
        $dynamic_date_num->setValue($details['add_meeting_dynamic_date_num']);
        $form->addGroup(array($dynamic_date_num), 'add_meeting_dynamic_date_num', NULL);

        $dynamic_date_denom_ops = array('class' => 'am_dmd_date_controls am_dmd_dynamic_date');
        if (array_pop($add_meeting_date_choice->getValue()) != 'dynamic_date') {
            $dynamic_date_denom_ops['disabled'] = true;
        }
        $denominations = array(
            0 => __('Day') . '(s)', 1 => __('Week') . '(s)', 2 => __('Month') . '(s)'
        );
        $dynamic_date_denom = $form->createElement('select', $index, NULL, $denominations, $dynamic_date_denom_ops);
        $dynamic_date_denom->setValue($details['add_meeting_dynamic_date_denom']);
        $form->addGroup(array($dynamic_date_denom), 'add_meeting_dynamic_date_denom', NULL);

        $time_format = Base_RegionalSettingsCommon::time_12h() ? 'h:i a' : 'H:i';
        $lang_code = Base_LangCommon::get_lang_code();
        $meeting_time = $form->createElement('timestamp', $index, NULL, array(
            'date' => FALSE,
            'format' => $time_format,
            'optionIncrement' => array('i' => 5),
            'language' => $lang_code,
        ));
        if ($details['add_meeting_time']) {
            $meeting_time->setValue($details['add_meeting_time']);
        } else {
            $meeting_time->setValue(array('__date' => array('h' => 12, 'i' => 00, 'a' => 'am')));
        }
        $form->addGroup(array($meeting_time), 'add_meeting_time', __("Default Meeting Time"));
        $col1[] = 'add_meeting_time';

        $timeless = $form->createElement('checkbox', $index, NULL, NULL, array(
            'onclick' => "if(!jQuery(this).is(':checked')){jQuery('.am_timeless_" . $index . "').css('visibility','visible');}else{ jQuery('.am_timeless_" . $index . "').css('visibility','hidden');}"
        ));
        $timeless->setChecked($details['add_meeting_timeless']);
        $form->addGroup(array($timeless), 'add_meeting_timeless', __('Timeless'));
        $timetype_duration_ops = array('onchange' =>
            "if(jQuery(this).is(':checked')){CallCampaignRules.updateDisabled('#rule_item_" . $index . " .am_timeless_tt', '#rule_item_" . $index . " .am_timeless_td')};"
        );
        $timetype_duration = $form->createElement('radio', $index, __('Time Type'), __('Duration'), 'duration', $timetype_duration_ops);
        $timetype_end_time_ops = array('onchange' =>
            "if(jQuery(this).is(':checked')){CallCampaignRules.updateDisabled('#rule_item_" . $index . " .am_timeless_tt', '#rule_item_" . $index . " .am_timeless_te');}"
        );
        $timetype_end_time = $form->createElement('radio', $index, __('Time Type'), __('End Time'), 'end_time', $timetype_end_time_ops);
        $add_meeting_time_type = $form->addGroup(array($timetype_duration, $timetype_end_time), 'add_meeting_time_type', __('Time Type'));
        if ($details['add_meeting_time_type']) {
            $add_meeting_time_type->setValue($details['add_meeting_time_type']);
        } else {
            $add_meeting_time_type->setValue('duration');
        }
        $dur = array(
            300 => __('5 minutes'),
            900 => __('15 minutes'),
            1800 => __('30 minutes'),
            2700 => __('45 minutes'),
            3600 => __('1 hour'),
            7200 => __('2 hours'),
            14400 => __('4 hours'),
            28800 => __('8 hours')
        );
        $duration_ops = array('class' => 'am_timeless_tt am_timeless_td');
        if (array_pop($add_meeting_time_type->getValue()) != 'duration') {
            $duration_ops['disabled'] = true;
        }
        $duration = $form->createElement('select', $index, __('Duration'), $dur, $duration_ops);
        if ($details['add_meeting_duration']) {
            $duration->setValue($details['add_meeting_duration']);
        } else {
            $duration->setValue(3600);
        }
        $form->addGroup(array($duration), 'add_meeting_duration', __('Duration'));

        $end_time_ops = array('class' => 'am_timeless_tt am_timeless_te');
        if (array_pop($add_meeting_time_type->getValue()) != 'end_time') {
            $end_time_ops['disabled'] = true;
        }
        $end_time = $form->createElement('timestamp', $index, __('End Time'), array(
            'date' => FALSE,
            'format' => $time_format,
            'optionIncrement' => array('i' => 5),
            'language' => $lang_code,
        ), $end_time_ops);
        if ($details['add_meeting_end_time']) {
            $end_time->setValue($details['add_meeting_end_time']);
        } else {
            $end_time->setValue(array('__date' => array('h' => 12, 'i' => 00, 'a' => 'am')));
        }
        $form->addGroup(array($end_time), 'add_meeting_end_time', __('End Time'));

        $alert = $form->createElement('select', $index, __("Alert"), array('none' => __('None'), 'me' => __('me'), 'all' => __('all selected employees')),
            array(
                'onchange' => 'if(jQuery(this).val() == "none"){CallCampaignRules.updateDisabled("#rule_item_' . $index . ' .am_alert");}else{CallCampaignRules.updateDisabled(false,"#rule_item_' . $index . ' .am_alert");}'
            ));
        if ($details['add_meeting_alert']) {
            $alert->setValue($details['add_meeting_alert']);
        } else {
            $alert->setValue('none');
        }
        $form->addGroup(array($alert), 'add_meeting_alert', __('Alert'));
        $popup_alert =
            array(
                0 => __('on event start'),
                900 => __('15 minutes before event'),
                1800 => __('30 minutes before event'),
                2700 => __('45 minutes before event'),
                3600 => __('1 hour before event'),
                2 * 3600 => __('2 hours before event'),
                3 * 3600 => __('3 hours before event'),
                4 * 3600 => __('4 hours before event'),
                8 * 3600 => __('8 hours before event'),
                12 * 3600 => __('12 hours before event'),
                24 * 3600 => __('24 hours before event')
            );
        $pop_alert_ops = array('class' => 'am_alert');
        if (array_pop($alert->getValue()) == 'none') {
            $pop_alert_ops['disabled'] = true;
        }
        $popup_alert = $form->createElement('select', $index, __('Popup Alert'), $popup_alert, $pop_alert_ops);
        if ($details['add_meeting_popup_alert']) {
            $popup_alert->setValue($details['add_meeting_popup_alert']);
        } else {
            $popup_alert->setValue(0);
        }
        $form->addGroup(array($popup_alert), 'add_meeting_popup_alert', __('Popup Alert'));

        $pop_message_ops = array('class' => 'am_alert');
        if (array_pop($alert->getValue()) == 'none') {
            $pop_message_ops['disabled'] = true;
        }
        $popup_message = $form->createElement('textarea', $index, __('Popup Message'), $pop_message_ops);
        $popup_message->setValue($details['add_meeting_popup_message']);
        $form->addGroup(array($popup_message), 'add_meeting_popup_message', __('Popup Message'));

        $t_emps = Utils_RecordBrowserCommon::get_records('contact', CRM_Contacts_RBO_Employee::employee_crits());
        $employees = array('current' => '[emp_last_name] [emp_first_name]');
        foreach ($t_emps as $t_emp) {
            $employees[$t_emp['id']] = CRM_ContactsCommon::contact_format_no_company($t_emp, true);
        }
        $employees_multi = $form->addElement('multiselect', $index, __('Employees'), $employees);
        if ($details['add_meeting_employees']) {
            $employees_multi->setValue($details['add_meeting_employees']);
        } else {
            $employees_multi->setValue('__SEP__current');
        }
        $form->addGroup(array($employees_multi), 'add_meeting_employees', __('Default Assigned Employees'));
        if ($action == "AutoAdd") {
            $form->addRule('add_meeting_employees', __('Fields is required'), 'required');
        }
        $longfields[] = 'add_meeting_employees';

        $form->assign_theme('form', $theme);
        $theme->assign('index', $index);
        $theme->assign('static_texts', array('after' => __('after'), 'current_date' => __('current date')));
        $theme->assign('col1', $col1);
        $theme->assign('col2', $col2);
        $theme->assign('details', $details);
        $theme->assign('longfields', $longfields);
        return $theme->get_html('Meeting');
    }

    private function add_task_template($details, $index, $action)
    {
        $form = $this->init_module(Libs_QuickForm::module_name());
        $theme = $this->init_module(Base_Theme::module_name());

        $col1 = array();
        $col2 = array();
        $longfields = array();

        $title = $form->createElement('text', $index, __('Default Title'));
        $title->setValue($details['add_task_title']);
        $form->addGroup(array($title), 'add_task_title', __('Default Title'));
        $col1[] = 'add_task_title';
        if ($action == "AutoAdd") {
            $form->addRule('add_task_title', __('Fields is required'), 'required');
        }

        $description = $form->createElement('textarea', $index, __('Default Description:'), array('cols' => 35, 'rows' => 5));
        $description->setValue($details['add_task_description']);
        $form->addGroup(array($description), 'add_task_description', __('Default Description'));
        $longfields[] = 'add_task_description';

        $permission = $form->createElement('select', $index, __('Default Permission'),
            Utils_CommonDataCommon::get_translated_array('CRM/Access', FALSE));
        if ($details['add_task_permission']) {
            $permission->setSelected($details['add_task_permission']);
        } else {
            $permission->setSelected(0);
        }
        $form->addGroup(array($permission), 'add_task_permission', __('Default Permission'));
        $col1[] = 'add_task_permission';

        $status = $form->createElement('select', $index, __('Default Status'),
            Utils_CommonDataCommon::get_translated_array('CRM/Status', FALSE));
        if ($details['add_task_status']) {
            $status->setSelected($details['add_task_status']);
        } else {
            $status->setSelected(0);
        }
        $form->addGroup(array($status), 'add_task_status', __('Default Status'));
        $col2[] = 'add_task_status';

        $priority = $form->createElement('select', $index, __('Default Priority'),
            Utils_CommonDataCommon::get_translated_array('CRM/Priority', FALSE));
        if ($details['add_task_priority']) {
            $priority->setSelected($details['add_task_priority']);
        } else {
            $priority->setSelected(1);
        }
        $form->addGroup(array($priority), 'add_task_priority', __('Default Priority'));
        $col2[] = 'add_task_priority';

        $longterm = $form->createElement('checkbox', $index, __('Longterm'));
        $form->addGroup(array($longterm), 'add_task_longterm', __('Longterm'));
        $col1[] = 'add_task_longterm';

        $current_date = $form->createElement('radio', $index, __('Default Deadline'), __('Current Date'), 'current_date',
            array('onchange' => "if(jQuery(this).is(':checked')){CallCampaignRules.updateDisabled('#rule_item_" . $index . " .at_dd_date_controls', '#rule_item_" . $index . " .at_dd_current_date');}"));
        $specific_date = $form->createElement('radio', $index, __('Default Deadline'), __('Specific Date'), 'specific_date',
            array('onchange' => "if(jQuery(this).is(':checked')){CallCampaignRules.updateDisabled('#rule_item_" . $index . " .at_dd_date_controls', '#rule_item_" . $index . " .at_dd_specific_date');}"));
        $dynamic_date = $form->createElement('radio', $index, __('Default Deadline'), __('Dynamic Date'), 'dynamic_date',
            array('onchange' => "if(jQuery(this).is(':checked')){CallCampaignRules.updateDisabled('#rule_item_" . $index . " .at_dd_date_controls', '#rule_item_" . $index . " .at_dd_dynamic_date');}"));
        $add_task_deadline = $form->addGroup(array($current_date, $specific_date, $dynamic_date), 'add_task_deadline', __('Default Deadline'));
        if ($details['add_task_deadline']) {
            $add_task_deadline->setValue($details['add_task_deadline']);
        } else {
            $add_task_deadline->setValue('current_date');
        }

        $specific_date_val_ops = array('class' => 'at_dd_date_controls at_dd_specific_date');
        if (array_pop($add_task_deadline->getValue()) != 'specific_date') {
            $specific_date_val_ops['disabled'] = true;
        }
        $specific_date_val = $form->createElement('date', $index, NULL, array('format' => 'Y-m-d', 'minYear' => date('Y'), 'maxYear' => date('Y', strtotime('+10 years'))),
            $specific_date_val_ops);
        if ($details['add_task_specific_date_val']) {
            $specific_date_val->setValue($details['add_task_specific_date_val']);
        }
        $form->addGroup(array($specific_date_val), 'add_task_specific_date_val', NULL);

        $dynamic_date_num_ops = array('style' => 'width:50px;', 'placeholder' => '0',
            'class' => 'at_dd_date_controls at_dd_dynamic_date');
        if (array_pop($add_task_deadline->getValue()) != 'dynamic_date') {
            $dynamic_date_num_ops['disabled'] = true;
        }
        $dynamic_date_num = $form->createElement('text', $index, NULL, $dynamic_date_num_ops);
        $dynamic_date_num->setValue($details['add_task_dynamic_date_num']);
        $form->addGroup(array($dynamic_date_num), 'add_task_dynamic_date_num', NULL);

        $dynamic_date_denom_ops = array('class' => 'at_dd_date_controls at_dd_dynamic_date');
        if (array_pop($add_task_deadline->getValue()) != 'dynamic_date') {
            $dynamic_date_denom_ops['disabled'] = true;
        }
        $denominations = array(
            0 => __('Day') . '(s)', 1 => __('Week') . '(s)', 2 => __('Month') . '(s)'
        );
        $dynamic_date_denom = $form->createElement('select', $index, NULL, $denominations, $dynamic_date_denom_ops);
        $dynamic_date_denom->setValue($details['add_task_dynamic_date_denom']);
        $form->addGroup(array($dynamic_date_denom), 'add_task_dynamic_date_denom', NULL);

        $t_emps = Utils_RecordBrowserCommon::get_records('contact', CRM_Contacts_RBO_Employee::employee_crits());
        $employees = array('current' => '[emp_last_name] [emp_first_name]');
        foreach ($t_emps as $t_emp) {
            $employees[$t_emp['id']] = CRM_ContactsCommon::contact_format_no_company($t_emp, true);
        }
        $employees_multi = $form->addElement('multiselect', $index, __('Employees'), $employees);
        if ($details['add_task_employees']) {
            $employees_multi->setValue($details['add_task_employees']);
        } else {
            $employees_multi->setValue('__SEP__current');
        }
        $form->addGroup(array($employees_multi), 'add_task_employees', __('Default Assigned Employees'));
        if ($action == "AutoAdd") {
            $form->addRule('add_task_employees', __('Fields is required'), 'required');
        }
        $longfields[] = 'add_task_employees';

        $form->assign_theme('form', $theme);
        $theme->assign('index', $index);
        $theme->assign('col1', $col1);
        $theme->assign('col2', $col2);
        $theme->assign('longfields', $longfields);
        $theme->assign('static_texts', array('after' => __('after'), 'current_date' => __('current date')));
        return $theme->get_html('Task');
    }

    private function add_salesopp_template($details, $index, $action)
    {
        $form = $this->init_module(Libs_QuickForm::module_name());
        $theme = $this->init_module(Base_Theme::module_name());

        $col1 = array();
        $col2 = array();
        $longfields = array();

        $opp_name = $form->createElement('text', $index, __('Default') . ' ' . __('Opportunity Name'));
        $opp_name->setValue($details['add_opp_name']);
        $form->addGroup(array($opp_name), 'add_opp_name', __('Default') . ' ' . __('Opportunity Name'));
        $col1[] = 'add_opp_name';

        $description = $form->createElement('textarea', $index, __('Default Description:'), array('cols' => 35, 'rows' => 5));
        $description->setValue($details['add_opp_description']);
        $form->addGroup(array($description), 'add_opp_description', __('Default Description'));
        $longfields[] = 'add_opp_description';

        $t_employees = Utils_RecordBrowserCommon::get_records('contact', CRM_Contacts_RBO_Employee::employee_crits());
        $employees = array('current' => '[emp_last_name] [emp_first_name]');
        foreach ($t_employees as $t_employee) {
            $employees[$t_employee['id']] = CRM_ContactsCommon::contact_format_no_company($t_employee, true);
        }
        $opp_manager = $form->createElement('select', $index, __('Default') . ' ' . __('Opportunity Manager'), $employees);
        if ($details['add_opp_manager']) {
            $opp_manager->setValue($details['add_opp_manager']);
        } else {
            $opp_manager->setValue('current');
        }
        $form->addGroup(array($opp_manager), 'add_opp_manager', __('Default') . ' ' . __('Opportunity Manager'));
        $col2[] = 'add_opp_manager';


        $opp_type = $form->createElement('select', $index, __('Default') . ' ' . __('Type'),
            Utils_CommonDataCommon::get_translated_array('Premium/SalesOpportunity/Type', FALSE));
        if ($details['add_opp_type']) {
            $opp_type->setValue($details['add_opp_type']);
        } else {
            $opp_type->setValue(0);
        }
        $form->addGroup(array($opp_type), 'add_opp_type', __('Default') . ' ' . __('Type'));
        $col1[] = 'add_opp_type';


        $opp_lead_source = $form->createElement('select', $index, __('Default') . ' ' . __('Lead Source'),
            Utils_CommonDataCommon::get_translated_array('Premium/SalesOpportunity/Source', FALSE));
        if ($details['add_opp_lead_source']) {
            $opp_lead_source->setValue($details['add_opp_lead_source']);
        } else {
            $opp_lead_source->setValue(4);
        }
        $form->addGroup(array($opp_lead_source), 'add_opp_lead_source', __('Default') . ' ' . __('Lead Source'));
        $col2[] = 'add_opp_lead_source';

        $opp_probability = $form->createElement('text', $index, __('Default') . ' ' . __('Probability') . ' (%)', array('placeholder' => '0'));
        if ($details['add_opp_probability']) {
            $opp_probability->setValue($details['add_opp_probability']);
        } else {
            $opp_probability->setValue(50);
        }
        $form->addGroup(array($opp_probability), 'add_opp_probability', __('Default') . ' ' . __('Probability') . ' (%)');
        $col1[] = 'add_opp_probability';

        $opp_status = $form->createElement('select', $index, __('Default') . ' ' . __('Status'),
            Utils_CommonDataCommon::get_translated_array('Premium/SalesOpportunity/Status', FALSE));
        if ($details['add_opp_status']) {
            $opp_status->setValue($details['add_opp_status']);
        } else {
            $opp_status->setValue(0);
        }
        $form->addGroup(array($opp_status), 'add_opp_status', __('Default') . ' ' . __('Status'));
        $col2[] = 'add_opp_status';

        $opp_contract_amount = $form->createElement('currency', $index, __('Default') . ' ' . __('Contract Amount'));
        if ($details['add_opp_contract_amount']) {
            $opp_contract_amount->setValue($details['add_opp_contract_amount']);
        } else {
            $opp_contract_amount->setValue('[product_price]');
        }
        $form->addGroup(array($opp_contract_amount), 'add_opp_contract_amount', __('Default') . ' ' . __('Contract Amount'));
        $col1[] = 'add_opp_contract_amount';

        $current_date = $form->createElement('radio', $index, NULL, __('Current Date'), 'current_date');
        $specific_date = $form->createElement('radio', $index, NULL, __('Specific Date'), 'specific_date');
        $dynamic_date = $form->createElement('radio', $index, NULL, __('Dynamic Date'), 'dynamic_date');
        $denominations = array(
            0 => __('Day') . '(s)', 1 => __('Week') . '(s)', 2 => __('Month') . '(s)'
        );
        $dynamic_date_ref = array(
            0 => __('Start Date'),
            1 => __('Current Date')
        );
        $date_ops = array('format' => 'Y-m-d', 'minYear' => date('Y'), 'maxYear' => date('Y', strtotime('+10 years')));
        $num_ops = array('style' => 'width:50px;', 'placeholder' => '0');

        $start_current_date = clone $current_date;
        $start_current_date->setAttribute('onchange',
            "if(jQuery(this).is(':checked')){CallCampaignRules.updateDisabled('#rule_item_" . $index . " .as_start_date_controls', '#rule_item_" . $index . " .as_start_current_date');}");
        $start_specific_date = clone $specific_date;
        $start_specific_date->setAttribute('onchange',
            "if(jQuery(this).is(':checked')){CallCampaignRules.updateDisabled('#rule_item_" . $index . " .as_start_date_controls', '#rule_item_" . $index . " .as_start_specific_date');}");
        $start_dynamic_date = clone $dynamic_date;
        $start_dynamic_date->setAttribute('onchange',
            "if(jQuery(this).is(':checked')){CallCampaignRules.updateDisabled('#rule_item_" . $index . " .as_start_date_controls', '#rule_item_" . $index . " .as_start_dynamic_date');}");
        $add_opp_start_date = $form->addGroup(array($start_current_date, $start_specific_date, $start_dynamic_date), 'add_opp_start_date', __('Default') . ' ' . __('Start Date'));
        if ($details['add_opp_start_date']) {
            $add_opp_start_date->setValue($details['add_opp_start_date']);
        } else {
            $add_opp_start_date->setValue('current_date');
        }
        $start_specific_date_val_ops = array('class' => 'as_start_date_controls as_start_specific_date');
        if (array_pop($add_opp_start_date->getValue()) != 'specific_date') {
            $start_specific_date_val_ops['disabled'] = true;
        }
        $start_specific_date_val = $form->createElement('date', $index, NULL, $date_ops, $start_specific_date_val_ops);
        if ($details['add_opp_start_specific_date_val']) {
            $start_specific_date_val->setValue('add_opp_start_specific_date_val');
        }
        $form->addGroup(array($start_specific_date_val), 'add_opp_start_specific_date_val', NULL);

        $start_dynamic_date_num_ops = $num_ops;
        $start_dynamic_date_num_ops['class'] = 'as_start_date_controls as_start_dynamic_date';
        if (array_pop($add_opp_start_date->getValue()) != 'dynamic_date') {
            $start_dynamic_date_num_ops['disabled'] = true;
        }
        $start_dynamic_date_num = $form->createElement('text', $index, NULL, $start_dynamic_date_num_ops);
        $start_dynamic_date_num->setValue($details['add_opp_start_dynamic_date_num']);
        $form->addGroup(array($start_dynamic_date_num), 'add_opp_start_dynamic_date_num', NULL);

        $start_dynamic_date_denom_ops = array('class' => 'as_start_date_controls as_start_dynamic_date');
        if (array_pop($add_opp_start_date->getValue()) != 'dynamic_date') {
            $start_dynamic_date_denom_ops['disabled'] = true;
        }
        $start_dynamic_date_denom = $form->createElement('select', $index, NULL, $denominations, $start_dynamic_date_denom_ops);
        $start_dynamic_date_denom->setValue($details['add_opp_start_dynamic_date_denom']);
        $form->addGroup(array($start_dynamic_date_denom), 'add_opp_start_dynamic_date_denom', NULL);

        $follow_current_date = clone $current_date;
        $follow_current_date->setAttribute('onchange',
            "if(jQuery(this).is(':checked')){CallCampaignRules.updateDisabled('#rule_item_" . $index . " .as_follow_date_controls', '#rule_item_" . $index . " .as_follow_current_date');}");
        $follow_specific_date = clone $specific_date;
        $follow_specific_date->setAttribute('onchange',
            "if(jQuery(this).is(':checked')){CallCampaignRules.updateDisabled('#rule_item_" . $index . " .as_follow_date_controls', '#rule_item_" . $index . " .as_follow_specific_date');}");
        $follow_dynamic_date = clone $dynamic_date;
        $follow_dynamic_date->setAttribute('onchange',
            "if(jQuery(this).is(':checked')){CallCampaignRules.updateDisabled('#rule_item_" . $index . " .as_follow_date_controls', '#rule_item_" . $index . " .as_follow_dynamic_date');}");
        $add_opp_followup_date = $form->addGroup(array($follow_current_date, $follow_specific_date, $follow_dynamic_date), 'add_opp_followup_date', __('Default') . ' ' . __('Follow-up Date'));
        if ($details['add_opp_followup_date']) {
            $add_opp_followup_date->setValue($details['add_opp_followup_date']);
        } else {
            $add_opp_followup_date->setValue('dynamic_date');
        }
        $followup_specific_date_val_ops = array('class' => 'as_follow_date_controls as_follow_specific_date');
        if (array_pop($add_opp_followup_date->getValue()) != 'specific_date') {
            $followup_specific_date_val_ops['disabled'] = true;
        }
        $followup_specific_date_val = $form->createElement('date', $index, NULL, $date_ops, $followup_specific_date_val_ops);
        if ($details['add_opp_followup_specific_date_val']) {
            $followup_specific_date_val->setValue($details['add_opp_followup_specific_date_val']);
        }
        $form->addGroup(array($followup_specific_date_val), 'add_opp_followup_specific_date_val', NULL);

        $followup_dynamic_date_num_ops = $num_ops;
        $followup_dynamic_date_num_ops['class'] = 'as_follow_date_controls as_follow_dynamic_date';
        if (array_pop($add_opp_followup_date->getValue()) != 'dynamic_date') {
            $followup_dynamic_date_num_ops['disabled'] = true;
        }
        $followup_dynamic_date_num = $form->createElement('text', $index, NULL, $followup_dynamic_date_num_ops);
        if ($details['add_opp_followup_dynamic_date_num']) {
            $followup_dynamic_date_num->setValue($details['add_opp_followup_dynamic_date_num']);
        } else {
            $followup_dynamic_date_num->setValue(3);
        }
        $form->addGroup(array($followup_dynamic_date_num), 'add_opp_followup_dynamic_date_num', NULL);

        $followup_dynamic_date_denom_ops = array('class' => 'as_follow_date_controls as_follow_dynamic_date');
        if (array_pop($add_opp_followup_date->getValue()) != 'dynamic_date') {
            $followup_dynamic_date_denom_ops['disabled'] = true;
        }
        $followup_dynamic_date_denom = $form->createElement('select', $index, NULL, $denominations, $followup_dynamic_date_denom_ops);
        if ($details['add_opp_followup_dynamic_date_denom']) {
            $followup_dynamic_date_denom->setValue($details['add_opp_followup_dynamic_date_denom']);
        } else {
            $followup_dynamic_date_denom->setValue(0);
        }
        $form->addGroup(array($followup_dynamic_date_denom), 'add_opp_followup_dynamic_date_denom', NULL);

        $form->assign_theme('form', $theme);
        $theme->assign('col1', $col1);
        $theme->assign('col2', $col2);
        $theme->assign('longfields', $longfields);
        $theme->assign('index', $index);
        $theme->assign('static_texts', array('after' => __('after'), 'current_date' => __('current date'), 'start_date' => __('start date')));
        return $theme->get_html('Opportunity');
    }

    public function add_rule()
    {
        $new_rule = array(
            'type' => '',
            'condition' => '',
            'action' => '',
            'details' => ''
        );
        if ($this->mode == 'admin') {
            $rules = Variable::get('telemarketing_default_rules');
            $rules[] = $new_rule;
            Variable::set('telemarketing_default_rules', $rules);
        } else {
            Telemarketing_CallCampaigns_RulesCommon::update_rules($this->campaign['id'], false, $new_rule);
        }
    }

    public function save_rules($values)
    {
        $rules = array();
        $per_rule = array();
        foreach ($values as $key => $group) {
            foreach ($group as $index => $value) {
                if (!isset($per_rule[$index])) {
                    $per_rule[$index] = array();
                }
                $per_rule[$index][$key] = $value;
            }
        }
        foreach ($per_rule as $index => $submitted_rule) {
            $rule = array(
                'type' => '',
                'condition' => '',
                'action' => ''
            );
            $details = array();
            foreach ($submitted_rule as $key => $value) {
                if (starts_with($key, 'callcampaign_rules')) {
                    if (ends_with($key, '_type')) {
                        $rule['type'] = $value;
                    } else if (ends_with($key, '_condition_0')) {
                        $rule['condition'] = $value . $rule['condition'];
                    } else if (ends_with($key, '_action_0')) {
                        $rule['action'] = $value . $rule['action'];
                    } else if (preg_match("/_condition_[0-9]+$/", $key)) {
                        $rule['condition'] = $rule['condition'] . ':' . $value;
                    } else if (preg_match("/_action_[0-9]+$/", $key)) {
                        $rule['action'] = $rule['action'] . ':' . $value;
                    }
                } else {
                    $details[$key] = $value;
                }
            }
            $rule['details'] = $details;
            $rules[$index] = $rule;
        }
        if ($this->mode == 'admin') {
            Variable::set('telemarketing_default_rules', $rules);
        } else {
            foreach ($rules as $index => $rule) {
                Telemarketing_CallCampaigns_RulesCommon::update_rules($this->campaign['id'], $index, $rule);
            }
        }
        Base_StatusBarCommon::message(__('Rules saved'));
    }

    public function delete_rule($index)
    {
        if ($this->mode == 'admin') {
            $rules = Variable::get('telemarketing_default_rules');
            unset($rules[$index]);
            Variable::set('telemarketing_default_rules', $rules);
            Variable::delete('telemarketing_default_rules_details_' . $index, false);
        } else {
            Telemarketing_CallCampaigns_RulesCommon::delete_rule($this->campaign['id'], $index);
        }
    }
}
