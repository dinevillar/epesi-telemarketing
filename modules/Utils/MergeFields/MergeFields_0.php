<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 02/06/2020
 * @Time: 9:35 AM
 */

defined("_VALID_ACCESS") || die();

class Utils_MergeFields extends Module
{

    const PATH = 'modules/Utils/MergeFields/';
    const JS_APPEND_FUNCTION = 'append_to_ck_or_textarea';

    public function get_accordion_html_for_ck($target_element, $label, $merge_field_groups, $insert_func = false)
    {

        if (is_object($target_element) && get_class($target_element) == 'HTML_QuickForm_ckeditor') {

            $element_id = 'ckeditor_' . $target_element->getName();
        } else {
            $element_id = $target_element;
        }
        $merge_fields_theme = $this->init_module('Base/Theme');
        $merge_fields_theme->assign('merge_fields_label', __($label));
        $merge_fields_theme->assign('merge_fields_group', $merge_field_groups);
        $merge_fields_theme->assign('element_id', $element_id);
        if (!$insert_func) {
            $merge_fields_theme->assign('insert_function_name', self::JS_APPEND_FUNCTION);
        } else {
            $merge_fields_theme->assign('insert_function_name', $insert_func);
        }
        return $merge_fields_theme->get_html('merge_fields_accordion');
    }

    public function body()
    {
        load_css(self::PATH . 'theme/merge_fields_accordion.css');
        load_js(self::PATH . 'js/merge_fields_accordion.js');
        //call init in js file.
        eval_js('Utils_MergeFields_InitJS();');
    }

}
