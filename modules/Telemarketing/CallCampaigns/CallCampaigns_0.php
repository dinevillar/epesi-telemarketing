<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/17/20
 * @Time: 12:53 PM
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Telemarketing_CallCampaigns extends Module
{

    public function body()
    {
        $me = CRM_ContactsCommon::get_my_record();
        $campaigns_tb = new Telemarketing_CallCampaigns_RBO_Campaigns();
        $campaigns_tb->refresh_magic_callbacks();
        $rb = $campaigns_tb->create_rb_module($this);
        $rb->set_defaults(
            array(
                'start_date' => date('m/d/Y'),
                'end_date' => date('m/d/Y', strtotime("+1 month")),
                'telemarketers' => array($me['id']),
                'list_type' => 'AP',
                'status' => 0,
                'permission' => 1
            )
        );
        $rb->set_default_order(array('start_date' => 'DESC'));
        $rb->set_additional_actions_method(array($this, 'cc_campaigns_actions'));

        if (ModuleManager::is_installed("Telemarketing/CallCampaigns/Dispositions") >= 0) {
            $bl_type = Base_ThemeCommon::get_template_file(
                Telemarketing_CallCampaigns_DispositionsInstall::module_name(), 'blacklist.png'
            );
            Base_ActionBarCommon::add($bl_type, __("Blacklisted Records"),
                $this->create_callback_href(array($this, 'blacklisted_records')));
        }

        $this->display_module($rb);
    }

    public function blacklisted_records()
    {

    }

    public function settings_addon($campaign)
    {
        $this->campaign_settings($campaign, "record");
    }

    public function campaign_settings($campaign, $mode = "admin")
    {
        load_js('modules/' . self::module_name() . '/js/settings.js');
        if ($mode == 'admin') {
            Base_ThemeCommon::load_css('Utils_RecordBrowser', 'View_entry');
        }
        $form = $this->init_module(Libs_QuickForm::module_name());

        if ($mode != 'admin') {
            $form->addElement("submit", '', 'Submit');
        }

        $form->addElement(
            "checkbox",
            "auto_call",
            __("Use Auto-call feature")
        );

        $form->addElement(
            "text",
            "auto_call_delay",
            __("Auto-call delay (seconds)")
        );

        $form->addRule(
            "auto_call_delay",
            __("Must be numeric") . Libs_QuickFormCommon::get_error_closing_button(),
            'numeric'
        );

        $form->addElement(
            "checkbox",
            "filter_inv_phone",
            __("Filter out records with no phone number")
        );

        $form->addElement(
            "checkbox",
            "auto_scroll",
            __("Use Auto-scroll feature")
        );

        $form->addElement(
            "text",
            "auto_scroll_speed",
            __("Auto-scroll speed (milliseconds)")
        );

        $form->addRule(
            "auto_scroll_speed",
            __("Must be numeric") . Libs_QuickFormCommon::get_error_closing_button(),
            'numeric'
        );

        $form->addElement(
            "checkbox",
            "allow_skip",
            __("Allow skipping of records")
        );

        $form->addElement(
            "checkbox",
            "newest_records_first",
            __("Prioritize newest records first")
        );

        $form->addElement(
            "checkbox",
            "prioritize_call_backs",
            __("Prioritize records with past due call back times")
        );

        $form->addElement(
            "date",
            "optimal_call_time_start",
            __("Optimal call time start"),
            array(
                'format' => 'H:i'
            )
        );

        $form->addElement(
            "date",
            "optimal_call_time_end",
            __("Optimal call time end"),
            array(
                'format' => 'H:i'
            )
        );

        $form->addElement(
            "checkbox",
            "filter_not_optimal_call_time",
            __("Filter out records not currently on optimal time")
        );

        $priority = array(
            1 => __("First"),
            2 => __("Second"),
            3 => __("Third"),
        );
        $form->addElement("select", 'prio_work', __("Work Phone Priority"), $priority);
        $form->addElement("select", 'prio_mobile', __("Mobile Phone Priority"), $priority);
        $form->addElement("select", 'prio_home', __("Home Phone Priority"), $priority);

        if ($form->validate()) {
            $values = $form->exportValues();
            unset($values['submited']);
            if ($mode == 'admin') {
                Variable::set("telemarketing_default_settings", $values);
            } else {
                Telemarketing_CallCampaignsCommon::update_settings($campaign['id'], $values);
            }
            Base_StatusBarCommon::message(__('Settings saved'));
        }

        if ($mode == 'admin') {
            $default_rules = Variable::get("telemarketing_default_settings", false);
        } else {
            $default_rules = Telemarketing_CallCampaignsCommon::get_settings($campaign['id']);
        }

        $form->setDefaults(array(
            'auto_call' => $default_rules['auto_call'],
            'auto_call_delay' => $default_rules['auto_call_delay'],
            'filter_inv_phone' => $default_rules['filter_inv_phone'],
            'auto_scroll' => $default_rules['auto_scroll'],
            'auto_scroll_speed' => $default_rules['auto_scroll_speed'],
            'allow_skip' => $default_rules['allow_skip'],
            'newest_records_first' => $default_rules['newest_records_first'],
            'prioritize_call_backs' => $default_rules['prioritize_call_backs'],
            'optimal_call_time_start' => $default_rules['optimal_call_time_start'],
            'optimal_call_time_end' => $default_rules['optimal_call_time_end'],
            'filter_not_optimal_call_time' => $default_rules['filter_not_optimal_call_time'],
            'prio_work' => $default_rules['prio_work'],
            'prio_mobile' => $default_rules['prio_mobile'],
            'prio_home' => $default_rules['prio_home'],
        ));

        if ($mode == 'admin') {
            echo "<h3>" . __("Default Call Campaign Settings") . "</h3>";
            echo "<p>The settings specified here are the default settings used when a user adds a new call campaign record.</p>";
            echo "<p>Specific settings for each call campaign can be set on its view screen.</p>";
        }
        echo "<div id='cc_settings_form'>";
        $form->display_as_column();
        echo "</div>";

        if ($mode == 'admin') {
            Base_ActionBarCommon::add("save", __("Save"), $form->get_submit_form_href());
        }
        eval_js("CallCampaignSettings.init();");
    }

    public function admin()
    {
        if ($this->is_back()) {
            if ($this->parent->get_type() == 'Base_Admin')
                $this->parent->reset();
            else
                location(array());
            return;
        }
        Base_ActionBarCommon::add('back', __('Back'), $this->create_back_href());
        $tabbed_browser = $this->init_module(Utils_TabbedBrowser::module_name());
        $tabbed_browser->set_tab(__('Default Call Campaign Settings'), array($this, 'campaign_settings'));
        $plugins = ModuleManager::call_common_methods('call_campaign_settings_tab');
        foreach ($plugins as $class => $details) {
            $mod = $this->init_module($class);
            $tabbed_browser->set_tab($details['label'], array($mod, $details['func']));
        }
        $this->display_module($tabbed_browser);
    }
}
