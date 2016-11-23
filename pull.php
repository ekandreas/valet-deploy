<?php
namespace Deployer;

task('pull:create_database_dump', function () {
    writeln('Creating a new database dump on the remote server');
    $database = get('database');
    run("mysqldump {$database} > /tmp/{$database}.sql", 999);
});

task('pull:get_database_dump', function () {
    writeln('Downloading database dump from remote server');
    $database_file = '/tmp/' . get('database') . '.sql';
    download(get('database') . '.sql', $database_file);
});

task('pull:restore_database', function () {
    writeln('Restore remote database backup to local database');
    $database = get('database') . '.sql';
    runLocally("wp db import {$database}", 999);
});

task('pull:search_and_replace_database', function () {
    writeln('Search and replace urls in the imported database to local urls');
    $local_domain = get('domain');
    runLocally("wp search-replace www.{{domain}} {$local_domain}", 999);
    runLocally("wp search-replace {{domain}} {$local_domain}", 999);
    runLocally("wp search-replace https://{$local_domain} http://{$local_domain}", 999);
    //runLocally('wp search-replace {{remote.domain}} {{local.domain}}', 999);
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
    writeln('Cleaning up locally...');
    runLocally('rm {{database}}.sql');
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
    'pull:create_database_dump',
    'pull:get_database_dump',
    'pull:restore_database',
    'pull:search_and_replace_database',
    'pull:files',
    'pull:elastic',
    'pull:cleanup',
]);
