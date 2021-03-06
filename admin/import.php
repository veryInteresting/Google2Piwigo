<?php
defined('PICASA_WA_PATH') or die('Hacking attempt!');

set_time_limit(600);

include_once(PICASA_WA_PATH . 'include/functions.inc.php');

// check API parameters and connect to flickr
if (empty($conf['google2piwigo']['api_key']) or empty($conf['google2piwigo']['secret_key']))
{
  $_SESSION['page_warnings'][] = l10n('Please fill your API keys on the configuration tab');
  redirect(PICASA_WA_ADMIN . '-config');
}
else if (!test_remote_download())
{
  $page['errors'][] = l10n('No download method available');
  $_GET['action'] = 'error';
}
else
{
  // init Gdata API
  set_include_path(get_include_path() . PATH_SEPARATOR . PICASA_WA_PATH.'include');
  require_once('Zend/Loader.php');
  Zend_Loader::loadClass('Zend_Gdata_AuthSub');
  Zend_Loader::loadClass('Zend_Gdata_Photos');
  require_once('OAuth2/Client.php');
  require_once('OAuth2/GrantType/AuthorizationCode.php');
  
  $oauth_client = new OAuth2_Client($conf['google2piwigo']['api_key'], $conf['google2piwigo']['secret_key']);
  $oauth_redirect_url = get_absolute_root_url() . PICASA_WA_ADMIN . '-import';
  
  // generate token after authentication
  if (!empty($_GET['code']))
  {
    $params = array('code' => $_GET['code'], 'redirect_uri' => $oauth_redirect_url, 'scope' => 'https://picasaweb.google.com/data/');
    $response = $oauth_client->getAccessToken($conf['google2piwigo']['token_endpoint'], 'authorization_code', $params);
    $_SESSION['gdata_auth_token'] = $response['result']['access_token'];
    $_GET['action'] = 'logged';
  }
  
  // must authenticate
  if (!empty($_SESSION['gdata_auth_token']))
  {
    $client = Zend_Gdata_AuthSub::getHttpClient($_SESSION['gdata_auth_token']);
    $picasa = new Zend_Gdata_Photos($client, "Piwigo-Google2Piwigo-1.0");
  }
  else
  {
    $params = array('scope' => 'https://picasaweb.google.com/data/');
    $auth_url = $oauth_client->getAuthenticationUrl($conf['google2piwigo']['auth_endpoint'], $oauth_redirect_url, $params);
    $_GET['action'] = 'init_login';
  }
}


if (!isset($_GET['action']))
{
  $_GET['action'] = 'main';
}

