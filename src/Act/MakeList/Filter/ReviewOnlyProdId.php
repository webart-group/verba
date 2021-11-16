<?php

namespace Verba\Act\MakeList\Filter;

class ReviewOnlyProdId extends \Verba\Act\MakeList\Filter
{

    public $captionLangKey = 'review list filters onlyProdId';
    public $name = 'onlyProdId';
    public $felement = '\Html\Hidden';
    public $templates = array(
        'content' => 'review/product/list/filters/only_prod_id.tpl',
    );

    function init()
    {
        $prodItem = $this->list->getExtendedData('prodItem');
        if (!$prodItem || !$prodItem instanceof \Verba\Model\Item) {
            $this->hidden = true;
            $this->disabled = true;
        }
    }

    function applyValue()
    {
        $wgAlias = $this->makeWhereAlias();
        $this->list->QM()->removeWhere($wgAlias);

        $prodItem = $this->list->getExtendedData('prodItem');
        if (!is_object($prodItem) || !$prodItem instanceof \Verba\Model\Item) {
            return;
        }
        if ($this->value != 1) {
            return;
        }

        $GW = $this->list->QM()->addWhereGroup($wgAlias);

        $GW->addWhere($prodItem->getId(), $wgAlias . '_prodId', 'prodId');
        $GW->addWhere($prodItem->getOtId(), $wgAlias . '_prodOt', 'prodOt');

    }

    function build()
    {

        $this->E->listen('makeEFinalize', 'attachCustomCode', $this);
        $this->value = (int)(bool)$this->value;

        $this->E->setValue($this->value);

        return $this->E->build();
    }

    function attachCustomCode()
    {

        $this->tpl->clear_tpl(array_keys($this->templates));
        $this->tpl->define($this->templates);

        $FE = $this->E->getE();

        $this->tpl->assign(array(
            'FILTER_ID' => $this->getId(),
            'FILTER_CAPTION' => $this->makeCaption(),
            'FILTER_ELEMENT_NAME' => $this->E->getName(),
        ));
        $this->E->setE($FE . $this->tpl->parse(false, 'content'));
    }
}

?>