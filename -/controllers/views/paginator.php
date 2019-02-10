<?php namespace std\ui\grid\controllers\views;

class Paginator extends \Controller
{
    public function view()
    {
        return $this->c('\std\ui paginator:view', [
            'items_count' => $this->data['count'],
            'per_page'    => $this->data['pager']['per_page'],
            'page'        => $this->data['pager']['page'],
            'controls'    => [
                'page'          => [
                    '\std\ui button:view',
                    [
                        'path'    => $this->_p('xhr:setPage:%page|'),
                        'data'    => [],
                        'class'   => 'page_button',
                        'content' => '%page'
                    ]
                ],
                'current_page'  => [
                    '\std\ui button:view',
                    [
                        'class'   => 'page_button selected',
                        'content' => '%page'
                    ]
                ],
                'skipped_pages' => [
                    '\std\ui button:view',
                    [
                        'class'   => 'skipped_pages_button',
                        'content' => '...'
                    ]
                ]
            ]
        ]);
    }
}
