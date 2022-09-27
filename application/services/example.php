<?php

namespace application\services;

use \engine\Service;

class Example extends Service
{
  public function welcomeMsg($name = "")
  {
    return "Welcome {$name} to SPLIT PHP, the lean, low learning curve PHP framework!";
  }
}
