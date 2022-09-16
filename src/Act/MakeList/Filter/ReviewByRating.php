<?php
namespace Verba\Act\MakeList\Filter;

class ReviewByRating extends \Verba\Act\MakeList\Filter {
  public $captionLangKey = 'review list filters rewByRating';
  public $name = 'rewByRating';
  public $attr = 'rating';
  public $felement = '\Verba\Html\Select';

  function setValue($val){
    $val = intval($val);
    $this->value = $val;
  }

  function applyValue(){
    $wgAlias = $this->makeWhereAlias();
    $this->list->QM()->removeWhere($wgAlias);

    if(!$this->value){
      return;
    }

    $this->list->QM()->addWhere($this->value, $wgAlias, 'rating');
  }

  function build(){

    $this->tpl->clear_tpl(array_keys($this->templates));
    $this->tpl->define($this->templates);
    $_review = \Verba\_oh('review');
    /**
     * @var $mReview \Review
     */
    $mReview = \Verba\_mod('review');

    $values = array(
      '' => 0,
      $mReview->getRatingIdFromNominal(1) => 0,
      $mReview->getRatingIdFromNominal(2) => 0,
      $mReview->getRatingIdFromNominal(3) => 0,
      $mReview->getRatingIdFromNominal(4) => 0,
      $mReview->getRatingIdFromNominal(5) => 0,
    );

    $qm = clone $this->list->QM();
    $wgAlias = $this->makeWhereAlias();
    $qm->removeWhere($wgAlias);
    $where = $qm->compileWhere();
    list($ta) = $this->list->QM()->createAlias();

    $q = "SELECT `".$ta."`.ratingNom, count(`".$ta."`.id) AS c1  
    FROM ".$_review->vltURI()." as `".$ta."` 
    WHERE ".$where." 
    GROUP BY ratingNom 
    ORDER BY ratingNom ASC";
    $sqlr = $this->DB()->query($q);

    if($sqlr && $sqlr->getNumRows()){
      while($row = $sqlr->fetchRow()){
        $values[''] += $row['c1'];
        $values[$mReview->getRatingIdFromNominal($row['ratingNom'])] = $row['c1'];
      }
    }
    $A = $_review->A('rating');
    $vv = $A->PdSet()->getValues();
    foreach($values as $k => $v){
      if($k == ''){
        $w = \Verba\Lang::get('review list filters rewByRating all');
      }else{
        $w = array_key_exists($k, $vv) ? $vv[$k] : '???';
      }
      $values[$k] = $w.' - '.$v;
    }

    $this->E->setValues($values);
    $this->E->setValue($this->value);

    $this->tpl->assign(array(
      'FILTER_ELEMENT' => $this->E->build(),
    ));

    return $this->tpl->parse(false, 'content');
  }
}
?>