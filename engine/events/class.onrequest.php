<?php

namespace engine\events;

use engine\Event;
use engine\Request;

class OnRequest implements Event
{
  public const EVENT_NAME = 'onRequest';

  private $request;

  public function __construct(Request $req)
  {
    $this->request = $req;
  }

  public function info()
  {
    return $this->request;
  }
}
