<?php

class Telemarketing_CallCampaigns_Premium_ListManagerInstall extends ModuleInstall
{

    /**
     * Module installation function.
     * @return true if installation success, false otherwise
     */
    public function install()
    {
        Utils_CommonDataCommon::extend_array(
            'CallCampaign/LeadListTypes',
            array('premium_listmanager' => _M("List Manager Lists")));
        Premium_ListManagerCommon::add_list_type(
            _M('Call campaign'), array(
                'Telemarketing_CallCampaigns_Premium_ListManagerCommon', 'call_campaign_listtype'
            )
        );
        return true;
    }

    /**
     * Module uninstallation function.
     * @return true if installation success, false otherwise
     */
    public function uninstall()
    {
        Utils_CommonDataCommon::remove('CallCampaign/LeadListTypes/premium_listmanager');
        Premium_ListManagerCommon::delete_list_type('Call campaign');
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
            array('name' => 'Premium/ListManager', 'version' => 0),
        );
    }

    public function version()
    {
        return array(TelemarketingInstall::version);
    }
}
