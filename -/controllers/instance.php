<?php namespace std\ui\grid\controllers;

class Instance extends \Controller
{
    public function get()
    {
        $this->render();

        return $this->instanceData;
    }

    private $s;

    private $instanceData = [
        // cached
        'model'                   => false,
        'trashed'                 => false,
        'columns'                 => false,
        'ordering'                => false,
        'relations'               => false,
        'filter'                  => false,
        'filter_call'             => false,
        'sorter'                  => false,
        'pager'                   => false,
        'row_click_call'          => false,
        'cells_click_calls'       => false,
        'rows_classes'            => [],
        'row_class_renderer_call' => false,
        // rendered
        'ordering_field'          => false,
        'ordering_enabled'        => true,
        'columns_order'           => [],
        'visible_columns'         => [],
        'sortable_columns'        => [],
        'callbacks'               => []
    ];

    private function render()
    {
        $this->s = &$this->s('~|');

        if (empty($this->s['initialized'])) {
            $this->sessionDefaultsSet();
        }

        if ($this->data('set')) {
            $this->applySettings($this->data['set']);
        }

        $this->initFromSession();
    }

    private function sessionDefaultsSet()
    {
        ap($this->s, false, [
            'initialized'             => true,
            'model'                   => false,
            'trashed'                 => false,
            'with'                    => [],
            'filter'                  => [],
            'filter_call'             => [],
            'sorter'                  => [],
            'pager'                   => [],
            'columns'                 => [],
            'ordering'                => [],
            'ordering_field'          => false,
            'ordering_enabled'        => true,
            'columns_order'           => [],
            'row_click_call'          => null,
            'cells_click_calls'       => null,
            'rows_classes'            => [],
            'row_class_renderer_call' => false,
            'callbacks'               => []
        ]);

        $this->applySettings($this->data('defaults'));
    }

