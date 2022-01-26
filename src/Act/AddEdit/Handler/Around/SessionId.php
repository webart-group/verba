<?php
//unhandled
namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class SessionId extends Around
{
  function run()
  {
    return session_id();
  }
}
