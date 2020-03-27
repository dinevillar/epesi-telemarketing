<li id="rule_item_{$index}" rel="{$index}" class="rule_item"
    style="border-radius: 5px;border: 2px solid #ccc; padding:5px;margin:10px; 0;">
    <div style="font-size:10pt;">
        {foreach from=$form_data item=field}
            {if $field.name && $field.html|replace:' ':'' && $field.label != '<'}
                <div style="display: inline-block; white-space: nowrap;" class="field_group">
                    {if $field.label|replace:' ':'' }
                        <span style="display:table-cell; width:10px;" class="epesi_label">
                        {$field.label}
                        </span>
                    {/if}
                    <span style="display:table-cell; width:auto;" class="epesi_data">
                        {$field.html}
                    </span>
                </div>
            {/if}
        {/foreach}
    </div>
    <div style="text-align: right;">
        <a href="javascript:void(0);" onclick="CallCampaignRules.delete_rule({$index});"
           style="text-decoration: underline;line-height: 20px;">{$static_texts.delete}</a>
        <span class="has_details" {if !$details}style="display:none;"{/if}>|</span>
        <a href="javascript:void(0);" onclick="CallCampaignRules.show_details({$index});" class="has_details"
                {if !$details}
                    style="text-decoration: underline;line-height: 20px;display:none;"
                {else}
                    style="text-decoration: underline;line-height: 20px;"
                {/if}
        >
            <span class="show_text">{$static_texts.show}</span>
            <span class="hide_text" style="display:none;">{$static_texts.hide}</span>
            {$static_texts.details}
        </a>
    </div>
    <div class="details_box" style="margin:5px;min-height:100px;margin:10px;display:none;">
        {if $details}
            {$details}
        {/if}
    </div>

</li>