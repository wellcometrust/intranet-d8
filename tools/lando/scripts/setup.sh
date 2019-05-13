#!/usr/bin/env bash
#Add certificate
#sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain ~/.lando/certs/lndo.site.pem
lando destroy -y
lando start
lando db-import tools/lando/assets/database/db.sql --host migrationdb
lando drush si trustnet --account-name=admin --account-pass=admin -y --existing-config
