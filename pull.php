<?php
namespace Deployer;

task('pull:check_parameters', function () {
    if (!has('domain')) {
        $server = get('server');
        $serverHost = $server['host'];
        writeln("No parameter 'domain' set, assuming $serverHost");
    }
    if (!has('database')) {
        throw new \Exception('The server setting in deploy missing parameter "database" for dump');
    }
    if (!has('elastic')) {
        writeln('No parameter elastic set');
    }
});

task('pull:create_database_dump', function () {
    $database = get('database');
    $server = get('server');
    $serverHost = $server['host'];
    writeln("Creating database dump ({$database}) on $serverHost");
    $databaseFile = "/tmp/{$database}.sql";
    run("mysqldump {{database}} > {$databaseFile}", 999);
});

task('pull:get_database_dump', function () {
    $database = get('database');
    $databaseFile = "/tmp/{$database}.sql";
    $databaseFileSize = ((string)run("stat --printf=\"%s\" $databaseFile"));
    $databaseFileSize = round($databaseFileSize / 1000000);
    $server = get('server');
    $serverHost = $server['host'];
    writeln("Downloading database dump ({$databaseFileSize}MB) from {$serverHost}");
    download("{$database}.sql", $databaseFile);
    run("rm -f $databaseFile");
});

task('pull:restore_database', function () {
    writeln('Restore remote database backup to WordPress database');
    $database = get('database');
    $databaseFile = "{$database}.sql";
    runLocally("wp db import {$databaseFile}", 999);
    runLocally("rm -f {$databaseFile}.sql");
});

task('pull:search_and_replace_database', function () {
    $root = realpath(__DIR__ . '/../../../');
    $local = basename($root) . '.' . runLocally('valet domain');
    $server = get('server');
    $domain = has('domain') ? get('domain') : $server['host'];
    writeln("Search and replace '\033[33m{$domain}\e[37m' => '\e[33m{$local}\e[37m' urls in the imported database to local urls");

    $output = runLocally("wp search-replace www.{$domain} {$local}", 999);
    preg_match('/Made (\d+) replacements/', $output, $matches);
    $replaced1 = (int)$matches[1];
    writeln("\e[33m{$replaced1}\e[37m replacements...");

    $output = runLocally("wp search-replace {$domain} {$local}", 999);
    preg_match('/Made (\d+) replacements/', $output, $matches);
    $replaced2 = (int)$matches[1];
    writeln("\e[33m{$replaced2}\e[37m replacements...");

    $output = runLocally("wp search-replace https://{$domain} http://{$local}", 999);
    preg_match('/Made (\d+) replacements/', $output, $matches);
    $replaced3 = (int)$matches[1];
    writeln("\e[33m{$replaced3}\e[37m replacements...");

    $totalReplaced = $replaced1 + $replaced2 + $replaced3;

    writeln("Total of \e[33m{$totalReplaced}\e[37m replacements");
});

task('pull:files', function () {
    writeln('Getting uploads, long duration first time! (approx. 60s)');
    $user = run('echo $USER');
    runLocally("rsync --exclude .cache -re ssh {$user}@{{server.host}}:{{deploy_path}}/shared/web/app/uploads web/app", 999);
});

task('pull:elastic', function () {
    if (has('elastic') && get('elastic')) {
        writeln('Setup elasticsearch and elasticpress');
        runLocally('wp elasticpress index --setup', 999);
    }
});

task('pull:cleanup', function () {
    writeln('Remove all tranisents');
    runLocally('wp transient delete-all');
    writeln('Permalinks rewrite/flush');
    runLocally('wp rewrite flush');
    writeln('Activate query monitor');
    runLocally('wp plugin activate query-monitor');
    if (file_exists('web/app/uploads/.cache/')) {
        runLocally('chmod -R 777 web/app/uploads/.cache');
        writeln('Empty Bladerunner cache');
        array_map('unlink', glob("web/app/uploads/.cache/*.*"));
    }
});

task('pull', [
    'pull:check_parameters',
    'pull:create_database_dump',
    'pull:get_database_dump',
    'pull:restore_database',
    'pull:search_and_replace_database',
    'pull:files',
    'pull:elastic',
    'pull:cleanup',
    'success'
]);
