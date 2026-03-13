<?php

return function ($page) {
	$catalog = $page->catalog()->toPages()->first();
	$children = $catalog
		? $catalog->children()->listed()
		: new Pages();

	return [
		'catalog' => $catalog,
		'children' => $children,
		'showAuthors' => $page->showauthors()->toBool(),
		'showNotes' => $page->shownotes()->toBool(),
	];
};
