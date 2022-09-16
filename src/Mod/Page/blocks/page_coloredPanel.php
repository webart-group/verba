<?php
class page_coloredPanel extends page_contentTitled{


  public $templates = array(
    'content' => '/page/elements/coloredPanel.tpl'
  );

  /**
   * @var string|null css-class for identifying panel
   */
  public $extra_css_class;
  public $scheme;
  public $width = '';
  public $centered = true;
  protected $avaible_schemes = array(
    'blue', 'green', 'brown', 'grey'
  );

  public $agregate_parse = false;

  function build(){

    parent::build();

    if(!is_string($this->scheme)){
      $this->scheme = $this->avaible_schemes[array_rand($this->avaible_schemes)];
    }

    $this->tpl->assign(array(
      'SCHEME' => $this->scheme,
      'WIDTH' => is_string($this->width) && !empty($this->width) ? ' '.$this->width : '',
      'EXTRA_CSS_CLASS' => is_string($this->extra_css_class) && !empty($this->extra_css_class) ? ' '.$this->extra_css_class : '',
      'CENTERED' => $this->centered ? ' center-block' : '',
      'TITLE' => is_string($this->title) ? $this->title : '',
      'CONTENT' => (string)$this->content,
    ));

    $this->content = $this->tpl->parse(false, 'content');

    return $this->content;
  }

}
?>
