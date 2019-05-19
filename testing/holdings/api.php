
<?php
//echo gethostname()  . '<br />';
//echo 'query "' . htmlspecialchars($_GET["query"]) . '"<br />';
//echo 'searchField  "' . htmlspecialchars($_GET["searchField"]) . '"<br />';
include 'includes/connect.php'; // connects to appropriate database
include 'includes/test_input.php';   // test_input function
include 'includes/getLibNames.php'; // getLibNames, getLibLimitName functions
include 'includes/isTesting.php'; // is this the testing site


$query = test_input($_GET["query"], "query") ; // searchstring
$field = test_input($_GET["searchField"], "searchfield") ; // worldcat_oclc_nbr, titlesearch, isbn, issn


$fields = "worldcat_oclc_nbr, any_value(title),  any_value(isbn), any_value(issn), any_value(rectype),  count(worldcat_oclc_nbr) as cnt ";


if ($field === "isbn") {
    $sql = "SELECT " . $fields . " FROM bib_info  WHERE isbn = '" . $query . "'";

} else if ($field === "issn") {
    $sql = "SELECT " . $fields . " FROM bib_info  WHERE issn = '" . $query . "'";

} else  { //($field === "worldcat_oclc_nbr") -DEFAULT SEARCH TYPE
    // check here if OCLC also in is table of oclcs updated by SCS
    $sqltestn = "SELECT inst_id, worldcat_oclc_nbr FROM local_worldcat_oclc_nbr WHERE local_oclc_nbr =".$query ;
    extract(runQuery($sqltestn, $db), EXTR_PREFIX_ALL, "alt"); // creates alt_Results

    if ($alt_Results !== false && $alt_rowCount !== false) {
        foreach ($alt_Results as $row) {
            $nOCLC = $row['worldcat_oclc_nbr'];
            $nLib = $row['inst_id'];
            $alt_lib[$nLib] = $nOCLC ; // key library value mapped oclc
            $alt_oclc[$nOCLC][] = $nLib; // array of libs for each distinct OCLC, does this work if more than one?
        } // end foreach alt oclc number result
    } // end if results from query on alt oclc number table, used later??

    $sql = "SELECT " . $fields . " FROM bib_info WHERE worldcat_oclc_nbr=" . $query  ;

} // end field type

$sql_limits = " GROUP BY worldcat_oclc_nbr " ;
$sql_search = $sql . $sql_limits;

//echo "sql_limits : *" . $sql_limits . "*<br/>";
//echo "sql_search : " . $sql_search . "<br/>";

$countQuery = $sql_search ;

extract(runQuery($countQuery, $db),   EXTR_PREFIX_ALL, "count"); //$count_Results  $count_rowCount
extract(runQuery($sql_search, $db), EXTR_PREFIX_ALL, "result");//$result_Results $result_rowCount


//if ( preg_match("/testing/", htmlspecialchars($_SERVER['PHP_SELF']) ) ) { echo "Search SQL:<br/> " . $sql_search . " <br/>Count SQL:<br/>" . $countQuery ;}

if ($count_rowCount == 0 ) { //no search results in bib_info

    if ( count($alt_lib) > 0) { // if there was an alt oclc number search
        extract(getMessage($alt_oclc, $db, $query), EXTR_PREFIX_ALL, "message"); //$message_text , $message_mappedOCLC

        $newSQL = "SELECT " . $fields . " FROM bib_info  WHERE bib_info.worldcat_oclc_nbr = ". $message_mappedOCLC ;
        $newSQL = $subquery_start . $newSQL .  $sql_limits . $subquery_end ;
        extract(runQuery($newSQL, $db), EXTR_PREFIX_ALL, "mapped"); //$mapped_Results  $mapped_rowCount
        if ($mapped_rowCount > 0) {
            showResultsTop ($field, $count_rowCount, $limit, $to, $offset, $query,$appliedLimits, $limitlibrariesnamesstring, $alt_oclc, $message_mappedOCLC );
            showResults($mapped_Results, $newsearch, $pagination, $end, $db);
            echo $message_text;
        } else { // this shouldn't happen- if in alt table should be in bib table too
            showNoResults($query, $appliedLimits, $limitlibraries, $limitlibrariesnamesstring, $newsearch, $field) ;
        }

    } else {
        showNoResults($query, $appliedLimits, $limitlibraries, $limitlibrariesnamesstring, $newsearch, $field) ;
    }

} else { // there are results
    extract(getMessage($alt_oclc, $db, $query), EXTR_PREFIX_ALL, "message"); //$message_text , $message_mappedOCLC

    showResultsTop ($field, $count_rowCount, $limit, $to, $offset, $query,$appliedLimits, $limitlibrariesnamesstring, $alt_oclc, "" );

    showResults($result_Results, $newsearch, $pagination, $end, $db);
    echo $message_text;
} // end else not zero results

