<?php
//unhandled
namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class Ip2long extends Around
{
  function run()
  {
    return ip2long(\Verba\getClientIP());
  }
}
