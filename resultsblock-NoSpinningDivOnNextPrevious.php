
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
$query = test_input($_GET["query"], "query") ;
$field = test_input($_GET["searchField"], "searchfield") ;
$retentions = test_input($_GET["east_retentions"], "east_retentions") ;
$retentionsOperator = test_input($_GET["east_retentions_operator"], "east_retentions_operator");
$in_hathi = test_input($_GET["in_hathi"], "in_hathi");
$page = test_input($_GET{'page'}, "page");
$displaylimit = 25;
$limit = 25 ;
$fields = "worldcat_oclc_nbr, title, east_retentions, in_hathi, hathi_ic, hathi_pd, hathi_url, titlesearch";


if( isset($_GET{'page'} ) ) {
    $page = $_GET{'page'} ;
    $offset = $limit * ($page-1) ;
    include '../includes/header.html';
}else {
    $page = 1;
    $offset = 0;
}


if(!empty($_GET['libraries'])){ // Loop to store  values of individual checked checkbox.
    $limitlibraries = array();
    foreach($_GET['libraries'] as $selected){
        $valselected = test_input($selected, "libraries") ;
        array_push($limitlibraries,  "library_id = " . $selected) ;
    }
    $limitlibrary = " AND ( " . implode(' OR ',$limitlibraries) . ")" ;
}
//SELECT worldcat_oclc_nbr, title, east_retentions, in_hathi, hathi_ic, hathi_pd, MATCH (title) AGAINST ('"books"' IN NATURAL LANGUAGE MODE) as Relevance FROM bib_info WHERE MATCH ( title) AGAINST ('"books"' IN NATURAL LANGUAGE MODE) and (library_id = 1 or library_id=4934)

$host = gethostname();
$attribs = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode="TRADITIONAL"');
if ( preg_match("/saras/", $host) ) {
    //$db = new PDO('mysql:host=localhost;dbname=retentions','east','e2a2s2t2', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode="TRADITIONAL"') );
    //$db = new PDO('mysql:host=localhost;unix_socket=/Applications/MAMP/tmp/mysql/mysql.sock;dbname=retentions','east','e2a2s2t2', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode="TRADITIONAL"') );
    $dbhost = "mysql:host=localhost;unix_socket=/Applications/MAMP/tmp/mysql/mysql.sock;dbname=retentions" ;
    $dbuser = "east" ;
    $dbpass = "e2a2s2t2";
    $machine = "local" ;
} else {
    //$db = new pdo('mysql:unix_socket=/cloudsql/east-retention-db:us-east1:east-retention-db;dbname=retentions','root', 'e2a2s2t2', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode="TRADITIONAL"') );
    $dbhost = "mysql:unix_socket=/cloudsql/east-retention-db:us-east1:eastretentiondb;dbname=retentions" ;
    $dbuser = "root" ;
    $dbpass = "e2a2s2t2" ;
    $machine = "appspot" ;
}

try {
    $db = new PDO($dbhost, $dbuser, $dbpass, $attribs);
}
catch (Exception $e) {
    echo 'Connection Error: ',  $e->getMessage(), "\n";
}

