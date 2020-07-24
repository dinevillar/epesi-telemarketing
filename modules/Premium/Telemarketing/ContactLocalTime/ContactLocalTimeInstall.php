<?php

/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 3/22/20
 * @Time: 4:29 AM
 */
class Premium_Telemarketing_ContactLocalTimeInstall extends ModuleInstall
{

    /**
     * Module installation function.
     * @return true if installation success, false otherwise
     */
    public function install()
    {
        try {
            Utils_RecordBrowserCommon::delete_record_field('contact', 'Local Time');
            $lt = new RBO_Field_Text(_M("Local Time"), 64);
            $lt->set_display_callback(array("Premium_Telemarketing_ContactLocalTimeCommon", "display_local_time"));
            $lt->set_QFfield_callback(array("Premium_Telemarketing_ContactLocalTimeCommon", "qffield_local_time"));
            $lt->set_position('country');
            $lt->set_filter();

            $lt_c = new RBO_Field_Text(_M("Local Time"), 64);
            $lt_c->set_display_callback(array("Premium_Telemarketing_ContactLocalTimeCommon", "display_local_time_company"));
            $lt_c->set_QFfield_callback(array("Premium_Telemarketing_ContactLocalTimeCommon", "qffield_local_time"));
            $lt_c->set_position('country');
            $lt_c->set_filter();

            $lt_p = new RBO_Field_Calculated(_M("Local Time"), 64);
            $lt_p->set_display_callback(array("Premium_Telemarketing_ContactLocalTimeCommon", "display_local_time_phonecall"));
            $lt_p->set_QFfield_callback(array("Premium_Telemarketing_ContactLocalTimeCommon", "qffield_local_time"));

            Utils_RecordBrowserCommon::new_record_field('contact', $lt->get_definition());
            Utils_RecordBrowserCommon::new_record_field('company', $lt_c->get_definition());
            Utils_RecordBrowserCommon::new_record_field('phonecall', $lt_p->get_definition());

            Utils_RecordBrowserCommon::register_processing_callback('contact', array('Premium_Telemarketing_ContactLocalTimeCommon', 'submit_contact'));
            Utils_RecordBrowserCommon::register_processing_callback('company', array('Premium_Telemarketing_ContactLocalTimeCommon', 'submit_company'));
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Module uninstallation function.
     * @return true if installation success, false otherwise
     */
    public function uninstall()
    {
        try {
            Utils_RecordBrowserCommon::unregister_processing_callback('contact', array('Premium_Telemarketing_ContactLocalTimeCommon', 'submit_contact'));
            Utils_RecordBrowserCommon::unregister_processing_callback('company', array('Premium_Telemarketing_ContactLocalTimeCommon', 'submit_company'));
            Utils_RecordBrowserCommon::delete_record_field('contact', 'Local Time');
            Utils_RecordBrowserCommon::delete_record_field('company', 'Local Time');
            Utils_RecordBrowserCommon::delete_record_field('phonecall', 'Local Time');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function simple_setup()
    {
        return array(
            'package' => __('Telemarketing')
        );
    }

    public function info()
    {
        return array(
            'Author' => '<a href="mailto:dean.villar@gmail.com">Rodine Mark Paul Villar</a>',
            'Description' => "Contact Current Local Time Field. Install Google Maps Services for a more precise timezone querying."
        );
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
            array('name' => 'Utils/CommonData', 'version' => 0),
            array('name' => 'CRM/Contacts', 'version' => 0),
            array('name' => 'CRM/PhoneCall', 'version' => 0)
        );
    }

    public function version()
    {
        return array(Premium_TelemarketingInstall::version);
    }
}
