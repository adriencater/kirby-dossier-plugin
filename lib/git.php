<?php

/**
 * Git helper functions for the Dossier plugin.
 *
 * Auto-commit content changes, pull from remote, check status.
 * Configure via options:
 *   adrien.dossier.git.autocommit (bool)
 *   adrien.dossier.git.push (bool)
 *   adrien.dossier.git.bin (string, path to git)
 *   adrien.dossier.git.remote (string)
 *   adrien.dossier.git.branch (string)
 */

function dossierGitAutoCommit(string $message): void
{
	if (!option('adrien.dossier.git.autocommit')) {
		return;
	}

	$bin = option('adrien.dossier.git.bin', 'git');
	$root = kirby()->root('base');

	$commands = [
		sprintf('%s -C %s add -A content/', escapeshellarg($bin), escapeshellarg($root)),
		sprintf(
			'%s -C %s commit -m %s --author=%s',
			escapeshellarg($bin),
			escapeshellarg($root),
			escapeshellarg($message),
			escapeshellarg('Kirby Panel <panel@localhost>')
		),
	];

	foreach ($commands as $cmd) {
		exec($cmd . ' 2>&1', $output, $code);
		if ($code !== 0) return;
	}

	if (option('adrien.dossier.git.push')) {
		$remote = option('adrien.dossier.git.remote', 'origin');
		$branch = option('adrien.dossier.git.branch', 'main');
		exec(sprintf(
			'%s -C %s push %s %s 2>&1',
			escapeshellarg($bin),
			escapeshellarg($root),
			escapeshellarg($remote),
			escapeshellarg($branch)
		));
	}
}

function dossierGitPull(): array
{
	$bin = option('adrien.dossier.git.bin', 'git');
	$root = kirby()->root('base');
	$remote = option('adrien.dossier.git.remote', 'origin');
	$branch = option('adrien.dossier.git.branch', 'main');

	$output = [];
	exec(sprintf(
		'%s -C %s pull %s %s 2>&1',
		escapeshellarg($bin),
		escapeshellarg($root),
		escapeshellarg($remote),
		escapeshellarg($branch)
	), $output, $code);

	return [
		'status' => $code === 0 ? 'ok' : 'error',
		'output' => implode("\n", $output),
	];
}

function dossierGitStatus(): array
{
	$bin = option('adrien.dossier.git.bin', 'git');
	$root = kirby()->root('base');

	exec(sprintf('%s -C %s fetch --dry-run 2>&1', escapeshellarg($bin), escapeshellarg($root)), $fetchOutput, $fetchCode);
	exec(sprintf('%s -C %s status --porcelain content/ 2>&1', escapeshellarg($bin), escapeshellarg($root)), $statusOutput, $statusCode);

	$remote = option('adrien.dossier.git.remote', 'origin');
	$branch = option('adrien.dossier.git.branch', 'main');
	$behind = 0;
	$ahead = 0;
	exec(sprintf(
		'%s -C %s rev-list --left-right --count %s/%s...HEAD 2>&1',
		escapeshellarg($bin),
		escapeshellarg($root),
		escapeshellarg($remote),
		escapeshellarg($branch)
	), $countOutput, $countCode);
	if ($countCode === 0 && !empty($countOutput[0])) {
		$parts = preg_split('/\s+/', trim($countOutput[0]));
		$behind = (int)($parts[0] ?? 0);
		$ahead = (int)($parts[1] ?? 0);
	}

	return [
		'dirty' => !empty($statusOutput),
		'behind' => $behind,
		'ahead' => $ahead,
	];
}
