<?php
namespace Deployer;

require 'recipe/common.php';

if (!has('database')) {
    throw new \Exception('The server setting in deploy missing parameter "database" for dump');
}

if (!has('domain')) {
    throw new \Exception('The server setting in deploy missing parameter "domain" for search & replace');
}

require 'init.php';
require 'pull.php';
