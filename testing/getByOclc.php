
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

    $db = connect_db() ;
    $libraryid = 1 ;
    $oclc = "" ;

    // test_input !!

    $fields = "worldcat_oclc_nbr, title, east_retentions, in_hathi, hathi_ic, hathi_pd, hathi_url, titlesearch";
    $sql = "SELECT " . $fields . " FROM bib_info  WHERE worldcat_oclc_nbr = ". $oclc . AND "library_id = ". $libraryid ;

   // select title, library_id from bib_info where bib_info.worldcat_oclc_nbr = "34731450" and library_id = "1"



//   try/catch here doesn't seem to do any good - still hangs on mysql gone away error
    try {
        $resultQuery = $db->query($sql);
        $resultCount = $db->query($countQuery);
        $count = $resultCount->rowCount();
    } catch (PDOException $e) {
        print "Error!: " . $e->getMessage() . "<br/>";
        die();
    }

    $self = preg_replace('/\.php.*/', "", $self);

    if ( preg_match("/testing/", $self) ) {
        echo $sql . " <br/>" ;  $testing = "/testing" ;
    } else { $testing = "" ;}


    $newsearch = "<button><a href='" . $testing . "/'>New Search</a></button>";

    ////////////////////////////////////////////



    if ($count == 0 ) {
        // find and report any library name limits here
       // if ($limitlibraries) { echo "LIMIT: $limitlibraries" ;}

        echo "<h3>No results for $query</h3>" ;
        echo $newsearch ;
    } else {
        if ($field === 'title') {
            if ($count < $limit) { $limit = $count ;}
            if ($to > $count) { $to = $count ; }
            echo '<h3>Showing ' ;
            echo  $offset + 1 . " to $to  of $count results, sorted by relevance</h3>" ;
            echo'<p><b>You searched ' . $field. '</b> : ' . $query ;
            if (sizeof($appliedLimits) > 0) {
               echo  "<b> Limited by:</b> " ;
               echo  join(', ', $appliedLimits) ;
            }
            if (isset($limitlibrariesnamesstring)) {
                echo  $limitlibrariesnamesstring ;
            }
            echo '</p>' ;
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

       /* if( isset($_GET{'page'} ) ) {
            include '../includes/footer.html';
        }
       */
    } else { // query failed
        echo 'The SQL query failed with error '.$db->errorCode;
    } // end of query and page display

    $db = null ;

    function getLibLimitName($id, $db) {  /// SEA HERE  SEA WORKING HERE TO GET libnames to report on you searched limit
        $namesql = "SELECT library FROM inst_id WHERE Inst_ID = $id" ;
        $nameresults = $db->query($namesql) ;
        $row = $nameresults->fetch();
        $name = $row[library];
        return $name ;
    } // end getLibLimitName



    function test_input($data, $testfield) {

        $data = trim($data);
        if ($testfield == "query") {
            if ($_GET["searchField"] == 'worldcat_oclc_nbr') {
                if ( filter_var($data, FILTER_VALIDATE_INT)===false)  {
                    exit("OCLC Number must contain only digits!  ");
                }
            }
        //$data = stripslashes($data);
        //$data = htmlspecialchars($data);
        return $data;


} // end test_input


    function connect_db () {
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
            $dbhost = "mysql:unix_socket=/cloudsql/east-retention-db:us-east1:east-retention-db;dbname=retentions" ;
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
        return $db ;
    }
    ?>




<script>
    $( ".entry:even" ).css( "background-color", "#dcdcdc");
</script>

