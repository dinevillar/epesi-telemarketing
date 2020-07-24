<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 4/2/2020
 * @Time: 10:54 PM
 */
defined("_VALID_ACCESS") || die();

class Premium_Telemarketing_CallCampaigns_ReportsInstall extends ModuleInstall
{

    public function install()
    {
        Base_ThemeCommon::install_default_theme(self::module_name());
        return true;
    }

    public function uninstall()
    {
        Base_ThemeCommon::uninstall_default_theme(self::module_name());
        return true;
    }

    public function version()
    {
        return array(Premium_TelemarketingInstall::version);
    }

    public function requires($v)
    {
        return array(
            array('name' => 'Base', 'version' => 0),
            array('name' => 'Libs/QuickForm', 'version' => 0),
            array('name' => 'Utils/RecordBrowser', 'version' => 0),
            array('name' => 'Utils/RecordBrowser/Reports', 'version' => 0),
            array('name' => 'CRM/Contacts', 'version' => 0),
            array('name' => 'Telemarketing/CallCampaigns', 'version' => 0),
            array('name' => 'Telemarketing/CallCampaigns/Dispositions', 'version' => 0),
        );
    }

    public function info()
    {
        return array(
            'Author' => '<a href = "mailto:dean.villar@gmail.com" > Rodine Mark Paul L . Villar </a> ',
            'License' => 'MIT',
            'Description' => 'Call Campaigns Reports'
        );
    }

    public function simple_setup()
    {
        return array(
            'package' => __('Telemarketing'),
            'option' => __('Reports')
        );
    }
}
