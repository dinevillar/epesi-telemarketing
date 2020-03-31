<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 3/29/2020
 * @Time: 2:54 AM
 */
defined("_VALID_ACCESS") || die();

class Apps_WebPhoneInstall extends ModuleInstall
{
    const version = "1.0";

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
        return array('1.0');
    }

    public function requires($v)
    {
        return array(
            array('name' => 'Base_Dashboard', 'version' => 0)
        );
    }

    public function info()
    {
        return array(
            'Author' => '<a href="mailto:dean.villar@gmail.com">Rodine Mark Paul L. Villar</a>',
            'Description' => 'Generic Web Phone'
        );
    }

    public static function simple_setup()
    {
        return array(
            'package' => __('Telemarketing'),
            'option' => __('Apps') . ' - ' . __('Web Phone'), 'version' => self::version
        );
    }
}
