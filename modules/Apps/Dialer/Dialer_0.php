<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 3/11/20
 * @Time: 1:00 PM
 */

defined("_VALID_ACCESS") || die('Direct access forbidden');

class Apps_Dialer extends Module
{
    private $campaign;

    /**
     * @var Libs_QuickForm
     */
    private $form;

    /**
     * @var Base_Theme
     */
    private $theme;

    private $record_type;
    private $record;
    private $product;
    private $disposition;
    private $mode;
    private $error;
    private $new_record = false;

    /**
     * @param bool $campaign
     * @throws Exception
     */
    public function body($campaign = false)
    {
        if ($this->is_back()) {
            Base_BoxCommon::pop_main();
            return;
        }

        Base_HelpCommon::screen_name('call_campaign_dialer');
        $this->init_campaign_select_dialog(!$campaign);
        if ($campaign) {
            if (is_numeric($campaign)) {
                $campaign = Utils_RecordBrowserCommon::get_record(Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME,
                    $campaign);
            }
            if ($campaign[':active'] == 1) {
                Base_ThemeCommon::load_css('Utils_RecordBrowser', 'View_entry');
                Base_ThemeCommon::load_css('Base_StatusBar');
                load_css('modules/' .
                    Telemarketing_CallScripts::module_name() .
                    '/callscript-ck/plugin.css'
                );
                load_js('modules/' .
                    Telemarketing_CallScripts::module_name() .
                    '/js/callscripts.js'
                );
                load_js('modules/' . self::module_name() . '/js/autodivscroll.js');
                load_js('modules/' . self::module_name() . '/js/dialer.js');

                $this->campaign = $campaign;
                eval_js('Dialer.callcampaign=' . $campaign['id']);
                if ($cl_rules = Telemarketing_CallCampaigns_RulesCommon::match_rules(
                    $this->campaign, 'Record', 'Callback', 'Flagged'
                )) {
                    $cl_dispos = array();
                    foreach ($cl_rules as $cl_rule) {
                        $condition = explode(':', $cl_rule['condition']);
                        if (isset($condition[1])) {
                            $cl_dispos[] = $condition[1];
                        }
                    }
                    if (!empty($cl_dispos)) {
                        eval_js('Dialer.cldispositions=' . json_encode($cl_dispos) . ';');
                    }
                }
                eval_js_once('Dialer.web_phone_attach();');
                if ($campaign['product']) {
                    $products_rbo = new Telemarketing_Products_RBO_Products();
                    $this->product = $product = $products_rbo->get_record($campaign['product']);
                }

                $this->build_dialer_form();

                if ($this->isset_module_variable('dialer_disposition')) {
                    $disp_id = $this->get_module_variable('dialer_disposition');
                    $this->disposition = Utils_RecordBrowserCommon::get_record(
                        Telemarketing_CallCampaigns_Dispositions_RBO_Status::TABLE_NAME,
                        $disp_id
                    );
                }

                if ($this->isset_module_variable('dialer_current_record')) {
                    $current_record_id = $this->get_module_variable('dialer_current_record');
                    $current_record_type = $this->get_module_variable('dialer_current_record_type');
                    $this->record = Utils_RecordBrowserCommon::get_record(
                        $current_record_type,
                        $current_record_id
                    );
                    $this->record_type = $current_record_type;
                }

                $values = array();
                if ($this->form->validate()) {
                    $this->error = array();
                    $values = $this->form->exportValues();
                    $this->process_dialer_form($values);
                }

                if (!$this->isset_module_variable('dialer_in_call')) {
                    Base_ActionBarCommon::add('back', __('Back'), Base_BoxCommon::pop_main_href());

                    $templates_table = Telemarketing_CallScripts_RBO_Templates::TABLE_NAME;
                    $template = Utils_RecordBrowserCommon::get_record($templates_table, $campaign['call_script']);
                    if (Utils_RecordBrowserCommon::get_access($templates_table, 'edit', $template)) {
                        Base_ActionBarCommon::add('edit', __('Edit Call Script'),
                            Utils_RecordBrowserCommon::create_record_href(
                                Telemarketing_CallScripts_RBO_Templates::TABLE_NAME,
                                $campaign['call_script'],
                                'edit'
                            ) . ' style="float:right"'
                        );
                    }

                    Base_ActionBarCommon::add('settings', __('Campaign Settings'),
                        Utils_RecordBrowserCommon::create_record_href(
                            Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME, $campaign['id']) .
                        ' style="float:right"'
                    );

                }

                $next_record = Apps_DialerCommon::get_next_record($campaign);
                if (!$next_record || empty($next_record)) {
                    Base_StatusBarCommon::message(__("The campaign lead list is empty."));
                    Base_BoxCommon::pop_main();
                    return;
                } else {
                    $this->record_type = $next_record['f_record_type'];
                    $this->record = Utils_RecordBrowserCommon::get_record(
                        $next_record['f_record_type'],
                        $next_record['f_record_id']
                    );

                    if (!$this->isset_module_variable('dialer_current_record') ||
                        $this->get_module_variable('dialer_current_record') != $this->record['id']
                    ) {
                        $this->new_record = true;
                        $this->set_module_variable('dialer_current_record', $this->record['id']);
                        $this->set_module_variable('dialer_current_record_type', $next_record['f_record_type']);
                        eval_js("Dialer.calledrecord=0;");
                    }
                    eval_js("Dialer.currentrecord=" . $this->record['id'] . ";");
                    eval_js("Dialer.currentrecordtype='" . $this->record_type . "';");

                    $location = Telemarketing_ContactLocalTimeCommon::get_contact_location($this->record);
                    $map_link = CRM_ContactsCommon::create_map_href($this->record);
                    $this->form->addElement(
                        'static',
                        'current_location',
                        __('Record Location'),
                        "<a $map_link>$location</a>"
                    );

                    $this->form->addElement(
                        'static',
                        'current_local_time',
                        __('Record Local Time'),
                        Telemarketing_ContactLocalTimeCommon::local_time(
                            $this->record,
                            $this->record_type
                        )
                    );

                    $record_info = $this->record;
                    $record_info['identifier'] = Utils_RecordBrowserCommon::create_default_linked_label(
                        $this->record_type,
                        $this->record['id'],
                        true
                    );
                    $record_info = json_encode($record_info);
                    eval_js("Dialer.recordinfo={$record_info};");

                    if (!$this->isset_module_variable('dialer_in_call')) {

                        Base_ActionBarCommon::add(
                            'save', __('Set Disposition'),
                            ' href="javascript:void(0);" onclick="Dialer.show_dialog(\'disposition\');"'
                        );

                        Base_ActionBarCommon::add(
                            Base_ThemeCommon::get_template_file(self::module_name(), 'icon.png'),
                            __('Call'), ' href="javascript:void(0);" onclick="Dialer.show_dialog(\'call\');"'
                        );

                        if (!$this->isset_module_variable('dialer_auto_call')) {
                            Base_ActionBarCommon::add(Base_ThemeCommon::get_template_file(self::module_name(), 'play.png'),
                                __('Start Auto-Call'),
                                ' href="javascript:void(0);" onclick="Dialer.start_auto_call();" style="float:right;"',
                                '',
                                30
                            );
                        } else {
                            Base_ActionBarCommon::add(
                                Base_ThemeCommon::get_template_file(self::module_name(), 'stop.png'),
                                __('Stop Auto-Call'),
                                ' href="javascript:void(0);" onclick="Dialer.stop_auto_call();" style="float:right;"',
                                '', 30

                            );
                        }

                        $count_next = Apps_DialerCommon::count_remaining_records($this->campaign);
                        $allow_skip = Telemarketing_CallCampaignsCommon::get_settings($this->campaign['id'], 'allow_skip');
                        if ($count_next > 1 && $allow_skip) {
                            Base_ActionBarCommon::add(Base_ThemeCommon::get_template_file(self::module_name(), 'next_record.png'), __('Next Record'),
                                ' href="javascript:void(0);" onclick="Dialer.next();"',
                                __('Go to the next record in the campaign list.'), 50
                            );
                        }
                    } else {
                        Base_ActionBarCommon::add(
                            'delete', __('End Call'),
                            ' href="javascript:void(0);" onclick="Dialer.end_call();"'
                        );
                        $start_icon = Base_ThemeCommon::get_template_file(self::module_name(), 'play.png');
                        $stop_icon = Base_ThemeCommon::get_template_file(self::module_name(), 'stop.png');
                        $auto_scroll_buttons = [
                            [
                                "id" => "auto_scroll_start_button",
                                "attrs" => Utils_TooltipCommon::open_tag_attrs("Start Auto Scroll") . " onclick=\"Dialer.autoscrollstart();\"",
                                "html" => "<img src=\"$start_icon\" width='24' height='24'/>"
                            ],
                            [
                                "id" => "auto_scroll_pause_button",
                                "attrs" => Utils_TooltipCommon::open_tag_attrs("Stop Auto Scroll") . " onclick=\"Dialer.autoscrollstop();\"",
                                "html" => "<img src=\"$stop_icon\" width='24' height='24'/>",
                                "styles" => "display:none;"
                            ]
                        ];
                        $values['auto_scroll_buttons'] = $auto_scroll_buttons;
                    }

                    $this->set_defaults($values);
                    $this->build_dialer_theme($values);
                    $this->theme->display('Dialer');
                    $this->render_dialog();
                }
            }
        }
    }

