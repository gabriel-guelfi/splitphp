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

use Exception;
use stdClass;
use \engine\exceptions\DatabaseException;

/**
 * Class RestService
 * 
 * This class aims to provide an interface where the developer creates the application's API layer, defines its endpoints, handles the requests and builds
 * standardized responses. Here's where the RESTful magic happens.
 *
 * @package engine
 */
abstract class WebService extends Service
{
  /**
   * @var array $routes
   * Stores a list of endpoint routes.
   */
  protected $routes;

  /**
   * @var array $routeIndex
   * This is a summary for the $routes list.
   */
  protected $routeIndex;

  /**
   * @var Response $response
   * Stores an instance of the class Response, used to build the standardized responses.
   */
  protected $response;

  /**
   * @var object $template404
   * Stores an object containing information about a pre-defined 404 template.
   */
  private $template404;

  /**
   * @var Dblink $template404
   * Stores an instance of the class Dblink, used to perform database connections and operations.
   */
  private $dblink;

  /**
   * @var string $xsrfToken
   * Stores a automatically generated dynamic token, which is used to authenticate requests and ensure that the request
   * is coming from an authorized application.
   */
  private $xsrfToken;

  /**
   * @var boolean $antiXsrfValidation
   * This flag is a control to whether the requests received by the Web Service shall be authenticates by a XSRF token or not. Default = true.
   */
  private $antiXsrfValidation;

  /**
   * @var array $inputRestriction
   * This is an array of regex patterns that will be used against request payloads to check for potentially harmful data.
   */
  private $inputRestriction;

  /** 
   * Defines constants for user errors, set properties with their initial values, instantiate other classes, then returns an
   * instance of the Web Service(constructor).
   * 
   * @return WebService 
   */
  public final function __construct()
  {
    require_once __DIR__ . '/class.response.php';

    define('VALIDATION_FAILED', 1);
    define('BAD_REQUEST', 2);
    define('NOT_AUTHORIZED', 3);
    define('NOT_FOUND', 4);
    define('PERMISSION_DENIED', 5);
    define('CONFLICT', 6);

    $this->routes = [
      "GET" => [],
      "POST" => [],
      "PUT" => [],
      "DELETE" => []
    ];

    $this->routeIndex = [];

    if (DB_CONNECT == 'on')
      $this->dblink = System::loadClass(ROOT_PATH . "/engine/databasemodules/" . DBTYPE . "/class.dblink.php", 'dblink');

    $this->inputRestriction = [
      '/<[^>]*script/mi',
      '/<[^>]*iframe/mi',
      '/<[^>]*on[^>]*=/mi',
      '/{{.*}}/mi',
      '/<[^>]*(ng-.|data-ng.)/mi'
    ];
    
    $this->xsrfToken = Utils::dataEncrypt((string) Request::getUserIP(), PRIVATE_KEY);
    $this->antiXsrfValidation = true;
    $this->response = System::loadClass(ROOT_PATH . "/engine/class.response.php", 'response');
    parent::__construct();
  }

  /** 
   * Returns a string representation of this class for printing purposes.
   * 
   * @return string 
   */
  public function __toString()
  {
    return "class:WebService:" . __CLASS__ . "()";
  }

