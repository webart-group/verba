<?php

namespace Mod\Sitemap\Block;

class GenerateMenuFaq extends GenerateMenu {

  function extractParseData($Node){

    $_menu = \Verba\_oh('menu');
    $_cnt = \Verba\_oh('content');

    $r = array(
      'LOC' => '',
    );


    if($Node->item['ot_id'] != $_cnt->getID()) {
      return parent::extractParseData($Node);
    }
    // content node

    $r['LOC'] = (new \Url($Node->item['url']))->get(true);



    return $r;
  }
}

