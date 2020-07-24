<div class="layer" style="padding:9px;width:70%;">
	<div class="css3_content_shadow">
		<div class="Utils_RecordBrowser__container">
			<table class="Utils_RecordBrowser__table" cellpadding="5" cellspacing="5" border="0">
				<tbody>
				<tr>
					<td>
						<table class="Utils_RecordBrowser__View_entry" cellpadding="0" cellspacing="0"
						       border="0">
							<tbody>
							<td class="label" style="width:50%;">
                                {$remain.label}
							</td>
							<td class="data" style="background:whitesmoke;text-align: center;">{$remain.data}</td>
							</tbody>
						</table>
					</td>
					<td></td>
				</tr>
                {foreach from=$summary item=summaryItem}
					<tr>
                        {foreach from=$summaryItem key=label item=sum}
							<td>
								<div class="name" style="margin:5px 0 0 0">
                                    {$label}
								</div>
								<table class="Utils_RecordBrowser__View_entry" cellpadding="0" cellspacing="0"
								       border="0">
                                    {foreach from=$sum key=disp item=dispCount}
										<tr>
											<td class="label" style="width:50%;">{$disp}</td>
											<td class="data"
											    style="background:whitesmoke;text-align: center;">{$dispCount}
												record(s)
											</td>
										</tr>
                                    {/foreach}
								</table>
							</td>
                        {/foreach}
					</tr>
                {/foreach}
				</tbody>
			</table>
		</div>
	</div>
</div>
