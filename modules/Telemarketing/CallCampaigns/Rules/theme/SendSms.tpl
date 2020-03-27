<table class="Utils_RecordBrowser__View_entry" cellpadding="0" cellspacing="0" border="0">
    <tbody>
    <tr>
        <td class="column">
            <table cellpadding="0" cellspacing="2" border="0" style="width:100%;"
                   class="edit">
                <tr>
                    <td class="label" style="width:100px">
                        {$form_data.send_sms_recipients.label}*
                    </td>
                    <td class="data">
                        <div style="position:relative;">
                            {if $form_data.send_sms_recipients.error}{$form_data.send_sms_recipients.error}{/if}
                            {$form_data.send_sms_recipients.html}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="label" style="width:100px">
                        {$form_data.send_sms_message.label}*
                    </td>
                    <td class="data">
                        {$form_data.send_sms_message.html}
                        <p style="text-align:right;font-size:7pt;">
                            <span><strong>Note: </strong>No. of messages may vary depending on merge text value length.</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    </tbody>
</table>