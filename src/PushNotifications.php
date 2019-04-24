<?php

namespace Drupal\pushy;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Class PushMessages.
 */
class PushNotifications implements PushNotificationsInterface {

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
   * Constructs a new PushNotifications object.
   */
  public function __construct(
    LoggerChannelFactoryInterface $logger_factory,
    ConfigFactoryInterface $config_factory,
    AccountProxyInterface $current_user
  ) {
    $this->loggerFactory = $logger_factory;
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
  }

  public function sendNotification() {
    // Default result.
    $result = -1;
    // Change depending on where to send notifications - either production
    // or development.

    $payload_info = $this->createPayloadJson('foo');
    
    $pem_preference = "development";
    $user_device_type = $user_mobile_info['user_device_type'];
    $user_device_key = $user_mobile_info['user_mobile_token'];

    switch($user_device_type) {
      case 'iOS':
        $apns_url = NULL;
        $apns_cert = NULL;
        // Apple server listening port.
        $apns_port = 2195;

        if ($pem_preference == 'production') {
          $apns_url = 'gateway.push.apple.com';
          $apns_cert = __DIR__ . '/cert-prod.pem';
        }
        // develop .pem
        else {
          $apns_url = 'gateway.sandbox.push.apple.com';
          $apns_cert = '/Users/davidmcnee/vhosts/barnes-team/devpushcert.pem';
        }

        $stream_context = stream_context_create();
        stream_context_set_option($stream_context, 'ssl', 'local_cert', $apns_cert);

        $apns = stream_socket_client('ssl://' . $apns_url . ':' . $apns_port, $error, $error_string, 2, STREAM_CLIENT_CONNECT, $stream_context);

        $apns_message = chr(0) . chr(0) . chr(32) . pack('H*', str_replace(' ', '', $user_device_key)) . chr(0) . chr(strlen($payload_info)) . $payload_info;

        if ($apns) {
          $result = fwrite($apns, $apns_message);
        }

        @socket_close($apns);
        @fclose($apns);
      break;

    case 'Android':
      // API access key from Google API's Console.
      define('API_ACCESS_KEY', ADD_HERE);

      // prep the bundle.
      $msg = [
        'message' => json_decode($payload_info)->aps->alert,
        'title' => 'This is a title. title',
        'subtitle' => 'This is a subtitle. subtitle',
        'tickerText'=> 'Ticker text here...Ticker text here...Ticker text here',
        'vibrate' => 1,
        'sound' => 1,
        'largeIcon' => 'large_icon',
        'smallIcon' => 'small_icon',
      ];

      $fields = [
        'registration_ids' => [$user_device_key],
        'data' => $msg
      ];

      $headers = [
        'Authorization: key=' . API_ACCESS_KEY,
        'Content-Type: application/json',
      ];

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, 'https://android.googleapis.com/gcm/send');
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
      $result = curl_exec($ch);
      curl_close($ch);
    }

    return $result > 0;
  }

  //Create json file to send to Apple/Google Servers with notification request and body
  public function createPayloadJson($message) {
    // Badge icon to show at users ios app icon after receiving notification
    $badge = "0";
    $sound = 'default';

    $payload = [
      'aps' => [
        'alert' => $message,
        'badge' => intval($badge),
        'sound' => $sound,
      ],
    ];

    return json_encode($payload);
  }

}
