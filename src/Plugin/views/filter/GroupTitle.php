<?php
/**
 * @file
 * Definition of Drupal\d8views\Plugin\views\filter\NodeTitles.
 */

namespace Drupal\custom_group\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;

/**
 * Filters by given list of group title options.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("group_titles")
 */
class GroupTitle extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = t('Allowed Group titles');
    $this->definition['options callback'] = [$this, 'generateOptions'];
  }

  /**
   * Override the query so that no filtering takes place if the user doesn't
   * select any options.
   */
  public function query() {
    $table = 'group_relationship_field_data_node_field_data';
    $column = 'gid';
    $this->query->addWhere($this->options['group'], $table . '.' . $column, $this->value, 'IN');
    $this->query->distinct = TRUE;
  }

  /**
   * Skip validation if no options have been chosen so we can use it as a
   * non-filter.
   */
  public function validate() {
    if (!empty($this->value)) {
      parent::validate();
    }
  }

  /**
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function generateOptions() {
    if ($groups = \Drupal::entityTypeManager()->getStorage('group')->loadMultiple()) {
      foreach ($groups as $key => $group) {
        $array[$key] = $group->label();
      }
      return $array;
    }
    return [];
  }

}
