<?php

namespace Verba\Act\AddEdit\Handler\Around\Catalog;

use \Verba\Act\AddEdit\Handler\Around;

class ConfigUI extends Around
{
    function run()
    {

        if($this->value === null){
            return $this->value;
        }

        if($this->action == 'edit'){
            $cfg = unserialize($this->getExistsValue($this->A->getId()));
            if($this->ah->getTempValue('itemsOtId') == null){
                $catOtId = $this->getExistsValue('itemsOtId');
            }else{
                if($this->ah->getTempValue('itemsOtId') > 0){
                    $catOtId = $this->ah->getTempValue('itemsOtId');
                }else{
                    $catOtId = false;
                }
            }
        }
        if(!$cfg){
            $cfg = array(
                'groups' => array(),
            );
        }

        if($this->value['groups']){
            $itemMtdHandlerDefault = 'Item';

            foreach($this->value['groups'] as $gcode => $gdata){

                if($gdata['ot_id'] && \Verba\isOt($gdata['ot_id'])) {
                    $g_ot_id = $gdata['ot_id'];
                }elseif($catOtId){
                    $g_ot_id = $catOtId;
                }else{
                    continue;
                }

                $g_oh = \Verba\_oh($g_ot_id);

                if(!isset($cfg['groups'][$gcode])){
                    $cfg['groups'][$gcode] = array(
                        'itemClassSuffix' => $gdata['itemClassSuffix'],
                    );
                }
                $cfg['groups'][$gcode]['items'] = array();
                $cfg['groups'][$gcode]['ot_id'] = $g_ot_id;

                $cstmMethod = isset($gdata['itemClassSuffix'])
                && !empty($gdata['itemClassSuffix'])
                    ? $itemMtdHandlerDefault.ucfirst($gdata['itemClassSuffix'])
                    : false;

                $itemMtdHandler = $cstmMethod && method_exists($this, $cstmMethod)
                    ? $cstmMethod
                    : $itemMtdHandlerDefault;

                if(isset($gdata['items']) && is_array($gdata['items'])) {
                    foreach($gdata['items'] as $aidx => $adata){
                        $this->A = $g_oh->A($adata['id']);
                        $cfg['groups'][$gcode]['items'][] = $this->$itemMtdHandler($adata, $gdata);
                    }
                }
                //sort by priority
                usort($cfg['groups'][$gcode]['items'], '\Verba\sortByPriorityAsArrayDesc');
                $cfg['groups'][$gcode]['items'] = array_values($cfg['groups'][$gcode]['items']);
            }

        }

        if(isset($cfg['ot'])){
            unset($cfg['ot']);
        }
        \Verba\_mod('system')->planeClearCache();
        return $cfg;
    }

    function Item($item, $group)
    {
        if($this->A){
            $marray = array(
                'id' => $this->A->getId(),
                'code' => $this->A->getCode(),
            );
        }else{
            $marray = array();
        }
        return array_merge($item, $marray);
    }

    function ItemFilter($item, $group)
    {
        $mProduct = \Verba\_mod('product');
        $r = $this->Item($item, $group);
        if(!array_key_exists('filtertype',$item)){
            $r['filtertype'] = $mProduct->getProductGroupAttrFilterType($item, $this->A);
        }

        return $r;
    }

    function ItemFormField($item, $group)
    {
        $r = $this->Item($item, $group);
        $r['formElement'] = isset($item['formElement']) && !empty($item['formElement'])
            ? $item['formElement'] : "";

        $r['rememberPrev'] = isset($item['rememberPrev'])
            ? ((int)(bool)$item['rememberPrev'])
            : 0;
        return $r;
    }

    function ItemTrqFormField($item, $group)
    {
        $r = $this->ItemFormField($item, $group);

        $r['showInOrder'] = isset($item['showInOrder']) && !empty($item['showInOrder'])
            ? ((int)(bool)$item['showInOrder'])
            : 0;

        return $r;
    }
}