switch ($_GET['action'])
{
  // button to login page
  case 'init_login':
  {
    $template->assign('picasa_login', $auth_url);
    break;
  }
  
  // message after login
  case 'logged':
  {
    $_SESSION['page_infos'][] = l10n('Successfully logged to you Google account');
    redirect(PICASA_WA_ADMIN . '-import');
    break;
  }
  
  // logout
  case 'logout':
  {
    unset($_SESSION['gdata_auth_token']);
    $_SESSION['page_infos'][] = l10n('Logged out');
    redirect(PICASA_WA_ADMIN . '-import');
    break;
  }
  
  // main menu
  case 'main':
  {
    $template->assign(array(
      'username' => $picasa->getUserEntry( $picasa->newUserQuery() )->getGphotoNickname()->getText(),
      'logout_url' =>      PICASA_WA_ADMIN . '-import&amp;action=logout',
      'list_albums_url' => PICASA_WA_ADMIN . '-import&amp;action=list_albums',
      'import_all_url' =>  PICASA_WA_ADMIN . '-import&amp;action=list_all',
      ));
    break;
  }
  
  // list user albums
  case 'list_albums':
  {
    // get all albums
    $userFeed = $picasa->getUserFeed("default");
    
    $albums = array();
    foreach ($userFeed as $userEntry)
    {
      $albums[] = array(
        'title' =>       $userEntry->title->text,
        'description' => $userEntry->mediaGroup->description->text,
        'photos' =>      $userEntry->gphotoNumPhotos->text,
        'U_LIST' => PICASA_WA_ADMIN . '-import&amp;action=list_photos&amp;album=' . $userEntry->gphotoId->text,
        );
    }
    
    $template->assign(array(
      'total_albums' => count($albums),
      'albums' => $albums,
      ));
    break;
  }
  
  // list photos of an album
  case 'list_photos':
  {
    $self_url = PICASA_WA_ADMIN . '-import&amp;action=list_photos&amp;album='.$_GET['album'];
    $picasa_prefix = 'picasa-';
    
    // pagination
    if (isset($_GET['start']))   $page['start'] = intval($_GET['start']);
    else                         $page['start'] = 0;
    if (isset($_GET['display'])) $page['display'] = $_GET['display']=='all' ? 500 : intval($_GET['display']);
    else                         $page['display'] = 20;
    
    // get photos
    $query = $picasa->newAlbumQuery();
    $query->setUser('default');
    $query->setAlbumId($_GET['album']);
    $query->setImgMax('800');
    $albumFeed = $picasa->getAlbumFeed($query);
    
    $all_photos = array();
    foreach ($albumFeed as $albumEntry)
    {
      $all_photos[] = array(
        'id' =>    $albumEntry->getGphotoId()->getText(),
        'name' =>  $albumEntry->mediaGroup->title->text,
        'thumb' => $albumEntry->mediaGroup->thumbnail[1]->url,
        'src' =>   $albumEntry->mediaGroup->content[0]->url,
        'url' =>   $albumEntry->link[2]->href,
        );
    }
    
    // get existing photos
    $query = '
SELECT id, file
  FROM '.IMAGES_TABLE.'
  WHERE file LIKE "'.$picasa_prefix.'%"
;';
    $existing_photos = simple_hash_from_query($query, 'id', 'file');
    $existing_photos = array_map(create_function('$p', 'return preg_replace("#^'.$picasa_prefix.'([0-9]+)\.([a-z]{3,4})$#i", "$1", $p);'), $existing_photos);
    
    // remove existing photos
    $duplicates = 0;
    foreach ($all_photos as $i => $photo)
    {
      if (in_array($photo['id'], $existing_photos))
      {
        unset($all_photos[$i]);
        $duplicates++;
      }
    }
    
    if ($duplicates>0)
    {
      $page['infos'][] = '<a href="admin.php?page=batch_manager&amp;filter=prefilter-picasa">'
          .l10n_dec(
            'One picture is not displayed because already existing in the database.',
            '%d pictures are not displayed because already existing in the database.',
            $duplicates)
        .'</a>';
    }
    
    // displayed photos
    $page_photos = array_slice($all_photos, $page['start'], $page['display']);
    $all_elements = array_map(create_function('$p', 'return  \'"\'.$p["id"].\'"\';'), $all_photos);
  
    $template->assign(array(
      'nb_thumbs_set' =>  count($all_photos),
      'nb_thumbs_page' => count($page_photos),
      'thumbnails' =>     $page_photos,
      'all_elements' =>   $all_elements,
      'album' =>          $_GET['album'],
      'F_ACTION' =>       PICASA_WA_ADMIN.'-import&amp;action=import_set',
      'U_DISPLAY' =>      $self_url,
      ));
      
    // get piwigo categories
    $query = '
SELECT id, name, uppercats, global_rank
  FROM '.CATEGORIES_TABLE.'
;';
    display_select_cat_wrapper($query, array(), 'category_parent_options');
    
    // get navbar
    $nav_bar = create_navigation_bar(
      $self_url,
      count($all_elements),
      $page['start'],
      $page['display']
      );
    $template->assign('navbar', $nav_bar);
    break;
  }
  
  // list all photos of the user
  case 'list_all':
  {
    $picasa_prefix = 'picasa-';
    
    // get all photos in all albums
    $userFeed = $picasa->getUserFeed("default");

    $all_photos = array();
    foreach ($userFeed as $userEntry)
    {
      $query = $picasa->newAlbumQuery();
      $query->setUser('default');
      $query->setAlbumId( $userEntry->gphotoId->text );
      $albumFeed = $picasa->getAlbumFeed($query);
      
      foreach ($albumFeed as $albumEntry)
      {
        $all_photos[ $albumEntry->getGphotoId()->getText() ] = $userEntry->gphotoId->text;
      }
    }
    
    // get existing photos
    $query = '
SELECT id, file
  FROM '.IMAGES_TABLE.'
  WHERE file LIKE "'.$picasa_prefix.'%"
;';
    $existing_photos = simple_hash_from_query($query, 'id', 'file');
    $existing_photos = array_map(create_function('$p', 'return preg_replace("#^'.$picasa_prefix.'([0-9]+)\.([a-z]{3,4})$#i", "$1", $p);'), $existing_photos);
    
    // remove existing photos
    $duplicates = 0;
    foreach ($all_photos as $id => &$photo)
    {
      if (in_array($id, $existing_photos))
      {
        unset($all_photos[$id]);
        $duplicates++;
      }
      else
      {
        $photo = array(
          'id' => $id,
          'album' => $photo,
          );
      }
    }
    unset($photo);
    $all_photos = array_values($all_photos);
    
    if ($duplicates>0)
    {
      $page['infos'][] = '<a href="admin.php?page=batch_manager&amp;filter=prefilter-picasa">'
          .l10n_dec(
            'One picture is not displayed because already existing in the database.',
            '%d pictures are not displayed because already existing in the database.',
            $duplicates)
        .'</a>';
    }
    
    $template->assign(array(
      'nb_elements' =>  count($all_photos),
      'all_elements' => $all_photos,
      'F_ACTION' =>     PICASA_WA_ADMIN . '-import&amp;action=import_set',
      ));
      
    // get piwigo categories
    $query = '
SELECT id, name, uppercats, global_rank
  FROM '.CATEGORIES_TABLE.'
;';
    display_select_cat_wrapper($query, array(), 'category_parent_options');
    break;
  }
  
  // success message after import
  case 'import_set':
  {
    if (isset($_POST['done']))
    {
      $_SESSION['page_infos'][] = l10n('%d pictures imported', $_POST['done']);
    }
    redirect(PICASA_WA_ADMIN . '-import');
  }
}


$template->assign('ACTION', $_GET['action']);

$template->set_filename('picasa_web_albums', realpath(PICASA_WA_PATH . '/admin/template/import.tpl'));
