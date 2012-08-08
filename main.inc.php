<?php 
/*
Plugin Name: Google2Piwigo
Version: auto
Description: Import photos from your Google account (including Picasa Web Albums)
Plugin URI: http://piwigo.org/ext/extension_view.php?eid=628
Author: Mistic
Author URI: http://www.strangeplanet.fr
*/

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

global $conf;

define('PICASA_WA_PATH', PHPWG_PLUGINS_PATH . basename(dirname(__FILE__)) . '/');
define('PICASA_WA_ADMIN', get_root_url() . 'admin.php?page=plugin-' . basename(dirname(__FILE__)));
define('PICASA_WA_CACHE', $conf['data_location'].'picasa_wa_cache/');


add_event_handler('get_admin_plugin_menu_links', 'picasa_wa_admin_menu');

function picasa_wa_admin_menu($menu) 
{
  array_push($menu, array(
    'NAME' => 'Google2Piwigo',
    'URL' => PICASA_WA_ADMIN,
  ));
  return $menu;
}


include_once(PICASA_WA_PATH . 'include/ws_functions.inc.php');

add_event_handler('ws_add_methods', 'picasa_wa_add_ws_method');

?>