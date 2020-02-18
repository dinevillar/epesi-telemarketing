<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/09/20
 * @Time: 7:05 AM
 */
defined("_VALID_ACCESS") || die();

class Telemarketing_CallCampaignsInstall extends ModuleInstall
{
    const manage_permission = 'Manage Call Campaigns';

    /**
     * Module installation function.
     * @return true if installation success, false otherwise
     * @throws Exception
     */
    public function install()
    {
        Base_ThemeCommon::install_default_theme(self::module_name());
        Utils_RecordBrowserCommon::register_datatype(
            Telemarketing_CallCampaigns_RBO_LeadsList::type,
            'Telemarketing_CallCampaignsCommon', 'telemarketing_callcampaign_lead_list_datatype'
        );
        ModuleManager::include_common('Telemarketing_CallCampaigns', 0);
        $call_campaigns = new Telemarketing_CallCampaigns_RBO_Campaigns();
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
                Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME,
                array($this->get_type() . 'Common', 'watchdog_label')
            );
            $call_campaigns->set_favorites(true);
            $call_campaigns->register_processing_callback(
                array('Telemarketing_CallCampaignsCommon', 'submit_call_campaign')
            );
            Utils_AttachmentCommon::new_addon(Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME);

            Utils_RecordBrowserCommon::new_record(
                'phonecall_related',
                array('recordset' => Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME)
            );
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
            $call_campaigns = new Telemarketing_CallCampaigns_RBO_Campaigns();
            $call_campaigns->unregister_processing_callback(
                array('Telemarketing_CallCampaignsCommon', 'submit_call_campaign')
            );
            Utils_CommonDataCommon::remove('Contacts_Groups/telemarketer');
            if ($call_campaigns->uninstall()) {
//            Variable::delete('default_user_campaign_settings', false);
                Base_AclCommon::delete_permission(self::manage_permission);
                Utils_RecordBrowserCommon::unregister_datatype(Telemarketing_CallCampaigns_RBO_LeadsList::type);
                $phonecall_related = Utils_RecordBrowserCommon::get_records('phonecall_related', array(
                    'recordset' => Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME
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

    public function version()
    {
        return array(TelemarketingInstall::version);
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
            array('name' => CRM_CriteriaInstall::module_name(), 'version' => 0),
            array('name' => TelemarketingInstall::module_name(), 'version' => 0),
            array('name' => Telemarketing_CallScriptsInstall::module_name(), 'version' => 0)
        );
    }
}
