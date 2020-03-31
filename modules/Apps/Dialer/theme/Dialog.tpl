<div style="width:70%;margin: 20px auto;">
    <div class="layer">
        <div class="css3_content_shadow">
            <div class="Utils_RecordBrowser__container">
                {$form_open}
                <table id="call_form" class="call_visible Utils_RecordBrowser__View_entry"
                       style="background: whitesmoke;">
                    <tr style="height: 30px;">
                        <td class="label" style="width: 15%;">
                            {$form_data.current_location.label}
                        </td>
                        <td class="data" style="width: 35%;padding-left:8px;">
                            {$form_data.current_location.html}
                        </td>
                    </tr>
                    <tr style="height: 30px;">
                        <td class="label" style="width: 15%;">
                            {$form_data.current_local_time.label}
                        </td>
                        <td class="data" style="width: 35%;padding-left:8px;">
                            {$form_data.current_local_time.html}
                        </td>
                    </tr>
                    <tr style="height: 30px;">
                        <td class="label" style="width: 15%;">
                            {$form_data.dialer.label}
                        </td>
                        <td class="data" style="width: 35%;">
                            {$form_data.dialer.html}
                        </td>
                    </tr>
                    <tr>
                        <td class="label"
                            style="width:90px;">
                            {$form_data.phone.label}*
                        </td>
                        <td class="data">
                            {$form_data.phone.html}
                        </td>
                    </tr>
                </table>
                <table id="disposition_form" class="disposition_visible Utils_RecordBrowser__View_entry"
                       style="background: whitesmoke;">
                    <tr>
                        <td class="label">
                            {$form_data.disposition.label}*
                        </td>
                        <td class="data">
                            {if $error.disposition}
                                <span class="form_error"
                                      style="right:25px;">
                                    {$error.disposition}
                                    {$static_texts.error_closing}
                                    <br>
                                </span>
                            {/if}
                            {$form_data.disposition.html}
                        </td>
                    </tr>
                    <tr id="cl_extra_row">
                        <td class="label">
                            {$form_data.cl_timestamp.label}
                        </td>
                        <td class="data" id="cl_timestamp">
                            {if $error.cl_timestamp}
                                <span class="form_error">{$error.cl_timestamp}{$static_texts.error_closing}
                                                                                <br></span>
                            {/if}
                            {$form_data.cl_timestamp.html}
                        </td>
                    </tr>
                    <tr>
                        <td class="label">
                            {$form_data.log_text.label}
                        </td>
                        <td class="data">
                            {$form_data.log_text.html}
                        </td>
                    </tr>
                </table>
                {$form_close}
            </div>
        </div>
    </div>
    <a href="javascript:void(0);" id="call_button"
       onclick="Dialer.call_record();" style="height:30px; margin: 0 40%;line-height:30px;"
       class="call_visible button">Call Now</a>
    <a href="javascript:void(0);" id="save_button"
       onclick="Dialer.save();" style="height:30px; margin: 0 40%;line-height:30px;"
       class="disposition_visible button">Save</a>
</div>