if ($field === "title")  {
    $boolstring = "" ;
    $boolstring = remove_stopwords($query,$boolstring);
    $titlelike  = remove_punctuation($query) ;

    $sql = "SELECT " . $fields . ", MATCH (". $field .") AGAINST ('" . $boolstring . "' IN BOOLEAN MODE) AND titlesearch LIKE '" . $titlelike ."%'  FROM bib_info  WHERE MATCH ( " . $field .") AGAINST ('" . $boolstring . "' IN BOOLEAN MODE) AND titlesearch LIKE '" . $titlelike ."%'";
    //$sql = "SELECT " . $fields . ", MATCH (". $field .") AGAINST ('" . $boolstring . "' IN BOOLEAN MODE) AND title LIKE '" . $query ."%'  FROM bib_info  WHERE MATCH ( " . $field .") AGAINST ('" . $boolstring . "' IN BOOLEAN MODE) AND title LIKE '" . $query ."%'";

    //$sql = "SELECT " . $fields . ", MATCH (". $field .") AGAINST ('" . $query . "' IN NATURAL LANGUAGE MODE) as Relevance FROM bib_info  WHERE MATCH ( " . $field .") AGAINST ('" . $query . "' IN NATURAL LANGUAGE MODE)";
    //SELECT worldcat_oclc_nbr, title, east_retentions, in_hathi, hathi_ic, hathi_pd, MATCH (title) AGAINST ('"harry potter and the goblet of fire"' IN NATURAL LANGUAGE MODE) as Relevance FROM bib_info WHERE MATCH (title) AGAINST ('"harry potter and the goblet of fire"' IN NATURAL LANGUAGE MODE) and hathi_pd = 'F'
    //SELECT worldcat_oclc_nbr FROM bib_info WHERE MATCH (title) AGAINST ('books' IN NATURAL LANGUAGE MODE)-slow
    //SELECT worldcat_oclc_nbr, title, east_retentions, in_hathi, hathi_ic, hathi_pd, MATCH (title) AGAINST ('books' IN NATURAL LANGUAGE MODE) as Relevance FROM bib_info HAVING Relevance > 0.1 ORDER BY Relevance DESC - slow - 6 seconds
    //SELECT worldcat_oclc_nbr, title, east_retentions, in_hathi, hathi_ic, hathi_pd, MATCH (title) AGAINST ('books' IN NATURAL LANGUAGE MODE) as Relevance FROM bib_info ORDER BY Relevance DESC - faster 1 second
    //$sort = "  GROUP BY worldcat_oclc_nbr ORDER BY title_sort " ;
} else { // only other option is worldcat_oclc_nbr
    $sql = "SELECT " . $fields . " FROM bib_info  WHERE worldcat_oclc_nbr = ". $query ;
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

} // end if retentions not any

if ($in_hathi != "") {
    switch ($in_hathi) {
        case "T":
            $hsql = " AND in_hathi = 'T'" ;
            break ;
        case "F":
            $hsql = " AND in_hathi = 'F'" ;
            break ;
        case "hathi_pd":
            $hsql = " AND hathi_pd = 'T'" ;
            break ;
        case "hathi_ic":
            $hsql = " AND hathi_ic = 'T'" ;
            break ;
    }
    $sql = $sql .  $hsql;
}

if ($limitlibrary) {
    $sql = $sql . $limitlibrary ;
}
$sql = $sql . " GROUP BY worldcat_oclc_nbr " ;
$countQuery = $sql ;
$sql = $sql .  " LIMIT " . $offset . "," . $limit ;

//   try/catch here doesn't seem to do any good - still hangs on mysql gone away error
try {
    $resultQuery = $db->query($sql);
    $resultCount = $db->query($countQuery);
    $count = $resultCount->rowCount();
} catch (PDOException $e) {
    print "Error!: " . $e->getMessage() . "<br/>";
    die();
}
// pagination
$querystring =  $_SERVER['QUERY_STRING'] ;
$self = htmlspecialchars($_SERVER['PHP_SELF']) ;
$pagination = "" ;
$to = $limit * $page  ;

$self = preg_replace('/\.php.*/', "", $self);
if ( preg_match("/testing/", $self) ) { echo $sql . " <br/>" ; }

if( $page > 1 ) { // need a previous button
    $pagination = $pagination . "<button><a href = \"$self?$querystring&page=" . ($page - 1) . "\"> Previous </a></button> ";
}
if($to < $count) { // need a next button
    $pagination = $pagination . "<button><a href = \"$self?$querystring" . "&page=" . ($page + 1) . "\"> Next  </a></button>";
}

$newsearch = "<button><a href='/'>New Search</a></button>";

if ($count == 0 ) {
    // find and report any library name limits here
    if ($limitlibraries) { echo "LIMIT: $limitlibraries" ;}

    echo "<h3>No results for $query</h3>" ;
    echo $newsearch ;
} else {
    if ($field === 'title') {
        if ($count < $limit) { $limit = $count ;}
        if ($to > $count) { $to = $count ; }
        echo '<h3>Showing ' ;
        echo  $offset + 1 . " to $to  of $count results, sorted by relevance</h3>" ;
        echo'<p>You searched ' . $field. ' : ' . $query . '</p>' ;
    } else { // oclc search
        echo'<p>You searched OCLC number : ' . $query . '</p>' ;
    }
    echo $newsearch ;
    echo $pagination ;
} // end else not zero results

