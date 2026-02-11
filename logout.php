<?php
require_once('utils.php');

$client = getTwitterClient();
$client->logout();
header("Location: login.php");
exit;
?>
