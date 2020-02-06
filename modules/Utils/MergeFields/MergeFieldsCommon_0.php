<?php

/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 02/06/2020
 * @Time: 8:45 AM
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Utils_MergeFieldsCommon extends ModuleCommon
{

    //excluded database fields
    private static $MERGE_FIELD_EXCLUDES = array(
        "f_login"
    );

    //merge field opening and closing identifiers
    const MERGE_OPEN_ID = "[";
    const MERGE_CLOSE_ID = "]";

    public static function get_fields($recordset, $additionalExcludes = array(), $prefix = "", $pretty_print = true, $sort = false, $optionize = false)
    {

        $merge_fields = array();
        //Fails on an empty recordset
//        $record_info = DB::GetRow('SELECT * FROM ' . $recordset . '_data_1');
        $record_info = DB::GetCol("SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`='" . DATABASE_NAME . "' AND `TABLE_NAME`='" . $recordset . "_data_1';");
        $field_names = DB::GetCol("SELECT field FROM " . $recordset . "_field WHERE active=1 AND type " .
            "NOT IN ('foreign index', 'page_split','multiselect','calculated') AND field NOT IN " .
            "('Permission')");
        if ($sort) {
            sort($field_names);
        }
        foreach ($field_names as $field) {
            //replace spaces with underscores
            $merge_field_key = strtolower(str_replace(' ', '_', $field));
            //prefix f_ to match database fields
            $database_field = 'f_' . $merge_field_key;
            $bool_include_field = in_array($database_field, $record_info) &&
                !in_array($database_field, self::$MERGE_FIELD_EXCLUDES) &&
                !in_array($merge_field_key, $additionalExcludes);
            //add prefix to key if present
            $merge_field_key_prefixed = ($prefix ? $prefix . "_" : "") . $merge_field_key;
            if ($bool_include_field && $pretty_print) {
                $merge_field_value = ucwords(str_replace("_", " ", $merge_field_key));
                $merge_fields[$merge_field_key_prefixed] = $merge_field_value;
            } elseif ($bool_include_field && !$pretty_print) {
                array_push($merge_fields, $merge_field_key);
            }
        }
        if ($optionize) {
            $option_text = "";
            foreach ($merge_fields as $k => $v) {
                $option_text .= "<option value='$k'>$v</option>";
            }
            return $option_text;
        } else {
            return $merge_fields && !empty($merge_fields) ? $merge_fields : FALSE;
        }
    }

    public static function parse_fields($recordset, $record, $text, $prefix = "", $excluded_fields = array(), $excluded_field_types = array())
    {
        if ($record['id'] == -1) {
            //do nothing
            return $text;
        }
        $merge_fields = Utils_MergeFieldsCommon::get_fields($recordset, $excluded_fields, $prefix);
        foreach ($merge_fields as $merge_field_key => $merge_field_value) {
            if (!empty($prefix)) {
                $key = str_replace($prefix . '_', '', $merge_field_key);
            } else {
                $key = $merge_field_key;
            }
            $value = Utils_RecordBrowserCommon::get_val($recordset, $key, $record, true);
            $text = str_replace(self::MERGE_OPEN_ID . $merge_field_key . self::MERGE_CLOSE_ID, $value, $text);
        }
        return $text;
    }

    public static function get_accordion_html_for_ck($any_module_instance, $target_element, $label, $merge_field_groups, $insert_func = false)
    {
        if ($any_module_instance instanceof Module) {
            $merge_fields_module = $any_module_instance->init_module('Utils/MergeFields');
            return $merge_fields_module->get_accordion_html_for_ck($target_element, $label, $merge_field_groups, $insert_func);
        } else {
            trigger_error('Object should be instance of Module class', E_USER_ERROR);
        }
    }

    public static function mergify_placeholder($placeholder)
    {
        return self::MERGE_OPEN_ID . $placeholder . self::MERGE_CLOSE_ID;
    }

    public static function has_merge_fields($recordset, $text, $prefix = "",
                                            $excluded_fields = array())
    {
        $merge_fields = self::get_fields($recordset, $excluded_fields, $prefix, false);
        foreach ($merge_fields as $merge_field) {
            if (strpos($text, $merge_field)) {
                return true;
            }
        }
        return false;
    }

}
