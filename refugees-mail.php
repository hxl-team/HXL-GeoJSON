<?php
header('Content-type: application/json');
?>

{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "geometry": {
        "type": "Point",
        "coordinates": [ 102, 0.5 ]
      },
      "properties": {
        "prop0": "value0"
      }
    }
  ]
}