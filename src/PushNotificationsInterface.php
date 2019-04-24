<?php

namespace Drupal\pushy;

/**
 * Interface PushNotificationsInterface.
 */
interface PushNotificationsInterface {

  public function sendNotification();

  //public function createPayloadJson($message);

}
