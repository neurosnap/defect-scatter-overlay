<?php

function resizeImage($image_resource, $new_width = false, $new_height = false) {

  if (!$new_width && !$new_height)
    die("Error resizeImage missing new height or width");

  //list($img_width, $img_height) = getimagesize($file);
  $img_width = imagesx($image_resource);
  $img_height = imagesy($image_resource);

  $ratio = $img_width / $img_height;

  //adjust width or height to match ratio and new width or height
  if (!$new_width) {
    //$new_width = ceil(abs($new_height - $img_height) * $ratio + $img_width);
    $new_width = ceil($img_width * ($new_height / $img_height));
  }

  if (!$new_height) {
    $new_height = ceil($img_height * ($new_width / $img_width));
  }

  $dest = imagecreatetruecolor($new_width, $new_height);

  imagecopyresampled($dest, $image_resource, 0, 0, 0, 0, $new_width, $new_height, $img_width, $img_height);

  return $dest;

}

?>