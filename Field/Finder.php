<?php
namespace Floxim\Form\Field;

use Floxim\Floxim\System\Fx as fx;

class Finder extends \Floxim\Floxim\Component\Basic\Finder 
{
    public function generate($params = array())
    {
        $field = $this->create($params);
        $field->is_generated = true;
        return $field;
    }
    
    public function orderDefault() {
        $this->order('priority');
    }
}