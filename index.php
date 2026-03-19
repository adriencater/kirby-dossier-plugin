<?php

/**
 * Dossier — Kirby 5 document catalog plugin
 *
 * A reusable plugin for managing collections of short documents ("fiches")
 * with two-column web UI, PDF export via PagedJS, and git-backed content.
 *
 * @author  Adrien
 * @license MIT
 * @version 1.0.0
 */

require __DIR__ . '/lib/git.php';

// Guard flag to prevent infinite recursion in auto-author hook
$dossierAutoAuthorRunning = false;

// Temporary storage for PDF render tokens
$dossierPdfTokens = [];

Kirby::plugin('adrien/dossier', [

	// ── Options ─────────────────────────────────────────────
	'options' => [
		// Git integration
		'git.autocommit' => false,
		'git.push'       => false,
		'git.bin'        => 'git',
		'git.remote'     => 'origin',
		'git.branch'     => 'main',
		// PDF service
		'pdf.endpoint'   => 'http://localhost:3100/render',
		'pdf.mode'       => 'html',  // 'html' (inline, for local dev) or 'url' (fetch-based, for production)
	],

	// ── Blueprints ──────────────────────────────────────────
	'blueprints' => [
		// Page blueprints
		'pages/dossier'        => __DIR__ . '/blueprints/pages/dossier.yml',
		'pages/dossier-folder' => __DIR__ . '/blueprints/pages/dossier-folder.yml',
		'pages/dossier-public' => __DIR__ . '/blueprints/pages/dossier-public.yml',
		'pages/fiche'          => __DIR__ . '/blueprints/pages/fiche.yml',
		// File blueprints
		'files/dossier-image'      => __DIR__ . '/blueprints/files/dossier-image.yml',
		'files/dossier-attachment'  => __DIR__ . '/blueprints/files/dossier-attachment.yml',
	],

	// ── Templates ───────────────────────────────────────────
	'templates' => [
		'dossier'        => __DIR__ . '/templates/dossier.php',
		'dossier-public' => __DIR__ . '/templates/dossier-public.php',
	],

	// ── Controllers ─────────────────────────────────────────
	'controllers' => [
		'dossier'        => require __DIR__ . '/controllers/dossier.php',
		'dossier-public' => require __DIR__ . '/controllers/dossier-public.php',
	],

	// ── Snippets ────────────────────────────────────────────
	'snippets' => [
		'dossier-fiche'   => __DIR__ . '/snippets/dossier-fiche.php',
		'dossier-cover'   => __DIR__ . '/snippets/dossier-cover.php',
		'dossier-toc'     => __DIR__ . '/snippets/dossier-toc.php',
		'dossier-header'  => __DIR__ . '/snippets/dossier-header.php',
		'dossier-footer'  => __DIR__ . '/snippets/dossier-footer.php',
	],

	// ── Routes ──────────────────────────────────────────────
	'routes' => [
		[
			// Return fiche HTML fragment
			'pattern' => 'fetch/fiche/(:all)',
			'action' => function ($path) {
				$page = page($path);
				if (!$page || $page->intendedTemplate()->name() !== 'fiche') {
					return new Kirby\Http\Response('Not found', 'text/plain', 404);
				}
				return new Kirby\Http\Response(
					snippet('dossier-fiche', ['fiche' => $page], true),
					'text/html',
					200
				);
			}
		],
		[
			// Return cover page HTML fragment
			'pattern' => 'fetch/cover',
			'method' => 'POST',
			'action' => function () {
				$data = json_decode(file_get_contents('php://input'), true);
				$ids = $data['ids'] ?? [];
				$showToc = $data['toc'] ?? false;
				$title = $data['title'] ?? '';

				$fiches = array_filter(array_map(function ($id) {
					return page($id);
				}, $ids));

				return new Kirby\Http\Response(
					snippet('dossier-cover', [
						'fiches' => $fiches,
						'showToc' => $showToc,
						'title' => $title,
					], true),
					'text/html',
					200
				);
			}
		],
		[
			// Serve print-ready HTML for a PDF render token (used by PDF service)
			'pattern' => 'fetch/pdf-render/(:any)',
			'action' => function ($token) {
				$tmpFile = sys_get_temp_dir() . '/dossier-pdf-' . preg_replace('/[^a-zA-Z0-9]/', '', $token) . '.json';
				if (!file_exists($tmpFile)) {
					return new Kirby\Http\Response('Not found', 'text/plain', 404);
				}

				$data = json_decode(file_get_contents($tmpFile), true);
				$ids = $data['ids'] ?? [];
				$showCover = $data['cover'] ?? false;
				$showToc = $data['toc'] ?? false;
				$showMeta = $data['meta'] ?? true;
				$showNotes = $data['notes'] ?? true;
				$title = $data['title'] ?? '';
				$pageUrl = $data['pageUrl'] ?? '';

				$fiches = array_filter(array_map(function ($id) {
					return page($id);
				}, $ids));

				$parts = [];

				if ($showCover) {
					$parts[] = snippet('dossier-cover', [
						'fiches' => $fiches,
						'showToc' => $showToc,
						'title' => $title,
					], true);
				}

				foreach ($fiches as $fiche) {
					$parts[] = snippet('dossier-fiche', ['fiche' => $fiche], true);
				}

				$body = implode("\n", $parts);

				$pluginAssets = url('media/plugins/adrien/dossier');
				$qrJs = file_get_contents(__DIR__ . '/assets/js/qrcode.js');

				// Visibility overrides
				$hideRules = '';
				if (!$showMeta) {
					$hideRules .= '.fiche-authors, .fiche-date { display: none !important; }';
				}
				if (!$showNotes) {
					$hideRules .= '.fiche-notes { display: none !important; }';
				}

				// QR code init script
				$qrInit = '';
				if ($showCover && $pageUrl) {
					$escapedUrl = htmlspecialchars($pageUrl, ENT_QUOTES);
					$qrInit = <<<SCRIPT
					<script>
					(function() {
						var el = document.getElementById("cover-qr");
						if (!el) return;
						var qr = qrcode(0, "M");
						qr.addData("{$escapedUrl}");
						qr.make();
						el.innerHTML = qr.createSvgTag(4);
					})();
					</script>
					SCRIPT;
				}

				$html = <<<HTML
				<!doctype html>
				<html>
				<head>
				<meta charset="utf-8">
				<link rel="stylesheet" href="{$pluginAssets}/css/dossier-style.css">
				<link rel="stylesheet" href="{$pluginAssets}/css/dossier-print.css">
				<style>{$hideRules}</style>
				<script>{$qrJs}</script>
				{$qrInit}
				</head>
				<body class="print-view">
				{$body}
				</body>
				</html>
				HTML;

				// Clean up token file
				@unlink($tmpFile);

				return new Kirby\Http\Response($html, 'text/html', 200);
			}
		],
		[
			// Generate PDF via external service
			'pattern' => 'fetch/pdf',
			'method' => 'POST',
			'action' => function () {
				$data = json_decode(file_get_contents('php://input'), true);
				$ids = $data['ids'] ?? [];
				$showCover = $data['cover'] ?? false;
				$showToc = $data['toc'] ?? false;
				$showMeta = $data['meta'] ?? true;
				$showNotes = $data['notes'] ?? true;
				$title = $data['title'] ?? '';
				$pageUrl = $data['pageUrl'] ?? '';

				if (empty($ids)) {
					return new Kirby\Http\Response(
						json_encode(['error' => 'No fiche IDs provided']),
						'application/json', 400
					);
				}

				$fiches = array_filter(array_map(function ($id) {
					return page($id);
				}, $ids));

				// Collect PDF attachments
				$attachments = [];
				foreach ($fiches as $fiche) {
					foreach ($fiche->files()->template('dossier-attachment') as $file) {
						$filePath = $file->root();
						if (file_exists($filePath)) {
							$attachments[] = [
								'data' => base64_encode(file_get_contents($filePath)),
							];
						}
					}
				}

				$endpoint = option('adrien.dossier.pdf.endpoint', 'http://localhost:3100/render');

				// Determine rendering mode: URL-based or inline HTML
				$pdfMode = option('adrien.dossier.pdf.mode', 'html');

				if ($pdfMode === 'url') {
					// URL mode: store selection in temp file, send URL to PDF service
					$token = bin2hex(random_bytes(16));
					$tmpFile = sys_get_temp_dir() . '/dossier-pdf-' . $token . '.json';
					file_put_contents($tmpFile, json_encode($data));

					$siteUrl = rtrim(site()->url(), '/');
					$renderUrl = $siteUrl . '/fetch/pdf-render/' . $token;

					$payload = json_encode([
						'url' => $renderUrl,
						'attachments' => $attachments,
					]);
				} else {
					// HTML mode: build self-contained HTML with inlined assets
					$parts = [];

					if ($showCover) {
						$parts[] = snippet('dossier-cover', [
							'fiches' => $fiches,
							'showToc' => $showToc,
							'title' => $title,
						], true);
					}

					foreach ($fiches as $fiche) {
						$parts[] = snippet('dossier-fiche', ['fiche' => $fiche], true);
					}

					$body = implode("\n", $parts);

					// Inline CSS assets
					$pluginAssets = __DIR__ . '/assets';
					$siteAssetsDir = kirby()->root('assets') . '/css';
					$styleCss = file_exists($siteAssetsDir . '/dossier-style.css')
						? file_get_contents($siteAssetsDir . '/dossier-style.css')
						: file_get_contents($pluginAssets . '/css/dossier-style.css');
					$printCss = file_exists($siteAssetsDir . '/dossier-print.css')
						? file_get_contents($siteAssetsDir . '/dossier-print.css')
						: file_get_contents($pluginAssets . '/css/dossier-print.css');
					$qrJs = file_get_contents($pluginAssets . '/js/qrcode.js');

					// Visibility overrides
					$hideRules = '';
					if (!$showMeta) {
						$hideRules .= '.fiche-authors, .fiche-date { display: none !important; }';
					}
					if (!$showNotes) {
						$hideRules .= '.fiche-notes { display: none !important; }';
					}

					// QR code init script
					$qrInit = '';
					if ($showCover && $pageUrl) {
						$escapedUrl = htmlspecialchars($pageUrl, ENT_QUOTES);
						$qrInit = <<<SCRIPT
						<script>
						(function() {
							var el = document.getElementById("cover-qr");
							if (!el) return;
							var qr = qrcode(0, "M");
							qr.addData("{$escapedUrl}");
							qr.make();
							el.innerHTML = qr.createSvgTag(4);
						})();
						</script>
						SCRIPT;
					}

					// Convert image src to inline base64 data URIs
					$body = preg_replace_callback(
						'/(<img[^>]+src=")([^"]+)(")/i',
						function ($m) {
							$url = $m[2];
							$mediaPrefix = '/media/';
							$pos = strpos($url, $mediaPrefix);
							if ($pos !== false) {
								$relPath = substr($url, $pos);
								$filePath = kirby()->root('index') . $relPath;
							} elseif (str_starts_with($url, '/')) {
								$filePath = kirby()->root('index') . $url;
							} else {
								return $m[0];
							}
							if (file_exists($filePath)) {
								$mime = mime_content_type($filePath);
								$data = base64_encode(file_get_contents($filePath));
								return $m[1] . 'data:' . $mime . ';base64,' . $data . $m[3];
							}
							return $m[0];
						},
						$body
					);

					$html = <<<HTML
					<!doctype html>
					<html>
					<head>
					<meta charset="utf-8">
					<style>{$styleCss}</style>
					<style>{$printCss}</style>
					<style>{$hideRules}</style>
					<script>{$qrJs}</script>
					{$qrInit}
					</head>
					<body class="print-view">
					{$body}
					</body>
					</html>
					HTML;

					$payload = json_encode([
						'html' => $html,
						'attachments' => $attachments,
					]);
				}

				$ch = curl_init($endpoint);
				curl_setopt_array($ch, [
					CURLOPT_POST => true,
					CURLOPT_POSTFIELDS => $payload,
					CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_TIMEOUT => 120,
				]);
				$pdf = curl_exec($ch);
				$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				$curlError = curl_error($ch);
				curl_close($ch);

				if ($pdf === false || $httpCode !== 200) {
					return new Kirby\Http\Response(
						json_encode([
							'error' => 'PDF service unavailable',
							'detail' => $curlError ?: $pdf,
							'endpoint' => $endpoint,
							'http_code' => $httpCode,
							'payload_size' => strlen($payload),
						]),
						'application/json', 502
					);
				}

				$filename = 'document.pdf';
				if ($title) {
					$filename = Str::slug($title) . '.pdf';
				} elseif (count($fiches) === 1) {
					$filename = Str::slug(reset($fiches)->title()->value()) . '.pdf';
				}

				return new Kirby\Http\Response($pdf, 'application/pdf', 200, [
					'Content-Disposition' => 'attachment; filename="' . $filename . '"',
				]);
			}
		]
	],

	// ── API routes (Panel) ──────────────────────────────────
	'api' => [
		'routes' => [
			[
				'pattern' => 'dossier/git/pull',
				'method' => 'POST',
				'action' => function () {
					return dossierGitPull();
				}
			],
			[
				'pattern' => 'dossier/git/status',
				'method' => 'GET',
				'action' => function () {
					return dossierGitStatus();
				}
			]
		]
	],

	// ── Hooks ────────────────────────────────────────────────
	'hooks' => [
		// Auto-author: add current user to contributors on fiche edit
		'page.update:after' => function ($newPage) {
			global $dossierAutoAuthorRunning;

			// Auto-author
			if (!$dossierAutoAuthorRunning && $newPage->intendedTemplate()->name() === 'fiche') {
				$user = kirby()->user();
				if ($user) {
					$userRef = 'user://' . $user->uuid()->id();
					$contributors = $newPage->contributors()->yaml();

					$found = false;
					foreach ($contributors as $entry) {
						$existing = $entry['user'] ?? [];
						if (is_array($existing) && in_array($userRef, $existing)) { $found = true; break; }
						if (is_string($existing) && $existing === $userRef) { $found = true; break; }
					}

					if (!$found) {
						$contributors[] = [
							'user' => [$userRef],
							'listed' => true,
						];
						$dossierAutoAuthorRunning = true;
						kirby()->impersonate('kirby');
						$newPage->update(['contributors' => Yaml::encode($contributors)]);
						$dossierAutoAuthorRunning = false;
					}
				}
			}

			// Git auto-commit
			dossierGitAutoCommit('Update ' . $newPage->title());
		},
		'page.create:after' => function ($page) {
			global $dossierAutoAuthorRunning;

			// Auto-author
			if (!$dossierAutoAuthorRunning && $page->intendedTemplate()->name() === 'fiche') {
				$user = kirby()->user();
				if ($user) {
					$contributors = [[
						'user' => ['user://' . $user->uuid()->id()],
						'listed' => true,
					]];
					$dossierAutoAuthorRunning = true;
					kirby()->impersonate('kirby');
					$page->update(['contributors' => Yaml::encode($contributors)]);
					$dossierAutoAuthorRunning = false;
				}
			}

			// Git auto-commit
			dossierGitAutoCommit('Create ' . $page->title());
		},
		'page.delete:after' => function ($status, $page) {
			dossierGitAutoCommit('Delete ' . $page->title());
		},
		'page.changeTitle:after' => function ($newPage) {
			dossierGitAutoCommit('Rename to ' . $newPage->title());
		},
		'page.changeSlug:after' => function ($newPage) {
			dossierGitAutoCommit('Move ' . $newPage->title());
		},
		'page.changeStatus:after' => function ($newPage) {
			dossierGitAutoCommit('Change status of ' . $newPage->title());
		},
		'page.changeSortNum:after' => function ($newPage) {
			dossierGitAutoCommit('Reorder ' . $newPage->title());
		},
	],

	// ── KirbyTags ───────────────────────────────────────────
	'tags' => [
		'image' => [
			'attr' => [
				...Kirby\Text\KirbyTag::$types['image']['attr'],
			],
			'html' => function ($tag) {
				$file = $tag->file($tag->value);

				if (!$file) {
					return '';
				}

				$alt     = $tag->alt ?? $file->alt()->value() ?? '';
				$caption = $file->caption()->value() ?? '';
				$credit  = $file->credit()->value() ?? '';

				$img = Html::img($file->url(), ['alt' => $alt]);

				if ($caption === '' && $credit === '') {
					return $img;
				}

				$parts = [];
				if ($caption !== '') $parts[] = $caption;
				if ($credit !== '') $parts[] = '<span class="image-credit">' . $credit . '</span>';
				$figcaption = '<figcaption>' . implode(' ', $parts) . '</figcaption>';

				return '<figure>' . $img . $figcaption . '</figure>';
			}
		]
	],
]);
