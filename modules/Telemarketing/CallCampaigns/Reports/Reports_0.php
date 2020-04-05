<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 4/2/2020
 * @Time: 10:53 PM
 */
defined("_VALID_ACCESS") || die();

class Telemarketing_CallCampaigns_Reports extends Module
{

    const MODE_TELEMARKETER = 'by_telemarketer';
    const MODE_CALL_CAMPAIGNS = 'by_call_campaign';

    private $rbr = null;
    private $dates = array();
    private $range_type = array();
    private $format = array();

    public function body()
    {
        $mode = $this->get_module_variable('report_mode', $_REQUEST['report_mode']);
        $this->set_module_variable('report_mode', $mode);
        $this->show_report();
    }

    public function show_report()
    {
        /* @var $rb_reports Utils_RecordBrowser_Reports */
        /* @var $form Libs_QuickForm */
        /* @var $reports_common Telemarketing_CallCampaigns_ReportsCommon */
        set_time_limit(0);
        $mode = $this->get_module_variable('report_mode', self::MODE_CALL_CAMPAIGNS);
        load_js('modules/' . self::module_name() . '/js/reports.js');
        $reports_common = Telemarketing_CallCampaigns_ReportsCommon::class;
        $rb_reports = $this->init_module(Utils_RecordBrowser_Reports::module_name());
        $this->rbr = $rb_reports;
        $form = $this->init_module(Libs_QuickForm::module_name());
        eval_js("CallCampaignManualReports.submit_form=function(){" . $form->get_submit_form_js() . "}");
        if ($mode == self::MODE_TELEMARKETER) {
            eval_js("CallCampaignManualReports.init_telemarketer();");
            $rb_reports->set_reference_record_display_callback(array('CRM_ContactsCommon', 'contact_format_no_company'));
        } else {
            eval_js("CallCampaignManualReports.init_call_campaigns();");
            $rb_reports->set_reference_record_display_callback(array('Telemarketing_CallCampaignsCommon',
                'display_call_campaign'));
        }

        $call_campaigns_rec = Utils_RecordBrowserCommon::get_records(
            Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME
        );

        $call_campaigns = array();
        foreach ($call_campaigns_rec as $call_campaign) {
            $call_campaigns[$call_campaign['id']] = $call_campaign['name'];
        }
        asort($call_campaigns);
        $call_campaigns = array('' => '---') + $call_campaigns;
        $form->addElement(
            'select',
            'campaign',
            __('Call Campaign'),
            $call_campaigns,
            array('id' => 'call_campaign_select')
        );

        $telemarketers = array();
        $selected_campaign = false;
        if ($mode == self::MODE_TELEMARKETER) {
            if ($form->validate()) {
                $values = $form->exportValues();
                if (isset($values['campaign']) && $values['campaign']) {
                    $selected_campaign = $values['campaign'];
                    $this->set_module_variable('selected_call_campaign', $selected_campaign);
                } else {
                    $this->unset_module_variable('selected_call_campaign');
                }
            }
            foreach ($call_campaigns_rec as $call_campaign) {
                if (!$selected_campaign || $selected_campaign == $call_campaign['id']) {
                    foreach ($call_campaign['telemarketers'] as $employee) {
                        if (!isset($telemarketers[$employee])) {
                            $telemarketers[$employee] = CRM_ContactsCommon::contact_format_no_company($employee, true);
                        }
                    }
                }
            }
            asort($telemarketers);
            $telemarketers = array('' => '---') + $telemarketers;
            $form->addElement(
                'select',
                'telemarketer',
                __('Telemarketer'),
                $telemarketers,
                array('id' => 'telemarketers_select')
            );
        }

        $rb_reports->set_categories($reports_common::get_report_categories());
        $rb_reports->set_summary('col', array('label' => __('Total')));
        $rb_reports->set_summary('row', array('label' => __('Total')));

        $date_range =
            $rb_reports->display_date_picker(
                array(
                    'date_range_type' => 'day',
                    'from_day' => date('Y-m-d'),
                    'to_day' => date('Y-m-d')
                ),
                $form
            );

        if ($mode == self::MODE_TELEMARKETER) {
            $crits = array();
            if (isset($date_range['other']['telemarketer']) && $date_range['other']['telemarketer']) {
                $crits['id'] = $date_range['other']['telemarketer'];
            } else {
                $t = array();
                foreach ($telemarketers as $id => $name) {
                    if ($id) {
                        $t[] = $id;
                    }
                }
                $crits['id'] = $t;
            }

            $reference_records = Utils_RecordBrowserCommon::get_records(
                'contact', $crits, array(),
                array('last_name' => 'ASC', 'first_name' => 'ASC'),
                array()
            );
        } else {
            if (isset($date_range['other']['campaign']) && $date_range['other']['campaign']) {
                $reference_records = Utils_RecordBrowserCommon::get_records(
                    Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME,
                    array('id' => $date_range['other']['campaign'])
                );
            } else {
                $reference_records = $call_campaigns_rec;
            }
        }

        $rb_reports->set_reference_records($reference_records);
        $rb_reports->set_format($reports_common::get_report_categories_format());
        $this->dates = $date_range['dates'];
        $this->range_type = $date_range['type'];
        $this->format = $reports_common::get_report_date_format($date_range['type']);

        if ($mode == self::MODE_TELEMARKETER) {
            $header = array('Telemarketer');
            $campaign_name = "";
            if ($selected_campaign = $this->get_module_variable('selected_call_campaign', false)) {
                foreach ($call_campaigns_rec as $call_campaign) {
                    if ($call_campaign['id'] == $selected_campaign) {
                        $campaign_name = " - " . $call_campaign['name'];
                        break;
                    }
                }
            }
            $pdf_title = __('Telemarketer Report%s, %s', array($campaign_name, date('Y-m-d H:i:s')));
            $pdf_filename = __('Telemarketer_Report%s_%s', array($campaign_name, date('Y_m_d__H_i_s')));
            $rb_reports->set_display_cell_callback(array($this, 'display_cells_by_telemarketer'));
        } else {
            $header = array('Call Campaigns');
            $pdf_title = __('Call Campaigns Report, %s', array(date('Y-m-d H:i:s')));
            $pdf_filename = __('Call_Campaigns_Report_%s', array(date('Y_m_d__H_i_s')));
            $rb_reports->set_display_cell_callback(array($this, 'display_cells_by_call_campaign'));
        }
        foreach ($this->dates as $v) {
            $header[] = date($this->format, $v);
        }

        $rb_reports->set_table_header($header);
        $rb_reports->set_pdf_title($pdf_title);
        $rb_reports->set_pdf_subject($rb_reports->pdf_subject_date_range());
        $rb_reports->set_pdf_filename($pdf_filename);
        $this->display_module($rb_reports);
    }

