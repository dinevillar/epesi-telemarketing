<?php

defined("_VALID_ACCESS") || die();

class Premium_Telemarketing_CallScriptsInstall extends ModuleInstall
{

    /**
     * Module installation function.
     * @return true if installation success, false otherwise
     */
    public function install()
    {
        try {
            Base_ThemeCommon::install_default_theme(self::module_name());
            $templates_table = Premium_Telemarketing_CallScripts_RBO_Templates::TABLE_NAME;
            $templates_recordset = new Premium_Telemarketing_CallScripts_RBO_Templates();
            ModuleManager::include_common(self::module_name(), 0);
            if ($templates_recordset->install()) {
                DB::CreateTable(Premium_Telemarketing_CallScripts_RBO_Templates::PAGES_TABLE_NAME,
                    'id I8 AUTO KEY,' .
                    'template_id I,' .
                    'page I,' .
                    'content X',
                    array('constraints' => ", FOREIGN KEY (template_id) REFERENCES {$templates_table}_data_1(id), UNIQUE KEY `callscript_page_uniq` (`template_id`,`page`)")
                );
                $templates_recordset->set_favorites();
                $templates_recordset->set_quickjump('name');
                $templates_recordset->set_caption(_M("Call Script Templates"));
                $templates_recordset->set_recent();
                $templates_recordset->set_tpl(
                    Base_ThemeCommon::get_template_filename(self::module_name(), 'Templates')
                );
                $templates_recordset->set_icon(
                    Base_ThemeCommon::get_template_filename(self::module_name(), 'icon.png')
                );
                $templates_recordset->register_processing_callback(
                    array('Premium_Telemarketing_CallScriptsCommon', 'submit_callscript_templates')
                );
                Utils_RecordBrowserCommon::enable_watchdog(
                    $templates_table, array('Premium_Telemarketing_CallScriptsCommon', 'watchdog_label')
                );
                self::install_permissions();
                Utils_AttachmentCommon::new_addon(
                    Premium_Telemarketing_CallScripts_RBO_Templates::TABLE_NAME
                );
            }
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    public static function install_permissions()
    {
        $templates_table = Premium_Telemarketing_CallScripts_RBO_Templates::TABLE_NAME;
        Utils_RecordBrowserCommon::add_access($templates_table, 'print', 'SUPERADMIN');
        Utils_RecordBrowserCommon::add_access($templates_table, 'export', 'SUPERADMIN');
        Utils_RecordBrowserCommon::add_access(
            $templates_table, 'view', 'ACCESS:employee',
            array('(!permission' => 2, '|:Created_by' => 'USER_ID')
        );
        Utils_RecordBrowserCommon::add_access($templates_table, 'add', 'ACCESS:employee');
        Utils_RecordBrowserCommon::add_access(
            $templates_table, 'edit', 'ACCESS:employee',
            array('(permission' => 0, '|:Created_by' => 'USER_ID')
        );
        Utils_RecordBrowserCommon::add_access($templates_table, 'delete', 'ACCESS:employee',
            array(':Created_by' => 'USER_ID')
        );
        Utils_RecordBrowserCommon::add_access($templates_table, 'delete',
            array('ACCESS:employee', 'ACCESS:manager')
        );
    }

    /**
     * Module uninstallation function.
     * @return true if installation success, false otherwise
     */
    public function uninstall()
    {
        try {
            Base_ThemeCommon::uninstall_default_theme(Premium_Telemarketing_CallScriptsInstall::module_name());
            DB::DropTable(Premium_Telemarketing_CallScripts_RBO_Templates::PAGES_TABLE_NAME);
            $templates_recordset = new Premium_Telemarketing_CallScripts_RBO_Templates();
            $templates_recordset->uninstall();
            Utils_RecordBrowserCommon::delete_addon($templates_recordset::TABLE_NAME, 'Telemarketing/CallScripts', 'attachment_addon');
        } catch (Exception $e) {
            return false;
        }
        return true;
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
            array('name' => 'Utils/BBCode', 'version' => 0),
            array('name' => 'Premium/Telemarketing/MergeFields', 'version' => 0),
            array('name' => 'Premium/Telemarketing/Paginator', 'version' => 0)
        );
    }

    public function info()
    {
        return array(
            'Author' => '<a href="mailto:dean.villar@gmail.com">Rodine Mark Paul L. Villar</a>',
            'License' => 'MIT',
            'Description' => 'Telemarketing Call Script Templates'
        );
    }

    public function version()
    {
        return array(Premium_TelemarketingInstall::version);
    }

    public function simple_setup()
    {
        return array('package' => __('Telemarketing'),);
    }
}
