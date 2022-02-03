<?php
class faq_index extends \Verba\Block\Html
{

  public $templates = array(
    'content' => '/faq/index/content.tpl',
  );

  public $css = array('ic faq');

  function init(){
    $this->addItems(array(
      'IC_MENU' => new infocenter_menu($this),
    ));
  }

  function build()
  {

    $_menu = \Verba\_oh('menu');
    $_cnt = \Verba\_oh('content');

    $treeCfg = array(
      'nodeTypes' => array(
        'menu' => array('\Mod\Menu\Tree\View\Menu',array('templates' => array('body' => 'tree/node/body-no-link.tpl'))),
        'content' => '\Mod\Faq\Tree\View\ContentFaqOnPage',
      ),
      'levelsCfg' => array(
        1 => array(
          'skipBody' => true,
          'skipBodyClass' => true,
        ),
      )
    );

    $Tree = new Tree($_menu, 320, 1, array($_cnt->getID()));
    $Tree->applyConfigDirect($treeCfg);
    /**
     * @var $Node Mod\Infocenter\Tree\View\Menu
     */
    $Node = $Tree->buildNodesTree();

    $this->tpl->assign('FAQ_ENTRIES', $Node->parse());

    $b = new content_block($this);
    $b->addCssClass(array($Node->item['css_class'], 'frst-entry'));
    $b->title = $Node->item['title'];
    $b->text = false;

    $this->tpl->assign(array(
      'FAQ_TITLE' => $b->build(),
    ));

    $this->content = $this->tpl->parse(false, 'content');
    $Node->tpl()->clearShared();

    return $this->content;
  }
}