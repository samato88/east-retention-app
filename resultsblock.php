
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
// if title we need 3 variants: w/o stopwords, w/o punctuation , original to report back
$field = test_input($_GET["searchField"], "searchfield") ; // worldcat_oclc_nbr or titlesearch or isbn
$retentionsOperator = test_input($_GET["east_retentions_operator"], "east_retentions_operator");
$retentions = test_input($_GET["east_retentions"], "east_retentions") ;
if ($_GET["in_hathi"]) {
    $in_hathi = test_input($_GET["in_hathi"], "in_hathi");
}

$displaylimit = 25;
$limit = 25 ;
$fields = "bib_info.worldcat_oclc_nbr, title, east_retentions, in_hathi, hathi_ic, hathi_pd, hathi_url, titlesearch, isbn";


if( isset($_GET{'page'} ) ) {
    $page = test_input($_GET{'page'}, "page");
    $offset = $limit * ($page-1) ;
    //include '../includes/header.html';
}else {
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
    }
    $limitlibrary = " AND ( " . implode(' OR ',$limitlibraries) . ")" ;
    $limitlibrariesnamesstring = "  <b>Retained at</b>: " . implode(" or ", $limitlibrariesnames) ;
}
if ($field === "titlesearch")  {
    $boolstring = "" ;
    $titlelike  = remove_punctuation($query) ;
    $boolstring = remove_stopwords($titlelike,$boolstring);


    if (strlen($boolstring) === 2) { // +ww - two letter word - search it
        $sql = "SELECT " . $fields . " FROM bib_info  WHERE  title LIKE '" . $titlelike . " %'";

    } else  {
        $sql = "SELECT " . $fields . ", MATCH (" . $field . ") AGAINST ('" . $boolstring . "' IN BOOLEAN MODE) AND titlesearch LIKE '" . $titlelike . "%'  FROM bib_info  WHERE MATCH ( " . $field . ") AGAINST ('" . $boolstring . "' IN BOOLEAN MODE) AND titlesearch LIKE '" . $titlelike . "%'";
    }
    //$sql = "SELECT " . $fields . ", MATCH (". $field .") AGAINST ('" . $boolstring . "' IN BOOLEAN MODE) AND title LIKE '" . $query ."%'  FROM bib_info  WHERE MATCH ( " . $field .") AGAINST ('" . $boolstring . "' IN BOOLEAN MODE) AND title LIKE '" . $query ."%'";

    //$sql = "SELECT " . $fields . ", MATCH (". $field .") AGAINST ('" . $query . "' IN NATURAL LANGUAGE MODE) as Relevance FROM bib_info  WHERE MATCH ( " . $field .") AGAINST ('" . $query . "' IN NATURAL LANGUAGE MODE)";
    //SELECT worldcat_oclc_nbr, title, east_retentions, in_hathi, hathi_ic, hathi_pd, MATCH (title) AGAINST ('"books"' IN NATURAL LANGUAGE MODE) as Relevance FROM bib_info WHERE MATCH ( title) AGAINST ('"books"' IN NATURAL LANGUAGE MODE) and (library_id = 1 or library_id=4934)
    //SELECT worldcat_oclc_nbr, title, east_retentions, in_hathi, hathi_ic, hathi_pd, MATCH (title) AGAINST ('"harry potter and the goblet of fire"' IN NATURAL LANGUAGE MODE) as Relevance FROM bib_info WHERE MATCH (title) AGAINST ('"harry potter and the goblet of fire"' IN NATURAL LANGUAGE MODE) and hathi_pd = 'F'
    //SELECT worldcat_oclc_nbr FROM bib_info WHERE MATCH (title) AGAINST ('books' IN NATURAL LANGUAGE MODE)-slow
    //SELECT worldcat_oclc_nbr, title, east_retentions, in_hathi, hathi_ic, hathi_pd, MATCH (title) AGAINST ('books' IN NATURAL LANGUAGE MODE) as Relevance FROM bib_info HAVING Relevance > 0.1 ORDER BY Relevance DESC - slow - 6 seconds
    //SELECT worldcat_oclc_nbr, title, east_retentions, in_hathi, hathi_ic, hathi_pd, MATCH (title) AGAINST ('books' IN NATURAL LANGUAGE MODE) as Relevance FROM bib_info ORDER BY Relevance DESC - faster 1 second
    //$sort = "  GROUP BY worldcat_oclc_nbr ORDER BY title_sort " ;
} else if ($field === "worldcat_oclc_nbr") {
    // check if OCLC in is table of oclcs updated by SCS
    $sqltest = "SELECT worldcat_oclc_nbr FROM local_worldcat_oclc_nbr WHERE local_oclc_nbr =".$query." GROUP BY worldcat_oclc_nbr";
    try {
        $updatedOCLC = $db->query($sqltest)->fetchAll()[0][0];
    } catch (PDOException $e) {
        print "Error!: " . $e->getMessage() . "<br/>";
        die();
    }
    if ($updatedOCLC != "") { $oquery = $updatedOCLC ; } else { $oquery = $query ; }

    $sql = "SELECT " . $fields . " FROM bib_info  WHERE bib_info.worldcat_oclc_nbr = ". $oquery ;

} else { //  ($field === "isbn") {
    $sql = "SELECT " . $fields . " FROM bib_info  WHERE isbn = '" . $query . "'" ;
}

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

    $sql = $sql . " AND east_retentions $retentionsOperator $retentions" ;
    array_push($appliedLimits,  "east retentions $retentionsOperator $retentions") ;

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
    $sql = $sql .  $hsql;
    array_push($appliedLimits,  $h) ;
}

