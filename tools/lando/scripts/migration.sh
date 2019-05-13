#!/usr/bin/env bash
lando drush pm-uninstall migrate_tools,migrate_file,tn_group_migration,tn_migration,migrate,migrate_drupal -y
lando drush en migrate_tools,migrate_file,tn_group_migration,tn_migration,migrate,migrate_drupal -y
lando drush migrate:rollback tn_groups
lando drush migrate:import tn_groups
