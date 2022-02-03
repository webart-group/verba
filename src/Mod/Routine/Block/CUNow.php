<?php
namespace Mod\Routine\Block;

class CUNow extends \Verba\Block\Json
{
    public $data;

    /**
     * @var \Act\AddEdit
     */
    public $ae;

    /**
     * @var \Model
     */
    public $oh;

    /**
     * @var bool|string
     */
    public $responseAs = false;

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

        if (!$this->isRoutedAction(\Act\AddEdit::make_action_sign2($this->ae->getAction(), $this->ae->getIID()))) {
            $this->content = false;
            throw new \Exception\Routing('Bad action param');
        }

        if (!is_array($this->data)) {
            if (isset($_REQUEST['NewObject'][$this->oh->getID()])) {
                $this->data = $_REQUEST['NewObject'][$this->oh->getID()];
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
