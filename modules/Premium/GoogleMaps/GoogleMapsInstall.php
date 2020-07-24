<?php

defined("_VALID_ACCESS") || die('Direct access forbidden');

class Premium_GoogleMapsInstall extends ModuleInstall
{
    const version = "1.0.0";

    /**
     * Module installation function.
     * @return true if installation success, false otherwise
     */
    public function install()
    {
        Base_ThemeCommon::install_default_theme(self::module_name());
        Variable::set("google_maps_token", "");
        return true;
    }

    /**
     * Module uninstallation function.
     * @return true if installation success, false otherwise
     * @throws NoSuchVariableException
     */
    public function uninstall()
    {
        Base_ThemeCommon::uninstall_default_theme(self::module_name());
        Variable::delete("google_maps_token", false);
        return true;
    }

    /**
     * Returns array that contains information about modules required by this module.
     * The array should be determined by the version number that is given as parameter.
     *
     * @param int $v module version number
     * @return array Array constructed as following: array(array('name'=>$ModuleName,'version'=>$ModuleVersion),...)
     */
    public function requires($v)
    {
        return array(
            array('name' => 'Base', 'version' => 0),
            array('name' => 'Libs/QuickForm', 'version' => 0),
        );
    }

    public function version()
    {
        return array(self::version);
    }

    public static function simple_setup()
    {
        return array(
            'package' => __('Google Maps Services'),
            'icon' => true,
            'version' => self::version
        );
    }

    public function info()
    {
        return array(
            'Author' => '<a href="mailto:dean.villar@gmail.com">Rodine Mark Paul Villar</a>',
            'Description' => 'EPESI Wrapper module for yidas/google-maps-services.'
        );
    }
}
