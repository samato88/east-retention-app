
<?php
//echo gethostname()  . '<br />';
//echo 'query "' . htmlspecialchars($_GET["query"]) . '"<br />';
//echo 'searchField  "' . htmlspecialchars($_GET["searchField"]) . '"<br />';
//echo 'east_retentions_operator  "' . htmlspecialchars($_GET["east_retentions_operator"]) . '"<br />';
//echo 'east_retentions  "' . htmlspecialchars($_GET["east_retentions"]) . '"<br />';
//echo 'in_hathi  "' . htmlspecialchars($_GET["in_hathi"]) . '"<br />';
//echo 'hathistatus "' . htmlspecialchars($_GET["hathistatus"]) . '"<br />';
//echo 'libraries  "' . htmlspecialchars($_GET["libraries"]) . '"<br />';
//echo '<hr />'
// test numbers:  alt: 9539920   real: 10025928
// test numbers: 19216337  - both in bib_info and mapped elsewhere
// test numbers:  182681 - mapped to more than one oclc, not in bib_info
?>

<?php

include 'includes/connect.php'; // connects to appropriate database
include 'includes/remove_punctuation.php'; // remove_punctuation function
include 'includes/remove_stopwords.php';  // remove_stopwords function
include 'includes/test_input.php';   // test_input function
include 'includes/getLibNames.php'; // getLibNames, getLibLimitName functions
include 'includes/listLimits.php'; // list search limited by in results
include 'includes/isTesting.php'; // is this the testing site
include 'includes/paging.php';   // pagination


$appliedLimits = array();
$query = test_input($_GET["query"], "query") ; // searchstring - #####  or title data
$field = test_input($_GET["searchField"], "searchfield") ; // worldcat_oclc_nbr or titlesearch or isbn
$retentionsOperator = test_input($_GET["east_retentions_operator"], "east_retentions_operator");
$retentions = test_input($_GET["east_retentions"], "east_retentions") ;

if ($_GET["in_hathi"]) {
    $in_hathi = test_input($_GET["in_hathi"], "in_hathi");
}

$displaylimit = 25;
$limit = 25 ;
$fields = "bib_info.worldcat_oclc_nbr, title, east_retentions, in_hathi, hathi_ic, hathi_pd, hathi_url, titlesearch, isbn, COUNT(*) as cnt";

if( isset($_GET{'page'} ) ) {
    $page = test_input($_GET{'page'}, "page");
    $offset = $limit * ($page-1) ;
} else {
    $page = 1;
    $offset = 0;
}

$limitlibraries = array();
$limitlibrariesnames = array();
$limitlibrariesnamesstring = "" ;

if(!empty($_GET['libraries'])){ // Loop to store  values of individual checked checkbox.
    foreach($_GET['libraries'] as $selected){
        $valselected = test_input($selected, "libraries") ;
        array_push($limitlibraries,  "library_id = " . $valselected) ;
        array_push($limitlibrariesnames, getLibLimitName($valselected, $db) );
    } // end foreach library
    $limitlibrary = " AND ( " . implode(' OR ',$limitlibraries) . ")" ;
    $limitlibrariesnamesstring = "  <b>Retained at</b>: " . implode(" or ", $limitlibrariesnames) ;
} // end if library limits

if ($field === "titlesearch")  { // need 3 variants: w/o stopwords, w/o punctuation , original to report back
    $boolstring = "" ;
    $titlelike  = remove_punctuation($query) ;
    $boolstring = remove_stopwords($titlelike,$boolstring);

    if (strlen($boolstring) === 2) { // +ww - two letter word - search it
        $sql = "SELECT " . $fields . " FROM bib_info  WHERE  title LIKE '" . $titlelike . " %'";
    } else  {
        $sql = "SELECT " . $fields . ", MATCH (" . $field . ") AGAINST ('" . $boolstring . "' IN BOOLEAN MODE) AND titlesearch LIKE '" . $titlelike . "%'  FROM bib_info  WHERE MATCH ( " . $field . ") AGAINST ('" . $boolstring . "' IN BOOLEAN MODE) AND titlesearch LIKE '" . $titlelike . "%'";
    }

} else if ($field === "worldcat_oclc_nbr") { // check here if OCLC also in is table of oclcs updated by SCS
    $sqltestn = "SELECT inst_id, worldcat_oclc_nbr FROM local_worldcat_oclc_nbr WHERE local_oclc_nbr =".$query ;
    extract(runQuery($sqltestn, '', $db), EXTR_PREFIX_ALL, "alt");

    if ($alt_Results !== false && $alt_rowCount !== false) {
        $alt_lib_ids = array();
        foreach ($alt_Results as $row) {
            $nOCLC = $row['worldcat_oclc_nbr'];
            $nLib = $row['inst_id'];
            $alt_lib[$nLib] = $nOCLC ;
            $alt_oclc[$nOCLC][] = $nLib; // array of libs for each distinct OCLC
        } // end foreach alt oclc number result
    } // end if results from query on alt oclc number table

    $sql = "SELECT " . $fields . " FROM bib_info  WHERE bib_info.worldcat_oclc_nbr = ". $query ;

} else { //  ($field === "isbn") {
    $sql = "SELECT " . $fields . " FROM bib_info  WHERE isbn = '" . $query . "'" ;
} // end else which search field

