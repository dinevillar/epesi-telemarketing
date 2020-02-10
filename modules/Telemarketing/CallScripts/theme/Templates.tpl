<table class="Utils_RecordBrowser__table" border="0" cellpadding="0" cellspacing="0">
    <tbody>
    <tr>
        <td style="width:100px;">
            <div class="name">
                <img alt="&nbsp;" class="icon" src="{$icon}" width="32" height="32" border="0">

                <div class="label">{$caption}</div>
            </div>
        </td>
        <td>
            <div>
                {foreach item=n from=$new}
                    <span class="epesi_big_button" onclick="jq(this).find('a').click();"
                          style="margin:1px;">{$n}</span>
                {/foreach}
            </div>
        </td>
        <td class="required_fav_info">
            {if $required_note}
                &nbsp;*&nbsp;{$required_note}
            {/if}
            {if isset($subscription_tooltip)}
                &nbsp;&nbsp;&nbsp;{$subscription_tooltip}
            {/if}
            {if isset($fav_tooltip)}
                &nbsp;&nbsp;&nbsp;{$fav_tooltip}
            {/if}
            {if isset($info_tooltip)}
                &nbsp;&nbsp;&nbsp;{$info_tooltip}
            {/if}
            {if isset($clipboard_tooltip)}
                &nbsp;&nbsp;&nbsp;{$clipboard_tooltip}
            {/if}
            {if isset($history_tooltip)}
                &nbsp;&nbsp;&nbsp;{$history_tooltip}
            {/if}
        </td>
    </tr>
    </tbody>
</table>

{if isset($click2fill)}
    {$click2fill}
{/if}

<div class="layer" style="padding: 9px; width: 98%;">
    <div class="css3_content_shadow">
        <div class="Utils_RecordBrowser__container">

            {* Outside table *}
            <table class="Utils_RecordBrowser__View_entry" cellpadding="0" cellspacing="0" border="0">
                <tbody>
                <tr>
                    <td class="column" style="width:300px;">
                        <table cellpadding="0" cellspacing="2" border="0"
                               class="{if $action == 'view'}view{else}edit{/if}">
                            <tr>
                                <td class="label" style="width:100px">
                                    {$fields.name.label}*
                                </td>
                                <td class="data {$fields.name.style}">
                                    <div style="position:relative;">
                                        {if $fields.name.error}{$fields.name.error}{/if}
                                        {$fields.name.html}
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="label">
                                    {$fields.permission.label}*
                                </td>
                                <td class="data {$fields.permission.style}">
                                    <div style="position:relative;">
                                        {if $fields.permission.error}{$fields.permission.error}{/if}
                                        {$fields.permission.html}
                                    </div>
                                </td>
                            </tr>
                            {if $action == 'view'}
                                <tr>
                                    <td class="label">
                                        {$fields.created_by.label}
                                    </td>
                                    <td class="data {$fields.created_by.style}">
                                        <div style="position:relative;">
                                            {$fields.created_by.html}
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label">
                                        {$fields.created_on.label}
                                    </td>
                                    <td class="data {$fields.created_on.style}">
                                        <div style="position:relative;">
                                            {$fields.created_on.html}
                                        </div>
                                    </td>
                                </tr>
                            {/if}
                            {if $action != 'view'}
                                <tr>
                                    <td colspan="2" class="placeholders">
                                        {$form_data.placeholders.html}
                                    </td>
                                </tr>
                            {/if}
                        </table>
                    </td>
                    <td class="column">
                        <table cellpadding="0" cellspacing="2" border="0" style="width:100%;"
                               class="{if $action == 'view'}view{else}edit{/if}">
                            <tr>
                                <td {if $action == 'view'}colspan="2"{/if}>
                                    {$form_data.pagination.html}
                                </td>
                                {if $action != 'view'}
                                    <td style="text-align: right;width:50%;">
                                        <a id="delete_callscript_page_button" class="button" href="javascript:void(0)"
                                           onclick="CallScripts.deletePage();">Delete
                                            Page</a>
                                        <a id="add_callscript_page_button" class="button" href="javascript:void(0)"
                                           onclick="CallScripts.addPage();">Add
                                            Page</a>
                                        <a class="button" href="javascript:void(0)"
                                           onclick="CallScripts.ckExec('page_link_dialog')">Link
                                            To
                                            Page</a>
                                        <a class="button" href="javascript:void(0)"
                                           onclick="CallScripts.ckExec('collapse_dialog')">Insert
                                            Collapsible Text</a>
                                    </td>
                                {/if}
                            </tr>
                            <tr>
                                <td colspan="2" class="data">
                                    <div id="callscript_template_content">
                                        {$form_data.pages.html}
                                        {$longfields.content.html}
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                </tbody>
            </table>

            {if $main_page}
                {php}
                    if (isset($this->_tpl_vars['focus'])) eval_js('focus_by_id(\''.$this->_tpl_vars['focus'].'\');');
                {/php}
            {/if}

        </div>
    </div>
</div>
