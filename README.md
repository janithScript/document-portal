#  Document Management Portal (PDF Upload, Signature & Editing)

A Laravel 10-based web application to upload PDFs, add digital signatures, annotate, and download signed documents using PDF.js, Fabric.js, and FPDI.

---

##  Project Setup

```bash
# 1. Clone repository
git clone https://github.com/janithScript/document-portal.git
cd document-portal

# 2. Install dependencies
composer install

# 3. Configure environment
cp .env.example .env
php artisan key:generate

# 4. Setup database (update .env first)
php artisan migrate

# 5. Create storage link
php artisan storage:link

# 6. Create required directory
mkdir -p storage/app/public/signed

# 7. Install required packages
composer require setasign/fpdf setasign/fpdi intervention/image

# (Optional - for PDF compatibility)
composer require setasign/fpdi-pdf-parser

# 8. Run server
php artisan serve
```

---

##  Usage

```bash
Open: http://localhost:800
```

---
