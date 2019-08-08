<?php

namespace Drupal\pushy\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "messages_resource",
 *   label = @Translation("Messages resource"),
 *   uri_paths = {
 *     "canonical" = "/pushy/messages"
 *   }
 * )
 */
class MessagesResource extends ResourceBase {

    /**
     * A current user instance.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected $currentUser;

    /**
     * Constructs a new MessagesResource object.
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
        $container->get('logger.factory')->get('pushy'),
        $container->get('current_user')
      );
    }

    /**
     * Responds to GET requests.
     *
     * @param string $payload
     *
     * @return \Drupal\rest\ResourceResponse
     *   The HTTP response object.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected.
     */
    public function get() {
      if (!intval($this->currentUser->id())) {
        throw new AccessDeniedHttpException();
      }

      $query = \Drupal::entityQuery('message');

      $group = $query->orConditionGroup()
       ->condition('user_id', $this->currentUser->id(), '=')
       ->condition('recipient', [$this->currentUser->id()], 'IN');
      $query->condition($group);

      $date_from = \Drupal::requestStack()->getCurrentRequest()->get('date_from');
      if ($date_from) {
        $query->condition('created', strtotime($date_from), '>');
      }

      $query->sort('created', 'DESC');
      $message_ids = $query->execute();

      $messages = [];

      if (!empty($message_ids)) {
        $messages = \Drupal::entityTypeManager()->getStorage('message')->loadMultiple($message_ids);
        $messages = array_values($messages);
      }

      $build = [
        '#cache' => [
          'max-age' => 0,
        ],
      ];

      return (new ResourceResponse($messages))->addCacheableDependency($build);
    }

  }
