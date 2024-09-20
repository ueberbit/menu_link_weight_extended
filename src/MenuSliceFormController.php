<?php

/**
 * @file
 * Contains \Drupal\menu_link_weight_extended\MenuSliceFormController.
 */

namespace Drupal\menu_link_weight_extended;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

class MenuSliceFormController extends MenuFormLinkController {

  /**
   * @var \Drupal\Core\Menu\MenuLinkInterface
   */
  protected $menuLink;

  protected $maxDepth;

  protected function prepareEntity() {
    $this->menuLink = $this->getRequest()->attributes->get('menu_link');
  }

  /**
   * @inheritdoc
   */
  protected function buildOverviewFormWithDepth(array &$form, FormStateInterface $form_state, $depth = 1, $menu_link = NULL) {
    // Ensure that menu_overview_form_submit() knows the parents of this form
    // section.
    if (!$form_state->has('menu_overview_form_parents')) {
      $form_state->set('menu_overview_form_parents', []);
    }

    // Use Menu UI adminforms
    $form['#attached']['library'][] = 'menu_ui/drupal.menu_ui.adminforms';
    $form['#attached']['library'][] = 'menu_link_weight_extended/menu_link_weight_extended.tabledrag';

    // Add a link to go back to the full menu.
    $form['back_link'][] = array(
      '#type' => 'link',
      '#title' => sprintf('Back to top level %s menu',  $this->entity->id()),
      '#url' => Url::fromRoute('menu_link_weight_extended.menu', array(
        'menu' => $this->entity->id(),
      ))
    );

    $form['links'] = array(
      '#type' => 'table',
      '#theme' => 'table__menu_overview',
      '#header' => array(
        $this->t('Menu link'),
        $this->t('Edit children'),
        array(
          'data' => $this->t('Enabled'),
          'class' => array('checkbox'),
        ),
        $this->t('Weight'),
        array(
          'data' => $this->t('Operations'),
          'colspan' => 3,
        ),
      ),
      '#attributes' => array(
        'id' => 'menu-overview',
      ),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'menu-weight',
        ),
      ),
    );

    // No Links available (Empty menu)
    $form['links']['#empty'] = $this->t('There are no menu links yet. <a href=":url">Add link</a>.', [
      ':url' => Url::fromRoute('entity.menu.add_link_form', ['menu' => $this->entity->id()], [
        'query' => ['destination' => $this->entity->toUrl('edit-form')->toString()],
      ])->toString(),
    ]);

    // Get the menu tree if it's not in our property.
    if (empty($this->tree)) {
      $parents = $this->menuLinkManager->getParentIds($this->menuLink->getPluginId());
      $parents[''] = '';
      $tree_params = new MenuTreeParameters();
      $tree_params->addExpandedParents($parents);
      $tree_params->setActiveTrail($parents);
      $tree_params->setMinDepth(1);
      $tree_params->setMaxDepth(count($parents) + 1);
      $this->maxDepth = count($parents) + 1;
      $this->tree = $this->getTreeFromMenuTreeParameters($tree_params);
      $this->tree = $this->filterSubtree($this->tree, $parents, $this->menuLink);
    }

    // Determine the delta; the number of weights to be made available.
    $count = function (array $tree) {
      $sum = function ($carry, MenuLinkTreeElement $item) {
        return $carry + $item->count();
      };
      return array_reduce($tree, $sum);
    };

    // Tree maximum or 50.
    $delta = max($count($this->tree), 50);

    $links = $this->buildOverviewTreeForm($this->tree, $delta);
    $this->processLinks($form, $links);
    $this->removeDraggable($form, $links, $this->menuLink);

    return $form;
  }

  protected function getTreeFromMenuTreeParameters(MenuTreeParameters $tree_params) {
   $tree = $this->menuTree->load($this->entity->id(), $tree_params);

   // We indicate that a menu administrator is running the menu access check.
   $this->getRequest()->attributes->set('_menu_admin', TRUE);
   $manipulators = array(
     array('callable' => 'menu.default_tree_manipulators:checkAccess'),
     array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
   );
   $tree = $this->menuTree->transform($tree, $manipulators);
   $this->getRequest()->attributes->set('_menu_admin', FALSE);

   return $tree;
  }

  protected static function filterSubtree($tree, $parents, $current_link) {
    // Trim tree to active trail.
    $tree = array_filter($tree, function ($item) use ($parents, $current_link) {
      return in_array($item->link->getPluginId(), $parents) || ($item->link->getPluginDefinition()['parent'] == $current_link);
    });
    foreach (array_keys($tree) as $key) {
      $tree[$key]->subtree = static::filterSubtree($tree[$key]->subtree, $parents, $current_link);
    }
    return $tree;
  }

  public function removeDraggable(&$form, &$links, $menu_link) {
    foreach (Element::children($links) as $id) {
      if (isset($links[$id]['#item'])) {
        $element = $links[$id];
        if ($element['#item']->depth < $this->maxDepth - 1) {
          $form['links'][$id]['#attributes']['class'] = array_diff($form['links'][$id]['#attributes']['class'], ['draggable']);
        }
      }
    }
  }

}
