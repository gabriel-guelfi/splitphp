<?php

class Service extends Dao
{
  protected $utils;

  public function __construct()
  {
    parent::__construct();

    $this->utils = System::loadClass(INCLUDE_PATH . "/engine/class.utils.php", "utils");
  }
  protected final function getService($path, $args = [])
  {
    @$className = strpos($path, '/') ? end(explode('/', $path)) : $path;

    return System::loadClass(INCLUDE_PATH . '/application/services/' . $path . '.php', $className, $args);
  }

  protected final function renderTemplate($path, $varlist = [])
  {
    if (!empty($varlist)) extract($this->escapeOutput($varlist));

    ob_start();
    include INCLUDE_PATH . "/application/templates/" . $path . ".php";

    return ob_get_clean();
  }

  private function escapeOutput($payload)
  {
    foreach ($payload as &$value) {
      if (gettype($value) == 'array' || (gettype($value) == 'object' && $value instanceof StdClass)) {
        $value = $this->escapeOutput($value);
        continue;
      }

      $value = htmlspecialchars($value);
    }

    return $payload;
  }
}