    private function applySettings($settings)
    {
        remap($this->s, $settings, '
            model, trashed, with, 
            sorter, pager, filter,
            ordering, ordering_field, ordering_enabled, 
            columns_order, 
            rows_classes, 
            callbacks
        ');

        if (isset($settings['columns'])) {
            $this->s['columns'] = $this->columnsFormatFix($settings['columns']);

//            if (empty($this->s['columns_order'])) {
            $this->s['columns_order'] = array_keys($this->s['columns']);
//            }
        }

        $gridCaller = $this->_caller()->_caller(); // todo убедиться что это всегда прокатывает, лучше сделать baseController

        if (isset($settings['filter_call'])) {
            $this->s['filter_call'] = $gridCaller->_abs($settings['filter_call']);
        }

        if (isset($settings['row_click_call'])) {
            $this->s['row_click_call'] = $gridCaller->_abs($settings['row_click_call']);
        }

        if (isset($settings['row_class_renderer_call'])) {
            $this->s['row_class_renderer_call'] = $gridCaller->_abs($settings['row_class_renderer_call']);
        }

        if (isset($settings['cells_click_calls'])) {
            foreach ($settings['cells_click_calls'] as $field => $call) {
                $this->s['cells_click_calls'][$field] = $gridCaller->_abs($call);
            }
        }
    }

    private function initFromSession()
    {
        remap($this->instanceData, $this->s, '
            columns, 
            model, trashed, with, 
            filter, filter_call,
            sorter, pager, 
            ordering, ordering_field, ordering_enabled, 
            columns_order, 
            row_click_call, cells_click_calls, rows_classes, 
            row_class_renderer_call, 
            callbacks
        ');

        if (!$this->instanceData['ordering_field']) {
            foreach ($this->instanceData['ordering'] as $field => $filter) {
                $this->instanceData['ordering_field'] = $field;

                break;
            }
        }

        $this->instanceData['visible_columns'] = [];
        $this->instanceData['sortable_columns'] = [];
        $this->instanceData['relations'] = [];

        foreach ($this->instanceData['columns_order'] as $columnId) {
            $column = $this->instanceData['columns'][$columnId];

            if ($column['visible']) {
                $this->instanceData['visible_columns'][] = $columnId;
            }

            if ($column['visible'] && $column['sortable'] && $column['field']) {
                $this->instanceData['sortable_columns'][] = $columnId;
            }

            if ($column['relation']) {
                $relation = (new $this->instanceData['model'])->{$column['relation']}();

                $relationType = implode(array_slice(explode('\\', get_class($relation)), -1));

                $otherKey = false;
                if ($relationType == 'BelongsToMany') {
                    $otherKey = end(explode('.', $relation->getOtherKey()));
                }

                $this->instanceData['relations'][$column['relation']] = [
                    'other_key' => $otherKey,
                    'type'      => $relationType
                ];
            }
        }

        foreach (l2a($this->instanceData['with']) as $with) {
            $relation = (new $this->instanceData['model'])->{$with}();

            $relationType = implode(array_slice(explode('\\', get_class($relation)), -1));

            $otherKey = false;
            if ($relationType == 'BelongsToMany') {
                $otherKey = end(explode('.', $relation->getOtherKey()));
            }

            $this->instanceData['relations'][$with] = [
                'other_key' => $otherKey,
                'type'      => $relationType
            ];
        }
    }

    private $columnDefault = [
        'label'           => false,
        'label_visible'   => true,
        'relation'        => false,
        'field'           => true,
        'visible'         => true,
        'sortable'        => false,
        'class'           => false,
        'hover'           => 'hover',
        'hover_listen'    => false,
        'hover_broadcast' => false,
        'hover_group'     => false,
        'control'         => [],
    ];

    private $columnWidthsDefault = [
        'wrapper'   => [
            'width' => false,
            'min'   => false,
            'max'   => false
        ],
        'container' => [
            'width' => false,
            'min'   => false,
            'max'   => false
        ]
    ];

    private function columnsFormatFix($columns)
    {
        foreach (array_keys($columns) as $columnId) {
            $column = &$columns[$columnId];

            $column = $this->columnFormatFix($column, $columnId);
        }

        return $columns;
    }

    private function columnFormatFix($column, $columnId)
    {
        aa($column, $this->columnDefault);

        if ($column['field']) {
            if (is_bool($column['field'])) {
                $column['field'] = $columnId;
            } else {
                if (strpos($column['field'], '/')) {
                    $field = p2a($column['field']);

                    $column['relation'] = implode('.', array_slice($field, 0, -1));
                    $column['field'] = end($field);
                }
            }
        }

        $column['sortable'] = $column['sortable'] && $column['field'] && $column['visible'];

        if (isset($column['width'])) {
            $column['width'] = $this->parseWidths($column['width']);
        } else {
            $column['width'] = $this->columnWidthsDefault;
        }

        if (!$column['label']) {
            $column['label'] = $columnId;
        }

        if (false === $column['class']) {
            $column['class'] = $columnId;
        }

        if ($column['control']) {
            $column['control'] = $this->_caller()->_caller()->_abs($column['control']);
        }

        return $column;
    }

    /**
     * @param $width
     *
     * @return array
     *
     * format: wrapperRule[, containerRule]
     *
     * wrapperRule:
     *  1 arg:  width
     *  2 args: width max
     *  3 args: min width max
     *
     * containerRule:
     *  1 arg:  width
     *  2 args: min max
     *  3 args: min width max
     *
     */
    private function parseWidths($width)
    {
        $output = $this->columnWidthsDefault;

        list($wrapper, $container) = array_pad(explode(',', $width), 2, null);

        $wrapper = $this->explodeWidths($wrapper);

        if (count($wrapper) == 1) {
            ra($output, [
                'wrapper/width' => $wrapper[0] == '-' ? false : $wrapper[0]
            ]);
        }

        if (count($wrapper) == 2) {
            ra($output, [
                'wrapper/width' => $wrapper[0],
                'wrapper/min'   => $wrapper[0] == '-' ? false : $wrapper[0],
                'wrapper/max'   => $wrapper[1] == '-' ? false : $wrapper[1]
            ]);
        }

        if (count($wrapper) == 3) {
            ra($output, [
                'wrapper/width' => $wrapper[1],
                'wrapper/min'   => $wrapper[0] == '-' ? false : $wrapper[0],
                'wrapper/max'   => $wrapper[2] == '-' ? false : $wrapper[2]
            ]);
        }

        if (null !== $container) {
            $container = $this->explodeWidths($container);

            if (count($container) == 1) {
                ra($output, [
                    'container/width' => $container[0]
                ]);
            }

            if (count($container) == 2) {
                ra($output, [
                    'container/width' => false,
                    'container/min'   => $container[0] == '-' ? false : $container[0],
                    'container/max'   => $container[1] == '-' ? false : $container[1]
                ]);
            }

            if (count($container) == 3) {
                ra($output, [
                    'container/width' => $container[1],
                    'container/min'   => $container[0] == '-' ? false : $container[0],
                    'container/max'   => $container[2] == '-' ? false : $container[2],
                ]);
            }
        }

        if (!$output['container']['width']) {
            $output['container']['width'] = $output['wrapper']['width'];
        }

        if (!$output['container']['min']) {
            $output['container']['min'] = $output['wrapper']['min'];
        }

        if (!$output['container']['max']) {
            $output['container']['max'] = $output['wrapper']['max'];
        }

        // todo проверять. был случай когда враппер был шире контейнера и было некрасиво
        if ($output['container']['min'] < $output['wrapper']['min']) {
            $output['container']['min'] = $output['wrapper']['min'];
        }

        return $output;
    }

    private function explodeWidths($widths)
    {
        return explode(' ', preg_replace('/ {2,}/', ' ', trim($widths)));
    }
}
