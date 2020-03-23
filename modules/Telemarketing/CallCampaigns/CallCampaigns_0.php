<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/17/20
 * @Time: 12:53 PM
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Telemarketing_CallCampaigns extends Module
{
    public function body()
    {
        $me = CRM_ContactsCommon::get_my_record();
        $campaigns_tb = new Telemarketing_CallCampaigns_RBO_Campaigns();
        $campaigns_tb->refresh_magic_callbacks();
        $rb = $campaigns_tb->create_rb_module($this);
        $rb->set_defaults(
            array(
                'start_date' => date('m/d/Y'),
                'end_date' => date('m/d/Y', strtotime("+1 month")),
                'telemarketers' => array($me['id']),
                'list_type' => 'AP',
                'status' => 0,
                'permission' => 1
            )
        );
        $rb->set_default_order(array('start_date' => 'DESC'));
        $rb->set_additional_actions_method(array($this, 'cc_campaigns_actions'));

        if (ModuleManager::is_installed("Telemarketing/CallCampaigns/Dispositions") >= 0) {
            $bl_type = Base_ThemeCommon::get_template_file(
                Telemarketing_CallCampaigns_DispositionsInstall::module_name(), 'blacklist.png'
            );
            Base_ActionBarCommon::add($bl_type, __("Blacklisted Records"),
                $this->create_callback_href(array($this, 'blacklisted_records')));
        }

        $this->display_module($rb);
    }

    public function blacklisted_records()
    {

    }
}
