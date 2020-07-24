<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 3/29/2020
 * @Time: 2:54 AM
 */
defined("_VALID_ACCESS") || die();

class Premium_WebPhoneInstall extends ModuleInstall
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
        return array(self::version);
    }

    public function requires($v)
    {
        return array(
            array('name' => 'Base', 'version' => 0)
        );
    }

    public function info()
    {
        return array(
            'Author' => '<a href="mailto:dean.villar@gmail.com">Rodine Mark Paul L. Villar</a>',
            'Description' => 'Integrated Web Phone can be used with Telemarketing Module'
        );
    }

    public static function simple_setup()
    {
        return array(
            'package' => __('Web Phone'),
            'icon' => true,
            'version' => self::version
        );
    }
}
