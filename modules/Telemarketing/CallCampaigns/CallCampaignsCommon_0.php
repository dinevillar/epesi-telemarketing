<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/17/20
 * @Time: 9:52 AM
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Telemarketing_CallCampaignsCommon extends ModuleCommon
{

    public static function menu()
    {
        return array(
            _M('Telemarketing') =>
                array('__submenu__' => 1, _M('Call Campaigns') => array('__weight__' => 1))
        );
    }

    public static function crm_criteria_callcampaign_crits()
    {
        return array(
            'recordset' => array('contact', 'company')
        );
    }

    public static function submit_call_campaign($values, $mode)
    {
        if ($mode === "editing" || $mode === "adding") {
            load_js("modules/" . Telemarketing_CallCampaignsInstall::module_name() . "/js/campaigns.js");
            eval_js("CallCampaigns.init();");
        }
        return $values;
    }

    public static function campaign_format($record, $nolink = false)
    {
        $t = "Call Campaign - " . $record['name'];
        if (!$nolink) {
            $t = ' < a href = "' . Utils_RecordBrowserCommon::create_record_href(
                    Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME, $record['id']
                ) . '" > ' . $t . '</a > ';
        }
        return $t;
    }

    public static function count_remaining_records($campaign)
    {
        return 0;
    }

    public static function get_summary($campaign, $tele = false, $translate_key = false)
    {
        $campaign_rbo = new Telemarketing_CallCampaigns_RBO_Campaigns();
        if (is_array($campaign)) {
            $campaign = $campaign_rbo->record_to_object($campaign);
        }
        $disp_rbo = new Telemarketing_CallCampaigns_Dispositions_RBO_Status();
        $dispositions = $disp_rbo->get_records(array('call_campaign' => $campaign->id));
        $c_dispositions = Utils_CommonDataCommon::get_array('CallCampaign/Dispositions');
        $summary = array();
        foreach ($c_dispositions as $k => $v) {
            if ($translate_key) {
                $summary[$v] = 0;
            } else {
                $summary[$k] = 0;
            }
        }
        foreach ($dispositions as $disposition) {
            if (empty($disposition->disposition)) continue;
            if ($tele) {
                if ($disposition->employee == $tele) {
                    if ($translate_key) {
                        $summary[$c_dispositions[$disposition->disposition]]++;
                    } else {
                        $summary[$disposition->disposition]++;
                    }
                }
            } else {
                if ($translate_key) {
                    $summary[$c_dispositions[$disposition->disposition]]++;
                } else {
                    $summary[$disposition->disposition]++;
                }
            }
        }
        $total = 0;
        foreach ($summary as $k => $v) {
            $total += $v;
        }
        $summary['Total'] = $total;
        return $summary;
    }
}
