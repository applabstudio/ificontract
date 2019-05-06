<?php

/**
 * @file
 * Contains \DrupalProject\composer\ScriptHandler.
 */

namespace DrupalProject\composer;

use Composer\Script\Event;
use Composer\Semver\Comparator;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class ScriptHandler {

  public static function createRequiredFiles(Event $event) {
    $fs = new Filesystem();
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $drupalRoot = $drupalFinder->getDrupalRoot();

    $dirs = [
      'modules',
      'profiles',
      'themes',
      'libraries',
    ];

    // Required for unit testing.
    foreach ($dirs as $dir) {
      if (!$fs->exists($drupalRoot . '/' . $dir)) {
        $fs->mkdir($drupalRoot . '/' . $dir);
        $fs->touch($drupalRoot . '/' . $dir . '/.gitkeep');
      }
    }

    // Prepare the settings file for installation, IF it doesn't exist.
    if (!$fs->exists($drupalRoot . '/sites/default/settings.php') and $fs->exists($drupalRoot . '/sites/default/default.settings.php')) {
      $fs->copy($drupalRoot . '/sites/default/default.settings.php', $drupalRoot . '/sites/default/settings.php');
      require_once $drupalRoot . '/core/includes/bootstrap.inc';
      require_once $drupalRoot . '/core/includes/install.inc';
      $settings['config_directories'] = [
        CONFIG_SYNC_DIRECTORY => (object) [
          'value' => Path::makeRelative($drupalFinder->getComposerRoot() . '/config/sync', $drupalRoot),
          'required' => TRUE,
        ],
      ];
      drupal_rewrite_settings($settings, $drupalRoot . '/sites/default/settings.php');
      $event->getIO()->write("Created a sites/default/settings.php file");

      // Add the settings_local_include declaration at the bottom of
      // settings.php file.
      $settings_local_include = '
if (file_exists(__DIR__ . \'/settings.local.php\')) {
  include __DIR__ . \'/settings.local.php\';
}'
;

      $settings_local_private_path ="\n\n\$settings['file_private_path'] = 'sites/default/files/private';";

      $settings_content = file_get_contents($drupalRoot . '/sites/default/settings.php');
      file_put_contents($drupalRoot . '/sites/default/settings.php', $settings_content . $settings_local_include . $settings_local_private_path);
    }

      $fs->chmod($drupalRoot . '/sites/default/settings.php', 0666);
      $event->getIO()->write("Set sites/default/settings.php file with chmod 0666");

    // Prepare the settings.local file for installation, IF it doesn't exist.
    if (!$fs->exists($drupalRoot . '/sites/default/settings.local.php') and $fs->exists($drupalRoot . '/sites/example.webit.settings.local.php')) {
      $fs->copy($drupalRoot . '/sites/example.webit.settings.local.php', $drupalRoot . '/sites/default/settings.local.php');

      $empty_row = '
      
';

      $settings_local_content = file_get_contents($drupalRoot . '/sites/default/settings.local.php');
      file_put_contents($drupalRoot . '/sites/default/settings.local.php', $settings_local_content . $empty_row);

      $fs->chmod($drupalRoot . '/sites/default/settings.local.php', 0666);
      $event->getIO()->write("Created a sites/default/settings.local.php file with chmod 0666");
    }

    // Prepare the development.services.local.yml file for dev installation.
    if (!$fs->exists($drupalRoot . '/sites/default/development.services.local.yml')) {

$development_services_local_content = '# Custom Local development services.

parameters:
  twig.config:
      debug: true
';

      $fs->dumpFile($drupalRoot . '/sites/default/development.services.local.yml', $development_services_local_content);

      $fs->chmod($drupalRoot . '/sites/default/development.services.local.yml', 0666);
      $event->getIO()->write("Created a sites/default/development.services.local.yml file with chmod 0666");
    }

    // Create the files directory with chmod 0777.
    if (!$fs->exists($drupalRoot . '/sites/default/files')) {
      $oldmask = umask(0);
      $fs->mkdir($drupalRoot . '/sites/default/files', 0777);
      umask($oldmask);
      $event->getIO()->write("Created a sites/default/files directory with chmod 0777");
    }

    // Create the private directory into the fiels one, with chmod 0777.
    if (!$fs->exists($drupalRoot . '/sites/default/files/private')) {
      $oldmask = umask(0);
      $fs->mkdir($drupalRoot . '/sites/default/files/private', 0777);
      umask($oldmask);
      $event->getIO()->write("Created a sites/default/files/private directory with chmod 0777");
    }

    // Create the tmp directory into the package root.
    if (!$fs->exists('tmp')) {
      $oldmask = umask(0);
      $fs->mkdir('tmp', 0777);
      umask($oldmask);
      $event->getIO()->write("Created a tmp directory with chmod 0777");
    }

  }

  /**
   * Checks if the installed version of Composer is compatible.
   *
   * Composer 1.0.0 and higher consider a `composer install` without having a
   * lock file present as equal to `composer update`. We do not ship with a lock
   * file to avoid merge conflicts downstream, meaning that if a project is
   * installed with an older version of Composer the scaffolding of Drupal will
   * not be triggered. We check this here instead of in drupal-scaffold to be
   * able to give immediate feedback to the end user, rather than failing the
   * installation after going through the lengthy process of compiling and
   * downloading the Composer dependencies.
   *
   * @see https://github.com/composer/composer/pull/5035
   */
  public static function checkComposerVersion(Event $event) {
    $composer = $event->getComposer();
    $io = $event->getIO();

    $version = $composer::VERSION;

    // The dev-channel of composer uses the git revision as version number,
    // try to the branch alias instead.
    if (preg_match('/^[0-9a-f]{40}$/i', $version)) {
      $version = $composer::BRANCH_ALIAS_VERSION;
    }

    // If Composer is installed through git we have no easy way to determine if
    // it is new enough, just display a warning.
    if ($version === '@package_version@' || $version === '@package_branch_alias_version@') {
      $io->writeError('<warning>You are running a development version of Composer. If you experience problems, please update Composer to the latest stable version.</warning>');
    }
    elseif (Comparator::lessThan($version, '1.0.0')) {
      $io->writeError('<error>Drupal-project requires Composer version 1.0.0 or higher. Please update your Composer before continuing</error>.');
      exit(1);
    }
  }

}
