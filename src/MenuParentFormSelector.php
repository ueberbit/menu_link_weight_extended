<?php

/**
 * @file
 * Contains \Drupal\menu_link_weight_extended\MenuParentFormSelector.
 */

namespace Drupal\menu_link_weight_extended;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Menu\MenuParentFormSelector as DefaultMenuParentFormSelector;
use Drupal\Core\Menu\MenuTreeParameters;

class MenuParentFormSelector extends DefaultMenuParentFormSelector {

  /**
   * {@inheritdoc}
   */
  public function getParentSelectOptions($id = '', array $menus = NULL, CacheableMetadata &$cacheability = NULL) {
    if (!isset($menus)) {
      $menus = $this->getMenuOptions();
    }

    $options = array();
    $depth_limit = $this->getParentDepthLimit($id);
    foreach ($menus as $menu_name => $menu_title) {
      $options[$menu_name . ':'] = [
        'name' => '<' . $menu_title . '>',
        'parent_tid' => 0,
      ];

      $parameters = new MenuTreeParameters();
      $parameters->setMaxDepth($depth_limit);
      $tree = $this->menuLinkTree->load($menu_name, $parameters);
      $manipulators = array(
        array('callable' => 'menu.default_tree_manipulators:checkNodeAccess'),
        array('callable' => 'menu.default_tree_manipulators:checkAccess'),
        array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
      );
      $tree = $this->menuLinkTree->transform($tree, $manipulators);
      $this->parentSelectOptionsTreeWalk($tree, $menu_name, 0, $options, $id, $depth_limit, $cacheability);
    }
    return $options;
  }

  /**
   * @inheritDoc
   */
  public function parentSelectElement($menu_parent, $id = '', array $menus = NULL) {
    $element = parent::parentSelectElement($menu_parent, $id, $menus);
    if (empty($element)) {
      return $element;
    }
    $element['#type'] = 'cshs';
    $element['#attached']['library'][] = 'menu_link_weight_extended/menu_link_weight_extended.menu_parent_selector';
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function parentSelectOptionsTreeWalk(array $tree, $menu_name, $indent, array &$options, $exclude, $depth_limit, CacheableMetadata &$cacheability = NULL) {
    /** @var \Drupal\Core\Menu\MenuLinkTreeElement[] $tree */
    foreach ($tree as $element) {
      if ($element->depth > $depth_limit) {
        // Don't iterate through any links on this level.
        break;
      }

      // Collect the cacheability metadata of the access result, as well as the
      // link.
      if ($cacheability) {
        $cacheability = $cacheability
          ->merge(CacheableMetadata::createFromObject($element->access))
          ->merge(CacheableMetadata::createFromObject($element->link));
      }

      // Only show accessible links.
      if (!$element->access->isAllowed()) {
        continue;
      }

      $link = $element->link;
      if ($link->getPluginId() != $exclude) {
        $title = Unicode::truncate($link->getTitle(), 30, TRUE, FALSE);
        if (!$link->isEnabled()) {
          $title .= ' (' . $this->t('disabled') . ')';
        }
        $options[$menu_name . ':' . $link->getPluginId()] = [
          'name' => $title,
          'parent_tid' => $indent,
        ];
        if (!empty($element->subtree)) {
          $this->parentSelectOptionsTreeWalk($element->subtree, $menu_name, $menu_name . ':' . $link->getPluginId(), $options, $exclude, $depth_limit, $cacheability);
        }
      }
    }
  }

}
