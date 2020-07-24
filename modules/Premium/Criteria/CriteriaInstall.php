<?php

defined("_VALID_ACCESS") || die('Direct access forbidden');

class Premium_CriteriaInstall extends ModuleInstall
{

    const version = "1.0";

    /**
     * Module installation function.
     * @return true if installation success, false otherwise
     */
    public function install()
    {
        $rules = new Premium_Criteria_RBO_Rules();
        if ($rules->install()) {
            Base_ThemeCommon::install_default_theme(Premium_CriteriaInstall::module_name());
            Base_AclCommon::add_permission(_M('Criteria - Browse'), array('ACCESS:employee'));
            Base_AclCommon::add_permission(_M('Criteria - Manage'), array('ACCESS:employee'));

            $rules->add_access('view', 'ACCESS:employee', array(':Created_by' => 'USER_ID'));
            $rules->add_access('view', 'ACCESS:employee', array('!permission' => 2));
            $rules->add_access('add', 'ACCESS:employee');
            $rules->add_access('edit', 'ACCESS:employee', array('|:Created_by' => 'USER_ID'));
            $rules->add_access('edit', 'ACCESS:employee', array('permission' => 0));
            $rules->add_access('delete', 'ACCESS:employee', array(':Created_by' => 'USER_ID'));
            $rules->add_access('delete', array('ACCESS:employee', 'ACCESS:manager'));
            $rules->set_caption(_M('Recordset Criteria'));
            $rules->set_icon(Base_ThemeCommon::get_template_filename(Premium_CriteriaInstall::module_name(), 'icon.png'));
            Utils_RecordBrowserCommon::register_processing_callback(
                $rules->table_name(),
                array('Premium_CriteriaCommon', 'submit_criteria')
            );
            return true;
        }
        return false;
    }

    /**
     * Module uninstallation function.
     * @return true if installation success, false otherwise
     */
    public function uninstall()
    {
        $rules = new Premium_Criteria_RBO_Rules();
        if ($rules->uninstall()) {
            Base_AclCommon::delete_permission('Criteria - Browse');
            Base_AclCommon::delete_permission('Criteria - Manage');
            Base_ThemeCommon::uninstall_default_theme(Premium_CriteriaInstall::module_name());
            return true;
        }
        return false;
    }

    public function version()
    {
        return array(self::version);
    }

    public static function info()
    {
        return array(
            'Description' => 'Record browser criteria module for creating saved filters on any recordset. Utilizes query builder.',
            'Author' => 'dean.villar@gmail.com',
            'License' => 'MIT');
    }

    public static function simple_setup()
    {
        return array('package' => __('Recordset Criteria'), 'icon' => true, 'version' => self::version);
    }

    /**
     * Returns array that contains information about modules required by this module.
     * The array should be determined by the version number that is given as parameter.
     *
     * @param int $v module version number
     * @return array Array constructed as following: array(array('name'=>$ModuleName,'version'=>$ModuleVersion),...)
     */
    public function requires($v)
    {
        return array(
            array('name' => 'Base', 'version' => 0),
            array('name' => 'Utils/RecordBrowser', 'version' => 0),
            array('name' => 'Utils/QueryBuilder', 'version' => 0)
        );
    }
}
