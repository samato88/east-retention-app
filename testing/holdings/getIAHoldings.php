<?php

//https://openlibrary.org/dev/docs/api/books
//http://openlibrary.org/api/volumes/brief/oclc/52377561.json
//
$urlbase = "http://openlibrary.org/api/volumes/brief/oclc/";
$q = urlencode(htmlentities($_GET['query'])); // 436577

if (ctype_digit($q) && (strlen($q) < 20)) { // it's all digits, and less than 20 chars, good to go
    $oclc = $query ;// NEED TO STRIP LEADING ZEROS?
} else {
    echo "OCLC number must be all digits, and less then 20 digits long" ;
    exit(1);
}

$format = ".json";

$iaurl = $urlbase . $q . $format ;
$iacontents = file_get_contents($iaurl);
$iajson = json_decode($iacontents);

if (count($iajson) > 0) {
    foreach ($iajson->{'records'} as $rec) { //  get record url - search should only return one record
        $recordURL = $rec->{'recordURL'};
    }
    foreach ($iajson->{'items'} as $holding) {
        $status = $holding->{'status'};
        //$usRightsString = $holding->{'usRightsString'};
    }
    $IAmessage = "Access: <a href=\"" . $recordURL . "\">". $status . "</a>";
}
else {
    $IAmessage = "No Results";
}

//echo $contents ;
//var_dump(json_decode($contents));

echo $IAmessage;
//echo $json{'library'}{'institutionName'};

