<?php

/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/10/20
 * @Time: 7:40 AM
 */
class Telemarketing_CallCampaigns_RBO_Campaigns extends RBO_Recordset
{

    const TABLE_NAME = "callcampaigns";

    /**
     * String that represents recordset in database
     *
     * @return string String that represents recordset in database
     */
    function table_name()
    {
        return self::TABLE_NAME;
    }

    /**
     * Return list of fields in recordset.
     * @return array RBO_FieldDefinition
     */
    function fields()
    {
        $name = new RBO_Field_Text(_M("Name"), 64);
        $name->set_required()->set_visible();

        $date = new RBO_Field_Timestamp(_M("Start Date"));
        $date->set_required()->set_filter();

        $end_date = new RBO_Field_Timestamp(_M("End Date"));
        $end_date->set_filter();

        $timeless = new RBO_Field_Checkbox(_M("Timeless"));
        $timeless->set_filter();

        $duration = new RBO_Field_Calculated(_M("Duration"));
        $duration->set_visible();

        $status = new RBO_Field_CommonData(
            _M("Status"), "CRM/Status", true);
        $status->set_required()->set_visible()->set_filter();

        $permission = new RBO_Field_CommonData(
            _M("Permission"), "CRM/Access", false);
        $permission->set_required();

        $call_script = new RBO_Field_Select(
            _M("Call Script"),
            Telemarketing_CallScripts_RBO_Templates::TABLE_NAME,
            'Name',
            array('Telemarketing_CallCampaignsCommon', 'callscript_template_crits')
        );
        $call_script->set_required()->set_visible();

        $telemarketers = new CRM_Contacts_RBO_Employee(_M('Telemarketers'));
        $telemarketers->set_multiple(true)->set_required()->set_visible()->set_filter();

        $list_type = new RBO_Field_CommonData(_M("List Type"), 'CallCampaign/LeadListTypes');
        $list_type->set_required()->set_filter();

        $lead_list = new RBO_Field_Text(_M("Lead List"), 32);
        $lead_list->set_visible();

        return array($name, $date, $end_date, $timeless, $duration, $call_script,
            $telemarketers, $list_type, $lead_list, $status, $permission);
    }

    function display_name($record, $nolink = false)
    {
        return Utils_RecordBrowserCommon::create_linked_label_r(
            self::TABLE_NAME, 'Name', $record, $nolink
        );
    }

    function display_duration($record)
    {
        if ($record['timeless']) {
            return $record['start_date'] . " ~ (" . __("No End Date") . ")";
        } else {
            return $record['start_date'] . " ~ " . $record['end_date'];
        }
    }

    function QFfield_timeless($form, $field, $label, $mode, $default, $args, $rb_obj)
    {
        if ($mode === "view") {
            return;
        }
        Utils_RecordBrowserCommon::QFfield_checkbox($form, $field, $label, $mode, $default, $args, $rb_obj);
    }

    function QFfield_start_date($form, $field, $label, $mode, $default, $args, $rb_obj)
    {
        if ($mode === "view") {
            return;
        }
        Utils_RecordBrowserCommon::QFfield_timestamp($form, $field, $label, $mode, $default, $args, $rb_obj);
    }

    function QFfield_end_date($form, $field, $label, $mode, $default, $args, $rb_obj)
    {
        if ($mode === "view") {
            return;
        }
        Utils_RecordBrowserCommon::QFfield_timestamp($form, $field, $label, $mode, $default, $args, $rb_obj);
    }

    function QFfield_duration($form, $field, $label, $mode, $default, $args, $rb_obj)
    {
        if ($mode === "view") {
            $record = $rb_obj->record;
            Utils_RecordBrowserCommon::QFfield_text($form, $field, $label, $mode, $this->display_duration($record), $args, $rb_obj);
            $form->freeze($field);
        }
        return;
    }

    function QFfield_lead_list($form, $field, $label, $mode, $default, $args, $rb_obj)
    {
        $record = $rb_obj->record;
        $list_type = $record['list_type'] ?: "AP";
        $not_all = $list_type !== "AP" && $list_type !== "AC" && $list_type != "APC";
        if ($mode === 'add' || $mode === 'edit') {
            $criteria = Utils_RecordBrowserCommon::get_records(
                CRM_Criteria_RBO_Rules::TABLE_NAME,
                Telemarketing_CallCampaignsCommon::crm_criteria_callcampaign_crits()
            );
            $criteria_options = [
                '' => '---'
            ];
            foreach ($criteria as $criterion) {
                $criteria_options[$criterion['id']] = Utils_RecordBrowserCommon::create_default_linked_label(
                    CRM_Criteria_RBO_Rules::TABLE_NAME, $criterion['id'], true
                );
            }
            $form->addElement(
                'select',
                $field . "_crm_criteria",
                __("Criteria"),
                $criteria_options
            );
            if (ModuleManager::is_installed('Telemarketing/CallCampaigns/Premium/ListManager') >= 0) {
                Telemarketing_CallCampaigns_Premium_ListManagerCommon::QFfield_lead_list($form, $field, $label, $mode, $default, $args, $rb_obj);
            }
            $form->addElement(
                'text',
                $field,
                $label
            );
            if (!empty($default)) {
                if ($not_all) {
                    $form->setDefaults(array(
                        $field => $default,
                        $field . "_" . $list_type => $default
                    ));
                }
            }
        } else if ($not_all) {
            $form->addElement(
                'static',
                $field,
                $label
            );
            $form->setDefaults(array(
                $field => $this->display_lead_list($record)
            ));
        }
    }

