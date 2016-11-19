<?php

function remove_punctuation($query)
{
    $query = preg_replace('/^The /i', '', $query);
    $query = preg_replace('/^A /i', '', $query);
    $query = preg_replace('/^An /i', '', $query);

    $query = htmlspecialchars_decode($query);
    $query = preg_replace('/&#39;/i', '', $query); // hack for ' - decode above should have done it??
    $query = preg_replace('/[^A-Za-z0-9 ]+/', '', $query); #

    $query = preg_replace('/[ ]{2,}+/i', ' ', $query); #


    return $query;

}

?>