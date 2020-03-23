<?php

defined("_VALID_ACCESS") || die('Direct access forbidden');

class Telemarketing_CallCampaigns_Premium_ListManagerCommon extends ModuleCommon
{
    public static function get_call_campaign_list_manager_crits()
    {
        return array('type' => Premium_ListManagerCommon::get_list_id('Call campaign'));
    }

    public static function QFfield_lead_list($form, $field, $label, $mode, $default, $args, $rb_obj)
    {
        if ($mode === 'add' || $mode === 'edit') {
            $lists = Utils_RecordBrowserCommon::get_records(
                'premium_listmanager',
                Telemarketing_CallCampaigns_Premium_ListManagerCommon::get_call_campaign_list_manager_crits()
            );
            $list_options = [
                '' => '---'
            ];
            foreach ($lists as $list) {
                $list_options[$list['id']] = Utils_RecordBrowserCommon::create_default_linked_label(
                    'premium_listmanager', $list['id'], true
                );
            }
            $form->addElement(
                'select',
                $field . "_premium_listmanager",
                __("List Manager Lists"),
                $list_options
            );
        }
    }

    public static function call_campaign_listtype($type, $param = null, $nolink = false)
    {
        if ($type == 'general') {
            $me = CRM_ContactsCommon::get_my_record();
            return array('contact' => array(
                'crits' => array(),
                'cols' => array('work_phone' => true, 'home_phone' => true, 'mobile_phone' => true),
                'format_callback' => array('CRM_ContactsCommon', 'contact_format_default')
            ), 'company' => array(
                'crits' => array(),
                'format_callback' => array('CRM_ContactsCommon', 'display_company')
            ));
        } else {
            switch ($param['record_type']) {
                case 'contact':
                    $ret = array();
                    $r = CRM_ContactsCommon::get_contact($param['record_id']);
                    $ret['target'] = CRM_ContactsCommon::contact_format_default($r, $nolink);
                    $ret['data'] = $r['email'];
                    $phones = array();
                    foreach (array('work_phone' => __('Work Phone'), 'mobile_phone' => __('Mobile Phone'), 'home_phone' => __('Home Phone')) as $id => $label) {
                        if (!$r[$id]) continue;
                        $phones[] = '<b>' . $label[0] . ':</b> ' . $r[$id];
                    }
                    $ret['data'] = implode(str_pad('&nbsp;', 30, '&nbsp;'), $phones);
                    if (empty($phones)) $ret['warning'] = true;
                    return $ret;
                case 'company':
                    $ret = array();
                    $r = Utils_RecordBrowserCommon::get_record('company', $param['record_id']);
                    $ret['target'] = $r['company_name'];
                    if (!$nolink) $ret['target'] = Utils_RecordBrowserCommon::record_link_open_tag('company', $r['id']) . $ret['target'] . Utils_RecordBrowserCommon::record_link_close_tag();
                    $ret['data'] = $r['phone'];
                    if (!$r['phone']) $ret['warning'] = true;
                    return $ret;
            }
        }
    }
}
