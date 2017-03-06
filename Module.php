<?php

namespace Floxim\Form;

use Floxim\Floxim\System\Fx as fx;

class Module extends \Floxim\Floxim\Component\Module\Entity {
    public function init()
    {
        fx::template()->register('floxim.form.form');
    }
}