<?php

namespace Drupal\tn_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\ProcessPluginBase;

/**
 * Generate a nice file name.
 *
 * @see Core ProcessPluginBase for more details.
 *
 * Example:
 *
 * @code
 * process:
 *   title:
 *     plugin: tn_nice_file_name
 *     source: filename
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "tn_nice_file_name"
 * )
 */
class NiceFileName extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($path, $filename) = $this->filePathAndName($value);
    $filename = $this->niceFileName($filename);
    return $filename;
  }

  /**
   * Returns file & pathname.
   *
   * @param $filepath
   * @param $detok
   *
   * @return array
   */
  public function filePathAndName($filepath, $detok = TRUE) {
    $pathname = $filename = NULL;
    if (!empty($filepath)) {
      $parts = explode('/', $filepath);
      // Last bit of path.
      $filename = array_pop($parts);
      $pathname = implode('/', $parts);
    }
    $pathname = '';
    return [$pathname, $filename];
  }

  /**
   * Convert to nice name.
   *
   * @param $filename
   *
   * @return string
   */
  public function niceFileName($filename) {
    $filename = str_replace(' ', '-', strtolower($filename));
    $filename = preg_replace('/[^\da-z\.\-_]/', '', $filename);
    return $filename;
  }

}
