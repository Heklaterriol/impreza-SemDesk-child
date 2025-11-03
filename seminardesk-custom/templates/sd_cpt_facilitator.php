<?php
/**
 * all the facilitator pages are redirected to ueber-uns
 */
 
$host = $_SERVER['HTTP_HOST'];
header("Location: https://$host/ueber-uns/");
die();
?>