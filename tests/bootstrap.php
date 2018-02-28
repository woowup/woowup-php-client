<?php
date_default_timezone_set('UTC');

require __DIR__.'/../vendor/autoload.php';

try {
	$dotenv = new Dotenv\Dotenv(__DIR__.'/../');
	$dotenv->load();
} catch (Exception $e) {
	// do nothing
}