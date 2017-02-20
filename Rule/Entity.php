<?php
namespace Floxim\Form\Rule;

use Floxim\Floxim\System\Fx as fx;

class Entity extends \Floxim\Floxim\Component\Basic\Entity
{
    public function getFormFieldCondition($field)
    {
        $jsf = $field->getJsField($this);
        $form = $this['form'];
        if (!$form) {
            $form = fx::data('floxim.form.form')->create();
        }
        $inputs = $form->getInputs();
        $jsf['fields'] = array();
        foreach ($inputs as $input) {
            $jsf['fields'][]= array(
                'name' => $input['label'],
                'type' => 'string',
                'id' => $input['id'],
                'keyword' => 'field.'.$input['id']
            );
        }
        return $jsf;
    }
    
    protected $conditions = null;
    
    public function getConditions()
    {
        if (is_null($this->conditions)) {
            $this->conditions = json_decode($this['condition'], true);
        }
        return $this->conditions;
    }
    
    public function getAffectedField()
    {
        $res = null;
        $conds = $this->getConditions();
        $get = function($f) use (&$res, &$get) {
            if ($f['type'] === 'group') {
                foreach ($f['values'] as $sub) {
                    $sub_res = $get($sub);
                    if ($sub_res === false) {
                        return false;
                    }
                }
                return;
            }
            $field_path = explode(".", $f['field']);
            
            $field_base = array_shift($field_path);
            if ($field_base !=='field') {
                return;
            }
            $field_name = join(".", $field_path);
            if ($res === null) {
                $res = $field_name;
                return;
            }
            if ($res !== $field_name) {
                $res = null;
                return false;
            }
        };
        $get($conds);
        if ($res) {
            $res = $this['form']->getInputs()->findOne('id', $res)->get('name');
        }
        return $res;
    }
    
    public function check()
    {
        $form = $this['form'];
        if (isset($this['validation_closure'])) {
            $res = call_user_func($this['validation_closure'], $form);
        } else {
            $conds = $this->getConditions();
            $res = \Floxim\Floxim\Field\Condition::check(
                $conds, 
                array(
                    'getters' => array(
                        'field' => function($path) use ($form) {
                            $field = $form->getInputs()->findOne('id', $path[0]);
                            $val  = $field ? trim($field->getValue()) : null;
                            return $val;
                        }
                    )
                )
            );
        }
        
        if ($res) {
            $field = $this->getAffectedField();
            $form->addError($this, $field);
        }
    }
}