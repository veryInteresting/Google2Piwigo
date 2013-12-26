<?php
defined('PICASA_WA_PATH') or die('Hacking attempt!');

function picasa_wa_add_ws_method($arr)
{
  $service = &$arr[0];
  
  $service->addMethod(
    'pwg.images.addPicasa',
    'ws_images_addPicasa',
    array(
      'id' => array(),
      'pwa_album' => array(),
      'category' => array(),
      'fills' => array('default' => 'fill_name,fill_author,fill_tags,fill_date,fill_description'),
      ),
    'Used by Picasa Web Albums',
    null,
    array('hidden'=>true)
    );
}

function ws_images_addPicasa($params, &$service)
{
  if (!is_admin())
  {
    return new PwgError(401, 'Forbidden');
  }
  
  global $conf, $pwg_loaded_plugins;
  
  include_once(PHPWG_ROOT_PATH . 'admin/include/functions.php');
  include_once(PHPWG_ROOT_PATH . 'admin/include/functions_upload.inc.php');
  include_once(PICASA_WA_PATH . 'include/functions.inc.php');
  
  if (test_remote_download() === false)
  {
    return new PwgError(null, l10n('No download method available'));
  }
  
  if (empty($_SESSION['gdata_auth_token']))
  {
    return new PwgError(null, l10n('API not authenticated'));
  }
  
  // init Gdata API
  set_include_path(get_include_path() . PATH_SEPARATOR . PICASA_WA_PATH.'include');
  require_once('Zend/Loader.php');
  Zend_Loader::loadClass('Zend_Gdata_AuthSub');
  Zend_Loader::loadClass('Zend_Gdata_Photos');
  
  $client = Zend_Gdata_AuthSub::getHttpClient($_SESSION['gdata_auth_token']);
  $picasa = new Zend_Gdata_Photos($client, "Piwigo-Google2Piwigo-1.0");
  
  // photos infos
  $query = $picasa->newPhotoQuery();
  $query->setUser('default');
  $query->setAlbumId($params['pwa_album']);
  $query->setPhotoId($params['id']);
  $query->setImgMax('d');
  $photoEntry = $picasa->getPhotoEntry($query);
  
  $photo = array(
    'id' =>           $params['id'],
    'url' =>          $photoEntry->getMediaGroup()->content[0]->getUrl(),
    'title' =>        get_filename_wo_extension($photoEntry->getMediaGroup()->getTitle()->getText()),
    'author' =>       $photoEntry->getMediaGroup()->credit[0]->getText(),
    'description' =>  $photoEntry->getMediaGroup()->getDescription()->getText(),
    'tags' =>         $photoEntry->getMediaGroup()->getKeywords()->getText(),
    'timestamp' =>    substr($photoEntry->getGphotoTimestamp(), 0, -3),
    'latlon' =>       null,
    );
  
  $photo['path'] = PICASA_WA_CACHE . 'picasa-'.$photo['id'].'.'.get_extension($photo['url']);
  
  if ($photoEntry->getGeoRssWhere() !== null)
  {
    $photo['latlon'] = $photoEntry->getGeoRssWhere()->getPoint()->getPos()->getText();
  }
  
  // copy file
  $ti = microtime(true);
  if (picasa_wa_download_remote_file($photo['url'], $photo['path']) !== true)
  {
    return new PwgError(null, l10n('Can\'t download file'));
  }
  
  // category
  if ($params['category'] == '<!-- create -->')
  {
    // search existing category
    $query = '
SELECT id FROM '.CATEGORIES_TABLE.'
  WHERE name LIKE("%<!-- picasa-'.$params['pwa_album'].' -->")
;';
    $result = pwg_query($query);
    
    if (pwg_db_num_rows($result))
    {
      list($cat_id) = pwg_db_fetch_row($result);
      $photo['category'] = $cat_id;
    }
    else
    {
      // create new category
      $query = $picasa->newAlbumQuery();
      $query->setUser('default');
      $query->setAlbumId($params['pwa_album']);
      $albumEntry = $picasa->getAlbumEntry($query);
      
      $category = array(
        'name' => pwg_db_real_escape_string($albumEntry->mediaGroup->title->text).' <!-- picasa-'.$params['pwa_album'].' -->',
        'comment' => pwg_db_real_escape_string($albumEntry->mediaGroup->description->text),
        'parent' => 0,
        );
  
      $cat = ws_categories_add($category, $service);
      $photo['category'] = $cat['id'];
    }
  }
  else
  {
    $photo['category'] = $params['category'];
  }
  
  // add photo
  $photo['image_id'] = add_uploaded_file($photo['path'], basename($photo['path']), array($photo['category']));
  
  // do some updates
  if (!empty($params['fills']))
  {
    $params['fills'] = rtrim($params['fills'], ',');
    $params['fills'] = explode(',', $params['fills']);
  
    $updates = array();
    if (in_array('fill_name', $params['fills']))        $updates['name'] = pwg_db_real_escape_string($photo['title']); 
    if (in_array('fill_date', $params['fills']))        $updates['date_creation'] = date('Y-m-d H:i:s', $photo['timestamp']);
    if (in_array('fill_author', $params['fills']))      $updates['author'] = pwg_db_real_escape_string($photo['author']);
    if (in_array('fill_description', $params['fills'])) $updates['comment'] = pwg_db_real_escape_string($photo['description']);
    if (in_array('fill_geotag', $params['fills']) and !empty($photo['latlon']))
    {
      $latlon = explode(' ', $photo['latlon']);
      if (count($latlon) == 2)
      {
        $updates['latitude'] = pwg_db_real_escape_string($latlon[0]);
        $updates['longitude'] = pwg_db_real_escape_string($latlon[1]);
      }
    }
    
    if (count($updates))
    {
      single_update(
        IMAGES_TABLE,
        $updates,
        array('id' => $photo['image_id'])
        );
    }
    
    if (!empty($photo['tags']) and in_array('fill_tags', $params['fills']))
    {
      set_tags(get_tag_ids($photo['tags']), $photo['image_id']);
    }
  }
  
  return l10n('Photo "%s" imported', $photo['title']);
}
