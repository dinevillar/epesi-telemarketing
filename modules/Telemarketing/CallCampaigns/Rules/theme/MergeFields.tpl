<div style="width:60%;margin:auto;max-height:250px;overflow: auto;padding:5px;margin-top:20px;box-shadow: inset 1px 1px 1px #777;border-radius: 0 4px 4px 0;">
    <h3>{$static_texts.call_campaign} {$static_texts.merge_fields}</h3>
    <table class="Utils_RecordBrowser__View_entry" cellpadding="0" cellspacing="0" border="0">
        <tbody>
        {foreach from=$call_campaign_merge_fields key=k item=merge_field}
            <tr>
                <td class="label">
                    {$merge_field}
                </td>
                <td class="data">
                    &nbsp;{$static_texts.merge_open}{$k}{$static_texts.merge_close}
                    <a href="javascript:void(0);" style="float:right;"
                       onclick="CallCampaignRules.copyToClipboard('{$static_texts.merge_open}{$k}{$static_texts.merge_close}');leightbox_deactivate('{$lb_id}');">
                        <i class="fa fa-copy"></i> {$static_texts.copy}
                    </a>
                </td>
            </tr>
        {/foreach}
        </tbody>
    </table>
    <hr/>
    <h3>{$static_texts.product} {$static_texts.merge_fields}</h3>
    <table class="Utils_RecordBrowser__View_entry" cellpadding="0" cellspacing="0" border="0">
        <tbody>
        {foreach from=$product_merge_fields key=k item=merge_field}
            <tr>
                <td class="label">
                    {$merge_field}
                </td>
                <td class="data">
                    &nbsp;{$static_texts.merge_open}{$k}{$static_texts.merge_close}
                    <a href="javascript:void(0);" style="float:right;"
                       onclick="CallCampaignRules.copyToClipboard('{$static_texts.merge_open}{$k}{$static_texts.merge_close}');leightbox_deactivate('{$lb_id}');">
                        <i class="fa fa-copy"></i> {$static_texts.copy}
                    </a>
                </td>
            </tr>
        {/foreach}
        </tbody>
    </table>
    <hr/>
    <h3>{$static_texts.target_contact} {$static_texts.merge_fields}</h3>
    <table class="Utils_RecordBrowser__View_entry" cellpadding="0" cellspacing="0" border="0">
        <tbody>
        {foreach from=$contact_merge_fields key=k item=merge_field}
            <tr>
                <td class="label">
                    {$merge_field}
                </td>
                <td class="data">
                    &nbsp;{$static_texts.merge_open}{$k}{$static_texts.merge_close}
                    <a href="javascript:void(0);" style="float:right;"
                       onclick="CallCampaignRules.copyToClipboard('{$static_texts.merge_open}{$k}{$static_texts.merge_close}');leightbox_deactivate('{$lb_id}');">
                        <i class="fa fa-copy"></i> {$static_texts.copy}
                    </a>
                </td>
            </tr>
        {/foreach}
        </tbody>
    </table>
    <hr/>
    <h3>{$static_texts.employee} {$static_texts.merge_fields}</h3>
    <table class="Utils_RecordBrowser__View_entry" cellpadding="0" cellspacing="0" border="0">
        <tbody>
        {foreach from=$emp_merge_fields key=k item=merge_field}
            <tr>
                <td class="label">
                    {$merge_field}
                </td>
                <td class="data">
                    &nbsp;{$static_texts.merge_open}{$k}{$static_texts.merge_close}
                    <a href="javascript:void(0);" style="float:right;"
                       onclick="CallCampaignRules.copyToClipboard('{$static_texts.merge_open}{$k}{$static_texts.merge_close}');leightbox_deactivate('{$lb_id}');">
                        <i class="fa fa-copy"></i> {$static_texts.copy}
                    </a>
                </td>
            </tr>
        {/foreach}
        </tbody>
    </table>
</div>