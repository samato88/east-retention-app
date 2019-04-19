<?php
/*TO DO - make search page, param for frbr,
east db api search and add here
hathi api search - https://catalog.hathitrust.org/api/volumes/full/oclc/10538871.json
internet archives api search
**Double check on service level - does full get you more holdings?**
 * https://www.oclc.org/developer/develop/web-services/worldcat-search-api/service-levels.en.html
*/
include '../includes/connect.php'; // connects to appropriate database
include '../includes/test_input.php';   // test_input function include 'includes/listLimits.php'; // list search limited by in results
include '../includes/isTesting.php'; // is this the testing site
include '../includes/paging.php';   // pagination

$urlbase = "http://www.worldcat.org/webservices/catalog/content/libraries/";
$q = urlencode(htmlentities($_GET['q'])); // 436577

if (ctype_digit($q) && (strlen($q) < 20)) { // it's all digits, and less than 20 chars, good to go
    $oclc = $query ;
    // NEED TO STRIP LEADING ZEROS?
} else {
    echo "OCLC number must be all digits, and less then 20 digits long" ;
    exit(1);
}

$monographs ="BOP,CBYSP,MVA,PFM,TFH,YPI,ZHL,mbu,PLA,TFF,BZM,DDO,TUFTV,BOS,CBY,NHM,PIT,TFW,SYB,ZIH,VJA,AMH,VVP,BXM,MBB,BDR,BMC,CTL,NKF,FAU,YHM,HAM,LAF,LOY,MDY,MTH,PEX,SAC,VKM,VZS,SNN,PSC,TYC,ZWU,UCW,AUM,BMU,SMU,ULN,RRR,VXW,WEL,WLU,WCM,MAFCI,PBU,NNM,ALL,FDA,SFU,GDC,ZYU,TWU,ZWU";
$serials = "BOP,PFM,TFH,YPI,ZHL,mbu,PLA,TFF,BZM,DDO,TUFTV,BOS,PIT,TFW,SYB,ZIH,AMH,VVP,BXM,MBB,CTL,NKF,FAU,YHM,LAF,LOY,MTH,VKM,SNN,PSC,TYC,ZWU,AUM,RRR,VXW,WCM,MAFCI,ALL,ZYU,TWU,ZWU,CGA,MBW,WQM";

//$callback = $_GET["callback"] ;
$format = "?format=json";
$callback = "&callback=holdings";
$libtype = "&libtype=1"; // academic
$servicelevel = "&servicelevel=full";
$symbol = "&oclcsymbol=$monographs"; // PIT
$wskey = "&wskey=" . $wskey;

//frbrGrouping

$url = $urlbase . $q . $format . $symbol . $wskey;
//echo $url ;
$contents = file_get_contents($url);
//echo $contents ;
//var_dump(json_decode($contents));

$json = json_decode($contents);
// {"title":"Dogs.","author":"Grabianski, Janusz.","publisher":"F. Watts","date":"1968.","OCLCnumber":"436577","totalLibCount":3,"library":
$title = $json->{'title'};
$resultOCLC = $json->{'OCLCnumber'};
$eastCount =  $json->{'totalLibCount'};

$libraries = $json->{'library'}; // libraries is an array
echo "<b>You searched: </b><a href='https://www.worldcat.org/oclc/". $q . "'>" . $q . "</a> </b></br>";
echo "<b>Current OCLC Number: </b><a href='https://www.worldcat.org/oclc/" . $resultOCLC. "'/>" . $resultOCLC . "</a><br />";
echo "<hr/><p>Search results with FRBR On :</p>";
echo "<b>$title</b>";
echo "<br />EAST Holdings: $eastCount  <br />";

foreach($libraries as $lib) {
    //var_dump($lib);
    $libName = $lib->{'institutionName'};
    $url =  $lib->{'opacUrl'};
    $libsymbol = $lib->{'oclcSymbol'};
    $state = $lib->{'state'};

    echo <<<EOL
    <a href="$url">$libName</a> ($libsymbol)
EOL;
} // end foreach lib
echo "<p>TODO:  FRBR , total worldcat?  all alt numbers?   Search retentions, search hathi and ia</p>" ;

//echo $json{'library'}{'institutionName'};


//echo $json;

// URLS......
//http://localhost:8080/testing/oclcHoldings?q=436577
// http://localhost:8080/testing/oclc?q=1172085
//$jurl = "http://www.worldcat.org/webservices/catalog/content/libraries/"
// . $oclc .
// "?oclcsymbol=BYNSP,BTSSP&wskey=" . $key .
// "&servicelevel=full&format=json&callback=" . $callback ;

//https://www.worldcat.org/webservices/catalog/content/libraries/436577?oclcsymbol=PIT&libtype&format=json&callback=function&wskey=$wskey

// this gets you 019s:
// https://www.worldcat.org/webservices/catalog/content/1172085?format=json&callback=function&servicelevel=full&wskey=$wskey