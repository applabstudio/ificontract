<?php

namespace Drupal\webit_installer\Form;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Installer\Form\SiteSettingsForm;

/**
 * Overrides the Core SiteConfigureForm.
 *
 * This is based on the install_configure_form provided by core.
 *
 * @see \Drupal\Core\Installer\Form\SiteConfigureForm
 */
class WebitSiteSettingsForm extends SiteSettingsForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    global $install_state;

    // Write specific local settings array and save.
    $settings_local = [];
    $settings_local['settings']['hash_salt'] = (object) [
      'value'    => Crypt::randomBytesBase64(55),
      'required' => TRUE,
    ];
    // Remember the profile which was used.
    $settings_local['settings']['install_profile'] = (object) [
      'value' => $install_state['parameters']['profile'],
      'required' => TRUE,
    ];

    $database = $form_state->get('database');
    $settings_local['databases']['default']['default'] = (object) array(
      'value'    => $database,
      'required' => TRUE,
    );

    // Needed for Staging environment (really?)
    // @TODO: may be better verify this asap.
    $settings_local['settings']['extension_discovery_scan_tests'] = (object) [
      'value' => TRUE,
      'required' => TRUE,
    ];

    $settings_local_file = \Drupal::service('site.path') . '/settings.local.php';
    drupal_rewrite_settings($settings_local, $settings_local_file);

    // Add the config directories to settings.php.
    drupal_install_config_directories();

    // Indicate that the settings file has been verified, and check the database
    // for the last completed task, now that we have a valid connection. This
    // last step is important since we want to trigger an error if the new
    // database already has Drupal installed.
    $install_state['settings_verified'] = TRUE;
    $install_state['config_verified'] = TRUE;
    $install_state['database_verified'] = TRUE;
    $install_state['completed_task'] = install_verify_completed_task();
  }

}
