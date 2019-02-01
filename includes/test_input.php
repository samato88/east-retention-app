<?php
function test_input($data, $testfield) { //http://php.net/manual/en/filter.filters.sanitize.php

    $data = trim($data);
    if ($testfield == "query") {
        if ($_GET["searchField"] == 'worldcat_oclc_nbr') {
	    $data = preg_replace('/^0+/', "", $data); // strip leading zeros		
            if (filter_var($data, FILTER_VALIDATE_INT) === false) {
                exit("OCLC Number must contain only digits! <a href='/'><button class='easttext'>New Search</button></a>");
            }
        } elseif ($_GET["searchField"] == 'isbn') {
            //if (filter_var($data, FILTER_VALIDATE_INT) === false) {
              //  exit("ISBN must contain only digits! <a href='/'><button class='easttext'>New Search</button></a>");
            //}
            $data = filter_var($data, FILTER_SANITIZE_NUMBER_INT) ;
            $data = preg_replace('/-/', "", $data) ;// remove dashes
            $data = str_pad($data, 13, "0", STR_PAD_LEFT);

        } elseif($_GET["searchField"] == 'issn') {
            $data = filter_var($data, FILTER_SANITIZE_NUMBER_INT) ;

        }else { // is a title search or something random someone put in!
            if (strlen($data)>250) {
                $data = substr($data, 0, 249); # no titles longer than 250
            }
            $data = filter_var($data, FILTER_SANITIZE_STRING);
            $data = strtolower($data);
        }
    } elseif ($testfield == "east_retentions" || $testfield == "page" ) {
        if ($data && $data !== "any" ) {
            if ( filter_var($data, FILTER_VALIDATE_INT)===false)  {
                $data = "1" ; // if it isn't a number just set it to one
            }
        }
    } elseif ($testfield ==  "libraries" ) {
        $data = filter_var($data, FILTER_SANITIZE_NUMBER_INT);
    } elseif ($testfield == "searchField" || $testfield == "east_retentions_operator" || $testfield == "in_hathi" || $testfield == "rectype" || $testfield = "in_ia") {
        $data = filter_var($data, FILTER_SANITIZE_STRING);
    }
    //$data = stripslashes($data);
    //$data = htmlspecialchars($data);
    return $data;
}

?>