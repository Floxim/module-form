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
            }
            $res[]= $v;
        }
        return $res;
    }
}