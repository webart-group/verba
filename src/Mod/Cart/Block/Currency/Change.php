<?php
namespace Verba\Mod\Cart\Block\Currency;

use Verba\Exception\Building;

class Change extends \Verba\Block\Json
{
    function build()
    {
        /**
         * @var \Verba\Mod\Cart $mCart
         */
        $mCart = \Verba\Mod\Cart::getInstance();

        $newCur = $mCart->getCart()->currencyChange($this->request->post('id'));
        if (!$newCur || !$newCur instanceof \Verba\Model\Currency) {
            throw new Building($mCart->getCart()->log()->getLastError());
        }

        return $this->content = $newCur->getId();
    }
}