    public function init_campaign_select_dialog($show = false)
    {
        $active_campaigns = Telemarketing_CallCampaignsCommon::get_active_campaigns();
        $content = "<div style='width:50%;margin:50px auto;text-align:center;'>";
        $form = null;
        if (count($active_campaigns) > 0) {
            $form = $this->init_module(Libs_QuickForm::module_name(), null, "select_campaign_form");
            $campaign_selection = array("" => "--");
            foreach ($active_campaigns as $campaign) {
                $campaign_selection[$campaign['id']] = $campaign['name'];
            }
            $form->addElement("select", "call_campaign", __("Call Campaign"), $campaign_selection);
            $form->addRule(
                "call_campaign",
                __("Value required"),
                'required'
            );
            $form->addElement("submit", "", __("Select"));
            if ($form->validate()) {
                $campaign = $form->exportValue("call_campaign");
                Base_BoxCommon::push_module(self::module_name(), 'body', array($campaign));
                return;
            }
            ob_start();
            $form->display_as_column();
            $content .= ob_get_clean();
        } else {
            $create_campaign_href = Base_BoxCommon::create_href(
                $this,
                "Utils/RecordBrowserCommon",
                "view_entry",
                array("add"),
                array(Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME)
            );
            $content .= "<h2>" . __("There are currently no active campaigns.") . "</h2>";
            $content .= " <h3><a $create_campaign_href>Please click here to add a new call campaign.</a></h3>";
        }
        $content .= "</div>";
        Libs_LeightboxCommon::display("select_call_campaign", $content, __("Select Campaign"));
        if ($show) {
            Base_ActionBarCommon::add("folder", __("Select Campaign"), Libs_LeightboxCommon::get_open_href("select_call_campaign"));
            eval_js_once('leightbox_activate(\'select_call_campaign\');');
        } else {
            Base_ActionBarCommon::add("folder", __("Select Campaign"), Libs_LeightboxCommon::get_open_href("select_call_campaign") . " style=\"float:right;\"");
            Libs_LeightboxCommon::close('select_call_campaign');
        }
    }

