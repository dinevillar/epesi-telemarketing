<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/11/20
 * @Time: 4:50 PM
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Premium_Telemarketing_Products_SalesOpportunityIntegrationInstall extends ModuleInstall
{

    /**
     * Module installation function.
     * @return true if installation success, false otherwise
     */
    public function install()
    {
        Utils_RecordBrowserCommon::new_addon(
            Premium_Telemarketing_Products_RBO_Products::TABLE_NAME,
            self::module_name(),
            'sales_opportunities_addon',
            _M('Sales Opportunities')
        );

        $product_field = new RBO_Field_MultiSelect(
            __("Product"),
            Premium_Telemarketing_Products_RBO_Products::TABLE_NAME
        );
        $product_field->set_filter()->set_visible();

        Utils_RecordBrowserCommon::new_record_field(
            'premium_salesopportunity',
            $product_field->get_definition()
        );
        return true;
    }

    /**
     * Module uninstallation function.
     * @return true if installation success, false otherwise
     */
    public function uninstall()
    {
        Utils_RecordBrowserCommon::delete_addon(
            Premium_Telemarketing_Products_RBO_Products::TABLE_NAME,
            self::module_name(),
            'sales_opportunities_addon'
        );
        Utils_RecordBrowserCommon::delete_record_field(
            'premium_salesopportunity', __("Product")
        );
        return true;
    }

    public function version()
    {
        return array(Premium_TelemarketingInstall::version);
    }

    public function simple_setup()
    {
        if (ModuleManager::is_installed('Premium/SalesOpportunity') >= 0) {
            return array(
                'package' => __('Telemarketing'),
                'option' => __('Products') . ' - ' . __('Sales Opportunity Integration')
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
            array('name' => 'Premium/Telemarketing/Products', 'version' => 0),
            array('name' => 'Premium/SalesOpportunity', 'version' => 0)
        );
    }

}