    function display_lead_list($record)
    {
        if ($record['lead_list']) {
            return Utils_RecordBrowserCommon::create_default_linked_label(
                $record['list_type'],
                $record['lead_list']
            );
        } else {
            $record['list_type'];
        }
        return Utils_CommonDataCommon::get_value('CallCampaign/LeadListTypes/' . $record['list_type']);
    }

//    function display_status($record, $nolink, $desc)
//    {
//        $prefix = self::table_name() . '_leightbox';
//        $v = $record[$desc['id']];
//        if (!$v) $v = 0;
//        $status = Utils_CommonDataCommon::get_translated_array('CRM/Status');
//        if ($v >= 2 || $nolink) return $status[$v];
//        if (!Utils_RecordBrowserCommon::get_access(self::table_name(), 'edit', $record) && !Base_AclCommon::i_am_admin()) return $status[$v];
//        CRM_FollowupCommon::drawLeightbox($prefix);
//        if (isset($_REQUEST['form_name']) && $_REQUEST['form_name'] == $prefix . '_follow_up_form' && $_REQUEST['id'] == $record['id']) {
//            unset($_REQUEST['form_name']);
//            $v = $_REQUEST['closecancel'];
//            $action = $_REQUEST['action'];
//
//            $note = $_REQUEST['note'];
//            if ($note) {
//                if (get_magic_quotes_gpc())
//                    $note = stripslashes($note);
//                $note = str_replace("\n", '<br />', $note);
//                Utils_AttachmentCommon::add(
//                    self::table_name() . '/' . $record['id'], 0, Acl::get_user(), $note
//                );
//            }
//
//            if ($action == 'set_in_progress') $v = 1;
//            Utils_RecordBrowserCommon::update_record(self::table_name(), $record['id'], array('status' => $v));
//            if ($action == 'set_in_progress') location(array());
//
//            $values = $record;
//            $values['date_and_time'] = date('Y-m-d H:i:s');
//            $values['title'] = __('Follow-up') . ': ' . Telemarketing_CallCampaignsCommon::campaign_format($record, true);
//            $values['status'] = 0;
//
//            if ($action != 'none') {
//                $values['subject'] = __('Follow-up') . ': ' . Telemarketing_CallCampaignsCommon::campaign_format($record, true);
//                $values['follow_up'] = array(self::table_name(), $record['id'], Telemarketing_CallCampaignsCommon::campaign_format($record, true));
//                if ($action == 'new_task') Base_BoxCommon::push_module(Utils_RecordBrowser::module_name(), 'view_entry', array('add', null, array('title' => $values['subject'], 'permission' => $values['permission'], 'priority' => $values['priority'], 'description' => $values['description'], 'deadline' => date('Y-m-d H:i:s', strtotime('+1 day')), 'employees' => $values['employees'], 'customers' => $values['customer'], 'status' => 0, 'follow_up' => $values['follow_up'])), array('task'));
//                if ($action == 'new_phonecall') Base_BoxCommon::push_module(Utils_RecordBrowser::module_name(), 'view_entry', array('add', null, $values), array('phonecall'));
//                if ($action == 'new_meeting') Base_BoxCommon::push_module(Utils_RecordBrowser::module_name(), 'view_entry', array('add', null, array('title' => $values['subject'], 'permission' => $values['permission'], 'priority' => $values['priority'], 'description' => $values['description'], 'date' => date('Y-m-d'), 'time' => date('H:i:s'), 'duration' => 3600, 'status' => 0, 'employees' => $values['employees'], 'customers' => $values['customer'], 'follow_up' => $values['follow_up'])), array('crm_meeting'));
//                return false;
//            }
//
//            location(array());
//        }
//        if ($v == 0) {
//            return '<a href="javascript:void(0)" onclick="' . $prefix . '_set_action(\'set_in_progress\');' . $prefix . '_set_id(\'' . $record['id'] . '\');' . $prefix . '_submit_form();">' . $status[$v] . '</a>';
//        }
//        return '<a href="javascript:void(0)" class="lbOn" rel="' . $prefix . '_followups_leightbox" onMouseDown="' . $prefix . '_set_id(' . $record['id'] . ');">' . $status[$v] . '</a>';
//    }

//    function display_call_script($record, $nolink = false)
//    {
//        return Utils_RecordBrowserCommon::create_linked_label(
//            Telemarketing_CallScripts_RBO_Templates::TABLE_NAME,
//            array('Name'), $record['call_script'], $nolink
//        );
//    }
}
