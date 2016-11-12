<?php
namespace Floxim\Form\Checkbox;

use Floxim\Floxim\System\Fx as fx;

class Entity extends \Floxim\Form\Field\Entity
{
    public function loadValue($input)
    {
        $this['value'] = isset($input[$this['name']]);
    }
}