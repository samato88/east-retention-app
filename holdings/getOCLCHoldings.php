<?php

include 'includes/connect.php';
include 'includes/getMemberSpreadsheet.php'; // get symbols and oc contacts from spreadsheet

function comparator($object1, $object2) {
    if ($object1->institutionName == $object2->institutionName) { return 0; }
    return ($object1->institutionName < $object2->institutionName) ? -1 : 1;
}

list ($monographs, $serialssymbols, $symboltoid, $ocinfo) = getSpreadsheet();
//$monographs = "MAFCI,VXW,LOY,SFU,FDA,PBU,ALL,BDR,BOS,BOP,MBU,BZM,BXM,MDY,LAF,PSC,SNN,VKM,SAC,TFW,TFH,TFF,TUFTV,TYC,ZWU,AMH,VJA,WCM,VVP,NNM,PEX,MTH,GDC,ZIH,ZHL,ZYU,SYB,YPI,TWU,PIT,PFM,PLA,DDO,ZWU,WLU,AUM,WEL,UCW,YHM,BMU,SMU,ULN,FAU,HAM,CTL,NKF,BMC,CBY,CBYSP,MBB,NHM,MVA,RRR,VZS" ;

$urlbase = "http://www.worldcat.org/webservices/catalog/content/libraries/";
$q = urlencode(htmlentities($_GET['query'])); // 436577
$f = urlencode(htmlentities($_GET['frbr']));
$rt = urlencode(htmlentities($_GET['retentiontype']));
$q = trim($q) ;
$totalOCLCholdings = "" ;

if (ctype_digit($q) && (strlen($q) < 20)) { // it's all digits, and less than 20 chars, good to go
    $oclc = $q ; // NEED TO STRIP LEADING ZEROS?
} else {
    echo "OCLC number must be all digits, and less then 20 digits long : " ;
    echo $q;
    exit(0);
}
 if ($rt == 'serials') {
    $symbols = $serialssymbols ;
 } else {
    $symbols = $monographs ;
 }


$format = "?format=json";
$callback = "&callback=holdings";
$libtype = "&libtype=1"; // academic
//$servicelevel = "&servicelevel=full";
$maxlibraries = "&maximumLibraries=100";
$frbr= "&frbrGrouping=" . $f ;
$symbol = "&oclcsymbol=$symbols"; // monograph partners symbols
$wskey = "&wskey=" . $wskey;

$url = $urlbase . $q . $format . $maxlibraries . $frbr . $symbol . $wskey;
//echo $url ;
$contents = file_get_contents($url);
$json = json_decode($contents);
//  echo $contents ;
//var_dump(json_decode($contents));

// if json oclc different than dist oclc, re-execute with new oclc

if ($json->{'OCLCnumber'} != $q) { # should make sure json oclc is a number!
    #echo "NEW OCLC " . $json->{'OCLCnumber'} ;
    if (ctype_digit($json->{'OCLCnumber'})) {
        $q = $json->{'OCLCnumber'} ; //updating this for use in call to get total OCLC holdings
        $url = $urlbase . $json->{'OCLCnumber'} . $format . $maxlibraries . $frbr . $symbol . $wskey;
        $contents = file_get_contents($url);
        $json = json_decode($contents);
    }
}


if ($f == "off") { // check full oclc holdings if a frbr off search, frbr on calls to oclc api don't return totallibcount
    $furl = $urlbase . $q . $format . $maxlibraries . $frbr . $wskey;
    //echo $furl ;
    $fcontents = file_get_contents($furl);
    $fjson = json_decode($fcontents);

    if ($fcontents === FALSE || $fjson == "") {
        $totalOCLCholdings = "";
    } else {
        $totalOCLCholdings = $fjson->{'totalLibCount'};
    }
} // end if frbr off get total oclc holdings

//  display holdings limited to EAST symbols
if ($contents === FALSE || $json == "") {
    $OCLCmessage = "Failed to retrieve data  - <a target='_blank' href='https://www.worldcat.org/oclc/" .
      $oclc . "'>check WorldCat for holdings.</a> ";
}
else {
    // {"title":"Dogs.","author":"Grabianski, Janusz.","publisher":"F. Watts","date":"1968.","OCLCnumber":"436577","totalLibCount":3,"library":
    $title = $json->{'title'};
    $resultOCLC = $json->{'OCLCnumber'};
    $eastCount = $json->{'totalLibCount'};
    $libraries = $json->{'library'}; // libraries is an array

    if ($eastCount == "") {$eastCount = 0;}

    $OCLCmessage = "<p>";
    $OCLCmessage .= "# of EAST Holdings: $eastCount" ;
    if ($totalOCLCholdings != "") {
        $OCLCmessage .= "<br />Total OCLC holdings: $totalOCLCholdings";
    }
    $OCLCmessage .= "<br /> ";
    $OCLCmessage .= "Current OCLC Number: <a href='https://www.worldcat.org/oclc/" . $resultOCLC . "'/>" . $resultOCLC . "</a><br />";
    $OCLCmessage .= "Title: $title";
    $OCLCmessage .= "<p />";
    $OCLCmessage .= "<div id='holdings'>";
    $OCLCmessage .= "<table>\n";
    $OCLCmessage .= "<th>EAST Retention Partner</th><th>OCLC Symbol</th><th>Operational Contact</th>\n";


    if ($eastCount > 0) {
        $sorted_libraries = usort($libraries, 'comparator'); // this actually sorts $libraries, returns t/f

        foreach ($libraries as $i => $lib) {
            //var_dump($lib);
            $libName = $lib->{'institutionName'};
            $url = $lib->{'opacUrl'};
            $libsymbol = $lib->{'oclcSymbol'};
            $state = $lib->{'state'};
            $lib_id = $symboltoid[$libsymbol];
            $ocdata = $ocinfo[$lib_id];
            $OCLCmessage .=  "<tr>";
            $OCLCmessage .=  "<td><a href='$url'>$libName</a><td>$libsymbol</td>";
            $OCLCmessage .=  "<td>$ocdata</td>";
            $OCLCmessage .=  "</tr>\n";


            // was doing this when as a , delimited paragraph
            //if ($lib != end($libraries)) {
            //    $OCLCmessage = $OCLCmessage . ", ";
            //}

        } // end foreach lib
        /*   if we wanted a columnar table presentation this works:
        $columns = 4;
        $rows = ceil(count($libraries) / $columns);
        $table = "<table>";
        for ($n = 0; $n < $rows; $n++) {
            $table = $table . "<tr>";
            for ($i = 0; $i < $columns; $i++) {
                $key = $n + ($i * $rows);

                $libName = $libraries{$key}->{'institutionName'};
                $url = $libraries{$key}->{'opacUrl'};
                $libsymbol = $libraries{$key}->{'oclcSymbol'};
                $state = $libraries{$key}->{'state'};
                // NEED TO CHECK IF $libraries{$key} has data
                //echo "*" . $key . "* ";
                if (isset($libraries{$key})) {
                    $table = $table . "<td> <a href='$url'>$libName</a> ($libsymbol) </td>";
                }
            }
        }
        $table = $table . "</table>";
        echo $table ;
        */
    }
    $OCLCmessage .=  "</table>\n</div>\n";
    
 //echo $json{'library'}{'institutionName'};
 } // else get file contents didn't fail

//echo $OCLCmessage; //use this if not returning json
echo json_encode(array("a" => $OCLCmessage, "b" => $eastCount));
