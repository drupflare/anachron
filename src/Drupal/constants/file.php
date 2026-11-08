<?php

use Drupal\Core\File\FileSystemInterface;

const FILE_CREATE_DIRECTORY = FileSystemInterface::CREATE_DIRECTORY;

const FILE_MODIFY_PERMISSIONS = FileSystemInterface::MODIFY_PERMISSIONS;

// the Drupal 7 integer values; core 12 removed the constants and takes the FileExists enum
const FILE_EXISTS_RENAME = 0;

const FILE_EXISTS_REPLACE = 1;

const FILE_EXISTS_ERROR = 2;

// Moved to FileInterface, which is only available after file module loads.
const FILE_STATUS_PERMANENT = 1;

define('FILE_INSECURE_EXTENSIONS', implode('|', FileSystemInterface::INSECURE_EXTENSIONS));
