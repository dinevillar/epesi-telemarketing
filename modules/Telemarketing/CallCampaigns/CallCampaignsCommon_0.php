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

    public static function settings_addon_label($campaign)
    {
        if ($campaign['created_on'] === Acl::get_user() || Base_AclCommon::check_permission('Manage Call Campaigns')) {
            return array('label' => __('Settings'));
        }
        return array('show' => false);
    }

    public static function admin_caption()
    {
        return array('label' => __('Call Campaigns'), 'section' => __('Features Configuration'));
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
        } else if ($mode == 'added') {
            self::update_settings($values['id']);
        }
        return $values;
    }

    public static function get_active_campaigns()
    {
        return Utils_RecordBrowserCommon::get_records(
            Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME,
            array(
                "!status" => array(3, 4),
                "(timeless" => true,
                "|>end_date" => date("Y-m-d H:i:s")
            )
        );
    }

    public static function update_settings($campaign_id, $settings = false)
    {
        if (!$settings) {
            $settings = Variable::get('telemarketing_default_settings', false);
        }
        $settings_table = Telemarketing_CallCampaignsInstall::SETTINGS_TABLE;
        $sql = 'INSERT INTO `' . $settings_table . '` ' .
            '(`call_campaign_id`, `settings`) ' .
            'VALUES(%1$d, \'%2$s\') ON DUPLICATE KEY UPDATE ' .
            '`settings`=\'%2$s\'';
        $formatted_sql = sprintf($sql,
            $campaign_id,
            base64_encode(serialize($settings))
        );
        DB::Execute($formatted_sql);
    }

    public static function get_settings($campaign_id, $key = false)
    {
        $settings_table = Telemarketing_CallCampaignsInstall::SETTINGS_TABLE;
        $settings_row = DB::GetRow("SELECT * FROM `{$settings_table}` WHERE `call_campaign_id`={$campaign_id}");
        if (empty($settings_row)) {
            $settings = Variable::get('telemarketing_default_settings', false);
        } else {
            $settings = unserialize(base64_decode($settings_row['settings']));
        }
        if ($key) {
            if (isset($settings[$key])) {
                return $settings[$key];
            }
            return false;
        }
        return $settings;
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
                if ($disposition->telemarketer == $tele) {
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
