<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/11/20
 * @Time: 4:11 PM
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Apps_DialerInstall extends ModuleInstall
{
    const version = "1.0";

    /**
     * Module installation function.
     * @return true if installation success, false otherwise
     */
    public function install()
    {
        Base_ThemeCommon::install_default_theme(self::module_name());
        $dialing = Base_User_SettingsCommon::get('CRM_Common', 'method');
        if (!$dialing || $dialing == 'none') {
            Base_User_SettingsCommon::save('CRM_Common', 'method', 'callto');
        }
        Base_AclCommon::add_permission(_M('Dialer'), array('ACCESS:employee'));
        return true;
    }

    /**
     * Module uninstallation function.
     * @return true if installation success, false otherwise
     */
    public function uninstall()
    {
        Base_ThemeCommon::uninstall_default_theme(self::module_name());
        Base_AclCommon::delete_permission('Dialer');
        return true;
    }

    public static function simple_setup()
    {
        return array(
            'package' => __('Telemarketing'),
            'option' => __('Apps') . ' - ' . __('Dialer'), 'version' => self::version
        );
    }

    public function version()
    {
        return array(self::version);
    }

    public function info()
    {
        return array(
            'Author' => '<a href="mailto:dean.villar@gmail.com">Rodine Mark Paul L. Villar</a>',
            'License' => 'MIT',
            'Description' => 'Telemarketing Dialer'
        );
    }


    /**
     * Returns array that contains information about modules required by this module.
     * The array should be determined by the version number that is given as parameter.
     *
     * @param int  module version number
     * @return array Array constructed as following: array(array('name'=>,'version'=>),...)
     */
    public function requires($v)
    {
        return array(
            array('name' => CRM_ContactsInstall::module_name(), 'version' => 0),
            array('name' => Telemarketing_CallCampaignsInstall::module_name(), 'version' => 0),
            array('name' => Telemarketing_CallCampaigns_DispositionsInstall::module_name(), 'version' => 0),
            array('name' => Telemarketing_CallCampaigns_RulesInstall::module_name(), 'version' => 0)
        );
    }

}
