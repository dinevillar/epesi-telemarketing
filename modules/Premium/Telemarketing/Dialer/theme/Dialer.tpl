<div id="dialer_auto_call_countdown" class="Base_StatusBar" style="display:none;">
	<div class="layer">
		<div class="shadow_15px" id="auto_call_content">
			<div class="message" style="padding:25px 33px 25px 25px;">
                {$static_texts.auto_call} in
				<span id="auto_call_count" style="color:darkred;font-size:14pt;">
                    3
                </span> {$static_texts.seconds}...
			</div>
			<div id="dismiss" style="font-size:100%;">
				<a style="cursor:pointer;" href="javascript:void(0);"
				   onclick="Dialer.cancel_auto_call();">
                    {$static_texts.click_cancel}
				</a>
			</div>
		</div>
	</div>
</div>

<div id="dialer_parent" style="width: 99%;height:512px;">
	<a {$dialog_href} style="display: none;">Show Dialog</a>
	<div style="height:100%;">
		<div id="left_content">
			<table class="Utils_RecordBrowser__table" border="0" cellpadding="0" cellspacing="0">
				<tbody>
				<tr>
					<td style="width:100px;">
						<div class="name">
							<img alt="&nbsp;" class="icon"
							     src="{$callcampaign.icon}" width="32"
							     height="32" border="0">

							<div class="label">{$callcampaign.name}</div>
						</div>
					</td>
					<td>
						<div>
                            {foreach item=c from=$campaign_actions}
								<span class="epesi_big_button" onclick="jq(this).find('a').click();"
								      style="margin:1px;">{$c}</span>
                            {/foreach}
						</div>
					</td>
				</tr>
				</tbody>
			</table>
			<div id="left_top_content" class="layer">
				<div class="css3_content_shadow" style="height:100%;">
					<div id="callscript_container" class="Utils_RecordBrowser__container">
						<div id="callscript_content">
                            {$callscript.content}
						</div>
						<div id="callscript_bottom">
							<div style="float:left;">
                                {$pagination}
							</div>
                            {if $auto_scroll}
								<div style="float:right;margin:10px;">
                                    {if $auto_scroll_buttons}
										<div id="auto_scroll_slider">
										</div>
                                        {foreach item=a from=$auto_scroll_buttons}
											<span id="{$a.id}"
											      style="cursor:pointer;{$a.styles}"{$a.attrs}><a>{$a.html}</a></span>
                                        {/foreach}
                                    {/if}
								</div>
                            {/if}
						</div>
					</div>
				</div>
			</div>
		</div>
		<div id="right_content">
            {$record_info}
		</div>
	</div>
</div>
