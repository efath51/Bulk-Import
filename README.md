# BulkImporter

A Laravel + Vue 3 tool to import structured data from PDF or DOCX files into your database — one item at a time with image attachment support.

---

## The Problem

Manually entering large amounts of test or production data through a form is slow and error-prone. This tool lets you prepare your data in a document, upload it, and walk through each record to attach an image and save.

---
![Bulk Importer Screenshot](assets/demo.png)

## Features

- **File Upload** — supports PDF and DOCX, max 10MB, saved with original filename
- **Smart Parser** — auto-detects document format (Item-numbered, Name-to-Name, blank-line separated)
- **Configurable Patterns** — switch parsing strategy from the UI without touching code
- **Dynamic Form** — form fields generate automatically from parsed data, works for any data type
- **Wizard Flow** — step through each item, save with image, skip, or go back to previous
- **Dataset Manager** — view all uploaded files, preview parsed data as a table, load any previous file
- **Session Based** — no extra database tables needed for the import flow
- **Reusable** — bulk flow logic is a trait, drop it into any controller

---

## Tech Stack

- Laravel 12
- Vue 3 + TypeScript
- Inertia.js
- Tailwind CSS
- smalot/pdfparser
- phpoffice/phpword

---

## How It Works

```
Upload File → Parse → Session → Wizard Loop → Save to DB
     ↓            ↓                ↓
  PDF/DOCX    Smart detect     One item at
  stored as   format &         a time with
  real name   split entries    image upload
```

---

## Installation (for pdf/doc reader)

```bash
composer require smalot/pdfparser phpoffice/phpword
```


Add to `composer.json`:

```json
"autoload": {
    "files": ["app/Http/Helpers/helper.php"]
}
```

```bash
composer dump-autoload
```

---

## Routes

```
GET    /bulk-import                    Upload page
POST   /bulk-import/upload-dataset     Parse and store file
POST   /bulk-import/load-dataset       Load existing file
GET    /bulk-import/wizard             Step through items
GET    /bulk-import/wizard/item/{n}    Get item by index
POST   /bulk-import/save-item          Save item to DB
POST   /bulk-import/cancel             Clear session
GET    /bulk-import/datasets           Dataset manager
DELETE /bulk-import/datasets/{file}    Delete file
GET    /bulk-import/datasets/preview   Preview parsed data
POST   /bulk-import/parser-config      Save active pattern
```

---

## Document Format

The parser handles these formats out of the box:

**Format 1 — Item numbered:**

```
Item 1 Name: Product One Slug: product-one Status: active Description: Some text.
Item 2 Name: Product Two Slug: product-two Status: active Description: Some text.
```

**Format 2 — Continuous Name blocks:**

```
Name: Product One Slug: product-one Status: active Description: Some text.
Name: Product Two Slug: product-two Status: active Description: Some text.
```

**Format 3 — Blank line separated:**

```
Name: Product One
Slug: product-one
Status: active
Description: Some text.

Name: Product Two
Slug: product-two
Status: active
Description: Some text.
```



---

## File Structure

```
app/
├── Http/
│   ├── Helpers/
│   |   ├── helpers.php
│   ├── Controllers/
│   │   ├── BulkImportController.php
│   │   └── ItemController.php
│   
├── Services/
│   └── BulkImport/
│       └── DocumentParserService.php
├── Models/
│   └── Item.php
│   └── ParserConfig.php
├── Traits/
│   └── HandlesBulkImport.php


resources/js/
├── Pages/
│   ├── BulkImportStart.vue
│   ├── BulkImport.vue
│   └── BulkImport/
│       └── Datasets.vue
└── Components/
    └── BulkImport/
        ├── FileUpload.vue
        ├── DynamicFormFields.vue
        └── PdfPreview.vue
```

---
