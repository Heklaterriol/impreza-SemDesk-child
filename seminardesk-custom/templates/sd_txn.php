<?php
/**
 * The fallback template for taxonomy 
 * used when specific taxonomy template for CPT doesn't exist.
 * 
 * @package SeminardeskPlugin
 *
 * all the pages are redirected to the event listing
 */
 
$host = $_SERVER['HTTP_HOST'];
header("Location: https://$host/termine-seminare/");
die();
?>