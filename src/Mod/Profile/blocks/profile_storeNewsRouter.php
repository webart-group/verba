<?php
class profile_storeNewsRouter extends \Verba\Block{

  function route(){

    $_news = \Verba\_oh('news');
    $rq = $this->rq->asArray();
    $rq['ot_id'] = $_news->getID();
    $rq['ot_code'] = $_news->getCode();
    $blockCfg = array(
      'valid_otype' => 'news',
    );

    $_store = \Verba\_oh('store');
    $Store =\Verba\User()->Stores()->getStore();
    if(!$Store){
      throw  new \Verba\Exception\Building('Unknown store id');
    }

    $rq['pot'] = $_store->getID();
    $rq['piid'] = $Store->id;

    switch($this->rq->node){

      case 'create':
      case 'update':
        $blockCfg['responseAs'] = 'json-item-updated';

        $b = new \Mod\Routine\Block\CUNow($rq, $blockCfg);
        break;

      case 'cuform':
        $blockCfg['cfg'] = 'public public/profile/store_news';


        $b = new \Mod\Routine\Block\Form\Json($rq, $blockCfg);
        break;
      case '':
      case 'list':
        // list cfg
        $blockCfg['cfg'] = 'public public/profile/store_news';
        $blockCfg['dcfg'] = array(
          'listId' => 's'.$Store->getId().'news'
        );
        switch($this->rq->node){
          case 'list':
            $b = new \Mod\Routine\Block\MakeList\Json($rq, $blockCfg);
            break;
          case '':

            $b = new profile_contentTraders($rq, array(
              'coloredPanelCfg' => false,
              'titleLangKey' => 'store news list title',
            ));
            $b->addItems(array(
              new \Mod\Routine\Block\MakeList($rq, $blockCfg)
            ));
            break;
        }
        break;
      case 'delete':

        $b = new \Mod\Routine\Block\Delete\Json($rq, $blockCfg);
        break;
    }

    if(!isset($b)){
      throw new \Exception\Routing();
    }

    return $b->route();
  }

}
?>
