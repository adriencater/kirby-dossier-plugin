# Kirby Dossier

A document catalog plugin for Kirby 5. Manage collections of short documents ("fiches") with a two-column web interface, PDF export via PagedJS, and optional git integration.

## Features

- **Two-column UI** — table of contents navigation + content pane
- **Fiches** — short documents with text, notes, authors, images, and PDF attachments
- **Folders** — organize fiches into nested subfolders
- **Multi-select** — select multiple fiches and stack them in order
- **PDF export** — server-side via PagedJS + Puppeteer, with client-side fallback
- **Cover page** — auto-generated with title, table of contents, and QR code
- **PDF attachments** — attached PDFs are appended to the generated document
- **Public/private views** — password-protected dossier + public read-only view
- **Auto-author** — automatically adds the current Panel user to a fiche's contributors
- **Image tags** — custom kirbytag with caption and credit support
- **Git integration** — auto-commit content changes on Panel save, optional push

## Requirements

- Kirby 5
- PHP 8.1+

For server-side PDF generation (optional):
- Node.js 18+
- Separate [pdf-service](https://github.com/adriencater/pdf-service) (see PDF section below)

## Installation

Choose **one** of the two methods below. Do not use both — the plugin must only be installed once.

### Option A: Manual

Clone this repository into `site/plugins/dossier`:

```bash
cd your-kirby-site
mkdir -p site/plugins
git clone https://github.com/adriencater/kirby-dossier-plugin.git site/plugins/dossier
```

### Option B: Composer

Add the repository to your `composer.json`:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/adriencater/kirby-dossier-plugin"
    }
]
```

Then require the package:

```bash
composer require adrien/kirby-dossier:dev-main
```

## Setup

### 1. Update your site blueprint

Add the dossier templates to your `site/blueprints/site.yml`:

```yaml
title: Site

columns:
  main:
    width: 2/3
    sections:
      dossiers:
        type: pages
        label: Dossiers
        templates:
          - dossier
      pages:
        type: pages
        label: Pages
        templates:
          - default
          - dossier-public
          - home
```

### 2. Start Kirby

```bash
composer start
```

Then go to `http://localhost:8000/panel` to access the Panel.

### 3. Create a dossier

In the Kirby Panel, create a new page using the **Dossier** template. This is your private, password-protected document catalog.

Inside the dossier, create pages using the **Fiche** template for documents, or **Folder** template to organize them into groups.

### 4. Create a public view (optional)

Create a page using the **Dossier (public view)** template. In its settings, select which dossier to display. Only listed (published) fiches will be shown.

Configure whether to show authors and notes in the public view.

### 5. Set a password

Edit your dossier page in the Panel and set an access password in the sidebar. Users must enter this password to access the private view.

## Configuration

Add options to your `site/config/config.php`:

```php
return [
    'adrien.dossier' => [
        // Git integration
        'git.autocommit' => false,   // auto-commit on Panel save
        'git.push'       => false,   // auto-push after commit
        'git.bin'        => 'git',   // path to git binary
        'git.remote'     => 'origin',
        'git.branch'     => 'main',

        // PDF service (optional)
        'pdf.endpoint'   => 'http://localhost:3100/render',
    ],
];
```

## PDF export

### Client-side (no setup needed)

The PDF button works out of the box using PagedJS in the browser. It opens a print-ready view in a new window.

### Server-side (optional, better quality)

For headless PDF generation, run the separate PDF service:

1. Set up the [pdf-service](https://github.com/adriencater/pdf-service) (Node.js + Puppeteer + PagedJS)
2. Point the plugin to it:
   ```php
   'adrien.dossier' => [
       'pdf.endpoint' => 'http://localhost:3100/render',
   ],
   ```
3. The PDF button will use server-side generation, with automatic client-side fallback if the service is unavailable

The server-side PDF includes attached PDFs — they are appended as pages after the main content.

## Customization

### Snippets

The plugin provides minimal `dossier-header` and `dossier-footer` snippets. Override them by creating your own versions in your site:

```
site/snippets/dossier-header.php
site/snippets/dossier-footer.php
```

Your header must include the plugin's CSS and PagedJS:

```php
<?php $pluginAssets = url('media/plugins/adrien/dossier'); ?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= $page->title() ?></title>
    <link rel="stylesheet" href="<?= $pluginAssets ?>/css/style.css">
    <!-- Add your own stylesheets here -->
    <script src="<?= $pluginAssets ?>/js/paged.js"></script>
</head>
<body>
```

Your footer must include `app.js` with the asset config:

```php
<?php $pluginAssets = url('media/plugins/adrien/dossier'); ?>
<script>
window.dossierConfig = { assets: '<?= $pluginAssets ?>' };
</script>
<script src="<?= $pluginAssets ?>/js/app.js"></script>
</body>
</html>
```

### CSS

The plugin ships minimal structural CSS. Override styles by adding your own stylesheet after the plugin's in your `dossier-header` snippet.

Key classes:
- `.dossier` — main flex container
- `.dossier-nav` — left sidebar (20rem)
- `.dossier-main` — content area
- `.toc`, `.toc-item`, `.toc-folder` — table of contents
- `.fiche`, `.fiche-header`, `.fiche-text` — document content
- `.fiche-authors`, `.fiche-date`, `.fiche-notes` — metadata
- `.fiche-attachments` — PDF attachment list
- `.cover`, `.cover-title`, `.cover-toc` — cover page
- `.dossier-options` — export options panel

### Blueprints

Override any blueprint by placing your version in `site/blueprints/`:

- `pages/fiche.yml` — fiche fields and layout
- `pages/dossier.yml` — dossier settings
- `pages/dossier-folder.yml` — folder structure
- `pages/dossier-public.yml` — public view settings
- `files/dossier-image.yml` — image fields (alt, caption, credit)
- `files/dossier-attachment.yml` — PDF attachment settings

## Content structure

```
content/
├── my-dossier/              # URL: /my-dossier
│   ├── dossier.txt          # template: dossier
│   ├── 1_topic-a/
│   │   ├── dossier-folder.txt
│   │   ├── 1_first-fiche/
│   │   │   └── fiche.txt
│   │   └── 2_second-fiche/
│   │       ├── fiche.txt
│   │       ├── photo.jpg
│   │       └── report.pdf
│   └── 2_standalone-fiche/
│       └── fiche.txt
└── publications/            # URL: /publications
    └── dossier-public.txt   # template: dossier-public
```

## API routes

The plugin registers these routes:

| Method | Pattern | Description |
|--------|---------|-------------|
| GET | `/fetch/fiche/{id}` | Returns fiche HTML fragment |
| POST | `/fetch/cover` | Returns cover page HTML |
| POST | `/fetch/pdf` | Generates and returns PDF |

Panel API routes (require authentication):

| Method | Pattern | Description |
|--------|---------|-------------|
| POST | `/api/dossier/git/pull` | Pull from remote |
| GET | `/api/dossier/git/status` | Check git status |

## License

MIT
