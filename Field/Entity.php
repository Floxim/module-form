<?php
namespace Floxim\Form\Field;

use Floxim\Floxim\System\Fx as fx;

class Entity extends \Floxim\Floxim\Component\Basic\Entity
{
    
    public $is_generated = false;
    
    public function __construct($data = array(), $component_id = null) 
    {
        parent::__construct($data, $component_id);
        $this['errors'] = fx::collection();
    }
    
    public static function prepare($field)
    {
        if (!isset($field['type'])) {
            $field['type'] = 'text';
        }
        if ($field['type'] === 'submit') {
            $field['type'] = 'button';
        }
        $field['type'] = 'floxim.form.'.$field['type'];
        return $field;
    }
    
    public function _getFieldType() 
    {
        $type = $this['type'];
        return preg_replace('~^floxim.form.~', '', $type);
    }
    
    public function _getFieldId()
    {
        $form = $this->getForm();
        if (!$form) {
            return '';
        }
        return $form['form_id'].'-'.$this['name'];
    }
    
    public function _getName()
    {
        $real = $this->getReal('name');
        if ($real) {
            return $real;
        }
        return 'field-'.$this['id'];
    }
    
    public function getForm()
    {
        return $this['form'];
    }
    
    public function loadValue($input) 
    {
        if (isset($input[$this['name']]))  {
            $this['value'] = $input[$this['name']];
        }
        return $this;
    }
    
    public function getValue()
    {
        return $this['value'];
    }
    
    public function addError($error)
    {
        $this['has_errors'] = true;
        $this['errors'][]= $error;
        return $this;
    }
    
    protected static $editable_for_generated = array('label', 'placeholder');
    
    public function getFieldMeta($field_keyword)
    {
        if (!$this->is_generated) {
            return parent::getFieldMeta($field_keyword);
        }
        if (!in_array($field_keyword, self::$editable_for_generated)) {
            return null;
        }
        $field = $this->getField($field_keyword);
        $field_meta = array(
            'var_type' => 'visual',
            'id' => 'field_'.$this['name'].'_'.$field_keyword,
            'label' => $field['name']
        );
        return $field_meta;
    }
    
    public function offsetExists($offset) {
        if ($offset === 'display_value') {
            return true;
        }
        return parent::offsetExists($offset);
    }


    public function offsetGet($offset) {
        if ($offset === 'display_value') {
            return isset($this->data[$offset]) ? $this->data[$offset] : $this['value'];
        }
        if (!$this->is_generated || !in_array($offset, self::$editable_for_generated)) {
            return parent::offsetGet($offset);
        }
        $res = null;
        $template = fx::env()->getCurrentTemplate();
        if ($template && ($context = $template->context) ) {
            $res = $context->get('field_'.$this['name'].'_'.$offset);
        }
        if ($res) {
            return $res;
        }
        return parent::offsetGet($offset);
    }
    
    public function prepareForLivesearch($res, $term = '')
    {
        $res['name'] = $this['label'];
        return parent::prepareForLivesearch($res, $term);
    }
    
    public function getBoxFields() {
        $res = parent::getBoxFields();
        $res []= [
            'keyword' => 'input',
            'name' => 'Поле',
            'template' => 'floxim.form.form:control'
        ];
        $res []= [
            'keyword' => 'label',
            'name' => 'Название',
            'template' => 'floxim.form.form:label'
        ];
        return $res;
    }
    
    public function getDefaultBoxFields()
    {
        return [
            [
                [
                    'keyword' => 'label',
                    'template' => 'value'
                ]
            ],
            [
                [
                    'keyword' => 'input',
                    'template' => 'floxim.form.form:control'
                ]
            ]
        ];
    }
}