    public function set_defaults($values = array())
    {
        $this->set_disposition();
        $this->set_form_defaults($values);
        $this->set_auto_scroll();
        $this->set_auto_call();
        if (ModuleManager::is_installed("Telemarketing/ContactLocalTime") >= 0) {
            $this->set_optimal_call_time();
        }
        eval_js("Dialer.init();");
    }

    public function set_disposition()
    {
        $disp_recs = Utils_RecordBrowserCommon::get_records(
            Telemarketing_CallCampaigns_Dispositions_RBO_Status::TABLE_NAME,
            array(
                'call_campaign' => $this->campaign['id'],
                'record_type' => $this->record_type,
                'record_id' => $this->record['id']
            )
        );
        $my = CRM_ContactsCommon::get_my_record();
        if (empty($disp_recs)) {
            Telemarketing_CallCampaigns_DispositionsCommon::clear_lock(
                $my['id'], $this->campaign['id']
            );
            $id = Utils_RecordBrowserCommon::new_record(
                Telemarketing_CallCampaigns_Dispositions_RBO_Status::TABLE_NAME,
                array(
                    'call_campaign' => $this->campaign['id'],
                    'disposition' => '',
                    'record_id' => $this->record['id'],
                    'record_type' => $this->record_type,
                    'locked_to' => $my['id']
                ));
            $this->set_module_variable('dialer_disposition', $id);
            $this->disposition = Utils_RecordBrowserCommon::get_record(
                Telemarketing_CallCampaigns_Dispositions_RBO_Status::TABLE_NAME,
                $id
            );
        } else {
            $rec = array_pop($disp_recs);
            if ($rec['locked_to'] != $my['id']) {
                $rec['locked_to'] = $my['id'];
                Utils_RecordBrowserCommon::update_record(
                    Telemarketing_CallCampaigns_Dispositions_RBO_Status::TABLE_NAME, $rec['id'], $rec
                );
            }
            $this->set_module_variable('dialer_disposition', $rec['id']);
            $this->disposition = Utils_RecordBrowserCommon::get_record(
                Telemarketing_CallCampaigns_Dispositions_RBO_Status::TABLE_NAME,
                $rec['id']
            );
        }
    }

