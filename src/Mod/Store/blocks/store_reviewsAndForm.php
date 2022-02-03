<?php

class store_reviewsAndForm extends \Verba\Block\Html
{

    /**
     * @var \Model\Item
     */
    public $prodItem;
    public $addProductAsParent = false;

    protected $_userCanAddNewReview;
    /**
     * @var $Order \Mod\Order\Model\Order
     */
    public $Order;
    /**
     * @var Url
     */
    public $urlBase;
    public $listId;

    protected $_listCfg;

    /**
     * @var \Model\Store
     */
    public $Store;

    function route()
    {

        $_review = \Verba\_oh('review');

        $rq = clone $this->rq;
        $rq->iid = false;
        $rq->ot_id = $_review->getID();
        $rq->ot_code = $_review->getCode();
        $rq->action = 'new';
        $rq->key = $_review->getBaseKey();


        // Проверка при форме/добавление отзыва
        if ($this->rq->node === 'create'
            || $this->rq->node === 'createform') {
            if (!$this->userCanAddReview()) {
                $response = new \Block\Json($this);
                $response->setOperationStatus(false);
                $response->content = $this->_userCanAddNewReview;
                return $response;
            }
        }

        // форма отзывов
        if ($this->rq->node === 'createform') {

            if (is_object($this->Order)) {

                $rq->addParent(\Verba\_oh('order')->getID(), $this->Order->getId());

                if (is_object($this->prodItem) && $this->addProductAsParent) {
                    $rq->addParent($this->prodItem->getOtId(), $this->prodItem->getId());
                }
            }
            $response = $b = new \Mod\Routine\Block\Form\Json($rq, array(
                'cfg' => 'public public/order/review',
                'dcfg' => array(
                    'url' => array(
                        'forward' => $this->getUrlFor('create'),
                    )
                )
            ));
            // добавление отзыва
        } elseif ($this->rq->node === 'create') {

            $response = new \Mod\Routine\Block\CUNow($rq);

            // html-список отзывов
        } elseif ($this->rq->node === 'list') {
            $cfg = $this->getListCfg();
            $cfg['contentType'] = 'json';
            $response = new game_reviewsList(array(), $cfg);
        }

        if (isset($response)) {
            return $response;
        }

        throw new \Exception\Routing();
    }

    function setUrlBase($url)
    {
        $this->urlBase = new \Url($url);
    }

    function getUrlBase()
    {
        if ($this->urlBase === null) {
            $this->urlBase = new \Url($this->rq->getRequestUri());
        }
        return $this->urlBase;
    }

    function getUrlFor($ending)
    {

        $Url = clone $this->getUrlBase();

        $Url->shiftPath($ending);

        return $Url->get();
    }

    function userCanAddReview()
    {
        if ($this->_userCanAddNewReview === null) {
            /**
             * @var $mReview \Mod\Review
             */
            $mReview = \Verba\_mod('Review');
            $this->_userCanAddNewReview = $mReview->checkIfAllowCreateReview($this->Store, User());
        }
        return $this->_userCanAddNewReview === true;
    }

    function build()
    {
        /**
         * @var $list \Act\MakeList
         */
        $cfg = $this->getListCfg();
        $bList = new game_reviewsList(array(), $cfg);

        $bList->prepare();
        $bList->build();

        $this->mergeHtmlIncludes($bList);

        $q = $bList->list->QM()->getQuery();


        $this->content = $bList->getContent();
        return $this->content;
    }

    function getListCfg()
    {
        if ($this->_listCfg === null) {
            $this->_listCfg = $this->genListCfg();
        }

        return $this->_listCfg;
    }

    function genListCfg()
    {

        $r = array(
            'listId' => $this->listId,
            'extendedData' => array(
                'Store' => $this->Store,
                'prodItem' => $this->prodItem,
                'Order' => $this->Order,
            ),
            'dcfg' => array('url' => array(
                'forward' => $this->getUrlFor('list'),
            ),
            ),

            'Store' => $this->Store,

        );

        // определение права на добавление отзыва
        // - должен быть хотя бы один оплаченный заказ


        if ($this->userCanAddReview()) {
            $cstm['dcfg'] = array(
                'feats' => array(
                    'addnew' => 1,
                ),
                'url' => array(
                    'new' => $this->getUrlFor('createform'),
                ),
            );
            $r = array_replace_recursive($r, $cstm);
        }

        return $r;
    }
}
