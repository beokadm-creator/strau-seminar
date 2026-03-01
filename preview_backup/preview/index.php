<?php
// Preview Entry Point for Modern Frontend
// This allows viewing the site with the new theme without affecting the live site

define('_THEME_PREVIEW_', true);
$_GET['theme'] = 'preview_modern';

// Go one level up to include the main index
chdir('..');
include_once('./index.php');
?>