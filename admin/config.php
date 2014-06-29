<?php
defined('PICASA_WA_PATH') or die('Hacking attempt!');

if (isset($_POST['save_config']))
{
  $conf['google2piwigo'] = array(
    'api_key' => trim($_POST['api_key']),
    'secret_key' => trim($_POST['secret_key']),
    'auth_endpoint' => $conf['google2piwigo']['auth_endpoint'],
    'token_endpoint' => $conf['google2piwigo']['token_endpoint'],
    );
  unset($_SESSION['gata_auth_token']);

  conf_update_param('google2piwigo', serialize($conf['google2piwigo']));
  $page['infos'][] = l10n('Information data registered in database');
}


$template->assign(array(
  'google2piwigo' => $conf['google2piwigo'],
  'PICASA_WA_HELP_CONTENT' => load_language('help_api_key.html', PICASA_WA_PATH, array('return'=>true)),
  'PICASA_WA_CALLBACK' => get_absolute_root_url() . PICASA_WA_ADMIN . '-import',
  ));


$template->set_filename('picasa_web_albums', realpath(PICASA_WA_PATH . 'admin/template/config.tpl'));
