<?php
return array(
    'actions' => array(
        'form' => array(
            'name' => 'Форма',
            'install' => function($ib, $ctr, $params) {
                $ctr->install($ib, $ctr, $params);
            }
        )
    )
);