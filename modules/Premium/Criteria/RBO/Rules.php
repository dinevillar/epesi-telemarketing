<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 1/27/20
 * @Time: 3:36 PM
 */

class Premium_Criteria_RBO_Rules extends RBO_Recordset
{
    const TABLE_NAME = "crm_criteria";

    function table_name()
    {
        return self::TABLE_NAME;
    }

    /**
     * Return list of fields in recordset.
     * @return array RBO_FieldDefinition
     */
    function fields()
    {
        $fields = array();

        $description = new RBO_Field_Text(_M("Description"), 100);
        $description->set_required()->set_visible();
        $fields[] = $description;

        $recordset = new RBO_Field_Text(_M("Recordset"), 64);
        $recordset->set_required()->set_visible();
        $fields[] = $recordset;


        $permission = new RBO_Field_CommonData(_M("Permission"), "CRM/Access", false);
        $permission->set_required()->set_visible();
        $fields[] = $permission;

        $criteria = new RBO_Field_LongText(_M("Criteria"));
        $fields[] = $criteria;

        return $fields;
    }

    function display_recordset($record, $nolink = false)
    {
        $opts = Utils_RecordBrowserCommon::list_installed_recordsets('%caption (%tab)');
        return $opts[$record['recordset']];
    }

    function display_description($record, $nolink = false)
    {
        return Utils_RecordBrowserCommon::create_linked_label_r(
            Premium_Criteria_RBO_Rules::TABLE_NAME,
            "Description",
            $record,
            $nolink
        );
    }

    /**
     * @param $form Libs_QuickForm
     */
    function QFfield_recordset($form, $field, $label, $mode, $default, $args, $rb_obj)
    {
        $opts = Utils_RecordBrowserCommon::list_installed_recordsets('%caption (%tab)');
        asort($opts);
        $form->addElement('select', $field, $label, $opts);
        $form->setDefaults(array($field => $default));
        if ($mode === "edit") {
            $form->freeze($field);
        }
    }

    function display_criteria($record)
    {
        if ($record['criteria']) {
            $ctw = new Utils_RecordBrowser_CritsToWords($record['recordset']);
            $crits = Utils_RecordBrowser_QueryBuilderIntegration::json_to_crits($record['criteria']);
            return $ctw->to_words($crits);
        }
        return "(" . __("Edit") . ")";
    }

    /**
     * @param $form Libs_QuickForm
     * @param $rb_obj Utils_RecordBrowser
     */
    function QFfield_criteria($form, $field, $label, $mode, $default, $args, $rb_obj)
    {
        if ($mode === "edit") {
            $qbi = new Utils_RecordBrowser_QueryBuilderIntegration($rb_obj->record['recordset']);
            $crits = null;
            if ($rb_obj->record['criteria']) {
                $crits = Utils_RecordBrowser_QueryBuilderIntegration::json_to_crits($rb_obj->record['criteria']);
            }
            $qb = $qbi->get_builder_module($rb_obj, $crits);
            $qb->add_to_form($form, $field, $label);
        } else if ($mode === "view") {
            $criteria_text = $this->display_criteria($rb_obj->record);
            if ($rb_obj->record['criteria']) {
                $ctw = new Utils_RecordBrowser_CritsToWords($rb_obj->record['recordset']);
                $crits = Utils_RecordBrowser_QueryBuilderIntegration::json_to_crits($rb_obj->record['criteria']);
                $form->addElement("static", $field, $label, $criteria_text);
            } else {
                $naviHref = $rb_obj->create_callback_href(array($rb_obj, 'navigate'), array('view_entry', 'edit', $rb_obj->record['id']));
                $form->addElement("static", $field, $label, "<a$naviHref>$criteria_text</a>");
            }
        } else if ($mode === "add") {
            $form->addElement("static", $field, $label);
            $form->setDefaults(array(
                $field => __(" (Setup after adding)")
            ));
        }
        $form->freeze($field);
        return;
    }
}
