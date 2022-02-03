<?php
namespace Mod\Game\Act\Form\Element\Extension;

class CustomizePlaceholderTrqResource extends CustomizePlaceholderResource{

  function getSrcOh(){
    return $this->ah()->getExtendedData('prodItem')->oh();
  }

}
