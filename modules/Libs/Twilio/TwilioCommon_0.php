<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 3/30/2020
 * @Time: 2:44 AM
 */
defined("_VALID_ACCESS") || die();

class Libs_TwilioCommon extends ModuleCommon
{

    public static function store_config_href()
    {
        return array('module' => 'Libs_Twilio', 'func' => 'admin', 'args' => array());
    }

    public static function sms_method()
    {
        return array(
            'name' => __('Twilio SMS'),
            'settings_callback' => array('Libs_TwilioCommon', 'sms_settings_form'),
            'send_callback' => array('Libs_TwilioCommon', 'sms_send')
        );
    }

    public static function sms_settings_form($form)
    {
        $form->addElement('text', 'twilio_account_id', '<span style="color:red">*</span>' . __('Twilio Account SID'));
        $qf_enc_tok = $form->createElement('text', 'encrypt_twilio_auth_token', null, array('readonly' => 'true'));
        $qf_update = $form->createElement('password', 'default_twilio_auth_token', null, array('placeholder' => __("Update Auth Token"), 'maxlength' => 32));
        $form->addElement('group', 'twilio_auth_token', '<span style="color:red">*</span>' . __("Twilio Auth Token"), array($qf_enc_tok, $qf_update));

        $form->addFormRule(function ($fields) {
            if ($fields['twilio_auth_token']['encrypt_twilio_auth_token'] == __("Not Set") && !$fields['twilio_auth_token']['default_twilio_auth_token']) {
                return array('twilio_auth_token' => __("Field required") . Libs_QuickFormCommon::get_error_closing_button());
            }
            if (!isset($fields['twilio_account_id']) || !trim($fields['twilio_account_id'])) {
                return array('twilio_account_id' => __("Field required") . Libs_QuickFormCommon::get_error_closing_button());
            }
            return true;
        });
        if ($form->validate()) {
            $values = $form->exportValues();
            Variable::set('sms_twilio_account_sid', $values['twilio_account_id']);
            if ($values["twilio_auth_token"]["default_twilio_auth_token"]) {
                Variable::set("sms_twilio_auth_token", $values["twilio_auth_token"]["default_twilio_auth_token"]);
            } else if (Variable::get('default_twilio_auth_token', false)) {
                Variable::set("sms_twilio_auth_token", Variable::get('default_twilio_auth_token', false));
            }
            Base_StatusBarCommon::message(__('Settings Saved.'));
        }
        $defaults = array(
            'twilio_account_id' => Variable::get('sms_twilio_account_sid', false) ? Variable::get('sms_twilio_account_sid', false) : Variable::get('default_twilio_account_sid', false),
        );
        $token = Variable::get('sms_twilio_auth_token', false) ? Variable::get('sms_twilio_auth_token', false) : Variable::get('default_twilio_auth_token', false);
        if (!$token) {
            $defaults['twilio_auth_token']['encrypt_twilio_auth_token'] = __("Not Set");
        } else {
            $token_val = __("Encrypted") . ": " . $token;
            eval_js("jq(':input[name=\"twilio_auth_token[encrypt_twilio_auth_token]\"]').val('{$token_val}');");
        }
        eval_js("jq(':input[name=\"twilio_auth_token[default_twilio_auth_token]\"]').val('');");
        $form->setDefaults($defaults);
    }

    public static function sms_send($recipients, $message, $count = 1)
    {
        $recipients = is_array($recipients) ? $recipients : array($recipients);
        try {
            $phone_mapping_rbo = new Libs_Twilio_RBO_PhoneMappings();
            $pm_rec = $phone_mapping_rbo->get_pm_rec();
            if (!$pm_rec) {
                throw new Exception('User does not have a phone number set.');
            }
            $account_sid = Variable::get('sms_twilio_account_sid', false);
            $auth_token = Variable::get('sms_twilio_auth_token', false);
            $client = $phone_mapping_rbo->get_bare_twilio_service($account_sid, $auth_token);
            foreach ($recipients as $recipient) {
                $client->account->messages->create(array(
                    'To' => $recipient,
                    'From' => $pm_rec['phone_number'],
                    'Body' => $message
                ));
            }
            return true;
        } catch (Exception $e) {
            return $e->getCode() . ': ' . $e->getMessage();
        }
    }

