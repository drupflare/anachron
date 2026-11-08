<?php

namespace Drupal\legacy_d10_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Page callbacks that call Drupal 10 procedural helpers.
 */
class LegacyApiController extends ControllerBase {

  /**
   * Formats a byte count with format_size().
   */
  public function formatSize() {
    return new JsonResponse(['size' => (string) format_size(1024)]);
  }

  /**
   * Logs an exception with watchdog_exception().
   */
  public function watchdog() {
    watchdog_exception('legacy_d10_api', new \RuntimeException('feed unreachable'), 'Feed fetch failed: @message');
    return new JsonResponse(['logged' => true]);
  }

  /**
   * Includes a file with module_load_include() and calls a function from it.
   */
  public function moduleLoadInclude() {
    $loaded = module_load_include('inc', 'legacy_d10_api', 'legacy_d10_api.admin');
    return new JsonResponse([
      'file' => basename((string) $loaded),
      'answer' => legacy_d10_api_admin_answer(),
    ]);
  }

  /**
   * Returns role labels with user_role_names().
   */
  public function userRoleNames() {
    return new JsonResponse(user_role_names());
  }

  /**
   * Reports whether the complete theme registry has the html hook.
   */
  public function themeRegistry() {
    return new JsonResponse(['html' => array_key_exists('html', theme_get_registry())]);
  }

  /**
   * Returns the label of one time zone from system_time_zones().
   */
  public function timeZones() {
    $zones = system_time_zones();
    return new JsonResponse(['Europe/London' => (string) $zones['Europe/London']]);
  }

}
