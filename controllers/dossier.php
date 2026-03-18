<?php

return function ($page, $kirby) {
	$password = $page->password()->value();
	$authenticated = false;

	if (empty($password)) {
		$authenticated = true;
	} else {
		$sessionKey = 'dossier_auth_' . $page->id();
		$session = $kirby->session();

		$submitted = $kirby->request()->method() === 'POST' ? $kirby->request()->body()->get('password') : null;
		if ($submitted !== null) {
			if ($submitted === $password) {
				$session->set($sessionKey, true);
				$authenticated = true;
			}
		} else {
			$authenticated = $session->get($sessionKey, false);
		}
	}

	$children = $authenticated
		? $page->children()->filterBy('status', 'in', ['listed', 'unlisted'])
		: new Pages();

	return [
		'authenticated' => $authenticated,
		'children' => $children,
		'loginError' => $submitted !== null && !$authenticated,
	];
};
