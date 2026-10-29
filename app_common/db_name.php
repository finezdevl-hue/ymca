<?php
/**
 * Backward compatibility bridge for DB Name.
 * Canonical configuration in config/database.php
 */
$config = require __DIR__ . '/../config/database.php';
$db_name = $config['database'];