    public function set_form_defaults($values = array())
    {
        if (empty($values) || $this->new_record) {
            $values['disposition'] = 'none';
            $values['cl_timestamp'] = date('Y-m-d H:i:s', strtotime('+1 day'));
        }

        $phone_qf = $this->form->getElement('phone');

        $phones = array();
        if ($this->record['work_phone']) {
            $phones['work_phone'] = Apps_DialerCommon::format_phone($this->record, 'work_phone');
        }
        if ($this->record['home_phone']) {
            $phones['home_phone'] = Apps_DialerCommon::format_phone($this->record, 'home_phone');
        }
        if ($this->record['mobile_phone']) {
            $phones['mobile_phone'] = Apps_DialerCommon::format_phone($this->record, 'mobile_phone');
        }
        if ($this->record['phone']) {
            $phones['phone'] = Apps_DialerCommon::format_phone($this->record, 'phone');
        }

        foreach ($phones as $k => $phone) {
            $phone_qf->addOption(
                $phone,
                $k
            );
        }

        if ($this->record_type == 'contact' && count($phones) > 1) {
            $val = $values['phone'] ? $values['phone'] : 'work_phone';
            if (!$this->isset_module_variable('dialer_in_call')) {
                $prio['work_phone'] = Telemarketing_CallCampaignsCommon::get_settings($this->campaign['id'], 'prio_work');
                $prio['home_phone'] = Telemarketing_CallCampaignsCommon::get_settings($this->campaign['id'], 'prio_home');
                $prio['mobile_phone'] = Telemarketing_CallCampaignsCommon::get_settings($this->campaign['id'], 'prio_mobile');
                $high_key = 4;
                $discon_phones = array();
                if ($this->dialer_wnd_multiple) {
                    $phonecalls = $this->disposition['phonecall'];
                    if (count($phonecalls)) {
                        foreach ($phonecalls as $phonecall_id) {
                            $phonecall = Utils_RecordBrowserCommon::get_record('phonecall', $phonecall_id);
                            switch ($phonecall['phone']) {
                                case 1:
                                    $discon_phones[] = 'mobile_phone';
                                    break;
                                case 2:
                                    $discon_phones[] = 'work_phone';
                                    break;
                                case 3:
                                    $discon_phones[] = 'home_phone';
                                    break;
                            }
                        }
                    }
                }
                foreach ($prio as $k => $v) {
                    if (isset($phones[$k]) && $v < $high_key && array_search($k, $discon_phones) === false) {
                        $high_key = $v;
                        $val = $k;
                    }
                }
            }
            $phone_qf->setSelected($val);
        }

        $dialing_method = Base_User_SettingsCommon::get_users_settings('CRM_Common', 'method');
        $this->form->getElement('dialer')->setSelected($dialing_method);
        $this->form->getElement('disposition')->setSelected($values['disposition']);
        $this->form->getElement('log_text')->setValue('');
    }

    public function set_auto_scroll()
    {
        $auto_scroll = Telemarketing_CallCampaignsCommon::get_settings(
            $this->campaign['id'],
            'auto_scroll'
        );
        $auto_scroll_speed = Telemarketing_CallCampaignsCommon::get_settings(
            $this->campaign['id'],
            'auto_scroll_speed'
        );
        if ($auto_scroll) {
            eval_js("Dialer.autoscrollvalue={$auto_scroll_speed};");
        }
    }

    public function set_auto_call()
    {
        if ($this->isset_module_variable('dialer_auto_call')) {
            eval_js('Dialer.autocall=true;');
        } else {
            eval_js('Dialer.autocall=false;');
        }
        $auto_call_delay = Telemarketing_CallCampaignsCommon::get_settings(
            $this->campaign['id'],
            'auto_call_delay'
        );
        eval_js("Dialer.autocalldelay={$auto_call_delay};");
    }