    public function display_cells_by_telemarketer($telemarketer)
    {
        /* @var $reports_common Telemarketing_CallCampaigns_ReportsCommon */
        $reports_common = Telemarketing_CallCampaigns_ReportsCommon::class;
        $cats = $reports_common::get_report_categories();
        $cells = array();
        $i = 0;
        foreach ($this->dates as $v) {
            $cells[$i] = array();
            $parse_dt = $reports_common::get_report_to_from_date($v, $this->range_type);
            $from_dt = $parse_dt['from'];
            $to_dt = $parse_dt['to'];
//            var_dump("From " . $from_dt->format("Y-m-d H:i:s"));
//            var_dump("To " . $to_dt->format("Y-m-d H:i:s"));
            foreach ($cats as $k => $cat) {
                if ($selected_call_campaign = $this->get_module_variable('selected_call_campaign', false)) {
                    if ($k === 'AVGTT') {
                        $cells[$i][$cat] = number_format($reports_common::compute_avg_talk_time(
                            $selected_call_campaign,
                            $telemarketer['id'],
                            false,
                            $from_dt,
                            $to_dt
                        ), 2);
                    } else {
                        $cells[$i][$cat] = $reports_common::count_dispositions(
                            $selected_call_campaign,
                            $telemarketer['id'],
                            $k,
                            $from_dt,
                            $to_dt
                        );
                    }
                } else {
                    $call_campaigns = Utils_RecordBrowserCommon::get_records(
                        Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME
                    );
                    foreach ($call_campaigns as $call_campaign) {
                        if ($k === 'AVGTT') {
                            $cells[$i][$cat] += $reports_common::compute_avg_talk_time(
                                $call_campaign,
                                $telemarketer['id'],
                                false,
                                $from_dt,
                                $to_dt
                            );
                        } else {
                            $cells[$i][$cat] += $reports_common::count_dispositions(
                                $call_campaign,
                                $telemarketer['id'],
                                $k,
                                $from_dt,
                                $to_dt
                            );
                        }
                    }
                    if ($k === 'AVGTT') {
                        $cells[$i][$cat] = number_format($cells[$i][$cat], 2);
                    }
                }
            }
            $i++;
        }
//        var_dump($cells);
        return $cells;
    }

    public function display_cells_by_call_campaign($call_campaign)
    {
        /* @var $reports_common Telemarketing_CallCampaigns_ReportsCommon */
        $reports_common = Telemarketing_CallCampaigns_ReportsCommon::class;
        $cats = $reports_common::get_report_categories();
        $cells = array();
        $i = 0;
        foreach ($this->dates as $v) {
            $cells[$i] = array();
            $parse_dt = $reports_common::get_report_to_from_date($v, $this->range_type);
            $from_dt = $parse_dt['from'];
            $to_dt = $parse_dt['to'];
//            var_dump("From " . $from_dt->format("Y-m-d H:i:s"));
//            var_dump("To " . $to_dt->format("Y-m-d H:i:s"));
            foreach ($cats as $k => $cat) {
                if ($k === 'AVGTT') {
                    $cells[$i][$cat] = number_format($reports_common::compute_avg_talk_time(
                        $call_campaign,
                        false,
                        false,
                        $from_dt,
                        $to_dt
                    ), 2);
                } else {
                    $cells[$i][$cat] = $reports_common::count_dispositions(
                        $call_campaign,
                        false,
                        $k,
                        $from_dt,
                        $to_dt
                    );
                }
            }
            $i++;
        }
//        var_dump($cells);
        return $cells;
    }
}
