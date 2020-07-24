{if $cc_mode == 'admin'}
	<h3>{$static_text.title}</h3>
    {$static_text.desc}
{/if}
{$form_open}
<div class="layer" style="padding:9px;">
	<div class="css3_content_shadow" style="width:90%;">
		<div class="Utils_RecordBrowser__container" style="padding:9px;text-align: left;">
			<div style="background: whitesmoke;padding:9px;border-radius:5px;">
				<div class="child_button">
					<input type="button" value="{$static_text.add_rule}"
					       onclick="CallCampaignRules.add_rule();">
					<a class="button lbOn" rel="{$lb_id}" href="javascript:void(0);">
                        {$static_text.merge_fields}</a>
                    {if $cc_mode == 'record'}
						<a class="button" {$submit_href} style="float:right;">{$static_text.save}</a>
                    {/if}
				</div>
                {if !$rules}
					<p style="text-align: center;font-size:10pt;color:darkgray;">{$static_text.no_rules}</p>
                {else}
					<ol id="call_campaign_rules" style="font-size:10pt;">
                        {foreach key=k item=rule from=$rules}
                            {$rule}
                        {/foreach}
					</ol>
                {/if}
			</div>
		</div>
	</div>
</div>
{$form_close}
