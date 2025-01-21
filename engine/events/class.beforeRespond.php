<?php

namespace engine\events;

use engine\Event;
use engine\Response;

class BeforeRespond implements Event
{
  public const EVENT_NAME = 'beforeRespond';

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
