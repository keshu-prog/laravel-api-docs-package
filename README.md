# Laravel API Docs Generator 📘

A simple and developer-friendly Laravel package for auto-generating API documentation directly from your Laravel routes, views, and controller metadata.

---

## 🚀 Features

- Automatically scans your routes and generates documentation  
- Artisan command to generate docs  
- Blade view integration for web-based display  
- Easy customization by publishing views

---

## 📦 Installation

### Step 1: Add Local Repository

If you're developing this package locally from a `packages/` directory, add the following to your root project's `composer.json`:

```json
"repositories": [
  {
    "type": "path",
    "url": "packages/KmApiDocVendor/LaravelApiDocs"
  }
]
```

### Step 2: Require the Package

Run the following command from your Laravel project root:

```bash
composer require kmapidocvendor/laravel-api-docs:@dev
composer dump-autoload
```

### Step 3: Register the Service Provider (if not using auto-discovery)

If your Laravel version is below 5.5 or you're not using auto-discovery, add the provider manually to `config/app.php`:

```php
'providers' => [
    // ...
    KmApiDocVendor\LaravelApiDocs\ApiDocsServiceProvider::class,
],
```

### Step 4: Publish the Views (optional)

To customize the documentation UI:

```bash
php artisan vendor:publish --provider="KmApiDocVendor\LaravelApiDocs\ApiDocsServiceProvider"
```

This will publish the view files to:

```
resources/views/vendor/kmapidocs/
```

---

## ⚙️ Usage

### 1. Generate the Documentation

Use the artisan command to generate API documentation:

```bash
php artisan kmapidoc:generate
```

This will scan your routes/controllers and create structured docs.

---

### 2. View Documentation in the Browser

Once generated, you can view the documentation at:

```
http://your-app.test/api-docs
```

The view is rendered via:

```php
$this->loadViewsFrom(__DIR__.'/resources/views', 'kmapidocs');
```

---

## 🛠 Customizing the View

If you published the views, you can customize the layout or design:

```
resources/views/vendor/kmapidocs/api-docs.blade.php
```

---

## 📂 Project Structure

```
packages/
└── KmApiDocVendor/
    └── LaravelApiDocs/
        ├── src/
        │   ├── ApiDocsServiceProvider.php
        │   └── Console/
        │       └── GenerateDocsCommand.php
        ├── resources/
        │   └── views/
        │       └── api-docs.blade.php
        └── routes/
            └── web.php
```

---

## 🧪 Compatibility

- Laravel Versions Supported: ^8.0, ^9.0, ^10.0, ^11.0, ^12.0  
- PHP Version Required: ^8.0

---

## 🤝 Contributing

Feel free to fork, improve, and send a pull request.  
All contributions are welcome and appreciated!

---

## 📄 License

MIT License © Keshab Chandra Mandal
