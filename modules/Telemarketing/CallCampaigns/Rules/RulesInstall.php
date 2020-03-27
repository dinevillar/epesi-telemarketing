<?php


class Telemarketing_CallCampaigns_RulesInstall extends ModuleInstall
{
    const RULES_TABLE = 'telemarketing_callcampaigns_rules';

    /**
     * Module installation function.
     * @return true if installation success, false otherwise
     */
    public function install()
    {
        Base_ThemeCommon::install_default_theme(self::module_name());
        $campaign_tab = Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME;

        Utils_RecordBrowserCommon::new_addon(
            $campaign_tab,
            self::module_name(),
            'rules_addon',
            array(
                'Telemarketing_CallCampaigns_RulesCommon',
                'rules_addon_label'
            )
        );

        Utils_CommonDataCommon::new_array(
            'CallCampaign/Rules',
            array(
                'Campaign' => _M('Campaign'),
                'Record' => _M('Record')
            )
        );

        Utils_CommonDataCommon::new_array(
            'CallCampaign/Rules/Campaign',
            array(
                'Done' => _M('Done'),
                'Reached' => _M('Reached')
            )
        );

        Utils_CommonDataCommon::new_array(
            'CallCampaign/Rules/Campaign/Done',
            array(
                'Send' => _M('Send'),
                'SetCampaignField' => _M('Set Campaign Field')
            )
        );

        Utils_CommonDataCommon::new_array(
            'CallCampaign/Rules/Campaign/Done/Send',
            array(
                'E-mail' => _M('E-mail')
            )
        );

        Utils_CommonDataCommon::new_array(
            'CallCampaign/Rules/Campaign/Reached',
            array(
                'Send' => _M('Send'),
                'SetCampaignField' => _M('Set Campaign Status')
            )
        );

        Utils_CommonDataCommon::new_array(
            'CallCampaign/Rules/Campaign/Reached/Send',
            array(
                'E-mail' => _M('E-mail')
            )
        );

        Utils_CommonDataCommon::new_array(
            'CallCampaign/Rules/Record',
            array(
                'Called' => _M('Called'),
                'Flagged' => _M('Flagged')
            )
        );

        Utils_CommonDataCommon::new_array(
            'CallCampaign/Rules/Record/Called',
            array(
                'AutoAdd' => _M('Auto-add'),
                'Send' => _M('Send'),
                'SetRecordField' => _M('Set Record Status')
            )
        );

        Utils_CommonDataCommon::new_array(
            'CallCampaign/Rules/Record/Called/AutoAdd',
            array(
                'Phonecall' => _M('Phonecall'),
                'Task' => _M('Task'),
                'Meeting' => _M('Meeting'),
                'RecordNote' => _M('Record Note'),
                'ToList' => _M('To List')
            )
        );

        Utils_CommonDataCommon::new_array(
            'CallCampaign/Rules/Record/Called/Send',
            array(
                'E-mail' => _M('E-mail')
            )
        );

        Utils_CommonDataCommon::new_array(
            'CallCampaign/Rules/Record/Flagged',
            array(
                'Add' => _M('Add'),
                'AutoAdd' => _M('Auto-add'),
                'Send' => _M('Send'),
                'Remove' => _M('Remove'),
                'SetRecordField' => _M('Set Record Field'),
                'Enqueue' => _M('Put on End of Queue'),
                'Callback' => _M('Set Callback Time')
            )
        );

        Utils_CommonDataCommon::new_array(
            'CallCampaign/Rules/Record/Flagged/Add',
            array(
                'Phonecall' => _M('Phonecall'),
                'Task' => _M('Task'),
                'Meeting' => _M('Meeting'),
                'Contact' => _M('Contact')
            )
        );

        Utils_CommonDataCommon::new_array(
            'CallCampaign/Rules/Record/Flagged/AutoAdd',
            array(
                'Phonecall' => _M('Phonecall'),
                'Task' => _M('Task'),
                'Meeting' => _M('Meeting'),
                'RecordNote' => _M('Record Note'),
//                'ToList' => _M('To List') List Manager
            )
        );

        Utils_CommonDataCommon::new_array(
            'CallCampaign/Rules/Record/Flagged/Send',
            array(
                'E-mail' => _M('E-mail')
            )
        );

        Utils_CommonDataCommon::new_array(
            'CallCampaign/Rules/Record/Flagged/Remove',
            array(
                'RecordFromQueue' => _M('Record From Queue'),
                'RecordFromAllCallCampaigns' => _M('Record From All Call Campaigns (Blacklist)'),
                'RecordPhoneNumber' => _M('Called Record Phone Number')
            )
        );

        $this->default_campaign_rules();

        DB::CreateTable(self::RULES_TABLE,
            '`id` I AUTO KEY,' .
            '`call_campaign_id` I,' .
            '`ind` I,' .
            '`type` C(64),' .
            '`condition` C(64),' .
            '`action` C(64),' .
            '`details` XL',
            array('constraints' => ", UNIQUE KEY `rules_uniq_ind_call_campaign_id` (call_campaign_id, ind), FOREIGN KEY (call_campaign_id) REFERENCES {$campaign_tab}_data_1(id) ON UPDATE CASCADE ON DELETE CASCADE"));

        Utils_RecordBrowserCommon::register_processing_callback($campaign_tab, array(
            'Telemarketing_CallCampaigns_RulesCommon', 'submit_call_campaign'
        ));

        return true;
    }