    public function set_optimal_call_time()
    {
        $filter = Telemarketing_CallCampaignsCommon::get_settings(
            $this->campaign['id'],
            'filter_not_optimal_call_time'
        );
        if (!$filter && isset($this->record['local_time'])) {
            $optimal_call_time_start_f = Telemarketing_CallCampaignsCommon::get_settings(
                $this->campaign['id'],
                'optimal_call_time_start'
            );
            $optimal_call_time_end_f = Telemarketing_CallCampaignsCommon::get_settings(
                $this->campaign['id'],
                'optimal_call_time_end'
            );
            if ($optimal_call_time_start_f && $optimal_call_time_end_f) {
                eval_js('Dialer.checkoptimal=true');
            } else {
                eval_js('Dialer.checkoptimal=false');
            }
        } else {
            eval_js('Dialer.checkoptimal=false');
        }
    }

    public function build_dialer_form()
    {
        $this->form = $this->init_module(Libs_QuickForm::module_name());

        $this->form->addElement(
            'hidden',
            'mode',
            '',
            array('id' => "dialer_hidden_mode")
        );

        $this->form->addElement(
            'hidden',
            'talktime',
            '',
            array('id' => 'dialer_hidden_talktime')
        );

        $this->form->addElement(
            'select',
            'dialer',
            __('Select Dialer'),
            Apps_DialerCommon::get_dialing_methods(),
            array('id' => 'dialer_select', 'style' => 'height:100%;')
        );

        $this->form->addElement(
            'timestamp',
            'cl_timestamp',
            __('Call Back Time')
        );

        $call_dispositions['none'] = __('None');
        $call_dispositions = $call_dispositions +
            Utils_CommonDataCommon::get_array(
                'CallCampaign/Dispositions'
            );
        $this->form->addElement(
            'select',
            'disposition',
            __('Disposition'),
            $call_dispositions
        );

        $this->form->addElement(
            'select',
            'phone',
            __('Select Phone Number'),
            array()
        );

        $this->form->addElement(
            'textarea',
            'log_text',
            __("Quick Note"),
            array(
                'style' => 'height:80px;resize:vertical;',
                'maxlength' => '250'
            )
        );

        eval_js("Dialer.submit_form=function(){" . $this->form->get_submit_form_js() . "}");
    }

    public function build_dialer_theme($values = array())
    {
        $this->theme = $this->init_module(Base_Theme::module_name(), null, 'dialer_theme');
        $cs = $this->get_callscript_content();
        $campaign = $this->campaign;
        $campaign['icon'] = Base_ThemeCommon::get_template_file(
            Telemarketing_CallCampaigns::module_name(),
            'icon.png'
        );
        $this->theme->assign('callcampaign', $campaign);
        $this->theme->assign('callscript', $cs['callscript']);
        $this->theme->assign('pagination', $cs['pagination']);

        $this->theme->assign('record_info', $this->get_record_info());
        $this->theme->assign('product_info', $this->get_product_info());
        $this->theme->assign('record_notes', $this->get_record_notes());
        $auto_scroll = Telemarketing_CallCampaignsCommon::get_settings(
            $this->campaign['id'],
            'auto_scroll'
        );
        $this->theme->assign('auto_scroll', $auto_scroll);
        $this->theme->assign('mode', $this->mode);
        $this->theme->assign('error', $this->error);
        $this->theme->assign('dialog_href', Libs_LeightboxCommon::get_open_href('dialer_dialog'));
        $static_texts = array(
            "record_info" => __('Record Information'),
            "call_script" => __('Call Script'),
            "product_info" => __('Product Information'),
            "call_disposition" => __('Call Disposition'),
            "dialer" => __('Dialer'),
            "call_logs" => __('Notes And Documents'),
            'auto_scroll' => __('Auto Scroll'),
            'start' => __('Start'),
            'stop' => __('Stop'),
            'cancel' => __('Cancel'),
            'end_call_save' => __('End Call and Save'),
            'call' => __('Call'),
            'save' => __('Save'),
            'edit' => __('Edit'),
            'add' => __('Add'),
            'next' => __('Next Record'),
            'end_call' => __('End Call'),
            'error_closing' => Libs_QuickFormCommon::get_error_closing_button(),
            'seconds' => __('seconds'),
            'auto_call' => __('Auto-call'),
            'click_cancel' => __('Click here to cancel'),
            'source' => __('Source')
        );
        $this->theme->assign('static_texts', $static_texts);
        foreach ($values as $k => $v) {
            $this->theme->assign($k, $v);
        }

        $renderer = new HTML_QuickForm_Renderer_TCMSArraySmarty();
        $this->form->accept($renderer);
        $form_data = $renderer->toArray();
        $this->theme->assign(
            'alt_form_open',
            $form_data['javascript'] . '<form ' . $form_data['attributes'] . ' style="height:100%;">' . $form_data['hidden'] . "\n"
        );
    }

