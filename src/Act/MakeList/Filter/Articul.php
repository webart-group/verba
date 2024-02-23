<?php

namespace Verba\Act\MakeList\Filter;

class Articul extends \Verba\Act\MakeList\Filter
{
    public $captionLangKey = 'products acp list filters articul';
    public $ftype = 'articul-iid';
    public $name = 'artiid';
    public $attrs = array(); //see ->init()

    function init()
    {
        $this->attrs = array(
            'articul', $this->oh->getPAC()
        );
    }

    function applyValue()
    {
        $wgAlias = $this->makeWhereAlias();
        $this->list->QM()->removeWhere($wgAlias);
        $GW = $this->list->QM()->addWhereGroup($wgAlias);
        $withoutVariants = $this->list->QM()->getWhere('whr_ProdId_Is_Not_Null');
        if ($this->value) {
            if ($withoutVariants) {
                $this->list->QM()->removeWhere('whr_ProdId_Is_Not_Null');
            }
            foreach ($this->attrs as $cAttr) {
                $A = $this->oh->A($cAttr);
                if ($A->isLcd()) {
                    $cAttr = $cAttr . '_' . SYS_LOCALE;
                }
                $GW->addWhere('%' . $this->value . '%', $wgAlias . '_' . $cAttr, $cAttr, false, 'LIKE', '||');
            }
        }
    }

    function build()
    {
        if (!count($this->attrs)) {
            return '';
        }
        $this->tpl->clear_tpl(array_keys($this->templates));
        $this->tpl->define($this->templates);

        $this->E->setValue($this->value);

        $this->tpl->assign(array(
            'FILTER_ELEMENT' => $this->E->build()
        ));

        return $this->tpl->parse(false, 'content');
    }

    function asJson()
    {
        $r = parent::asJson();
        if (count($this->attrs)) {
            $r['attrs'] = [];
            foreach ($this->attrs as $attr){
                $r['attrs'][] = $attr;
            }
            return $r;
        }

        return $r;
    }
}