  /** 
   * Checks for allowed HTTP verbs, searches for the request's route in added routes list, generate a new XSRF token, executes the 
   * handler method provided for the endpoint, then respond the request with the response returned from this handler method.
   * 
   * @param string $route
   * @param string $httpVerb
   * @return Response 
   */
  public final function execute(string $route, string $httpVerb)
  {
    if ($httpVerb != 'GET' && $httpVerb != 'POST' && $httpVerb != 'PUT' && $httpVerb != 'DELETE') {
      http_response_code(405);
      die;
    }
    
    $routeData = $this->findRoute($route, $httpVerb);
    if (empty($routeData)) {
      if (!empty($this->template404)) $this->render404();
      
      http_response_code(404);
      die;
    }

    $this->antiXsrfValidation($routeData);

    try {
      $endpointHandler = is_callable($routeData->method) ? $routeData->method : [$this, $routeData->method];

      if (DB_CONNECT == "on" && DB_TRANSACTIONAL == "on") {
        $this->dblink->getConnection('writer')->startTransaction();
        $this->respond(call_user_func_array($endpointHandler, [$this->prepareParams($route, $routeData, $httpVerb)]));
        $this->dblink->getConnection('writer')->commitTransaction();
      } else {
        $this->respond(call_user_func_array($endpointHandler, [$this->prepareParams($route, $routeData, $httpVerb)]));
      }
    } catch (Exception $exc) {
      if (DB_CONNECT == "on" && DB_TRANSACTIONAL == "on" && $this->dblink->checkConnection('writer'))
        $this->dblink->getConnection('writer', false)->rollbackTransaction();

      if (APPLICATION_LOG == "on") {
        if ($exc instanceof DatabaseException) {
          System::errorLog('db_error', $exc, [
            'sqlState' => $exc->getSqlState(),
            'sqlCommand' => $exc->getSqlCmd()
          ]);
        } else {
          System::errorLog('application_error', $exc);
        }
      }

      $status = $this->userFriendlyErrorStatus($exc);
      $this->respond(
        $this->response
          ->withStatus($status)
          ->withData([
            "error" => true,
            "user_friendly" => $status !== 500,
            "message" => $exc->getMessage(),
            "webService" => System::$webservicePath,
            "route" => $route,
            "method" => $httpVerb,
            "params" => $this->prepareParams($route, $routeData, $httpVerb, false)
          ])
      );
    } finally {
      if (DB_CONNECT == "on")
        $this->dblink->disconnect('writer');
    }
  }

  /** 
   * Registers an endpoint on the list $routes, in other words: makes an endpoint available within the Web Service, with the 
   * HTTP verb, route and handler method provided.
   * 
   * @param string $httpVerb
   * @param string $route
   * @param mixed $method
   * @param boolean $antiXsrf = null
   * @param boolean $validateInput = true
   * @return void 
   */
  protected final function addEndpoint(string $httpVerb, string $route, $method, bool $antiXsrf = null, bool $validateInput = true)
  {
    if (!array_key_exists($httpVerb, $this->routes))
      throw new Exception("Attempt to add endpoint with an unknown http method.");

    if ($this->findRoute($route, $httpVerb) !== false)
      throw new Exception("Attempt to add duplicate endpoint (same route and same http method). (" . $httpVerb . " -> " . $route . ")");

    $endpointId = uniqid();
    $routeConfigs = $this->routeConfig($route);

    $this->routeIndex[] = (object) [
      "id" => $endpointId,
      "pattern" => $routeConfigs->pattern,
      "httpVerb" => $httpVerb
    ];

    $this->routes[$httpVerb][$endpointId] = (object) [
      "route" => $route,
      "verb" => $httpVerb,
      "method" => $method,
      "params" => $routeConfigs->params,
      "validateInput" => $validateInput,
      "antiXsrf" => is_null($antiXsrf) ? $this->antiXsrfValidation : $antiXsrf
    ];
  }

  /** 
   * Responds the request setting content type, payload, status code and headers according to the Response object passed as parameter.
   * 
   * @param Response $res
   * @return Response 
   */
  protected final function respond(Response $res)
  {
    http_response_code($res->getStatus());

    if (!empty($res->getData())) {
      header('Content-Type: ' . $res->getContentType());
      header('Xsrf-Token: ' . $this->xsrfToken());
      foreach ($res->getHeaders() as $header) header($header);
      echo $res->getData();
    }

    return $res;
  }

  /** 
   * Sets the information of a template that will be rendered in case of a 404 (not found) status.
   * 
   * @param string $path
   * @param array $args = []
   * @return void 
   */
  protected final function set404template(string $path, array $args = [])
  {
    $this->template404 = (object) [
      "path" => $path,
      "args" => $args
    ];
  }

