<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');


function plugin_install() 
{
  global $conf;
  
  mkgetdir(PHPWG_ROOT_PATH . $conf['data_location'] . 'picasa_wa_cache/', MKGETDIR_DEFAULT&~MKGETDIR_DIE_ON_ERROR);
}

function plugin_activate()
{
  global $conf;
  
  if (!file_exists(PHPWG_ROOT_PATH . $conf['data_location'] . 'picasa_wa_cache/'))
  {
    mkgetdir(PHPWG_ROOT_PATH . $conf['data_location'] . 'picasa_wa_cache/', MKGETDIR_DEFAULT&~MKGETDIR_DIE_ON_ERROR);
  }
}

function plugin_uninstall() 
{
  global $conf;
  
  rrmdir(PHPWG_ROOT_PATH . $conf['data_location'] . 'picasa_wa_cache/');
}

function rrmdir($dir)
{
  if (!is_dir($dir))
  {
    return false;
  }
  $dir = rtrim($dir, '/');
  $objects = scandir($dir);
  $return = true;
  
  foreach ($objects as $object)
  {
    if ($object !== '.' && $object !== '..')
    {
      $path = $dir.'/'.$object;
      if (filetype($path) == 'dir') 
      {
        $return = $return && rrmdir($path); 
      }
      else 
      {
        $return = $return && @unlink($path);
      }
    }
  }
  
  return $return && @rmdir($dir);
} 

?>