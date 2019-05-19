<?php
//require_once('jsonpath-0.8.1.php');

// https://www.hathitrust.org/bib_api
//http://catalog.hathitrust.org/api/volumes/brief/oclc/424023.json

$urlbase = "http://catalog.hathitrust.org/api/volumes/brief/oclc/";
$q = urlencode(htmlentities($_GET['query'])); // 436577

if (ctype_digit($q) && (strlen($q) < 20)) { // it's all digits, and less than 20 chars, good to go
    $oclc = $query ;
    // NEED TO STRIP LEADING ZEROS?
} else {
    echo "OCLC number must be all digits, and less then 20 digits long" ;
    exit(1);
}


$hurl = $urlbase . $q . ".json";


//echo $url ;
$hcontents = file_get_contents($hurl);

//echo $hcontents ;
//var_dump(json_decode($hcontents));


$hjson = json_decode($hcontents);


$hholdings = $hjson->{'items'}; // items is an array

//$records = $hjson->{'records'}[0]->{'recordURL'};
$records = $hjson->{'records'} ;
foreach ($records as $rec) { // just get the first record url
  $recordURL = $rec->{'recordURL'};

}
 /*
$title = $json->{'title'};
$libraries = $json->{'library'}; // libraries is an array
*/

 //$Hathimessage = $hjson;
//$Hathimessage = $hholdings;
//$Hathimessage = $records ;
$Hathimessage = $recordURL;
//$Hathimessage = "<p>Pending Implementation</p>";

echo $Hathimessage;
//echo $json{'library'}{'institutionName'};


//echo $json;
