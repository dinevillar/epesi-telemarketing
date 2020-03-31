<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 1/27/20
 * @Time: 3:48 PM
 */

class CRM_CriteriaCommon extends ModuleCommon
{
    public static function menu()
    {
        if (Base_AclCommon::check_permission('Criteria - Browse'))
            return array(_M('CRM') => array('__submenu__' => 1, _M('Criteria') => array()));
        else
            return array();
    }

    /**
     * @param $record
     * @param $mode
     * @return mixed
     * @throws Exception
     */
    public static function submit_criteria($record, $mode)
    {
        if ($mode === "edit") {
            if ($record['criteria'] instanceof Utils_RecordBrowser_CritsInterface) {
                $record['criteria'] = Utils_RecordBrowser_QueryBuilderIntegration::crits_to_json($record['criteria']);
                $record['criteria'] = json_encode($record['criteria']);
            }
        } else if ($mode === "display") {
            $main = Base_BoxCommon::main_module_instance();
            Base_ActionBarCommon::add(
                "view", __("View Records"),
                $main->create_callback_href('Base_BoxCommon::push_module', array(
                    CRM_Criteria::module_name(), 'view_records', array($record, true)
                ))
            );
        }
        return $record;
    }

    public static function get_raw_crits($criteria, $tab_alias = 'rest')
    {
        if (is_numeric($criteria)) {
            $criteria = Utils_RecordBrowserCommon::get_record('crm_criteria', $criteria);
        }
        $qb = new Utils_RecordBrowser_QueryBuilder($criteria['recordset'], $tab_alias);
        return $qb->to_sql(Utils_RecordBrowser_QueryBuilderIntegration::json_to_crits($criteria['criteria']));
    }
}
