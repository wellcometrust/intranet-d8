<?php

namespace Drupal\tn_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\ProcessPluginBase;

/**
 * Perform custom value transformations for alcohol field.
 *
 * @MigrateProcessPlugin(
 *   id = "tn_nice_stub_name"
 * )
 */
class NiceStubName extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if ($row->isStub()) {
      $prefix = 'Stub: ';
      $value = $prefix . (!empty($value) ? $value : uniqid('', TRUE));
    }
    if ($value) {
      $value = str_replace(' - ', ' – ', $value);
    }

    return $value;
  }

}
