<?php

class shop_ibFormResource extends shop_ibForm
{

    public $prodType = 'resource';
    public $ibformJsClass = 'IBFormResource';

    function prepare()
    {
        parent::prepare();
        $this->addScripts(array(
            'IBFormResource', 'shop/trq'
        ));

        $this->ibformCfg['scale'] = $this->_prod->p('scale');
        $this->ibformCfg['unitSymbol'] = $this->_prod->p('unitSymbol');
        $this->ibformCfg['resSu'] = (string)$this->_prod->p('su');
        $this->ibformCfg['amountMin'] = (int)$this->prodItem->getValue('amountMin');
        $this->ibformCfg['amountMax'] = (int)$this->prodItem->getValue('quantityAvaible');
        if (!$this->ibformCfg['amountMax']) {
            $this->ibformCfg['amountMax'] = null;
        }
        $this->ibformCfg['amountMinWarningMsg'] = \Verba\Lang::get('trq resource amountMin');
        $this->ibformCfg['amountMaxWarningMsg'] = \Verba\Lang::get('trq resource amountMax');

        $this->ibformCfg['msg']['price_info'] = \Verba\Lang::get('trq resource price_info');
    }

    function modifyTformDcfg()
    {
        $amountMin = (int)$this->prodItem->getValue('amountMin');
        if ($amountMin) {
            $this->tform_dcfg['fields']['amount'] = array(
                'value' => $amountMin,
            );
        }
    }
}
