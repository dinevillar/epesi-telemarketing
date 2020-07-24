<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 3/29/2020
 * @Time: 4:23 AM
 */
defined("_VALID_ACCESS") || die();

class Premium_WebPhone extends Module
{

    public function body()
    {
        load_js('modules/Premium/WebPhone/js/webphone.js');
        $selected = Premium_WebPhoneCommon::get_webphone_active();
        $theme = $this->init_module("Base/Theme");
        if ($selected !== false) {
            $theme->assign('short_name', __('Web Phone') . ' - ' . $selected['short_name']);
            $theme->assign('description', $selected['description']);
            $theme->assign('left_actions', $selected['left_actions']);
            $right_controls = $selected['right_controls'];
            $image_src = Base_ThemeCommon::get_template_file(Base_Dashboard::module_name(), 'close.png');
            $image_hover_src = Base_ThemeCommon::get_template_file(Base_Dashboard::module_name(), 'close-hover.png');
            $image = "<img src='$image_src' onmouseover='this.src=\"$image_hover_src\"' onmouseout='this.src=\"$image_src\"' width='14' height='14' border='0'/>";
            $href = " href='javascript:void(0);' onclick='jQuery(\"#web-phone-bar1\").trigger(\"click\")'";
            $right_controls[] = "<a$href>$image</a>";
            $theme->assign('right_controls', $right_controls);
            $user = Acl::get_user();
            eval_js("WebPhone.init($user);");
            $impl = $this->init_module($selected['mod']);
            $init = $selected['init'];
            $impl->$init();
        } else {
            eval_js("WebPhone.destroy();");
        }
        $theme->display();
    }

}
