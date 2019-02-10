<?php namespace std\ui\grid\controllers;

class Main extends \Controller
{
    public function __create()
    {
        $this->data(false, $this->c('instance:get|', false, 'set, defaults'));
    }

    public function reload()
    {
        $this->jquery('|')->replace($this->view());

        if ($reloadCallback = $this->data('callbacks/reload')) { // todo сделать performCallback($name)
            $this->_call($reloadCallback)->perform();
        }
    }

    public function rearrange() // todo сделать performCallback($name)
    {
        if ($rearrangeCallback = $this->data('callbacks/rearrange')) {
            $this->_call($rearrangeCallback)->perform();
        }
    }

    public function view()
    {
        $v = $this->v('|');

        $pager = $this->data['pager'];

        $count = $this->c('data:getCount|', [], 'model, trashed, filter, filter_call, relations');

        if ($count <= ($pager['page'] - 1) * $pager['per_page']) {
            $pager['page'] = floor($count / $pager['per_page']);

            $this->data('pager', $pager);
        }

        $rows = $this->c('data:getRows|', [], '
            model, trashed, 
            filter, filter_call,
            sorter, pager, 
            relations, 
            columns, sortable_columns, 
            ordering_field, ordering_enabled, 
            rows_classes, row_class_renderer_call
        ');

        $tableView = $this->c('views/table:view|', [
            'rows' => $rows
        ], '
            columns, visible_columns, 
            sorter, 
            ordering_field, ordering_enabled, 
            relations, 
            row_click_call, cells_click_calls, rows_classes, row_class_renderer_call, 
            callbacks
        ');

        $paginatorView = $this->c('views/paginator:view|', ['count' => $count], 'pager');

        $v->assign('CONTENT', $this->c('views/main:view|', [
            'table'     => $tableView,
            'paginator' => $paginatorView
        ]));

        return $v;
    }

    public function update() // todo вынести в app, здесь __create стирает set
    {
        $s = &$this->s(':|');

        ra($s, $this->data('set'));
    }
}
