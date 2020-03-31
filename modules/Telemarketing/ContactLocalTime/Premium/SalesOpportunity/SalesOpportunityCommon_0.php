<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 3/22/20
 * @Time: 8:20 AM
 */

class Telemarketing_ContactLocalTime_Premium_SalesOpportunityCommon extends ModuleCommon
{
    public static function display_local_time_sales_opp($record)
    {
        if ($record['customers'] && !empty($record['customers'])) {
            $lcl_time = "";
            $count = 0;
            $multiple = count($record['customers']) > 1;
            foreach ($record['customers'] as $customer) {
                $customer_met = explode(':', $customer);
                if ($customer_met[0] == 'P') {
                    $contact = Utils_RecordBrowserCommon::get_record('contact', $customer_met[1]);
                    $lcl_time .= self::local_time($contact, 'contact', true);
                    if ($multiple) {
                        $lcl_time .= ' [' . CRM_ContactsCommon::contact_format_no_company($contact, true) . ']';
                    }
                } else if ($customer_met[0] == 'C') {
                    $company = Utils_RecordBrowserCommon::get_record('company', $customer_met[1]);
                    $lcl_time .= self::local_time($company, 'company', true);
                    if ($multiple) {
                        $lcl_time .= ' [' . CRM_ContactsCommon::company_format_default($company, true) . ']';
                    }
                }
                $count++;
                if ($count < count($record['customers'])) {
                    $lcl_time .= "<br/>";
                }
            }
            return $lcl_time;
        }
        return '';
    }
}
