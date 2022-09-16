<?php
namespace Verba\Mod\Order\Act\MakeList\Worker;

use \Verba\Act\MakeList\Worker;

class AcpQueryModifier extends Worker
{
    function init()
    {
        $this->parent->listen('beforeQuery', 'doChanges', $this);
    }

    function doChanges()
    {
        $store = \Verba\_oh('store');

        /**
         * @var $Qm \Verba\QueryMaker
         */
        $Qm = $this->parent->QM();
        list($a) = $Qm->createAlias($store->vltT());
        $Qm->addSelectPastFrom('picture', $a, 'store_picture');
    }
}
