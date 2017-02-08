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
            //return;
            $form = $this->getDefaultForm();
        } else {
            $form = fx::data('floxim.form.form')->with('fields')->with('messages')->where('id', $form_id)->one();
        }
        if (!$form) {
            return;
        }
        
        $form = $this->ajaxForm($form);
        
        if ($form->isSent() && !$form->hasErrors()) {
            $lead = fx::data('floxim.form.lead')->create(
                array(
                    'created' => time(),
                    'form' => $form
                )
            );
            foreach ($form->getInputs() as $field) {
                $lead_prop = fx::data('floxim.form.lead_prop')->create(
                    array(
                        'field_name' => $field['label'],
                        'field' => $field,
                        'value' => $field->getValue()
                    )
                );
                $lead['props'] []= $lead_prop;
            }
            $lead->save();
            $mailer = $this->getMailer($form);
            
            $mailer->send();
            
            $form->finish();
        }
        $this->assign('form', $form);
    }
    
    protected function getMailer($form)
    {
        $m = fx::mail();
        $from_addr = fx::config('mail.from_address');
        $from_name = fx::config('mail.from_name');
        $m->from($from_addr, $from_name);
        
        $user = fx::data('floxim.user.user')->where('is_admin', 1)->one();
        
        $m->to($user['email']);
        
        $m->subject('Сообщение формы «'.$form['name'].'»');
        
        $show_fields = function($fields) {
            $res = '<table style="border-collapse:collapse;">';
            foreach ($fields as $name => $val) {
                $res .= '<tr>'.
                    '<td style="vertical-align:top; border:1px solid #CCC; padding:3px 10px;">'.
                        '<b>'.$name.':</b>'.
                    '</td>'.
                    '<td style="vertical-align:top; border:1px solid #CCC; padding:3px 10px;">'.
                        nl2br($val).
                    '</td>'.
                '</tr>';
            }
            $res .= '</table>';
            return $res;
        };
        
        $msg = '<h2>Новое сообщение</h2>';
        
        $fields = array();
        foreach ($form->getInputs() as $field) {
            $fields[$field['label']] = strip_tags($field->getValue());
        }
        
        $msg .= $show_fields($fields);
        
        $msg .= '<h3>Технические подробности</h3>';
        
        $page = fx::env('page');
        $extras = array(
            'Форма' => $form['name'],
            'IP' => $_SERVER['REMOTE_ADDR'],
            'Страница' => '<a href="http://'.$_SERVER['HTTP_HOST'].$page['url'].'" target="_blank">'.$page['name'].'</a>',
            'Дата и время' => date('d.m.Y, H:i')
        );
        
        $msg .= $show_fields($extras);
        
        $m->message($msg);
        
        return $m;
    }
    
    public function getActionSettings($action) {
        return array(
            'form_id' => array(
                'type' => 'livesearch',
                'label' => 'Форма',
                'values' => $this->getAvailForms()
            )
        );
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
    
    protected  function getDefaultForm()
    {
        $form = fx::data('floxim.form.form')->create(array('name' => ''));
        $fields = [
            [
                'type' => 'text',
                'label' => 'Ваше имя'
            ],
            [
                'type'=> 'text',
                'label' => 'E-mail'
            ],
            [
                'type'=> 'textarea',
                'rows' => 5,
                'label' => 'Сообщение'
            ],
            [
                'type' => 'button',
                'label' => 'Отправить'
            ]
        ];
        $form->addFields($fields);
        return $form;
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