  /** 
   * Returns the auto-generated XSRF token.
   * 
   * @return string 
   */
  protected final function xsrfToken()
  {
    return $this->xsrfToken;
  }

  /** 
   * Turn on/off the Anti XSRF validation.
   * 
   * @param boolean $validate
   * @return void 
   */
  protected final function setAntiXsrfValidation(bool $validate)
  {
    $this->antiXsrfValidation = $validate;
  }

  /** 
   * Configure the settings of the request's route, separating what is route and what is parameter. Returns an object containing 
   * the route and the parameters with their values set accordingly.
   * 
   * @param string $route
   * @return object 
   */
  private function routeConfig(string $route)
  {
    $result = (object) [
      "pattern" => '',
      "params" => []
    ];

    $tmp = explode('/', $route);
    foreach ($tmp as $i => $routePart) {
      if (preg_match('/\?.*\?/', $routePart)) {
        $tmp[$i] = preg_replace('/\?.*\?/', '.*', $routePart);
        $result->params[] = (object) [
          "index" => $i,
          "paramKey" => str_replace('?', '', $routePart)
        ];
      }
    }

    $result->pattern = implode('\/', $tmp);
    return $result;
  }

  /** 
   * Using the request's URL, Searches for a route in the routes's summary, where the URL and HTTP verb matches with the pattern and verb 
   * registered on the endpoint. Returns the route data or false, in case of not founding it.
   * 
   * @param string $route
   * @param string $httpVerb
   * @return object|boolean 
   */
  private function findRoute(string $route, string $httpVerb)
  {
    foreach ($this->routeIndex as $summary) {
      if (preg_match('/' . $summary->pattern . '/', $route) && $httpVerb == $summary->httpVerb) {
        return $this->routes[$httpVerb][$summary->id];
      }
    }

    return false;
  }

  /** 
   * Merges the request's payload data with the parameters received in line on the route and returns this merged array of data. If the 
   * flag $validate is set to true, performs a check for potentially harmful data.
   * 
   * @param string $route
   * @param object $routeData
   * @param string $httpVerb
   * @param boolean $validate = true
   * @return array
   */
  private function prepareParams(string $route, object $routeData, string $httpVerb, bool $validate = true)
  {
    $params = [];

    $routeInput = explode('/', $route);
    foreach ($routeData->params as $param) {
      $params[$param->paramKey] = $routeInput[$param->index];
    }

    switch ($httpVerb) {
      case 'GET':
        $params = $this->actualizeEmptyValues(array_merge($params, $_GET));
        break;
      case 'POST':
        $params = $this->actualizeEmptyValues(array_merge($params, array_merge($_POST, $_GET)));
        break;
      case 'PUT':
        global $_PUT;
        $params = $this->actualizeEmptyValues(array_merge($params, array_merge($_PUT, $_GET)));
        break;
      case 'DELETE':
        $params = $this->actualizeEmptyValues(array_merge($params, $_GET));
        break;
    }

    if ($routeData->validateInput && $validate) $this->inputValidation($params);

    return $params;
  }

