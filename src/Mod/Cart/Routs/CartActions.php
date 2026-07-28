<?php
namespace Verba\Mod\Cart\Routs;

use Verba\Mod\Cart\Actions\CartClear;
use Verba\Mod\Cart\Actions\CheckItemsAvaibility;
use Verba\Mod\Cart\Actions\CurrencyChange;
use Verba\Mod\Cart\Actions\SwitchUserByEmail;

class CartActions extends \Verba\Block\Html
{
    function route()
    {
        switch ($this->request->uf[0]) {
            case 'clear':
                $h = new CartClear($this);
                break;
            case 'currencychange':
                $this->request->ot_id = \Verba\_oh('currency')->getID();
                $h = new CurrencyChange($this);
                break;
            case 'checkcustomer':
                $h = new SwitchUserByEmail($this);
                break;
            case 'checkitemsavaibility':
                $confirmItems = isset($_REQUEST['confirmItems']) && is_array($_REQUEST['confirmItems'])
                    ? $_REQUEST['confirmItems']
                    : array();
                if (!empty($confirmItems)) {
                    $this->request->addParam(array('items' => $confirmItems));
                    $h = new CheckItemsAvaibility($this);
                } else {
                    throw new \Verba\Exception\Routing('Incoming data not found');
                }
                break;
            default:
                throw new \Verba\Exception\Routing('Invalid cart action');
        }

        $h->addItems($this);

        $r = new \Verba\Response\Json();
        $r->addItems($h);
        return $r;
    }

    function prepare()
    {
        \Verba\Mod\Cart::getInstance()->clearSessionCache();
    }
}






