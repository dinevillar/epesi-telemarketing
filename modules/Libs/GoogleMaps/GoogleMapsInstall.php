<?php

defined("_VALID_ACCESS") || die('Direct access forbidden');

class Libs_GoogleMapsInstall extends ModuleInstall
{

    /**
     * Module installation function.
     * @return true if installation success, false otherwise
     */
    public function install()
    {
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
        return array();
    }

    public function version()
    {
        return array("1.0");
    }

    public function info()
    {
        return array(
            'Author' => '<a href="mailto:dean.villar@gmail.com">Rodine Mark Paul Villar</a>',
            'Description' => 'Google Maps Services'
        );
    }
}
