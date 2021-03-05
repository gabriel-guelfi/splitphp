<?php

class Controller
{

  // The global object of Utils class
  protected $utils;
  // Current module. It's the same name of the running controller.
  private $module;

  // Set the global system property, set the module and create module's model instance.
  public function __construct($module)
  {
    $this->utils = System::loadClass(INCLUDE_PATH . "/engine/class.utils.php", "utils");

    $this->module = $module;
  }

  // Show or return the contents of a view file, passing specified variables for this file, if they're supplied.
  protected function view($path, $varlist = [], $return = false)
  {
    if (!empty($varlist)) extract($this->escapeOutput($varlist));

    ob_start();
    try {
      include INCLUDE_PATH . System::getExecEnv() . "/views" . $path . ".php";
    } catch (Exception $ex) {
      if ($msg = System::debug($ex))
        $this->system->utils->pesticide->debug([$msg], get_defined_vars());
    }

    if ($return === true)
      return ob_get_clean();
    else
      echo ob_get_clean();
  }

  protected function getModel($modelName = null)
  {
    $model = null;

    if (!is_null($modelName)) {
      $model =  System::loadClass(INCLUDE_PATH . System::getExecEnv() . "/models/" . $modelName . ".php", "Model" . ucfirst($modelName), [$modelName]);
    } else {
      $model =  System::loadClass(INCLUDE_PATH . System::getExecEnv() . "/models/" . $this->module . ".php", "Model" . ucfirst($this->module), [$this->module]);
    }

    return $model;
  }

  private function escapeOutput($payload)
  {
    foreach ($payload as &$value) {
      if (gettype($value) == 'array' || (gettype($value) == 'object' && $value instanceof StdClass)) {
        $value = $this->escapeOutput($value);
        continue;
      }

      $value = htmlentities($value);
    }

    return $payload;
  }
}
