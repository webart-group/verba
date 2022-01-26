<?php

namespace Verba\Model;


class Currency extends Item
{
    protected $otype = 'currency';

    protected $paysys = array(
        'input' => array(),
        'output' => array()
    );

    protected $_confPropsMeta = array(
        'rate' => array('dataType' => 'float'),
        'rate_val' => array('dataType' => 'float'),
        'active' => array('dataType' => 'integer'),
        'id' => array('dataType' => 'integer'),
        'precision' => array('dataType' => 'integer'),
    );

    function packToCart(){
        return array(
            'id' => $this->getId(),
            'active' => $this->active,
            'code' => $this->code,
            'codeNum' => $this->codeNum,
            'title' => (string)$this->getValue('title'),
            'rate' => (float)$this->rate,
            'short' => (string)$this->getValue('short'),
            'scale' => (int)$this->scale,
            'symbol' => (string)$this->getValue('symbol'),
            'rate_val' => (float)$this->rate_val,
            'hidden' => $this->hidden,
        );
    }

    function addPaysysLink($crule, $cpayid, $extLinkData = array())
    {
        if (!$crule || !is_string($crule)) {
            $crule = '';
        }
        if (!$cpayid) {
            return false;
        }
        if (!is_array($extLinkData)) {
            $extLinkData = array();
        }

        if (!array_key_exists($crule, $this->paysys)) {
            $this->paysys[$crule] = array();
        }

        $this->paysys[$crule][$cpayid] = $extLinkData;
    }

    function getPaysysLinkValue($rule, $payId, $linkField = false)
    {
        if (!array_key_exists($rule, $this->paysys)
            || !array_key_exists($payId, $this->paysys[$rule])
        ) {
            return null;
        }
        // если null отправляем все свойства связи
        return $linkField === null
            ? $this->paysys[$rule][$payId]
            // если назваение свойства передано и оно есть в массиве свойств - возвращаем
            // значение из массива свойств
            : (is_string($linkField) && array_key_exists($linkField, $this->paysys[$rule][$payId])
                ? $this->paysys[$rule][$payId][$linkField]
                //  такого поля нет в свойствах связи - false
                : false
            );
    }

    function isPaysysLinkExists($paysysId, $ruleAlias){
        $ruleAlias = !is_string($ruleAlias) ? '' :  $ruleAlias;
        return $paysysId
            && array_key_exists($ruleAlias,$this->paysys)
            && array_key_exists($paysysId, $this->paysys[$ruleAlias]);
    }

    function getPaysysByLinkRule($ruleAlias){
        if(!is_string($ruleAlias) || !array_key_exists($ruleAlias, $this->paysys)){
            return false;
        }
        return $this->paysys[$ruleAlias];
    }

    function round($val){
        return \Verba\reductionToCurrency($val);
    }

    function getPrecision(){
        return $this->data['precision'];
    }

    function toFixed($val){
        return \Verba\reductionToFloat($val, false, $this->data['precision']);
    }
}
