<?php

/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 3/22/20
 * @Time: 4:44 AM
 */

defined("_VALID_ACCESS") || die('Direct access forbidden');

class Premium_Telemarketing_ContactLocalTimeCommon extends ModuleCommon
{
    public static $IGNORED_FIELD_VALUES = ["notprovided", "n/a", "empty", "blank", "-", "notapplicable", "none"];

    /**
     * @param $record
     * @param string $type
     * @param bool $short
     * @param bool $returnDt
     * @param bool $nolink
     * @return DateTime|string
     * @throws Exception
     */
    public static function local_time($record, $type = 'contact', $short = false, $returnDt = false, $nolink = false)
    {
        if (!$record['local_time']) {
            $record['local_time'] = self::query_local_timezone($record, $type, true);
        }
        try {
            $d = new DateTime("now", new DateTimeZone($record['local_time']));
        } catch (Exception $e) {
            $d = new DateTime("now", new DateTimeZone('UTC'));
        }
        if ($returnDt) {
            return $d;
        }
        if ($short) {
            if (Base_RegionalSettingsCommon::time_12h()) {
                return $d->format('Y-m-d g:i A T');
            } else {
                return $d->format('Y-m-d H:i T');
            }
        } else {
            if (Base_RegionalSettingsCommon::time_12h()) {
                return $d->format('Y-m-d h:i A e');
            } else {
                return $d->format('Y-m-d H:i e');
            }
        }

    }

    /**
     * @param $record
     * @return DateTime|string
     * @throws Exception
     */
    public static function display_local_time($record)
    {
        return self::local_time($record);
    }

    /**
     * @param $record
     * @return DateTime|string
     * @throws Exception
     */
    public static function display_local_time_company($record)
    {
        return self::local_time($record, 'company');
    }

    /**
     * @param $record
     * @return DateTime|string
     * @throws Exception
     */
    public static function display_local_time_phonecall($record)
    {
        if ($record['customer']) {
            $customer = explode(':', $record['customer']);
            if ($customer[0] == 'P') {
                $contact = Utils_RecordBrowserCommon::get_record('contact', $customer[1]);
                return self::local_time($contact, 'contact', true);
            } else if ($customer[0] == 'C') {
                $company = Utils_RecordBrowserCommon::get_record('company', $customer[1]);
                return self::local_time($company, 'company', true);
            }
        }
        return '';
    }

    public static function clean($value)
    {
        $val = strtolower(trim($value));
        $val = preg_replace('/\s/', '', $val);
        if (in_array($val, self::$IGNORED_FIELD_VALUES)) {
            return "";
        }
        return $value;
    }

    public static function get_contact_location($r, $address = true, $delim = ", ")
    {
        $location = [];
        if ($address) {
            $a = "";
            if ($a1 = self::clean($r['address_1'])) {
                $a = $a1;
            }
            if ($a2 = self::clean($r['address_2'])) {
                if ($a) {
                    $a .= " " . $a2;
                } else {
                    $a = $a2;
                }
            }
            if ($a) {
                $location[] = $a;
            }
        }
        if ($c = self::clean($r['city'])) {
            $location[] = $c;
        }
        if ($z = self::clean($r['zone'])) {
            $location[] = $z;
        }
        if ($p = self::clean($r['postal_code'])) {
            $location[] = $p;
        }
        $location[] = Utils_CommonDataCommon::get_value('Countries/' . $r['country']);
        return implode($delim, $location);
    }

    /**
     * @param $record
     * @param $type
     * @param bool $update
     * @return string
     * @throws Exception
     */
    public static function query_local_timezone($record, $type, $update = false)
    {
        $tz = "UTC";
        $country = $record['country'];
        $city = $record['city'];
        if ($record['country']) {
            $query_geo = true;
            $tz_list = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $country);
            if (count($tz_list) === 1) {
                $tz = $tz_list[0];
                if ($record['id'] && $update) {
                    Utils_RecordBrowserCommon::update_record($type, $record["id"],
                        array("local_time" => $tz), false, null, true);
                }
                $query_geo = false;
            } else if (count($tz_list) > 1) {
                $selTz = $tz_list[0];
                if ($city) {
                    foreach ($tz_list as $tz) {
                        if (strpos(strtolower($tz), strtolower($city)) > -1) {
                            $selTz = $tz;
                            if ($record['id'] && $update) {
                                Utils_RecordBrowserCommon::update_record($type, $record["id"],
                                    array("local_time" => $selTz), false, null, true);
                            }
                            $query_geo = false;
                            break;
                        }
                    }
                }
                $tz = $selTz;
            }
            if ($query_geo && ModuleManager::is_installed("Libs/GoogleMaps") >= 0) {
                try {
                    $location = self::get_contact_location($record);
                    if ($googleMapsClient = Premium_GoogleMapsCommon::get_service_client()) {
                        $geocodeResult = $googleMapsClient->geocode($location);
                        if (count($geocodeResult) >= 1) {
                            $geocodeResult = $geocodeResult[0];
                            if ($loc = $geocodeResult['geometry']['location']) {
                                if (isset($loc['lat']) && isset($loc['lng'])) {
                                    $tzResult = $googleMapsClient->timezone([$loc['lat'], $loc['lng']]);
                                    if ($tzResult && isset($tzResult['timeZoneId'])) {
                                        $tz = $tzResult['timeZoneId'];
                                    }
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    //Fail silently
                }
            }
        }
        return $tz;
    }

    public static function qffield_local_time(&$form, $field, $label, $mode, $default, $desc, $rb_obj)
    {
        if ($mode == "add") {
            return;
        }
        $mode = "display";
        Utils_RecordBrowserCommon::QFfield_timestamp($form, $field, $label, $mode, $default, $desc, $rb_obj);
        $form->freeze($field);
    }

    /**
     * @param $record
     * @param $mode
     * @return mixed
     * @throws Exception
     */
    public static function submit_contact($record, $mode)
    {
        if ($mode == 'added') {
            self::query_local_timezone($record, 'contact', true);
        } else if ($mode == 'edited') {
            if (isset($record['zone']) || isset($record['country']) || isset($record['city'])) {
                $real_record = Utils_RecordBrowserCommon::get_record('contact', $record['id']);
                self::query_local_timezone($real_record, 'contact', true);
            }
        }
        return $record;
    }

    /**
     * @param $record
     * @param $mode
     * @return mixed
     * @throws Exception
     */
    public static function submit_company($record, $mode)
    {
        if ($mode == 'added') {
            self::query_local_timezone($record, 'company', true);
        } else if ($mode == 'edited') {
            if (isset($record['zone']) || isset($record['country']) || isset($record['city'])) {
                $real_record = Utils_RecordBrowserCommon::get_record('company', $record['id']);
                self::query_local_timezone($real_record, 'company', true);
            }
        }
        return $record;
    }
}
