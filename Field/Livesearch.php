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
        if (isset($params['values'])) {
            if (!isset($params['params'])) {
                $params['params'] = array();
            }
            $params['params']['preset_values'] = array();
            foreach ($params['values'] as $k => $v) {
                if (!is_array($v) && !$v instanceof \ArrayAccess) {
                    $v = array('id' => $k, 'name' => $v);
                }
                $params['params']['preset_values'][]= $v;
            }
            unset($params['values']);
        }
        return parent::__construct($params);
    }
    
    public function setValue($value) 
    {
        if (is_array($value) && count($value) > 0) {
            $first_val = current($value);
            if (!is_array($first_val) && ! $first_val instanceof \ArrayAccess) {
                if ($this['params']['preset_values']) {
                    foreach ($value as &$c_val) {
                        foreach ($this['params']['preset_values'] as $c_preset) {
                            if ($c_preset['id'] === $c_val) {
                                $c_val = $c_preset;
                                break;
                            }
                        }
                    }
                } else {
                    $this['params'] = array_merge(
                        is_array($this['params']) ? $this['params'] : array(), 
                        array(
                            'ajax_preload' => true,
                            'plain_values' => $value
                        )
                    );
                }
            }
            // we have values from $_POST
            elseif (!isset($first_val['value_id'])) {
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
        $res = parent::setValue($value);
        return $res;
    }
}