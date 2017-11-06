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
 *   id = "weekly_core_competencies_import",
 *   label = @Translation("Weekly Core Competencies"),
 *   uri_paths = {
 *     "https://www.drupal.org/link-relations/create" = "weekly/core/competencies/import"
 *   }
 * )
 */
class WeeklyCoreCompetencies extends ResourceBase {

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
    $year = $data['year'];
    $week = $data['week'];
    $title = $data['title'];
    $paragraphs = $data['paragraphs'];

    if (empty($title)) {
      return new ResourceResponse(t('Invalid input'));
    }

    foreach ($paragraphs as $key => $value) {
      $operator = $value['operator'];
      $operator_comment = $value['operator_comment'];

      $paragraph2_obj = array();
      foreach ($value['core_competencies_para'] as $key2 => $value2) {
        $paragraph2 = Paragraph::create([
          'type' => 'core_competencies',
          'field_comment' => $value2['comment'],
          'field_ratings' => $value2['ratings'],
          'field_core_competencies_link' => $value2['core_competency_link']
        ]);
        $paragraph2->save();
        $paragraph2_obj[] = $paragraph2;
      }

      $paragraph1 = Paragraph::create([
        'type' => 'operator_core_competencies',
        'field_operator_comment' => $operator_comment,
        'field_single_operator' => $operator
      ]);
      foreach ($paragraph2_obj as $p_obj2) {
        $paragraph1->field_core_competencies_para->appendItem($p_obj2);
      }
      $paragraph1->save();
      $paragraph1_obj[] = $paragraph1;
    }

    $node = Node::create([
      'type'        => 'weekly_core_competency',
      'title'       => $title,
      'field_week' => $week,
      'field_year' => $year,
    ]);
    foreach ($paragraph1_obj as $obj) {
      $node->field_core_competencies_details->appendItem($obj);
    }
    $node->save();

    if (empty($node)) {
      return new ResourceResponse(t('Something went wrong. Please contact administrator.'));
    }
    return new ResourceResponse($node);
  }

}
