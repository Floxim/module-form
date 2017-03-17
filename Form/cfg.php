<?php
return array(
    'actions' => array(
        'form' => array(
            'name' => 'Форма',
            'disabled' => false,
            'install' => function($ib, $ctr, $params) {
                $ctr->install($ib, $ctr, $params);
            }
        )
    )
);