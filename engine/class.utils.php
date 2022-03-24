<?php
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//                                                                                                                                                                //
//                                                                ** SPLIT PHP FRAMEWORK **                                                                       //
// This file is part of *SPLIT PHP Framework*                                                                                                                     //
//                                                                                                                                                                //
// Why "SPLIT"? Firstly because the word "split" is a reference to micro-services and split systems architecture (of course you can make monoliths with it,       //
// if that's your thing). Furthermore, it is an acronym for these 5 bound concepts which are the bases that this framework leans on, which are: "Simplicity",     //
// "Purity", "Lightness", "Intuitiveness", "Target Minded"                                                                                                        //
//                                                                                                                                                                //
// See more info about it at: https://github.com/gabriel-guelfi/split-php                                                                                         //
//                                                                                                                                                                //
// MIT License                                                                                                                                                    //
//                                                                                                                                                                //
// Copyright (c) 2022 SPLIT PHP Framework Community                                                                                                               //
//                                                                                                                                                                //
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to          //
// deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or         //
// sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:                            //
//                                                                                                                                                                //
// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.                                 //
//                                                                                                                                                                //
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,FITNESS     //
// FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY           //
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.     //
//                                                                                                                                                                //
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class Utils
{
  /* $summary is an index of utils specified in config.ini file. 
     * It holds data like util's class name, path and arguments to be passed to utils's construct method.
     */

  private $summary;
  private static $methodsCollection = [];

  public function __construct()
  {
    $c = parse_ini_file(INCLUDE_PATH . "/config.ini", true);

    foreach ($c["UTILS"] as $k => $v) {
      $k = strtolower($k);
      $temp = explode("?", $v);
      $v = $temp[0];
      $args = array();
      if (isset($temp[1]))
        $args = explode("&", $temp[1]);
      unset($temp);
      foreach ($args as $i => $val) {
        $args[$i] = trim(substr($val, strpos($val, "=")), "=");
      }

      $this->register($k, $v, $args);

      if ($c["SYSTEM"]["UTILS_AUTOLOAD"]) {
        $this->load($k);
      }
    }
  }

  public function load($name, $path = null, $args = array())
  {
    $name = strtolower($name);
    if (!empty($path) && !array_key_exists($name, $this->summary)) {
      $this->register($name, $path, $args);
    }

    return $this->$name = System::loadClass(INCLUDE_PATH . "/public/utils/" . $this->summary[$name]->path, $name, $this->summary[$name]->args);
  }

  private function register($name, $path, $args = array())
  {
    $this->summary[$name] = (object) array(
      'path' => $path,
      'args' => $args
    );
  }

  public static function registerMethod($methodName, $instructions)
  {
    if (is_callable($instructions))
      self::$methodsCollection[$methodName] = $instructions;
  }

  public static function __callstatic($name, $arguments)
  {
    try {
      if (!isset(self::$methodsCollection[$name]))
        throw new Exception('There is not a method named "' . $name . '" defined in class Utils. You can define it by calling "Utils::registerMethod()" to make it available. Check documentation for more info.');

      return call_user_func_array(self::$methodsCollection[$name], $arguments);
    } catch (Exception $ex) {
      System::log('sys_error', $ex->getMessage());
    }
  }

  public static function lineBreak()
  {
    if (PATH_SEPARATOR == ":")
      return "\r\n";
    else return "\n";
  }

  public static function dataEncrypt(string $data, string $key)
  {
    $m = 'AES-256-CBC';

    do {
      $f = openssl_random_pseudo_bytes(rand(1, 9), $sec);
    } while (!$sec);

    $iv = substr(hash('sha256', time() . $f), 0, 16);
    $dt = openssl_encrypt($data, $m, $key, 0, $iv);

    return base64_encode(serialize([$iv, $dt]));
  }

  public static function dataDecrypt(string $data, string $key)
  {
    $m = 'AES-256-CBC';

    $data = unserialize(base64_decode($data));
    $iv = $data[0];
    $data = $data[1];

    return openssl_decrypt($data, $m, $key, 0, $iv);
  }

  public static function XML_encode($data, $node_block = 'nodes', $node_name = 'node')
  {
    $xml = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";

    $xml .= '<' . $node_block . '>' . "\n";
    $xml .= self::_dataToXML($data, $node_name);
    $xml .= '</' . $node_block . '>' . "\n";

    return $xml;
  }

  private static function _dataToXML($data, $node_name)
  {
    $xml = '';

    if (is_array($data) || is_object($data)) {
      foreach ($data as $key => $value) {
        if (is_numeric($key)) {
          $key = $node_name;
        }

        $xml .= '<' . $key . '>' . self::lineBreak() . self::_dataToXML($value, $node_name) . '</' . $key . '>' . self::lineBreak();
      }
    } else {
      $xml = htmlspecialchars($data, ENT_QUOTES) . self::lineBreak();
    }

    return $xml;
  }

  public static function preg_grep_keys($pattern, $input, $flags = 0)
  {
    return array_intersect_key($input, array_flip(preg_grep($pattern, array_keys($input), $flags)));
  }

  public static function filterInputs($filterRules, $data)
  {
    foreach ($data as $key => $value) {
      if (gettype($value == 'array') || (gettype($value == 'object' && $value instanceof StdClass)))
        $data[$key] = self::filterInputs($filterRules, $value);

      // Remove any field that is not defined in the filter rules:
      if (!array_key_exists($key, $filterRules)) unset($data[$key]);

      $rule = $filterRules[$key];

      // Fix float decimal places:
      if (gettype($value) == 'double' && !is_null($rule->decimalPlaces)) {
        $data[$key] = round($value, $rule->decimalPlaces);
      }

      // Remove string content out of pattern:
      if (is_string($value) && !is_null($rule->pattern)) {
        $rest = preg_split($rule->pattern, $value);
        foreach ($rest as $strPartArray) {
          if (!empty($strPartArray)) {
            $strPart = $strPartArray[0];
            $data[$key] = str_replace($strPart, "", $value);
          }
        }
      }
    }

    return $data;
  }

  public static function validateData($validationRules, $data)
  {
    // Check required fields:
    foreach ($validationRules as $field => $_rule) {
      if (!isset($_rule->dataType)) throw new Exception("Data type is required within input validation rules.");

      if ((!empty($_rule->required)) && empty($data[$field])) {
        $logObj = (object) [
          "date" => date('d/m/Y H:i:s'),
          "message" => 'Input validation failed.',
          "cause" => 'Required field empty or not found.',
          "route" => $_SERVER["REQUEST_URI"],
          "input_name" => $field
        ];
        System::log('input_validation', json_encode($logObj));
        if (!empty($_rule->message)) throw new Exception($_rule->message, VALIDATION_FAILED_ERROR);
        else return false;
      }
    }

    foreach ($data as $key => $value) {
      if (gettype($value) == 'array' || (gettype($value) == 'object' && $value instanceof StdClass))
        if (self::validateInputs($validationRules, $value) === false) return false;

      if (!array_key_exists($key, $validationRules)) continue;

      $rule = $validationRules[$key];

      // Check for forbidden field:
      if (is_null($rule->dataType)) {
        $logObj = (object) [
          "date" => date('d/m/Y H:i:s'),
          "message" => 'Input validation failed.',
          "cause" => 'Forbidden field found.',
          "route" => $_SERVER["REQUEST_URI"],
          "input_name" => $key,
          "input_value" => $value
        ];
        System::log('input_validation', json_encode($logObj));
        if (!empty($rule->message)) throw new Exception($rule->message, VALIDATION_FAILED_ERROR);
        else return false;
      }

      // Data type validation:
      if (gettype($value) != $rule->dataType) {
        $logObj = (object) [
          "date" => date('d/m/Y H:i:s'),
          "message" => 'Input validation failed.',
          "cause" => 'Invalid type.',
          "route" => $_SERVER["REQUEST_URI"],
          "input_name" => $key,
          "input_value" => $value
        ];
        System::log('input_validation', json_encode($logObj));
        if (!empty($rule->message)) throw new Exception($rule->message, VALIDATION_FAILED_ERROR);
        else return false;
      }

      // String length validation:
      if (is_string($value) && !empty($rule->length)) {
        if (strlen($value) != $rule->length) {
          $logObj = (object) [
            "date" => date('d/m/Y H:i:s'),
            "message" => 'Input validation failed.',
            "cause" => 'String length does not match rule.',
            "route" => $_SERVER["REQUEST_URI"],
            "input_name" => $key,
            "input_value" => $value
          ];
          System::log('input_validation', json_encode($logObj));
          if (!empty($rule->message)) throw new Exception($rule->message, VALIDATION_FAILED_ERROR);
          else return false;
        }
      }

      // String pattern validation:
      if (is_string($value) && !empty($rule->pattern)) {
        $rest = preg_split($rule->pattern, $value);
        if (count($rest) != 2 || !empty($rest[0]) || !empty($rest[1])) {
          $logObj = (object) [
            "date" => date('d/m/Y H:i:s'),
            "message" => 'Input validation failed.',
            "cause" => 'String does not match the required pattern.',
            "route" => $_SERVER["REQUEST_URI"],
            "input_name" => $key,
            "input_value" => $value
          ];
          System::log('input_validation', json_encode($logObj));
          if (!empty($rule->message)) throw new Exception($rule->message, VALIDATION_FAILED_ERROR);
          else return false;
        }
      }

      // Custom validation function:
      if (!empty($rule->custom) && $rule->custom($value) === false) {
        $logObj = (object) [
          "date" => date('d/m/Y H:i:s'),
          "message" => 'Input validation failed.',
          "cause" => 'Input did not pass custom validation method.',
          "route" => $_SERVER["REQUEST_URI"],
          "input_name" => $key,
          "input_value" => $value
        ];
        System::log('input_validation', json_encode($logObj));
        if (!empty($rule->message)) throw new Exception($rule->message, VALIDATION_FAILED_ERROR);
        else return false;
      }
    }

    return true;
  }

  public static function uploadFile($inputName)
  {
    if (!empty($_FILES[$inputName])) {
      $filename = uniqid() . '_' . $_FILES[$inputName]['name'];
      $filepath = INCLUDE_PATH . '/public/resources/upload/' . $filename;
      if (file_put_contents($filepath, file_get_contents($_FILES[$inputName]['tmp_name']))) {
        return '/resources/upload/' . $filename;
      }
    }

    return null;
  }
}
