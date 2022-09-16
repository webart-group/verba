<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 16.09.19
 * Time: 12:14
 */

namespace Verba\Mod\Otype\Block\ACP\API\Attribute\Form\Inside\Select;


class Init extends \Verba\Mod\Otype\Block\ACP\API\Attribute\Form\Inside\Base
{
    public $templates = array(
        'body' => '/aef/fe/OAttribute/inside/select/body.tpl'
    );

    function build(){

        $this->content =  array(
            'avaible_ots' => array(),
            'assigns' => array(),
            'attr_id' => false,
            'templates' => array(),
        );

        if(!$this->attrData){
            throw new \Exception('Requested Attr not found');
        }

        $_oh = \Verba\_oh($this->attrData['ot_iid']);
        if(!$_oh){
            throw new \Exception('Unable to init attr Oh');
        }
        $A = $_oh->A($this->attrData['attr_code']);
        if(!$A){
            throw new \Exception('Unable to init A');
        }

        $this->content['attr_id'] = $A->getID();

        $descendants = $_oh->getDescendants();

        if(!$descendants){
            $descendants = array($_oh->getID());
        }else{
            array_unshift($descendants, $_oh->getID());
        }

        if(is_array($descendants) && count($descendants)){
            foreach($descendants as $descendant){
                $_d = \Verba\_oh($descendant);
                $this->content['avaible_ots'][$descendant] = array(
                    'code' => $_d->getCode(),
                    'id' => $_d->getID(),
                    'title' => $_d->getTitle(),
                );
            }
        }


        foreach($this->templates as $tplK => $tplPath){
            $this->content['templates'][$tplK] = $this->tpl->getTemplate($tplK);
        }


        $pdSets = $A->PdCollection()->get();
        if(!$pdSets || !count($pdSets)){
            return $this->content;
        }

        foreach($pdSets as $linkedOtId => $Set){
            $_loh = \Verba\_oh($linkedOtId);

            $this->content['assigns'][$linkedOtId] = self::createAssignClientCfg(
                array(
                    'pd_set_id' => $Set->pd_set['id'],
                    'pd_set_title' => $Set->pd_set['title'],
                    'default_value' => $Set->default_value,
                    'attr_id' => $A->getID(),
                    'ot_id' => $linkedOtId,
                    'ot_code' => $_loh->getCode(),
                    'ot_title' => $_loh->getTitle(),
                )
            );
        }

        return $this->content;
    }

    static function createAssignClientCfg($cfg){
        $_pdset = \Verba\_oh('pd_set');

        $r = array(
            'pd_set_id' => null,
            'pd_set_title' => null,
            'default_value' => null,
            'attr_id' => null,
            'ot_id' => null,
            'ot_code' => null,
            'ot_title' => null,
            'listHtml' => null,
        );
        $r = array_replace_recursive($r, $cfg);
        $_predefined = \Verba\_oh('predefined');
        $pdList = $_predefined->initList(array(
            'listId' => 'predefined_for_attr_'.$r['attr_id'].'_ot_'.$r['ot_id'],
            'pot' => array(
                $_pdset->getID() => array($r['pd_set_id'])
            ),
            'cfg' => 'acp/list acp/ots/predefined',
        ));

        $r['listHtml'] = $pdList->generateList();
        return $r;
    }
}
