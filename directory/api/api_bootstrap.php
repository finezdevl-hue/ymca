<?php
/**
 * Shared API Bootstrap
 * Denies access by default (HTTP 401) if user is not authenticated.
 */
require_once __DIR__ . '/../../includes/api_auth.php';
require_once __DIR__ . '/../../includes/db.php';

// Deny by default: require login for every API endpoint
$current_login_id = require_login();

// Initialize default database connection and transaction manager for all API endpoints
$db = new Database();
$conn = $db->getConnection();
$tm = new TransactionManager($conn);
