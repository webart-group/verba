<?php

namespace Verba\Act\Form\Element\OType;

use \Verba\Act\Form\Element\Select;

class Attribute extends Select
{
    protected $inlineExtensions = array();

    function loadValues()
    {

        $fe = \Verba\Mod\Otype::getInstance()->gC('avaibleFormElements');
        $translate = \Verba\Lang::get('oattribute avaibleFormElements');
        $r = array();
        foreach ($fe as $fe_code => $fe_cfg) {
            $r[$fe_code] = array_key_exists($fe_code, $translate)
                ? $translate[$fe_code]
                : $fe_code;
        }

        return $r;
    }

    function makeE()
    {

        $ftype = $this->getValue();

        if (strpos($ftype, '.') !== false) {
            $this->inlineExtensions = explode('.', $ftype);
            $ftype = array_shift($this->inlineExtensions);
            // переустанавливаем чистое значение элемента без .extension1.extension2
            $this->setValue($ftype);
        }

        parent::makeE();
        /**
         * @var $form AddEditForm
         */
        $form = $this->aef();

        $action = $form->getAction();

        if (!$ftype) {
            $ftype = 'default';
        }
        $ahs = array();
        if ($action == 'edit') {
            $_oh = \Verba\_oh($form->getExistsValue('ot_iid'));
            $A = $_oh->A($form->getIID());
            if ($A instanceof \Verba\ObjectType\Attribute) {

                $ahs = $A->getHandlers();

                if (is_array($this->inlineExtensions) && !empty($this->inlineExtensions)) {
                    if (!array_key_exists('form', $ahs)) {
                        $ahs['form'] = array();
                    }
                    $iei = 0;
                    foreach ($this->inlineExtensions as $iename) {
                        $ahs['form']['inline_form_' . $iei++] = array(
                            'priority' => '0',
                            'ah_id' => '0',
                            'set_id' => '0',
                            'logic' => '0',
                            'ah_name' => $iename,
                            'ah_type' => 7,
                            'params' =>
                                array(),
                            'ah_type_name' => 'form',
                        );
                    }
                }

                if (is_array($ahs) && count($ahs)) {
                    foreach ($ahs as $ah_type => $ah_sets) {
                        foreach ($ah_sets as $ah_set_id => $ah_set_data) {
                            $ahs[$ah_set_id] = $ah_set_data;
                            unset(
                                $ahs[$ah_set_id]['set_id'],
                                $ahs[$ah_set_id]['set_type_name']
                            );
                        }
                    }
                }
            }
        }
        /**
         * @var $mOtype \Verba\Mod\Otype
         */
        $mOtype = \Verba\Mod\Otype::getInstance();
        $workerCfg = array(
            '_className' => 'AcpOAttrUI',
            'OAttrUICfg' => array(
                'action' => $action,
                'ot_id' => $form->oh->getID(),
                'iid' => $action == 'edit' ? $form->getIID() : false,
                'fe' => $ftype,
                'form_prefix' => $form->getFormId(),
                'E' => array(
                    'form' => 'form#' . $form->getFormId(),
                    'fe' => $this->getID(),
                ),
                'dts' => $mOtype->getJsDataTypes(),
                'fes' => $mOtype->getJsFormElements(),
                'ahs' => $ahs,
                'ah_types' => $mOtype->getOAttrAhsTypes(),
            )
        );

        $form->addWorker('AcpOAttrUI', 'AcpOAttrUI', $workerCfg);
    }
}
