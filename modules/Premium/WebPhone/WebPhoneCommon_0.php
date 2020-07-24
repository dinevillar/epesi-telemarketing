<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 3/29/2020
 * @Time: 3:01 AM
 */
defined("_VALID_ACCESS") || die();

class Premium_WebPhoneCommon extends ModuleCommon
{

    public static function default_dialer($title)
    {
        $js = self::default_dialer_js($title);
        return "<a href='javascript:void(0);' onclick='$js'>$title</a>";
    }

    public static function default_dialer_js($title)
    {
        return "WebPhone.dial(\"$title\");";
    }

    public static function get_webphone_active()
    {
        $method = Base_User_SettingsCommon::get('CRM_Common', 'method');
        $selected = false;
        $web_phone_compatible = ModuleManager::call_common_methods('web_phone');
        foreach ($web_phone_compatible as $mod => $web_phone_impl) {
            if ($mod == $method) {
                $selected = $web_phone_impl;
                $selected['mod'] = $mod;
                break;
            }
        }
        return $selected;
    }

    public static function base_box_ini()
    {
        if ($selected = Premium_WebPhoneCommon::get_webphone_active()) {
            return [
                'web-phone' => [
                    'module' => self::module_name(),
                    'function' => 'body',
                    'display' => 'logged',
                    'orientation' => 'left'
                ]
            ];
        }
    }

    public static function base_box_tpl()
    {
        var_dump("TPL CALLED");
    }
}
