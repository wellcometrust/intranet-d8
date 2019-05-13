<?php

namespace Drupal\tn_migration\Plugin\Utils;

/**
 * Tag utilities for migration.
 */
class Tags {

  /**
   * Get term id from xref (loaded vocabularies).
   *
   * @param $name
   * @param $vocabs
   *
   * @return bool|int
   */
  public static function getXref($name, $vocabs) {
    if (empty($vocabs)) {
      return NULL;
    }
    $name = self::tidyName($name);
    $vocabs = is_array($vocabs) ? $vocabs : [$vocabs];

    return self::getTid($name, $vocabs);
  }

  /**
   * Minor tidy up of name.
   *
   * @param $name
   *
   * @return string
   */
  public static function tidyName($name) {
    if (!self::isEmpty($name)) {
      $maps = [];
      if (isset($maps[$name])) {
        $name = $maps[$name];
      }
    }
    return $name;
  }

  /**
   * Test is value is empty.
   *
   * @param $value
   *
   * @return bool
   */
  public static function isEmpty(&$value) {
    switch (TRUE) {
      case !isset($value):
        return TRUE;

      case is_string($value):
        return strlen(trim($value)) == 0;

      case is_int($value):
        return FALSE;

      default:
        return empty($value);
    }
  }

  // Private methods.
  /**
   * Derive term id from supplied "title".
   *
   * @param $name
   * @param $vocabularies
   *
   * @return int | bool
   */
  private static function getTid($name, array $vocabularies = []) {
    foreach ($vocabularies as $vid) {
      if ($terms = taxonomy_term_load_multiple_by_name($name, $vid)) {
        $tids = array_keys($terms);
        return $tids[0];
      }
    }
    return FALSE;
  }

}
