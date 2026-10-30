<?php
/**
 * VELOURA — Sign out (clears both customer and admin sessions safely).
 */
require_once __DIR__ . '/config/config.php';

$wasAdmin = current_admin() !== null;
logout_user();
logout_admin();

session_regenerate_id(true);

if ($wasAdmin) {
    set_flash('success', 'You have been signed out of the admin panel.');
    redirect(url('admin/login.php'));
}

set_flash('success', 'You have been signed out. À bientôt!');
redirect(url('index.php'));
