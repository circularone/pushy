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
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Routing\AccessAwareRouterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Drupal\pushy\Entity\Message;
use Drupal\user\Entity\User;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "pushy_message_resource",
 *   label = @Translation("Message Resource"),
 *   uri_paths = {
 *     "canonical" = "/pushy/message",
 *     "https://www.drupal.org/link-relations/create" = "/pushy/message"
 *   }
 * )
 */
class MessageResource extends ResourceBase {

  use EntityResourceValidationTrait;
  use EntityResourceAccessTrait;

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

    // if (!$data || !isset($data['body']) || empty($data['body'])) {
    //   throw new BadRequestHttpException('No entity content received.');
    // }

    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    $account = $user_storage->load($this->currentUser->id());

    if (isset($data['recipients']) && !empty($data['recipients']) && is_array($data['recipients'])) {
      if (!in_array('admin', $account->getRoles())) {
        // Non admins can only send to admins
        $user_ids = \Drupal::entityQuery('user')
          ->condition('status', 1)
          ->condition('roles', 'admin')
          ->condition('uid', $data['recipients'], 'IN')
          ->execute();
      }
      else {
        $user_ids = \Drupal::entityQuery('user')
          ->condition('status', 1)
          ->condition('uid', $data['recipients'], 'IN')
          ->execute();
      }
    }

    if (!isset($users_ids) || empty($user_ids)) {
      // Send to all admins
      $user_ids = \Drupal::entityQuery('user')
        ->condition('status', 1)
        ->condition('roles', 'admin')
        ->execute();
    }

    $users = $user_storage->loadMultiple($user_ids);
    $recipients = [];

    foreach ($users as $user) {
      $recipients[] = [
        'target_id' => $user->id(),
      ];
    }

    $entity = Message::create([
      'body' => $data['body'],
      'user_id' => ['target_id' => $account->id()],
      'recipient' => $recipients,
    ]);

    if (isset($data['field_image']) && $data['field_image']) {
      $entity->set('field_image', [['target_id' => $data['field_image']]]);
    }

    $entity_access = $entity->access('create', NULL, TRUE);

    // if (!$entity_access->isAllowed()) {
    //   throw new AccessDeniedHttpException($entity_access->getReason() ?: $this->generateFallbackAccessDeniedMessage($entity, 'create'));
    // }

    if (!$entity->isNew()) {
      throw new BadRequestHttpException('Only new entities can be created');
    }

    try {
      $entity->save();
      $this->logger->notice('Created entity %type with ID %id.', ['%type' => $entity->getEntityTypeId(), '%id' => $entity->id()]);

      // \Drupal::service('pushy.expo_notifications')->sendNotification(1, 'message', ['id' => $entity->id()]);

      return new ModifiedResourceResponse($entity, 201);
    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, 'Internal Server Error', $e->getMessage());
    }
  }

}
