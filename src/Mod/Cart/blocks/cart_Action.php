<?php

class cart_Action extends \Verba\Block\Html
{

    function route()
    {
        switch ($this->request->uf[0]) {
            case 'clear':
                $h = new cart_ActionClear($this);
                break;
            case 'currencychange':
                $this->request->ot_id = \Verba\_oh('currency')->getID();
                $h = new cart_ActionCurrencyChange($this);
                break;
            case 'checkcustomer':
                $h = new cart_ActionSwitchUserByEmail($this);
                break;
            case 'checkitemsavaibility':
                $confirmItems = isset($_REQUEST['confirmItems']) && is_array($_REQUEST['confirmItems'])
                    ? $_REQUEST['confirmItems']
                    : array();
                if (!empty($confirmItems)) {
                    $this->request->addParam(array('items' => $confirmItems));
                    $h = new cart_ActionCheckItemsAvaibility($this);
                } else {
                    throw new Exception('Incoming data not found');
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

class cart_ActionClear extends \Verba\Block\Html
{

    function build()
    {
        $this->content = \Verba\_mod('cart')->resetAndClearItems();
        return $this->content;
    }
}

class cart_ActionSwitchUserByEmail extends \Verba\Block\Html
{

    function build()
    {
        $this->content = \Verba\_mod('cart')->switchCustomerByEmail($this->request->asArray());
        return $this->content;
    }
}

class cart_ActionCheckItemsAvaibility extends \Verba\Block\Html
{

    function build()
    {
        $this->content = \Verba\_mod('cart')->checkItemsAvaibility($this->request->getParam('items'));
        return $this->content;
    }
}
