<?php

namespace Drupal\legacy_d9_basic;

class Users {

  /**
   * Returns the role ids except anonymous.
   */
  public function memberRoleIds() {
    return array_keys(user_roles(TRUE));
  }

  public function password($length) {
    return user_password($length);
  }

}
