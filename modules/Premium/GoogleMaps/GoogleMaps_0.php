<?php

defined("_VALID_ACCESS") || die('Direct access forbidden');

class Premium_GoogleMaps extends Module
{
    public function admin()
    {
        if ($this->is_back()) {
            if ($this->parent->get_type() == 'Base_Admin')
                $this->parent->reset();
            else
                location(array());

            return false;
        }
        if (isset($_REQUEST['back_location'])) {
            $back_location = $_REQUEST['back_location'];
            Base_ActionBarCommon::add('back', __('Back'), Base_BoxCommon::create_href(
                $this, $back_location['module'], $back_location['func'],
                isset($back_location['args']) ? $back_location['args'] : array()
            ));
        } else {
            Base_ActionBarCommon::add('back', __('Back'), $this->create_back_href());
        }

        /**
         * @var $qf Premium_QuickForm
         */
        $qf = $this->init_module("Libs_QuickForm", 'google_maps_form');
        $qf->addElement('text', 'google_maps_token', __("Google Maps API Token"));
        if ($qf->validate()) {
            $values = $qf->exportValues();
            if ($values['google_maps_token']) {
                Variable::set('google_maps_token', $values['google_maps_token']);
            }
            Base_StatusBarCommon::message("Saved");
        }
        $qf->setDefaults(array(
            'google_maps_token' => Variable::get('google_maps_token', "") ?: ""
        ));
        Base_ActionBarCommon::add("save", __("Save"), $qf->get_submit_form_href());
        $qf->display_as_column();
    }
}
