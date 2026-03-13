<?php

return function ($page, $kirby) {
	$password = $page->password()->value();
	$authenticated = false;

	if (empty($password)) {
		$authenticated = true;
	} else {
		$sessionKey = 'dossier_auth_' . $page->id();
		$session = $kirby->session();

		if (get('password') !== null) {
			if (get('password') === $password) {
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
		'loginError' => get('password') !== null && !$authenticated,
	];
};
