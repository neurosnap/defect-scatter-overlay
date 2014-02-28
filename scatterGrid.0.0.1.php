<?php

  class scatterGrid {

    private $section_columns;
    private $section_rows;

    private $image_width;
    private $image_height;

    private $section_width;
    private $section_height;

    private $totals_by_section;

    private $total_parts;
    private $total_defects;

    private $filter_selections;

    // $image_res = (Associative Array) with the following keys: "width" (Int) and "height" (Int) for
    //              dimensions of image
    // $columns = (Int) number of columns for grid overlay
    // $rows = (Int) number of rows for grid overlay
    // $total_parts = (Int) total number of parts being overlayed on image, required for Defects Per Unit calc
    // $filter_selections = (Array of Associative Arrays) tells the grid system to only count up and display totals for 
    //                      sections in this array, expects array("column" => (Int), "row" => (Int))
    public function __construct ($image_res = array(), $columns = 5, $rows = 5, $total_parts = false, $filter_selections = array()) {

      if (count($image_res) === 0)
        die("Error scatterGrid needs the image resolution");

      if (!is_int($image_res["width"]) || !is_int($image_res["height"])) {
        die("Error scatterGrid was not supplied integers in image resolution array,
            width: " . $image_res["width"] . ", height: " . $image_res["height"]);
      }

      if (!is_int($columns) || !is_int($rows))
        die("Error scatterGrid was not supplied integers, columns: " . (string) $columns . ", rows: " . (string) $rows);

      //required for DPU calculation
      if ($total_parts && $total_parts !== 0)
        $this->total_parts = $total_parts;

      $this->section_columns = $columns;
      $this->section_rows = $rows;

      $this->image_width = $image_res["width"];
      $this->image_height = $image_res["height"];

      $this->section_width = $this->image_width / $this->section_columns;
      $this->section_height = $this->image_height / $this->section_rows;

      //total points recorded by section
      $this->totals_by_section = array();

      //only display 
      $this->filter_selections = $filter_selections;

    }

    public function displayGridAndTotals($image_resource, $total_defects) {

      //set thickness for grid lines
      imagesetthickness($image_resource, 2);
      //prepare colors for defect numbers by section and DPU by section
      $gray = imagecolorallocate($image_resource, 128, 128, 128);
      $red = imagecolorallocate($image_resource, 255, 0, 0);

      $first_row_loop = true;
      for ($column = 1; $column <= $this->section_columns; $column++) {

        imageline($image_resource, 
                  ($column * $this->section_width), 
                  0, 
                  ($column * $this->section_width), 
                  $this->image_height, 
                  $gray);

        for ($row = 1; $row <= $this->section_rows; $row++) {

          if ($first_row_loop) {
            imageline($image_resource, 
                      0, 
                      ($row * $this->section_height), 
                      $this->image_width, 
                      ($row * $this->section_height), 
                      $gray);
          }

          $section_total = $this->getSectionTotals(false, false, $column, $row);

          if ($section_total == 0)
            continue;

          if ($section_total >= $total_defects * .10) {
            $color = $red;
          } else {
            $color = $gray;
          }

          if ($this->total_parts) {
            $text = $section_total . " DPU " . number_format($section_total / $this->total_parts, 2);
          } else {
            $text = $section_total;
          }

          imagestring($image_resource, 
                      4, 
                      (($column - 1) * $this->section_width), 
                      (($row - 1) * $this->section_height), 
                      $text, 
                      $color);

        }

        $first_row_loop = false;

      }

      return $image_resource;

    }

    public function getSectionPixels ($column, $row) {

      $this->errorCheckSection($column, $row);

      $response = new stdClass();

      $min_width = ($column - 1) * $this->section_width;
      $max_width = $column * $this->section_width;

      $response->width = array("min" => $min_width, "max" => $max_width);

      $min_height = ($row - 1) * $this->section_height;
      $max_height = $row * $this->section_height;

      $response->height = array("min" => $min_height, "max" => $max_height);

      return $response;

    }

    public function getSection ($x_coord, $y_coord) {

      $this->errorCheckCoords($x_coord, $y_coord);

      /*$column = floor($x_coord / $this->section_width);
      $row = floor($y_coord / $this->section_height);

      if ($column < 1)
        $column = 1;

      if ($row < 1)
        $row = 1;
      
      return array("column" => $column, "row" => $row);*/

      for ($column = 1; $column <= $this->section_columns; $column++) {

        for ($row = 1; $row <= $this->section_rows; $row++) {

          $section = $this->getSectionPixels($column, $row);

          if ($section->width["min"] <= $x_coord && $section->width["max"] > $x_coord) {

            if ($section->height["min"] <= $y_coord && $section->height["max"] > $y_coord) {
              return array("column" => $column, "row" => $row);
            }

          }

        }

      }

    }

    public function addToSectionTotal ($x_coord = false, $y_coord = false, $column = false, $row = false) {

      if ($x_coord && $y_coord) {

        $this->errorCheckCoords($x_coord, $y_coord);
        $current_section = $this->getSection($x_coord, $y_coord);

      } else if ($column && $row) {

        $this->errorCheckSection($column, $row);
        $current_section = array("column" => $column, "row" => $row);

      } else {
        die("You done goofd");
      } 

      //Remove any sections not in the filtered selection list
      if (count($this->filter_selections) > 0) {

        $found_section = false;

        for ($i = 0; $i < count($this->filter_selections); $i++) {
          
          $filter = $this->filter_selections[$i];

          if ($filter["column"] === $current_section["column"]
              && $filter["row"] === $current_section["row"]) {
            $found_section = true;
          }

          if ($filter["column"] === $current_section["column"] 
            && ($filter["row"] === 0 || !array_key_exists("row", $filter))) {
            $found_section = true;
          }

          if ($filter["row"] === $current_section["row"] 
            && ($filter["column"] === 0 || !array_key_exists("column", $filter))) {
            $found_section = true;
          }

          if ($filter["column"] === 0 && $filter["row"] === 0) {
            $found_section = true;
          }
            
        }

        if (!$found_section)
          return false;

      }

      $this->total_defects++;

      if (count($this->totals_by_section) > 0) {

        foreach ($this->totals_by_section as &$section) {
          
          if (floor($current_section["column"]) == floor($section["column"]) 
              && floor($current_section["row"]) == floor($section["row"])) {

            $section["count"] = $section["count"] + 1;
            return $current_section;

          }

        }

        //function did not end therefore totals_by_section 
        //does not have a record of this section so add one
        array_push($this->totals_by_section, 
                  array("column" => $current_section["column"], "row" => $current_section["row"], "count" => 1));

      } else {
        array_push($this->totals_by_section, 
                  array("column" => $current_section["column"], "row" => $current_section["row"], "count" => 1));
      }

      return $current_section;

    }

    public function getSectionTotals ($x_coord = false, $y_coord = false, $column = false, $row = false) {

      if ($x_coord && $y_coord) {

        $this->errorCheckCoords($x_coord, $y_coord);
        $current_section = $this->getSection($x_coord, $y_coord);

      } else if ($column && $row) {

        $this->errorCheckSection($column, $row);
        $current_section = array("column" => $column, "row" => $row);

      } else {
        $current_section = false;
      }

      //var_dump($current_section);

      if (!$current_section) {
        return $this->totals_by_section;
      }

      foreach ($this->totals_by_section as &$section) {

        if ($current_section["column"] == $section["column"] && $current_section["row"] == $section["row"]) {
          return $section["count"];
        }

      }

      return 0;

    }

    public function getSectionDimensions() {
      return array("width" => $this->section_width, "height" => $this->section_height);
    }

    private function errorCheckCoords ($x_coord, $y_coord) {

      $trace = debug_backtrace();
      $function = $trace[1]["function"];

      if (!is_int($x_coord) || !is_int($y_coord))
        die("Error " . $function . " was not supplied integers, x_coord: " . $x_coord . ", y_coord: " . $y_coord);
      
      if ($x_coord > $this->image_width || $y_coord > $this->image_height) {

        //die("Error " . $function . " x (" . $x_coord . ") or y (" . $y_coord . ") 
        //    exceeds width (" . $this->image_width . ") or height (" . $this->image_height . ")");
      
      }

      if ($x_coord < 0 || $y_coord < 0)
        die("Error " . $function . " column or row is less than zero");

    }

    private function errorCheckSection ($column, $row) {

      $trace = debug_backtrace();
      $function = $trace[1]["function"];

      if (!is_int($column) || !is_int($row))
        die("Error " . $function . " was not supplied integers, columns: " . $column . ", rows: " . $row);

      if ($column > $this->section_columns || $row > $this->section_rows)
        die("Error " . $function . " column or row exceeds total number of columns or rows");

      if ($column < 0 || $row < 0)
        die("Error " . $function . " column or row is less than zero");

    }


  }

?>