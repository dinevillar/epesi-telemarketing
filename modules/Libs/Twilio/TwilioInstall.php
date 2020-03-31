<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 3/30/2020
 * @Time: 2:32 AM
 */
defined("_VALID_ACCESS") || die();

class Libs_TwilioInstall extends ModuleInstall
{

    public function install()
    {
        $pm = new Libs_Twilio_RBO_PhoneMappings();
        if ($pm->install()) {
            $pm->add_access('view', 'ACCESS:employee', array('employee' => 'USER'));
            $pm->add_access('add', 'ADMIN');
            $pm->add_access('view', 'ADMIN');
            $pm->add_access('edit', 'ACCESS:employee', array('employee' => 'USER'));
            $pm->add_access('edit', 'ADMIN');
            $pm->add_access('delete', 'ADMIN');
            $pm->set_caption(__("Twilio Phone Number Mapping"));
            $pm->register_processing_callback(array('Libs_TwilioCommon', 'submit_phone_mappings'));
        }
        Base_ThemeCommon::install_default_theme(self::module_name());
        return true;
    }

    public function uninstall()
    {
        $pm = new Libs_Twilio_RBO_PhoneMappings();
        $pm->unregister_processing_callback(array('Libs_TwilioCommon', 'submit_phone_mappings'));
        $pm->uninstall();
        Base_ThemeCommon::uninstall_default_theme(self::module_name());
        return true;
    }

    public function version()
    {
        return array('5.12');
    }

    public function requires($v)
    {
        return array(
            array('name' => 'CRM_Contacts', 'version' => 0),
            array('name' => 'Apps_WebPhone', 'version' => 0)
        );
    }

    public function info()
    {
        return array(
            'Author' => '<a href="mailto:dean.villar@gmail.com">Rodine Mark Paul L. Villar</a>'
        , 'Description' => 'API Integration for Twilio Telephony'
        );
    }
}
