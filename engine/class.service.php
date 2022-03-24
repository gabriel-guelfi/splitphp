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

class Service extends Dao
{
  protected $utils;
  private $templateRoot;

  public function __construct()
  {
    parent::__construct();

    $this->templateRoot = "";

    $this->utils = System::loadClass(INCLUDE_PATH . "/engine/class.utils.php", "utils");

    $this->init();
  }

  protected function init()
  {
  }

  protected final function getService(string $path, array $args = [])
  {
    @$className = strpos($path, '/') ? end(explode('/', $path)) : $path;

    return System::loadClass(INCLUDE_PATH . '/application/services/' . $path . '.php', $className, $args);
  }

  protected final function renderTemplate(string $path, array $varlist = [])
  {
    if (!empty($varlist)) extract($this->escapeOutput($varlist));
    $path = ltrim($path, '/');

    ob_start();
    include INCLUDE_PATH . "/application/templates/" . $this->templateRoot . $path . ".php";

    return ob_get_clean();
  }

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

  protected final function setTemplateRoot(string $path)
  {
    if (!empty($path) && substr($path, -1) != "/") $path .= "/";
    $path = ltrim($path, '/');
    $this->templateRoot = $path;
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
