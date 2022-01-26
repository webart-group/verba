<?php
namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class UrlHash extends Around
{
  function run()
  {
    if(!isset($this->params['base'])
      || !is_object($Av = $this->oh->A($this->params['base']))
      || $this->ah->getGettedValue($Av->getCode()) === null
    ){
      return false;
    }

    $url = new \Verba\Url($this->ah->getGettedValue($Av->getCode()));

    $url->anchor = $url->user = $url->password = $url->params = null;

    switch((int)$this->params['mode']){
      // fullurl
      case 0:
        $r = md5($url->get(true, true), true);
        break;
      // host
      case 1:
        $r = md5($url->host, true);
        break;
      default:
        $r = false;
    }
    $this->ah->setGettedObjectData(array($this->A->getCode() => $r));
    return $r;
  }
}
