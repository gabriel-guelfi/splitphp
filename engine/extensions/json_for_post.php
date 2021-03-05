<?php
function _parseJson()
{
  if ($_SERVER['REQUEST_METHOD'] != 'POST' || strpos($_SERVER['CONTENT_TYPE'], 'application/json') === false) return;

  /* PUT data comes in on the stdin stream */
  $putdata = fopen("php://input", "r");

  /* Open a file for writing */
  // $fp = fopen("myputfile.ext", "w");

  $raw_data = '';

  /* Read the data 1 KB at a time
       and write to the file */
  while ($chunk = fread($putdata, 1024))
    $raw_data .= $chunk;

  /* Close the streams */
  fclose($putdata);

  $_POST = json_decode($raw_data, true);
  return;
}

_parseJson();