    /**
     * Module uninstallation function.
     * @return true if installation success, false otherwise
     */
    public function uninstall()
    {
        Base_ThemeCommon::uninstall_default_theme(self::module_name());
        $campaign_tab = Telemarketing_CallCampaigns_RBO_Campaigns::TABLE_NAME;
        Utils_RecordBrowserCommon::delete_addon(
            $campaign_tab,
            'Telemarketing/CallCampaigns/Rules',
            'rules_addon'
        );
        DB::DropTable(self::RULES_TABLE);
        $this->default_campaign_rules(false);

        Utils_CommonDataCommon::remove('CallCampaign/Rules');

        Utils_RecordBrowserCommon::unregister_processing_callback($campaign_tab, array(
            'Telemarketing_CallCampaigns_SettingsCommon', 'submit_call_campaign'
        ));
        return true;
    }

    private function default_campaign_rules($save = true)
    {
        if ($save) {
            $def_rules = array(
                array(
                    'type' => 'Record',
                    'condition' => 'Called',
                    'action' => 'AutoAdd:Phonecall',
                    'details' =>
                        array(
                            'add_phonecall_subject' => '[last_name] [first_name] has been called in call campaign: [call_campaign_name]',
                            'add_phonecall_permission' => '0',
                            'add_phonecall_status' => '0',
                            'add_phonecall_priority' => '1',
                            'add_phonecall_date_choice' => 'current_date',
                            'add_phonecall_description' => '[last_name] [first_name] has been called in call campaign: [call_campaign_name].',
                            'add_phonecall_employees' => '__SEP__current',
                        ),
                ),
                array(
                    'type' => 'Record',
                    'condition' => 'Flagged:I',
                    'action' => 'Remove:RecordFromQueue',
                    'details' =>
                        array(),
                ),
                array(
                    'type' => 'Record',
                    'condition' => 'Flagged:NI',
                    'action' => 'Remove:RecordFromQueue',
                    'details' =>
                        array(),
                ),
                array(
                    'type' => 'Record',
                    'condition' => 'Flagged:BNA',
                    'action' => 'Enqueue',
                    'details' =>
                        array(),
                ),
                array(
                    'type' => 'Record',
                    'condition' => 'Flagged:WDN',
                    'action' => 'Remove:RecordPhoneNumber',
                    'details' =>
                        array(),
                ),
                array(
                    'type' => 'Record',
                    'condition' => 'Flagged:WDN',
                    'action' => 'AutoAdd:RecordNote',
                    'details' =>
                        array(
                            'add_record_note_ck' => '<p>[call_campaign_called_phone_type]:&nbsp;[call_campaign_called_phone] is an unreachable number and has been removed from the record.</p>')
                ),
                array(
                    'type' => 'Record',
                    'condition' => 'Flagged:DNC',
                    'action' => 'Remove:RecordFromAllCallCampaigns',
                    'details' =>
                        array(),
                ),
                array(
                    'type' => 'Record',
                    'condition' => 'Flagged:CL',
                    'action' => 'AutoAdd:Phonecall',
                    'details' =>
                        array(
                            'add_phonecall_subject' => '[call_campaign_disposition] [last_name] [first_name]',
                            'add_phonecall_permission' => '0',
                            'add_phonecall_status' => '0',
                            'add_phonecall_priority' => '2',
                            'add_phonecall_date_choice' => 'current_date',
                            'add_phonecall_description' => ' Call [first_name] [last_name] regarding call campaign: [call_campaign_name]. ',
                            'add_phonecall_employees' => '__SEP__current',
                            'add_phonecall_email_employees' => '1',
                        ),
                ),
                array(
                    'type' => 'Record',
                    'condition' => 'Flagged:CL',
                    'action' => 'Callback',
                    'details' =>
                        array(),
                ),
                array(
                    'type' => 'Record',
                    'condition' => 'Flagged:RU',
                    'action' => 'Enqueue',
                    'details' => array()
                ),
                array(
                    'type' => 'Record',
                    'condition' => 'Flagged:RF',
                    'action' => 'Add:Contact',
                    'details' => array()
                ),
                array(
                    'type' => 'Campaign',
                    'condition' => 'Done',
                    'action' => 'SetCampaignField',
                    'details' =>
                        array(
                            'set_campaign_field_status' => '3',
                        )
                )
            );
            Variable::set('telemarketing_default_rules', $def_rules);
        } else {
            Variable::delete('telemarketing_default_rules', false);
        }
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
            array('name' => Telemarketing_CallCampaignsInstall::module_name(), 'version' => 0),
            array('name' => CRM_PhoneCallInstall::module_name(), 'version' => 0),
            array('name' => CRM_MeetingInstall::module_name(), 'version' => 0),
            array('name' => CRM_TasksInstall::module_name(), 'version' => 0)
        );
    }
}
