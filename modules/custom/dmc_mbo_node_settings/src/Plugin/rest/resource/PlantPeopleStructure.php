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
 *   id = "pps_import",
 *   label = @Translation("Plant People Structure import"),
 *   uri_paths = {
 *     "https://www.drupal.org/link-relations/create" = "/pps/import"
 *   }
 * )
 */
class PlantPeopleStructure extends ResourceBase {

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

    $paras = $data['paragraphs'];
    $gpl = $data['gpl'];
    $title = $data['title'];
    if (empty($title)) {
      return new ResourceResponse(t('Invalid input'));
    }

    foreach ($paras as $key => $value) {
      $paragraph = Paragraph::create([
        'type' => 'plant_peoples',
        'field_operator' => $value['operator'],
        'field_user_tpl' => $value['tpl'],
      ]);
      $paragraph->save();
      $paragraph_obj[] = $paragraph;
    }
    $node = Node::create([
      'type'        => 'plant_people_structure',
      'title'       => $title,
      'field_user_gpl' => $gpl
    ]);
    foreach ($paragraph_obj as $obj) {
      $node->field_plant_peoples_structure->appendItem($obj);
    }
    $node->save();

    if (empty($node)) {
      return new ResourceResponse(t('Something went wrong. Please contact administrator.'));
    }
    return new ResourceResponse($node);
  }

}
