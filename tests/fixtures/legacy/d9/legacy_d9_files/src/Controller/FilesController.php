<?php

namespace Drupal\legacy_d9_files\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\HttpFoundation\Response;

class FilesController extends ControllerBase {

  public function save($name) {
    $file = file_save_data('hello from d9', 'public://legacy-d9/' . $name, FileSystemInterface::EXISTS_REPLACE);
    if ($file === FALSE) {
      return new Response('save failed');
    }
    \Drupal::database()->insert('legacy_d9_files_log')
      ->fields(['uri' => $file->getFileUri()])
      ->execute();
    return new Response($file->getFileUri());
  }

  public function munge($name) {
    return new Response(file_munge_filename($name, 'txt jpg', FALSE));
  }

  public function schema() {
    $table = drupal_get_module_schema('legacy_d9_files', 'legacy_d9_files_log');
    return new Response($table['description']);
  }

  public function version() {
    return new Response((string) drupal_get_installed_schema_version('legacy_d9_files'));
  }

}
