


<?php

function connect_db () {
$host = gethostname();
$attribs = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode="TRADITIONAL"');
if ( preg_match("/saras/", $host) ) {
//$db = new PDO('mysql:host=localhost;dbname=retentions','east','e2a2s2t2', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode="TRADITIONAL"') );
//$db = new PDO('mysql:host=localhost;unix_socket=/Applications/MAMP/tmp/mysql/mysql.sock;dbname=retentions','east','e2a2s2t2', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode="TRADITIONAL"') );
$dbhost = "mysql:host=localhost;unix_socket=/Applications/MAMP/tmp/mysql/mysql.sock;dbname=retentions" ;
$dbuser = "east" ;
$dbpass = "e2a2s2t2";
$machine = "local" ;
} else {
//$db = new pdo('mysql:unix_socket=/cloudsql/east-retention-db:us-east1:east-retention-db;dbname=retentions','root', 'e2a2s2t2', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode="TRADITIONAL"') );
$dbhost = "mysql:unix_socket=/cloudsql/east-retention-db:us-east1:east-retention-db;dbname=retentions" ;
$dbuser = "root" ;
$dbpass = "e2a2s2t2" ;
$machine = "appspot" ;
}

try {
$db = new PDO($dbhost, $dbuser, $dbpass, $attribs);
}
catch (Exception $e) {
echo 'Connection Error: ',  $e->getMessage(), "\n";
}
return $db ;
}
?>