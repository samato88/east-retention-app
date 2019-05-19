<?php


$urlbase = "http://www.worldcat.org/webservices/catalog/content/libraries/";
$q = urlencode(htmlentities($_GET['query'])); // 436577
$t = urlencode(htmlentities($_GET['searchField']));

if (ctype_digit($q) && (strlen($q) < 20)) { // it's all digits, and less than 20 chars, good to go
    $oclc = $query ;
    // NEED TO STRIP LEADING ZEROS?
} else {
    echo "OCLC number must be all digits, and less then 20 digits long" ;
    exit(1);
}

//$callback = $_GET["callback"] ;
//$format = "?format=json";
//$callback = "&callback=holdings";
//$symbol = "&oclcsymbol=$monographs"; // PIT


//$url = $urlbase . $q . $format . $symbol . $wskey;
//echo $url ;
//$contents = file_get_contents($url);
// NEED ERROR CHECKING HERE - what does file_get_contents return?

//echo $contents ;
//var_dump(json_decode($contents));

/*
$json = json_decode($contents);
$title = $json->{'title'};
$resultOCLC = $json->{'OCLCnumber'};
$eastCount =  $json->{'totalLibCount'};
$libraries = $json->{'library'}; // libraries is an array
*/
$IAmessage = "<p>Pending Implementation";

echo $IAmessage;
//echo $json{'library'}{'institutionName'};


//echo $json;
