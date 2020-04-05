<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 4/2/2020
 * @Time: 10:53 PM
 */
defined("_VALID_ACCESS") || die();

class Telemarketing_CallCampaigns_ReportsCommon extends ModuleCommon
{
    public static function menu()
    {
        if (Acl::check_permission(Telemarketing_CallCampaignsInstall::manage_permission)) {
            return
                array(
                    _M('Reports') => array(
                        '__submenu__' => 1, '__weight__' => 7,
                        _M('Call Campaigns by Telemarketer') => array('__weight__' => 1, 'report_mode' =>
                            Telemarketing_CallCampaigns_Reports::MODE_TELEMARKETER),
                        _M('Call Campaigns') => array('report_mode' => Telemarketing_CallCampaigns_Reports::MODE_CALL_CAMPAIGNS),
                    )
                );
        } else {
            return array();
        }
    }

    public static function get_report_categories()
    {
        $cats = Utils_CommonDataCommon::get_array('CallCampaign/Dispositions');
        $cats['AVGTT'] = __("Average Talk Time (seconds)");
        return $cats;
    }

    public static function get_report_categories_format()
    {
        $cats = self::get_report_categories();
        $formats = array();
        foreach ($cats as $k => $v) {
            $formats[$v] = 'numeric';

        }
        return $formats;
    }

    public static function get_report_date_format($type)
    {
        $format = 'd M Y';
        switch ($type) {
            case 'day':
                $format = 'd M Y';
                break;
            case 'week':
                $format = 'W Y';
                break;
            case 'month':
                $format = 'M Y';
                break;
            case 'year':
                $format = 'Y';
                break;
        }

        return $format;
    }

    public static function get_report_to_from_date($date, $type)
    {
        $from = new DateTime();
        $from->setTimestamp($date);
        switch ($type) {
            case 'week':
                $to = clone $from;
                $to->add(new DateInterval('P1W'));
                break;
            case 'month':
                $from->setDate(date('Y', $date), date('m', $date), 1);
                $to = clone $from;
                $to->add(new DateInterval('P1M'));
                break;
            case 'year':
                $from->setDate(date('Y', $date), date('m', $date), 1);
                $to = clone $from;
                $to->add(new DateInterval('P1Y'));
                break;
            default:
                $to = clone $from;
                $to->add(new DateInterval('P1D'));
                break;
        }
        return array(
            "from" => $from,
            "to" => $to
        );
    }

    public static function count_dispositions(
        $campaign,
        $employee = false,
        $disp = false,
        $from = false,
        $to = false,
        $tz = false)
    {
        $dispositions_rbo = new Telemarketing_CallCampaigns_Dispositions_RBO_Status();
        $crits = array(
            'call_campaign' => $campaign['id']
        );
        if ($employee) {
            $crits['telemarketer'] = $employee['id'];
        }
        if ($disp) {
            $crits['disposition'] = $disp;
        }
        $dispos = $dispositions_rbo->get_records($crits);
        $count = count($dispos);
        if ($from !== false && $to !== false) {
            if ($tz === false) {
                $tz = new DateTimeZone(SYSTEM_TIMEZONE);
            }
            $count = 0;
            foreach ($dispos as $dispo) {
                if ($dispo['timestamp']) {
                    $ts = new DateTime($dispo['timestamp']);
                    $ts->setTimezone($tz);
                    if ($ts >= $from && $ts < $to) {
                        $count++;
                    }
                }
            }
        }
        return $count;
    }

    public static function compute_avg_talk_time(
        $campaign,
        $employee = false,
        $disp = false,
        $from = false,
        $to = false,
        $tz = false)
    {
        $dispositions_rbo = new Telemarketing_CallCampaigns_Dispositions_RBO_Status();
        $crits = array(
            'call_campaign' => $campaign['id'],
            '>=talk_time' => 0
        );
        if ($employee) {
            $crits['telemarketer'] = $employee['id'];
        }
        if ($disp) {
            $crits['disposition'] = $disp;
        }
        $dispos = $dispositions_rbo->get_records($crits);
        $tt_dispos = array();
        if ($from !== false && $to !== false) {
            if ($tz === false) {
                $tz = new DateTimeZone(SYSTEM_TIMEZONE);
            }
            foreach ($dispos as $dispo) {
                if ($dispo['timestamp']) {
                    $ts = new DateTime($dispo['timestamp']);
                    $ts->setTimezone($tz);
                    if ($ts > $from && $ts <= $to) {
                        $tt_dispos[] = $dispo;
                    }
                }
            }
        } else {
            $tt_dispos = $dispos;
        }

        $total_tt = 0;
        foreach ($tt_dispos as $tt_dispo) {
            $total_tt += $tt_dispo['talk_time'];
        }
        if ($total_tt > 0) {
            $total_tt = round($total_tt / count($tt_dispos), 2);
        }
        return $total_tt;
    }

}
