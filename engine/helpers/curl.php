<?php

namespace engine\helpers;

use \engine\Helpers;
use Exception;

class Curl
{
  private $headers = [];
  private $rawData;
  private $payload;
  private $httpVerb;
  private $url;

  public function setHeader(string $header)
  {
    if (in_array($header, $this->headers) == false)
      $this->headers[] = $header;

    return $this;
  }

  public function setData($data)
  {
    $this->rawData = $data;
    $this->payload = http_build_query($data);
    return $this;
  }

  public function setDataAsJson($data)
  {
    $this->rawData = $data;
    $this->payload = json_encode($data);
    $this->setHeader('Content-Type:application/json');
    return $this;
  }

  public function post($url)
  {
    return $this->request("POST", $url);
  }

  public function put($url)
  {
    return $this->request("PUT", $url);
  }

  public function patch($url)
  {
    return $this->request("PATCH", $url);
  }

  public function del($url)
  {
    return $this->request("DELETE", $url);
  }

  public function get($url)
  {
    return $this->request("GET", $url);
  }

  public function request($httpVerb, $url)
  {
    $this->httpVerb = $httpVerb;
    $this->url = $url;

    // Validates $httpVerb:
    if (!in_array($httpVerb, ['POST', 'PUT', 'PATCH', 'DELETE', 'GET']))
      throw new Exception("Invalid HTTP verb.");

    // basic setup:
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // set options by http verb:
    $httpVerb = strtoupper($httpVerb);
    switch ($httpVerb) {
      case 'POST':
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,  $this->payload);
        break;
      case 'PUT':
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS,  $this->payload);
        break;
      case 'PATCH':
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
        curl_setopt($ch, CURLOPT_POSTFIELDS,  $this->payload);
        break;
      case 'DELETE':
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS,  $this->payload);
        break;
      case 'GET':
        if (!empty($this->headers) && in_array('Content-Type:application/json', $this->headers))
          throw new Exception("It is not possible to send JSON data through GET requests.");

        $url = strpos($url, '?') !== false ? $url . '&' . $this->payload : $url . '?' . $this->payload;
        break;
    }

    // Set Headers:
    if (!empty($this->headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);

    // Execute and return:
    curl_setopt($ch, CURLOPT_URL, $url);

    $output = (object)[
      'data' => json_decode(curl_exec($ch), true),
      'status' => curl_getinfo($ch, CURLINFO_RESPONSE_CODE)
    ];
    curl_close($ch);

    $this->log($output);

    $this->headers = [];
    $this->rawData = null;
    $this->payload = null;
    $this->httpVerb = null;
    $this->url = null;

    return $output;
  }

  private function log($output)
  {
    $logObj = [
      "datetime" => date('Y-m-d H:i:s'),
      "url" => $this->url,
      "httpVerb" => $this->httpVerb,
      "headers" => $this->headers,
      "rawData" => $this->rawData,
      "payload" => $this->payload,
      "output" => $output
    ];

    Helpers::Log()->add('curl', $logObj);
  }
}