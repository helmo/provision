<?php

function drush_db_pre_provision_backup() {
  d()->service('db')->generate_dump();
}

function drush_db_pre_provision_backup_rollback() {
  d()->service('file')->unlink(d()->root . '/sites/' . d()->uri . '/database.sql')
    ->succeed('Deleted mysql dump from sites directory')
    ->fail('Could not delete mysql dump from sites directory');
}

function drush_db_post_provision_backup() {
  drush_db_pre_provision_backup_rollback();
}
