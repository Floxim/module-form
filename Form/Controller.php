<?php
namespace Floxim\Form\Form;

use Floxim\Floxim\System\Fx as fx;

class Controller extends \Floxim\Floxim\Component\Basic\Controller 
{
    public function doForm()
    {
        $form_id = $this->getParam('form_id');
        if (!$form_id) {
            // some fake data?
            return;
        }
        $form = fx::data('floxim.form.form')->with('fields')->where('id', $form_id)->one();
        if ($form->isSent() && !$form->hasErrors()) {
            $form->finish();
        }
        $this->assign('form', $form);
    }
    
    public function getAvailForms()
    {
        $forms = $this->getFinder()->all();
        $res = $forms->getValues(function($f) {
            return array(
                'id' => $f['id'],
                'name' => $f['name']
            );
        });
        $res []= array(
            'id' => '',
            'name' => ' - создать новую - '
        );
        return $res;
    }
    
    public function install(\Floxim\Floxim\Component\Infoblock\Entity $ib, $ctr, $params)
    {
        if (!isset($params['form_id']) || !$params['form_id']) {
            $form = fx::data('floxim.form.form')->create(array('name' => 'My new form'));
            $form->save();
            $ib->digSet('params.form_id', $form['id']);
            $ib->save();
            fx::log('ib savd', $ib, $params, $form);
        }
    }
}