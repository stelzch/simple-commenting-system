<?php

include 'config.php';

$db = new PDO("sqlite:$db_path");
if(!$db)
    die("No DB");

$res = $db->query('SELECT COUNT(email_uid) AS count FROM comments
    WHERE validated != 1');

$unvalidated = intval($res->fetch(PDO::FETCH_NAMED)["count"]);

header('Content-Type', 'application/json');
echo json_encode(array("unvalidated" => $unvalidated)) . "\n";

?>
