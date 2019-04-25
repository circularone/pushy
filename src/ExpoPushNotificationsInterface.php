<?php

namespace Drupal\pushy;

/**
 * Interface ExpoPushNotificationsInterface.
 */
interface ExpoPushNotificationsInterface {

  public function sendNotification($uid, $notice_type, $data = []);

}

