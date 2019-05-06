<?php

namespace Drupal\webit_common\Plugin\views\field;

use Drupal\system\Plugin\views\field\BulkForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a actions-based bulk operation form element.
 *
 * @ViewsField("webit_bulk_form")
 */
class WebitBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  public function viewsFormValidate(&$form, FormStateInterface $form_state) {
  }

}
