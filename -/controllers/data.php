<?php namespace std\ui\grid\controllers;

class Data extends \Controller
{
    public function getRows()
    {
        $builder = new $this->data['model'];

        $builder = $this->relations($builder);
        $builder = $this->filter($builder);
        $builder = $this->externalFilter($builder);
        $builder = $this->trashed($builder);
        $builder = $this->sort($builder);
        $builder = $this->limit($builder);

        $sql = $builder->toSql();

        $rows = $builder->get();

        return $rows;
    }

    public function getCount()
    {
        $builder = new $this->data['model'];

        $builder = $this->relations($builder);
        $builder = $this->filter($builder);
        $builder = $this->externalFilter($builder);
        $builder = $this->trashed($builder);

        return $builder->count();
    }

    private function relations($builder)
    {
        if (!empty($this->data['relations'])) {
//            $builder = $builder->includes(array_keys($this->data['relations'])); // todo че-то клинануло. изучить потом нужен ли вообще слипин оул
            $builder = $builder->with(array_keys($this->data['relations']));
        }

        return $builder;
    }

    private function trashed($builder)
    {
        if (!empty($this->data['trashed'])) {
            if ($this->data['trashed'] == 'with') {
                $builder = $builder->withTrashed();
            }

            if ($this->data['trashed'] == 'only') {
                $builder = $builder->onlyTrashed();
            }
        }

        return $builder;
    }

    private function externalFilter($builder)
    {
        if ($this->data['filter_call']) {
            $builder = $this->_call($this->data['filter_call'])->ra(['builder' => $builder])->perform();
        }

        return $builder;
    }

    private function filter($builder)
    {
        foreach ($this->data['filter'] as $field => $cond) {
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
                            } elseif ($operator == 'not_in') {
                                $query->whereNotIn($field, (array)$operand);
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

    private function sort($builder)
    {
        $sorted = false;

        foreach ($this->data['sortable_columns'] as $columnId) {
            if (isset($this->data['sorter'][$columnId])) {
                $column = $this->data['columns'][$columnId];

                $direction = $this->data['sorter'][$columnId];

                if ($direction) {
                    if ($column['relation']) {
                        $model = $builder->getModel();

                        $relation = $model->{$column['relation']}();
                        $foreignKey = $relation->getForeignKey();

                        $table = $model->getTable();
                        $relatedTable = $relation->getModel()->getTable();

                        $builder = $builder
                            ->addSelect($relatedTable . '.id')
                            ->addSelect($table . '.*')
                            ->leftJoin($relatedTable, $relatedTable . '.id', '=', $table . '.' . $foreignKey)
                            ->orderBy($relatedTable . '.' . $column['field'], $direction);
                    } else {
                        $builder = $builder->orderBy($column['field'], $direction);
                    }
                }

                $sorted = true;
            }
        }

        if (!$sorted && $this->data['ordering_field']) {
            $builder = $builder->orderBy($this->data['ordering_field']);
        }

        return $builder;
    }

    private function limit($builder)
    {
        $pager = $this->data['pager'];

        return $builder->offset(($pager['page'] - 1) * $pager['per_page'])->take($pager['per_page']);
    }
}
