<?php
namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class ScriptKey extends Around
{
  function run()
  {
    return SYS_SCRIPT_KEY;
  }
}
