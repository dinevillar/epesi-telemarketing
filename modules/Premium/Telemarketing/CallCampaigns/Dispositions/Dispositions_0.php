<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/17/20
 * @Time: 3:21 AM
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Premium_Telemarketing_CallCampaigns_Dispositions extends Module
{
    public function contact_addon($record)
    {
        $a = new Premium_Telemarketing_CallCampaigns_Dispositions_RBO_Status();
        $rb = $a->create_rb_module($this);
        $rb->set_button(false);
        $this->display_module($rb, array(
            array('record_id' => $record['id'], 'record_type' => 'contact', '!disposition' => ''),
            array('timestamp' => true, 'record_id' => false, 'call_back_time' => true)
        ), 'show_data');
    }

    public function company_addon($record)
    {
        $a = new Premium_Telemarketing_CallCampaigns_Dispositions_RBO_Status();
        $rb = $a->create_rb_module($this);
        $rb->set_button(false);
        $this->display_module($rb, array(
            array('record_id' => $record['id'], 'record_type' => 'company', '!disposition' => ''),
            array('timestamp' => true, 'record_id' => false, 'call_back_time' => true)
        ), 'show_data');
    }

    public function phonecall_addon($record)
    {
        $a = new Premium_Telemarketing_CallCampaigns_Dispositions_RBO_Status();
        $rb = $a->create_rb_module($this);
        $rb->set_button(false);
        $this->display_module($rb, array(
            array('phonecall' => $record['id'], '!disposition' => ''),
            array('timestamp' => true, 'phonecall' => false, 'record_id' => false, 'call_back_time' => true)
        ), 'show_data');
    }

    public function disposition_addon($record)
    {
        $a = new Premium_Telemarketing_CallCampaigns_Dispositions_RBO_Status();
        $rb = $a->create_rb_module($this);
        $rb->set_button(false);
        $this->display_module($rb, array(
            array('call_campaign' => $record['id'], '!disposition' => ''),
            array('call_campaign' => false)
        ), 'show_data');
    }

    public function summary_addon($record)
    {
        $disp_table = Premium_Telemarketing_CallCampaigns_Dispositions_RBO_Status::TABLE_NAME;
        $telemarketers = DB::GetAll("SELECT DISTINCT(f_telemarketer) as `tele` FROM {$disp_table}_data_1 WHERE f_telemarketer IS NOT NULL AND f_call_campaign = " . $record['id']);
        $campaigns = new Premium_Telemarketing_CallCampaigns_RBO_Campaigns();
        $campaign = $campaigns->record_to_object($record);
        $summary = array('All' => Premium_Telemarketing_CallCampaignsCommon::get_summary($campaign, false, true));
        foreach ($telemarketers as $telemarketer) {
            $label = CRM_ContactsCommon::get_user_label($telemarketer['tele'], true);
            $summary[$label] = Premium_Telemarketing_CallCampaignsCommon::get_summary($campaign, $telemarketer['tele'], true);
        }
        $remain = array(
            "label" => __("Remaining Records"),
            "data" => Premium_Telemarketing_CallCampaignsCommon::count_remaining_records($record) . ' record(s)'
        );
        $theme = $this->init_module(Base_Theme::module_name());
        $theme->assign('summary', array_chunk($summary, 2, true));
        $theme->assign('remain', $remain);
        $theme->display('Summary');
    }
}
