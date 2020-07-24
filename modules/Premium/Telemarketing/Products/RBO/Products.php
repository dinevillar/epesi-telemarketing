<?php
/**
 * Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * Date: 10/21/13
 * Time: 11:42 AM
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Premium_Telemarketing_Products_RBO_Products extends RBO_RecordSet
{

    const TABLE_NAME = 'telemarketing_products';

    public function fields()
    {
        $fields = array();

        $name = new RBO_Field_Text(__("Name"), 128);
        $name->set_required()->set_visible();
        $fields[] = $name;

        $code = new RBO_Field_Text(__("Code"), 128);
        $code->set_visible()->set_filter();
        $fields[] = $code;

        $manufacturer = new CRM_Contacts_RBO_Company(__("Producer"));
        $manufacturer->set_visible()->set_filter();
        $fields[] = $manufacturer;

        $vendor = new CRM_Contacts_RBO_CompanyOrContact(__("Vendor"));
        $vendor->set_multiple(false)->set_visible()->set_filter();
        $fields[] = $vendor;

        $wholesale_price = new RBO_Field_Currency(__("Wholesale Price"));
        $fields[] = $wholesale_price;

        $retail_sales_price = new RBO_Field_Currency(__("Retail Sales Price"));
        $fields[] = $retail_sales_price;

        $permission = new RBO_Field_CommonData("Permission", "CRM/Access", true);
        $permission->set_required()->set_extra();
        $fields[] = $permission;

        $description = new RBO_Field_LongText("Description");
        $fields[] = $description;

        return $fields;
    }

    public function table_name()
    {
        return self::TABLE_NAME;
    }

    public static function display_name($record, $nolink = false)
    {
        if (is_numeric($record)) {
            $record = Utils_RecordBrowserCommon::get_record(self::TABLE_NAME, $record);
        }
        if ($nolink) {
            return $record['name'];
        }
        $info = array(
            "Producer" => CRM_ContactsCommon::company_format_default($record['producer'], TRUE),
            "Vendor" => CRM_ContactsCommon::autoselect_company_contact_format($record['vendor'], TRUE),
            "Description" => $record['description'],
            "Created By" => Base_UserCommon::get_user_label($record['created_by']),
            "Created On" => Base_RegionalSettingsCommon::time2reg($record['created_on']),
        );
        $tip = Utils_TooltipCommon::create($record['name'], Utils_TooltipCommon::format_info_tooltip($info));
        return Utils_RecordBrowserCommon::record_link_open_tag(
            self::TABLE_NAME, $record['id'], $nolink, 'view')
            . $tip
            . Utils_RecordBrowserCommon::record_link_close_tag();
    }
}
