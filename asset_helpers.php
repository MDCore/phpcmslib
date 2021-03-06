<?

function default_scripts()
{
  ?><script type="text/javascript" src="vendor/jquery/jquery-1.3.2.min.js"></script><?
  }

  function image_path($asset_name, $face = null)
  {
    return asset_path('image', $asset_name, $face);
  }

  function stylesheet_path($asset_name, $face = null)
  {
    return asset_path('stylesheet', $asset_name, $face);
  }

  function script_path($asset_name, $face = null)
  {
    return asset_path('script', $asset_name, $face);
  }

  function swf_path($asset_name, $face = null)
  {
    return asset_path('swf', $asset_name, $face);
  }
  function font_path($asset_name, $face = null)
  {
    return asset_path('font', $asset_name, $face);
  }

  function asset_path($asset_type, $asset_name, $face = null)
  {
    switch ($asset_type)
    {
    case 'image':
    case 'stylesheet':
    case 'script':
    case 'swf':
    case 'font':
      break;
    default:
      throw new Exception("Invalid asset type"); return false;
    }

    $asset_type = pluralize($asset_type);

    #check for '..' ... and kill
    if (strpos($asset_type, '..') !== false) { throw new Exception("Invalid asset type"); return false; }
    if (strpos($asset_name, '..') !== false)  { throw new Exception("Invalid asset name"); return false; }
    if (strpos($face, '..') !== false) { throw new Exception("Invalid face"); return false; }

    #are we linking to an asset in another face or to the current face ?
    if (is_null($face)) { $face = App::$face; }

    $route = App::$env->url.'/'.
      $face.'/assets/'.
      $asset_type.'/'.
      $asset_name
      ;

    return $route;

    #check face etcetc

  }

?>
