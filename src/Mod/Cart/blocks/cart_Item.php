<?php

class cart_Item extends \Verba\Block\Html
{

    function route()
    {
        switch ($this->request->uf[0]) {
            case 'add':
                $this->request->addParam(array(
                    'item' => $_REQUEST['item'],
                ));
                $h = new cart_ItemAdd($this);
                break;
            case 'quantityupdate':
                $this->request->addParam(array(
                    'item' => $_REQUEST['item'],
                ));
                $h = new cart_ItemQuantityUpdate($this);
                break;
            case 'delete':
                $this->request->addParam(array(
                    'hash' => $_REQUEST['hash'],
                ));
                $h = new cart_ItemDelete($this);
                break;
            default:
                throw new \Verba\Exception\Routing();
        }

        $ItemSup = new cart_ItemSup($this);
        $h->addItems($ItemSup);

        $r = new \Verba\Response\Json();
        $r->addItems($h);
        return $r;
    }


}

class cart_ItemSup extends \Verba\Block
{
    function prepare()
    {
        \Verba\_mod('cart')->clearSessionCache();
    }
}

class cart_ItemAdd extends \Verba\Block\Html
{

    function build()
    {
        $this->content = \Verba\_mod('cart')->addItem($this->request->getParam('item'))->packToClient();
        return $this->content;
    }

}

class cart_ItemQuantityUpdate extends \Verba\Block\Html
{

    function build()
    {
        $this->content = \Verba\_mod('cart')->itemQuantityUpdate($this->request->getParam('item'))->packToClient();
    }

}

class cart_ItemDelete extends \Verba\Block\Html
{

    function build()
    {
        $this->content = \Verba\_mod('cart')->itemDelete($this->request->getParam('hash'));
    }

}

?>