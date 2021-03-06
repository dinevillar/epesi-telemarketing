<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 3/30/2020
 * @Time: 5:01 AM
 */
defined("_VALID_ACCESS") || die();

class Premium_WebPhone_Twilio extends Module
{

    public function body()
    {
        $method = Base_User_SettingsCommon::get("CRM_Common", 'method');
        if ($method == "Premium_WebPhone_Twilio") {
            load_js('modules/' . self::module_name() . '/twilio-lib/twilio.min.js');
            load_js('modules/' . self::module_name() . '/js/twilio.js');
            eval_js_once('EpesiTwilio.webPhoneAttach();');
            eval_js_once('EpesiTwilio.setupParams["outgoing"] = "' . get_epesi_url() . '/modules/' . self::module_name() . '/theme/outgoing.mp3";');
        }
    }

    public function admin()
    {
        if ($this->is_back()) {
            if ($this->parent->get_type() == 'Base_Admin')
                $this->parent->reset();
            else
                location(array());

            return false;
        }
        if (isset($_REQUEST['back_location'])) {
            $back_location = $_REQUEST['back_location'];
            Base_ActionBarCommon::add('back', __('Back'), Base_BoxCommon::create_href(
                $this, $back_location['module'], $back_location['func'],
                isset($back_location['args']) ? $back_location['args'] : array()
            ));
        } else {
            Base_ActionBarCommon::add('back', __('Back'), $this->create_back_href());
        }

        $tabbed_browser = $this->init_module(Utils_TabbedBrowser::module_name());
        $tabbed_browser->set_tab(__('Twilio Default Config'), array($this, 'default_config'));
        $tabbed_browser->set_tab(__('Twilio Phone Number Mappings'), array($this, 'phone_map'));
        $tabbed_browser->tag();
        echo '<div style="width:75%;text-align:left;">';
        $this->display_module($tabbed_browser);
        echo "</div>";
    }

    /**
     * @throws Exception
     * @throws HTML_QuickForm_Error
     * @throws NoSuchVariableException
     */
    public function default_config()
    {
        /** @var $qf HTML_QuickForm */
        $qf = $this->init_module(Libs_QuickForm::module_name());
        Base_ActionBarCommon::add('save', __("Save"), $qf->get_submit_form_href());
        $qf->addElement('text', 'default_twilio_account_sid', __("Default Twilio Account SID"), array('maxlength' => 34));
        $qf->addElement('text', 'default_twilio_auth_token', __("Default Twilio Auth Token"), array('maxlength' => 32));
        $qf->addElement('text', 'default_twilio_application_sid', __("Default Twilio Application SID"), array('maxlength' => 34));
        $qf->addRule('default_twilio_account_sid', __("Field Required."), 'required');
        $qf->addRule('default_twilio_auth_token', __("Field Required."), 'required');
        $qf->addRule('default_twilio_application_sid', __("Field Required."), 'required');
        if ($qf->validate() === true) {
            $values = $qf->exportValues();
            Variable::set("default_twilio_account_sid", $values["default_twilio_account_sid"]);
            Variable::set("default_twilio_auth_token", $values["default_twilio_auth_token"]);
            Variable::set("default_twilio_application_sid", $values["default_twilio_application_sid"]);
            Base_StatusBarCommon::message(__("Settings Saved."));
        }
        $defaults = array(
            "default_twilio_account_sid" => Variable::get("default_twilio_account_sid", false),
            "default_twilio_application_sid" => Variable::get("default_twilio_application_sid", false),
            "default_twilio_auth_token" => Variable::get("default_twilio_auth_token", false),
        );
        $qf->setDefaults($defaults);

        $qf->display_as_column();
    }

    /**
     * @throws NoSuchVariableException
     */
    public function phone_map()
    {
        $pm = new Premium_WebPhone_Twilio_RBO_PhoneMappings();
        $rb = $pm->create_rb_module($this, 'twilio_phone_mapping');
        $rb->set_defaults(array(
            'account_sid' => Variable::get("default_twilio_account_sid", false),
            'application_sid' => Variable::get("default_twilio_application_sid", false),
            'auth_token' => Variable::get("default_twilio_auth_token", false)
        ));
        $this->display_module($rb);
    }
}
