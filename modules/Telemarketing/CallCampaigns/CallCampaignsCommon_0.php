<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/17/20
 * @Time: 9:52 AM
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Telemarketing_CallCampaignsCommon extends ModuleCommon
{

    public static function menu()
    {
        return array(
            _M('Telemarketing') =>
                array('__submenu__' => 1, _M('Call Campaigns') => array('__weight__' => 1))
        );
    }

    public static function crm_criteria_callcampaign_crits()
    {
        return array(
            'recordset' => array('contact', 'company')
        );
    }

    public static function telemarketing_callcampaign_lead_list_datatype($field)
    {
        if (!isset($field['display_callback'])) $field['display_callback'] =
            array('Telemarketing_CallCampaignsCommon', 'display_lead_list');
        $field['type'] = $field['param']['field_type'];

        if (!isset($field['QFfield_callback'])) $field['QFfield_callback'] =
            array('Telemarketing_CallCampaignsCommon', 'QFfield_lead_list');
        $field['type'] = $field['param']['field_type'];

        $crits_callback = isset($field['param']['crits']) ? $field['param']['crits'] : array('', '');
        $crits_callback = is_array($crits_callback) ? implode('::', $crits_callback) : $crits_callback;

        $format_callback = isset($field['param']['format']) ?
            $field['param']['format'] :
            array('Telemarketing_CallCampaignsCommon', 'telemarketing_callcampaign_lead_list_select_options');
        $format_callback = is_array($format_callback) ? implode('::', $format_callback) : $format_callback;

        $field['param'] = "$crits_callback;$format_callback";
        return $field;
    }

    public static function decode_record_token($token)
    {
        $token = trim($token);
        $preg_ret = preg_match('#(^[a-zA-Z_0-9]+([:/])[a-zA-Z_0-9]+$)|(^[a-zA-Z_0-9]+$)#', $token, $matches);
        if ($preg_ret === false || $preg_ret === 0) {
            return array(false, false);
        }
        $delimiter = $matches[2];
        if (!$delimiter) {
            return array('contact', $token);
        }
        $exploded = explode($delimiter, $token);
        list($tab, $id) = $exploded;
        if ($delimiter == ':') {
            if ($tab == 'AP') $tab = 'contact';
            if ($tab == 'AC') $tab = 'company';
            if ($tab == 'CR') $tab = 'crm_criteria';
            if ($tab == 'LM') $tab = 'premium_listmanager';
        }
        return array($tab, $id);
    }

    public static function auto_lead_list_suggestbox($str, $fcallback)
    {
        $words = explode(' ', trim($str));
        $final_nr_of_records = 10;
        $recordset_records = array();
        $ref_recordset = array('crm_criteria' => 'CR');
        if (ModuleManager::is_installed("Premium/ListManager") >= 0) {
            $ref_recordset['premium_listmanager'] = 'LM';
        }
        foreach ($ref_recordset as $recordset => $recordset_indicator) {
            $crits = array();
            foreach ($words as $word) if ($word) {
                $word = "%$word%";
                switch ($recordset) {
                    case 'crm_criteria':
                        $crits = self::crm_criteria_callcampaign_crits();
                        $crits = Utils_RecordBrowserCommon::merge_crits($crits, array('~description' => $word));
                        $order = array('description' => 'ASC');
                        break;
                    case 'premium_listmanager':
                        $crits = Utils_RecordBrowserCommon::merge_crits($crits, array('~list_name' => $word));
                        $order = array('list_name' => 'ASC');
                        break;
                }
            }
            $recordset_records[$recordset_indicator] = Utils_RecordBrowserCommon::get_records($recordset, $crits, array(), $order, $final_nr_of_records);
        }
        $total = 0;
        foreach ($recordset_records as $records)
            $total += count($records);
        if ($total != 0)
            foreach ($recordset_records as $key => $records)
                $recordset_records[$key] = array_slice($records, 0, ceil($final_nr_of_records * count($records) / $total));
        $ret = array(
            "AC:ALL" => __("[Companies]") . " ALL",
            "AP:ALL" => __("[Contacts]") . " ALL"
        );
        foreach ($recordset_records as $recordset_indicator => $records) {
            foreach ($records as $rec) {
                $key = $recordset_indicator . ':' . $rec['id'];
                $ret[$key] = call_user_func($fcallback, $key, true);
            }
        }
        asort($ret);
        return $ret;
    }

    /**
     * @param Libs_QuickForm $form
     * @param $field
     * @param $label
     * @param $mode
     * @param $default
     * @param $desc
     * @param Utils_RecordBrowser $rb_obj
     */
    public function QFfield_lead_list(&$form, $field, $label, $mode, $default, $desc, $rb_obj = null)
    {
        $cont = array();
        if ($mode == 'add' || $mode == 'edit') {
            $fcallback = array('Telemarketing_CallCampaignsCommon', 'lead_list_format');
            if ($desc['type'] == 'multiselect') {
                $form->addElement(
                    'automulti', $field, $label,
                    array('Telemarketing_CallCampaignsCommon', 'auto_lead_list_suggestbox'),
                    array($fcallback), $fcallback
                );
            } else {
                $form->addElement(
                    'autoselect', $field, $label, $cont,
                    array(array('Telemarketing_CallCampaignsCommon', 'auto_lead_list_suggestbox'),
                        array($fcallback)), $fcallback, array('id' => $field)
                );
            }
            $form->setDefaults(array($field => $default));
        } else {
            $callback = $rb_obj->get_display_callback($desc['name']);
            if (!$callback) $callback = 'Telemarketing_CallCampaignsCommon::display_lead_list';
            $def = Utils_RecordBrowserCommon::call_display_callback(
                $callback, $rb_obj->record,
                false, $desc, $rb_obj->tab
            );
            $form->addElement('static', $field, $label, $def);
        }
    }

    public static function display_lead_list($record, $nolink, $desc)
    {
        $v = $record[$desc['id']];
        if (!is_array($v) && !preg_match('#([a-zA-Z]+/[1-9][0-9]*)|((AC|AP|CR|LM):[a-zA-Z_0-9]*)#', $v)) return $v;
        $def = '';
        if (!is_array($v)) $v = array($v);
        if (count($v) > 100) return count($v) . ' ' . __('values');
        foreach ($v as $k => $w) {
            if ($def) $def .= '<br>';
            $def .= Utils_RecordBrowserCommon::no_wrap(self::lead_list_format($w, $nolink));
        }
        if (!$def) $def = '---';
        return $def;
    }

    public static function telemarketing_callcampaign_lead_list_select_options()
    {
        return array('format_callback' => array('Telemarketing_CallCampaignsCommon', 'lead_list_format'));
    }

    public static function lead_list_format($arg, $nolink = false)
    {
        $icon = array(
            'company' => Base_ThemeCommon::get_template_file(CRM_Contacts::module_name(), 'company.png'),
            'contact' => Base_ThemeCommon::get_template_file(CRM_Contacts::module_name(), 'person.png'),
            'crm_criteria' => Base_ThemeCommon::get_template_file(CRM_CriteriaInstall::module_name(), 'icon.png'),
        );
        if (ModuleManager::is_installed('Premium / ListManager') >= 0) {
            $icon['premium_listmanager'] = Base_ThemeCommon::get_template_file('Premium/ListManager', 'icon.png');
        }

        //backward compatibility
        $id = null;

        if (!is_array($arg)) {
            list($tab, $id) = self::decode_record_token($arg);
        } else {
            $id = $arg['id'];
            $tab = "contact";
        }

        if ($id === "ALL") {
            $val = $id;
        } else {
            if (!$id) return '---';
            $val = Utils_RecordBrowserCommon::create_default_linked_label($tab, $id, $nolink, false);
        }

        switch ($tab) {
            case 'company':
                $indicator_text = __('Companies');
                break;
            case 'crm_criteria':
                $indicator_text = __('Criteria');
                break;
            case 'premium_listmanager':
                $indicator_text = __('List');
                break;
            default:
                $indicator_text = __('Contacts');
                break;
        }
        $rindicator = isset($icon[$tab]) ?
            "<span style=\"margin:1px 0.5em 1px 1px; width:1.5em; height:1.5em; display:inline-block; vertical-align:middle; background-image:url('" . $icon[$tab] . "'); background-repeat:no-repeat; background-position:left center; background-size:100%\"><span style=\"display:none\">[" . $indicator_text . "]</span></span>" : "[$indicator_text] ";
        $val = $rindicator . $val;
        if ($nolink) {
            return strip_tags($val);
        }
        return $val;
    }

    public static function submit_call_campaign($values, $mode)
    {
        return $values;
    }

    public static function campaign_format($record, $nolink = false)
    {
        $t = "Call Campaign - " . $record['name'];
        if (!$nolink) {
            $t = ' < a href = "' . Utils_RecordBrowserCommon::create_record_href(
                    Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME, $record['id']
                ) . '" > ' . $t . '</a > ';
        }
        return $t;
    }
}
