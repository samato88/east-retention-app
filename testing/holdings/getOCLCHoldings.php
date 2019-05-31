<?php

include 'includes/connect.php';

# monograph retention partners: eventually should get this from membersdb
#

function comparator($object1, $object2) {
    if ($object1->institutionName == $object2->institutionName) { return 0; }
    return ($object1->institutionName < $object2->institutionName) ? -1 : 1;
}

$monographs = "MAFCI,VXW,LOY,SFU,FDA,PBU,ALL,BDR,BOS,BOP,mbu,BZM,BXM,MDY,LAF,PSC,SNN,VKM,SAC,TFW,TFH,TFF,TUFTV,TYC,ZWU,AMH,VJA,WCM,VVP,NNM,PEX,MTH,GDC,ZIH,ZHL,ZYU,SYB,YPI,TWU,PIT,PFM,PLA,DDO,ZWU,WLU,AUM,WEL,UCW,YHM,BMU,SMU,ULN,FAU,HAM,CTL,NKF,BMC,CBY,CBYSP,MBB,NHM,MVA,RRR,VZS" ;

$urlbase = "http://www.worldcat.org/webservices/catalog/content/libraries/";
$q = urlencode(htmlentities($_GET['query'])); // 436577
$f = urlencode(htmlentities($_GET['frbr']));

if (ctype_digit($q) && (strlen($q) < 20)) { // it's all digits, and less than 20 chars, good to go
    $oclc = $query ; // NEED TO STRIP LEADING ZEROS?
} else {
    echo "OCLC number must be all digits, and less then 20 digits long" ;
    exit(1);
}

$format = "?format=json";
$callback = "&callback=holdings";
$libtype = "&libtype=1"; // academic
$servicelevel = "&servicelevel=full";
$maxlibraries = "&maximumLibraries=100";
$frbr= "&frbrGrouping=" . $f ;
$symbol = "&oclcsymbol=$monographs"; // monograph partners symbols
#$symbol = "&oclcsymbol=DNU"; // monograph partners symbols
$wskey = "&wskey=" . $wskey;

$url = $urlbase . $q . $format . $maxlibraries . $frbr . $symbol . $wskey;
//echo $url ;
$contents = file_get_contents($url);

if ($contents === FALSE) {
  echo "FAILED" ;
}
else {
 //  echo $contents ;
 //var_dump(json_decode($contents));

    $json = json_decode($contents);
// {"title":"Dogs.","author":"Grabianski, Janusz.","publisher":"F. Watts","date":"1968.","OCLCnumber":"436577","totalLibCount":3,"library":
    $title = $json->{'title'};
    $resultOCLC = $json->{'OCLCnumber'};
    $eastCount = $json->{'totalLibCount'};
    $libraries = $json->{'library'}; // libraries is an array

    if ($eastCount == "") {$eastCount = 0;}

    $OCLCmessage = "<p>Current OCLC Number: <a href='https://www.worldcat.org/oclc/" . $resultOCLC . "'/>" . $resultOCLC . "</a><br />";
    $OCLCmessage = $OCLCmessage . "Title: $title";
    $OCLCmessage = $OCLCmessage . "<br />EAST Holdings: $eastCount  <p />";

    if ($eastCount > 0) {
        $sorted_libraries = usort($libraries, 'comparator'); // this actually sorts $libraries, retruns t/f

        foreach ($libraries as $i => $lib) {
            //var_dump($lib);
            //if ($i % 4 == 0) { $OCLCmessage = $OCLCmessage . "<br />" ;}
            $libName = $lib->{'institutionName'};
            $url = $lib->{'opacUrl'};
            $libsymbol = $lib->{'oclcSymbol'};
            $state = $lib->{'state'};

            $OCLCmessage = $OCLCmessage . " <a href='$url'>$libName</a>($libsymbol)";
            if ($lib != end($libraries)) {
                $OCLCmessage = $OCLCmessage . ", ";
            }

        } // end foreach lib

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
                    $table = $table . "<td> <a href='$url'>$libName</a>($libsymbol) </td>";
                }
            }
        }
        $table = $table . "</table>";
        echo $table ;
    }
    echo $OCLCmessage;
 //echo $json{'library'}{'institutionName'};
 } // else get file contents didn't fail

# monograph retention partners:
#MAFCI VXW LOY SFU FDA PBU ALL BDR BOS BOP mbu BZM BXM MDY LAF PSC SNN VKM SAC TFW TFH TFF TUFTV TYC ZWU AMH VJA WCM VVP NNM PEX MTH GDC ZIH ZHL ZYU SYB YPI TWU PIT PFM PLA DDO ZWU WLU AUM WEL UCW YHM BMU SMU ULN FAU HAM CTL NKF BMC CBY CBYSP MBB NHM MVA RRR VZS
