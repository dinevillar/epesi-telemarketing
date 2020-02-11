<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/6/20
 * @Time: 1:50 PM
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Telemarketing_CallScriptsCommon extends ModuleCommon
{

    public static function menu()
    {
        return array(
            _M('Telemarketing') =>
                array('__submenu__' => 1, _M('Call Script Templates') => array('__weight__' => 1))
        );
    }

    public static function attachment_addon_label()
    {
        return array('show' => true, 'label' => __('Notes'));
    }

    public static function get_excluded_contact_fields()
    {
        $excluded_target_fields = array(
            "created_on",
            "referred_by",
            "fax",
            "last_email",
            "last_note",
            "last_phonecall",
            "quote_amount",
            "status",
            "can_receive_email",
            "can_receive_sms",
            "home_address_1",
            "home_address_2",
            "home_city",
            "home_zone",
            "home_postal_code",
            "home_country",
            "home_phone",
            "reassign_on"
        );
        return $excluded_target_fields;
    }

    public static function submit_callscript_templates($record, $mode)
    {
        if ($mode == 'adding') {
            $record['permission'] = 0;
        }
        if (($mode == 'add' || $mode == 'edit') && isset($record['pages'])) {
            $pages = json_decode($record['pages'], true);
            if (!$record['name']) {
                $record['name'] = '(' . __('Unnamed Call Script' . ')');
            }
            if ($mode == 'add') {
                $record['content'] = $pages['1'];
            } else if ($mode == 'edit') {
                foreach ($pages as $page => $content) {
                    if ($page == 1) {
                        $record['content'] = $content;
                    } else {
                        self::add_update_callscript_page($record['id'], $page, $content);
                    }
                }
                $saved_pages = self::get_callscript_page($record['id']);
                foreach ($saved_pages as $id => $saved_page) {
                    if (!isset($pages[$saved_page['page']])) {
                        self::delete_callscript_page($record['id'], $saved_page['page']);
                    }
                }
            }
        }
        if ($mode == 'added') {
            $pages = json_decode($record['pages']);
            foreach ($pages as $page => $content) {
                if ($page == 1) {
                    continue;
                } else {
                    self::add_update_callscript_page($record['id'], $page, $content);
                }
            }
        }
        return $record;
    }

    public static function get_paginator_by_pages_html($module, $pages, $client = 'CallScripts.paginate')
    {
        $paginator = $module->init_module(Utils_Paginator::module_name());
        $paginator->disable_all = true;
        $paginator->mid_range = 8;
        $paginator->set_client($client);
        $paginator->items_total = count($pages);
        return $paginator->get_paginator_html();
    }

    public static function get_paginator_html($module, $callscript, $client = 'CallScripts.paginate')
    {
        $id = $callscript;
        if (isset($callscript['id'])) {
            $id = $callscript['id'];
        } else if (!is_numeric($callscript)) {
            $id = false;
        }

        /**
         * @var Utils_Paginator $paginator
         */
        $paginator = $module->init_module(Utils_Paginator::module_name());
        $paginator->disable_all = true;
        $paginator->mid_range = 8;
        $paginator->set_client($client);
        if ($id !== false) {
            $pages = self::get_callscript_page($id);
            $paginator->items_total = count($pages) + 1;
        } else {
            $paginator->items_total = 1;
        }

        return $paginator->get_paginator_html();
    }

    public static function get_callscript_page($callscript, $page = 0)
    {
        $id = $callscript;
        if (isset($callscript['id'])) {
            $id = $callscript['id'];
        }
        $page_table = Telemarketing_CallScripts_RBO_Templates::PAGES_TABLE_NAME;
        if (!$page || $page < 1) {
            return DB::GetAssoc("SELECT * FROM {$page_table} WHERE `template_id` = {$id} ORDER BY `page` ASC");
        } else {
            return DB::GetRow("SELECT * FROM {$page_table} WHERE `template_id` = {$id} AND `page` = {$page}");
        }
    }

    public static function add_update_callscript_page($callscript, $page, $content)
    {
        $id = $callscript;
        if (isset($callscript['id'])) {
            $id = $callscript['id'];
        }
        $val = array(
            array(
                "field" => "page",
                "type" => Utils_RecordBrowserCommon::get_sql_type("integer"),
                "value" => $page
            ),
            array(
                "field" => "template_id",
                "type" => Utils_RecordBrowserCommon::get_sql_type("integer"),
                "value" => $id
            ),
            array(
                "field" => "content",
                "type" => Utils_RecordBrowserCommon::get_sql_type("text"),
                "value" => Utils_BBCodeCommon::optimize($content)
            )
        );
        $col_names = implode(",", array_map(function ($el) {
            return $el["field"];
        }, $val));
        $col_types = implode(",", array_map(function ($el) {
            return $el["type"];
        }, $val));
        $col_val = array_map(function ($el) {
            return $el["value"];
        }, $val);
        array_push($col_val, $val[2]['value']);
        $page_table = Telemarketing_CallScripts_RBO_Templates::PAGES_TABLE_NAME;
        $sql = "INSERT INTO {$page_table} ({$col_names}) VALUES({$col_types}) ON DUPLICATE KEY UPDATE `{$val[2]['field']}` = {$val[2]['type']}";
        return DB::Execute($sql, $col_val);
    }

    public static function delete_callscript_page($callscript, $page)
    {
        $id = $callscript;
        if (isset($callscript['id'])) {
            $id = $callscript['id'];
        }
        $page_table = Telemarketing_CallScripts_RBO_Templates::PAGES_TABLE_NAME;
        $int_type = Utils_RecordBrowserCommon::get_sql_type("integer");
        $sql = "DELETE FROM {$page_table} WHERE `template_id` = {$int_type} AND `page` = {$int_type}";
        $result = DB::Execute($sql, array($id, $page));
    }

}
