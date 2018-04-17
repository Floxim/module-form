<?php
namespace Floxim\Form\Select;

use Floxim\Floxim\System\Fx as fx;

class Entity extends \Floxim\Form\Field\Entity
{
    public function offsetSet($offset, $value) {
        if ($offset === 'values') {
            $value = self::prepareValues($value);
        }
        parent::offsetSet($offset, $value);
    }
    
    protected static function prepareValues($vals)
    {
        $res = [];
        foreach ($vals as $k => $v) {
            if (!is_array($v)) {
                $v = ['value' => $v, 'name' => $v];
            } elseif (!isset($v['value']) && !isset($v['name']) && count($v) === 2) {
                $v = ['value' => $v[0], 'name' => $v[1]];
            }
            $res[]= $v;
        }
        return $res;
    }

    public function getTotalLength ()
    {
        $res = 0;
        foreach ($this['values'] as $val) {
            $res += mb_strlen($val['name']);
        }
        return $res;
    }
}