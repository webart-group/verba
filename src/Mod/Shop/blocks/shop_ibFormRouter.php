<?php

class shop_ibFormRouter extends \Verba\Block
{

    public $prodItem;
    public $_prod;
    public $service;
    public $Store;

    function route()
    {

        if (!$this->service->itemsOtId) {
            throw new \Verba\Exception\Routing();
        }
        $oh = \Verba\_oh($this->service->itemsOtId);

        $rq = $this->rq->asArray();

        $cfg['prodItem'] = $this->prodItem;
        $cfg['_prod'] = $this->_prod;
        $cfg['service'] = $this->service;
        $cfg['Store'] = $this->Store;

        if ($oh instanceof \Verba\Model\Product\Resource) {
            $h = new shop_ibFormResource($rq, $cfg);
        } elseif ($oh instanceof \Verba\Model\Product\Multi) {
            $h = new shop_ibFormMulti($rq, $cfg);
        } else { //$oh instanceof ot_prodUniq or unknown
            $h = new shop_ibFormUnique($rq, $cfg);
        }
        return $h->route();

    }
}
