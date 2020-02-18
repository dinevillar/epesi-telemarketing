<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/17/20
 * @Time: 1:29 PM
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class TelemarketingInstall extends ModuleInstall
{
    const version = "1.0";

    /**
     * Module installation function.
     * @return true if installation success, false otherwise
     */
    public function install()
    {
        Base_ThemeCommon::install_default_theme(self::module_name());
        return true;
    }

    /**
     * Module uninstallation function.
     * @return true if installation success, false otherwise
     */
    public function uninstall()
    {
        Base_ThemeCommon::uninstall_default_theme(self::module_name());
        return true;
    }

    public static function simple_setup()
    {
        return array('package' => __('Telemarketing'), 'icon' => true, 'version' => self::version);
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
            'Description' => 'Telemarketing Module'
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
            array('name' => Utils_RecordBrowserInstall::module_name(), 'version' => 0),
            array('name' => Utils_AttachmentInstall::module_name(), 'version' => 0),
            array('name' => CRM_ContactsInstall::module_name(), 'version' => 0),
            array('name' => CRM_PhoneCallInstall::module_name(), 'version' => 0),
        );
    }

}
