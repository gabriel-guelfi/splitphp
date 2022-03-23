<?php
abstract class RestService extends Service
{
  protected $routes;
  protected $routeIndex;
  protected $response;
  private $template404;
  private $dblink;
  private $xsrfToken;
  private $antiXsrfValidation;

  public function __construct()
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

    $this->dblink = System::loadClass(INCLUDE_PATH . "/engine/databasemodules/" . DBTYPE . "/class.dblink.php", 'dblink');

    $this->inputRestriction = [
      '/<[^>]*script/mi',
      '/<[^>]*iframe/mi',
      '/<[^>]*on[^>]*=/mi',
      '/{{.*}}/mi',
      '/<[^>]*(ng-.|data-ng.)/mi'
    ];

    $this->antiXsrfValidation = true;
    $this->response = System::loadClass(INCLUDE_PATH . "/engine/class.response.php", 'response');
    parent::__construct();
  }

  public final function execute($route, $httpVerb)
  {
    $response = new Response();

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
    $this->xsrfToken = Utils::dataEncrypt((string) Request::getUserIP(), PRIVATE_KEY);

    $return = null;
    try {
      $endpointHandler = is_callable($routeData->method) ? $routeData->method : [$this, $routeData->method];

      if (DB_CONNECT == "on" && DB_TRANSACTIONAL == "on") {
        $this->dblink->getConnection('writer')->startTransaction();
        $return = $this->respond(call_user_func_array($endpointHandler, [$this->prepareParams($route, $routeData, $httpVerb)]));
        $this->dblink->getConnection('writer')->commitTransaction();
      } else {
        $return = $this->respond(call_user_func_array($endpointHandler, [$this->prepareParams($route, $routeData, $httpVerb)]));
      }
    } catch (Exception $exc) {
      if (DB_CONNECT == "on" && DB_TRANSACTIONAL == "on")
        $this->dblink->getConnection('writer')->rollbackTransaction();

      $status = $this->userFriendlyErrorStatus($exc);
      $err = (object) [
        "error" => true,
        "user_friendly" => $status !== false,
        "message" => $exc->getMessage(),
        "route" => $route,
        "method" => $httpVerb,
        "params" => $this->prepareParams($route, $routeData, $httpVerb, false)
      ];

      if (APPLICATION_LOG)
        System::errorLog('application_error', $exc);

      $return = $this->respond(
        $response
          ->withStatus(($status != false ? $status : 500))
          ->withData($err)
      );
    } finally {
      if (DB_CONNECT == "on")
        $this->dblink->disconnect('writer');
      return $return;
    }
  }

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

  protected final function set404template($path, $args = [])
  {
    $this->template404 = (object) [
      "path" => $path,
      "args" => $args
    ];
  }

  protected final function xsrfToken()
  {
    return $this->xsrfToken;
  }

  protected final function setAntiXsrfValidation(bool $validate)
  {
    $this->antiXsrfValidation = $validate;
  }

  private function routeConfig($route)
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

  private function findRoute($route, $httpVerb)
  {
    foreach ($this->routeIndex as $summary) {
      if (preg_match('/' . $summary->pattern . '/', $route) && $httpVerb == $summary->httpVerb) {
        return $this->routes[$httpVerb][$summary->id];
      }
    }

    return false;
  }

  private function prepareParams($route, $routeData, $httpVerb, $validate = true)
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
        $params = $this->actualizeEmptyValues(array_merge($params, $_REQUEST));
        break;
    }

    if ($routeData->validateInput && $validate) $this->inputValidation($params);

    return $params;
  }

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
              // "store" => $this->getService('store/store')->getInfo(),
              "user" => $this->getService('user/session')->getLoggedUser(),
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

          try {
            $this->getService('mail_service')->sendEmail(new MailObject((object) [
              "fromMail" => SMTP_SENDER_EMAIL,
              "fromName" => APPLICATION_NAME,
              "destMail" => ADMIN_EMAIL,
              "destName" => "System Administrator",
              "subject" => "ALERT - Malware Hazard",
              "body" => json_encode($info)
            ]));
          } catch (Exception $exc) {
            System::errorLog('sys_error', $exc);
          }

          throw new Exception("Invalid input.", 400);
        }
      }
    }
  }

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

    return false;
  }

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

  private function render404()
  {
    $response = new Response();

    $this->respond($response->withStatus(404)->withHTML($this->renderTemplate($this->template404->path, $this->template404->args)));
    die;
  }

  private function xsrfTknFromRequest()
  {
    if (!empty($_SERVER['HTTP_XSRF_TOKEN'])) {
      return $_SERVER['HTTP_XSRF_TOKEN'];
    }

    $xsrfToken = !empty($_REQUEST['XSRF_TOKEN']) ? $_REQUEST['XSRF_TOKEN'] : $_REQUEST['xsrf_token'];
    if (!empty($xsrfToken)) return $xsrfToken;

    return null;
  }

  private function antiXsrfValidation($routeData)
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
