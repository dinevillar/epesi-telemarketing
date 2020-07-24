<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/6/20
 * @Time: 1:31 PM
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Premium_Telemarketing_CallScripts extends Module
{
    public function body()
    {
        load_js("modules/" . Premium_Telemarketing_CallScriptsCommon::module_name() . "/js/callscripts.js");
        $callscript_templates = new Premium_Telemarketing_CallScripts_RBO_Templates();
        $callscript_templates->refresh_magic_callbacks();
        $rb = $callscript_templates->create_rb_module($this);
        $this->display_module($rb);
    }
}
