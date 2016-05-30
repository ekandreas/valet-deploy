<?php

/**
 * Init WP with noncens data
 */
task('init:wp', function () {

    writeln('Initialize WP to get WP-CLI working');
    runLocally('cd web && wp core install --url=http://something.dev --title=wp --admin_user=admin --admin_password=admin --admin_email=arne@nada.se');

});

/**
 * Gets the latest from prod
 */
task('init:pull', function () {

    writeln('Pull remote version of WP');
    runLocally('dep pull production', 999);

});

task('init', [
    'init:wp',
    'init:pull',
]);
