<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 3/30/2020
 * @Time: 2:32 AM
 */
defined("_VALID_ACCESS") || die();

class Premium_WebPhone_TwilioInstall extends ModuleInstall
{
    const version = '5.12';

    public function install()
    {
        $pm = new Premium_WebPhone_Twilio_RBO_PhoneMappings();
        if ($pm->install()) {
            $pm->add_access('view', 'ACCESS:employee', array('employee' => 'USER'));
            $pm->add_access('add', 'ADMIN');
            $pm->add_access('view', 'ADMIN');
            $pm->add_access('edit', 'ACCESS:employee', array('employee' => 'USER'));
            $pm->add_access('edit', 'ADMIN');
            $pm->add_access('delete', 'ADMIN');
            $pm->set_caption(__("Twilio Phone Number Mapping"));
            $pm->register_processing_callback(array('Premium_WebPhone_TwilioCommon', 'submit_phone_mappings'));
        }
        Base_ThemeCommon::install_default_theme(self::module_name());
        return true;
    }

    public function uninstall()
    {
        $pm = new Premium_WebPhone_Twilio_RBO_PhoneMappings();
        $pm->unregister_processing_callback(array('Premium_WebPhone_TwilioCommon', 'submit_phone_mappings'));
        $pm->uninstall();
        Base_ThemeCommon::uninstall_default_theme(self::module_name());
        return true;
    }

    public function version()
    {
        return array(self::version);
    }

    public function simple_setup()
    {
        return array(
            'package' => __('Web Phone'),
            'option' => 'Twilio Telephony',
            'version' => self::version
        );
    }

    public function requires($v)
    {
        return array(
            array('name' => 'Base', 'version' => 0),
            array('name' => 'CRM/Common', 'version' => 0),
            array('name' => 'Libs/QuickForm', 'version' => 0),
            array('name' => 'Premium/WebPhone', 'version' => 0)
        );
    }

    public function info()
    {
        return array(
            'Author' => '<a href="mailto:dean.villar@gmail.com">Rodine Mark Paul L. Villar</a>'
        , 'Description' => 'Implementation for Twilio Telephony used in Web Phone'
        );
    }
}
