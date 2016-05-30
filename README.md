# Valet Deploy
Support package for deploying Valet develop environments for Laravel and WordPress/Bedrock.

## Requirements
* PHP Deployer installed localy, not required in package
* deploy.php-file with staging environments declared

## Install
```
composer require 'ekandreas/valet-deploy':* --save-dev
```

Add a deploy.php in root, eg:

```php
<?php
date_default_timezone_set('Europe/Stockholm');

include_once 'vendor/ekandreas/valet-deploy/recipe.php';

set('domain','orasolvinfo.app');

server( 'production', 'theserver-dns-or-ip', 22 )
    ->env('deploy_path','/deploy_path')
    ->user( 'root' )
    ->env('branch', 'master')
    ->stage('production')
    ->env('database','the_dbname')
    ->env('domain','www.thedomain.se')
    ->identityFile();

set('repository', 'https://github.com/ekandreas/orasolv-intra');

// Symlink the .env file for Bedrock
set('env', 'prod');
set('keep_releases', 10);
set('shared_dirs', ['web/app/uploads']);
set('shared_files', ['.env', 'web/.htaccess', 'web/robots.txt']);
set('env_vars', '/usr/bin/env');
set('writable_dirs', ['web/app/uploads']);

task('deploy:restart', function () {
    // Bladerunner example: 
    // run("rm -f web/app/uploads/.cache/*");
})->desc('Refresh cache');

task( 'deploy', [
    'deploy:prepare',
    'deploy:release',
    'deploy:update_code',
    'deploy:vendors',
    'deploy:shared',
    'deploy:writable',
    'deploy:symlink',
    'cleanup',
    'deploy:restart',
    'success'
] )->desc( 'Deploy your Bedrock project, eg dep deploy production' );
```

## Usage
To get production db/uploads:
```
dep pull production
```

To deploy the commited code to production:
```
dep deploy production
```




