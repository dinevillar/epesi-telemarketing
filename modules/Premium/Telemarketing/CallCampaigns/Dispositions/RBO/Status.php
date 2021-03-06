<?php

/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/10/20
 * @Time: 7:40 AM
 */
class Premium_Telemarketing_CallCampaigns_Dispositions_RBO_Status extends RBO_Recordset
{
    const TABLE_NAME = "callcampaigns_dispositions_status";

    function table_name()
    {
        return self::TABLE_NAME;
    }

    function fields()
    {
        $campaign = new RBO_Field_Select(
            _M('Call Campaign'),
            Premium_Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME,
            'Name'
        );
        $campaign->set_visible()->set_required()->set_filter();

        $disposition = new RBO_Field_CommonData(
            _M('Disposition'),
            'CallCampaign/Dispositions'
        );
        $disposition->set_visible()->set_required()->set_filter();

        $record_id = new RBO_Field_Integer(_M('Record ID'));
        $record_id->set_visible()->set_required();

        $record_type = new RBO_Field_Text(_M('Record Type'), 64);
        $record_type->set_required();

        $phonecall = new RBO_Field_Select(
            _M("Phonecall"),
            'phonecall',
            'Subject'
        );
        $phonecall->set_visible()->set_filter();

        $talktime = new RBO_Field_Integer(_M('Talk Time'));
        $talktime->set_visible();

        $skip_date = new RBO_Field_Timestamp(_M('Skip Date'));

        $timestamp = new RBO_Field_Timestamp(_M('Timestamp'));

        $telemarketer = new CRM_Contacts_RBO_Employee(_M("Telemarketer"));
        $telemarketer->set_multiple(false)->set_required()->set_visible()->set_filter();

        $call_back_time = new RBO_Field_Timestamp(_M('Call Back Time'));

        $locked_to = new RBO_Field_Integer(_M('Locked To'));

        return array(
            $campaign, $disposition, $record_id, $record_type, $phonecall, $talktime, $skip_date, $timestamp,
            $telemarketer, $call_back_time, $locked_to
        );
    }

//    function QFfield_phonecall($form, $field, $label, $mode, $default, $desc, $rb_obj)
//    {
//        if ($mode == "add") {
//            return;
//        }
//        Utils_RecordBrowserCommon::QFfield_select($form, $field, $label, $mode, $default, $desc, $rb_obj);
//        $form->freeze($field);
//    }

    function display_record_id($record, $nolink = false)
    {
        $r = Utils_RecordBrowserCommon::get_record($record['record_type'], $record['record_id']);
        if ($record['record_type'] == 'contact') {
            return CRM_ContactsCommon::contact_format_default($r);
        } else if ($record['record_type'] == 'company') {
            return CRM_ContactsCommon::company_format_default($r);
        }
    }

    function display_talk_time($record, $nolink = false)
    {
        if ($record['talk_time'] == 0) {
            return '---';
        }
        return $record['talk_time'] . ' second(s)';
    }
}

