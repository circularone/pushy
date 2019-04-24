<?php

namespace Drupal\pushy;

/**
 * Interface PushMessagesInterface.
 */
interface PushNotificationsInterface {

  public function sendNotification();

  //public function createPayloadJson($message);

}
