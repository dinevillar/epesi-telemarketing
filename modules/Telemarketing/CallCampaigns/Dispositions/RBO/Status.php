<?php

/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/10/20
 * @Time: 7:40 AM
 */
class Telemarketing_CallCampaigns_Dispositions_RBO_Status extends RBO_Recordset
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
            Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME,
            'Name',
            array('Telemarketing_CallCampaignsCommon', 'call_campaign_crits')
        );
        $campaign->set_visible()->set_required()->set_filter();

        $status = new RBO_Field_CommonData(
            _M('Disposition'),
            'CallCampaign/Dispositions'
        );
        $status->set_visible()->set_required()->set_filter();

        $lead = new CRM_Contacts_RBO_CompanyOrContact(_M('Lead'));
        $lead->set_visible()->set_required()->set_filter();

        $skip_date = new RBO_Field_Timestamp(_M('Skip Date'));

        $timestamp = new RBO_Field_Calculated(_M('Timestamp'));
        $timestamp->set_visible()->set_filter();

        $telemarketer = new RBO_FieldDefinition(
            _M("Telemarketers"),
            'crm_contact',
            array(
                'field_type' => 'select',
                'crits' => array('Telemarketing_CallCampaignsCommon', 'telemarketer_disposition_crits'),
                'format' => array('CRM_ContactsCommon', 'contact_format_no_company')
            )
        );
        $telemarketer->set_required()->set_visible()->set_filter();

        $call_back_time = new RBO_Field_Timestamp(_M('Call Back Time'));

        $locked_to = new RBO_Field_Integer(_M('Locked To'));

        return array(
            $campaign, $status, $lead, $skip_date, $timestamp,
            $telemarketer, $call_back_time, $locked_to
        );
    }
}