if ($resultQuery !== false && $resultCount !== false) {
    foreach ($resultQuery as $row) {
        $OCLC = $row['worldcat_oclc_nbr'] ;
        $hathi = $row['in_hathi'];
        $hathi_pd = $row['hathi_pd'];
        $hathi_ic = $row['hathi_ic'];
        $hathi_url = $row['hathi_url'];

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


    echo $pagination ; // put pagination at the bottom of the page too

    if( isset($_GET{'page'} ) ) {
        include '../includes/footer.html';
    }

} else { // query failed
    echo 'The SQL query failed with error '.$db->errorCode;
} // end of query and page display

$db = null ;

function getLibLimitName ($id, $db) {  /// SEA HERE  SEA WORKING HERE TO GET libnames to report on you searched limit
    // $namesql = "SELECT   library FROM inst_id WHERE inst_id.Inst_ID = $id" ;
    // $nameresults = $db->query($namesql) ;

} // end getLibLimitName

function getLibNames($nbr, $db) {
    $nsql = "SELECT opac_url, library_id, library FROM bib_info, inst_id WHERE worldcat_oclc_nbr = $nbr AND bib_info.library_id = inst_id.Inst_ID" ;
    $result = $db->query($nsql) ;
    $library_names = array();
    foreach ($result as $row) {
        $id = $row['library_id'] ;
        $url = $row['opac_url'] ;
        $name = trim($row['library']) ;
        /* This is now handled pre load in datafordatabase script
                    switch ($id) { // cny libs lack opac urls in data - reconstruct search
                        case 1: //http://alicat.adelphi.edu/search~S1/?searchtype=o&searcharg=1074025
                            $url = "http://alicat.adelphi.edu/search~S1/?searchtype=o&searcharg=" . $nbr ;
                            break;
                        case 3: // bard
                            $url = "http://library.bard.edu/search~S1/?searchtype=o&searcharg=" . $nbr ;
                            break;
                        case 13: // hamilton
        //http://lib.hamilton.edu/vwebv/holdingsInfo?bibId=193098
                            break;
                        //case 15: //haverford ended up not being retention partner, was in VSS1 (?)
                        case 28: // union
                            $url = "http://libraryopac.union.edu/search~S1/?searchtype=o&searcharg=" . $nbr ;
                            break;
                        case 36: // vassar
                            $url = "http://vaslib.vassar.edu/search~S1/?searchtype=o&searcharg=" . $nbr ;
                            break;
                    }
        */
        if ($url !="") {
            $name = '<a href="' . $url . '">' . $name . "</a>" ;
        }
        array_push($library_names,  $name) ;
        //$library_names = $library_names . $name ;
    } // end foreach row
    return join(', ',$library_names);
    // return $nsql;
} // end getLibNames

function test_input($data, $testfield) {

    $data = trim($data);
    if ($testfield == "query") {
        if ($_GET["searchField"] == 'worldcat_oclc_nbr') {
            if ( filter_var($data, FILTER_VALIDATE_INT)===false)  {
                exit("OCLC Number must contain only digits!  ");
            }
        } else { // is a title search
            $data = filter_var($data, FILTER_SANITIZE_STRING);
            $data = strtolower($data);
        }
    } elseif ($testfield == "east_retentions" || $testfield == "page" ) {
        if ($data && $data !== "any" ) {
            if ( filter_var($data, FILTER_VALIDATE_INT)===false)  {
                $data = "1" ; // if it isn't a number just set it to one
            }
        }
    } elseif ($testfield == "searchField" || $testfield == "east_retentions_operator" || $testfield == "in_hathi" ) {
        $data = filter_var($data, FILTER_SANITIZE_STRING);
    }
    //$data = stripslashes($data);
    //$data = htmlspecialchars($data);
    return $data;
}
function remove_stopwords($query,$boolstring) {
    // $querysansand = preg_replace('/and/i', '', $query) ;
    // stopwords are in innodb_ft_default_stopwords table - copied here for expediency and adding 'and'
    $stopwords = explode(',', "a,about,an,and,are,as,at,be,by,com,de,en,for,from,how,i,in,is,it,la,of,on,or,that,the,this,to,was,what,when,where,who,will,with,und,www") ;
    $words = preg_split('/\s+/', $query);
    $searchwords = array_diff($words, $stopwords);

    foreach ($searchwords as $value) {     // split and add +
        $boolstring .= " +" . $value;
    }

    return $boolstring ;
}
function remove_punctuation($query) {
    $punctuation = explode(' ', "$ # @ ~ ! & * ( ) [ ] ; . , : ? ^ ' \"") ;
    $words = preg_split('/\s+/', $query);
    $queryarray = array_diff($words, $punctuation);
    $query = implode(' ', $queryarray);
    return $query ;
}
?>


<script>
    $( ".entry:even" ).css( "background-color", "#dcdcdc");
</script>

