<?php

namespace Verba\Mod\Acp\Router;

class Sitemap extends \Verba\Block\Html
{

    function route()
    {
        $uf0 = isset($this->request->uf[0]) ? $this->request->uf[0] : false;
        $uf1 = isset($this->request->uf[1]) ? $this->request->uf[1] : false;
        $rq = clone $this->request;
        array_shift($rq->uf);
        switch ($uf0) {

            case 'tools':
                switch ($uf1) {
                    case '':
                    case false:
                        $h = new \Verba\Mod\Sitemap\Block\AcpE($this);
                        break;
                    case 'cron_refresh':
                        $h = new \Verba\Mod\Sitemap\Block\AddCronTask($this);
                        break;
                    case 'info':
                        $h = new \Verba\Mod\Sitemap\Block\Info($this);
                        break;
                    default:
                        break;

                }
                break;

        }

        if (!isset($h)) {
            throw new \Verba\Exception\Routing();
        }
        $r = $h->route();
        return $r;
    }
}