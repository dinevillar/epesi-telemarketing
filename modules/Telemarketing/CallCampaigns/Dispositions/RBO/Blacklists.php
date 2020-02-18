<?php

/**
 * User: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * Date: 2/10/20
 * Time: 1:21 AM
 */
class Telemarketing_CallCampaigns_Dispositions_RBO_Blacklists extends RBO_Recordset
{
    const TABLE_NAME = 'callcampaigns_blacklist';

    public function table_name()
    {
        return self::TABLE_NAME;
    }

    public function fields()
    {
        $fields = array();

        $lead = new CRM_Contacts_RBO_CompanyOrContact(_M('Lead'));
        $lead->set_visible()->set_required()->set_filter();
        $fields[] = $lead;

        $reason = new RBO_Field_LongText(_M('Reason'));
        $fields[] = $reason;

        $disposition = new RBO_Field_Select(
            _M('Disposition'),
            Telemarketing_CallCampaigns_Dispositions_RBO_Status::TABLE_NAME,
            ['Lead', 'Disposition', 'Call Campaign']
        );
        $disposition->set_visible()->set_filter();
        $fields[] = $disposition;

        $employee = new RBO_Field_Calculated(_M('Blacklisted By'));
        $employee->set_visible()->set_filter();
        $fields[] = $employee;

        $timestamp = new RBO_Field_Calculated(_M('Timestamp'));
        $timestamp->set_visible()->set_filter();
        $fields[] = $timestamp;

        return $fields;
    }

    public function display_blacklisted_by($record)
    {
        $r = Utils_RecordBrowserCommon::get_record('contact', $record['created_by']);
        return CRM_ContactsCommon::contact_format_default($r);
    }

    function QFfield_record_id($form, $field, $label, $mode, $default, $args, $rb_obj)
    {
        CRM_ContactsCommon::QFfield_company_contact($form, $field, $label, $mode, $default, $args, $rb_obj);
    }

    public function display_timestamp($record)
    {
        return $record[':created_on'];
    }

}
