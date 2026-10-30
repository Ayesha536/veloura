<?php
/**
 * VELOURA — Admin logout.
 */
require_once __DIR__ . '/../config/config.php';

logout_admin();
session_regenerate_id(true);

set_flash('success', 'You have been signed out of the admin panel.');
redirect(url('admin/login.php'));
