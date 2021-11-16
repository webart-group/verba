<?php
namespace Verba\Act\AddEdit\Handler\Around;

use Act\AddEdit\Handler\Around;

class Ip extends Around
{
  function run()
  {
    return \Verba\getClientIP();
  }
}
