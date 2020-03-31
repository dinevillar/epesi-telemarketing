<div id="web-phone-bar1">
    <div class="web-phone-icon"></div>
    <div class="web-phone-text">
        {$short_name}
        <div class="web-phone-icon-down"></div>
        <div class="web-phone-icon-up" style="display: none;"></div>
        <div class="web-phone-icon-mic" style="display: none;"></div>
    </div>
</div>
<div id="web_phone_slide" class="applet" style="display: none;">
    <div class="layer" style="width:95%;height:95%">
        <div class="content_shadow_css3_dashboard dark-green_dashboard">
            <table class="container dark-green_dashboard" cellpadding="0" cellspacing="0"
                   border="0">
                <tbody>
                <tr class="nonselectable">
                    <td width="3px" class="header actions dark-gray_dashboard">
                    </td>
                    <td width="20px" class="header actions dark-gray">
                        {foreach item=action from=$left_actions}
                            {$action}
                        {/foreach}
                    </td>
                    <td class="header title handle dark-gray">
                        {$description}
                    </td>
                    <td class="header controls dark-gray">
                        {foreach item=control from=$right_controls}
                            {$control}
                        {/foreach}
                    </td>
                </tr>
                <tr>
                    <td class="content_td" colspan="4">
                        <div class="content">
                            <table border="0" style="width:100%;text-align: center;">
                                <tbody>
                                <tr>
                                    <td colspan="3" style="vertical-align: top;: top;">
                                        <input type="text" class="web_phone_screen"/>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="button" class="web_phone_button web_phone_button_yellow" value="C">
                                    </td>
                                    <td>
                                        <input type="button" class="web_phone_button" value="1">
                                    </td>
                                    <td>
                                        <input type="button" class="web_phone_button" value="2">
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="button" class="web_phone_button" value="3">
                                    </td>
                                    <td>
                                        <input type="button" class="web_phone_button" value="4">
                                    </td>
                                    <td>
                                        <input type="button" class="web_phone_button" value="5">
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="button" class="web_phone_button" value="6">
                                    </td>
                                    <td>
                                        <input type="button" class="web_phone_button" value="7">
                                    </td>
                                    <td>
                                        <input type="button" class="web_phone_button" value="8">
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="button" class="web_phone_button" value="9">
                                    </td>
                                    <td>
                                        <input type="button" class="web_phone_button" value="0">
                                    </td>
                                    <td>
                                        <input type="button" class="web_phone_button" value="+">
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="button" class="web_phone_button" value="#">
                                    </td>
                                    <td colspan="2">
                                        <input id="web_phone_call_button" type="button"
                                               class="web_phone_button web_phone_button_blue"
                                               style="width:150px;" value="CALL">
                                        <input id="web_phone_end_button" type="button"
                                               class="web_phone_button web_phone_button_red"
                                               style="width:150px;display:none;" value="END">
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
                <tr style="height:30px;">
                    <td colspan="4" style="padding: 5px">
                        <div class="web_phone_status_c" style="display:none;">
                            <span id="web_phone_status" style="font-weight: bold;"></span>
                            <span id="web_phone_status_spec"></span>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>