<table class="Utils_RecordBrowser__View_entry" cellpadding="0" cellspacing="0" border="0">
    <tbody>
    <tr>
        <td class="column">
            <table cellpadding="0" cellspacing="2" border="0" style="width:100%;"
                   class="edit">
                <tr>
                    <td class="label">
                        {$form_data.send_mail_to.label}*
                    </td>
                    <td class="data {$form_data.send_mail_to.style}">
                        <div style="position:relative;">
                            {if $form_data.send_mail_to.error}{$form_data.send_mail_to.error}{/if}
                            {$form_data.send_mail_to.html}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="label">
                        {$form_data.mailing_templates.label}
                    </td>
                    <td class="data">
                        {$form_data.mailing_templates.html}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    </tbody>
</table>