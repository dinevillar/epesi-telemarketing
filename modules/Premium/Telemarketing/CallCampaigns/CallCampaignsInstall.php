<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/09/20
 * @Time: 7:05 AM
 */
defined("_VALID_ACCESS") || die();

class Premium_Telemarketing_CallCampaignsInstall extends ModuleInstall
{
    const SETTINGS_TABLE = 'telemarketing_callcampaigns_settings';
    const manage_permission = 'Manage Call Campaigns';

    /**
     * Module installation function.
     * @return true if installation success, false otherwise
     * @throws Exception
     */
    public function install()
    {
        $lead_list_types = [
            'AP' => _M('All Contacts'),
            'AC' => _M('All Companies'),
            'APC' => _M('All Contacts & Companies'),
            "crm_criteria" => _M('Contact/Company Criteria')
        ];
        Utils_CommonDataCommon::new_array('CallCampaign/LeadListTypes', $lead_list_types);
        Base_ThemeCommon::install_default_theme(self::module_name());
        ModuleManager::include_common('Premium_Telemarketing_CallCampaigns', 0);
        $call_campaigns = new Premium_Telemarketing_CallCampaigns_RBO_Campaigns();
        if ($call_campaigns->install()) {
            Utils_CommonDataCommon::extend_array(
                'Contacts_Groups',
                array('telemarketer' => _M('Telemarketer'))
            );
            Base_AclCommon::add_permission(self::manage_permission, array('ACCESS:manager'));
            $call_campaigns->add_access(
                'view', 'ACCESS:employee',
                array('(!permission' => 2, '|telemarketers' => 'USER')
            );
            $call_campaigns->add_access(
                'add', 'ACCESS:employee'
            );
            $call_campaigns->add_access(
                'edit', 'ACCESS:employee',
                array('(permission' => 0, '|telemarketers' => 'USER')
            );
            $call_campaigns->add_access(
                'delete', 'ACCESS:employee',
                array(':Created_by' => 'USER_ID')
            );
            $call_campaigns->add_access(
                'delete',
                array('ACCESS:employee', 'ACCESS:manager')
            );
            $call_campaigns->set_caption(_M('Call Campaigns'));
            $call_campaigns->set_icon(Base_ThemeCommon::get_template_filename(
                self::module_name(),
                'icon.png'
            ));
            Utils_RecordBrowserCommon::enable_watchdog(
                Premium_Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME,
                array($this->get_type() . 'Common', 'watchdog_label')
            );
            $call_campaigns->set_favorites(true);
            $call_campaigns->register_processing_callback(
                array('Premium_Telemarketing_CallCampaignsCommon', 'submit_call_campaign')
            );
            Utils_AttachmentCommon::new_addon(Premium_Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME);
            $call_campaigns->set_tpl(Base_ThemeCommon::get_template_filename(
                self::module_name(),
                'View_entry'
            ));
            Utils_RecordBrowserCommon::new_record(
                'phonecall_related',
                array('recordset' => Premium_Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME)
            );
            $this->settings();
            return true;
        }
    }

    /**
     * Module uninstallation function.
     * @return true if installation success, false otherwise
     */
    public function uninstall()
    {
        try {
            Base_ThemeCommon::uninstall_default_theme(self::module_name());
            $call_campaigns = new Premium_Telemarketing_CallCampaigns_RBO_Campaigns();
            $call_campaigns->unregister_processing_callback(
                array('Premium_Telemarketing_CallCampaignsCommon', 'submit_call_campaign')
            );
            Utils_CommonDataCommon::remove('Contacts_Groups/telemarketer');
            $this->settings(false);
            if ($call_campaigns->uninstall()) {
                Utils_CommonDataCommon::remove('CallCampaign/LeadListTypes');
//            Variable::delete('default_user_campaign_settings', false);
                Base_AclCommon::delete_permission(self::manage_permission);
                $phonecall_related = Utils_RecordBrowserCommon::get_records('phonecall_related', array(
                    'recordset' => Premium_Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME
                ));
                if (count($phonecall_related) > 0) {
                    foreach ($phonecall_related as $related) {
                        Utils_RecordBrowserCommon::delete_record('phonecall_related', $related['id']);
                    }
                }
                return true;
            }
        } catch (Exception $e) {
            var_dump($e);
        }
        return false;
    }

    public function settings($install = true)
    {
        $campaign_tab = Premium_Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME;

        if ($install) {
            Utils_RecordBrowserCommon::new_addon(
                $campaign_tab,
                'Telemarketing/CallCampaigns',
                'settings_addon',
                array(
                    'Premium_Telemarketing_CallCampaignsCommon',
                    'settings_addon_label')
            );

            DB::CreateTable(self::SETTINGS_TABLE,
                '`id` I AUTO KEY,' .
                '`call_campaign_id` I,' .
                '`settings` XL',
                array('constraints' =>
                    ", UNIQUE KEY `cc_settings_uniq_call_campaign_id` (call_campaign_id), FOREIGN KEY (call_campaign_id) REFERENCES {$campaign_tab}_data_1(id) ON UPDATE CASCADE ON DELETE CASCADE")
            );

            $default_settings = array(
                'auto_call' => true,
                'auto_call_delay' => 3,
                'filter_inv_phone' => true,
                'auto_scroll' => true,
                'auto_scroll_speed' => 150,
                'allow_skip' => true,
                'newest_records_first' => false,
                'prioritize_call_backs' => true,
                'optimal_call_time_start' => array(
                    'H' => '9',
                    'i' => '0'
                ),
                'optimal_call_time_end' => array(
                    'H' => '21',
                    'i' => '0'
                ),
                'filter_not_optimal_call_time' => false,
                'prio_work' => 1,
                'prio_mobile' => 2,
                'prio_home' => 3,
            );
            Variable::set("telemarketing_default_settings", $default_settings);
        } else {
            Utils_RecordBrowserCommon::delete_addon(
                $campaign_tab,
                'Telemarketing/CallCampaigns',
                'settings_addon'
            );
            Variable::delete("telemarketing_default_settings", false);
            DB::DropTable(self::SETTINGS_TABLE);
        }
    }

    public function info()
    {
        return array(
            'Author' => '<a href="mailto:dean.villar@gmail.com">Rodine Mark Paul L. Villar</a>',
            'License' => 'MIT',
            'Description' => 'Telemarketing Call Campaigns'
        );
    }

    public static function simple_setup()
    {
        return array('package' => __('Telemarketing'));
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
            array('name' => 'Base', 'version' => 0),
            array('name' => 'Utils/RecordBrowser', 'version' => 0),
            array('name' => 'Utils/TabbedBrowser', 'version' => 0),
            array('name' => 'Utils/CommonData', 'version' => 0),
            array('name' => 'Utils/Attachment', 'version' => 0),
            array('name' => 'Libs/QuickForm', 'version' => 0),
            array('name' => 'CRM/Contacts', 'version' => 0),
            array('name' => 'Premium/Telemarketing/CallScripts', 'version' => 0),
            array('name' => 'Premium/Telemarketing/ContactLocalTime', 'version' => 0)
        );

    }
    public function version()
    {
        return array(Premium_TelemarketingInstall::version);
    }
}
