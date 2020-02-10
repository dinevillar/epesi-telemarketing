<?php

defined("_VALID_ACCESS") || die();

class Telemarketing_CallScriptsInstall extends ModuleInstall
{

    /**
     * Module installation function.
     * @return true if installation success, false otherwise
     */
    public function install()
    {
        try {
            Base_ThemeCommon::install_default_theme(self::module_name());
            $templates_table = Telemarketing_CallScripts_RBO_Templates::TABLE_NAME;
            $templates_recordset = new Telemarketing_CallScripts_RBO_Templates();
            ModuleManager::include_common(self::module_name(), 0);
            if ($templates_recordset->install()) {
                DB::CreateTable(Telemarketing_CallScripts_RBO_Templates::PAGES_TABLE_NAME,
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
                    array('Telemarketing_CallScriptsCommon', 'submit_callscript_templates')
                );
                Utils_RecordBrowserCommon::enable_watchdog(
                    $templates_table, array('Telemarketing_CallScriptsCommon', 'watchdog_label')
                );
                self::install_permissions();
                Utils_AttachmentCommon::new_addon(
                    Telemarketing_CallScripts_RBO_Templates::TABLE_NAME
                );
            }
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    public static function install_permissions()
    {
        $templates_table = Telemarketing_CallScripts_RBO_Templates::TABLE_NAME;
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
            Base_ThemeCommon::uninstall_default_theme(Telemarketing_CallScriptsInstall::module_name());
            DB::DropTable(Telemarketing_CallScripts_RBO_Templates::PAGES_TABLE_NAME);
            $templates_recordset = new Telemarketing_CallScripts_RBO_Templates();
            $templates_recordset->uninstall();
            Utils_RecordBrowserCommon::delete_addon($templates_recordset::TABLE_NAME, 'Telemarketing/CallScripts', 'attachment_addon');
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    public function version()
    {
        return array('1.0');
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
            array('name' => Utils_RecordBrowserInstall::module_name(), 'version' => 0),
            array('name' => Utils_BBCodeInstall::module_name(), 'version' => 0),
            array('name' => Utils_PaginatorInstall::module_name(), 'version' => 0),
            array('name' => Utils_MergeFieldsInstall::module_name(), 'version' => 0),
            array('name' => Utils_AttachmentInstall::module_name(), 'version' => 0),
            array('name' => CRM_ContactsInstall::module_name(), 'version' => 0),
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

    public function simple_setup()
    {
        return array('package' => __('Telemarketing'), 'icon' => true);
    }
}
