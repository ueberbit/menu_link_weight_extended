<?php

/**
 * @file
 * Contains \Drupal\menu_link_weight_extended\MenuSliceFormController.
 */

namespace Drupal\menu_link_weight_extended;


use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

class MenuSliceFormController extends MenuFormLinkController {

  /**
   * @var \Drupal\Core\Menu\MenuLinkInterface
   */
  protected $menuLink;


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
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'menu-parent',
          'subgroup' => 'menu-parent',
          'source' => 'menu-id',
          'hidden' => TRUE,
          'limit' => \Drupal::menuTree()->maxDepth() - 1,
        ),
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'menu-weight',
        ),
      ),
    );

    // No Links available (Empty menu)
    $form['links']['#empty'] = $this->t('There are no menu links yet. <a href=":url">Add link</a>.', [
      ':url' => $this->url('entity.menu.add_link_form', ['menu' => $this->entity->id()], [
        'query' => ['destination' => $this->entity->url('edit-form')],
      ]),
    ]);

    // Get the menu tree if it's not in our property.
    if (empty($this->tree)) {
      $this->tree = $this->getTree($depth, $this->menuLink);
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

    $this->process_links($form, $links, $this->menuLink);

    return $form;
  }

}
