<?php // used by footer and pagination
$self = htmlspecialchars($_SERVER['PHP_SELF']) ;
$self = preg_replace('/\.php.*/', "", $self);

if ( preg_match("/testing/", $self) ) {
    $testing = "testing/" ;
} else { $testing = "" ;}
?>