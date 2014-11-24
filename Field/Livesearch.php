<?php

namespace Floxim\Form\Field;

use Floxim\Floxim\System\Fx as fx;

class Livesearch extends Options
{
    public function __construct($params = array()) {
        fx::page()->addJsFile(FX_JQUERY_PATH);
        fx::page()->addJsFile(FX_JQUERY_UI_PATH);
        fx::page()->addJsFile('@floxim/lib/js/jquery.json-2.3.js');
        fx::page()->addJsFile('@floxim/Admin/js/livesearch.js');
        fx::page()->addCssFile('@floxim/Admin/style/livesearch.less');
        return parent::__construct($params);
    }
    
    public function setValue($value) 
    {
        if (is_array($value) && count($value) > 0) {
            $first_val = current($value);
            // we have values from $_POST
            if (!isset($first_val['value_id'])) {
                $value_ids = array();
                $value_prop = $this['name_postfix'];
                foreach ($value as $c_val) {
                    $value_ids []= $c_val[$value_prop];
                }
                $this['params'] = array_merge(
                    is_array($this['params']) ? $this['params'] : array(), 
                    array(
                        'ajax_preload' => true,
                        'plain_values' => $value_ids
                    )
                );
            }
        }
        return parent::setValue($value);
    }
}