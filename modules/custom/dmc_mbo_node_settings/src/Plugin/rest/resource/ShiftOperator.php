<?php

namespace Drupal\dmc_mbo_node_settings\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use \Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;


/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "shift_operator_import",
 *   label = @Translation("Shift Operator import"),
 *   uri_paths = {
 *     "https://www.drupal.org/link-relations/create" = "shift/operator/import"
 *   }
 * )
 */
class ShiftOperator extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('custom_rest'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to POST requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function post(array $data = []) {
    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }

    $sp_wc = $data['shift_performance_work_cen'];
    $spo = $data['shift_performance_operator'];
    $tpl = $data['tpl'];
    $shift_date = $data['shift_date'];
    $shift_id = $data['shift_id'];
    $title = $data['title'];

    if (empty($title)) {
      return new ResourceResponse(t('Invalid input'));
    }

    foreach ($sp_wc as $key => $value) {
      $work_center = $value['work_center'];
      $hours = $value['hours'];

      $paragraph3_obj = array();
      foreach ($value['shift_performance_kpi'] as $key2 => $value2) {
        $paragraph3 = Paragraph::create([
          'type' => 'shift_performance_kpi',
          'field_actual' => $value2['actual'],
          'field_target' => $value2['target'],
          'field_cost_kpi' => $value2['cost_kpi']
        ]);
        $paragraph3->save();
        $paragraph3_obj[] = $paragraph3;
      }

      $paragraph2 = Paragraph::create([
        'type' => 'shift_performance_hours',
        'field_hours' => $hours,
      ]);
      foreach ($paragraph3_obj as $p_obj3) {
        $paragraph2->field_shift_performance_kpi->appendItem($p_obj3);
      }
      $paragraph2->save();
      $paragraph2_obj = $paragraph2;

      $paragraph1 = Paragraph::create([
        'type' => 'shift_performance_work_centers',
        'field_work_center' => $work_center,
      ]);
      $paragraph1->field_shift_performance_hours->appendItem($paragraph2_obj);
      $paragraph1->save();
      $paragraph1_obj[] = $paragraph1;
    }

    foreach ($spo as $key => $value) {
      $flex_factor = $value['flex_factor'];
      $remaining_hour = $value['remaining_hour'];
      $safety_factor = $value['safety_factor'];
      $total_hour = $value['total_hour'];
      $tpl_action = $value['tpl_action'];
      $shift_operator = $value['shift_operator'];
      $paragraph4_obj = array();
      foreach ($value['shift_performance_work_cen'] as $key2 => $value2) {
        $paragraph4 = Paragraph::create([
          'type' => 'shift_performance_work_center',
          'field_hour' => $value2['hour'],
          'field_loan_hours' => $value2['loan_hours'],
          'field_work_center' => $value2['work_center']
        ]);
        $paragraph4->save();
        $paragraph4_obj[] = $paragraph4;
      }
      $paragraph5 = Paragraph::create([
        'type' => 'shift_performance_operator',
        'field_flex_factor' => $flex_factor,
        'field_shift_operator' => $shift_operator,
        'field_remaining_hour' => $remaining_hour,
        'field_total_hour' => $total_hour,
        'field_tpl_action' => $tpl_action,
        'field_safety_factor' => $safety_factor,
      ]);
      foreach ($paragraph4_obj as $p_obj4) {
        $paragraph5->field_shift_performance_work_cen->appendItem($p_obj4);
      }
      $paragraph5->save();
      $paragraph5_obj[] = $paragraph5;
    }

    $node = Node::create([
      'type'        => 'wc_shift_performance',
      'title'       => $title,
      'field_user_tpl' => $tpl,
      'field_select_shift' => $shift_id,
      'field_select_date' => [
        'value' => $shift_date
      ]
    ]);
    foreach ($paragraph1_obj as $obj) {
      $node->field_shift_performance_work_cen->appendItem($obj);
    }
    foreach ($paragraph5_obj as $obj) {
      $node->field_shift_performance_operator->appendItem($obj);
    }
    $node->save();

    if (empty($node)) {
      return new ResourceResponse(t('Something went wrong. Please contact administrator.'));
    }
    return new ResourceResponse($node);
  }

}
