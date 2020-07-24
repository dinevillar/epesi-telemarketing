<table class="Utils_RecordBrowser__View_entry" cellpadding="0" cellspacing="0" border="0">
    <tbody>
    <tr>
        <td class="column">
            <table cellpadding="0" cellspacing="2" border="0" style="width:100%;"
                   class="edit">
                <tr>
                    <td class="label">
                        {$form_data.set_record_field_status.label}
                    </td>
                    <td class="data {$form_data.set_record_field_status.style}">
                        <div style="position:relative;">
                            {if $form_data.set_record_field_status.error}{$form_data.set_record_field_status.error}{/if}
                            {$form_data.set_record_field_status.html}
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    </tbody>
</table>