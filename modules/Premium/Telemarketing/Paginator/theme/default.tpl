<span id='pagination'>
    <table id='pagination_table'>
        <tr id='pagination_row'>
            {if isset($page_previous)}
                <td class='page_link'>{$page_previous}</td>
            {/if}
            {foreach from=$page_pages key='key' item="page"}
                <td class='page_link'>
                    {if $key === 'current'}
                        {$page}
                    {else}
                        {$page}
                    {/if}
                </td>
            {/foreach}
            {if isset($page_next)}
                <td class='page_link'>{$page_next}</td>
            {/if}
            {if isset($page_all)}
                <td class='page_link'>{$page_all}</td>
            {/if}
            {if isset($jump_menu_name)}
                <td class='page_form'>
                    {$jump_menu_open}
                    {$jump_menu_data.ipp.html}
                    {$jump_menu_data.jump_menu.html}
                    {$jump_menu_close}
                </td>
            {/if}
            {if isset($ipp_menu_name)}
                <td class='page_form'>
                    {$ipp_menu_open}
                    {$ipp_menu_data.ipp_menu.html}
                    {$ipp_menu_close}
                </td>
            {/if}
    </table>
</span>