<?php

define('USE_SQLITE', true);

// Database Configuration
// Set USE_SQLITE=true or define USE_SQLITE before including this file for SQLite

// MySQL settings (used if SQLite is not enabled)
$dbHost = "localhost";
$dbUser = "";
$dbPW = "";
$dbName = "";

// Use SQLite for local development
// Set environment variable: USE_SQLITE=true
// Or define: define('USE_SQLITE', true);
// Or set custom path: define('SQLITE_PATH', '/path/to/faucet.db');

include(__DIR__ . "/db.php");
$mysqli = createDatabaseConnection();

// Website
$Website_Url = "";

?>
