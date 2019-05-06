<?php

namespace Drupal\webit_common\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\webform\Access\WebformAccountAccess;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the custom access control handler for the user accounts.
 */
class WebitWebformAccountAccess extends WebformAccountAccess {

  /**
   * Check whether the user has 'administer' or 'overview' permission.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */

  public static function isNotOnlyEditor(AccountInterface $account){
    $roles = $account->getRoles();

    $rolesEditor = ['authenticated','editor'];

    return !empty(array_diff($roles,$rolesEditor));

  }

  public static function checkOverviewAccess(AccountInterface $account) {
    return AccessResult::allowedIf(self::isNotOnlyEditor($account));
  }

  /**
   * Check whether the user can view submissions.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkSubmissionAccess(AccountInterface $account) {
    return AccessResult::allowedIf(self::isNotOnlyEditor($account));
  }

  public static function checkAboutAccess(AccountInterface $account) {
    return AccessResult::allowedIf(self::isNotOnlyEditor($account) && $account->hasPermission('access webform overview'));
  }

}
