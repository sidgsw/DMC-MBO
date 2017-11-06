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
 *   id = "pas_import",
 *   label = @Translation("Performance area setup import"),
 *   uri_paths = {
 *     "https://www.drupal.org/link-relations/create" = "/pas/import"
 *   }
 * )
 */
class PerformanceAreaSetup extends ResourceBase {

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

    $paras = $data['performance_area_work_centers'];
    $gpl = $data['gpl'];
    $title = $data['title'];
    if (empty($title)) {
      return new ResourceResponse(t('Invalid input'));
    }

    foreach ($paras as $key => $value) {

      $cost_kpi_paragraph_obj = array();
      foreach ($value['paragraphs']['cost_kpi_paragraph'] as $key2 => $value2) {
        $cost_kpi_paragraph = Paragraph::create([
          'type' => 'performance_area_kpi',
          'field_cost_kpi' => $value2['cost_kpi'],
          'field_weightage' => $value2['weightage'],
        ]);
        $cost_kpi_paragraph->save();
        $cost_kpi_paragraph_obj[] = $cost_kpi_paragraph;
      }

      $pas_paragraph = Paragraph::create([
        'type' => 'performance_area_work_center',
        'field_work_center' => $value['paragraphs']['work_center'],
        'field_description' => $value['paragraphs']['work_center_desc'],
        'field_cost_center' => $value['paragraphs']['cost_center'],
      ]);
      foreach ($cost_kpi_paragraph_obj as $obj2) {
        $pas_paragraph->field_performance_area_cost_kpi->appendItem($obj2);
      }
      $pas_paragraph->save();
      $pas_paragraph_obj[] = $pas_paragraph;

    }

    $node = Node::create([
      'type'        => 'performance_area_setup',
      'title'       => $title,
      'field_user_gpl' => $gpl
    ]);
    foreach ($pas_paragraph_obj as $obj) {
      $node->field_performance_area_work_cent->appendItem($obj);
    }
    $node->save();

    if (empty($node)) {
      return new ResourceResponse(t('Something went wrong. Please contact administrator.'));
    }
    return new ResourceResponse($node);
  }

}
