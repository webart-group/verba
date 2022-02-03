<?php

use \Act\Form;

class shop_ibForm extends \Verba\Block\Html
{

    public $prodType = 'unique';
    public $ibformJsClass = 'IBForm';

    public $templates = array(
        'content' => '/shop/ibform/wrap.tpl',
        'paysysSelector' => '/shop/paymentSelector/select.tpl',
    );

    public $css = array(
        'ibform',
        array('paysys-selector')
    );

    public $scripts = array(
        array('paysysSelector ibform', 'shop')
    );

    public $prodItem;
    /**
     * @var \Model
     */
    public $_prod;
    public $service;
    public $Store;

    public $trq_id;
    /**
     * @var \Model
     */
    public $_trq;
    // config from Ui
    public $tform_cfg;

    public $tform_dcfg = array(
        'fields' => array(),
    );

    public $tplvars = array(
        'PRODUCT_AGENT' => '',
        'TFORM' => '',
        'PAYMENT_SELECTOR' => '',
    );

    public $ibformCfg = array(
        'msg' => array()
    );

    public $prevTrq = array();

    function prepare()
    {
        $this->trq_id = isset($this->service->config['groups']['tform']['ot_id'])
            ? $this->service->config['groups']['tform']['ot_id']
            : false;

        if ($this->trq_id) {
            $this->_trq = \Verba\_oh($this->trq_id);
            $this->tform_cfg = isset($this->service->config['groups']['tform'])
                ? $this->service->config['groups']['tform']
                : false;
        }
        $U = User();
        if ($U->getAuthorized() && $this->_trq) {
            $qm = new \Verba\QueryMaker($this->_trq, false, true);
            $qm->addWhere($U->getID(), $this->_prod->getOwnerAttributeCode());
            $qm->addWhere($this->_prod->getID(), 'parentOt');
            $qm->addOrder(array($this->_trq->getPAC() => 'd'));
            $qm->addLimit(1);
            $sqlr = $qm->run();
            if ($sqlr && $sqlr->getNumRows()) {
                $this->prevTrq = new \Model\Item($sqlr->fetchRow());
            }
        }
    }

    function modifyTformDcfg()
    {

    }

    function parseTform()
    {

        if (!$this->trq_id || empty($this->tform_cfg)) {
            return '';
        }

        $this->modifyTformDcfg();

        \Verba\Hive::loadFormMakerClass();
        Form::extendCfgByUiConfigurator($this->_trq,
            $this->tform_dcfg, $this->tform_cfg, $this->prevTrq);

        if (!isset($this->tform_dcfg['class'])) {
            $this->tform_dcfg['class'] = '';
        }
        $this->tform_dcfg['class'] .= 'tform trq-' . $this->prodType . ' ' . $this->_prod->getCode();

        $form = $this->_trq->initForm(array(
            'action' => 'new',
            'cfg' => 'public public/tforms/tform public/tforms/tform_' . $this->prodType . ' public/tforms/prods/' . $this->_trq->getCode(),
            'dcfg' => $this->tform_dcfg,
            'block' => $this,
        ));

        $this->addCss('trq-' . $this->prodType, 'trq');

        $form->addExtendedData(array('prodItem' => $this->prodItem));

        $formHtml = $form->makeForm();

        return $formHtml;
    }

    function parseProductAgent()
    {
        $b = new \product_agent($this, array(
            'prodItem' => $this->prodItem,
            'catalog' => $this->service,
        ));

        $b->prepare();
        $b->build();

        $this->mergeHtmlIncludes($b);

        return $b->content;
    }

    function build()
    {
        try {
            $this->tpl->assign(array(
                'TFORM' => $this->parseTform(),
                'PRODUCT_AGENT' => $this->parseProductAgent(),
            ));
            $this->tpl->parse('PAYMENT_SELECTOR', 'paysysSelector');

            $psBlock = new \shop_paymentSelector($this, array(
                'Store' => $this->Store,
                'currency' => \Verba\_mod('Cart')->getCurrency()
            ));
            $psBlock->prepare();
            $this->mergeHtmlIncludes($psBlock);

            $this->ibformCfg['ot_id'] = $this->prodItem->oh->getID();
            $this->ibformCfg['id'] = $this->prodItem->iid;

            $this->tpl->assign(array(
                'PAYMENT_SELECTOR' => $psBlock->tpl()->getVar('PAYMENT_SELECTOR'),
                'PSYS_SELECTOR_CFG' => $psBlock->tpl()->getVar('PSYS_SELECTOR_CFG'),
                'PROD_OT_ID' => $this->prodItem->oh->getID(),
                'PROD_ID' => $this->prodItem->iid,
                'IBFORMCLASS' => $this->ibformJsClass,
                'IBFORM_CFG' => json_encode($this->ibformCfg)
            ));
            //$this->tpl->assign(array();
            $this->content = $this->tpl->parse(false, 'content');
        } catch ( \Verba\Exception\Building $e) {
            $this->log()->error($e);
            $this->content = '';
        }

        return $this->content;
    }
}