$db = null ;

?>

<?php
function showResults ($entries, $newsearch, $pagination, $end, $db) {

    foreach ($entries as $row) {
        $OCLC = $row['worldcat_oclc_nbr'] ;
        $hathi_url = $row['any_value(hathi_url)'];
        $isbn = $row['any_value(isbn)'];  // currently not used, should add it in

        $libNames = getLibNames($OCLC, $db, "htmlplain") ;

        echo <<<EOT1
        <span style="position: relative;">
             <!--<b>OCLC Number: </b><a href="http://www.worldcat.org/oclc/{$OCLC}">$OCLC</a>-->
             <br /><b>EAST Retentions: </b> {$row['cnt']} <br />
             <!--<b>TITLE:</b> {$row['any_value(title)']}<br />-->
EOT1;
        if ($row['isbn']) {
            echo "<b>ISBN: </b><a href=\"https://www.worldcat.org/isbn/" . $row['isbn'] . "\">" . $row['isbn'] . "</a><br />" ;
        }
        if ($row['issn']) {
            echo "<b>ISSN: </b><a href=\"https://www.worldcat.org/issn/" . $row['issn'] . "\">" . $row['issn'] . "</a><br />" ;
        }
        echo <<<EOT2
             <b>Retained by: </b> $libNames
EOT2;

        echo "</span>";
    } // end foreach OCLC Number


} // end showResults
?>
<?php
function showNoResults($query, $appliedLimits, $limitlibraries, $limitlibrariesnamesstring, $newsearch, $field) {
    // find and report any search and library name limits here
    if ($field == "worldcat_oclc_nbr") { $field = "OCLC Number" ;}
    echo "<p><b>No results for $field :</b> '$query' </p>";

}
?>
<?php
function getMessage($alt_oclc, $db, $oclcquery) { // alt_oclc is key new oclc, value lib name
    $messages['text'] = "";
    $messages['mappedOCLC'] = "";

    if ( $alt_oclc ) {
        foreach ($alt_oclc as $key => $value) { // $mappedOCLC will be random if more than one, that's okay
            $librariesAlt = array();
            $mappedOCLC = $key;
            foreach ($value as $id) { // get library names that have this mapped oclc
                $librariesAlt[] = getLibLimitName($id, $db);
            }
            $retsearchlink = '<a href="/searchresults?searchField=worldcat_oclc_nbr&query=' . $key . '&east_retentions_operator=equals&east_retentions=any&in_hathi=">EAST Retentions</a>';
            $oclcsearchlink = '<a href="https://worldcat.org/oclc/' . $key . '">WorldCat</a>';
            $searchlink = " ( " . $retsearchlink . " | " .  $oclcsearchlink. " )";
            //$message = " <i><sup>*</sup>SCS mapped OCLC Number ". $oclcquery . " to " . $searchlink . " for " . join(", ", $librariesAlt) . ".</i><br/>";
            $message = " <i><sup>*</sup>At " . join(", ", $librariesAlt) . " SCS mapped OCLC Number ". $oclcquery . " to " . $key . $searchlink . "</i><br/>";

            $messages['text'] = $messages['text'] . $message;
        } // end foreach atl_oclc

        $messages['text'] = "<br /><span>" . $messages['text'] . "</span>" ;
        $messages['mappedOCLC'] =  $mappedOCLC  ;
    }
    return $messages ;
} // end getMessage
?>
<?php
function showResultsTop ($field, $count_rowCount, $limit, $to, $offset, $query,$appliedLimits, $limitlibrariesnamesstring, $alt_oclc, $mapped ){
    if ($field === 'isbn') {
        echo'<span><b>You searched ISBN number</b> : ' . $query  ;
    } elseif ($field === 'issn') {
        echo'<span><b>You searched ISSN :</b> ' . $query  ;
    } else { // oclc search
        echo'<span><b>OCLC number</b> : ' . $query  ;
    }
    if ($mapped != "") {print " <b> returned no results. &nbsp; Returning results for : </b>" . $mapped  ; }
    if (count($alt_oclc) > 0) { print "<sup>*</sup>" ; }

    echo '</span>' ;
}
//$json = json_decode($contents);

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



