<?php

namespace Drupal\dmc_mbo_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Provides a 'DmcMboWCShiftPerformanceMigrate' migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *  id = "dmc_mbo_wcshift_performance_operator_process_migrate"
 * )
 */
class DmcMboWCShiftPerformanceOperatorProcessMigrate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $paragraphs = [];
    
      // Operator data
      $operator_paragraph = Paragraph::create(['type' => 'shift_performance_operator']);

      $operator_paragraph->set('field_shift_operator', 1961);
      $operator_paragraph->set('field_flex_factor', array_rand([0, 1]));
      $operator_paragraph->set('field_safety_factor', -1);

      $plant_action = _get_random_string(6);
      $operator_paragraph->set('field_tpl_action', $plant_action);
      $operator_paragraph->set('field_total_hour', mt_rand(1, 12));
      $operator_paragraph->set('field_remaining_hour', mt_rand(1, 12));    
    
      for ($k = 0; $k <= mt_rand(5, 5); $k++) {

          $performance_work_center_paragraph = Paragraph::create(['type' => 'shift_performance_work_center']);

          $performance_work_center_paragraph->set('field_hour', mt_rand(1, 12));
          $performance_work_center_paragraph->set('field_loan_hours', mt_rand(1, 12));

          $performance_work_center_paragraph->set('field_work_center', 'WC1');

          $performance_work_center_paragraph->save();
          $operator_paragraph->field_shift_performance_work_cen->appendItem($performance_work_center_paragraph);
        }
        
      $operator_paragraph->save();
      
      $paragraphs[]['entity'] = $operator_paragraph;
      
      return $paragraphs;
  }

}
