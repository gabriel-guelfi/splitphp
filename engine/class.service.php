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

namespace engine;

use stdClass;
use \Exception;

/**
 * Class Service
 * 
 * This class aims to provide an interface where the developer creates the application's Service layer, applying all th business rules, logic and database 
 * operations of the application.
 *
 * @package engine
 */
class Service extends Dao
{
  /**
   * @var Utils $utils
   * Stores an instance of the Utils class.
   */
  protected $utils;

  /**
   * @var string $templateRoot
   * Stores the path from which the template rendering must start, when searching for a template path.
   */
  private $templateRoot;

  /** 
   * Runs the parent's constructor, initiate the properties, calls init() method then returns an instance of the class (constructor).
   * 
   * @return Service 
   */
  public function __construct()
  {
    parent::__construct();

    $this->templateRoot = "";

    $this->utils = System::loadClass(ROOT_PATH . "/engine/class.utils.php", "utils");

    $this->init();
  }

  /** 
   * Returns a string representation of this class for printing purposes.
   * 
   * @return string 
   */
  public function __toString()
  {
    return "class:Service:" . __CLASS__ . "()";
  }

  /** 
   * It's an empty abstract method, used to replace __construct(), in case the dev wants to initiate his Service with some initial execution, he 
   * can extend this method and perform whatever he wants on the initiation of the Service.
   * 
   * @return mixed 
   */
  public function init()
  {
  }

  /** 
   * This returns an instance of a service specified in $path.
   * 
   * @param string $path
   * @return mixed 
   */
  protected final function getService(string $path)
  {
    @$className = strpos($path, '/') ? end(explode('/', $path)) : $path;

    if (!file_exists(ROOT_PATH . '/application/services/' . $path . '.php'))
      throw new Exception("The requested service path could not be found.");

    return System::loadClass(ROOT_PATH . '/application/services/' . $path . '.php', $className);
  }

  /** 
   * Renders a template, at a location specified in $path, starting from Service::templateRoot, then returns the rendered result in a string.
   * 
   * @param string $path
   * @param array $varlist = []
   * @return string 
   */
  protected final function renderTemplate(string $path, array $varlist = [])
  {
    if (!empty($varlist)) extract($this->escapeOutput($varlist));
    $path = ltrim($path, '/');

    ob_start();
    include ROOT_PATH . "/application/templates/" . $this->templateRoot . $path . ".php";

    return ob_get_clean();
  }

  /** 
   * Executes a cURL request on the specified $url, passing data under the $payload, using the defined $httpVerb, passing along the $headers, then returns 
   * an object containing the status and the resulting data.
   * 
   * @param string $url
   * @param array $payload = null
   * @param string $httpVerb = "GET"
   * @param array $headers = null
   * @return object 
   */
  protected final function requestURL(string $url, array $payload = null, string $httpVerb = 'GET', array $headers = null)
  {
    // basic setup
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // set options by http verb:
    switch ($httpVerb) {
      case 'POST':
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,  http_build_query($payload));
        break;
      case 'PUT':
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS,  http_build_query($payload));
        break;
      case 'PATCH':
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
        curl_setopt($ch, CURLOPT_POSTFIELDS,  http_build_query($payload));
        break;
      case 'DELETE':
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        $url = $this->payloadToQueryString($url, $payload);
      case 'GET':
        $url = $this->payloadToQueryString($url, $payload);
    }

    // set URL:
    curl_setopt($ch, CURLOPT_URL, $url);
    // set headers:
    $_headers = 0;
    if (!empty($headers)) {
      $_headers = [];
      foreach ($headers as $key => $value) {
        $_headers[] = $key . ": " . $value;
      }
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $_headers);

    $output = (object)[
      'data' => curl_exec($ch),
      'status' => curl_getinfo($ch, CURLINFO_RESPONSE_CODE)
    ];
    curl_close($ch);

    return $output;
  }

  /** 
   * By default, the root path of the templates is at /application/templates. With this method, you can add more directories under that.
   * 
   * @param string $path
   * @return void 
   */
  protected final function setTemplateRoot(string $path)
  {
    if (!empty($path) && substr($path, -1) != "/") $path .= "/";
    $path = ltrim($path, '/');
    $this->templateRoot = $path;
  }

  /** 
   * Sanitizes the a given dataset, specified on $payload, using htmlspecialchars() function, to avoid XSS attacks.
   * 
   * @param mixed $payload
   * @return mixed 
   */
  private function escapeOutput($payload)
  {
    foreach ($payload as &$value) {
      if (gettype($value) == 'array' || (gettype($value) == 'object' && $value instanceof StdClass)) {
        $value = $this->escapeOutput($value);
        continue;
      }

      if (!empty($value)) $value = htmlspecialchars($value);
    }

    return $payload;
  }

  /** 
   * Add a query string data on the given $url, based on the dataset passed in $payload, then returns the resulting URL. 
   * 
   * @param string $url
   * @param array $payload
   * @return string 
   */
  private function payloadToQueryString(string $url, array $payload)
  {
    if (!empty($payload)) {
      if (strpos($url, '?') !== false) {
        $url .= "&" . http_build_query($payload);
      } else {
        $url .= '?' . http_build_query($payload);
      }
    }

    return $url;
  }
}
