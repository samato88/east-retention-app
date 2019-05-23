<?
function listLimits( $appliedLimits) {
    if (sizeof($appliedLimits) > 0) {
        echo  "<b> Limited by:</b> " ;
        echo  join(', ', $appliedLimits) ;
    }

}
?>