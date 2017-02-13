<?php

/**
 * @file
 * Contains \Drupal\menu_link_weight_extended\Routing\RouteSubscriber.
 */

namespace Drupal\menu_link_weight_extended\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class RouteSubscriber extends RouteSubscriberBase {

  /**
   * @inheritDoc
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.menu.edit_form')) {
      $route->setDefault('_entity_form', 'menu.edit_menu_link_weight_extended');
    }
  }

}
