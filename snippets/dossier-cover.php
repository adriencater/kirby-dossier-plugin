<section class="cover">
	<h1 class="cover-title"><?= site()->title() ?></h1>
	<?php if (isset($title) && $title): ?>
		<p class="cover-subtitle"><?= $title ?></p>
	<?php endif ?>
	<?php if (isset($showToc) && $showToc && !empty($fiches)): ?>
		<nav class="cover-toc">
			<h2>Table of Contents</h2>
			<ol>
				<?php foreach ($fiches as $fiche): ?>
					<li><?= $fiche->title() ?></li>
				<?php endforeach ?>
			</ol>
		</nav>
	<?php endif ?>
	<div class="cover-qr" id="cover-qr"></div>
</section>
