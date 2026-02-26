<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

require_once __DIR__ . '/includes/cache.php';

// Clear transients/options created by this plugin.
wcfl_clear_all_cache();