if (isset($limitlibrary)) {
    $sql = $sql . $limitlibrary ;
}
$sql = $sql . " GROUP BY worldcat_oclc_nbr ORDER BY titlesearch" ;
$countQuery = $sql ;
$sql = $sql .  " LIMIT " . $offset . "," . $limit ;

//   try/catch here doesn't seem to do any good - still hangs on mysql gone away error
try {
    $resultQuery = $db->query($sql);
    $resultCount = $db->query($countQuery);
    $count = $resultCount->rowCount();
    $resulting = $resultQuery->fetchAll();

} catch (PDOException $e) {
    print "Error!: " . $e->getMessage() . "<br/>";
    die();
}

// pagination
$to = $limit * $page  ;
list ($pagination, $newsearch, $end) = paging($page, $to, $count, $testing) ;

if ( preg_match("/testing/", htmlspecialchars($_SERVER['PHP_SELF']) ) ) { echo "SQL:<br/> " . $sql . " <br/>" ;}

if ($count == 0 ) {
    // find and report any search and library name limits here

    echo "<b>No results for </b>'$query'" ;
    listLimits($appliedLimits, $limitlibraries);
    if (isset($limitlibrariesnamesstring)) {echo  $limitlibrariesnamesstring ; }
    echo "<br />" ;
    //echo $newsearch ;
} else {
    if ($field === 'titlesearch') {
        if ($count < $limit) { $limit = $count ;}
        if ($to > $count) { $to = $count ; }
        echo '<h3>Showing ' ;
        echo  $offset + 1 . " to $to  of $count results, sorted by relevance</h3>" ;
        echo'<p><b>You searched title</b> : ' . $query ;
        listLimits($appliedLimits, $limitlibrariesnamesstring );
        if (isset($limitlibrariesnamesstring)) {echo  $limitlibrariesnamesstring ; }
        echo '</p>' ;
    } else { // oclc search
        echo'<p>You searched OCLC number : ' . $query  ;
        //SEA HERE
        if ($query != $resulting[0][0]) {
            echo " <i>(SCS mapped this OCLC number to " . $resulting[0][0] . ")</i>" ;
        }
        echo '</p>' ;
    }

    echo $newsearch ;
    echo $pagination ;
    echo $end ;
} // end else not zero results

if ($resultQuery !== false && $resultCount !== false) {
    foreach ($resulting as $row) {
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
                                       <b>EAST Retentions: </b> {$row['east_retentions']} <br />
                                        <b>Hathi: </b> $hathi<br />
                                        <b>Retained by: </b> $libNames
                                   </div>
EOT;

    } // end foreach OCLC Number


    echo $newsearch ;
    echo $pagination ;
    echo $end ;


} else { // query failed
    echo 'The SQL query failed with error '.$db->errorCode;
} // end of query and page display

$db = null ;

?>

<script>
    $( ".entry:even" ).css( "background-color", "#dcdcdc");
</script>

