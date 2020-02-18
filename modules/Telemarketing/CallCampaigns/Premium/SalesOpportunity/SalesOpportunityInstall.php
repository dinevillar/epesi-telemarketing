<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/11/20
 * @Time: 5:18 PM
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Telemarketing_CallCampaigns_Premium_SalesOpportunityInstall extends ModuleInstall
{

    /**
     * Module installation function.
     * @return true if installation success, false otherwise
     */
    public function install()
    {
        Utils_CommonDataCommon::extend_array('Premium/SalesOpportunity/Source', array(
            4 => __('Telemarketing')
        ));
        return true;
    }

    /**
     * Module uninstallation function.
     * @return true if installation success, false otherwise
     */
    public function uninstall()
    {
        Utils_CommonDataCommon::remove('Premium/SalesOpportunity/Source/4');
        return true;
    }

    public function version()
    {
        return array('0.1');
    }

    public function simple_setup()
    {
        if (ModuleManager::is_installed('Premium/SalesOpportunity') >= 0) {
            return array(
                'package' => __('Telemarketing'),
                'option' => __('Call Campaigns') . ' - ' . __('Sales Opportunity')
            );
        }
        return array();
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
            array('name' => Telemarketing_CallCampaignsInstall::module_name(), 'version' => 0),
            array('name' => 'Premium/SalesOpportunity', 'version' => 0)
        );
    }

}
