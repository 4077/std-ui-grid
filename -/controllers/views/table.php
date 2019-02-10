<?php namespace std\ui\grid\controllers\views;

class Table extends \Controller
{
    public function view()
    {
        $v = $this->v('|');

        // header row

        foreach ($this->data['visible_columns'] as $columnId) {
            $column = $this->data['columns'][$columnId];

            $widthsData = $this->getWidthsData($columnId);

            $v->assign('column', [
                'ID'                  => $columnId,
                'LABEL'               => $column['label_visible']
                    ? $column['label']
                    : '&nbsp;',
                'SORTABLE_CLASS'      => $column['sortable']
                    ? 'sortable'
                    : '',
                'SORT_CLASS'          => isset($this->data['sorter'][$columnId])
                    ? 'sort_' . strtolower($this->data['sorter'][$columnId])
                    : '',
                'WIDTH_SET'           => $widthsData['width_set'],
                'TMP_MAX_WIDTH_CLASS' => $widthsData['width_by_header']
                    ? ''
                    : 'tmp_max_width',
                'WRAPPER_WIDTH_CSS'   => $widthsData['wrapper_css'],
                'CONTAINER_WIDTH_CSS' => $widthsData['container_css']
            ]);
        }

        // data rows

        foreach ($this->data['rows'] as $row) {
            $v->assign('row', [
                'ID'    => $row['id'],
                'CLASS' => $this->renderRowClass($row)
            ]);

            foreach ($this->data['visible_columns'] as $columnId) {
                $column = $this->data['columns'][$columnId];

                $widthsData = $this->getWidthsData($columnId);

                $v->assign('row/column', [
                    'ID'                   => $columnId,
                    'CLASS'                => $column['class'],
                    'WRAPPER_WIDTH_CSS'    => $widthsData['wrapper_css'],
                    'CONTAINER_WIDTH_CSS'  => $widthsData['container_css'],
                    'HOVER'                => $column['hover']
                        ? $column['hover']
                        : 'hover',
                    'HOVER_LISTEN_ATTR'    => $column['hover_listen']
                        ? ' hover_listen="' . $column['hover_listen'] . '_' . $row['id'] . '"'
                        : '',
                    'HOVER_BROADCAST_ATTR' => $column['hover_broadcast']
                        ? ' hover_broadcast="' . $column['hover_broadcast'] . '_' . $row['id'] . '"'
                        : '',
                    'HOVER_GROUP_ATTR'     => $column['hover_group']
                        ? ' hover_group="' . $column['hover_group'] . '_' . $row['id'] . '"'
                        : '',
                    'CONTENT'              => $this->getCellView($row, $columnId)
                ]);

                if (isset($this->data['cells_click_calls'][$columnId])) {
                    $this->c('\std\ui button:bind', [
                        'selector' => $this->_selector('|') . " table tr[row_id='" . $row['id'] . "'] td[column_id='" . $columnId . "']",
                        'path'     => $this->data['cells_click_calls'][$columnId][0],
                        'data'     => $this->tokenizeData($row, $columnId, $this->data['cells_click_calls'][$columnId][1])
                    ]);
                }
            }

            if ($this->data['row_click_call']) {
                $this->c('\std\ui button:bind', [
                    'selector' => $this->_selector('|') . " table tr[row_id='" . $row['id'] . "']",
                    'path'     => $this->data['row_click_call'][0],
                    'data'     => $this->tokenizeData($row, null, $this->data['row_click_call'][1])
                ]);
            }
        }

        $this->c('\std\ui sortable:bind', [
            'selector'       => $this->_selector('|') . " tr.header_row",
            'items_id_attr'  => 'column_id',
            'path'           => 'xhr:arrangeColumns|',
            'plugin_options' => [
                'axis'     => 'x',
                'distance' => 25,
                'helper'   => 'clone'
            ]
        ]);

        $this->widget(':|', [
            'paths'    => [
                'setColumnWidth'    => $this->_p('xhr:setColumnWidth|'),
                'toggleColumnSort'  => $this->_p('xhr:toggleColumnSort|'),
                'disableColumnSort' => $this->_p('xhr:disableColumnSort|'),
            ],
            'ordering' => $this->data['ordering_enabled'] && $this->data['ordering_field'] && empty($this->data['sorter'])
                ? ['path' => $this->_p('xhr:arrangeRows|')]
                : false
        ]);

        $this->css();

        return $v;
    }

