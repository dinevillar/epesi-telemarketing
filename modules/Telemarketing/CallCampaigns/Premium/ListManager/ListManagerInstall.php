<?php

class Telemarketing_CallCampaigns_Premium_ListManagerInstall extends ModuleInstall
{

    /**
     * Module installation function.
     * @return true if installation success, false otherwise
     */
    public function install()
    {
        try {
            Utils_CommonDataCommon::extend_array(
                'CallCampaign/LeadListTypes',
                array('premium_listmanager' => _M("List Manager Lists")));
            Utils_CommonDataCommon::extend_array(
                'CallCampaign/Rules/Record/Called/AutoAdd',
                array(
                    'ToList' => _M('To List')
                )
            );
            Utils_CommonDataCommon::extend_array(
                'CallCampaign/Rules/Record/Flagged/AutoAdd',
                array(
                    'ToList' => _M('To List')
                )
            );
            Premium_ListManagerCommon::add_list_type(
                _M('Call campaign'), array(
                    'Telemarketing_CallCampaigns_Premium_ListManagerCommon', 'call_campaign_listtype'
                )
            );
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Module uninstallation function.
     * @return true if installation success, false otherwise
     */
    public function uninstall()
    {
        try {
            Utils_CommonDataCommon::remove('CallCampaign/LeadListTypes/premium_listmanager');
            Utils_CommonDataCommon::remove('CallCampaign/Rules/Record/Called/AutoAdd/ToList');
            Utils_CommonDataCommon::remove('CallCampaign/Rules/Record/Flagged/AutoAdd/ToList');
            Premium_ListManagerCommon::delete_list_type('Call campaign');
        } catch (Exception $e) {
            return false;
        }
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
            array('name' => Telemarketing_CallCampaignsInstall::module_name(), 'version' => 0),
            array('name' => Telemarketing_CallCampaigns_RulesInstall::module_name(), 'version' => 0),
            array('name' => 'Premium/ListManager', 'version' => 0),
        );
    }

    public function version()
    {
        return array(TelemarketingInstall::version);
    }
}
