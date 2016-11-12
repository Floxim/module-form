<?php
return array(
    'actions' => array(
        'form' => array(
            'name' => 'Форма',
            'settings' => array(
                'form_id' => array(
                    'type' => 'livesearch',
                    'label' => 'Форма',
                    'values' => $this->getAvailForms()
                )
            ),
            'install' => function($ib, $ctr, $params) {
                $ctr->install($ib, $ctr, $params);
            }
        )
    )
);