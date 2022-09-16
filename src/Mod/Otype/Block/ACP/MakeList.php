<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 15.09.19
 * Time: 19:00
 */

namespace Verba\Mod\Otype\Block\ACP;

class MakeList extends \Verba\Mod\Routine\Block\MakeList {

    public $dcfg = array(
        );

    public $prod = 0;

    function prepare() {

        parent::prepare();

        if($this->prod) {
            list($a) = $this->list->QM()->createAlias();
            $this->list->QM()->addWhere('`' . $a . "`.`role` IN('public_product', 'public_product_base')");
        }
    }
}