<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 02/06/2020
 * @Time: 7:02 AM
 */

defined("_VALID_ACCESS") || die();

class Premium_Telemarketing_MergeFieldsInstall extends ModuleInstall
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

    public function requires($v)
    {
        return array(
            array('name' => 'Utils/RecordBrowser', 'version' => 0)
        );
    }

    public function version()
    {
        return array(Premium_TelemarketingInstall::version);
    }

    public function info()
    {
        return array(
            'Author' => '<a href="mailto:dean.villar@gmail.com">Rodine Mark Paul Villar</a>',
            'License' => 'MIT',
            'Description' => 'Common merge fields functions'
        );
    }

    public static function simple_setup()
    {
        return array('package' => __('Telemarketing'));
    }
}