  /** 
   * Performs a check for potentially harmful data within $input. If found, log information about it and throws exception.
   * 
   * @param mixed $input
   * @return void
   */
  private function inputValidation($input)
  {
    foreach ($input as $content) {
      if (gettype($content) == 'array' || (gettype($content) == 'object' && $content instanceof StdClass)) {
        $this->inputValidation($content);
        continue;
      }

      foreach ($this->inputRestriction as $pattern) {
        if (gettype($content) == 'string' && preg_match($pattern, $content, $matches)) {
          global $_PUT;
          $info = (object) [
            "info" => (object) [
              "log_time" => date('d/m/Y H:i:s'),
              "message" => "Someone has attempted to submit possible malware whithin a request payload.",
              "suspicious_content" => $matches,
              "client" => (object) [
                "user_agent" => $_SERVER['HTTP_USER_AGENT'],
                "ip" => $_SERVER['REMOTE_ADDR'],
                "port" => $_SERVER['REMOTE_PORT'],
              ],
              "server" => (object) [
                "ip" => $_SERVER['SERVER_ADDR'],
                "port" => $_SERVER['SERVER_PORT'],
              ],
              "request" => (object) [
                "time" => date('d/m/Y H:i:s', strtotime($_SERVER['REQUEST_TIME'])),
                "server_name" => $_SERVER['SERVER_NAME'],
                "uri" => $_SERVER['REQUEST_URI'],
                "query_string" => $_SERVER['QUERY_STRING'],
                "method" => $_SERVER['REQUEST_METHOD'],
                "params" => (object) [
                  "GET" => $_GET,
                  "POST" => $_POST,
                  "PUT" => $_PUT,
                  "REQUEST" => $_REQUEST,
                ],
                "upload" => $_FILES,
                "cookie" => $_COOKIE
              ]
            ]
          ];

          System::log('security', json_encode($info));

          throw new Exception("Invalid input.", 400);
        }
      }
    }
  }

  /** 
   * Returns an integer representing a specific http status code for predefined types of exceptions. Defaults to 500.
   * 
   * @param Exception $exc
   * @return integer
   */
  private function userFriendlyErrorStatus(Exception $exc)
  {
    switch ($exc->getCode()) {
      case (int) VALIDATION_FAILED:
        return 422;
        break;
      case (int) BAD_REQUEST:
        return 400;
        break;
      case (int) NOT_AUTHORIZED:
        return 401;
        break;
      case (int) NOT_FOUND:
        return 404;
        break;
      case (int) PERMISSION_DENIED:
        return 403;
        break;
      case (int) CONFLICT:
        return 409;
        break;
    }

    return 500;
  }

  /** 
   * Nullify string representations of empty values, like 'null' or 'undefined', then returns the modified dataset.
   * 
   * @param mixed $data
   * @return mixed
   */
  private function actualizeEmptyValues($data)
  {
    foreach ($data as $key => $value) {
      if (gettype($value) == 'array' || (gettype($value) == 'object' && $value instanceof StdClass)) {
        $data[$key] = $this->actualizeEmptyValues($data[$key]);
        continue;
      }

      if ($value === 'null' || $value === 'undefined') $data[$key] = null;
    }

    return $data;
  }

  /** 
   * Responds the request with the rendered pre defined template for 404 cases.
   * 
   * @return void
   */
  private function render404()
  {
    $response = new Response();

    $this->respond($response->withStatus(404)->withHTML($this->renderTemplate($this->template404->path, $this->template404->args)));
    die;
  }

  /** 
   * Searches within request's data for the XSRF token and returns it. If not found, returns null.
   * 
   * @return string
   */
  private function xsrfTknFromRequest()
  {
    if (!empty($_SERVER['HTTP_XSRF_TOKEN'])) {
      return $_SERVER['HTTP_XSRF_TOKEN'];
    }

    $xsrfToken = !empty($_REQUEST['XSRF_TOKEN']) ? $_REQUEST['XSRF_TOKEN'] : (!empty($_REQUEST['xsrf_token']) ? $_REQUEST['xsrf_token'] : null);
    if (!empty($xsrfToken)) return $xsrfToken;

    return null;
  }

  /** 
   * Authenticates the XSRF token received on the request is valid.
   * 
   * @param object $routeData
   * @return void
   */
  private function antiXsrfValidation(object $routeData)
  {
    // Whether the request must check XSRF token:
    if ($routeData->antiXsrf) {
      $tkn = $this->xsrfTknFromRequest();

      // Check if there is a token
      if (empty($tkn)) {
        http_response_code(401);
        die;
      }

      // Check the token's authenticity
      if (Utils::dataDecrypt($tkn, PRIVATE_KEY) != Request::getUserIP()) {
        http_response_code(401);
        die;
      }
    }
  }
}