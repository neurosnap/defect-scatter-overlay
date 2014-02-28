<?php

function defect_csv_to_array($location) {

  $file = file_get_contents($location);

  if ($file === false)
    die("File could not be read.");

  $lines = explode("\n", $file);

  $header = array();
  $response = array();

  foreach ($lines as $key => &$value) {

    $values = explode(",", $value);

    if ($key === 0) {

      foreach ($values as $keys) {
        array_push($header, $keys);
      }

      continue;

    }

    $arr = array();
    for ($j = 0; $j < count($header); $j++) {

      for ($i = 0; $i < count($values); $i++) {

        if ($j === $i) {
          $arr[$header[$j]] = $values[$i];
          break;
        }

      }

    }

    array_push($response, $arr);

  }

  return array_slice($response, 1, -1);

}

function defect_description_csv_to_array($location) {

  $file = file_get_contents($location);

  if ($file === false)
    die("File could not be read.");

  $lines = explode("\n", $file);

  $response = array();

  foreach ($lines as $key => &$value) {
    array_push($response, trim($value));
  }

  return $response;

}

?>