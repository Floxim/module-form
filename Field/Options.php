<?php

namespace Floxim\Form\Field;

use Floxim\Floxim\System\Fx as fx;

abstract class Options extends Field
{
    public function offsetSet($offset, $value)
    {
        if ($offset === 'values' && (is_array($value) || $value instanceof Traversable)) {
            if (is_array($value)) {
                $value = fx::collection($value);
            }
            foreach ($value as $opt_key => $opt_val) {
                if (is_scalar($opt_val)) {
                    $value[$opt_key] = array('name' => $opt_val);
                } 
                // value in format array( array(id, val), array(id, val)...)
                elseif (is_array($opt_val) && count($opt_val) == 2) {
                    unset($value[$opt_key]);
                    $value[$opt_val[0]] = array('name' => $opt_val[1]);
                }
            }
        }
        return parent::offsetSet($offset, $value);
    }
}