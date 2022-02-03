<?php

namespace Mod\Search\Block;

class Create extends \Verba\Block\Html
{
    public $q = '';

    function route()
    {
        $h = new \Verba\Response\Json($this->request);
        $h->addItems($this);
        return $h;
    }

    /**
     * @return array|string|string[]
     * @throws \Exception
     */
    function build()
    {
        /**
         * @var \Mod\Search $mSearch
         */
        $mSearch = \Verba\_mod('search');
        try {
            $hash = $mSearch->makeHash($this->q);
            $h = $mSearch->findQByHash($hash);
            if (!$h && !$mSearch->createSearch($this->q, $hash)) {
                throw new \Exception('Unable to create search');
            }

            $this->content = array('hash' => $hash);

        } catch (\Exception $e) {
            $this->content = 'Operation error';
            throw $e;
        }
        return $this->content;
    }
}
