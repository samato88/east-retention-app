<?php

include 'includes/connect.php';

# monograph retention partners: eventually should get this from membersdb
#
$monographs = "MAFCI,VXW,LOY,SFU,FDA,PBU,ALL,BDR,BOS,BOP,mbu,BZM,BXM,MDY,LAF,PSC,SNN,VKM,SAC,TFW,TFH,TFF,TUFTV,TYC,ZWU,AMH,VJA,WCM,VVP,NNM,PEX,MTH,GDC,ZIH,ZHL,ZYU,SYB,YPI,TWU,PIT,PFM,PLA,DDO,ZWU,WLU,AUM,WEL,UCW,YHM,BMU,SMU,ULN,FAU,HAM,CTL,NKF,BMC,CBY,CBYSP,MBB,NHM,MVA,RRR,VZS" ;


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
$format = "?format=json";
$callback = "&callback=holdings";
$libtype = "&libtype=1"; // academic
$servicelevel = "&servicelevel=full";
$maxlibraries = "&maximumLibraries=100";
$symbol = "&oclcsymbol=$monographs"; // monograph partners symbols
#$symbol = "&oclcsymbol=DNU"; // monograph partners symbols

$wskey = "&wskey=" . $wskey;

//frbrGrouping

$url = $urlbase . $q . $format . $maxlibraries . $symbol . $wskey;
//echo $url ;
$contents = file_get_contents($url);

if ($contents === FALSE) {
  echo "FAILED" ;
}
else {
 //  echo $url ;
 //  echo $contents ;
//var_dump(json_decode($contents));

    $json = json_decode($contents);
// {"title":"Dogs.","author":"Grabianski, Janusz.","publisher":"F. Watts","date":"1968.","OCLCnumber":"436577","totalLibCount":3,"library":
    $title = $json->{'title'};
    $resultOCLC = $json->{'OCLCnumber'};
    $eastCount = $json->{'totalLibCount'};
    $libraries = $json->{'library'}; // libraries is an array

    $OCLCmessage = "<p>Current OCLC Number: <a href='https://www.worldcat.org/oclc/" . $resultOCLC . "'/>" . $resultOCLC . "</a><br />";
    $OCLCmessage = $OCLCmessage . "Search results with FRBR On :<br />";
    $OCLCmessage = $OCLCmessage . "<b>$title</b>";
    $OCLCmessage = $OCLCmessage . "<br />EAST Holdings: $eastCount  <p />";


    foreach ($libraries as $lib) {
        //var_dump($lib);
        $libName = $lib->{'institutionName'};
        $url = $lib->{'opacUrl'};
        $libsymbol = $lib->{'oclcSymbol'};
        $state = $lib->{'state'};



        $OCLCmessage = $OCLCmessage . " <a href='$url'>$libName</a>($libsymbol)";
        if ($lib != end($libraries)) {
            $OCLCmessage = $OCLCmessage . ", ";
        }
        /*
            echo <<<EOL
            <a href="$url">$libName</a> ($libsymbol)
        <hr />
        EOL;
        */
    } // end foreach lib

    echo $OCLCmessage;
//echo "<hr />";
//echo $json{'library'}{'institutionName'};
//echo $json;
} // else get file contents didn't fail

# monograph retention partners:
#MAFCI VXW LOY SFU FDA PBU ALL BDR BOS BOP mbu BZM BXM MDY LAF PSC SNN VKM SAC TFW TFH TFF TUFTV TYC ZWU AMH VJA WCM VVP NNM PEX MTH GDC ZIH ZHL ZYU SYB YPI TWU PIT PFM PLA DDO ZWU WLU AUM WEL UCW YHM BMU SMU ULN FAU HAM CTL NKF BMC CBY CBYSP MBB NHM MVA RRR VZS
