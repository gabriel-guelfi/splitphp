<?php

namespace engine\events;

use engine\Event;
use engine\Action;

class BeforeRunCommand implements Event
{
  public const EVENT_NAME = 'beforeRunCommand';

  private $action;

  public function __construct(Action $action)
  {
    $this->action = $action;
  }

  public function info()
  {
    return $this->action;
  }
}
