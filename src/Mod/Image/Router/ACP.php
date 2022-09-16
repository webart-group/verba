<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 18.12.2019
 * Time: 1:16
 */

namespace Verba\Mod\Image\Router;

class ACP extends \Verba\Request\Http\Router
{

    public $otcode = 'image';

    function route()
    {

        if (!isset($this->request->action)
            && count($this->request->uf)) {
            $this->request->action = $this->request->uf[count($this->request->uf) - 1];
        }

        switch ($this->request->action) {
            case 'upload':
                $router = new ACP\Upload($this);

                break;
        }

        if (!isset($router)) {
            $h = (new \Verba\Mod\Routine\Router($this->rq))->route();
        } else {
            $h = $router->route();
        }

        return $h;
    }
}
