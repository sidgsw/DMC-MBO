<?php

namespace Drupal\dmc_mbo_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Provides a 'DmcMboWCShiftPerformanceSourceMigrate' migrate source.
 *
 * @MigrateSource(
 *  id = "dmc_mbo_wcshift_performance_source_migrate"
 * )
 */
class DmcMboWCShiftPerformanceSourceMigrate extends SqlBase {

  private static $count = 1;

  /**
   * {@inheritdoc}
   */
  public function query() {

    $query = $this->select('ophr_output', 'wco')
        ->fields('wco', ['Workcenter', 'Hours']);
        //->range(0, 2);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'title_new' => $this->t('Title new'),
      'Workcenter' => $this->t('Workcenter'),
      'Hours' => $this->t('Hours'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Perform extra pre-processing for keywords terms, if needed.

    $row->setSourceProperty('title_new', $this->t('WC Shift Performance ') . self::$count++);

    return parent::prepareRow($row);
  }

}
