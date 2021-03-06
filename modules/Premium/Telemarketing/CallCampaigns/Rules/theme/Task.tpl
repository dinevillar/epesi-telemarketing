<table class="Utils_RecordBrowser__View_entry" cellpadding="0" cellspacing="0" border="0">
    <tbody>
    <tr>
        <td class="column">
            <table cellpadding="0" cellspacing="2" border="0" style="width:100%;"
                   class="edit">
                {foreach item=item from=$col1}
                    <tr>
                        <td class="label">
                            {$form_data.$item.label}{if $form_data.$item.required}*{/if}
                        </td>
                        <td class="data {$form_data.$item.style}">
                            <div style="position:relative;">
                                {if $form_data.$item.error}{$form_data.$item.error}{/if}
                                {$form_data.$item.html}
                            </div>
                        </td>
                    </tr>
                {/foreach}
            </table>
        </td>
        <td class="column">
            <table cellpadding="0" cellspacing="2" border="0" style="width:100%;"
                   class="edit">
                {foreach item=item from=$col2}
                    <tr>
                        <td class="label">
                            {$form_data.$item.label}
                        </td>
                        <td class="data {$form_data.$item.style}">
                            <div style="position:relative;">
                                {if $form_data.$item.error}{$form_data.$item.error}{/if}
                                {$form_data.$item.html}
                            </div>
                        </td>
                    </tr>
                {/foreach}
            </table>
        </td>
    </tr>
    <tr>
        <td class="column" colspan="2">
            <table cellpadding="0" cellspacing="2" border="0" style="width:100%;"
                   class="edit">
                <tr>
                    <td class="label">
                        {$form_data.add_task_deadline.label}
                    </td>
                    <td style="box-shadow: inset 1px 1px 1px #777;border-radius: 0 4px 4px 0;">
                        <table style="width:100%;">
                            <tr>
                                <td style="width:100px;" class="label">
                                    {$form_data.add_task_deadline.$index.current_date.html}
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td class="label">
                                    {$form_data.add_task_deadline.$index.specific_date.html}
                                </td>
                                <td class="data">
                                    {$form_data.add_task_specific_date_val.html}
                                </td>
                            </tr>
                            <tr>
                                <td class="label">
                                    {$form_data.add_task_deadline.$index.dynamic_date.html}
                                </td>
                                <td class="data">
                                    <table>
                                        <tbody>
                                        <tr>
                                            <td>{$form_data.add_task_dynamic_date_num.html}</td>
                                            <td>{$form_data.add_task_dynamic_date_denom.html}</td>
                                            <td><span>{$static_texts.after}</span></td>
                                            <td><span>{$static_texts.current_date}</span></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>
                {foreach item=item from=$longfields}
                    <tr>
                        <td class="label">
                            {$form_data.$item.label}{if $form_data.$item.required}*{/if}
                        </td>
                        <td class="data {$form_data.$item.style}">
                            <div style="position:relative;">
                                {if $form_data.$item.error}{$form_data.$item.error}{/if}
                                {$form_data.$item.html}
                            </div>
                        </td>
                    </tr>
                {/foreach}
            </table>
        </td>
    </tr>
    </tbody>
</table>