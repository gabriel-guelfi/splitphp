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

/**
 * Class Request
 * 
 * This class if for capturing the incoming requests and managing its informations.
 *
 * @package engine
 */
class Request
{
  /**
   * @var string $route
   * Stores the current accessed route.
   */
  private $route;

  /**
   * @var string $restServicePath
   * Stores the defined RestService class path.
   */
  private $restServicePath;

  /**
   * @var string $restServiceName
   * Stores the defined RestService class name.
   */
  private $restServiceName;

  /**
   * @var array $args
   * Stores the parameters and data passed along the request.
   */
  private $args;

  /** 
   * Parse the incoming URI, separating DNS, Rest Service's path and name, route and arguments. Returns an instance of the Request class (constructor).
   * 
   * @param string $uri
   * @return Request 
   */
  public final function __construct(string $uri)
  {
    $urlElements = explode("/", str_replace(strrchr(urldecode($uri), "?"), "", urldecode($uri)));
    array_shift($urlElements);

    // If no route is found under URL, set it as default route:
    if (empty($urlElements[0])) {
      $urlElements = explode('/', str_replace(strrchr(urldecode(DEFAULT_ROUTE), "?"), "", urldecode(DEFAULT_ROUTE)));
      array_shift($urlElements);
    }

    $this->restServiceFindAndSet('/application/routes/', $urlElements);

    $this->args = [
      $this->route,
      $_SERVER['REQUEST_METHOD']
    ];
  }

  /** 
   * Returns the stored route.
   * 
   * @return string 
   */
  public function getRoute()
  {
    return $this->route;
  }

  /** 
   * Returns an object containing the name and the path of the Rest Service class.
   * 
   * @return object 
   */
  public function getRestService()
  {
    return (object) [
      "name" => $this->restServiceName,
      "path" => $this->restServicePath
    ];
  }

  /** 
   * Returns the parameters and data passed along the request.
   * 
   * @return array 
   */
  public function getArgs()
  {
    return $this->args;
  }

  /** 
   * Returns the client's IP.
   * 
   * @return string 
   */
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

  /** 
   * Using $path as a base, loops through the $urlElements searching for a valid Rest Service filepath. Once it is found, define the 
   * Rest Service's path and name, and the rest of the remaining elements up to that point are defined as the route.
   * 
   * @param string $path
   * @param array $urlElements
   * @return void 
   */
  private function restServiceFindAndSet(string $path, array $urlElements)
  {
    $basePath = "";
    if (strpos($path, INCLUDE_PATH)) {
      $basePath = $path;
    } else {
      $basePath = INCLUDE_PATH . $path;
    }

    foreach ($urlElements as $i => $urlPart) {
      if (is_dir($basePath . $urlPart))
        $basePath .= $urlPart . '/';
      elseif (is_file($basePath . $urlPart . '.php')) {
        $this->restServicePath = $basePath;
        $this->restServiceName = $urlPart;
        $this->route = '/' . implode('/', array_slice($urlElements, $i + 1));
        break;
      } else {
        http_response_code(404);
        die;
      }
    }
  }
}
