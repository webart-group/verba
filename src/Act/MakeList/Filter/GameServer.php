<?php
namespace Verba\Act\MakeList\Filter;

use \Verba\Act\Form\Element\ForeignSelectPlusParentsGameServers;

class GameServer extends \Verba\Act\MakeList\Filter
{

    function initE()
    {
        \Verba\Hive::loadFormMakerClass();

        $this->E = new ForeignSelectPlusParentsGameServers($this->ecfg, false, $this->name, $this->C);
    }

    function build()
    {
        $this->tpl->clear_tpl(array_keys($this->templates));
        $this->tpl->define($this->templates);

        if ($this->value != null) {
            $this->E->setValue($this->value);
        }
        $this->E->setBlankOption('', \Verba\Lang::get('game placeholders server'));

        $this->tpl->assign(array(
            'FILTER_ELEMENT' => $this->E->build(),
        ));
        return $this->tpl->parse(false, 'content');
    }
}
