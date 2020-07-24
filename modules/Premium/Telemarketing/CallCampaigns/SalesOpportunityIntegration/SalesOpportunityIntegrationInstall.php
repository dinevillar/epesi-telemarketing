<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/11/20
 * @Time: 5:18 PM
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Premium_Telemarketing_CallCampaigns_SalesOpportunityIntegrationInstall extends ModuleInstall
{
    const telemarketing_salesopp_source_key = 4;

    /**
     * Module installation function.
     * @return true if installation success, false otherwise
     * @throws NoSuchVariableException
     * @throws Exception
     */
    public function install()
    {
        Utils_CommonDataCommon::extend_array('Premium/SalesOpportunity/Source', array(
            self::telemarketing_salesopp_source_key => __('Telemarketing')
        ));
        Utils_CommonDataCommon::extend_array(
            'CallCampaign/Rules/Record/Called/AutoAdd',
            array(
                'SalesOpportunity' => _M('Sales Opportunity')
            )
        );
        Utils_CommonDataCommon::extend_array(
            'CallCampaign/Rules/Record/Flagged/Add',
            array(
                'SalesOpportunity' => _M('Sales Opportunity')
            )
        );
        Utils_CommonDataCommon::extend_array(
            'CallCampaign/Rules/Record/Flagged/AutoAdd',
            array(
                'SalesOpportunity' => _M('Sales Opportunity')
            )
        );

        $default_rules = Variable::get("telemarketing_default_rules", false);
        if ($default_rules) {
            array_push($default_rules, array(
                'type' => 'Record',
                'condition' => 'Flagged:I',
                'action' => 'Add:SalesOpportunity',
                'details' =>
                    array(
                        'add_opp_name' => '[last_name] [first_name] from call campaign [call_campaign_name]',
                        'add_opp_type' => '0',
                        'add_opp_probability' => '50',
                        'add_opp_contract_amount' => '[product_price]',
                        'add_opp_manager' => 'current',
                        'add_opp_lead_source' => '4',
                        'add_opp_status' => '0',
                        'add_opp_start_date' => 'current_date',
                        'add_opp_followup_date' => 'dynamic_date',
                        'add_opp_followup_dynamic_date_num' => '3',
                        'add_opp_followup_dynamic_date_denom' => '0',
                        'add_opp_description' => '[last_name] [first_name] is flagged as [call_campaign_disposition]. Lead from call campaign [call_campaign_name].',
                    ),
            ));
            Variable::set("telemarketing_default_rules", $default_rules);
        }
        return true;
    }

    /**
     * Module uninstallation function.
     * @return true if installation success, false otherwise
     * @throws NoSuchVariableException
     * @throws Exception
     */
    public function uninstall()
    {
        Utils_CommonDataCommon::remove('Premium/SalesOpportunity/Source/' . self::telemarketing_salesopp_source_key);
        Utils_CommonDataCommon::remove('CallCampaign/Rules/Record/Called/AutoAdd/SalesOpportunity');
        Utils_CommonDataCommon::remove('CallCampaign/Rules/Record/Flagged/Add/SalesOpportunity');
        $default_rules = Variable::get("telemarketing_default_rules", false);
        $key = array_search('Add:SalesOpportunity', array_column($default_rules, 'action'));
        if ($key > -1) {
            unset($default_rules[$key]);
            Variable::set("telemarketing_default_rules", $default_rules);
        }
        return true;
    }

    public function version()
    {
        return array(Premium_TelemarketingInstall::version);
    }

    public function simple_setup()
    {
        return array(
            'package' => __('Telemarketing'),
            'option' =>  __('Sales Opportunity Integration')
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
            array('name' => 'Utils/CommonData', 'version' => 0),
            array('name' => 'Premium/Telemarketing/CallCampaigns', 'version' => 0),
            array('name' => 'Premium/Telemarketing/CallCampaigns/Rules', 'version' => 0),
            array('name' => 'Premium/SalesOpportunity', 'version' => 0)
        );
    }

}
