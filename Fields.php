<?php

namespace Floxim\Form;

use Floxim\Floxim\System;
use Floxim\Floxim\System\Fx as fx;

class Fields extends System\Collection
{
    
    public static function create($fields = null) {
        $instance = new Fields();
        if ($fields instanceof System\Collection) {
            $instance = $fields->fork($instance);
        }
        if (is_array($fields) || $fields instanceof \Traversable) {
            foreach ($fields as $fk => $f) {
                if (! (is_array($f) || $f instanceof \ArrayAccess) ) {
                    continue;
                }
                if (!isset($f['name'])) {
                    $f['name'] =  $fk;
                }
                $instance->addField($f);
            }
        }
        return $instance;
    }

    public function offsetSet($offset, $value)
    {
        if (!$value instanceof Field\Field) {
            $value['owner'] = $this;
            $value = Field\Field::create($value);
        }
        //$this->data[$offset] = $value;
        $this->set($offset, $value);
    }

    public function setValue($field, $value)
    {
        if (isset($this[$field])) {
            $this[$field]->setValue($value);
        }
    }

    public function addField($params)
    {
        if ($params instanceof Field\Field) {
            $field = $params;
            $field->setOwner($this);
        } else {
            if ($params instanceof \Floxim\Floxim\System\Entity) {
                $entity = $params;
                $params = $entity instanceof \Floxim\Form\FieldEntityInterface ? $entity->getFieldParams() : $entity->get();
                $params['_entity'] = $entity;
            }
            $field = Field\Field::create($params + array('owner' => $this));
        }
        $this[$field['name']] = $field;
        return $field;
    }

    public function getField($name)
    {
        return $this[$name];
    }

    public function getValue($field_name)
    {
        $f = $this->getField($field_name);
        return $f ? $f->getValue() : null;
    }

    public function getValues()
    {
        $values = fx::collection();
        foreach ($this->data as $name => $field) {
            $values[$name] = $field->getValue();
        }
        return $values;
    }

    public function getErrors()
    {
        $errors = fx::collection();
        foreach ($this->data as $f) {
            $f_errors = $f['errors'];
            if (!$f_errors) {
                continue;
            }
            foreach ($f_errors as $e) {
                $errors [] = array('error' => $e, 'field' => $f);
            }
        }
        return $errors;
    }

    public function validate()
    {
        $is_valid = true;
        foreach ($this->data as $f) {
            if ($f->validate() === false) {
                $is_valid = false;
            }
        }
        return $is_valid;
    }

}