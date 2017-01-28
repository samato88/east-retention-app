<?php

// valid users for editing "user@place.edu" => library_i
function test_user($user)
{
    $users = array(
        "test@example.com" => 1,
        "joe@my.edu" = 2
    );

    if ($users[$user]) {

        return ($users[$user]) ;
    } else {
        return 0 ;
    }

} // end function test_user