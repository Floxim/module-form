<?php

namespace Floxim\Form\Field;

use Floxim\Floxim\System\Fx as fx;

class Datetime extends Field {
    public function getParts() {
        //$date_order = fx::lang('date_order');
        $date_order = 'd.m.y, h:i';
        $date_order = str_split($date_order);
        $parts = array(
            'd' => array(
                'max' => 31
            ),
            'm' => array(
                'max' => 12
            ),
            'y' => array(
                'min' => 0,
                'len' => 4, 
                'max' => 3000
            ),
            'h' => array(
                'min' => 0,
                'max' => 23
            ),
            'i' => array(
                'min' => 0,
                'max' => 59
            )
        );
        $parts = fx::collection($parts);
        $v = $this['value'];
        if ($v) {
            $v = fx::date($v, 'U');
            $vals = array(
                'd' => date('d', $v),
                'm' => date('m', $v),
                'y' => date('Y', $v),
                'h' => date('H', $v),
                'i' => date('i')
            );
            $parts->apply(function(&$i, $k) use ($vals) {
                $i['value'] = $vals[$k];
            });
        }
        $res = fx::collection();
        foreach ($date_order as $k) {
            $part = $parts[$k];
            if ($part) {
                $part['name'] = $k;
                $res[]= $part;
            } else {
                $res[]= array(
                    'string' => $k
                );
            }
        }
            
        return $res;
    }
}
