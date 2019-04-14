
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
$query = test_input($_GET["query"], "query") ; // searchstring
$field = test_input($_GET["searchField"], "searchfield") ; // worldcat_oclc_nbr, titlesearch, isbn, issn
$retentionsOperator = test_input($_GET["east_retentions_operator"], "east_retentions_operator");
$retentions = test_input($_GET["east_retentions"], "east_retentions") ;

if ($_GET["in_hathi"]) {
    $in_hathi = test_input($_GET["in_hathi"], "in_hathi");
}

if ($_GET["in_ia"]) {
    $in_ia = test_input($_GET["in_ia"], "in_ia");
}

if ($_GET["rectype"]) { // right now only using to limit to serials
    $rectype = 's';
    //$rectype = test_input($_GET["rectype", "rectype");
}

$displaylimit = 25;
$limit = 25 ;
$fields = "worldcat_oclc_nbr, any_value(title), any_value(in_hathi), any_value(hathi_ic), any_value(hathi_pd), any_value(hathi_url), any_value(titlesearch), any_value(isbn), any_value(issn), any_value(rectype), any_value(internetarchive), any_value(internetarchive_url),  count(worldcat_oclc_nbr) as cnt ";

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
$limitrectypes = array() ;


if(!empty($_GET['libraries'])){ // Loop to store values of individual checked checkbox.
    foreach($_GET['libraries'] as $selected){
        $valselected = test_input($selected, "libraries") ;
        array_push($limitlibraries,  "library_id = " . $valselected) ;
        array_push($limitlibrariesnames, getLibLimitName($valselected, $db) );
    } // end foreach library
    $limitlibrary = " AND ( " . implode(' OR ',$limitlibraries) . ")" ;// (library_id=1 OR library_id=3)
    $limitlibrariesnamesstring = "  <b>Retained at</b>: " . implode(" or ", $limitlibrariesnames) ;
} // end if library limits

if ($field === "titlesearch")  { // need 3 variants: w/o stopwords, w/o punctuation , original to report back
    $boolstring = "" ;
    $titlelike  = remove_punctuation($query) ;
    $boolstring = remove_stopwords($titlelike,$boolstring);
    if (strlen($boolstring) === 2) { // +ww - two letter word - search it
        #$sql = "SELECT " . $fields . " FROM bib_info  WHERE  title LIKE '" . $titlelike . " %'";
        $where = "title LIKE '" . $titlelike . " %'";
    } else  {
        $where =  "MATCH ( " . $field . ") AGAINST ('" . $boolstring . "' IN BOOLEAN MODE) AND titlesearch LIKE '" . $titlelike . "%'";
    }
    $sql = "SELECT " . $fields . " FROM bib_info WHERE " . $where ;
    $sql_count = "SELECT " . $fieldscnt . " FROM bib_info WHERE " . $where ;

} else if ($field === "isbn") {
    $sql = "SELECT " . $fields . " FROM bib_info  WHERE isbn = '" . $query . "'";

} else if ($field === "issn") {
    $sql = "SELECT " . $fields . " FROM bib_info  WHERE issn = '" . $query . "'";

} else  { //($field === "worldcat_oclc_nbr") -DEFAULT SEARCH TYPE
  // check here if OCLC also in is table of oclcs updated by SCS
    $sqltestn = "SELECT inst_id, worldcat_oclc_nbr FROM local_worldcat_oclc_nbr WHERE local_oclc_nbr =".$query ;
    extract(runQuery($sqltestn, $db), EXTR_PREFIX_ALL, "alt"); // creates alt_Results

    if ($alt_Results !== false && $alt_rowCount !== false) {
        $alt_lib_ids = array(); // does this ever get used?
        foreach ($alt_Results as $row) {
            $nOCLC = $row['worldcat_oclc_nbr'];
            $nLib = $row['inst_id'];
            $alt_lib[$nLib] = $nOCLC ;
            $alt_oclc[$nOCLC][] = $nLib; // array of libs for each distinct OCLC, does this work if more than one?
        } // end foreach alt oclc number result
    } // end if results from query on alt oclc number table, used later??

    $sql = "SELECT " . $fields . " FROM bib_info WHERE worldcat_oclc_nbr=" . $query  ;

} // end field type

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

if (isset($in_ia)) {
   switch ($in_ia) {
       case "T":
           $hsql = " AND internetarchive = 'y'" ;
           $h = " in Internet Archives";
           break ;
       case "F":
           $hsql = " AND (internetarchive = 'n' OR internetarchive is null)" ;
           $h = " not in Internet Archives";
           break ;
    }

    array_push($appliedLimits,  $h) ;
    $sql_limits = $sql_limits . $hsql ;
}


if (isset($rectype)){
    array_push($appliedLimits, "Serials/Journals" ) ;
    $sql_limits = $sql_limits . " AND rectype ='s'" ;
}

//SEA taking this out 4/14/19 until EAST retentions field updated
//was never working right with group by OCLC anyway
/*
if (isset($limitlibrary)){
    //array_push($appliedLimits, "Serials/Journals" ) ;
    $sql_limits = $sql_limits . $limitlibrary ;
}
*/

$sql_limits = $sql_limits .  " GROUP BY worldcat_oclc_nbr " ;


if ($retentions != 'any') { // limit by number of retentions using subquery
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
    $sql_limits = $sql_limits  . " HAVING cnt " . $retentionsOperator . $retentions ;
} // end if retentions not any

$sql_limits = $sql_limits . " ORDER BY any_value(titlesearch) " ;
$sql_search = $sql . $sql_limits;

//echo "sql_limits : *" . $sql_limits . "*<br/>";
//echo "sql_search : " . $sql_search . "<br/>";

