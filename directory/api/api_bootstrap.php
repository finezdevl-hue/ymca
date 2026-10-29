<?php
/**
 * Shared API Bootstrap
 * Denies access by default (HTTP 401) if user is not authenticated.
 */
require_once __DIR__ . '/../../includes/api_auth.php';
require_once __DIR__ . '/../../includes/db.php';

// Deny by default: require login for every API endpoint
$current_login_id = require_login();
