<?php

defined("_VALID_ACCESS") || die('Direct access forbidden');

require_once(__DIR__ . "/vendor/autoload.php");

class Premium_GoogleMapsCommon extends ModuleCommon
{
    private static $instance = null;

    public static function admin_caption()
    {
        return array('label' => __('Google Maps'), 'section' => __('Server Configuration'));
    }

    public static function get_api_token()
    {
        return Variable::get('google_maps_token', false) ?: "";
    }

    /**
     * @return \yidas\googleMaps\Client|null
     * @throws Exception
     */
    public static function get_service_client()
    {
        $token = self::get_api_token();
        if (self::$instance === null && $token) {
            self::$instance = new yidas\googleMaps\Client(['key' => $token]);
        } else {
            return null;
        }
        return self::$instance;
    }
}
