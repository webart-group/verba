<?php
namespace Mod\Cart\Block\Currency;

use Verba\Exception\Building;

class Change extends \Verba\Block\Json
{
    function build()
    {
        /**
         * @var \Mod\Cart $mCart
         */
        $mCart = \Mod\Cart::getInstance();

        $newCur = $mCart->getCart()->currencyChange($this->request->post('id'));
        if (!$newCur || !$newCur instanceof \Verba\Model\Currency) {
            throw new Building($mCart->getCart()->log()->getLastError());
        }

        return $this->content = $newCur->getId();
    }
}