    public function switch_record_mode()
    {
        if ($this->get_module_variable("dialer_record_mode", "view") === "view") {
            $this->set_module_variable("dialer_record_mode", "edit");
        } else {
            $this->set_module_variable("dialer_record_mode", "view");
        }
    }

    public function get_record_info()
    {
        $mode = $this->get_module_variable("dialer_record_mode", "view");
        if ($mode === "edit") {
            Base_ActionBarCommon::clean();
            eval_js("jQuery('#rb_cancel_action').hide()");
            Base_ActionBarCommon::add("back", __("Cancel"), $this->create_callback_href(array($this, 'switch_record_mode')));
        } else {
            if (Utils_RecordBrowserCommon::get_access($this->record_type, 'edit', $this->record)) {
                Base_ActionBarCommon::add('edit', __('Edit Record'),
                    $this->create_callback_href(array($this, "switch_record_mode")) . ' style="float:right"'
                );
            }
        }
        /**
         * @var $record_rb Utils_RecordBrowser
         */
        $record_rb = $this->init_module(
            Utils_RecordBrowser::module_name(),
            [$this->record_type],
            'dialer_record_rb'
        );
        $record_rb->disable_headline();
        $record_rb->disable_quickjump();
        $record_info = $this->get_html_of_module($record_rb, [
            $mode,
            $this->record,
            [],
            $mode === "edit"
        ], 'view_entry');
        return $record_info;
    }

    public function get_record_notes()
    {
        $notes = $this->init_module(Utils_Attachment::module_name());
        $record_rb = $this->init_module(
            Utils_RecordBrowser::module_name(),
            [$this->record_type],
            'dialer_record_rb_notes'
        );
        return $this->get_html_of_module($notes, [
            $this->record,
            $record_rb,
            null
        ]);
    }

    public function get_product_info()
    {
        if (isset($this->product) && $this->product) {
            $product_info = array(
                __('Name') => $this->product['name'],
                __('Code') => $this->product['code'],
                __('Retail Sales Price') => Utils_CurrencyFieldCommon::format(
                    $this->product['retail_sales_price']
                ),
                __('Wholesale Price') => Utils_CurrencyFieldCommon::format(
                    $this->product['wholesale_price']
                ),
                __('Description') => $this->product['description']
            );
            if ($this->product['vendor']) {
                $product_info[__('Vendor')] = CRM_ContactsCommon::display_company_contact(
                    $this->product, false, array('id' => 'vendor')
                );
            }
            if ($this->product['producer']) {
                $product_info[__('Producer')] = CRM_ContactsCommon::display_company(
                    $this->product, false, array('id' => 'producer')
                );
            }
            return $product_info;
        }
        return false;
    }

    public function get_callscript_content()
    {
        $callscript = Utils_RecordBrowserCommon::get_record(
            Telemarketing_CallScripts_RBO_Templates::TABLE_NAME,
            $this->campaign['call_script']
        );
        $callscript_paginator = Telemarketing_CallScriptsCommon::get_paginator_html($this, $callscript);
        $first_content = Utils_MergeFieldsCommon::parse_fields(
            $this->record_type,
            $this->record,
            $callscript['content']
        );
        $first_content = Utils_MergeFieldsCommon::parse_fields(
            'contact',
            CRM_ContactsCommon::get_my_record(),
            $first_content,
            'emp'
        );

        $pageCache = array('1' => $first_content);
        $callscript['content'] = $first_content;
        $callscript_pages = Telemarketing_CallScriptsCommon::get_callscript_page($callscript);
        if (count($callscript_pages)) {
            foreach ($callscript_pages as $k => $v) {
                $target_content = Utils_MergeFieldsCommon::parse_fields(
                    $this->record_type,
                    $this->record,
                    $v['content']
                );
                $target_content = Utils_MergeFieldsCommon::parse_fields(
                    'contact',
                    CRM_ContactsCommon::get_my_record(),
                    $target_content,
                    'emp'
                );
                $pageCache[$v['page']] = $target_content;
            }
        }
        $jsonPageCache = json_encode((object)$pageCache);

        eval_js("CallScripts.pageCache = {$jsonPageCache};");
        eval_js("CallScripts.mode = 'dialer';");
        eval_js("CallScripts.contentContainer = '#callscript_content';");
        eval_js("CallScripts.dialerInit();");

        return array(
            'callscript' => $callscript,
            'pagination' => $callscript_paginator
        );
    }

