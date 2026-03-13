<article class="fiche" data-id="<?= $fiche->id() ?>">
	<header class="fiche-header">
		<h2><?= $fiche->title() ?></h2>
		<?php
			// Collect listed contributors (Kirby users)
			$authorNames = [];
			foreach ($fiche->contributors()->toStructure() as $entry) {
				if (!$entry->listed()->toBool()) continue;
				$users = $entry->user()->toUsers();
				foreach ($users as $user) {
					$authorNames[] = $user->name()->or($user->email())->value();
				}
			}
			// Add other (non-Kirby) authors
			if ($fiche->otherauthors()->isNotEmpty()) {
				$authorNames = array_merge($authorNames, $fiche->otherauthors()->yaml());
			}
		?>
		<?php if (!empty($authorNames)): ?>
			<p class="fiche-authors"><?= implode(', ', $authorNames) ?></p>
		<?php endif ?>
		<time class="fiche-date"><?= date('d.m.Y', $fiche->modified()) ?></time>
	</header>
	<div class="fiche-text">
		<?= $fiche->text()->kirbytext() ?>
	</div>
	<?php $attachments = $fiche->files()->template('dossier-attachment'); ?>
	<?php if ($attachments->count() > 0): ?>
		<section class="fiche-attachments">
			<h3>Attachments</h3>
			<ul>
				<?php foreach ($attachments as $file): ?>
					<li>
						<a href="<?= $file->url() ?>" target="_blank">
							<svg class="icon-file" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
							<?= $file->filename() ?>
						</a>
					</li>
				<?php endforeach ?>
			</ul>
		</section>
	<?php endif ?>
	<?php if ($fiche->notes()->isNotEmpty()): ?>
		<aside class="fiche-notes">
			<h3>Notes</h3>
			<?= $fiche->notes()->kirbytext() ?>
		</aside>
	<?php endif ?>
</article>