    public static function web_phone()
    {
        $pm_rbo = new Libs_Twilio_RBO_PhoneMappings();
        $pm = $pm_rbo->get_pm_rec();
        $short_name = __('Twilio');
        $d = array(
            "short_name" => $short_name,
            "init" => 'body',
            'description' => " ",
            'left_actions' => array(),
            'right_controls' => array()
        );
        if ($pm && $pm['phone_number']) {
            $d['description'] .= __('My') . ' ' . __('Phone Number') . ': ' . $pm['phone_number'] . ' ';
            if ($pm['client_name']) {
                $d['description'] .= ' (' . $pm['client_name'] . ')';
            }
            if (Utils_RecordBrowserCommon::get_access($pm_rbo::TABLE_NAME, 'edit', $pm)) {
                $image_src = Base_ThemeCommon::get_template_file(Base_Dashboard::module_name(), 'configure.png');
                $image_hover_src = Base_ThemeCommon::get_template_file(Base_Dashboard::module_name(), 'configure-hover.png');
                $image = "<img src='$image_src' onmouseover='this.src=\"$image_hover_src\"' onmouseout='this.src=\"$image_src\"' width='14' height='14' border='0'/>";
                $module = Base_BoxCommon::main_module_instance();
                $href = $module->create_callback_href('Base_BoxCommon::push_module',
                    array('Utils/RecordBrowser', 'view_entry', array('edit', $pm['id'], array()), array($pm_rbo::TABLE_NAME)));
                $tip = Utils_TooltipCommon::open_tag_attrs(__('Configure Twilio Account'));
                array_push($d['right_controls'], "<a$href$tip>$image</a>");
            }
        } else {
            $d['description'] .= __('Phone Number') . ': <span style="color:#993333">' . __('Not set') . '</span> ';
        }
        if (Utils_RecordBrowserCommon::get_access($pm_rbo::TABLE_NAME, 'add')) {
            $image = "<img src='" . Base_ThemeCommon::get_template_file(Utils_RecordBrowser::module_name(), 'add.png') . "' border='0'/>";
            $module = Base_BoxCommon::main_module_instance();
            $href = $module->create_callback_href('Base_BoxCommon::push_module',
                array('Utils/RecordBrowser', 'view_entry', array('add', NULL, array()), array($pm_rbo::TABLE_NAME)));
            $tip = Utils_TooltipCommon::open_tag_attrs(__('Add New Phone Number'));
            array_push($d['left_actions'], "<a$href$tip>$image</a>");
        }
        return $d;
    }

    public static function admin_caption()
    {
        return array('label' => __('Twilio Telephony'), 'section' => __('Server Configuration'));
    }

    public static function dialer_description()
    {
        return __('Twilio Telephony');
    }

    public static function dialer($title)
    {
        return Apps_WebPhoneCommon::default_dialer($title);
    }

    public static function dialer_js($title)
    {
        return Apps_WebPhoneCommon::default_dialer_js($title);
    }

    public static function submit_phone_mappings($record, $mode)
    {
        if ($mode == 'add' || $mode == 'edit') {
            if ($record['client_name']) {
                $record['client_name'] = preg_replace('/[^a-zA-Z0-9]+/', '_', $record['client_name']);
            }
        }
        return $record;
    }

    public static function first_run()
    {
        Variable::delete('default_twilio_account_sid', false);
        Variable::delete('default_twilio_application_sid', false);
        Variable::delete('default_twilio_auth_token', false);
    }
}
