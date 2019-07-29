<?php

namespace Drupal\pushy\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a resource to add a push notification device token.
 *
 * @RestResource(
 *   id = "token_resource",
 *   label = @Translation("Token resource"),
 *   uri_paths = {
 *     "canonical" = "/pushy/token"
 *   }
 * )
 */
class TokenResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The curren request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a new TokenResource object.
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
    Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
    $this->request = $request;
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
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * Responds to PATCH requests.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function patch($data) {

    $device_type = $data['device'];
    $token = $data['token'];
    $device_type = $this->request->query->get('device');
    $token = $this->request->query->get('token');

    if (!$device_type || !$token) {
      return new ModifiedResourceResponse([
        'error' => t('Device and token must be provided'),
      ], 406);
    }

    if (!in_array($device_type, ['expo', 'ios', 'android'])) {
      $this->logger->notice("User tried to register unsupported push notification device token type");

      return new ModifiedResourceResponse([
        'error' => t('Device type must be one of expo, ios or android'),
      ], 406);
    }

    $account = $this->currentUser->getAccount();
    $update_value = [];
    $tokens = $account->get('push_notification_device_token_' . $device_type . '.')->value;

    if (!$tokens) {
      foreach ($tokens as $item) {
        if ($item->value === $token) {
          $this->logger->notice("Push notification token already exists {$device_type}: {token}. Exiting.");

          return new ModifiedResourceResponse($account, 200);
        }
        $update_value[] = ['value' => $item->value];
      }
    }

    $update_value[] = ['value' => $token];
    $account->set('push_notification_device_token_' . $device_type, $update_value);
    $account->save();

    $this->logger->notice("Push notification device token added for {$device_type}: {token}.");

    return new ModifiedResourceResponse($account, 200);

  }

}
