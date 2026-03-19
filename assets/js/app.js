document.addEventListener('DOMContentLoaded', function () {
	var main = document.getElementById('dossier-main');
	if (!main) return;

	var config = window.dossierConfig || {};
	var assetsBase = config.assets || '/media/plugins/adrien/dossier';
	var printCssUrl = config.printCss || assetsBase + '/css/dossier-print.css';
	var styleCssUrl = config.styleCss || assetsBase + '/css/dossier-style.css';

	var options = document.getElementById('dossier-options');
	var btnPdf = document.getElementById('btn-pdf');
	var btnPdfDownload = document.getElementById('btn-pdf-download');
	var optCover = document.getElementById('opt-cover');
	var optToc = document.getElementById('opt-toc');
	var optMeta = document.getElementById('opt-meta');
	var optNotes = document.getElementById('opt-notes');
	var optPages = document.getElementById('opt-pages');
	var optTocItem = document.querySelector('.opt-toc-item');
	// Read defaults from data attributes (used by public view)
	var defaultShowAuthors = main.dataset.showAuthors !== 'false';
	var defaultShowNotes = main.dataset.showNotes !== 'false';

	var selected = [];
	var viewing = null;

	// TOC order: collect all fiche IDs in DOM order for sorting
	var tocOrder = [];
	document.querySelectorAll('.toc-select').forEach(function (btn) {
		tocOrder.push(btn.dataset.id);
	});

	// Click on fiche link: view single fiche, clear multi-select
	document.querySelectorAll('.toc-link').forEach(function (link) {
		link.addEventListener('click', function (e) {
			e.preventDefault();
			var id = this.dataset.id;
			viewing = id;
			clearSelection();
			fetchFiche(id).then(function (html) {
				main.innerHTML = html;
				applyVisibility();
				if (options) options.hidden = false;
				updateOptions();
			});
		});
	});

	// Click on dot/checkbox: toggle selection
	document.querySelectorAll('.toc-select').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			var id = this.dataset.id;
			var idx = selected.indexOf(id);
			if (idx > -1) {
				selected.splice(idx, 1);
				this.classList.remove('is-selected');
			} else {
				selected.push(id);
				this.classList.add('is-selected');
			}
			renderSelected();
		});
	});

	// Visibility toggles
	if (optMeta) {
		optMeta.addEventListener('change', function () {
			if (optPages && optPages.checked) {
				renderPaged();
			} else {
				applyVisibility();
			}
		});
	}
	if (optNotes) {
		optNotes.addEventListener('change', function () {
			if (optPages && optPages.checked) {
				renderPaged();
			} else {
				applyVisibility();
			}
		});
	}
	if (optPages) {
		optPages.addEventListener('change', function () {
			if (optPages.checked) {
				renderPaged();
			} else {
				// Re-render without paged view
				removeStylesheet('paged-print-css');
				removeStylesheet('paged-interface-css');
				removeStylesheet('paged-recto-verso-css');
				var ids = exportIds();
				if (ids.length === 0) return;
				Promise.all(ids.map(fetchFiche)).then(function (fragments) {
					main.innerHTML = fragments.join('');
					main.classList.remove('paged-preview');
					applyVisibility();
				});
			}
		});
	}

	// PDF buttons
	if (btnPdf) {
		btnPdf.addEventListener('click', function () { handlePdf('open'); });
	}
	if (btnPdfDownload) {
		btnPdfDownload.addEventListener('click', function () { handlePdf('download'); });
	}

	function clearSelection() {
		selected = [];
		document.querySelectorAll('.toc-select.is-selected').forEach(function (btn) {
			btn.classList.remove('is-selected');
		});
		if (optPages) {
			optPages.checked = false;
			removeStylesheet('paged-print-css');
			removeStylesheet('paged-interface-css');
			removeStylesheet('paged-recto-verso-css');
			main.classList.remove('paged-preview');
		}
	}

	function fetchFiche(id) {
		return fetch('/fetch/fiche/' + id)
			.then(function (res) {
				if (!res.ok) throw new Error('Failed to fetch fiche');
				return res.text();
			});
	}

	// Returns the list of fiche IDs to export: multi-select if any, otherwise the viewed fiche
	function exportIds() {
		if (selected.length > 0) {
			return selected.slice().sort(function (a, b) {
				return tocOrder.indexOf(a) - tocOrder.indexOf(b);
			});
		}
		if (viewing) return [viewing];
		return [];
	}

	function fetchCover(ids) {
		return fetch('/fetch/cover', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({
				ids: ids,
				toc: optToc && optToc.checked,
				title: document.title
			})
		}).then(function (res) {
			if (!res.ok) throw new Error('Failed to fetch cover');
			return res.text();
		});
	}

	function renderSelected() {
		if (selected.length === 0) {
			main.innerHTML = '<p class="dossier-empty">Select a fiche from the table of contents.</p>';
			if (options) options.hidden = true;
			if (optPages) {
				optPages.checked = false;
				removeStylesheet('paged-print-css');
				removeStylesheet('paged-interface-css');
				removeStylesheet('paged-recto-verso-css');
				main.classList.remove('paged-preview');
			}
			return;
		}
		if (options) options.hidden = false;
		updateOptions();
		if (optPages && optPages.checked) {
			renderPaged();
		} else {
			var ordered = exportIds();
			Promise.all(ordered.map(fetchFiche)).then(function (fragments) {
				main.innerHTML = fragments.join('');
				applyVisibility();
			});
		}
	}

	function updateOptions() {
		var count = exportIds().length;
		var multiSelect = count > 1;

		// Show TOC option only for multi-select
		if (optTocItem) optTocItem.hidden = !multiSelect;

		// Auto-enable cover + TOC when multiple selected
		if (multiSelect) {
			if (optCover) optCover.checked = true;
			if (optToc) optToc.checked = true;
		}

		// Hide TOC if back to single
		if (!multiSelect && optToc) {
			optToc.checked = false;
		}
	}

	function applyVisibility() {
		var showMeta = optMeta ? optMeta.checked : defaultShowAuthors;
		main.querySelectorAll('.fiche-authors, .fiche-date').forEach(function (el) {
			el.hidden = !showMeta;
		});
		var showNotes = optNotes ? optNotes.checked : defaultShowNotes;
		main.querySelectorAll('.fiche-notes').forEach(function (el) {
			el.hidden = !showNotes;
		});
	}

	function addStylesheet(id, href) {
		if (!document.getElementById(id)) {
			var link = document.createElement('link');
			link.id = id;
			link.rel = 'stylesheet';
			link.href = href;
			document.head.appendChild(link);
		}
	}

	function removeStylesheet(id) {
		var el = document.getElementById(id);
		if (el) el.remove();
	}

	function renderPaged() {
		var ids = exportIds();
		if (ids.length === 0) return;

		var coverPromise = (optCover && optCover.checked)
			? fetchCover(ids)
			: Promise.resolve(null);

		coverPromise.then(function (coverHtml) {
			return Promise.all(ids.map(fetchFiche)).then(function (fragments) {
				var parts = [];
				if (coverHtml) parts.push(coverHtml);
				parts = parts.concat(fragments);

				// Build a template with the content
				var template = document.createElement('template');
				template.innerHTML = parts.join('');
				var content = template.content;

				var showMeta = optMeta ? optMeta.checked : true;
				if (!showMeta) {
					content.querySelectorAll('.fiche-authors, .fiche-date').forEach(function (el) {
						el.remove();
					});
				}
				var showNotes = optNotes ? optNotes.checked : true;
				if (!showNotes) {
					content.querySelectorAll('.fiche-notes').forEach(function (el) {
						el.remove();
					});
				}

				// Load PagedJS CSS into the document
				addStylesheet('paged-print-css', printCssUrl);
				addStylesheet('paged-interface-css', assetsBase + '/css/pagedjs/interface/interface.css');
				addStylesheet('paged-recto-verso-css', assetsBase + '/css/pagedjs/interface/recto-verso.css');

				// Clear main and run PagedJS
				main.innerHTML = '';
				main.classList.add('paged-preview');

				if (typeof Paged !== 'undefined' && Paged.Previewer) {
					var paged = new Paged.Previewer();
					paged.preview(
						content,
						[printCssUrl, assetsBase + '/css/pagedjs/interface/interface.css', assetsBase + '/css/pagedjs/interface/recto-verso.css'],
						main
					);
				}
			});
		});
	}

	function setPdfLoading(loading) {
		if (btnPdf) btnPdf.disabled = loading;
		if (btnPdfDownload) btnPdfDownload.disabled = loading;
		var spinner = document.getElementById('pdf-spinner');
		if (spinner) spinner.style.display = loading ? 'inline-block' : 'none';
	}

	function handlePdf(mode) {
		var ids = exportIds();
		if (ids.length === 0) return;

		setPdfLoading(true);

		var payload = {
			ids: ids,
			cover: optCover ? optCover.checked : false,
			toc: optToc ? optToc.checked : false,
			meta: optMeta ? optMeta.checked : true,
			notes: optNotes ? optNotes.checked : true,
			title: document.title,
			pageUrl: window.location.href
		};

		fetch('/fetch/pdf', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify(payload)
		}).then(function (res) {
			if (!res.ok) throw new Error('Server PDF failed');
			return res.blob();
		}).then(function (blob) {
			var url = URL.createObjectURL(blob);
			if (mode === 'download') {
				var a = document.createElement('a');
				a.href = url;
				a.download = 'document.pdf';
				document.body.appendChild(a);
				a.click();
				document.body.removeChild(a);
				URL.revokeObjectURL(url);
			} else {
				window.open(url, '_blank');
			}
			setPdfLoading(false);
		}).catch(function () {
			setPdfLoading(false);
			// Fallback to client-side PagedJS
			handlePdfClientSide();
		});
	}

	function handlePdfClientSide() {
		var ids = exportIds();
		if (ids.length === 0) return;

		var promise = (optCover && optCover.checked)
			? fetchCover(ids)
			: Promise.resolve(null);

		promise.then(function (coverHtml) {
			return Promise.all(ids.map(fetchFiche)).then(function (fragments) {
				var parts = [];
				if (coverHtml) parts.push(coverHtml);
				parts = parts.concat(fragments);
				openPrintView(parts);
			});
		});
	}

	function openPrintView(parts) {
		var showMeta = optMeta ? optMeta.checked : true;
		var showNotes = optNotes ? optNotes.checked : true;
		var content = document.createElement('div');
		content.innerHTML = parts.join('');

		if (!showMeta) {
			content.querySelectorAll('.fiche-authors, .fiche-date').forEach(function (el) {
				el.remove();
			});
		}
		if (!showNotes) {
			content.querySelectorAll('.fiche-notes').forEach(function (el) {
				el.remove();
			});
		}

		var pageUrl = window.location.href;
		var qrScript = '';
		if (content.querySelector('.cover-qr')) {
			qrScript =
				'<script src="' + assetsBase + '/js/qrcode.js"><\/script>' +
				'<script>' +
				'(function() {' +
				'  var el = document.getElementById("cover-qr");' +
				'  if (!el) return;' +
				'  var qr = qrcode(0, "M");' +
				'  qr.addData("' + pageUrl + '");' +
				'  qr.make();' +
				'  el.innerHTML = qr.createSvgTag(4);' +
				'})();' +
				'<\/script>';
		}

		var html = '<!doctype html><html><head>' +
			'<meta charset="utf-8">' +
			'<link rel="stylesheet" href="' + styleCssUrl + '">' +
			'<link rel="stylesheet" href="' + printCssUrl + '">' +
			qrScript +
			'<script src="' + assetsBase + '/js/paged.polyfill.js"><\/script>' +
			'</head><body class="print-view">' +
			content.innerHTML +
			'</body></html>';

		var win = window.open('', '_blank');
		win.document.write(html);
		win.document.close();
	}
});
