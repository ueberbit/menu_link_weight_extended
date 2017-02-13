<?php

/**
 * @file
 * Contains \Drupal\menu_link_weight_extended\MenuFormLinkController.
 */

namespace Drupal\menu_link_weight_extended;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\menu_ui\MenuForm as DefaultMenuFormController;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Render\Element;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Core\Url;
use Drupal\menu_link_weight_extended\MenuFormController;

/**
 * Class MenuFormController
 * @package Drupal\menu_link_weight_extended
 */
class MenuFormLinkController extends MenuFormController {

  public $tree = array();

  /**
   * @inheritdoc
   */
  protected function buildOverviewForm(array &$form, FormStateInterface $form_state) {
    return $this->buildOverviewFormWithDepth($form, $form_state, 1, NULL);
  }

  /**
   * @inheritdoc
   */
  protected function buildOverviewFormWithDepth(array &$form, FormStateInterface $form_state, $depth = 1, $menu_link = NULL) {
    $form = array();

    // Create a link to add a menu item
    $uri = Url::fromRoute('entity.menu.add_link_form', array(
      'menu' => $this->getEntity()->id(),
      'destination' => $this->getEntity()->url('edit-form')
    ));

    $form['addlink'] = array(
      '#type' => 'link',
      '#title' => t('Add link'),
      '#url' => $uri,
    );

    array_merge($form, parent::buildOverviewFormWithDepth($form, $form_state, $depth, $menu_link));

    $form['links']['#header'] = array(
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
    );

    return $form;
  }

  /**
   * Format the links appropriately so draggable views will work.
   * @param $form
   * @param $links
   * @param string $menu_link
   */
  public function processLinks(&$form, &$links, $menu_link) {
    foreach (Element::children($links) as $id) {
      if (isset($links[$id]['#item'])) {
        $element = $links[$id];

        $form['links'][$id]['#item'] = $element['#item'];

        // TableDrag: Mark the table row as draggable.
        $form['links'][$id]['#attributes'] = $element['#attributes'];
        $form['links'][$id]['#attributes']['class'][] = 'draggable';

        // TableDrag: Sort the table row according to its existing/configured weight.
        $form['links'][$id]['#weight'] = $element['#item']->link->getWeight();

        // Add special classes to be used for tabledrag.js.
        $element['parent']['#attributes']['class'] = array('menu-parent');
        $element['weight']['#attributes']['class'] = array('menu-weight');
        $element['id']['#attributes']['class'] = array('menu-id');

        $form['links'][$id]['title'] = array(
          array(
            '#theme' => 'indentation',
            '#size' => $element['#item']->depth - 1,
          ),
          $element['title'],
        );

        $form['links'][$id]['root'][] = array();

        if ($form['links'][$id]['#item']->hasChildren) {
          $uri = Url::fromRoute('menu_link_weight_extended.menu_link', array(
            'menu' => $this->entity->id(),
            'menu_link' => $element['#item']->link->getPluginId(),
          ));

          $form['links'][$id]['root'][] = array(
            '#type' => 'link',
            '#title' => t('View child items'),
            '#url' => $uri,
          );
        }

        $form['links'][$id]['enabled'] = $element['enabled'];
        $form['links'][$id]['enabled']['#wrapper_attributes']['class'] = array('checkbox', 'menu-enabled');

        $form['links'][$id]['weight'] = $element['weight'];

        // Operations (dropbutton) column.
        $form['links'][$id]['operations'] = $element['operations'];

        $form['links'][$id]['id'] = $element['id'];
        $form['links'][$id]['parent'] = $element['parent'];
      }
    }
  }

}
