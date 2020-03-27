<table class="Utils_RecordBrowser__View_entry" cellpadding="0" cellspacing="0" border="0">
	<tbody>
	<tr>
		<td class="column">
			<table cellpadding="0" cellspacing="2" border="0" style="width:100%;"
			       class="edit">
				<tr>
					<td class="label">
                        {$form_data.add_to_list_list.label}
					</td>
					<td class="data {$form_data.add_to_list_list.style}">
						<div style="position:relative;">
                            {if $form_data.add_to_list_list.error}{$form_data.add_to_list_list.error}{/if}
                            {$form_data.add_to_list_list.html}
						</div>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	</tbody>
</table>
