<?php

namespace engine\events;

use engine\Event;
use engine\Response;

class AfterResponded implements Event
{
  public const EVENT_NAME = 'afterResponded';

  private $response;

  public function __construct(Response $response)
  {
    $this->response = $response;
  }

  public function info()
  {
    return $this->response;
  }
}
