<?php

/**
 * @file
 * Contains operepiecodogno drush aliases.
 */

$aliases['stage'] = array(
  'uri' => '', //es operepiecodogno.s.infotel.it/
  'root' => '/var/www/html/stage/[folder_name]/web',
  'remote-host' => 'endor.infotel.it',
  'remote-user' => 'webmaster',
  'ssh-options' => '-o StrictHostKeyChecking=no',
  //'ssh-options' => '-p 2222',
  'os' => 'Linux',
  'path-aliases' => array(
    '%drush' => '/usr/local/bin',
    '%drush-script' => '/usr/local/bin/drush',
    '%dump-dir' => '/var/www/html/drush-backups',
    '%files' => 'sites/default/files',
    '%private' => 'sites/default/files/private',
  ),
  'databases' => array(
    'default' => array(
      'default' => array(
        'database' => '',
        'username' => '',
        'password' => '',
        'host' => 'endor.infotel.it',
        'port' => '',
        'driver' => 'mysql',
        'prefix' => '',
      ),
    ),
  ),
  'command-specific' => array(
    'sql-sync' => array(
      'no-cache' => TRUE,
    ),
  ),
);

$aliases['prod'] = array(
  'uri' => '', //es. operepiecodogno.infotel.it/
  'root' => '/var/www/html/production/[folder_name]/web',
  'remote-host' => 'yavin.infotel.it',
  'remote-user' => 'webmaster',
  'ssh-options' => '-o StrictHostKeyChecking=no',
  //'ssh-options' => '-p 2222',
  'os' => 'Linux',
  'path-aliases' => array(
    '%drush' => '/usr/local/bin',
    '%drush-script' => '/usr/local/bin/drush',
    '%dump-dir' => '/var/www/html/drush-backups',
    '%files' => 'sites/default/files',
    '%private' => 'sites/default/files/private',
  ),
  'databases' => array(
    'default' => array(
      'default' => array(
        'database' => '',
        'username' => '',
        'password' => '',
        'host' => 'yavin.infotel.it',
        'port' => '',
        'driver' => 'mysql',
        'prefix' => '',
      ),
    ),
  ),
  'command-specific' => array(
    'sql-sync' => array(
      'no-cache' => TRUE,
    ),
  ),
);

/**** PUSH FILES & DB FROM LOCAL TO STAGE ****/

$options['shell-aliases']['push-files'] = '!drush rsync @self:%files/ @[folder_name].stage:%files';
// It means just write:
// drush @[folder_name] push-files

$options['shell-aliases']['push-private-files'] = '!drush rsync @self:%private/ @[folder_name].stage:%private';
// It means just write:
//  drush @[folder_name] push-private-files

$options['shell-aliases']['push-db'] = '!drush sql-sync @self @[folder_name].stage';
// It means just write:
// drush @[folder_name] push-db


/**** PULL FILES & DB FROM STAGE TO LOCAL ****/

$options['shell-aliases']['pull-files'] = '!drush rsync @[folder_name].stage:%files/ @self:%files';
// It means just write:
// drush @[folder_name] pull-files

$options['shell-aliases']['pull-private-files'] = '!drush rsync @[folder_name].stage:%private/ @self:%private';
// It means just write:
// drush @[folder_name] pull-private-files

$options['shell-aliases']['pull-db'] = '!drush sql-sync @[folder_name].stage @self';
// It means just write:
// drush @[folder_name] pull-db
