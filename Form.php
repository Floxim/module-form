<?php

namespace Floxim\Form;

use Floxim\Floxim\Template;
use Floxim\Floxim\System\Fx as fx;

class Form implements \ArrayAccess, Template\Entity
{

    protected $params = array();
    
    public function getAvailableOffsetKeys() {
        return array_flip(array_keys($this->params));
    }
    
    public function addTemplateRecordMeta($html, $collection, $index, $is_subroot) {
        return $html;
    }
    
    public function getFieldMeta($field_keyword) {
        return array();
    }

    public function __construct($params = array())
    {
        $params = array_merge(array(
            'method' => 'POST',
            'skin' => 'default',
            'messages' => fx::collection()
        ), $params);
        $fields = new Fields();
        $fields->form = $this;
        $params['fields'] = $fields;
        $this->params = $params;
        $this->addField(array('name' => 'default_submit', 'type' => 'submit', 'label' => 'Submit'));
    }

    public function addFields($fields)
    {
        foreach ($fields as $name => $props) {
            if (!isset($props['name'])) {
                $props['name'] = $name;
            }
            $this->addField($props);
        }
    }

    /**
     * Returns input with merged $_FILES array
     */
    protected $input = null;
    
    public function getInput()
    {
        if (!is_null($this->input)) {
            return $this->input;
        }
        $input = strtolower($this['method']) == 'post' ? $_POST : $_GET;
        $merge_branch_copy = function($branch, $key, &$target, $last_key) use (&$merge_branch_copy) {
            if (!isset($target[$key])) {
                $target[$key] = array();
            }
            if (is_array($branch)) {
                foreach ($branch as $branch_key => $sub) {
                    $merge_branch_copy($sub, $branch_key, $target[$key], $last_key);
                }
            } else {
                $target[$key][$last_key] = $branch;
            }
        };
        foreach ($_FILES as $top_key => $props) {
            if (!isset($input[$top_key])) {
                $input[$top_key] = array();
            }
            $res =& $input[$top_key];
            if (isset($props['name']) && !is_array($props['name'])) {
                $res = $props;
            } else {
                foreach ($props as $pkey => $pr) {
                    foreach ($pr as $bkey => $b) {
                        $merge_branch_copy($b, $bkey, $res, $pkey);
                    }
                }
            }
        }
        $this->input = $input;
        return $input;
    }

    public function getId()
    {
        if (!isset($this['id'])) {
            $this['id'] = md5(join(",", $this->params['fields']->keys()));
        }
        return $this['id'];
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

    protected $is_sent = null;

    public function isSent()
    {
        if (is_null($this->is_sent)) {
            $input = $this->getInput();
            $this->is_sent = isset($input[$this->getId() . '_sent']);
            if ($this->is_sent) {
                $this->loadValues($input);
                //$this->validate();
                $this->trigger('sent');
            }
        }
        return $this->is_sent;
    }

    public function validate()
    {
        if (!$this->isSent()) {
            return true;
        }
        return $this->params['fields']->validate();
    }

    public function loadValues($source = null)
    {
        if (is_null($source)) {
            $source = $this->getInput();
        }
        foreach ($this->params['fields'] as $name => $field) {
            $field->loadValue($source);
        }
    }

    public function setValue($field, $value)
    {
        $this->params['fields']->setValue($field, $value);
    }

    public function getValue($field = null)
    {
        $this->isSent();
        return $this->params['fields']->getValue($field);
    }

    /**
     * Magic getter returns field value
     * @param type $name
     */
    public function __get($name)
    {
        return $this->getValue($name);
    }

    public function getValues()
    {
        return $this->params['fields']->getValues();
    }

    public function addField($params)
    {
        if (isset($params['type']) && $params['type'] == 'submit') {
            $this->params['fields']->findRemove('name', 'default_submit');
        }
        $field = $this->params['fields']->addField($params);
        if ($this->isSent()) {
            $field->loadValue($this->getInput());
        }
        return $field;
    }
    
    public function storeValue($name, $value) {
        $this->addField(array(
            'type' => 'hidden',
            'name' => $name,
            'value' => $value
        ));
        return $this->getValue($name);
    }

    public function addMessage($message, $after_finish = false)
    {
        $this['messages'][] = array('message' => $message, 'after_finish' => (bool)$after_finish);
    }

    public function finish($message = null)
    {
        $this['is_finished'] = true;
        if ($message) {
            $this->addMessage($message, true);
        }
        $this->trigger('finish');
    }

    public function hasErrors()
    {
        $this->validate();
        return count($this->getErrors()) > 0;
    }

    public function getErrors()
    {
        $errors = isset($this['errors']) ? $this['errors'] : fx::collection();
        $errors->concat($this->params['fields']->getErrors());
        return $errors;
    }

    public function addError($error, $field_name = false)
    {
        $field = $field_name ? $this->getField($field_name) : false;
        if ($field) {
            $field->addError($error);
            return;
        }
        if (!isset($this->params['errors'])) {
            $this->params['errors'] = fx::collection();
        }
        $this->params['errors'][] = array('error' => $error);
    }

    public function getField($name)
    {
        return $this->params['fields']->getField($name);
    }

    public function get($offset = null)
    {
        if (is_null($offset)) {
            return $this->params;
        }
        return $this->offsetGet($offset);
    }

    /* ArrayAccess methods */

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->params) || in_array($offset, array('is_sent'));
    }

    public function offsetGet($offset)
    {
        if ($offset === 'is_sent') {
            return $this->isSent();
        }
        return array_key_exists($offset, $this->params) ? $this->params[$offset] : null;
    }

    public function offsetSet($offset, $value)
    {
        $this->params[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->params[$offset]);
    }
}