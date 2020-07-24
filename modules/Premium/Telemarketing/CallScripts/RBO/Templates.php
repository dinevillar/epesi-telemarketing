<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/6/20
 * @Time: 1:42 PM
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Premium_Telemarketing_CallScripts_RBO_Templates extends RBO_Recordset
{
    const TABLE_NAME = "callscript_templates";
    const PAGES_TABLE_NAME = "callscript_templates_pages";

    /**
     * Return list of fields in recordset.
     * @return array RBO_FieldDefinition
     */
    function fields()
    {
        $callscript_name = new RBO_Field_Text(_M('Name'), 128);
        $callscript_name->set_required()->set_visible();

        $callscript_text = new RBO_Field_LongText(_M('Content'));

        $created_on = new RBO_Field_Calculated(_M("Created On"));
        $created_on->set_visible();

        $created_by = new RBO_Field_Calculated(_M("Created By"));
        $created_by->set_visible();

        $permission = new RBO_Field_CommonData('Permission', 'CRM/Access', true);
        $permission->set_required()->set_extra();

        return array($callscript_name, $created_on, $created_by, $callscript_text, $permission);
    }

    function display_created_on($record, $nolink = false)
    {
        if (!isset($record['id'])) {
            eval_js("jQuery('#_created_on__data').parent().remove()");
            return null;
        }
        $info = Utils_RecordBrowserCommon::get_record_info(self::TABLE_NAME, $record['id']);
        return $info['created_on'];
    }

    function display_created_by($record, $nolink = false)
    {
        if (!isset($record['id'])) {
            eval_js("jQuery('#_created_by__data').parent().remove()");
            return null;
        }
        $info = Utils_RecordBrowserCommon::get_record_info(self::TABLE_NAME, $record['id']);
        return CRM_ContactsCommon::get_user_label($info['created_by'], $nolink);
    }

    function display_content($record, $nolink = false)
    {
        return $record['content'];
    }

    function display_name($record, $nolink = false)
    {
        return Utils_RecordBrowserCommon::create_linked_label_r(
            Premium_Telemarketing_CallScripts_RBO_Templates::TABLE_NAME, 'Name', $record, $nolink);
    }

    function QFfield_content($form, $field, $label, $mode, $default, $args, $rb_obj)
    {
        $pageCache = array('1' => '');
        $submitValues = $form->getSubmitValues();
        if (isset($submitValues['pages']) && $submitValues['pages']) {
            $pageCache = json_decode($submitValues['pages']);
        } else {
            if ($mode == 'view' || $mode == 'edit') {
                $pages = Premium_Telemarketing_CallScriptsCommon::get_callscript_page($rb_obj->record['id']);
                $pageCache['1'] = $rb_obj->record['content'];
                foreach ($pages as $k => $v) {
                    $pageCache[$v['page']] = $v['content'];
                }
            }

        }
        $jsonPageCache = json_encode((object)$pageCache);

        $paginator = Premium_Telemarketing_CallScriptsCommon::get_paginator_by_pages_html(
            $form, (array)$pageCache
        );
        $form->addElement('static', 'pagination', '', $paginator);


        $fck = $form->addElement('ckeditor', $field, $label, array('style' => 'height:700px;'));
        $fck->setFCKProps('99%', '500', true);
        if ($mode == 'add' || $mode == 'edit') {
            if ($mode == 'edit') {
                $form->setDefaults(array($field => $default));
            }
            $excluded_target_fields = Premium_Telemarketing_CallScriptsCommon::get_excluded_contact_fields();
            $placeholders = array(
                "Target" => Premium_Telemarketing_MergeFieldsCommon::get_fields("contact", $excluded_target_fields),
                "Employee" => Premium_Telemarketing_MergeFieldsCommon::get_fields("contact", $excluded_target_fields, "emp")
            );
            if (ModuleManager::is_installed("Telemarketing/Products") >= 0) {
                $placeholders["Product"] = Premium_Telemarketing_MergeFieldsCommon::get_fields(Premium_Telemarketing_Products_RBO_Products::TABLE_NAME, array(), "product");
            }
            eval_js("CallScripts.mergeFields = " . json_encode($placeholders));
            $placeholders_html = Premium_Telemarketing_MergeFieldsCommon::get_accordion_html_for_ck($form, $fck, _M('Insert Merge Field'), $placeholders);
            $form->addElement('static', 'placeholders', '', $placeholders_html);
            $form->addElement('hidden', 'pages', $jsonPageCache);
        }
        $ck_module_path = '/modules/' . Premium_Telemarketing_CallScriptsInstall::module_name() . '/callscript-ck/';
        eval_js("jq('#ckeditor_" . $field . "').attr('rel', 'callscripts_ck');");
        eval_js("CallScripts.ckModulePath = '{$ck_module_path}';");
        eval_js("CallScripts.contentContainer = '#callscript_template_content';");
        eval_js("CallScripts.mode = '{$mode}';");
        eval_js("CallScripts.pageCache = {$jsonPageCache};");
        eval_js_once("CallScripts.ckInit();");
    }

    function QFfield_created_on($form, $field, $label, $mode, $default, $desc, $rb_obj)
    {
        if ($mode == "add" || $mode == "edit") {
            return;
        }
        Utils_RecordBrowserCommon::QFfield_timestamp(
            $form, $field, $label, $mode, $default, $desc, $rb_obj
        );
    }

    function QFfield_created_by($form, $field, $label, $mode, $default, $desc, $rb_obj)
    {
        if ($mode == "add" || $mode == "edit") {
            return;
        }
        Utils_RecordBrowserCommon::QFfield_static_display(
            $form, $field, $label, $mode, $default, $desc, $rb_obj
        );
    }

    /**
     * String that represents recordset in database
     *
     * @return string String that represents recordset in database
     */
    function table_name()
    {
        return self::TABLE_NAME;
    }
}
