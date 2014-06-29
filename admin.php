<?php
defined('PICASA_WA_PATH') or die('Hacking attempt!');

global $template, $page, $conf;

load_language('plugin.lang', PICASA_WA_PATH);

if (!file_exists(PICASA_WA_CACHE))
{
  mkdir(PICASA_WA_CACHE, 0755);
}

// tabsheet
include_once(PHPWG_ROOT_PATH.'admin/include/tabsheet.class.php');
$page['tab'] = (isset($_GET['tab'])) ? $_GET['tab'] : $page['tab'] = 'import';

$tabsheet = new tabsheet();
$tabsheet->add('import', l10n('Import'), PICASA_WA_ADMIN . '-import');
$tabsheet->add('config', l10n('Configuration'), PICASA_WA_ADMIN . '-config');
$tabsheet->select($page['tab']);
$tabsheet->assign();

// include page
include(PICASA_WA_PATH . 'admin/' . $page['tab'] . '.php');

// template
$template->assign(array(
  'PICASA_WA_PATH'=> PICASA_WA_PATH,
  'PICASA_WA_ABS_PATH'=> dirname(__FILE__).'/',
  'PICASA_WA_ADMIN' => PICASA_WA_ADMIN,
  ));

$template->assign_var_from_handle('ADMIN_CONTENT', 'picasa_web_albums');
