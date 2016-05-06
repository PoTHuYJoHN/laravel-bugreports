## Installation (Laravel 5.x)
Run:

    composer require webkid/bugreporter

Add the service provider to `config/app.php` under `providers`:

    'providers' => [
        Webkid\BugReporter\BugReporterServiceProvider::class,
    ]

Publish Config

	php artisan vendor:publish --provider="Webkid\BugReporter\BugReporterServiceProvider"

Update config file:

	config/bugreports.php
