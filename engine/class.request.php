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

class Request
{
  private $route;
  private $restServicePath;
  private $restServiceName;
  private $args;

  public function __construct(string $uri)
  {
    $urlElements = explode("/", str_replace(strrchr($uri, "?"), "", urldecode($uri)));
    array_shift($urlElements);

    $this->setRestService('/application/routes/', $urlElements);

    $this->args = [
      $this->route,
      $_SERVER['REQUEST_METHOD']
    ];
  }

  public function getRoute()
  {
    return $this->route;
  }

  public function getRestService()
  {
    return (object) [
      "name" => $this->restServiceName,
      "path" => $this->restServicePath
    ];
  }

  public function getArgs()
  {
    return $this->args;
  }

  public static function getUserIP()
  {
    //whether ip is from the share internet  
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    //whether ip is from the proxy  
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    //whether ip is from the remote address  
    else {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
  }

  private function setRestService(string $path, $urlElements)
  {
    $basePath = "";
    if (strpos($path, INCLUDE_PATH)) {
      $basePath = $path;
    } else {
      $basePath = INCLUDE_PATH . $path;
    }

    if (empty($urlElements[0])) {
      $this->restServicePath = $basePath;
      $this->restServiceName = DEFAULT_REST_SERVICE;
      $this->route = DEFAULT_ROUTE;

      return;
    }

    foreach ($urlElements as $i => $urlPart) {
      if (is_dir($basePath . $urlPart))
        $basePath .= $urlPart.'/';
      elseif (is_file($basePath . $urlPart . '.php')) {
        $this->restServicePath = $basePath;
        $this->restServiceName = $urlPart;
        $this->route = '/'.implode('/', array_slice($urlElements, $i +1));
        break;
      }
    }
  }
}
