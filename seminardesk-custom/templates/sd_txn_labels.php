<?php
/**
 * all the label pages are redirected to the event listing
 */
 
$host = $_SERVER['HTTP_HOST'];
header("Location: https://$host/termine-seminare/");
die();
?>