    private function renderRowClass($row)
    {
        $class = [];

        if ($rowClassRendererCall = $this->data['row_class_renderer_call']) {
            $rowClassRendererCall[1] = $this->tokenizeData($row, null, $rowClassRendererCall[1]);

            $class[] = $this->_call($rowClassRendererCall)->perform();
        }

        foreach ($this->data['rows_classes'] as $rowClassName => $rowsWithThisClassIds) {
            if (in_array($row['id'], $rowsWithThisClassIds)) {
                $class[] = $rowClassName;
            }
        }

        return implode(' ', $class);
    }

    private function getCellView($row, $columnId)
    {
        $column = $this->data['columns'][$columnId];

        if ($control = $column['control']) {
            $controlCall = $this->_call($control);
            $controlCall->data(false, $this->tokenizeData($row, $columnId, $controlCall->data()));

            $content = $controlCall->perform();
        } else {
            $content = $this->getCellValue($row, $columnId);
        }

        return $content;
    }

    private $cellsValuesCache = [];

    private function getCellValue($row, $columnId)
    {
        if (!isset($this->cellsValuesCache[$columnId][$row->id])) {
            $column = $this->data['columns'][$columnId];

            if ($column['relation']) {
                $relationType = $this->data['relations'][$column['relation']]['type'];

                if ($relationType == 'BelongsToMany') {
                    $otherKey = $this->data['relations'][$column['relation']]['other_key'];
                    $otherValue = $column['other_value'];

                    $value = null;
                    foreach ($row[$column['relation']] as $related) {
                        if ($related['pivot'][$otherKey] == $otherValue) {
                            $value = $related['pivot'][$column['field']];

                            break;
                        }
                    }
                } else {
                    $value = $row[$column['relation']][$column['field']] ?? false;
                }
            } else {
                $value = !empty($column['field']) ? $row[$column['field']] : false;
            }

            $this->cellsValuesCache[$columnId][$row->id] = $value;
        }

        return $this->cellsValuesCache[$columnId][$row->id];
    }

    protected function tokenizeData($row, $columnId, $data)
    {
        $flatten = a2f($data);

        foreach ($flatten as $path => $value) {
            if ($value === '%model') {
                $flatten[$path] = $row;
            } elseif ($value === '%model_id') {
                $flatten[$path] = $row->id;
            } elseif ($value === '%pack') {
                $flatten[$path] = pack_model($row);
            } elseif ($value === '%xpack') {
                $flatten[$path] = xpack_model($row);
            } elseif ($value === '%cell') {
                $flatten[$path] = pack_cell($row, $columnId); // будет работать только для полей в текущей таблице (не на связях)
            }

            if (null !== $columnId) {
                if ($value === '%column_id') {
                    $flatten[$path] = $columnId;
                } elseif ($value === '%value') {
                    $flatten[$path] = $this->getCellValue($row, $columnId);
                }
            }
        }

        $output = f2a($flatten);

        return $output;
    }

    private function getWidthsCss($widths, $type)
    {
        $output = '';

        if (!empty($widths[$type])) {
            $widths[$type]['width'] && $output .= 'width: ' . $widths[$type]['width'] . 'px; ';
            $widths[$type]['min'] && $output .= 'min-width: ' . $widths[$type]['min'] . 'px; ';
            $widths[$type]['max'] && $output .= 'max-width: ' . $widths[$type]['max'] . 'px; ';
        }

        return $output;
    }

    private $widthsData = [];

    private function getWidthsData($columnId)
    {
        if (!isset($this->widthsData[$columnId])) {
            $column = $this->data['columns'][$columnId];

            $this->widthsData[$columnId] = [
                'wrapper_css'     => $this->getWidthsCss($column['width'], 'wrapper'),
                'container_css'   => $this->getWidthsCss($column['width'], 'container'),
                'width_set'       => $column['width']['wrapper']['width'] && $column['width']['wrapper']['width'] != 'h' ? 1 : 0,
                'width_by_header' => $column['width']['wrapper']['width'] == 'h'
            ];
        }

        return $this->widthsData[$columnId];
    }
}
