<div class="merge_fields_accordion">
    <div class="label label_top" style="padding-top:3px;width: auto;display: block;">{$merge_fields_label}</div>
    {foreach from=$merge_fields_group item=merge_fields key=group}
        <div class="merge_fields">
            <div class="merge_field_group" rel="{$group|lower}">{$group}<span></span></div>
            <div class="merge_fields_container">
                {foreach from=$merge_fields key=merge_field item=value}
                    <div class="merge_field">
                        <div class="insert_button"
                             onclick="javascript:{$insert_function_name}('{$element_id}', '[{$merge_field}]');">Insert
                        </div>
                        <span>{$value}</span>
                    </div>
                {/foreach}
            </div>
        </div>
    {/foreach}
</div>
