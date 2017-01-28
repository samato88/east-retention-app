<?php
function test_input($data, $testfield) {

    $data = trim($data);
    if ($testfield == "query") {
        if ($_GET["searchField"] == 'worldcat_oclc_nbr') {
            if ( filter_var($data, FILTER_VALIDATE_INT)===false)  {
               // include 'paging.php'; // pagination
                exit("OCLC Number must contain only digits!  <a href='/'><button class='easttext'>New Search</button></a>");
            }
        } else { // is a title search or something random someone put in!
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
    } elseif ($testfield == "searchField" || $testfield == "east_retentions_operator" || $testfield == "in_hathi" ) {
        $data = filter_var($data, FILTER_SANITIZE_STRING);
    }
    //$data = stripslashes($data);
    //$data = htmlspecialchars($data);
    return $data;
}

?>