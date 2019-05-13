#!/usr/bin/env bash
#Add certificate
#sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain ~/.lando/certs/lndo.site.pem
#lando destroy -y
#lando start
#lando db-import tools/lando/assets/database/db.sql --host migrationdb
lando drush si trustnet --account-name=admin --account-pass=admin -y --existing-config
lando drush en migrate_tools,migrate_file,tn_group_migration,tn_content_migration,tn_migration,migrate,migrate_drupal -y
lando drush migrate:rollback tn_groups_groups
lando drush migrate:import tn_groups_groups
lando drush migrate:import tn_content_articles
lando drush pm-uninstall migrate_tools,migrate_file,tn_group_migration,tn_content_migration,tn_migration,migrate,migrate_drupal -y
