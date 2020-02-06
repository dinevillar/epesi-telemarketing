<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/3/20
 * @Time: 1:28 PM
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Utils_PaginatorInstall extends ModuleInstall
{

    public function install()
    {
        Base_ThemeCommon::install_default_theme($this->get_type());
        return true;
    }

    public function uninstall()
    {
        Base_ThemeCommon::uninstall_default_theme($this->get_type());
        return true;
    }

    public function requires($v)
    {
        return array(
            array('name' => 'Base/Lang', 'version' => 0),
        );
    }

    public function version()
    {
        return array('0.1');
    }

    public function info()
    {
        return array(
            'Author' => '<a href="mailto:dean.villar@gmail.com">Rodine Mark Paul Villar</a>',
            'License' => 'MIT',
            'Description' => 'Pagination of content'
        );
    }

}
