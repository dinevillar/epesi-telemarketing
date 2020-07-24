<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/11/20
 * @Time: 12:59 PM
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Premium_Telemarketing_Products extends Module
{
    public function body()
    {
        $recordset = new Premium_Telemarketing_Products_RBO_Products();
        $recordset->set_caption(__("Products"));
        $recordset->refresh_magic_callbacks();
        $this->display_module($recordset->create_rb_module($this));
    }

}
