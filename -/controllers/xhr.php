<?php namespace std\ui\grid\controllers;

class Xhr extends \Controller
{
    public $allow = self::XHR;

    private $s;

    private $instanceData;

    public function __create()
    {
        $this->s = &$this->s('~|');

        $this->instanceData = $this->c('instance:get|', false, 'instance');
    }

    private function reload()
    {
        $this->c('~:reload|');
    }

    public function setColumnWidth()
    {
        $columnId = $this->data('column_id');
        $width = $this->data('width');

        if ($columnId && $width) {
            $columnId = $this->data['column_id'];

            if (isset($this->s['columns'][$columnId])) {
                ap($this->s, path('columns', $columnId, 'width/wrapper/width'), $this->data['width']);
                ap($this->s, path('columns', $columnId, 'width/container/width'), $this->data['width']);
            }
        }
    }

    public function arrangeColumns()
    {
        if ($this->dataHas('sequence')) {
            $columnsOrder = [];

            $n = 0;
            foreach ($this->s['columns_order'] as $columnId) {
                if ($this->s['columns'][$columnId]['visible']) {
                    $columnsOrder[] = $this->data['sequence'][$n];

                    $n++;
                } else {
                    $columnsOrder[] = $columnId;
                }
            }

            $this->s['columns_order'] = $columnsOrder;

            $this->reload();
        }
    }

    public function setPage($page = 1)
    {
        if (is_numeric($page)) {
            $this->s['pager']['page'] = $page;

            $this->reload();
        }
    }

    public function toggleColumnSort()
    {
        if ($columnId = $this->data('column_id')) {
            if ($this->isSortable($columnId)) {
                $sorter = &$this->s['sorter'];

                if (isset($sorter[$columnId])) {
                    $sorter[$columnId] = $sorter[$columnId] == 'ASC' ? 'DESC' : 'ASC';
                } else {
                    $sorter[$columnId] = 'ASC';
                }

                $this->reload();
            }
        }
    }

    public function disableColumnSort()
    {
        if ($columnId = $this->data('column_id')) {
            if ($this->isSortable($columnId)) {
                $sorter = &$this->s['sorter'];

                unset($sorter[$columnId]);

                $this->reload();
            }
        }
    }

    private function isSortable($columnId)
    {
        return $this->s['columns'][$columnId]['sortable'] && $this->s['columns'][$columnId]['field'];
    }

    public function arrangeRows()
    {
        $ordering = $this->instanceData['ordering'];
        $placing = $this->data('placing');

        if ($ordering && $placing) {
            $builder = new $this->instanceData['model'];

            if (isset($placing['neighbor_id']) && isset($placing['side']) && in($placing['side'], 'before, after')) {
                $neighbor = $builder->find($placing['neighbor_id']);

                $delta = $placing['side'] == 'before' ? -5 : 5;

                if ($neighbor) {
                    $orderingField = $this->instanceData['ordering_field'];

                    $builder->find($placing['id'])->update([
                                                               $orderingField => $neighbor->{$orderingField} + $delta
                                                           ]);

                    \DB::statement('SET @i := 0;');

                    $builder = $this->filter($builder, $ordering[$orderingField]);

                    $builder->orderBy($orderingField)->update([$orderingField => \DB::raw('(@i := @i + 10)')]);
                }
            }

            $this->c('~:rearrange|');
        }
    }

    private function filter($builder)
    {
        foreach ($this->instanceData['filter'] as $field => $cond) {
            $relation = false;

            if (strpos($field, '/')) {
                $relation = str_replace('/', '.', path_slice($field, 0, -1));
                $field = path_slice($field, -1);
            }

            if ($relation) {
                $builder = $builder->whereHas($relation, function ($query) use ($field, $cond) {
                    if (is_numeric($cond)) {
                        $query->where($field, $cond);
                    }

                    if (is_array($cond)) {
                        foreach ($cond as $operator => $operand) {
                            if ($operator == 'in') {
                                $query->whereIn($field, (array)$operand);
                            } else {
                                $query->where($field, $operator, $operand);
                            }
                        }
                    }
                });
            } else {
                if (is_scalar($cond)) {
                    $builder = $builder->where($field, $cond);
                }

                if (is_array($cond)) {
                    foreach ($cond as $operator => $operand) {
                        if ($operator == 'in') {
                            $builder = $builder->whereIn($field, (array)$operand);
                        } else {
                            $builder = $builder->where($field, $operator, $operand);
                        }
                    }
                }
            }
        }

        return $builder;
    }

    // todo tmp

    private function tmpFilter($model)
    {
        foreach ($this->instanceData['filter'] as $field => $cond) {
            $relation = false;

            if (strpos($field, '/')) {
                $related_field = explode('/', $field);

                $relation = $related_field[0];
                $field = $related_field[1];
            }

            if ($relation) {
                //                $model = // todo похоже не нужно
                $model->whereHas($relation, function ($q) use ($field, $cond) {
                    if (is_numeric($cond)) {
                        $q->where($field, $cond);
                    }

                    if (is_array($cond)) {
                        foreach ($cond as $operator => $operand) {
                            $q->where($field, $operator, $operand);
                        }
                    }
                });
            } else {
                if (is_numeric($cond)) {
                    $model = $model->where($field, $cond);
                }

                if (is_array($cond)) {
                    foreach ($cond as $operator => $operand) {
                        $model = $model->where($field, $operator, $operand);
                    }
                }
            }
        }

        return $model;
    }
}