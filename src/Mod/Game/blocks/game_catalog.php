<?php

class game_catalog extends game_pageContent
{

    public $templates = array(
        'content' => 'game/buy/wrap.tpl',
    );


    function route()
    {

        if (!is_object($this->gsr) || !$this->gsr->isValid()) {
            throw new \Exception\Routing();
        }

        if ($this->gsr->isServiceOblivious()) {
            $redirectUrl = $this->gsr->service->getUrlByAction($this->gsr->gameAction);
            $redirectResponse = new \Verba\Block\Html($this);
            $redirectResponse->addHeader('HTTP/1.1 301 Moved Permanently');
            $redirectResponse->addHeader('Location', $redirectUrl);

            return $redirectResponse;
        }

        $this->addItems(array(
            'CONTENT' => new game_buyList($this->rq, [
                'gsr' => $this->gsr,
            ]),
            new game_meta($this, array('gsr' => $this->gsr)),
        ));
        $this->setCss(array(
            ['reviews'],
            array('offers', 'game'),
        ));

        if (!isset($_SERVER['QUERY_STRING']) || empty($_SERVER['QUERY_STRING'])) {
            $this->addItems(array(
                'CATALOG_DESCRIPTION' => new catalog_pbphl($this, array(
                    'items' => array(new catalog_description($this))
                )),
            ));
        }
        return $this;
    }

    function build()
    {

        $this->content = $this->tpl->parse(false, 'content');
        \Verba\Hive::setBackURL();

        if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
            $url = new \Url($_SERVER['SCRIPT_URL']);
            $this->addHeadTag('link', array('rel' => 'canonical', 'href' => $url->get(true)));
        }

        return $this->content;
    }

}

?>