<?php
/**
 * Minimal header snippet — customize or replace with your own.
 *
 * Override by creating site/snippets/dossier-header.php in your project.
 * Must include the plugin's CSS and paged.js for the preview to work.
 *
 * Plugin assets are served at:
 *   /media/plugins/adrien/dossier/css/style.css
 *   /media/plugins/adrien/dossier/js/paged.js
 *   /media/plugins/adrien/dossier/js/app.js
 */
$pluginAssets = url('media/plugins/adrien/dossier');
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= $page->title() ?> — <?= $site->title() ?></title>
	<link rel="stylesheet" href="<?= $pluginAssets ?>/css/style.css">
	<script src="<?= $pluginAssets ?>/js/paged.js"></script>
</head>
<body>
