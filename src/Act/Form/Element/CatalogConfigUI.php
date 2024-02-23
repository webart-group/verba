<?php

namespace Verba\Act\Form\Element;

use \Verba\Html\Element;

class CatalogConfigUI extends Element
{
    public $templates = array(
        'body' => 'aef/fe/catalogconfigui/wrap.tpl',
        'group' => 'aef/fe/catalogconfigui/group.tpl',
        'item_default' => 'aef/fe/catalogconfigui/item_default.tpl',
        'item_worker' => 'aef/fe/catalogconfigui/item_worker.tpl',
        'item_filter' => 'aef/fe/catalogconfigui/item_filter.tpl',
        'item_filter_types' => 'aef/fe/catalogconfigui/item_filter_types.tpl',
        'item_form_field' => 'aef/fe/catalogconfigui/item_form_field.tpl',
        'item_trq_form_field' => 'aef/fe/catalogconfigui/item_trq_form_field.tpl',
        'item_attr' => 'aef/fe/catalogconfigui/item_attr.tpl',
    );

    public $groups = array();
    protected $_group_default = array(
        'itemClassSuffix' => null,
        'title' => null,
        'ot_id' => null,
        'ot_title' => null,
        'ot_selector' => '',
        'items' => array(),
    );

    public $avaible_ot = array(
        'role' => 'public_product',
        'bloankOption' => '',
    );

    public $foreign_ot_selector = '';

    public $attrToOt = false;
    // брать текущий выбранный от от первой группы
    // или группы с указанным кодом
    // __0 | <group_code>
    public $otFromGroup = false;

    function setGroups($groups)
    {
        if (!is_array($groups) || !count($groups)) {
            return false;
        }

        foreach ($groups as $gid => $gdata) {
            $this->groups[$gid] = array_replace_recursive(
                $this->_group_default, $gdata
            );
            if ($this->groups[$gid]['ot_id']) {
                $goh = \Verba\_oh($this->groups[$gid]['ot_id']);
                $this->groups[$gid]['ot_title'] = $goh->getTitle();
            } else {
                $this->groups[$gid]['ot_id'] = null;
            }
        }
    }

    function makeE()
    {
        $this->fire('makeE');
        $cot = '';
        if ($this->aef()->getAction() == 'edit') {
            $existsCfg = $this->aef()->getExistsValue('config');

            if (is_string($existsCfg) && !empty($existsCfg)) {
                $existsCfg = unserialize($existsCfg);
            }

            if ($this->attrToOt) {
                $cot = $this->aef()->getExistsValue('itemsOtId');
            } elseif (is_string($this->otFromGroup) && !empty($this->otFromGroup)) {
                $gcode = $this->otFromGroup == '__0' ? key($existsCfg['groups']) : $this->otFromGroup;
                $cot = array_key_exists($gcode, $existsCfg['groups'])
                && is_array($existsCfg['groups'][$gcode])
                && array_key_exists('ot_id', $existsCfg['groups'][$gcode]) && !empty($existsCfg['groups'][$gcode]['ot_id'])
                    ? $existsCfg['groups'][$gcode]['ot_id']
                    : false;
            }

            if ($cot) {
                $coh = \Verba\_oh($cot);
                $cot = $coh->getID();
            }
        } else {
            $existsCfg = array();
        }

        $jsCfg = array(
            'groups' => $this->groups,
            'attrs' => array(),
            'acode' => $this->acode,
            'ot' => $cot,
            'newObjectPrefix' => $this->getName(),
            'foreign_ot_selector' => $this->foreign_ot_selector,
        );

        if (!$this->foreign_ot_selector) {
            $otvalues = array();
            $_otype = \Verba\_oh('otype');
            $qm = new \Verba\QueryMaker($_otype, false, array('ot_code', 'title'));
            $qm->addWhere($this->avaible_ot['role'], 'role');
            $sqlr = $qm->run();
            $pac = $_otype->getPAC();
            if ($sqlr && $sqlr->getNumRows()) {
                while ($row = $sqlr->fetchRow()) {
                    $otvalues[$row[$pac]] = $row['title'] . ' (' . $row['ot_code'] . ')';
                }
            }

            $fe = new \Verba\Html\Select();
            $fe->setBlankoption('', $this->avaible_ot['blankOption']);
            $fe->setId('');
            $fe->setName('');
            $fe->setValues($otvalues);
            if (isset($coh)) {
                $fe->setValue($coh->getID());
            }
            $otSelectorHtml = $fe->build();
        } else {
            $otSelectorHtml = '';
        }

        if (is_array($existsCfg) && array_key_exists('groups', $existsCfg)
            && !empty($existsCfg['groups']) && is_array($existsCfg['groups'])) {

            foreach ($existsCfg['groups'] as $grpCode => $grpData) {

                if (!array_key_exists($grpCode, $this->groups)) {
                    continue;
                }

                $g_ot_id = isset($grpData['ot_id']) && $grpData['ot_id']
                    ? $grpData['ot_id']
                    : ($cot ? $cot : false);

                if (!$g_ot_id) {
                    continue;
                }

//        if($g_ot_id != $cot){
//          $g_ot_id = $cot;
//          if(is_array($grpData['items']) && count($grpData['items'])){
//            foreach($grpData['items'] as $icode => $icfg){
//              if(!$coh->isA($icfg['code'])){
//                unset($grpData['items'][$icode]);
//              }
//            }
//          }
//        }

                $g_oh = \Verba\_oh($g_ot_id);

                $cfgDescArray = array_key_exists($grpCode, $this->groups)
                    ? $this->groups[$grpCode]
                    : array();
                $jsCfg['groups'][$grpCode] = array_replace_recursive($this->_group_default, $cfgDescArray, $grpData);
                $jsCfg['groups'][$grpCode]['ot_id'] = $g_oh->getID();
                $jsCfg['groups'][$grpCode]['ot_title'] = $g_oh->getTitle();
                $jsCfg['groups'][$grpCode]['itemClassSuffix'] = $cfgDescArray['itemClassSuffix'];
            }
        }

        $this->tpl->assign(array(
            'CCUI_ID' => $this->aef()->getId() . '_ccui',
            'CCUI_OT_SELECT' => $otSelectorHtml,
            'CCUI_JS_CFG' => json_encode($jsCfg, JSON_FORCE_OBJECT),
        ));
        $this->tpl->clear_tpl(array_keys($this->templates));
        $this->tpl->define($this->templates);

        $this->tpl->assign(array('FILTER_TYPES' => $this->tpl->getTemplate('item_filter_types')));

        // items  templates
        $this->tpl->assign(array(
            'ITEM_DEFAULT' => $this->tpl->getTemplate('item_default'),
            'ITEM_WORKER' => $this->tpl->getTemplate('item_worker'),
            'ITEM_FORM_FIELD' => $this->tpl->getTemplate('item_form_field'),
            'ITEM_TRQ_FORM_FIELD' => $this->tpl->getTemplate('item_trq_form_field'),
            'ITEM_FILTER' => $this->tpl->parse(false, 'item_filter'),
            'ITEM_ATTRZ' => $this->tpl->parse(false, 'item_attr'),
            'GROUP' => $this->tpl->getTemplate('group')
        ));

        $this->setE($this->tpl->parse(false, 'body'));
        $this->fire('makeEFinalize');
    }
}
