<?php
namespace Mod\Game\Act\MakeList\Handler;

class DefaultHandler extends \Act\MakeList\Handler\Field {

    static $c = 0;

    function handle(){
        self::$c++;
        $tpl = $this->list->tpl();
        $oh = \Verba\_oh($this->list->row['ot_id']);
        $url = \Mod\Seo::idToSeoStr($this->list->row, array('seq' => $this->list->getCurrentPos(), 'slID' => $this->list->getID()));
        $iid = $this->list->row[$oh->getPAC()];
        $cur = \Verba\_mod('cart')->getCurrency();

        $tpl->assign(array(
            'ITEM_CURRENCY_SHORT' => $cur->p('short'),
        ));

        $tpl->assign(array(
            'ITEM_PAGE_URL' => $url,
            'ITEM_PRICE_UNIT' => $cur->p('short'),
            'ITEM_OT' => $this->list->row['ot_id'],
            'ITEM_ID' => $iid,
        ));
    }

    function handleStartAt(){
        $startat = strtotime($this->list->row['startat']);
        if(!$startat){
            $this->list->fieldCfg['content_tpl_hash'] = false;
            return '';
        }
        $this->list->tpl()->assign(array(
            'ITEM_STARTAT_DATE' => date("d/m/y", $startat),
            'ITEM_STARTAT_TIME' => date("H:i", $startat),
        ));

        $r = $this->list->tpl()->parse(false, $this->list->fieldCfg['content_tpl_hash']);

        $this->list->fieldCfg['content_tpl_hash'] = false;

        return $r;
    }

}