    public function render_dialog()
    {
        $dialog_theme = $this->init_module(
            Base_Theme::module_name(), null, 'dialer_dialog_theme'
        );
        $this->form->assign_theme('form', $dialog_theme);
        $dialog_content = $dialog_theme->get_html('Dialog');
        $dialog_name = "dialer_dialog";
        Libs_LeightboxCommon::display(
            $dialog_name,
            $dialog_content,
            '<span id="dialer_dialog_header"></span>',
            0,
            [
                'width' => "50%",
                'height' => "30%"
            ]
        );
        eval_js("Dialer.dialogname=\"$dialog_name\";");
    }

    public function process_dialer_form($values)
    {
        $mode = $values['mode'];
        $this->mode = $mode;
        switch ($mode) {
            case 'call':
                $this->call_record($values);
                break;
//            case 'end_call_save':
//                $this->end_call_save_record($values);
//                break;
            case 'end_call':
                $this->end_call_record($values);
                break;
            case 'save':
                $this->save($values);
                break;
            case 'add_log':
                Apps_DialerCommon::add_call_log(
                    $this->disposition,
                    Utils_BBCodeCommon::optimize($values['log_text'])
                );
                break;
            case 'next':
                $this->next_record();
                break;
            case 'user_setting':
                Base_User_SettingsCommon::save('CRM_Common', 'method', $values['dialer']);
                break;
            case 'start_auto_call':
                $this->set_module_variable('dialer_auto_call', 1);
                break;
            case 'stop_auto_call':
                $this->unset_module_variable('dialer_auto_call');
                break;
        }
    }

    public function call_record($values)
    {
        if (!isset($values['phone']) || !$values['phone']) {
            $this->mode = '';
            Base_StatusBarCommon::message(__("No phone number set/selected."), 'error');
            return;
        }
        Base_ActionBarCommon::clean();

        $phone = Apps_DialerCommon::fix_phone_number(
            $this->record[$values['phone']],
            $this->record
        );
        eval_js(Apps_DialerCommon::get_dial_code_js($phone, $values['dialer']));
        $phone = Apps_DialerCommon::format_phone($this->record, $values['phone'], true);
        $methods = Apps_DialerCommon::get_dialing_methods();
        Apps_DialerCommon::add_call_log(
            $this->disposition,
            __("Called") . " " . $this->record_type . " at {$phone} using " .
            $methods[$values['dialer']] . ' dialer.'
        );
        if ($called_rules = Telemarketing_CallCampaigns_RulesCommon::match_rules(
            $this->campaign, 'Record', 'Called')
        ) {
            foreach ($called_rules as $called_rule) {
                Apps_DialerCommon::process_rule_action(
                    $called_rule,
                    $this->campaign,
                    $this->record,
                    $this->record_type,
                    $this->product,
                    $this->disposition,
                    $values
                );
            }
        }
        $this->set_module_variable('dialer_in_call', true);
    }

    public function end_call_record($values)
    {
        Base_ActionBarCommon::clean();

        $phone = Apps_DialerCommon::format_phone($this->record, $values['phone'], true);
        Apps_DialerCommon::add_call_log(
            $this->disposition,
            "Call ended for {$phone}"
        );
        if ($this->isset_module_variable('dialer_phonecall')) {
            $phonecall_id = $this->get_module_variable('dialer_phonecall');
            $phonecall = Utils_RecordBrowserCommon::get_record('phonecall', $phonecall_id);
            $phonecall['status'] = 3;
            if ($values['disposition'] == 'BNA') {
                $phonecall['call_status'] = 'BNA';
            } else if ($values['disposition'] == 'WDN') {
                $phonecall['call_status'] = 'WDN';
            } else {
                $phonecall['call_status'] = 'COM';
            }
            Utils_RecordBrowserCommon::update_record('phonecall', $phonecall_id, $phonecall);
            $this->unset_module_variable('dialer_phonecall');
        }
        $this->unset_module_variable('dialer_in_call');
        eval_js("Dialer.show_dialog(\"disposition\")");
    }

