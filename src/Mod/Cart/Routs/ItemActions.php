<?php
namespace Verba\Mod\Cart\Routs;

use Verba\Mod\Cart\Actions\ItemAdd;
use Verba\Mod\Cart\Actions\ItemQuantityUpdate;
use Verba\Mod\Cart\Actions\ItemDelete;
use Verba\Mod\Cart\Actions\ItemSup;

class ItemActions extends \Verba\Request\Console\Router
{

    function route()
    {
        switch ($this->request->uf[0]) {
            case 'add':
//                $this->request->addParam(array(
//                    'item' => $_REQUEST['item'],
//                ));
                $h = new ItemAdd($this);
                break;
            case 'quantityupdate':
                $this->request->addParam(array(
                    'item' => $_REQUEST['item'],
                ));
                $h = new ItemQuantityUpdate($this);
                break;
            case 'delete':
                $this->request->addParam(array(
                    'hash' => $_REQUEST['hash'],
                ));
                $h = new ItemDelete($this);
                break;
            default:
                throw new \Verba\Exception\Routing();
        }

        $ItemSup = new ItemSup($this);
        $h->addItems($ItemSup);

        $r = new \Verba\Response\Json();
        $r->addItems($h);

        return $r;
    }
}
