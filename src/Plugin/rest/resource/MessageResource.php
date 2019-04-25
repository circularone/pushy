<?php

namespace Drupal\pushy\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\pushy\Entity\MessageInterface;
use Drupal\pushy\ExpoPushNotificationsInterface;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "pushy_message_resource",
 *   label = @Translation("Message Resource"),
 *   uri_paths = {
 *     "canonical" = "/pushy/message"
 *   }
 * )
 */
class MessageResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Expo notification service.
   *
   * @var \Drupal\pushy\ExpoPushNotificationsInterface
   */
  protected $notification;

  /**
   * Constructs a new Message object.
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
    AccountProxyInterface $current_user,
    ExpoPushNotificationsInterface $notification) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
    $this->notification = $notification;
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
      $container->get('current_user'),
      $container->get('pushy.expo_notifications')
    );
  }

  /**
   * Responds to POST requests.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function post($data) {

    if (!isset($data['message']) || empty($data['message'])) {
      return new ModifiedResourceResponse([
        'error' => $this->t('Message cannot be empty'),
      ], 406);
    }

    $message = Message::create([
      'user_id' => $this->currentUser->id(),
      'body' => $data['message'],
    ]);

    if (isset($data['recipient'])) {
      $message->set('recipient', $data['recipient']);
    }

    $message->save();

    $this->notifications->sendNotification($data['recipient'], 'message', [
      'id' => $message->id(),
    ]);

    return new ModifiedResourceResponse(['test' => 'foo'], 200);
  }

}
