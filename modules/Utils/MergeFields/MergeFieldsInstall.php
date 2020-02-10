<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 02/06/2020
 * @Time: 7:02 AM
 */

defined("_VALID_ACCESS") || die();

class Utils_MergeFieldsInstall extends ModuleInstall
{

    public function install()
    {
        Base_ThemeCommon::install_default_theme(self::module_name());
        return true;
    }

    public function uninstall()
    {
        return true;
    }

    public function version()
    {
        return array('0.1');
    }

    public function requires($v)
    {
        return array(
            array('name' => 'Utils/RecordBrowser', 'version' => 0)
        );
    }

    public function info()
    {
        return array(
            'Author' => '<a href="mailto:dean.villar@gmail.com">Rodine Mark Paul Villar</a>',
            'License' => 'MIT',
            'Description' => 'Common merge fields functions'
        );
    }
}
