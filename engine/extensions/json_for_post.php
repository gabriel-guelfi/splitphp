<?php
function _parseJson()
{
  if ($_SERVER['REQUEST_METHOD'] != 'POST' || strpos($_SERVER['CONTENT_TYPE'], 'application/json') === false) return;

  /* Data comes in on the stdin stream */
  $putdata = fopen("php://input", "r");

  $raw_data = '';

  /* Read the data 1 KB at a time*/
  while ($chunk = fread($putdata, 1024))
    $raw_data .= $chunk;

  /* Close the streams */
  fclose($putdata);

  $_POST = json_decode($raw_data, true);
  $_REQUEST = array_merge($_POST, $_REQUEST);
  return;
}

_parseJson();
