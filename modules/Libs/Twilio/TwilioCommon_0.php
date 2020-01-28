<?php

defined("_VALID_ACCESS") || die();

require_once 'modules/Libs/Twilio/vendor/autoload.php';

class Libs_TwilioCommon extends ModuleCommon
{
    private static $rest_client = null;

    public static function get_rest_client()
    {
        if (self::$rest_client === null) {
            $sid = Variable::get("twilio_sid");
            $token = Variable::get("twilio_token");
            self::$rest_client = new \Twilio\Rest\Client($sid, $token);
        }
        return self::$rest_client;
    }
}
