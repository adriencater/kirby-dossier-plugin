<?php snippet('dossier-header') ?>

<?php if (!$authenticated): ?>
<main class="dossier-login">
	<h1><?= $page->title() ?></h1>
	<?php if ($loginError): ?>
		<p class="login-error">Incorrect password.</p>
	<?php endif ?>
	<form method="post" action="<?= $page->url() ?>">
		<label for="password">Password</label>
		<input type="password" name="password" id="password" required>
		<button type="submit">Enter</button>
	</form>
</main>

<?php else: ?>
<main class="dossier" data-mode="private">
	<nav class="dossier-nav">
		<?php snippet('dossier-toc', ['pages' => $children, 'mode' => 'private']) ?>
		<div class="dossier-options" id="dossier-options" hidden>
			<h3>Export options</h3>
			<ul>
				<li><label><input type="checkbox" id="opt-cover"> Cover page</label></li>
				<li class="opt-toc-item" hidden><label><input type="checkbox" id="opt-toc"> Table of contents</label></li>
				<li><label><input type="checkbox" id="opt-meta"> Include metadata</label></li>
				<li><label><input type="checkbox" id="opt-notes"> Include notes</label></li>
				<li><label><input type="checkbox" id="opt-pages"> Show as pages</label></li>
			</ul>
			<div class="btn-pdf-group">
				<button class="btn-pdf" id="btn-pdf">PDF</button>
				<button class="btn-pdf-download" id="btn-pdf-download" title="Download PDF">&#x2193;</button>
				<span id="pdf-spinner" class="pdf-spinner" style="display:none"></span>
			</div>
		</div>
	</nav>
	<section class="dossier-main" id="dossier-main">
		<p class="dossier-empty">Select a fiche from the table of contents.</p>
	</section>
</main>
<?php endif ?>

<?php snippet('dossier-footer') ?>
