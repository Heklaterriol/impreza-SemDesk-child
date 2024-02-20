<?php
/**
 * Custom template for agenda page, taxonomy sd_txn_dates with upcoming event dates.
 * 
 * all the pages are redirected to the event listing
 */
 
$host = $_SERVER['HTTP_HOST'];
header("Location: https://$host/termine-seminare/");
die();
?>