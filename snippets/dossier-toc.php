<?php $mode = $mode ?? 'public'; ?>
<ul class="toc">
	<?php foreach ($pages as $entry): ?>
		<?php if ($entry->intendedTemplate()->name() === 'dossier-folder'): ?>
			<li class="toc-folder">
				<span class="toc-folder-title"><?= $entry->title() ?></span>
				<?php
					$folderChildren = $mode === 'private'
						? $entry->children()->filterBy('status', 'in', ['listed', 'unlisted'])
						: $entry->children()->listed();
					snippet('dossier-toc', ['pages' => $folderChildren, 'mode' => $mode]);
				?>
			</li>
		<?php else: ?>
			<li class="toc-item">
				<button class="toc-select" data-id="<?= $entry->id() ?>" aria-label="Select <?= $entry->title() ?>">
					<span class="toc-dot"></span>
				</button>
				<a class="toc-link" href="<?= $entry->url() ?>" data-id="<?= $entry->id() ?>">
					<?= $entry->title() ?>
				</a>
			</li>
		<?php endif ?>
	<?php endforeach ?>
</ul>
