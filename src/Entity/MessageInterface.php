<?php

namespace Drupal\pushy\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Message entities.
 *
 * @ingroup pushy
 */
interface MessageInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Message name.
   *
   * @return string
   *   Name of the Message.
   */
  public function getName();

  /**
   * Sets the Message name.
   *
   * @param string $name
   *   The Message name.
   *
   * @return \Drupal\pushy\Entity\MessageInterface
   *   The called Message entity.
   */
  public function setName($name);

  /**
   * Gets the Message creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Message.
   */
  public function getCreatedTime();

  /**
   * Sets the Message creation timestamp.
   *
   * @param int $timestamp
   *   The Message creation timestamp.
   *
   * @return \Drupal\pushy\Entity\MessageInterface
   *   The called Message entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Message published status indicator.
   *
   * Unpublished Message are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Message is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Message.
   *
   * @param bool $published
   *   TRUE to set this Message to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\pushy\Entity\MessageInterface
   *   The called Message entity.
   */
  public function setPublished($published);

}
