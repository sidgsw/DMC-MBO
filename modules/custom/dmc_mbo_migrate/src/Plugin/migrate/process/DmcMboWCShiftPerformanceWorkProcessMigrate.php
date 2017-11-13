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
 *  id = "dmc_mbo_wcshift_performance_work_process_migrate"
 * )
 */
class DmcMboWCShiftPerformanceWorkProcessMigrate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // transform() is called on each source value
    
    $source = $row->getSource();
    $paragraphs = [];
    for ($i = 0; $i <= mt_rand(3, 3); $i++) {
      $work_centers_paragraph = Paragraph::create(['type' => 'shift_performance_work_centers']);

      $work_centers_paragraph->set('field_work_center',$source['Workcenter']);

      $performance_hours_paragraph = Paragraph::create(['type' => 'shift_performance_hours']);

      $performance_hours_paragraph->set('field_hours', $source['Hours']);    

      
        for ($j = 0; $j <= mt_rand(3, 3); $j++) {

          $performance_kpi_paragraph = Paragraph::create(['type' => 'shift_performance_kpi']);

          $performance_kpi_paragraph->set('field_actual', mt_rand(1, 12));
          $performance_kpi_paragraph->set('field_target', mt_rand(1, 12));

          $performance_kpi_paragraph->set('field_cost_kpi', 20);

          $performance_kpi_paragraph->save();
          $performance_hours_paragraph->field_shift_performance_kpi->appendItem($performance_kpi_paragraph);
        }      
      
      $performance_hours_paragraph->save();

      $work_centers_paragraph->field_shift_performance_hours->appendItem($performance_hours_paragraph);

      $work_centers_paragraph->save();
      
      $paragraphs[]['entity'] = $work_centers_paragraph;
    }
    return $paragraphs;
    
  }

}
