<?php snippet('dossier-header') ?>

<main class="dossier" data-mode="public">
	<nav class="dossier-nav">
		<?php if ($children->count()): ?>
			<?php snippet('dossier-toc', ['pages' => $children]) ?>
		<?php else: ?>
			<p>No published documents.</p>
		<?php endif ?>
	</nav>
	<section class="dossier-main" id="dossier-main"
		data-show-authors="<?= $showAuthors ? 'true' : 'false' ?>"
		data-show-notes="<?= $showNotes ? 'true' : 'false' ?>">
		<p class="dossier-empty">Select a document from the list.</p>
	</section>
</main>

<?php snippet('dossier-footer') ?>
