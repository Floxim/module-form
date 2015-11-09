<?php

namespace Floxim\Form\Field;

use Floxim\Floxim\System\Fx as fx;

class File extends Field {
    
    public function __construct($params = array()) {
        $params = array_merge(
            array(
                'use_file' => true,
                'use_url' => true,
                'default_type' => 'file',
                'allowed_types' => array(
                    'pdf', 
                    'doc', 
                    'docx', 
                    'xls', 
                    'xlsx', 
                    'ppt', 
                    'pptx', 
                    'txt',
                    'png',
                    'jpg',
                    'gif',
                    'jpeg'
                )
            ), 
            $params
        );
        parent::__construct($params);
    }
    
    public function getInputs() {
        $switcher_name = $this['name'].'[input_type]';
        $res = array();
        $default = $this['default_type'];
        if ($this['use_file']) {
            $res []= array(
                'type' => 'file', 
                'label' => 'File', 
                'name' => $switcher_name, 
                'checked' => $default === 'file'
            );
        }
        if ($this['use_url']) {
            $res []= array(
                'type' => 'url', 
                'label' => 'URL', 
                'name' => $switcher_name, 
                'checked' => $default === 'url'
            );
        }
        if ($this['default_type'] != 'file') {
            $res = array_reverse($res);
        }
        return $res;
    }
    
    protected function isUploadedPath($path)
    {
        $str = fx::path()->http('@files/upload');
        $res =  substr($path, 0, strlen($str)) === $str;
        fx::log('isupl', $res, substr($path, 0, strlen($str)), $str);
        return $res;
    }
    
    public function setValue($val) {
        $res = null;
        if (is_string($val)) {
            $res = fx::files()->getInfo( fx::path($val) );
        } else {
            $c_type = isset($val['input_type']) ? $val['input_type'] : $this['default_type'];
            $c_val = $val[$c_type];
            if (!$c_val) {
                if (isset($val['uploaded']) && $this->isUploadedPath($val['uploaded'])) {
                    $res = fx::files()->getInfo( fx::path($val['uploaded']) );
                }
            } else {
                $res = fx::files()->saveFile($c_val, 'upload');
            }
        }
        return parent::setValue($res);
    }
    /*
    public function getValue() {
        $val = parent::getValue();
        if (!$val) {
            return;
        }
        if (isset($val['uploaded'])) {
            return $val;
        }
            
        $c_type = isset($val['input_type']) ? $val['input_type'] : $this['default_type'];
        $c_val = $val[$c_type];
        if (!$c_val) {
            return null;
        }
        $saved_val = fx::files()->saveFile($c_val, 'upload');
        if ($saved_val) {
            $saved_val['uploaded'] = true;
        }
        $this->setValue($saved_val);
        return $saved_val;
    }
     * 
     */
}