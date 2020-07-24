<?php

/**
 * User: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * Date: 2/10/20
 * Time: 1:21 AM
 */
class Premium_Telemarketing_CallCampaigns_Dispositions_RBO_Blacklists extends RBO_Recordset
{
    const TABLE_NAME = 'callcampaigns_blacklist';

    public function table_name()
    {
        return self::TABLE_NAME;
    }

    public function fields()
    {
        $fields = array();

        $record_id = new RBO_Field_Integer(_M('Record ID'));
        $record_id->set_visible()->set_required();
        $fields[] = $record_id;

        $record_type = new RBO_Field_Text(_M('Record Type'), 64);
        $record_type->set_required();
        $fields[] = $record_type;

        $reason = new RBO_Field_LongText(_M('Reason'));
        $fields[] = $reason;

        $disposition = new RBO_Field_Select(
            _M('Disposition'),
            Premium_Telemarketing_CallCampaigns_Dispositions_RBO_Status::TABLE_NAME,
            ['Record Type', 'Record ID', 'Disposition', 'Call Campaign']
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

    public function display_record_id($record)
    {
        $id = 'P:' . $record['record_id'];
        if ($record['record_type'] == 'company') {
            $id = 'C:' . $record['record_id'];
        }
        return CRM_ContactsCommon::autoselect_company_contact_format($id);
    }

    public function display_blacklisted_by($record)
    {
        $r = Utils_RecordBrowserCommon::get_record('contact', $record['created_by']);
        return CRM_ContactsCommon::contact_format_default($r);
    }

    function QFfield_record_type()
    {
        return false;
    }

    function QFfield_record_id($form, $field, $label, $mode, $default, $args, $rb_obj)
    {
        CRM_ContactsCommon::QFfield_company_contact($form, $field, $label, $mode, $default, $args, $rb_obj);
    }

    function QFfield_timestamp($form, $field, $label, $mode, $default)
    {
        if ($mode == 'view') {
            $form->addElement('static', $field, $label);
            $form->setDefaults(array(
                $field => $default
            ));
        }
    }

}
