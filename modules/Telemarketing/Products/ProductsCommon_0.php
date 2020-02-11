<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/11/20
 * @Time: 1:00 PM
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

/**
 * Class Telemarketing_ProductsCommon
 */
class Telemarketing_ProductsCommon extends ModuleCommon
{
    /**
     * @return array
     */
    public static function menu()
    {
        return array(
            _M('Telemarketing') =>
                array('__submenu__' => 1, _M('Products') => array('__weight__' => 2))
        );
    }

    /**
     * @param null $rid
     * @param array $events
     * @param bool $details
     * @return array|null
     */
    public function product_watchdog_label($rid = NULL, $events = array(), $details = TRUE)
    {
        return Utils_RecordBrowserCommon::watchdog_label(
            Telemarketing_Products_RBO_Products::TABLE_NAME, __('Products'), $rid, $events,
            array('ContactedBase_Products_RBO_ProductItems', 'display_name'), $details
        );
    }

    /**
     * @param $values
     * @param $mode
     * @return mixed
     */
    public static function submit_products($values, $mode)
    {
        if ($mode == "adding") {
            $values['permission'] = Base_User_SettingsCommon::get(
                'CRM_Common',
                'default_record_permission'
            );
        }
        return $values;
    }

}
