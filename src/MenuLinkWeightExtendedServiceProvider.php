<?php

/**
 * @file
 * Contains \Drupal\menu_link_weight_extended\ServiceProvider.
 */

namespace Drupal\menu_link_weight_extended;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

class MenuLinkWeightExtendedServiceProvider extends ServiceProviderBase {

  /**
   * @inheritDoc
   */
  public function alter(ContainerBuilder $container) {
    $defintion = $container->getDefinition('menu.parent_form_selector');
    $defintion->setClass(MenuParentFormSelector::class);
  }

}