$countQuery = $sql_search ;
$sql_search = $sql_search . " LIMIT " . $offset . "," . $limit ;

extract(runQuery($countQuery, $db),   EXTR_PREFIX_ALL, "count"); //$count_Results  $count_rowCount
extract(runQuery($sql_search, $db), EXTR_PREFIX_ALL, "result");//$result_Results $result_rowCount

// pagination
$to = $limit * $page  ;
list ($pagination, $newsearch, $end) = paging($page, $to, $count_rowCount, $testing) ;

if ( preg_match("/testing/", htmlspecialchars($_SERVER['PHP_SELF']) ) ) { echo "Search SQL:<br/> " . $sql_search . " <br/>Count SQL:<br/>" . $countQuery ;}

if ($count_rowCount == 0 ) { //no search results in bib_info

    if ( count($alt_lib) > 0) { // if there was an alt oclc number search
        extract(getMessage($alt_oclc, $db), EXTR_PREFIX_ALL, "message"); //$message_text , $message_mappedOCLC

        $newSQL = "SELECT " . $fields . " FROM bib_info  WHERE bib_info.worldcat_oclc_nbr = ". $message_mappedOCLC ;
        $newSQL = $subquery_start . $newSQL .  $sql_limits . $subquery_end ;
        extract(runQuery($newSQL, $db), EXTR_PREFIX_ALL, "mapped"); //$mapped_Results  $mapped_rowCount
        if ($mapped_rowCount > 0) {
            showResultsTop ($field, $count_rowCount, $limit, $to, $offset, $query,$appliedLimits, $limitlibrariesnamesstring, $alt_oclc );
            showResults($mapped_Results, $newsearch, $pagination, $end, $db);
            echo $message_text;
        } else { // this shouldn't happen- if in alt table should be in bib table too
            showNoResults($query, $appliedLimits, $limitlibraries, $limitlibrariesnamesstring, $newsearch, $field) ;
        }

    } else {
        showNoResults($query, $appliedLimits, $limitlibraries, $limitlibrariesnamesstring, $newsearch, $field) ;
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
        $hathi = $row['any_value(in_hathi)'];
        $hathi_pd = $row['any_value(hathi_pd)'];
        //$hathi_ic = $row['hathi_ic']; // not used
        $hathi_url = $row['any_value(hathi_url)'];
        $ia = $row['any_value(internetarchive)'];
        $ia_url = $row['any_value(internetarchive_url)'];
        $isbn = $row['any_value(isbn)'];  // currently not used, should add it in


        if ($hathi === 'T') {
            if ($hathi_pd === 'T') {
                $hathi_message = "Hathi Public Domain" ;
            } else {// if ($hathi_ic === 'T') {
                $hathi_message = "Hathi In Copyright" ;
            }
            $hathi_message = '<a href="' . $hathi_url . '">' . $hathi_message . '</a>' ;
        } else { // hathi not T
            $hathi_message = '' ;
        }

        if ($ia === 'y') {
            $ia_message = '&nbsp;&nbsp;<a href="' . $ia_url . '">Internet Archive</a>' ;
        }  else {
            $ia_message = '' ;
        }

        $libNames = getLibNames($OCLC, $db) ;

        echo <<<EOT1
        <div class="entry" style="border:solid 1px black; margin-top:3px; position: relative;">
             <b>OCLC Number: </b><a href="http://www.worldcat.org/oclc/{$OCLC}">$OCLC</a><br />
             <b>TITLE:</b> {$row['any_value(title)']}<br />
EOT1;
        if ($row['isbn']) {
            echo "<b>ISBN: </b><a href=\"https://www.worldcat.org/isbn/" . $row['isbn'] . "\">" . $row['isbn'] . "</a><br />" ;
        }
        if ($row['issn']) {
            echo "<b>ISSN: </b><a href=\"https://www.worldcat.org/issn/" . $row['issn'] . "\">" . $row['issn'] . "</a><br />" ;
        }
        echo <<<EOT2
             <b>EAST Retentions: </b> {$row['cnt']} <br />
             <b>Retained by: </b> $libNames
EOT2;
        if ($hathi_message != '' || $ia_message != '') {
            echo "<br /><b> Digital Surrogates: </b > $hathi_message $ia_message <br />" ;
        }
        echo "</div>";
    } // end foreach OCLC Number

    echo $newsearch ;
    echo $pagination ;
    echo $end ;
} // end showResults
?>
<?php
function showNoResults($query, $appliedLimits, $limitlibraries, $limitlibrariesnamesstring, $newsearch, $field) {
    // find and report any search and library name limits here
    echo "<p><b>No results for $field :</b> '$query' </p>";
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
        echo  $offset + 1 . " to $to  of $count_rowCount results</h3>" ;
        echo'<p><b>You searched title</b> : ' . $query ;

    } elseif ($field === 'isbn') {
        echo'<p><b>You searched ISBN number</b> : ' . $query  ;
    } elseif ($field === 'issn') {
        echo'<p><b>You searched ISSN :</b> ' . $query  ;
    } else { // oclc search
        echo'<p><b>You searched OCLC number</b> : ' . $query  ;
    }
    if (count($alt_oclc) > 0) { print "<sup>*</sup>" ; }

    listLimits($appliedLimits, $limitlibrariesnamesstring );
    if (isset($limitlibrariesnamesstring)) {echo  $limitlibrariesnamesstring ; }
    echo '</p>' ;
}
?>
<?php
function runQuery ($sql, &$db){
    //   try/catch doesn't solve hanging mysql gone away error
    try {
        $Query = $db->query($sql);
        if ($Query) {
            $Count = $Query->rowCount();
            $Results = $Query->fetchAll();
        }

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

