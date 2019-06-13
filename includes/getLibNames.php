<?php
function getLibNames($nbr, $db, $format) {
    // options for format:  json, htmlstyled, htmlplain, inst_id, text?

    $nsql = "SELECT opac_url, library_id, lib_holdings, library FROM bib_info, inst_id WHERE worldcat_oclc_nbr = $nbr AND bib_info.library_id = inst_id.Inst_ID ORDER BY library" ;

    if ($format=="revoked") { // check revoked table rather than bib_info
        $nsql = "SELECT opac_url, library_id, lib_holdings, library FROM revoked_retentions, inst_id WHERE worldcat_oclc_nbr = $nbr AND revoked_retentions.library_id = inst_id.Inst_ID ORDER BY library" ;
        $format="inst_id"; // set back to inst_id for plain string results
    } else {
        $nsql = "SELECT opac_url, library_id, lib_holdings, library FROM bib_info, inst_id WHERE worldcat_oclc_nbr = $nbr AND bib_info.library_id = inst_id.Inst_ID ORDER BY library" ;
    }
    $result = $db->query($nsql) ;
    $library_names = array();
    foreach ($result as $row) {
        $id = $row['library_id'] ;
        $url = $row['opac_url'] ;
        $name = trim($row['library']) ;
        $holdings = $row['lib_holdings'] ;
        if ($url =="") { $format = "text" ;}

        switch ($format)  {
            case "text":
                $name = $name . " (". $url . ")";
                break ;
            case "inst_id":
                $name = $id;
                break ;
            case "htmlplain":
                $name = '<a href="' . $url . '">' . $name . "</a>";
                if ($holdings != "") { // if holdings available
                    $name = $name .
                        "(<span class='tooltiptext' >" . $holdings . "</span> )";
                }
                break ;
            case "htmlstyled":
                $name = '<div class="tooltip"><a href="' . $url . '">' . $name . "</a>";
                if ($holdings != "") { // if holdings available
                    $name = $name .
                        "<span class='tooltiptext' >" . $holdings . "</span>";
                }
                $name = $name . "</div>" ;
                break ;

        }

        array_push($library_names,  $name) ;
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
    $option = "";
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