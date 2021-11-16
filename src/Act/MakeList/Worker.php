<?php
namespace Verba\Act\MakeList;

use \Verba\Act\Worker as ActWorker;

class Worker extends ActWorker{

  protected $_confOnlyPubPropDirectSetAllowed = false;

  /**
   * @var \Verba\Act\MakeList
   */
  protected $parent;
}
