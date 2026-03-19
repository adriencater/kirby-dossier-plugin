<?php
/**
 * Minimal footer snippet — customize or replace with your own.
 *
 * Override by creating site/snippets/dossier-footer.php in your project.
 * Must include app.js and set window.dossierConfig for asset URLs.
 */
$pluginAssets = url('media/plugins/adrien/dossier');
$siteAssetsDir = kirby()->root('assets') . '/css';
$printCssUrl = file_exists($siteAssetsDir . '/dossier-print.css')
	? url('assets/css/dossier-print.css')
	: null;
$styleCssUrl = file_exists($siteAssetsDir . '/dossier-style.css')
	? url('assets/css/dossier-style.css')
	: null;
?>
<script>
window.dossierConfig = {
	assets: '<?= $pluginAssets ?>'<?php if ($printCssUrl): ?>,
	printCss: '<?= $printCssUrl ?>'<?php endif ?><?php if ($styleCssUrl): ?>,
	styleCss: '<?= $styleCssUrl ?>'<?php endif ?>

};
</script>
<script src="<?= $pluginAssets ?>/js/app.js"></script>
</body>
</html>
