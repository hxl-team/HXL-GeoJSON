<?php
if(isset($_GET['callback'])){
  header('Content-Type: application/javascript');  
}else{
  header('Content-type: application/json');
}

include_once('lib/geoPHP/geoPHP.inc');
include_once('lib/sparqllib/sparqllib.php');

// we need to know the emergency:
if(!isset($_GET['emergency'])){

  echo 'Please add the emergency parameter to your query to let me know the emergency for which you want the refugee numbers. See the README for details: https://github.com/hxl-team/HXL-GeoJSON#hxl-geojson-api';
  die();

}elseif(!isset($_GET['level'])){

  // the query is quite different depending on whether we are looking for everything down from a specific level,
  // or just the APLs and populated places
  
  queryLowestLevel();
  
}else{

  queryAtLevel();

}

function queryAtLevel(){
    $query="
prefix ogc: <http://www.opengis.net/ont/geosparql#> 
prefix hxl: <http://hxl.humanitarianresponse.info/ns/#>

SELECT 

  (MAX(?valid) as ?latest) 
  ?unit 
  ?unitName 
  ?wkt
  (MAX(?lvl) as ?level) 
  (SUM(?count) AS ?totalRefugees) 

WHERE {
  
  GRAPH ?g {
    ?g hxl:aboutEmergency <".$_GET['emergency']."> ; 
       hxl:validOn ?valid .
    ?pop a hxl:RefugeesAsylumSeekers ; 
           hxl:personCount ?count ;
           hxl:atLocation  ?location .
  }
        
  ?location hxl:atLocation+ ?unit .
  
  ?unit hxl:atLevel ?lvl ;
    hxl:featureName ?unitName;
        ogc:hasGeometry ?geom .
        
  ?geom ogc:hasSerialization ?wkt .  

  FILTER regex(str(?lvl), \"".$_GET["level"]."$\")
} 

GROUP BY ?unit ?unitName ?wkt ?level 
ORDER BY ?locationName DESC(?lvl)
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
        
        // produce a normalized radius ti inidcate the number of refugees on the map
        while( $row = $queryResult->fetch_array() ){
            $counts[] = $row['totalRefugees'];
            $rows[] = $row;
        }

        $max = max($counts);
        $divider = $max / 15;

        while( $row = $queryResult->fetch_array() ){  

            $return .= '{
           "type": "Feature",
           "geometry": '.shrinkToPoint($row["wkt"]).',
           "properties": {
             "name": "'.$row["unitName"].'",
             "personCount": "'.$row["totalRefugees"].'",
             "description": "'.$row["totalRefugees"].' refugees",
             "date": "'.$row["latest"].'",
             "placeURI": "'.$row["unit"].'",
              "radius": "'.$row["totalRefugees"] / $divider .'"
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

}


// if no level is given in the query, this function will be called, 
// retruning the numbers at the lowest level
function queryLowestLevel(){
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
        

         // produce a normalized radius ti inidcate the number of refugees on the map
        while( $row = $queryResult->fetch_array() ){
            $counts[] = $row['totalRefugees'];
            $rows[] = $row;
        }

        $max = max($counts);
        $divider = $max / 15;


        while( $row = $queryResult->fetch_array() ){  

            $return .= '{
           "type": "Feature",
           "geometry": '.wkt_to_json($row["wkt"]).',
           "properties": {
            "name": "'.$row["locationName"].'",
            "personCount": "'.$row["totalRefugees"].'",
            "description": "'.$row["totalRefugees"].' refugees",
            "date": "'.$row["latest"].'",
            "placeURI": "'.$row["location"].'",
            "radius": "'.$row["totalRefugees"] / $divider .'"
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
}


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

// converts wkt to geojson
function wkt_to_json($wkt) {
  $geom = geoPHP::load($wkt,'wkt');
  return $geom->out('json');
}

// returns the centroid of a wkt polygon (if the centroid is inside)
// TODO: make sure the returned point in the polygon
function shrinkToPoint($wkt) {
  $geom = geoPHP::load($wkt,'wkt');
  $point = $geom->centroid();
  return $point->out('json');
}

?>