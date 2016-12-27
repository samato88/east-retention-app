<?php

function paging($page, $to, $count) {

    $querystring =  $_SERVER['QUERY_STRING'] ;
    $self = htmlspecialchars($_SERVER['PHP_SELF']) ;
    $self = preg_replace('/\.php.*/', "", $self);

    if ( preg_match("/testing/", $self) ) {
        echo $sql . " <br/>" ;  $testing = "testing/" ;
    } else { $testing = "" ;}

    $pagination = "" ;
    $end = "" ;

    if( $page > 1 ) { // need a previous button
        $pagination = $pagination . "<a href = \"/$testing" . "searchresults?$querystring&page=" . ($page - 1) . "\"><button class='easttext'> Previous </button></a> ";

    }
    if($to < $count) { // need a next button
       $pagination = $pagination . "<a href = \"/$testing" . "searchresults?$querystring" . "&page=" . ($page + 1) . "\"><button class='easttext'> Next  </button></a>";

    }

    $newsearch = "<a href='/" . $testing . "'><button class='easttext'>New Search</button></a>";

    if ($count > 100) {
        $end = "<a href = \"/$testing" . "searchresults?$querystring" . "&page=" . ((int) ($count / 25) +1) . "\"><button class='easttext right'> Jump to End  </button></a>";

    }

     return array ($pagination, $newsearch, $end) ;
} // end paging
 ?>