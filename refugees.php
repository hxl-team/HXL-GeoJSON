<?php
if(isset($_GET['callback'])){
  header('Content-Type: application/javascript');  
}else{
  header('Content-type: application/json');
}

include_once('lib/geoPHP/geoPHP.inc');
include_once('lib/sparqllib/sparqllib.php');

if(!isset($_GET['emergency'])){
  echo 'Please add the emergency parameter to your query to let me know the emergency for which you want the refugee numbers.';
  die();
}

$query = "
prefix ogc: <http://www.opengis.net/ont/geosparql#> 
prefix hxl: <http://hxl.humanitarianresponse.info/ns/#>

SELECT (MAX(?valid) as ?latest) ?location ?locationName ?wkt (SUM(?count) AS ?totalRefugees) WHERE {
  
  GRAPH ?g {
    ?g hxl:aboutEmergency <".$_GET['emergency']."> ; 
       hxl:validOn ?valid .
    ?pop a hxl:RefugeesAsylumSeekers ; 
           hxl:personCount ?count ;
           hxl:atLocation  ?location .
  }
  ?location hxl:featureName ?locationName;
            ogc:hasGeometry ?geom .
  ?geom ogc:hasSerialization ?wkt .
  
} GROUP BY ?location ?locationName ?wkt ORDER BY ?locationName
";


$queryResult = getQueryResults($query);

if ($queryResult->num_rows() == 0){
  echo 'no result';
  die();
} else {

  $return = '';

  if(isset($_GET['callback'])){
    $return = $_GET['callback'].'(';    
  }

  $return .= '{
  "type": "FeatureCollection",
  "features": [
     ';
    // To extract coordinates from the polygon string.
    while( $row = $queryResult->fetch_array() ){  

        $return .= '{
       "type": "Feature",
       "geometry": '.wkt_to_json($row["wkt"]).',
       "properties": {
         "name": "'.$row["locationName"].'",
         "personCount": "'.$row["totalRefugees"].'",
         "description": "'.$row["totalRefugees"].' refugees",
         "date": "'.$row["latest"].'"
       }
     },';

    } 
    $return = substr($return, 0, -1); // remove trailing comma to make it valid JSON
    $return .= '
  ]
}';

if(isset($_GET['callback'])){
    $return .= ');';    
  }
}
echo $return;


function getQueryResults($query){
   try {
        $db = sparql_connect( "http://hxl.humanitarianresponse.info/sparql" );
        
        if( !$db ) {
            print $db->errno() . ": " . $db->error(). "\n"; exit;
        }
        $result = $db->query($query);
        if( !$result ) {
            print $db->errno() . ": " . $db->error(). "\n"; exit;
        }

    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }
  return $result;
}


  function wkt_to_json($wkt) {
    $geom = geoPHP::load($wkt,'wkt');
    return $geom->out('json');
  }

?>