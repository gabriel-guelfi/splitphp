<?php

class Service extends Dao
{
  protected $utils;

  public function __construct()
  {
    parent::__construct();

    $this->utils = System::loadClass(INCLUDE_PATH . "/engine/class.utils.php", "utils");
  }
  protected final function getService(string $path, array $args = [])
  {
    @$className = strpos($path, '/') ? end(explode('/', $path)) : $path;

    return System::loadClass(INCLUDE_PATH . '/application/services/' . $path . '.php', $className, $args);
  }

  protected final function renderTemplate(string $path, array $varlist = [])
  {
    if (!empty($varlist)) extract($this->escapeOutput($varlist));

    ob_start();
    include INCLUDE_PATH . "/application/templates/" . $path . ".php";

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
