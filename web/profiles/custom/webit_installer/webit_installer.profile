<?php

/**
 * @file
 * Enables modules and site configuration for a Webit standard installation.
 */

use Drupal\contact\Entity\ContactForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Need to do a manual include since this install profile never actually gets
 * installed so therefore its code cannot be autoloaded.
 */
include_once __DIR__ . '/src/Form/WebitSiteSettingsForm.php';

/**
 * Implements hook_form_FORM_ID_alter() for install_configure_form().
 *
 * Allows the profile to alter the site configuration form.
 */
function webit_installer_form_install_configure_form_alter(&$form, FormStateInterface $form_state) {
  $form['#submit'][] = 'webit_installer_form_install_configure_submit';

  // Default account
  $form['admin_account']['account']['name']['#default_value'] = 'su';

  // Default date/time
  $form['regional_settings']['site_default_country']['#default_value'] = 'IT';
  $form['regional_settings']['date_default_timezone']['#default_value'] = 'Europe/Rome';
}

/**
 * Submission handler to sync the contact.form.feedback recipient.
 */
function webit_installer_form_install_configure_submit($form, FormStateInterface $form_state) {

}

/**
 * Implements hook_install_tasks_alter().
 */
function webit_installer_install_tasks_alter(&$tasks, $install_state) {
  $tasks['install_settings_form']['function'] = 'Drupal\webit_installer\Form\WebitSiteSettingsForm';
}
