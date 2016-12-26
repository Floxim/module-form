<?php
namespace Floxim\Form\Textarea;

use Floxim\Floxim\System\Fx as fx;

class Finder extends \Floxim\Form\Field\Finder 
{
    public function create($data = array()) {
        if (!isset($data['rows'])) {
            $data['rows'] = 4;
        }
        return parent::create($data);
    }
}