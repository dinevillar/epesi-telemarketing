<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/11/20
 * @Time: 4:53 PM
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Premium_Telemarketing_Products_SalesOpportunityIntegration extends Module
{
    public function sales_opportunities_addon($arg)
    {
        $rb = $this->init_module('Utils/RecordBrowser', 'premium_salesopportunity', 'premium_salesopportunity');
        $params = array(
            array('product' => $arg['id']),
            array('product' => false),
            array('id' => 'DESC')
        );

        $me = CRM_ContactsCommon::get_my_record();
        $rb->set_defaults(
            array(
                'product' => $arg['id'],
                'opportunity_manager' => $me['id'],
                'employees' => $me['id'],
                'start_date' => date('Y-m-d'), 'status' => 0)
        );
        $this->display_module($rb, $params, 'show_data');
    }
}
