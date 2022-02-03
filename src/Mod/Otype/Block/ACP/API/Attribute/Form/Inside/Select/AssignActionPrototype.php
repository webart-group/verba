<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 16.09.19
 * Time: 12:17
 */

namespace Mod\Otype\Block\ACP\API\Attribute\Form\Inside\Select;


class AssignActionPrototype extends \Verba\Block\Json{

    protected $pd_set_id;
    protected $ot_id;
    protected $attr_id;
    /**
     * @var \Model
     */
    protected $_attr;
    /**
     * @var \Model
     */
    protected $_oh;
    /**
     * @var \ObjectType\Attribute
     */
    protected $A;
    /**
     * @var \Model
     */
    protected $_pdset;
    protected $pdSetData;

    function prepare(){

        $this->pd_set_id = $this->rq->getParam('pdset_id');
        $this->ot_id = $this->rq->getParam('rule');
        $this->attr_id  = $this->rq->getParam('attr_id');
        $this->_attr = \Verba\_oh('ot_attribute');

        $this->_oh = \Verba\_oh($this->ot_id);
        if(!$this->_oh){
            throw  new \Verba\Exception\Building('Bad ot');
        }

        $this->A = $this->_oh->A($this->attr_id);
        if(!$this->A || !$this->A->isPredefined()){
            throw  new \Verba\Exception\Building('Bad A');
        }

        $this->_pdset = \Verba\_oh('pd_set');
        if(!is_numeric($this->pd_set_id)){
            throw  new \Verba\Exception\Building('Bad pd_set');
        }
        if($this->pd_set_id == -1){
            $aes = $this->_pdset->initAddEdit(array('action' => 'new'));
            $aes->setGettedObjectData(array(
                'title' => 'New Set',
                'active' => 1,
            ));
            $set_iid = $aes->addedit_object();
            if(!$set_iid){
                throw new \Exception('Unable to create new Pd_set. Info: '.$aes->log()->getMessagesAsStr('error'));
            }
            $this->pdSetData = $aes->getActualData();
        }else{
            $this->pdSetData = $this->_pdset->getData($this->pd_set_id, 1);
        }
        if(!$this->pdSetData){
            throw new \Exception('Pdset Data not found');
        }
    }

}