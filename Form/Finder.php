<?php
namespace Floxim\Form\Form;

use Floxim\Floxim\System\Fx as fx;

class Finder extends \Floxim\Floxim\Component\Basic\Finder 
{
    public function generate($params = array())
    {
        $form = $this->create($params);
        $form->is_generated = true;
        $form['fields'] = fx::collection();
        return $form;
    }
}