<?php
/*TO DO
Query EAST db ala the way it does for the web interface
Return mapped oclc, holding libraries

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



$url = $urlbase . $q ;
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
echo <<<EOL1
<p>TODO:  </p>
<ul>
<li>FRBR</li>
<li>total worldcat? </li> 
<li>all alt numbers? </li>  
<li>Search retentions</li>
<li>search hathi </li>
<li>search ia </li>
</ul>
EOL1;

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

/*upload file small example

if ($_FILES['uploadedfile']['error'] == UPLOAD_ERR_OK               //checks for errors
      && is_uploaded_file($_FILES['uploadedfile']['tmp_name'])) { //checks that file is uploaded
  echo file_get_contents($_FILES['uploadedfile']['tmp_name']);
}
 * /
 */
/* upload file examples: https://www.php.net/manual/en/features.file-upload.php
<?php

header('Content-Type: text/plain; charset=utf-8');

try {

    // Undefined | Multiple Files | $_FILES Corruption Attack
    // If this request falls under any of them, treat it invalid.
    if (
        !isset($_FILES['upfile']['error']) ||
        is_array($_FILES['upfile']['error'])
    ) {
        throw new RuntimeException('Invalid parameters.');
    }

    // Check $_FILES['upfile']['error'] value.
    switch ($_FILES['upfile']['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            throw new RuntimeException('No file sent.');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new RuntimeException('Exceeded filesize limit.');
        default:
            throw new RuntimeException('Unknown errors.');
    }

    // You should also check filesize here.
    if ($_FILES['upfile']['size'] > 1000000) {
        throw new RuntimeException('Exceeded filesize limit.');
    }

    // DO NOT TRUST $_FILES['upfile']['mime'] VALUE !!
    // Check MIME Type by yourself.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    if (false === $ext = array_search(
        $finfo->file($_FILES['upfile']['tmp_name']),
        array(
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
        ),
        true
    )) {
        throw new RuntimeException('Invalid file format.');
    }

    // You should name it uniquely.
    // DO NOT USE $_FILES['upfile']['name'] WITHOUT ANY VALIDATION !!
    // On this example, obtain safe unique name from its binary data.
    if (!move_uploaded_file(
        $_FILES['upfile']['tmp_name'],
        sprintf('./uploads/%s.%s',
            sha1_file($_FILES['upfile']['tmp_name']),
            $ext
        )
    )) {
        throw new RuntimeException('Failed to move uploaded file.');
    }

    echo 'File is uploaded successfully.';

} catch (RuntimeException $e) {

    echo $e->getMessage();

}

?>
*/