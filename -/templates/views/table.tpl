<div class="{__NODE_ID__}" instance="{__INSTANCE__}">

    <table>
        <tr class="header_row">
            <!-- column -->
            <td class="cell {SORTABLE_CLASS} {SORT_CLASS}" hover="hover" column_id="{ID}" width="1" ws="{WIDTH_SET}" title="{LABEL}">
                <div class="cell_wrapper" style="{WRAPPER_WIDTH_CSS}">
                    <div class="cell_container" style="{CONTAINER_WIDTH_CSS}">
                        <div class="cell_content {TMP_MAX_WIDTH_CLASS}">
                            <div class="sort_icon"></div>
                            {LABEL}
                        </div>
                    </div>
                </div>
            </td>
            <!-- / -->
        </tr>

        <!-- row -->
        <tr class="row {CLASS}" row_id="{ID}" hover="hover">
            <!-- row/column -->
            <td class="cell {CLASS}" column_id="{ID}" valign="top" hover="{HOVER}"{HOVER_LISTEN_ATTR}{HOVER_BROADCAST_ATTR}{HOVER_GROUP_ATTR}>
                <div class="cell_wrapper" style="{WRAPPER_WIDTH_CSS}">
                    <div class="cell_container" style="{CONTAINER_WIDTH_CSS}">
                        <div class="cell_content">
                            {CONTENT}
                        </div>
                    </div>
                </div>
            </td>
            <!-- / -->
        </tr>
        <!-- / -->
    </table>

</div>