<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 3/30/2020
 * @Time: 5:01 AM
 */
defined("_VALID_ACCESS") || die();

class Libs_Twilio extends Module
{

    public function body()
    {
        $method = Base_User_SettingsCommon::get("CRM_Common", 'method');
        if ($method == "Libs_Twilio") {
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

    public function default_config()
    {
        /** @var $qf HTML_QuickForm */
        $qf = $this->init_module(Libs_QuickForm::module_name());
        Base_ActionBarCommon::add('save', __("Save"), $qf->get_submit_form_href());
        $qf->addElement('text', 'default_twilio_account_sid', __("Default Twilio Account SID"), array('maxlength' => 34));
        $qf_enc_tok = $qf->createElement('text', 'encrypt_twilio_auth_token', null, array('readonly' => 'true'));
        $qf_update = $qf->createElement('password', 'default_twilio_auth_token', null, array('placeholder' => __("Update Auth Token"), 'maxlength' => 32));
        $qf->addElement('group', 'twilio_auth_token', __("Default Twilio Auth Token"), array($qf_enc_tok, $qf_update));
        $qf->addElement('text', 'default_twilio_application_sid', __("Default Twilio Application SID"), array('maxlength' => 34));
        $qf->addRule('default_twilio_account_sid', __("Field Required."), 'required');
        $qf->addRule('default_twilio_application_sid', __("Field Required."), 'required');
        $qf->addFormRule(function ($fields) {
            if ($fields['twilio_auth_token']['encrypt_twilio_auth_token'] == __("Not Set") && !$fields['twilio_auth_token']['default_twilio_auth_token']) {
                return array('twilio_auth_token' => __("Field required") . Libs_QuickFormCommon::get_error_closing_button());
            }
            return true;
        });
        if ($qf->validate() === true) {
            $values = $qf->exportValues();
            Variable::set("default_twilio_account_sid", $values["default_twilio_account_sid"]);
            if ($values["twilio_auth_token"]["default_twilio_auth_token"]) {
                Variable::set("default_twilio_auth_token", $values["twilio_auth_token"]["default_twilio_auth_token"]);
            }
            Variable::set("default_twilio_application_sid", $values["default_twilio_application_sid"]);
            Base_StatusBarCommon::message(__("Settings Saved."));
        }
        $defaults = array(
            "default_twilio_account_sid" => Variable::get("default_twilio_account_sid", false),
            "default_twilio_application_sid" => Variable::get("default_twilio_application_sid", false),
        );
        $token = Variable::get("default_twilio_auth_token", false);
        if (!$token) {
            $defaults['twilio_auth_token']['encrypt_twilio_auth_token'] = __("Not Set");
        } else {
            $token_val = __("Encrypted") . ": " . $token;
            eval_js("jq(':input[name=\"twilio_auth_token[encrypt_twilio_auth_token]\"]').val('{$token_val}');");
        }
        eval_js("jq(':input[name=\"twilio_auth_token[default_twilio_auth_token]\"]').val('');");
        $qf->setDefaults($defaults);

        $qf->display_as_column();
    }

    public function phone_map()
    {
        $pm = new Libs_Twilio_RBO_PhoneMappings();
        $rb = $pm->create_rb_module($this, 'twilio_phone_mapping');
        $rb->set_defaults(array(
            'account_sid' => Variable::get("default_twilio_account_sid", false),
            'application_sid' => Variable::get("default_twilio_application_sid", false),
            'auth_token' => Variable::get("default_twilio_auth_token", false)
        ));
        $this->display_module($rb);
    }
}
