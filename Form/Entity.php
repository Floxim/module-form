<?php
namespace Floxim\Form\Form;

use Floxim\Floxim\System\Fx as fx;

class Entity extends \Floxim\Floxim\Component\Basic\Entity
{
    
    public $is_generated = false;
    
    public function __construct($data = array(), $component_id = null) 
    {
        parent::__construct($data, $component_id);
        $this['errors'] = fx::collection();
    }
    
    public function isGenerated()
    {
        return (bool) $this->is_generated;
    }
            
    
    public function addFields($fields)
    {
        foreach ($fields as $keyword => $field) {
            $field['name'] = $keyword;
            $this->addField($field);
        }
        return $this;
    }
    
    public function __get($prop)
    {
        return $this->getValue($prop);
    }
    
    public function addField($field)
    {
        if (is_array($field)) {
            if (!isset($field['type'])) {
                $field['type'] = 'text';
            }
            if ($field['type'] === 'submit') {
                $field['type'] = 'button';
            }
            $field['type'] = 'floxim.form.'.$field['type'];
            $field = fx::data('floxim.form.field')->generate($field);
        }
        
        $field['form'] = $this;
        
        $this['fields'][]= $field;
        if ($this->isSent()) {
            $field->loadValue($this->getInput());
        }
        return $this;
    }
    
    protected $is_sent = null;
    
    protected function getInput()
    {
        return $_POST;
    }
    
    public function _getIsSent()
    {
        return $this->isSent();
    }
    
    public function isSent($set = null)
    {
        if (func_num_args() > 0 && $set) {
            $this->is_sent = true;
            return true;
        }
        if (is_null($this->is_sent)) {
            $input = $this->getInput();
            $this->is_sent = isset($input[$this->getSentMarkerName()]);
            if ($this->is_sent) {
                $this->loadValues();
            }
        }
        return $this->is_sent;
    }
    
    public function loadValues()
    {
        $input = $this->getInput();
        foreach ($this['fields'] as $field) {
            $field->loadValue($input);
        }
        return $this;
    }
    
    public function hasErrors()
    {
        $this->validateValues();
        if (count($this['errors']) > 0) {
            return true;
        }
        foreach ($this['fields'] as $f) {
            if ( count($f['errors']) > 0 ) {
                return true;
            }
        }
        return false;
    }
    
    public function _getMessagesBefore()
    {
        return $this['messages']->find('when_to_show', 'after', '!=');
    }
    
    public function _getMessagesAfter()
    {
        return $this['messages']->find('when_to_show', 'before', '!=');
    }
    
    public function validateValues()
    {
        $that = $this;
        $this['fields']->find('required', 1)->apply(function($f) use ($that) {
            if (!$f->getValue()) {
                $that->addError(
                    'Нужно заполнить поле &laquo;'.$f['label'].'&raquo;',
                    $f['field_type'] === 'hidden' ? null : $f['name']
                );
            }
        });
    }
    
    public function addMessage($m, $when_to_show = 'always')
    {
        if (is_string($m)) {
            $m = array(
                'text' => $m,
                'when_to_show' => $when_to_show
            );
        }
        $this['messages'] []= $m;
    }
    
    public function finish($message = null)
    {
        if ($message) {
            if (is_string($message)) {
                $message = (array) $message;
            }
            $message['when_to_show'] = 'after';
            $this->addMessage($message);
        }
        $this['is_finished'] = true;
        $this->trigger('finish');
    }
    
    protected $_listeners = array();

    public function __call($name, $args)
    {
        if (preg_match("~^on[A-Z]~", $name) && count($args) == 1) {
            $event_name = preg_replace("~^on~", '', $name);
            $event_name = fx::util()->camelToUnderscore($event_name);
            $this->on($event_name, $args[0]);
            return $this;
        }
    }

    public function on($event, $callback)
    {
        if (!isset($this->_listeners[$event])) {
            $this->_listeners[$event] = array();
        }
        $this->_listeners[$event] [] = $callback;
    }

    public function trigger($event)
    {
        if (is_string($event) && isset($this->_listeners[$event])) {
            foreach ($this->_listeners[$event] as $listener) {
                if (is_callable($listener)) {
                    call_user_func($listener, $this);
                }
            }
        }
    }
    
    public function getValues()
    {
        $res = array();
        foreach ($this['fields'] as $field) {
            $res[$field['name']] = $field->getValue();
        }
        return $res;
    }
    
    public function getValue($field_name)
    {
        $this->isSent();
        $field = $this->getField($field_name);
        if ($field) {
            return $field->getValue($field);
        }
        return null;
    }
    
    public function addError($error, $field = false)
    {
        if ($field  && ($field = $this->getField($field))) {
            $field->addError($error);
            return $this;
        }
        $this['errors'][]= array('error' => $error);
        return $this;
    }
    
    public function getField($name)
    {
        return $this['fields']->findOne('name', $name);
    }
    
    public function getInputs()
    {
        return $this['fields']->find(
            'type',
            array('floxim.form.hidden', 'floxim.form.button', 'floxim.form.field'),
            \Floxim\Floxim\System\Collection::FILTER_NOT_IN
        );
    }
    
    public function getButtons()
    {
        return $this['fields']->find('type', 'floxim.form.button');
    }
    
    public function getHidden()
    {
        return $this['fields']->find('field_type', 'hidden');
    }
    
    // prepare to render
    public function prepare()
    {
        $this->addField(
            array(
                'name' => $this->getSentMarkerName(),
                'value' => 1,
                'type' => 'hidden'
            )
        );
        $tpl = fx::env()->getCurrentTemplate();
        $form_id = isset($this['form_id']) ? $this['form_id'] : 'form';
        if ($tpl) {
            $ib = $tpl->context->getFromTop('infoblock');
            if ($ib) {
                $form_id = $form_id.'-'.$ib['id'];
            }
        }
        $this['form_id'] = $form_id;
        //$this->validateValues();
    }
    
    protected function getSentMarkerName()
    {
        return 'form-olo-is-sent';
    }
}