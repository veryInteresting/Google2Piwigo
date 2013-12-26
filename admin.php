<?php
defined('PICASA_WA_PATH') or die('Hacking attempt!');

global $template, $page, $conf;

load_language('plugin.lang', PICASA_WA_PATH);

if (!file_exists(PICASA_WA_CACHE))
{
  mkdir(PICASA_WA_CACHE, 0755);
}

// include page
include(PICASA_WA_PATH . 'admin/import.php');

// template
$template->assign(array(
  'PICASA_WA_PATH'=> PICASA_WA_PATH,
  'PICASA_WA_ABS_PATH'=> dirname(__FILE__).'/',
  'PICASA_WA_ADMIN' => PICASA_WA_ADMIN,
  ));

$template->assign_var_from_handle('ADMIN_CONTENT', 'picasa_web_albums');
