<?php
namespace Verba\Mod\Langu\Block;

use Verba\Exception\Building;
use Verba\Lang;

class Change extends \Verba\Block\Json
{
    function build()
    {
        $request = $this->request->post();

        if(!isset($request['code'])){
            throw new Building('Bad request');
        }

        if(!Lang::setLocale($request['code'])){
            throw new Building('Unable to set locale');
        }

        return $this->content = true;
    }
}
