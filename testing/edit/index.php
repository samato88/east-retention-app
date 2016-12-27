<?php # Looks for current Google account session
use google\appengine\api\users\User;
use google\appengine\api\users\UserService;

$user = UserService::getCurrentUser();
include 'includes/connect.php';
include 'includes/getLibNames.php';
include 'includes/users.php';


//echo 'Hello, ' . $user->getEmail();


if (test_user($user->getEmail()) != 0) { // if they are in the users list
    header('Location: ' . '/testing/edit/updates') ;

//    echo 'Hello, ' . htmlspecialchars($user->getEmail()) . '<br/>';
  //  echo 'You are associated with ' . getLibLimitName(test_user($user->getEmail()), $db) ;
    //echo test_user($user->getNickname()) ;
}
else {
//    header('Location: ' . UserService::createLoginURL($_SERVER['REQUEST_URI']));
  header('Location: ' . '/testing/edit/nologin') ;
}


?>