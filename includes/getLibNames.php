<?php
function getLibNames($nbr, $db) {
    $nsql = "SELECT opac_url, library_id, library FROM bib_info, inst_id WHERE worldcat_oclc_nbr = $nbr AND bib_info.library_id = inst_id.Inst_ID" ;
    $result = $db->query($nsql) ;
    $library_names = array();
    foreach ($result as $row) {
        $id = $row['library_id'] ;
        $url = $row['opac_url'] ;
        $name = trim($row['library']) ;

        if ($url !="") {
            $name = '<a href="' . $url . '">' . $name . "</a>" ;
        }
        array_push($library_names,  $name) ;
        //$library_names = $library_names . $name ;
    } // end foreach row
    return join(', ',$library_names);
} // end getLibNames


function getLibLimitName($id, $db) {  // libnames to report on you searched limit
    $namesql = "SELECT library FROM inst_id WHERE Inst_ID = $id" ;
    $nameresults = $db->query($namesql) ;
    $row = $nameresults->fetch();
    $name = $row[0];
    return $name ;
} // end getLibLimitName

function listLibraries($db, $id) {
    $sql = "SELECT library, Inst_ID FROM inst_id ORDER BY library" ;
    $nameresults = $db->query($sql) ;
    $library_names = array();
    foreach ($nameresults as $row) {
        $libid = $row['Inst_ID'] ;

        if ($libid == $id) {
            next ;
        } // don't include current lib in select list
        else {
            $libname = $row['library'];
            $option .= "<option value='$libid'>$libname</option>";
        }
    } // end foreach nameresult
    return $option ;
} // end listLibraries
?>