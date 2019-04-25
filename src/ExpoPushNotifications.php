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

  public function sendNotification($uid, $notice_type, $data = []) {
    $user = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties([
        'uid' => $account->id(),
      ]);
    
    $user = reset($user);
    $tokens = $user->get('push_notification_device_token_expo');

    if (!empty($tokens)) {
      // Build the notification data
      switch ($notice_type) {
        case 'message':
          $notification = [
            'body' => 'You have a new message',
            'data'=> json_encode([
              'type' => 'message',
              'id' => $data['id'],
            ]),
          ];
          break;
      }

      foreach ($tokens as $token) {
        $interestDetails = ['barnesteam', 'ExponentPushToken[' . $token . ']'];

        // Subscribe the recipient to the server
        $this->expo->subscribe($interestDetails[0], $interestDetails[1]);
      
        // Notify an interest with a notification
        $this->expo->notify($interestDetails[0], $notification);
      }
    }
  }

}