if ($retentions != 'any') {
    switch ($retentionsOperator) {
        case "equals":
            $retentionsOperator = "=" ;
            break;
        case "greaterthan":
            $retentionsOperator = ">" ;
            break;
        case "lessthan":
            $retentionsOperator = "<" ;
            break;
        default:
            $retentionsOperator = "=" ; // default should never be used unless someone messed w/ url
    } // end switch

    array_push($appliedLimits,  "EAST retentions $retentionsOperator $retentions") ;
    $sql_limits = " AND east_retentions $retentionsOperator $retentions" ;

} // end if retentions not any

if (isset($in_hathi)) {
    switch ($in_hathi) {
        case "T":
            $hsql = " AND in_hathi = 'T'" ;
            $h = " in HathiTrust";
            break ;
        case "F":
            $hsql = " AND in_hathi = 'F'" ;
            $h = " not in HathiTrust";
            break ;
        case "hathi_pd":
            $hsql = " AND hathi_pd = 'T'" ;
            $h = " HathiTrust public domain";
            break ;
        case "hathi_ic":
            $hsql = " AND hathi_ic = 'T'" ;
            $h = " HathiTrust in copyright" ;
            break ;
    }

    array_push($appliedLimits,  $h) ;
    $sql_limits = $sql_limits . $hsql ;
}

if (isset($limitlibrary)) {
    //$sql = $sql . $limitlibrary ;
    $sql_limits = $sql_limits . $limitlibrary ;
}

$sql_limits = $sql_limits .  " GROUP BY worldcat_oclc_nbr ORDER BY titlesearch" ;

$countQuery = $sql . $sql_limits;
$sql_limits = $sql_limits .   " LIMIT " . $offset . "," . $limit ;

extract(runQuery($countQuery, '', $db),   EXTR_PREFIX_ALL, "count"); //$count_Results  $count_rowCount
extract(runQuery($sql, $sql_limits, $db), EXTR_PREFIX_ALL, "result");//$result_Results $result_rowCount

// pagination
$to = $limit * $page  ;
list ($pagination, $newsearch, $end) = paging($page, $to, $count_rowCount, $testing) ;

if ( preg_match("/testing/", htmlspecialchars($_SERVER['PHP_SELF']) ) ) { echo "SQL:<br/> " . $sql . $sql_limits . " <br/>" ;}

if ($count_rowCount == 0 ) { //no search results in bib_info

    if ( count($alt_lib) > 0) { // if there was an alt oclc number search
        extract(getMessage($alt_oclc, $db), EXTR_PREFIX_ALL, "message"); //$message_text , $message_mappedOCLC

        $newSQL = "SELECT " . $fields . " FROM bib_info  WHERE bib_info.worldcat_oclc_nbr = ". $message_mappedOCLC ;
        extract(runQuery($newSQL, $sql_limits, $db), EXTR_PREFIX_ALL, "mapped"); //$mapped_Results  $mapped_rowCount
        if ($mapped_rowCount > 0) {
            showResultsTop ($field, $count_rowCount, $limit, $to, $offset, $query,$appliedLimits, $limitlibrariesnamesstring, $alt_oclc );
            showResults($mapped_Results, $newsearch, $pagination, $end, $db);
            echo $message_text;
        } else { // this shouldn't happen- if in alt table should be in bib table too
            showNoResults($query, $appliedLimits, $limitlibraries, $limitlibrariesnamesstring, $newsearch) ;
        }

    } else {
        showNoResults($query, $appliedLimits, $limitlibraries, $limitlibrariesnamesstring, $newsearch) ;
    }

} else { // there are results
    extract(getMessage($alt_oclc, $db), EXTR_PREFIX_ALL, "message"); //$message_text , $message_mappedOCLC
    showResultsTop ($field, $count_rowCount, $limit, $to, $offset, $query,$appliedLimits, $limitlibrariesnamesstring, $alt_oclc );
    showResults($result_Results, $newsearch, $pagination, $end, $db);
    echo $message_text;
} // end else not zero results


$db = null ;

?>

