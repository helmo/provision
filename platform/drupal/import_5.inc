<?php

if (is_array($GLOBALS['db_url'])) {
  $db_url = $GLOBALS['db_url']['default'];
}

if ($parts = @parse_url($db_url)) {
  drush_set_option('db_type', $parts['scheme'], 'site');
  drush_set_option('db_user', $parts['user'], 'site');
  drush_set_option('db_host', $parts['host'], 'site');
  drush_set_option('db_passwd', $parts['pass'], 'site');
  drush_set_option('db_name', substr($parts['path'], 1), 'site');

  drush_set_option('profile', variable_get('install_profile', 'default'), 'site');
  $has_locale = db_result(db_query("SELECT status FROM {system} WHERE type='module' AND name='locale'"));
  if ($has_locale) {
    $locale = db_result(db_query("SELECT locale FROM {locales_meta} WHERE isdefault=1 AND enabled=1"));
  }
  drush_set_option('language', $locale, 'site');
}