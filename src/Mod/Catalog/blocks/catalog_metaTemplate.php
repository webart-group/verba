<?php
class catalog_metaTemplate extends \Verba\Block\Html{

  protected $template;

  protected $p;
  protected $case = false;
  protected $otcode = false;
  protected $key = false;
  protected $node_meta = array();
  protected $item_meta = array();
  protected $last_node_key;
  protected $rpl = array();


  function prepare(){
    $this->p = $this->getParent();
    $this->key = $this->p->metaKey;
    $this->node_key = $this->p->item_key;

    $this->last_node_key = end(array_keys($this->p->itemsMeta));

    $exp = explode('_',$this->last_node_key);
    $this->otcode = \Verba\_oh($exp[0])->getCode();

    if($this->otcode == 'catalog'){
      //$this->case = 'catalog';
    }else{
      $this->case = 'product';
    }

    if(!$this->case){
      return;
    }

    $this->p->finalizedMeta[$this->key] = true;
  }

  function extractTemplate(){

    $raw_value = '';
    if(isset($this->node_meta['meta_'.SYS_LOCALE])){
      $raw_value = $this->node_meta['meta_'.SYS_LOCALE];
    }else{
      $raw_value = $this->node_meta[$this->key];
    }

    $r = preg_match_all('/^#\{([a-z]+)\}(?:\r\n)?(?:\n\n)?(.*)/im', trim($raw_value), $matches);
    if($r
      && count($matches[1])
      && in_array($this->case, $matches[1])
      && count($key = array_keys($matches[1], $this->case))){
      return $matches[2][$key[0]];
    }
    return false;
  }

  function build(){
    $this->content = '';

    if(!$this->case
      || !($this->template = $this->extractTemplate())){
      return $this->content;
    }
    $method = 'build'.ucfirst($this->case);
    if(!method_exists($this, $method)){
      $method = 'buildDefault';
    }
    $this->$method();

    if(isset($this->item_meta[$this->key])
      && is_array($this->item_meta[$this->key])){
      $meta = &$this->item_meta[$this->key];
    }else{
      $meta = &$this->item_meta;
    }
    $_oh = \Verba\_oh($this->otcode);
    if(preg_match_all('/\{(\w+)\}/im', $this->template, $matches)
      && count($matches[1])){
      $srh = $rpl = array();
      foreach($matches[1] as $var_name){
        $propName = $replaceTo = false;
        if(strpos($var_name, 'item_') === 0){
          $propName = substr($var_name, 5);
          if(isset($meta[$propName])){
            if($_oh->isA($propName) && $_oh->A($propName)->isLcd() && isset($meta[$propName.'_'.SYS_LOCALE])){
              $replaceTo = $meta[$propName.'_'.SYS_LOCALE];
            }else{
              $replaceTo = $meta[$propName];
            }
          }
        }
        if(!$propName || !is_string($replaceTo)){
          continue;
        }
        $srh[] = '{'.$var_name.'}';
        $rpl[] = $replaceTo;
      }

      if(count($srh)){
        $this->content = str_replace($srh, $rpl, $this->template);
      }
    }elseif(is_string($this->template) && !empty($this->template)){
      $this->content = $this->template;
    }
    //$this->content = $this->template;
    return $this->content;
  }

  function buildDefault(){
    return $this->template;
  }

  function buildCatalog(){
    $this->item_meta = $this->p->itemsMeta[$this->node_key];
  }

  function buildProduct(){
    $this->item_meta = $this->p->itemsMeta[$this->last_node_key];
  }
}
?>