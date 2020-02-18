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
        $date->set_required()->set_visible()->set_filter();

        $end_date = new RBO_Field_Timestamp(_M("End Date"));
        $end_date->set_visible()->set_filter();

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

        $lead_list = new Telemarketing_CallCampaigns_RBO_LeadsList(_M("Lead List"));
        $lead_list->set_required()->set_visible();

        return array($name, $date, $end_date, $call_script, $telemarketers, $lead_list, $status, $permission);
    }

    function display_name($record, $nolink = false)
    {
        return Utils_RecordBrowserCommon::create_linked_label_r(
            self::TABLE_NAME, 'Name', $record, $nolink
        );
    }

    function display_end_date($record)
    {
        if (!$record['end_date']) {
            return "[No End Date]";
        }
        return $record['end_date'];
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
