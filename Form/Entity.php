<?php
namespace Floxim\Form\Form;

use Floxim\Floxim\System\Fx as fx;

class Entity extends \Floxim\Floxim\Component\Basic\Entity
{
    
    public function __construct($data = array(), $component_id = null) 
    {
        parent::__construct($data, $component_id);
        $this['errors'] = fx::collection();
    }
    
    public function addFields($fields)
    {
        foreach ($fields as $keyword => $field) {
            if (!isset($field['name'])) {
                $field['name'] = $keyword;
            }
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
            $field = \Floxim\Form\Field\Entity::prepare($field);
            $field = fx::data('floxim.form.field')->generate($field);
        }
        
        $field['form'] = $this;
        
        $validators = $field['validators'];
        if ($validators) {
            if (is_string($validators)) {
                $validators = [$validators];
            }
            foreach ($validators as $validator) {
                if ($validator === 'email') {
                    $validator = [
                        'form' => $this,
                        'text' => 'Укажите корректный e-mail',
                        'affected_field' => $field['name'],
                        'validation_closure' => function($form) use ($field) {
                            $val = trim($field->getValue());
                            if (empty($val)) {
                                return;
                            }
                            if (!preg_match("~[0-9a-z_\.\-]+@[a-z0-9\-]+\.[a-z0-9]+~", $val)) {
                                return true;
                            }
                        }
                    ];
                }
                if (is_array($validator)) {
                    $validator = fx::data('floxim.form.rule')->create($validator);
                    $this['validators'] []= $validator;
                }
            }
        }
        
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
    
    public function validateValues($sent_only = true)
    {
        $that = $this;
        
        if ($sent_only && !$this->isSent()) {
            return;
        }
        
        $required = $this['fields']->find('required');
        $required->apply(function($f) use ($that) {
            if (!$f->getValue() && !$f['required_validated']) {
                $that->addError(
                    'Нужно заполнить поле &laquo;'.$f['label'].'&raquo;',
                    $f['field_type'] === 'hidden' ? null : $f['name']
                );
                $f['required_validated'] = true;
            }
        });
        
        if (!$this['validators']) {
            return;
        }
        foreach ($this['validators'] as $validator) {
            $validator->check();
        }
    }
    
    public function addMessage($m, $when_to_show = 'always')
    {
        if (is_string($m)) {
            $m = array(
                'text' => $m,
                'when_to_show' => $when_to_show
            );
        }
        if (is_array($m)) {
            if (isset($m['header'])) {
                $m['name'] = $m['header'];
                unset($m['header']);
            }
            $m = fx::data('floxim.form.message')->generate($m);
        }
        $this['messages'] []= $m;
    }
    
    public function finish($message = null)
    {
        if ($message) {
            if (is_string($message)) {
                $message = ['text' => $message];
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
        $is_sent = $this->isSent();
        $field = $this->findField($field_name);
        if ($field) {
            return $field->getValue($field);
        }
        if ($is_sent) {
            $input = $this->getInput();
            if (isset($input[$field_name])) {
                return $input[$field_name];
            }
        }
        return null;
    }
    
    public function addError($error, $field = false)
    {
        if ($field  && ($field = $this->findField($field))) {
            $field->addError($error);
            return $this;
        }
        $this['errors'][]= array('error' => $error);
        return $this;
    }
    
    public function findField($name)
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
        
        if (fx::page()->isOverriden()) {
            $this->prepareOverriden();
        }
        
        $tpl = fx::env()->getCurrentTemplate();
        $form_id = isset($this['form_id']) ? $this['form_id'] : 'form';
        if ($tpl) {
            $ib = $tpl->context->getFromTop('infoblock');
            if ($ib) {
                $form_id = $form_id.'-'.$ib['id'];
            }
        }
        $this['form_id'] = $form_id;
        if ($this->isSent()) {
            $this->hasErrors();
        }
    }
    
    protected function prepareOverriden()
    {
        if (!$this->hasErrors()) {
            $this->addError('Так будет выглядеть сообщение об ошибке для формы!');
        }
        $inputs = $this->getInputs();
        if ( count($inputs) === 0) {
            $this->addField([
                'name' => 'test_field_for_overriden_block',
                'label' => 'Это поле-заглушка'
            ]);
            $inputs = $this->getInputs();
        }
        $input = $this->getInputs()->first();
        if ($input) {
            $this->addError('Так будет выглядеть сообщение об ошибке для поля!', $input['name']);
        }
        if (count($this->getButtons()) === 0) {
            $this->addField([
                'type' => 'submit',
                'label' => 'Это кнопка'
            ]);
        }
    }
    
    public function handleCaptcha()
    {
        $session_key = $this['id'].'_captcha';
        
        $cv = rand(100,999);
        $_SESSION[$session_key] = $cv;
        $nums = explode(',', ',один,два,три,четыре,пять,шесть,семь,восемь,девять');
        $extracted = rand(1,9);
        $this->captcha_value = $cv;
        $this->addField([
            'name' => $session_key,
            'label' => 'Сколько будет '.($cv - $extracted).' плюс '.$nums[$extracted].'?',
            'display_value' => '',
            'is_captcha' => true
        ]);
        $this['validators'] []= fx::data('floxim.form.rule')->create([
            'text' => 'Для отправки этой формы нужно включить JavaScript (и не быть роботом)',
            'form' => $this,
            'validation_closure' => function($form) use ($session_key) {
                $sent_val = $form->getValue($session_key) * 1;
                $session_val = fx::input('session', $session_key) * 1;
                return (!$sent_val || $sent_val !== $session_val);
            } 
        ]);
        $this['captcha_expression'] = self::generateCaptchaExpression($cv);
    }
    
    protected static function generateCaptchaExpression($val)
    {
        $e = self::generateExpression($val);
        return base64_encode($e);
    }

    protected static function generateExpression($val, $level = 0) 
    {
	$ops = ['+','-','/'];
    
        if ( round($val) == $val ) {
            $ops []= '*';
        }


        $op = $ops[array_rand($ops)];
        $rand = rand(100,999);

        switch ($op) {
            case '+':
                    $alt = $val - $rand;
                    break;
            case '-':
                    $alt = $val + $rand;
                break;
            case '*':
                    $alt = round($val / $rand, 4);
                break;
                case '/':
                    $alt = $val * $rand;
                break;
        }

        if ($level === 0) {
            $alt = '('.self::generateExpression($alt, $level+1).')';
            $rand = '('.self::generateExpression($rand, $level+1).')';
        }


        $expr = $alt.$op.$rand;
        if ($op === '*') {
            $expr = 'round('.$expr.')';
        }
        return $expr;
    }
    
    protected function getSentMarkerName()
    {
        return 'form-'.$this['id'].'-is-sent';
    }
}