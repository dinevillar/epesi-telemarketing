<?php

/**
 * User: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * Date: 4/4/2016
 * Time: 11:09 PM
 */
defined("_VALID_ACCESS") || die();

require_once('modules/Libs/Twilio/twilio-lib/Twilio/autoload.php');

class Libs_Twilio_RBO_PhoneMappings extends RBO_Recordset
{
    const TABLE_NAME = 'twilio_phone_mappings';

    private $twilioRestClient;

    /**
     * @return mixed
     */
    public function getTwilioRestClient()
    {
        return $this->twilioRestClient;
    }

    /**
     * @param mixed $twilioRestClient
     */
    public function setTwilioRestClient($twilioRestClient)
    {
        $this->twilioRestClient = $twilioRestClient;
    }

    public function table_name()
    {
        return self::TABLE_NAME;
    }

    public function fields()
    {
        $employee = new CRM_Contacts_RBO_Employee(_M("Employee"));
        $employee->set_filter();
        $employee->set_visible();
        $employee->set_required();

        $phone_number = new RBO_Field_Text(_M("Phone Number"), 64);
        $phone_number->set_visible();
        $phone_number->set_required();

        $allowed_outgoing = new RBO_Field_Checkbox(_M("Allowed Outgoing"));
        $allowed_outgoing->set_visible();
        $application_sid = new RBO_Field_Text(_M("Application SID"), 34);

        $allowed_incoming = new RBO_Field_Checkbox(_M("Allowed Incoming"));
        $allowed_incoming->set_visible();
        $client_name = new RBO_Field_Text(_M("Client Name"), 64);
        $client_name->set_visible();

        $advanced = new RBO_Field_PageSplit(_M("Custom Connection"));
        $account_sid = new RBO_Field_Text(_M("Account SID"), 34);
        $account_sid->set_extra();
        $auth_token = new RBO_Field_Text(_M("Auth Token"), 64);
        $auth_token->set_extra();

        return array($employee, $phone_number, $allowed_outgoing, $application_sid, $allowed_incoming, $client_name, $advanced, $account_sid, $auth_token);
    }

    public function get_capability_token($user = false)
    {
        $pm_rec = $this->get_pm_rec($user);
        if ($pm_rec) {
            if ($pm_rec['account_sid']) {
                $account_sid = $pm_rec['account_sid'];
            } else {
                $account_sid = Variable::get('default_twilio_account_sid', false);
            }
            if ($pm_rec['auth_token']) {
                $auth_token = $pm_rec['auth_token'];
            } else {
                $auth_token = Variable::get('default_twilio_auth_token', false);
            }
            if ($account_sid && $auth_token) {
                /* @var $capability Services_Twilio_Capability */
                $capability = new \Twilio\Jwt\ClientToken($account_sid, $auth_token);
                $app_sid = $pm_rec['application_sid'];
                if ($pm_rec['allowed_outgoing'] && $app_sid) {
                    $capability->allowClientOutgoing($app_sid);
                }
                if ($pm_rec['allowed_incoming']) {
                    if ($pm_rec['client_name']) {
                        $capability->allowClientIncoming($pm_rec['client_name']);
                    } else {
                        $me = Base_UserCommon::get_my_user_login();
                        $me = preg_replace('/[^a-zA-Z0-9]+/', '_', $me);
                        $capability->allowClientIncoming($me);
                    }
                }
                return $capability->generateToken();
            }
        }
        return false;
    }

    public function get_pm_rec($user = false)
    {
        if (!$user) {
            $user = Acl::get_user();
        }
        $contact = CRM_ContactsCommon::get_contact_by_user_id($user);
        $pm_rec = $this->get_records(array('employee' => $contact['id']));
        if (!empty($pm_rec)) {
            return array_pop($pm_rec);
        } else {
            return false;
        }
    }

    public function get_twilio_service($user = false)
    {
        $pm_rec = $this->get_pm_rec($user);
        if ($pm_rec) {
            if ($pm_rec['account_sid']) {
                $account_sid = $pm_rec['account_sid'];
            } else {
                $account_sid = Variable::get('default_twilio_account_sid', false);
            }
            if ($pm_rec['auth_token']) {
                $auth_token = $pm_rec['auth_token'];
            } else {
                $auth_token = Variable::get('default_twilio_auth_token', false);
            }
            if ($account_sid && $auth_token) {
                return $this->get_bare_twilio_service($account_sid, $auth_token);
            }
        }
        return false;
    }

    public function get_bare_twilio_service($account_sid, $auth_token)
    {
        $client = $this->getTwilioRestClient();
        if (!$client) {
            $client = new \Twilio\Rest\Client($account_sid, $auth_token);
            $this->setTwilioRestClient($client);
        }
        return $client;
    }

    public
    function display_phone_number($record, $nolink = false)
    {
        return $record['phone_number'];
    }

    public
    function QFfield_phone_number($form, $field, $label, $mode, $default, $args, $rb_obj)
    {
        if ($mode == 'add' || $mode == 'edit') {
            if ($mode == 'add') {
                $account_sid = Variable::get("default_twilio_account_sid", false);
                $auth_token = Variable::get("default_twilio_auth_token", false);
            } else {
                $record = $rb_obj->record;
                $account_sid = $record['account_sid'];
                $auth_token = $record['auth_token'];
            }
            if ($account_sid && $auth_token) {
                $client = self::get_bare_twilio_service($account_sid, $auth_token);
                try {
                    $ops = array();
                    foreach ($client->incomingPhoneNumbers->read() as $number) {
                        $ops[$number->phoneNumber] = $number->friendlyName . "  [" . $number->phoneNumber . "]";
                    }
                    $form->addElement('select', $field, $label, $ops);
                } catch (Exception $e) {
                    $form->addElement('static', $field, $label, __("Error") . ": " . $e->getCode() . " " . $e->getMessage());
                }
            }
        } else {

            $form->addElement('text', $field, $label);
            $form->setDefaults(array(
                $field => $default
            ));
        }
    }

    public function display_auth_token($record, $nolink = false)
    {
        return __("Encrypted") . ": " . $record['auth_token'];
    }

    public function QFfield_auth_token($form, $field, $label, $mode, $default, $args, $rb_obj)
    {
        if ($mode == 'edit' || $mode == 'add') {
            $form->addElement('password', $field, $label);
            if ($mode == 'edit') {
                $form->setDefaults(array(
                    $field => $default
                ));
            }
        } else {
            $form->addElement('text', $field, $label);
            $form->setDefaults(array(
                $field => __("Encrypted") . ": " . $default
            ));
        }
    }
}