<?php
function showResults ($entries, $newsearch, $pagination, $end, $db) {
    echo $newsearch ;
    echo $pagination ;
    echo $end ;

    foreach ($entries as $row) {
        $OCLC = $row['worldcat_oclc_nbr'] ;
        $hathi = $row['in_hathi'];
        $hathi_pd = $row['hathi_pd'];
        $hathi_ic = $row['hathi_ic'];
        $hathi_url = $row['hathi_url'];
        $isbn = $row['isbn'];


        if ($hathi === 'T') {
            if ($hathi_pd === 'T') {
                $hathi = "Hathi Public Domain" ;
            } else if ($hathi_ic === 'T') {
                $hathi = "Hathi In Copyright" ;
            } else {
                $hathi = "In Hathi" ;
            }
            $hathi = '<a href="' . $hathi_url . '">' . $hathi . '</a>' ;
        } else { // hathi not T
            $hathi = "Not In Hathi" ;
        }

        $libNames = getLibNames($OCLC, $db) ;

        echo <<<EOT
        <div class="entry" style="border:solid 1px black; margin-top:3px">
             <b>OCLC Number: </b><a href="http://www.worldcat.org/oclc/{$OCLC}">$OCLC</a><br />
             <b>TITLE:</b> {$row['title']} <br />
             <b>EAST Retentions: </b> {$row['cnt']} <br />
             <b>Hathi: </b> $hathi<br />
             <b>Retained by: </b> $libNames
        </div>
EOT;

    } // end foreach OCLC Number

    echo $newsearch ;
    echo $pagination ;
    echo $end ;
} // end showResults
?>
<?php
function showNoResults($query, $appliedLimits, $limitlibraries, $limitlibrariesnamesstring, $newsearch) {
    // find and report any search and library name limits here
    echo "<b>No results for </b>'$query'";
    listLimits($appliedLimits, $limitlibraries);
    if (isset($limitlibrariesnamesstring)) {
        echo $limitlibrariesnamesstring;
    }
    echo "<br />$newsearch";
}
?>
<?php
function getMessage($alt_oclc, $db) {
    $messages['text'] = "";
    $messages['mappedOCLC'] = "";

    // SEA HERE - different messages if alt worldcat > 1 number
    if ( $alt_oclc ) {
        foreach ($alt_oclc as $key => $value) { // $mappedOCLC will be random if more than one, that's okay
            $librariesAlt = array();
            $mappedOCLC = $key;
            foreach ($value as $id) { // get library names that have this mapped oclc
                $librariesAlt[] = getLibLimitName($id, $db);
            }
            $searchlink = '<a href="/searchresults?searchField=worldcat_oclc_nbr&query=' . $key . '&east_retentions_operator=equals&east_retentions=any&in_hathi=">' . $key . '</a>';
            $message = " <i><sup>*</sup>SCS mapped this OCLC number to " . $searchlink . " for " . join(", ", $librariesAlt) . ".</i><br/>";
            $messages['text'] = $messages['text'] . $message;
        } // end foreach atl_oclc

        $messages['text'] = "<p>" . $messages['text'] . "</p>" ;
        $messages['mappedOCLC'] =  $mappedOCLC  ;
    }
    return $messages ;
} // end getMessage
?>
<?php
function showResultsTop ($field, $count_rowCount, $limit, $to, $offset, $query,$appliedLimits, $limitlibrariesnamesstring, $alt_oclc ){
    if ($field === 'titlesearch') {
        if ($count_rowCount < $limit) { $limit = $count_rowCount ;}
        if ($to > $count_rowCount) { $to = $count_rowCount ; }
        echo '<h3>Showing ' ;
        echo  $offset + 1 . " to $to  of $count_rowCount results, sorted by relevance</h3>" ;
        echo'<p><b>You searched title</b> : ' . $query ;
        listLimits($appliedLimits, $limitlibrariesnamesstring );
        if (isset($limitlibrariesnamesstring)) {echo  $limitlibrariesnamesstring ; }
        echo '</p>' ;
    } else { // oclc search
        echo'<p>You searched OCLC number : ' . $query  ;
    }
    if (count($alt_oclc) > 0) { print "<sup>*</sup>" ; }
    echo '</p>' ;
}
?>
<?php
function runQuery ($sql, $sql_limits, &$db){
    $sql = $sql . $sql_limits ;
    //   try/catch doesn't solve hanging mysql gone away error
    try {
        $Query = $db->query($sql);
        $Count = $Query->rowCount();
        $Results = $Query->fetchAll();

    } catch (PDOException $e) {
        print "Error!: " . $e->getMessage() . "<br/>";
        //echo 'The SQL query failed with error '.$db->errorCode;
        die();
    }
    $queryResults['rowCount'] = $Count ;
    $queryResults['Results'] = $Results ;
    return $queryResults ;
}
?>
<script>
    $( ".entry:even" ).css( "background-color", "#dcdcdc");
</script>

