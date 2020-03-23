<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/17/20
 * @Time: 9:49 AM
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Telemarketing_CallCampaigns_DispositionsCommon extends ModuleCommon
{
    public static function disposition_desc_callback($record, $nolink = false)
    {
        $call_campaign = Utils_RecordBrowserCommon::get_record(
            Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME,
            $record['call_campaign']
        );
        $disps = Utils_CommonDataCommon::get_array('CallCampaign/Dispositions');
        $label = __('Call Campaign') . ': ' . $call_campaign['name'] . " ";
        if ($record['disposition']) {
            $label .= "[" . CRM_ContactsCommon::company_contact_format_default($record['lead'], true);
            $label .= ", " . $disps[$record['disposition']];
        }
        $label .= "]";
        if ($nolink) {
            return $label;
        }
        return "<a" . Utils_RecordBrowserCommon::create_record_href(
                Telemarketing_CallCampaigns_Dispositions_RBO_Status::TABLE_NAME,
                $record['id']
            ) . '>' . $label . '</a>';
    }

    public static function summary_addon_label()
    {
        if (Base_AclCommon::check_permission(Telemarketing_CallCampaignsInstall::manage_permission)) {
            return array('label' => __('Statistics'));
        }
        return array('show' => false);
    }

    public static function disposition_addon_label()
    {
        if (Base_AclCommon::check_permission(Telemarketing_CallCampaignsInstall::manage_permission)) {
            return array('label' => __('Dispositions'));
        }
        return array('show' => false);
    }

    public static function blacklist_watchdog_label($rid = null, $events = array(), $details = true)
    {
        return Utils_RecordBrowserCommon::watchdog_label(
            Telemarketing_CallCampaigns_Dispositions_RBO_Blacklists::TABLE_NAME,
            __('Call Campaigns Blacklisted Records'),
            $rid,
            $events,
            false,
            $details
        );
    }

//    public static function phonecall_disposition_crits()
//    {
//
//    }

    public static function submit_blacklist($values, $mode)
    {
        if ($mode == 'add') {
            if (!isset($values['blacklisted_by'])) {
                $values['blacklisted_by'] = CRM_ContactsCommon::get_my_record()['id'];
            }
            if (!isset($values['timestamp'])) {
                $values['timestamp'] = date('Y-m-d H:i:s');
            }
        }
        return $values;
    }

    public static function campaign_get_summary($campaign, $emp = false, $translate_key = false)
    {
        $disposition_rbo = new Telemarketing_CallCampaigns_Dispositions_RBO_Status();
        $dispositions_records = $disposition_rbo->get_records([
            'call_campaign' => $campaign['id']
        ]);
        $c_dispositions = Utils_CommonDataCommon::get_array('CallCampaign/Dispositions');
        $dispositions = array();
        foreach ($c_dispositions as $k => $v) {
            if ($translate_key) {
                $dispositions[$v] = 0;
            } else {
                $dispositions[$k] = 0;
            }
        }
        foreach ($dispositions_records as $d) {
            if (empty($d['disposition'])) continue;
            if ($emp) {
                if ($d['employee'] == $emp) {
                    if ($translate_key) {
                        $dispositions[$c_dispositions[$d['disposition']]]++;
                    } else {
                        $dispositions[$d['disposition']]++;
                    }
                }
            } else {
                if ($translate_key) {
                    $dispositions[$c_dispositions[$d['disposition']]]++;
                } else {
                    $dispositions[$d['disposition']]++;
                }
            }
        }
        $total = 0;
        foreach ($dispositions as $k => $v) {
            $total += $v;
        }
        $dispositions['Total'] = $total;
        return $dispositions;
    }

}

