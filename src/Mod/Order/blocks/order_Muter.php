<?php
class order_Muter extends \Verba\Block\Html{

  function prepare(){
    $cart_pageBlock = $this->getBlockByRole('cart-widget');
    if($cart_pageBlock){
      $cart_pageBlock->mute();
    }

    $menu_pageBreadcrumbs = $this->getBlockByRole('page-breadcrubms');
    if($menu_pageBreadcrumbs){
      $menu_pageBreadcrumbs->mute();
    }

  }

}

?>
