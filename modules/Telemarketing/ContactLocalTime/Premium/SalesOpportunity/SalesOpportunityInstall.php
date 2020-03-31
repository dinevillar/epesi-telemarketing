<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 3/22/20
 * @Time: 8:12 AM
 */

class Telemarketing_ContactLocalTime_Premium_SalesOpportunityInstall extends ModuleInstall
{

    /**
     * Module installation function.
     * @return true if installation success, false otherwise
     */
    public function install()
    {
        try {
            $lt_so = new RBO_Field_Calculated(_M("Local Time"), 64);
            $lt_so->set_display_callback(array("Telemarketing_ContactLocalTime_Premium_SalesOpportunityCommon", "display_local_time_sales_opp"));
            $lt_so->set_QFfield_callback(array("Telemarketing_ContactLocalTimeCommon", "qffield_local_time"));

            Utils_RecordBrowserCommon::new_record_field('premium_salesopportunity', $lt_so->get_definition());

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Module uninstallation function.
     * @return true if installation success, false otherwise
     */
    public function uninstall()
    {
        try {
            Utils_RecordBrowserCommon::delete_record_field('premium_salesopportunity', 'Local Time');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function simple_setup()
    {
        if (ModuleManager::is_installed('Premium/SalesOpportunity') >= 0) {
            return array(
                'package' => __('Telemarketing'),
                'option' => __('ContactLocalTime') . ' - ' . __('Sales Opportunity')
            );
        }
        return array();
    }

    public function version()
    {
        return array(Telemarketing_ContactLocalTimeInstall::version);
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
            array('name' => Telemarketing_ContactLocalTimeInstall::module_name(), 'version' => 0),
            array('name' => 'Premium/SalesOpportunity', 'version' => 0)
        );
    }
}
