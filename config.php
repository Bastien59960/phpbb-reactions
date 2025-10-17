<?php
// phpBB 3.3.x auto-generated configuration file
// Do not change anything in this file!
$dbms = 'phpbb\\db\\driver\\mysqli';
$dbhost = '';
$dbport = '';
$dbname = 'bastien-phpbb';
$dbuser = 'bastien';
$dbpasswd = 'OvpgY3ijiDsB6W6Rvocx';
$table_prefix = 'phpbb_';
$phpbb_adm_relative_path = 'adm/';
$acm_type = 'phpbb\\cache\\driver\\file';

$debug = true;

@define('PHPBB_INSTALLED', true);
@define('PHPBB_ENVIRONMENT', 'production');
// @define('DEBUG_CONTAINER', true);
@define('DEBUG', true);
@define('DEBUG_CONTAINER', true);
$dbcharset = 'utf8mb4';

$config['phpbb.dependency_injection.compile_container_on_load'] = true;
