<?php
namespace Verba\DBDriver;

interface Driver {

  public function connect($connectData);

  public function close();

  public function query($query);

  public function escape_string($strIn);

  public function getLastError();

  public function getLastResult();

  public function multiQuery($query);

  public function getQueryCount();
}
