<?php namespace std\ui\grid\controllers\views;

class Main extends \Controller
{
    public function view()
    {
        $v = $this->v('|');

        $v->assign([
                       'TABLE'     => $this->data['table'],
                       'PAGINATOR' => $this->data['paginator']
                   ]);

        $this->css();

        return $v;
    }
}