    public function save($values)
    {
        if (!isset($values['phone']) || !$values['phone']) {
            Base_StatusBarCommon::message(__("No phone number set/selected."), 'error');
            return;
        }

        if (!$values['disposition'] || $values['disposition'] == 'none') {
            $this->error['disposition'] = __('Required');
            if ($this->isset_module_variable('dialer_in_call')) {
                $this->mode = 'call';
            }
            return;
        }

        $remarks = '';
        if (isset($values['cl_timestamp']) && $values['cl_timestamp']) {
            if (strtotime($values['cl_timestamp']) <= time()) {
                Base_StatusBarCommon::message(__("Please input a call back date later than today."), 'error');
                $this->error['cl_timestamp'] = __('Invalid date');
                if ($this->isset_module_variable('dialer_in_call')) {
                    $this->mode = 'call';
                }
                return;
            } else {
                $this->disposition['call_back_time'] = $values['cl_timestamp'];
                $remarks = "To be called on " .
                    Base_RegionalSettingsCommon::time2reg($values['cl_timestamp']) . ' ' .
                    Base_User_SettingsCommon::get('Base_RegionalSettings', 'tz') . ' time.';
            }
        } else {
            $this->disposition['call_back_time'] = null;
        }

        $talktime = $values['talktime'];
        if ($values['disposition'] == "WDN" || $values['disposition'] == "BNA") {
            $talktime = 0;
        }
        $this->disposition['talk_time'] = $talktime;
        $this->disposition['locked_to'] = 0;
        $this->disposition['disposition'] = $values['disposition'];
        $me = CRM_ContactsCommon::get_my_record();
        $this->disposition['telemarketer'] = $me['id'];
        if ($values['disposition'] != "WDN") {
            $this->disposition['timestamp'] = date('Y-m-d H:i:s');
        }
        Utils_RecordBrowserCommon::update_record(
            Telemarketing_CallCampaigns_Dispositions_RBO_Status::TABLE_NAME, $this->disposition['id'], $this->disposition
        );

        $call_dispositions = Utils_CommonDataCommon::get_array('CallCampaign/Dispositions');
        $disposition = $call_dispositions[$values['disposition']];
        $phone = Apps_DialerCommon::format_phone($this->record, $values['phone'], true);
        $log = "<p>" . ucwords($this->record_type) . " was flagged with {$disposition} disposition. " .
            "Phone number: {$phone}.</p>";
        if ($remarks) {
            $log .= "<p><strong>$remarks</strong></p>";
        }
        if ($values['log_text']) {
            $log .= "<p><strong>" . $values['log_text'] . "</strong></p>";
        }
        Apps_DialerCommon::add_call_log($this->disposition, Utils_BBCodeCommon::optimize($log));

        if ($flag_rules = Telemarketing_CallCampaigns_RulesCommon::match_rules(
            $this->campaign, 'Record', false, 'Flagged:' . $values['disposition'])
        ) {
            //Do remove action s last and push module actions 2nd to the last.
            $remove_rules = array();
            $add_rules = array();
            foreach ($flag_rules as $flag_rule) {
                if (starts_with($flag_rule['action'], 'Add')) {
                    $add_rules[] = $flag_rule;
                } else if (starts_with($flag_rule['action'], 'Remove')) {
                    $remove_rules[] = $flag_rule;
                } else {
                    Apps_DialerCommon::process_rule_action(
                        $flag_rule,
                        $this->campaign,
                        $this->record,
                        $this->record_type,
                        $this->product,
                        $this->disposition,
                        $values
                    );
                }
            }

            if (!empty($add_rules)) {
                Apps_DialerCommon::process_rule_action(
                    array_pop($add_rules), //Can only push module once so we can go back to dialer.
                    $this->campaign,
                    $this->record,
                    $this->record_type,
                    $this->product,
                    $this->disposition,
                    $values
                );
            }

            foreach ($remove_rules as $remove_rule) {
                Apps_DialerCommon::process_rule_action(
                    $remove_rule,
                    $this->campaign,
                    $this->record,
                    $this->record_type,
                    $this->product,
                    $this->disposition,
                    $values
                );
            }
        }
    }

    public function next_record()
    {
        $this->disposition['skip_date'] = date("Y-m-d H:i:s");
        $this->disposition['locked_to'] = 0;
        $this->disposition['timestamp'] = date('Y-m-d H:i:s');
        Utils_RecordBrowserCommon::update_record(
            Telemarketing_CallCampaigns_Dispositions_RBO_Status::TABLE_NAME,
            $this->disposition['id'],
            $this->disposition
        );
        Apps_DialerCommon::add_call_log(
            $this->disposition,
            "Record was skipped."
        );
    }
}
