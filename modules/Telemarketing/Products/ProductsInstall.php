<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/10/20
 * @Time: 2:10 AM
 */
defined("_VALID_ACCESS") || die();


class Telemarketing_ProductsInstall extends ModuleInstall
{

    /**
     * Module installation function.
     * @return true if installation success, false otherwise
     */
    public function install()
    {
        Base_ThemeCommon::install_default_theme(self::module_name());
        $products_rbo = new Telemarketing_Products_RBO_Products();
        if ($products_rbo->install()) {
            $products_rbo->set_quickjump(__('Name'));
            $products_rbo->set_favorites(TRUE);
            $products_rbo->set_recent(15);
            $products_rbo->set_caption(__("Products"));
            $products_rbo->set_icon(
                Base_ThemeCommon::get_template_filename(
                    self::module_name(), 'icon.png')
            );
            Utils_RecordBrowserCommon::enable_watchdog(
                Telemarketing_Products_RBO_Products::TABLE_NAME,
                array('Telemarketing_ProductsCommon', 'product_watchdog_label')
            );
            self::install_permissions();
            Utils_AttachmentCommon::new_addon(Telemarketing_Products_RBO_Products::TABLE_NAME);

            Utils_RecordBrowserCommon::register_processing_callback(
                Telemarketing_Products_RBO_Products::TABLE_NAME,
                array('Telemarketing_ProductsCommon', 'submit_products'));

            $product_campaign_field = new RBO_Field_MultiSelect(
                _M("Products"),
                Telemarketing_Products_RBO_Products::TABLE_NAME,
                array('Name', 'Code'));
            $product_campaign_field->set_filter();

            Utils_RecordBrowserCommon::new_record_field(
                Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME,
                $product_campaign_field->get_definition()
            );

            return true;
        }
    }

    public static function install_permissions()
    {
        $tab = Telemarketing_Products_RBO_Products::TABLE_NAME;
        Utils_RecordBrowserCommon::wipe_access($tab);
        Utils_RecordBrowserCommon::add_access($tab, 'print', 'SUPERADMIN');
        Utils_RecordBrowserCommon::add_access($tab, 'export', 'SUPERADMIN');
        Utils_RecordBrowserCommon::add_access($tab, 'view', 'ACCESS:employee', array('(!permission' => 2, '|:Created_by' => 'USER_ID'));
        Utils_RecordBrowserCommon::add_access($tab, 'view', 'ALL', array('id' => 'USER_COMPANY'));
        Utils_RecordBrowserCommon::add_access($tab, 'add', 'ACCESS:employee');
        Utils_RecordBrowserCommon::add_access($tab, 'edit', 'ACCESS:employee', array('(permission' => 0, '|:Created_by' => 'USER_ID'));
        Utils_RecordBrowserCommon::add_access($tab, 'edit', array('ALL', 'ACCESS:manager'), array('id' => 'USER_COMPANY'));
        Utils_RecordBrowserCommon::add_access($tab, 'edit', array('ACCESS:employee', 'ACCESS:manager'), array());
        Utils_RecordBrowserCommon::add_access($tab, 'delete', 'ACCESS:employee', array(':Created_by' => 'USER_ID'));
        Utils_RecordBrowserCommon::add_access($tab, 'delete', array('ACCESS:employee', 'ACCESS:manager'));
    }

    /**
     * Module uninstallation function.
     * @return true if installation success, false otherwise
     */
    public function uninstall()
    {
        try {
            Utils_RecordBrowserCommon::delete_record_field(
                Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME,
                "Products"
            );
        } catch (Exception $e) {
            //fail silently
        }
        $products_rbo = new Telemarketing_Products_RBO_Products();
        if ($products_rbo->uninstall()) {
            Base_ThemeCommon::uninstall_default_theme(self::module_name());
            Utils_AttachmentCommon::delete_addon(Telemarketing_Products_RBO_Products::TABLE_NAME);
            Utils_RecordBrowserCommon::unregister_processing_callback(
                Telemarketing_Products_RBO_Products::TABLE_NAME,
                array('Telemarketing_ProductsCommon', 'submit_products')
            );
            return true;
        }
        return false;
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
            array('name' => CRM_ContactsInstall::module_name(), 'version' => 0),
            array('name' => TelemarketingInstall::module_name(), 'version' => 0),
        );
    }

    public function simple_setup()
    {
        return array(
            'package' => __('Telemarketing'),
            'option' => __('Products')
        );
    }

    public function version()
    {
        return array("0.1");
    }

    public function info()
    {
        return array(
            'Author' => '<a href="mailto:dean.villar@gmail.com">Rodine Mark Paul Villar</a>',
            'Description' => "Products"
        );
    }

}
