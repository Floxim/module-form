<?php
namespace Floxim\Form\Field;

use Floxim\Floxim\System\Fx as fx;

class Finder extends \Floxim\Floxim\Component\Basic\Finder 
{
    public function orderDefault() {
        $this->order('priority');
    }
}