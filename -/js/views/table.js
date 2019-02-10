// head {
var __nodeId__ = "std_ui_grid__views_table";
var __nodeNs__ = "std_ui_grid";
// }

(function (__nodeNs__, __nodeId__) {
    $.widget(__nodeNs__ + "." + __nodeId__, {
        options: {},

        _create: function () {
            this.bind();
        },

        bind: function () {
            var widget = this;
            var $widget = widget.element;

            $("td.cell[ws='0']", $widget).each(function () {
                var width = $(this).width();
                var columnId = $(this).attr("column_id");

                $("td.cell[column_id='" + columnId + "'] .cell_wrapper", $widget).width(width);
                $("td.cell[column_id='" + columnId + "'] .cell_container", $widget).width(width);
            });

            $(".cell_content", $widget).removeClass("tmp_max_width");

            ewma.delay(function () {
                $(".cell_wrapper", $widget).height("auto").each(function () {
                    $(this).height($(this).closest("td.cell").height());
                });
            });

            // setColumnWidth

            $("td.cell > .cell_wrapper", $widget).resizable({
                handles: 'e',
                stop:    function (e, ui) {
                    request(widget.options.paths.setColumnWidth, {
                        column_id: ui.element.parent().attr("column_id"),
                        width:     ui.size.width
                    });

                    ui.element.height(ui.element.closest("td.cell").height());

                    e.stopPropagation();
                },
                resize:  function (e, ui) {
                    $(".cell_wrapper", $widget).height("auto").each(function () {
                        $(this).height($(this).closest("td.cell").height());
                    });

                    $("td.cell[column_id='" + ui.element.parent().attr("column_id") + "'] .cell_wrapper", $widget).width(ui.size.width).height("100%");
                    $("td.cell[column_id='" + ui.element.parent().attr("column_id") + "'] .cell_container", $widget).width(ui.size.width);
                }
            });

            // toggleColumnSort

            $("td.cell.sortable", $widget).rebind("click", function () {
                request(widget.options.paths.toggleColumnSort, {
                    column_id: $(this).attr("column_id")
                });
            });

            // disableColumnSort

            $("td.cell.sortable", $widget).rebind("contextmenu", function (e) {
                e.preventDefault();

                request(widget.options.paths.disableColumnSort, {
                    column_id: $(this).attr("column_id")
                });
            });

            if (widget.options.ordering) {
                this._bindRowsSorting();
            }

            $(".ui-resizable-handle", $widget).css("z-index", 50);
        },

        _bindRowsSorting: function () {
            var widget = this;
            var $widget = widget.element;

            $("table", $widget).sortable({
                items:    "tr[row_id]",
                distance: 10,
                helper:   function (e, tr) { // http://stackoverflow.com/questions/1307705/jquery-ui-sortable-with-table-and-tr-width
                    var $originals = tr.children();
                    var $helper = tr.clone();

                    $helper.children().each(function (index) {
                        $(this).width($originals.eq(index).width()); // Set helper cell sizes to match the original sizes
                    });

                    return $helper;
                },
                update:   function (e, ui) {
                    if (widget.options.ordering) {
                        var previous = false;
                        var placedBeforeNext = false;

                        var placingData = {
                            id:          ui.item.attr("row_id"),
                            neighbor_id: false,
                            side:        false
                        };

                        $("table", $widget).find("tr[row_id]").each(function () {
                            if (placedBeforeNext) {
                                placingData.neighbor_id = $(this).attr("row_id");
                                placingData.side = 'before';

                                placedBeforeNext = false;
                            }

                            if ($(this).attr("row_id") == ui.item.attr("row_id")) {
                                if (previous) {
                                    placingData.neighbor_id = previous;
                                    placingData.side = 'after';
                                } else {
                                    placedBeforeNext = true;
                                }
                            }

                            previous = $(this).attr("row_id");
                        });

                        request(widget.options.ordering.path, {
                            placing: placingData
                        });

                        e.stopPropagation();
                    }
                }
            });
        }
    });
})(__nodeNs__, __nodeId__);
