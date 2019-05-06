<?php

namespace Drupal\webit_common\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class WebformRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {

    /* @var \Symfony\Component\Routing\Route|null $route */

    // Change path '/user/login' to '/login'.
    if ($route = $collection->get('entity.webform.templates')) {
      //$route->setPath('/login');
      $route->setRequirement('_custom_access','\Drupal\webit_common\Access\WebitWebformAccountAccess::checkOverviewAccess');
    }

    if ($route = $collection->get('entity.webform_submission.collection')) {
      //$route->setPath('/login');
      $route->setRequirement('_custom_access','\Drupal\webit_common\Access\WebitWebformAccountAccess::checkSubmissionAccess');
    }

    if ($route = $collection->get('webform.about')) {
      $route->setRequirement('_custom_access', '\Drupal\webit_common\Access\WebitWebformAccountAccess::checkAboutAccess');
    }

  }

}