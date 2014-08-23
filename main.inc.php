<?php 
/*
Plugin Name: Google2Piwigo
Version: auto
Description: Import photos from your Google account (including Picasa Web Albums)
Plugin URI: auto
Author: Mistic
Author URI: http://www.strangeplanet.fr
*/

defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

global $conf;

define('PICASA_WA_ID',    basename(dirname(__FILE__)));
define('PICASA_WA_PATH',  PHPWG_PLUGINS_PATH . PICASA_WA_ID . '/');
define('PICASA_WA_ADMIN', get_root_url() . 'admin.php?page=plugin-' . PICASA_WA_ID);
define('PICASA_WA_CACHE', PHPWG_ROOT_PATH . $conf['data_location'] . 'picasa_wa_cache/');


if (defined('IN_WS'))
{
  include_once(PICASA_WA_PATH . 'include/ws_functions.inc.php');

  add_event_handler('ws_add_methods', 'picasa_wa_add_ws_method');
}

if (defined('IN_ADMIN'))
{
  add_event_handler('get_admin_plugin_menu_links', 'picasa_wa_admin_menu');

  add_event_handler('get_batch_manager_prefilters', 'picasa_wa_add_batch_manager_prefilters');
  add_event_handler('perform_batch_manager_prefilters', 'picasa_wa_perform_batch_manager_prefilters', EVENT_HANDLER_PRIORITY_NEUTRAL, 2);

  function picasa_wa_admin_menu($menu) 
  {
    $menu[] = array(
      'NAME' => 'Google2Piwigo',
      'URL' => PICASA_WA_ADMIN,
      );
    return $menu;
  }

  function picasa_wa_add_batch_manager_prefilters($prefilters)
  {
    $prefilters[] = array(
      'ID' => 'picasa',
      'NAME' => l10n('Imported from Google/Picasa'),
      );
    return $prefilters;
  }

  function picasa_wa_perform_batch_manager_prefilters($filter_sets, $prefilter)
  {
    if ($prefilter == 'picasa')
    {
      $query = '
  SELECT id
    FROM '.IMAGES_TABLE.'
    WHERE file LIKE "picasa-%"
  ;';
      $filter_sets[] = query2array($query, null, 'id');
    }
    
    return $filter_sets;
  }
}
