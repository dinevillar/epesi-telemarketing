<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/13/20
 * @Time: 9:58 AM
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Premium_Telemarketing_CallCampaigns_DispositionsInstall extends ModuleInstall
{

    /**
     * Module installation function.
     * @return true if installation success, false otherwise
     */
    public function install()
    {
        $call_dispositions_status = array(
            'DNC' => _M('Do not call'),
            'BNA' => _M('Busy/No Answer'),
            'WDN' => _M('Wrong/Disconnected Number'),
            'I' => _M('Interested'),
            'CL' => _M('Call Later'),
            'NI' => _M('Not Interested'),
            'RF' => _M('Has Referral'),
            'RU' => _M('Record Update')
        );
        Utils_CommonDataCommon::new_array('CallCampaign/Dispositions', $call_dispositions_status);
        $call_dispositions = new Premium_Telemarketing_CallCampaigns_Dispositions_RBO_Status();
        if ($call_dispositions->install()) {
            $call_dispositions->add_access('view', 'ACCESS:employee');
            $call_dispositions->add_access('add', 'ACCESS:employee');
            $call_dispositions->add_access('edit', 'ACCESS:manager');
            $call_dispositions->add_access('delete', 'ACCESS:manager');
            $call_dispositions->set_caption(_M('Call Campaign Dispositions'));
            $call_dispositions->set_description_callback(
                array('Premium_Telemarketing_CallCampaigns_DispositionsCommon', 'disposition_desc_callback')
            );

            Utils_AttachmentCommon::new_addon(
                $call_dispositions::TABLE_NAME
            );

            Utils_RecordBrowserCommon::new_addon(
                Premium_Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME,
                self::module_name(), 'summary_addon',
                array('Premium_Telemarketing_CallCampaigns_DispositionsCommon', 'summary_addon_label')
            );

            Utils_RecordBrowserCommon::new_addon(
                Premium_Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME,
                self::module_name(), 'disposition_addon',
                array('Premium_Telemarketing_CallCampaigns_DispositionsCommon', 'disposition_addon_label')
            );

            Utils_RecordBrowserCommon::new_addon(
                "phonecall",
                self::module_name(), 'phonecall_addon',
                array('Premium_Telemarketing_CallCampaigns_DispositionsCommon', 'phonecall_addon_label')
            );

            Utils_RecordBrowserCommon::new_addon(
                "contact",
                self::module_name(), 'contact_addon',
                array('Premium_Telemarketing_CallCampaigns_DispositionsCommon', 'contact_addon_label')
            );

            Utils_RecordBrowserCommon::new_addon(
                "company",
                self::module_name(), 'company_addon',
                array('Premium_Telemarketing_CallCampaigns_DispositionsCommon', 'company_addon_label')
            );

            $blacklist = new Premium_Telemarketing_CallCampaigns_Dispositions_RBO_Blacklists();
            if ($blacklist->install()) {
                $blacklist->add_access('view', 'ACCESS:employee');
                $blacklist->add_access('add', 'ACCESS:employee');
                $blacklist->add_access(
                    'edit', 'ACCESS:employee',
                    array(':Created_by' => 'USER_ID')
                );
                $blacklist->add_access(
                    'edit', array('ACCESS:employee', 'ACCESS:manager')
                );
                $blacklist->add_access(
                    'delete', 'ACCESS:employee',
                    array(':Created_by' => 'USER_ID')
                );
                $blacklist->add_access(
                    'delete', array('ACCESS:employee', 'ACCESS:manager')
                );
                $blacklist->set_icon(
                    Base_ThemeCommon::get_template_filename(self::module_name(), 'blacklist.png')
                );
                $blacklist->register_processing_callback(
                    array('Premium_Telemarketing_CallCampaigns_DispositionsCommon', 'submit_blacklist')
                );
                Utils_RecordBrowserCommon::enable_watchdog(
                    $blacklist::TABLE_NAME,
                    array('Premium_Telemarketing_CallCampaigns_DispositionsCommon', 'blacklist_watchdog_label')
                );
                return true;
            }
        }
        return false;
    }

    /**
     * Module uninstallation function.
     * @return true if installation success, false otherwise
     */
    public function uninstall()
    {
        $blacklist = new Premium_Telemarketing_CallCampaigns_Dispositions_RBO_Blacklists();
        $blacklist->unregister_processing_callback(
            array('Premium_Telemarketing_CallCampaigns_DispositionsCommon', 'submit_blacklist')
        );
        if ($blacklist->uninstall()) {
            $call_dispositions = new Premium_Telemarketing_CallCampaigns_Dispositions_RBO_Status();
            if ($call_dispositions->uninstall()) {
                Utils_CommonDataCommon::remove('CallCampaign/Dispositions');
                Utils_AttachmentCommon::delete_addon(
                    $call_dispositions::TABLE_NAME
                );
                Utils_RecordBrowserCommon::delete_addon(
                    Premium_Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME,
                    self::module_name(), 'summary_addon'
                );
                Utils_RecordBrowserCommon::delete_addon(
                    Premium_Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME,
                    self::module_name(), 'disposition_addon'
                );
                Utils_RecordBrowserCommon::delete_addon(
                    'phonecall',
                    self::module_name(), 'phonecall_addon'
                );
                Utils_RecordBrowserCommon::delete_addon(
                    'contact',
                    self::module_name(), 'contact_addon'
                );
                Utils_RecordBrowserCommon::delete_addon(
                    'company',
                    self::module_name(), 'company_addon'
                );
                return true;
            }
        }
        return false;
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
            array('name' => 'Base', 'version' => 0),
            array('name' => 'CRM/Contacts', 'version' => 0),
            array('name' => 'Utils/CommonData', 'version' => 0),
            array('name' => 'Utils/Attachment', 'version' => 0),
            array('name' => 'Utils/RecordBrowser', 'version' => 0),
            array('name' => 'Premium/Telemarketing/CallCampaigns', 'version' => 0)
        );
    }

    public function version()
    {
        return array(Premium_TelemarketingInstall::version);
    }

    public function info()
    {
        return array(
            'Author' => '<a href="mailto:dean.villar@gmail.com">Rodine Mark Paul L. Villar</a>',
            'License' => 'MIT',
            'Description' => 'Telemarketing Call Campaign Dispositions'
        );
    }

    public function simple_setup()
    {
        return array(
            'package' => __('Telemarketing')
        );
    }

}
