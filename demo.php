<?php

error_reporting(E_ALL);
ini_set('display_errors', TRUE);

$root = realpath(dirname(__FILE__));

//generate some fake data
require_once(implode(DIRECTORY_SEPARATOR, array($root, "gen", "defect_csv_to_array.php")));
$data = defect_csv_to_array(implode(DIRECTORY_SEPARATOR, array($root, "gen", "defects.csv")));
$descriptions = defect_description_csv_to_array(implode(DIRECTORY_SEPARATOR, array($root, "gen", "defect_descriptions.csv")));

$image_location = implode(DIRECTORY_SEPARATOR, array($root, "gen", "hh.jpg"));

$columns = 5;
$rows = 5;
$num_parts = 1477;

//$filter_selections = array(array("column" => 2, "row" => 2), array("column" => 4), array("row" => 3));
$filter_selections = array();

//include classes
require_once(implode(DIRECTORY_SEPARATOR, array($root, "scatterImg.0.0.1.php")));

//Resize image
require_once(implode(DIRECTORY_SEPARATOR, array($root, "gen", "resizeImage.php")));
$image_resource = resizeImage(imagecreatefromjpeg($image_location), false, 450);

$scImg = new scatterImg($image_resource, $data, $descriptions, 
						$columns, $rows, $filter_selections);

$scImg->generateDefectImage(true);
$scImg->displayGridAndTotals($num_parts);
   
$res = '<div><img style="float: left;" src="data:image/jpeg;charset=utf-8;base64,' . $scImg->imgToBase64() . '">';

$color_map = $scImg->getFinalColorMap();

$res .= '<table border="1" cellpadding="15" cellspacing="10" style="float: right;">';

if (isset($color_map) && count($color_map) > 0) {

  for ($j = 0; $j < count($color_map); $j++) {

    $color = $scImg->getColor($color_map[$j]["color"]);
    $RGB_CSS = "rgb(" . $color["red"] . ", " . $color["green"] . ", " . $color["blue"] . ")";

    $res .= '<tr>
              <td>' . $color_map[$j]["defect"] . '</td>
              <td>' . $color_map[$j]["color"] . '</td>
              <td style="background-color:' . $RGB_CSS . '">&nbsp;&nbsp;&nbsp;&nbsp;</td>
            </tr>';

  }

}

$res .= '</table></div><div style="clear: both;"></div> <br />';

echo $res;

?>