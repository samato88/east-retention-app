<?php

function test_user($user)
{
    $users = array(
        "test@example.com" => 1,
        "samato@blc.org" => 1,
        "aperricci@blc.org" => 2,

    );

    if ($users[$user]) {

        return ($users[$user]) ;
    } else {
        return 0 ;
    }

} // end function test_user