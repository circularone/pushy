<?php

namespace Drupal\pushy;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use ExponentPhpSDK\Expo;

/**
 * Class ExpoPushNotifications.
 */
class ExpoPushNotifications implements ExpoPushNotificationsInterface {

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Expo instance.
   *
   * @var \ExponentPhpSDK\Expo
   */
  private $expo;

  /**
   * Constructs a new PushMessages object.
   *
   */
  public function __construct(
    LoggerChannelFactoryInterface $logger_factory,
    ConfigFactoryInterface $config_factory,
    AccountProxyInterface $current_user
  ) {
    $this->loggerFactory = $logger_factory;
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->expo = \ExponentPhpSDK\Expo::normalSetup();
  }

  public function sendNotification() {
    $interestDetails = ['hola', 'ExponentPushToken[aq-ZPhOamnne5N0kc9hzOB]'];

    // Subscribe the recipient to the server
    $this->expo->subscribe($interestDetails[0], $interestDetails[1]);

    // Build the notification data
    $notification = ['body' => 'Hello World!'];

    // Build the notification data
    // $notification = [
    //   'body' => 'Hello World!',
    //   'data'=> json_encode(array('someData' => 'goes here'))
    // ];

    // Notify an interest with a notification
    $this->expo->notify($interestDetails[0], $notification);
  }

}
