<?php

  class scatterImg {

    private $data;
    private $full_path;

    private $color_ref;
    private $final_color_map;

    private $descriptions;

    //private $image_width;
    //private $image_height;

    private $section_columns;
    private $section_rows;

    private $image_resource;

    //  $data = (Associative Array) with the following keys: "area_x" => (Int), "area_y" => (Int), and "description" => (String)
    //  $descriptions = (Array of Strings) of the defect descriptions
    public function __construct($data, $descriptions) {

      clearstatcache();

      //PHP GD library required to generate a scatter plot
      if (!in_array('gd', get_loaded_extensions()))
        die("Error libgd is not loaded");

      if (!$data)
        die('Error no data selected!');

      if (!$descriptions)
        die('Error no array of defect descriptions found');

      $this->data = $data;

      //This is an array of all the descriptions that will
      //  be mapped to specific colors
      //This allows us to keep a consistent color to defect 
      //  correspondence
      $this->descriptions = $descriptions;
  
      //Probably not the best way to do this but the 
      //  color_ref is an associative array and we really need
      //  an indexed array to map the color description to the defect
      $colors = array();
      //Associative array that links a color name 
      //  to RGB values for that color
      $this->color_ref = $this->getColorSwatch();
      foreach ($this->color_ref as $key => $value) {
        array_push($colors, $key);
      }
      
      //This is the map that links a color to a specific type of defect
      $this->color_map = array();
      for ($j = 0; $j < count($this->descriptions); $j++) {
        array_push($this->color_map, array("color" => $colors[$j], "defect" => $this->descriptions[$j]));
      }

    }

    //Takes the image, defect (x,y) positions and plots
    //  the defects as dots on the image
    //Also calculates the defects per unit
    //  if number of parts was included in the new instance
    //  of the class
    public function generateDefectImage($image_resource, $scatterGrid = false) {

      //Do we have data?
      if (count($this->data) > 0) {

        //final colors and their associated defects
        $this->final_color_map = array();
        //temporary array to search for existing defects
        $tmp_desc = array();

        foreach($this->data as $key => &$defect) {

          if (!$this->isAssoc($defect))
            continue;

          $color = $this->getColor("black"); 

          $def_desc = $defect['description'];

          //add defect point to section totals
          //if data point gets added to a section
          if ($scatterGrid) {
            
            $added = $scatterGrid->addToSectionTotal((int) $defect["area_x"], (int) $defect["area_y"]);

            if (!$added) {
              //remove defect from data
              unset($this->data[$key]);
              continue;
            }

            $this->data[$key]["section"] = $added;

          }

          for ($i = 0; $i < count($this->color_map); $i++) {

            if ($this->color_map[$i]["defect"] == $def_desc) {

              //cannot find the defect in the temporary array?
              if (array_search($def_desc, $tmp_desc) === false) {

                //save final defects found and their colors
                array_push($this->final_color_map, 
                          array("color" => $this->color_map[$i]["color"], "defect" => $this->color_map[$i]["defect"]));

                array_push($tmp_desc, $def_desc);

              }

              $color = $this->getColor($this->color_map[$i]["color"]);

              break;

            }

          }

          //plot the defect point
          imagefilledellipse($image_resource, 
                            $defect['area_x'], 
                            $defect['area_y'], 
                            8, 
                            8, 
                            imagecolorallocate($image_resource, $color['red'], $color['green'], $color['blue']));


        }

        //reindex data array
        $this->data = array_values($this->data);

      } else {

        //error no data found
        imagestring($image_resource, 
                    5, 
                    0, 
                    10, 
                    "ERROR LOADING PLOT DATA", 
                    imagecolorallocate($image_resource, 255, 0, 0));

      }

      //set text color to black and display total defects that displays
      //in the upper-left corner
      $textcolor = imagecolorallocate($image_resource, 0, 0, 0);
      imagestring($image_resource, 5, 0, 0 , count($this->data), $textcolor);

      return $image_resource;

    }

    public function imgToBase64($image_resource = false) {

      if (get_resource_type($image_resource) !== "gd")
        die("image_res is not a GD image resource.");

      //Turn on output buffering
      ob_start();

      $test = imagejpeg($image_resource);

      if (!$test) {
        die('Image resource did not work!');
      }

      $image_data = ob_get_contents();

      //Turn off output buffering
      ob_end_clean();

      //Convert to base64 so inject inside an HTML image tag
      $img_base64 = base64_encode($image_data);

      return $img_base64;

    }

    //getColor returns an associative array with the red, green and blue
    //  values of the desired color
    public function  getColor($c_name) {
      return  $this->color_ref[$c_name];
    }

    public function getColorMap() {
      return $this->color_map;
    }

    //Only return color map of the defects that were used
    public function getFinalColorMap() {

      //return $this->final_color_map;
      $response = array();

      foreach ($this->final_color_map as $map) {

        //$obj = new stdClass();
        
        $obj = $map;

        $obj["rgb"] = $this->getColor($map["color"]);

        array_push($response, $obj);

      }

      return $response;

    }

    //Correspondence to get color or defect name
    public function getColorMapItem($item) {

      for ($i = 0; $i < count($this->color_map); $i++) {

        if ($item == $this->color_map[$i]['color']) {
          return $this->color_map[$i]['defect'];
        }

        if ($item == $this->color_map[$i]['defect']) {
          return $this->color_map[$i]['color'];
        }

      }

    }

    public function getData() {
      return $this->data;
    }

    public function isAssoc($arr) {
      return array_keys($arr) !== range(0, count($arr) - 1);
    }

    //Color name to RGB color in HEX
    private function getColorSwatch() {

      return array(
        "black"=>array( "red"=>0x00,  "green"=>0x00,  "blue"=>0x00),
        "maroon"=>array( "red"=>0x80,  "green"=>0x00,  "blue"=>0x00),
        "green"=>array( "red"=>0x00,  "green"=>0x80,  "blue"=>0x00),
        "olive"=>array( "red"=>0x80,  "green"=>0x80,  "blue"=>0x00),
        "navy"=>array( "red"=>0x00,  "green"=>0x00,  "blue"=>0x80),
        "purple"=>array( "red"=>0x80,  "green"=>0x00,  "blue"=>0x80),
        "teal"=>array( "red"=>0x00,  "green"=>0x80,  "blue"=>0x80),
        "gray"=>array( "red"=>0x80,  "green"=>0x80,  "blue"=>0x80),
        "silver"=>array( "red"=>0xC0,  "green"=>0xC0,  "blue"=>0xC0),
        "red"=>array( "red"=>0xFF,  "green"=>0x00,  "blue"=>0x00),
        "darkgoldenrod"=>array( "red"=>0xB8,  "green"=>0x86,  "blue"=>0x0B),
        "yellow"=>array( "red"=>0xFF,  "green"=>0xFF,  "blue"=>0x00),
        "blue"=>array( "red"=>0x00,  "green"=>0x00,  "blue"=>0xFF),
        "fuchsia"=>array( "red"=>0xFF,  "green"=>0x00,  "blue"=>0xFF),
        "aqua"=>array( "red"=>0x00,  "green"=>0xFF,  "blue"=>0xFF),
        "white"=>array( "red"=>0xFF,  "green"=>0xFF,  "blue"=>0xFF),
        "tomato"=>array( "red"=>0xFF,  "green"=>0x63,  "blue"=>0x47),
        "antiquewhite"=>array( "red"=>0xFA,  "green"=>0xEB,  "blue"=>0xD7),
        "aquamarine"=>array( "red"=>0x7F,  "green"=>0xFF,  "blue"=>0xD4),
        "brown"=>array( "red"=>0xA5,  "green"=>0x2A,  "blue"=>0x2A),
        "beige"=>array( "red"=>0xF5,  "green"=>0xF5,  "blue"=>0xDC),
        "blueviolet"=>array( "red"=>0x8A,  "green"=>0x2B,  "blue"=>0xE2),
        "azure"=>array( "red"=>0xF0,  "green"=>0xFF,  "blue"=>0xFF),
        "burlywood"=>array( "red"=>0xDE,  "green"=>0xB8,  "blue"=>0x87),
        "cadetblue"=>array( "red"=>0x5F,  "green"=>0x9E,  "blue"=>0xA0),
        "chartreuse"=>array( "red"=>0x7F,  "green"=>0xFF,  "blue"=>0x00),
        "chocolate"=>array( "red"=>0xD2,  "green"=>0x69,  "blue"=>0x1E),
        "coral"=>array( "red"=>0xFF,  "green"=>0x7F,  "blue"=>0x50),
        "cornflowerblue"=>array( "red"=>0x64,  "green"=>0x95,  "blue"=>0xED),
        "cornsilk"=>array( "red"=>0xFF,  "green"=>0xF8,  "blue"=>0xDC),
        "crimson"=>array( "red"=>0xDC,  "green"=>0x14,  "blue"=>0x3C),
        "darkblue"=>array( "red"=>0x00,  "green"=>0x00,  "blue"=>0x8B),
        "darkcyan"=>array( "red"=>0x00,  "green"=>0x8B,  "blue"=>0x8B),
        "lightgrey"=>array( "red"=>0xD3,  "green"=>0xD3,  "blue"=>0xD3),
        "darkgray"=>array( "red"=>0xA9,  "green"=>0xA9,  "blue"=>0xA9),
        "darkgreen"=>array( "red"=>0x00,  "green"=>0x64,  "blue"=>0x00),
        "darkkhaki"=>array( "red"=>0xBD,  "green"=>0xB7,  "blue"=>0x6B),
        "darkmagenta"=>array( "red"=>0x8B,  "green"=>0x00,  "blue"=>0x8B),
        "darkolivegreen"=>array( "red"=>0x55,  "green"=>0x6B,  "blue"=>0x2F),
        "darkorange"=>array( "red"=>0xFF,  "green"=>0x8C,  "blue"=>0x00),
        "darkorchid"=>array( "red"=>0x99,  "green"=>0x32,  "blue"=>0xCC),
        "darkred"=>array( "red"=>0x8B,  "green"=>0x00,  "blue"=>0x00),
        "darksalmon"=>array( "red"=>0xE9,  "green"=>0x96,  "blue"=>0x7A),
        "darkseagreen"=>array( "red"=>0x8F,  "green"=>0xBC,  "blue"=>0x8F),
        "darkslateblue"=>array( "red"=>0x48,  "green"=>0x3D,  "blue"=>0x8B),
        "darkslategray"=>array( "red"=>0x2F,  "green"=>0x4F,  "blue"=>0x4F),
        "darkturquoise"=>array( "red"=>0x00,  "green"=>0xCE,  "blue"=>0xD1),
        "darkviolet"=>array( "red"=>0x94,  "green"=>0x00,  "blue"=>0xD3),
        "deeppink"=>array( "red"=>0xFF,  "green"=>0x14,  "blue"=>0x93),
        "deepskyblue"=>array( "red"=>0x00,  "green"=>0xBF,  "blue"=>0xFF),
        "dimgray"=>array( "red"=>0x69,  "green"=>0x69,  "blue"=>0x69),
        "dodgerblue"=>array( "red"=>0x1E,  "green"=>0x90,  "blue"=>0xFF),
        "firebrick"=>array( "red"=>0xB2,  "green"=>0x22,  "blue"=>0x22),
        "floralwhite"=>array( "red"=>0xFF,  "green"=>0xFA,  "blue"=>0xF0),
        "forestgreen"=>array( "red"=>0x22,  "green"=>0x8B,  "blue"=>0x22),
        "gainsboro"=>array( "red"=>0xDC,  "green"=>0xDC,  "blue"=>0xDC),
        "ghostwhite"=>array( "red"=>0xF8,  "green"=>0xF8,  "blue"=>0xFF),
        "gold"=>array( "red"=>0xFF,  "green"=>0xD7,  "blue"=>0x00),
        "goldenrod"=>array( "red"=>0xDA,  "green"=>0xA5,  "blue"=>0x20),
        "greenyellow"=>array( "red"=>0xAD,  "green"=>0xFF,  "blue"=>0x2F),
        "honeydew"=>array( "red"=>0xF0,  "green"=>0xFF,  "blue"=>0xF0),
        "hotpink"=>array( "red"=>0xFF,  "green"=>0x69,  "blue"=>0xB4),
        "indianred"=>array( "red"=>0xCD,  "green"=>0x5C,  "blue"=>0x5C),
        "indigo"=>array( "red"=>0x4B,  "green"=>0x00,  "blue"=>0x82),
        "ivory"=>array( "red"=>0xFF,  "green"=>0xFF,  "blue"=>0xF0),
        "khaki"=>array( "red"=>0xF0,  "green"=>0xE6,  "blue"=>0x8C),
        "lavender"=>array( "red"=>0xE6,  "green"=>0xE6,  "blue"=>0xFA),
        "lavenderblush"=>array( "red"=>0xFF,  "green"=>0xF0,  "blue"=>0xF5),
        "lawngreen"=>array( "red"=>0x7C,  "green"=>0xFC,  "blue"=>0x00),
        "lemonchiffon"=>array( "red"=>0xFF,  "green"=>0xFA,  "blue"=>0xCD),
        "lightblue"=>array( "red"=>0xAD,  "green"=>0xD8,  "blue"=>0xE6),
        "lightcoral"=>array( "red"=>0xF0,  "green"=>0x80,  "blue"=>0x80),
        "tan"=>array( "red"=>0xD2,  "green"=>0xB4,  "blue"=>0x8C),
        "lightgoldenrodyellow"=>array( "red"=>0xFA,  "green"=>0xFA,  "blue"=>0xD2),
        "lightgreen"=>array( "red"=>0x90,  "green"=>0xEE,  "blue"=>0x90),
        "lime"=>array( "red"=>0x00,  "green"=>0xFF,  "blue"=>0x00),
        "lightpink"=>array( "red"=>0xFF,  "green"=>0xB6,  "blue"=>0xC1),
        "lightsalmon"=>array( "red"=>0xFF,  "green"=>0xA0,  "blue"=>0x7A),
        "lightseagreen"=>array( "red"=>0x20,  "green"=>0xB2,  "blue"=>0xAA),
        "lightskyblue"=>array( "red"=>0x87,  "green"=>0xCE,  "blue"=>0xFA),
        "lightslategray"=>array( "red"=>0x77,  "green"=>0x88,  "blue"=>0x99),
        "lightsteelblue"=>array( "red"=>0xB0,  "green"=>0xC4,  "blue"=>0xDE),
        "lightyellow"=>array( "red"=>0xFF,  "green"=>0xFF,  "blue"=>0xE0),
        "limegreen"=>array( "red"=>0x32,  "green"=>0xCD,  "blue"=>0x32),
        "linen"=>array( "red"=>0xFA,  "green"=>0xF0,  "blue"=>0xE6),
        "mediumaquamarine"=>array( "red"=>0x66,  "green"=>0xCD,  "blue"=>0xAA),
        "mediumblue"=>array( "red"=>0x00,  "green"=>0x00,  "blue"=>0xCD),
        "mediumorchid"=>array( "red"=>0xBA,  "green"=>0x55,  "blue"=>0xD3),
        "mediumpurple"=>array( "red"=>0x93,  "green"=>0x70,  "blue"=>0xD0),
        "mediumseagreen"=>array( "red"=>0x3C,  "green"=>0xB3,  "blue"=>0x71),
        "mediumslateblue"=>array( "red"=>0x7B,  "green"=>0x68,  "blue"=>0xEE),
        "mediumspringgreen"=>array( "red"=>0x00,  "green"=>0xFA,  "blue"=>0x9A),
        "mediumturquoise"=>array( "red"=>0x48,  "green"=>0xD1,  "blue"=>0xCC),
        "mediumvioletred"=>array( "red"=>0xC7,  "green"=>0x15,  "blue"=>0x85),
        "midnightblue"=>array( "red"=>0x19,  "green"=>0x19,  "blue"=>0x70),
        "mintcream"=>array( "red"=>0xF5,  "green"=>0xFF,  "blue"=>0xFA),
        "mistyrose"=>array( "red"=>0xFF,  "green"=>0xE4,  "blue"=>0xE1),
        "moccasin"=>array( "red"=>0xFF,  "green"=>0xE4,  "blue"=>0xB5),
        "navajowhite"=>array( "red"=>0xFF,  "green"=>0xDE,  "blue"=>0xAD),
        "oldlace"=>array( "red"=>0xFD,  "green"=>0xF5,  "blue"=>0xE6),
        "olivedrab"=>array( "red"=>0x6B,  "green"=>0x8E,  "blue"=>0x23),
        "orange"=>array( "red"=>0xFF,  "green"=>0xA5,  "blue"=>0x00),
        "orangered"=>array( "red"=>0xFF,  "green"=>0x45,  "blue"=>0x00),
        "orchid"=>array( "red"=>0xDA,  "green"=>0x70,  "blue"=>0xD6),
        "palegoldenrod"=>array( "red"=>0xEE,  "green"=>0xE8,  "blue"=>0xAA),
        "palegreen"=>array( "red"=>0x98,  "green"=>0xFB,  "blue"=>0x98),
        "paleturquoise"=>array( "red"=>0xAF,  "green"=>0xEE,  "blue"=>0xEE),
        "palevioletred"=>array( "red"=>0xDB,  "green"=>0x70,  "blue"=>0x93),
        "papayawhip"=>array( "red"=>0xFF,  "green"=>0xEF,  "blue"=>0xD5),
        "peachpuff"=>array( "red"=>0xFF,  "green"=>0xDA,  "blue"=>0xB9),
        "peru"=>array( "red"=>0xCD,  "green"=>0x85,  "blue"=>0x3F),
        "pink"=>array( "red"=>0xFF,  "green"=>0xC0,  "blue"=>0xCB),
        "plum"=>array( "red"=>0xDD,  "green"=>0xA0,  "blue"=>0xDD),
        "powderblue"=>array( "red"=>0xB0,  "green"=>0xE0,  "blue"=>0xE6),
        "rosybrown"=>array( "red"=>0xBC,  "green"=>0x8F,  "blue"=>0x8F),
        "royalblue"=>array( "red"=>0x41,  "green"=>0x69,  "blue"=>0xE1),
        "saddlebrown"=>array( "red"=>0x8B,  "green"=>0x45,  "blue"=>0x13),
        "salmon"=>array( "red"=>0xFA,  "green"=>0x80,  "blue"=>0x72),
        "sandybrown"=>array( "red"=>0xF4,  "green"=>0xA4,  "blue"=>0x60),
        "seagreen"=>array( "red"=>0x2E,  "green"=>0x8B,  "blue"=>0x57),
        "seashell"=>array( "red"=>0xFF,  "green"=>0xF5,  "blue"=>0xEE),
        "sienna"=>array( "red"=>0xA0,  "green"=>0x52,  "blue"=>0x2D),
        "skyblue"=>array( "red"=>0x87,  "green"=>0xCE,  "blue"=>0xEB),
        "slateblue"=>array( "red"=>0x6A,  "green"=>0x5A,  "blue"=>0xCD),
        "slategray"=>array( "red"=>0x70,  "green"=>0x80,  "blue"=>0x90),
        "snow"=>array( "red"=>0xFF,  "green"=>0xFA,  "blue"=>0xFA),
        "springgreen"=>array( "red"=>0x00,  "green"=>0xFF,  "blue"=>0x7F),
        "steelblue"=>array( "red"=>0x46,  "green"=>0x82,  "blue"=>0xB4),
        "lightcyan"=>array( "red"=>0xE0,  "green"=>0xFF,  "blue"=>0xFF),
        "thistle"=>array( "red"=>0xD8,  "green"=>0xBF,  "blue"=>0xD8),
        "aliceblue"=>array( "red"=>0xF0,  "green"=>0xF8,  "blue"=>0xFF),
        "turquoise"=>array( "red"=>0x40,  "green"=>0xE0,  "blue"=>0xD0),
        "violet"=>array( "red"=>0xEE,  "green"=>0x82,  "blue"=>0xEE),
        "wheat"=>array( "red"=>0xF5,  "green"=>0xDE,  "blue"=>0xB3),
        "whitesmoke"=>array( "red"=>0xF5,  "green"=>0xF5,  "blue"=>0xF5),
        "yellowgreen"=>array( "red"=>0x9A,  "green"=>0xCD,  "blue"=>0x32)
      );

    }

  }

?>