<?php
namespace Verba\Mod\Routine\Block;

class CUNow extends \Verba\Block\Json
{
    public $data;

    /**
     * @var \Verba\Act\AddEdit
     */
    public $ae;

    /**
     * @var \Verba\Model
     */
    public $oh;

    /**
     * @var bool|string
     */
    public $responseAs = 'data';

    public $responseAsKeys;

    use Common;

    function routedActions(){
        return [
            'create' => true,
            'update' => true,
        ];
    }

    function prepare()
    {
        parent::prepare();

        $this->oh = \Verba\_oh($this->rq->ot_id);

        $cfg = $this->rq->asArray();
        $cfg['responseAs'] = $this->responseAs;
        if (is_array($this->responseAsKeys)) {
            $cfg['responseAsKeys'] = $this->responseAsKeys;
        }

        $this->ae = $this->oh->initAddEdit($cfg);

        if (!$this->isRoutedAction(\Verba\Act\AddEdit::make_action_sign2($this->ae->getAction(), $this->ae->getIID()))) {
            $this->content = false;
            throw new \Verba\Exception\Routing('Bad action param');
        }

        if (!is_array($this->data)) {
            $this->data = $this->rq->post();
            if (isset($this->data['NewObject'][$this->oh->getID()])) {
                $this->data = $this->data['NewObject'][$this->oh->getID()];
            }
        }
    }

    function build()
    {
        if (is_array($this->data)) {
            $this->ae->setGettedData($this->data);
        }
        try{
            $iid = $this->ae->addedit_object();
        }catch(\Exception $e){

            throw $e;

        }


        return $this->content = $this->ae->getResponseByFormat();
    }

    function setResponseAs($val)
    {
        if (!is_string($val) || !$val) {
            return false;
        }
        $this->responseAs = strtolower($val);
        return $this->responseAs;
    }

    function getResponseAs()
    {
        return $this->responseAs;
    }

    function setResponseAsKeys($val)
    {
        if (!is_array($val)) {
            return false;
        }
        $this->responseAsKeys = $val;
        return $this->responseAsKeys;
    }
}
