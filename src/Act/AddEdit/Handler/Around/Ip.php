<?php
namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class Ip extends Around
{
  function run()
  {
    return \Verba\getClientIP();
  }
}
