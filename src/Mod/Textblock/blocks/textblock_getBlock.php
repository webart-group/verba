<?php
class textblock_getBlock extends \Verba\Block\Html{

  protected $_mod = 'textblock';
  protected $_ot = 'textblock';

  public $parseContent = false;

  public $text = false;
  public $title;
  public $titleClass = '';
  public $id = false; // textblock wrap id
  public $extra_css_class;

  public $templates = array(
    'content' => '/textblock/block.tpl',
    'title' => '/textblock/block-title.tpl',
  );

  public $tplvars = array(
    'EXTRA_CSS_CLASS' => ''
  );

  /**
   * @var \Model\Item
   */
  protected $OItem;

  function getOItem(){
    if($this->OItem === null){
      $this->OItem = \Verba\_mod($this->_mod)->OTIC()->getItem($this->request->iid);
    }
    return $this->OItem;
  }

  function build(){

    $text = $title = $id = false;

    if(isset($this->text) && !empty($this->text)){
      $text = (string)$this->text;
      if(isset($this->title) && !empty($this->title)){
        $title = (string)$this->title;
      }
      if(isset($this->id)){
        $id = (string)$this->id;
      }
    }elseif($this->request->iid){
      $row = $this->getOItem();
      if(is_object($row)){
        $_content = \Verba\_oh($this->_ot);
        $text = $row->text;
        $title = $row->title;
        $id = $row->{$_content->getPAC()};
        if(strlen($row->extra_css_class)){
          if(!empty($this->extra_css_class)){
            $this->extra_css_class .= ' '.$row->extra_css_class;
          }else{
            $this->extra_css_class = $row->extra_css_class;
          }
        }
      }
    }

    if(!$text){
      return '';
    }

    if(gettype($this->titleClass) != 'string'){
      settype($this->titleClass, 'string');
    }

    if($this->title === false || empty($title)){
      $this->titleClass .= ' hidden';
      $this->tpl->assign(array('TEXTBLOCK_TITLE' => ''));
    }else{
      $this->tpl->assign(array(
        'TEXTBLOCK_TITLE_CLASS' => $this->titleClass,
        'TEXTBLOCK_TITLE_VALUE' => $title
      ));
      $this->tpl->parse('TEXTBLOCK_TITLE', 'title');
    }

    $this->tpl->assign(array(
      'TEXTBLOCK_ID_TAG_ATTR' => $id ? ' id="textblock_'.htmlspecialchars($id).'"' : '',
      'TEXTBLOCK_CONTENT'  => (bool)$this->parseContent
        ? $this->tpl->parse_template($text)
        : $text,
      'EXTRA_CSS_CLASS' => is_string($this->extra_css_class) && strlen($this->extra_css_class)
        ? ' '.$this->extra_css_class
        : '',
    ));

    $this->content = $this->tpl->parse(false, 'content');
    return $this->content;
  }
}
?>