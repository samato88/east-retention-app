<?php
//require_once('jsonpath-0.8.1.php');
// https://www.hathitrust.org/bib_api
//http://catalog.hathitrust.org/api/volumes/brief/oclc/424023.json

$urlbase = "http://catalog.hathitrust.org/api/volumes/brief/oclc/";
$q = urlencode(htmlentities($_GET['query'])); // 436577

if (ctype_digit($q) && (strlen($q) < 20)) { // it's all digits, and less than 20 chars, good to go
    $oclc = $query ; // NEED TO STRIP LEADING ZEROS?
} else {
    echo "OCLC number must be all digits, and less then 20 digits long" ;
    exit(1);
}

$hurl = $urlbase . $q . ".json";
$hcontents = file_get_contents($hurl);
$hjson = json_decode($hcontents);
//echo $hcontents ;
//var_dump(json_decode($hcontents));
//print_r($hjson);

if (count($hjson->{'items'}) > 0) {// check if results returned
    foreach ($hjson->{'records'} as $rec) { //  get record url - search should only return one record
        $recordURL = $rec->{'recordURL'};
    }

    foreach ($hjson->{'items'} as $holding) {
        $rightsCode = $holding->{'rightsCode'};
        $usRightsString = $holding->{'usRightsString'};
    }

    $Hathimessage = "Access: <a href=" . $recordURL . ">" . $usRightsString . "</a>";
} else {
    $Hathimessage = "No Results" ;
}

echo $Hathimessage;
