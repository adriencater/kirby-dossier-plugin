<?php
/**
 * Minimal footer snippet — customize or replace with your own.
 *
 * Override by creating site/snippets/dossier-footer.php in your project.
 * Must include app.js and set window.dossierConfig for asset URLs.
 */
$pluginAssets = url('media/plugins/adrien/dossier');
?>
<script>
window.dossierConfig = {
	assets: '<?= $pluginAssets ?>'
};
</script>
<script src="<?= $pluginAssets ?>/js/app.js"></script>
</body>
</html>
