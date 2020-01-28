<?php
/**
 *
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 1/27/20
 * @Time: 3:56 PM
 *
 * @property CRM_Criteria_RBO_Rules rules_rbo
 */

defined("_VALID_ACCESS") || die('Direct access forbidden');;

class CRM_Criteria extends Module
{
    /**
     * @var CRM_Criteria_RBO_Rules $rules_rbo
     */
    public $rules_rbo = null;

    public function construct()
    {
        $this->rules_rbo = new CRM_Criteria_RBO_Rules();
        $this->rules_rbo->refresh_magic_callbacks();
    }

    public function body()
    {
        $rules_rb = $this->rules_rbo->create_rb_module($this);
        $rules_rb->set_defaults(array(
            'permission' => 1,
            'recordset' => 'contact'
        ));
        $rules_rb->disable_export();
        $rules_rb->disable_pdf();

        $rules_rb->set_additional_actions_method(array($this, 'criteria_actions'));
        $this->display_module($rules_rb);
    }

    /**
     * @param $r CRM_Criteria_RBO_Rule
     * @param $gb_row Utils_GenericBrowser_RowObject
     */
    public function criteria_actions($r, $gb_row)
    {
        if ($r['criteria']) {
            $gb_row->add_action(
                $this->create_callback_href(
                    array($this, 'view_records'),
                    array($r)
                ),
                __('View Records'),
                null,
                Base_ThemeCommon::get_template_file(
                    CRM_CriteriaInstall::module_name(),
                    'icon-small.png')
            );
        }
    }

    public function view_records($record, $pop = false)
    {
        if ($this->is_back()) {
            if ($pop) {
                Base_BoxCommon::pop_main();
            } else {
                location(array());
            }
            return;
        }
        Base_ActionBarCommon::add("back", __("Back"), $this->create_back_href());
        $crits = Utils_RecordBrowser_QueryBuilderIntegration::json_to_crits($record['criteria']);
        /**
         * @var Utils_RecordBrowser $rb
         */
        $rb = $this->init_module(
            Utils_RecordBrowserInstall::module_name(),
            array($record['recordset']),
            'criteria_' . $record['id'] . '_view_records'
        );
        $rb->disable_add_button();
        $rb->disable_watchdog();
        $rb->set_caption($record['description']);
        $this->display_module($rb, array(array(), $crits));
        return true;
    }
}
