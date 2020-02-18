<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/12/20
 * @Time: 9:39 PM
 */

class Telemarketing_CallCampaigns_RBO_LeadsList extends RBO_FieldDefinition
{
    const type = 'callcampaign_leads_list';

    private $multi = false;
    private $crits_callback = null;

    public function __construct($display_name)
    {
        parent::__construct($display_name, self::type);
        $this->disable_magic_callbacks();
        $this->param = array();
    }

    public function set_multiple($bool = true)
    {
        $this->multi = $bool;
        return $this;
    }

    public function set_crits_callback($callback)
    {
        $this->crits_callback = $callback;
        return $this;
    }

    public function get_definition()
    {
        $this->param['field_type'] = $this->multi ? 'multiselect' : 'select';
        if ($this->crits_callback)
            $this->param['crits'] = $this->crits_callback;
        return parent::get_definition();
    }
}
