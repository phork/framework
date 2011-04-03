<?php
	//the default title for the public pages
	$arrConfig['SiteTitle'] = 'Phork - A PHP Framework';
	
	//whether the sessions should be enabled
	$arrConfig['SessionsEnabled'] = true;
	
	
	/*******************************************/
	/**     URLS                              **/
	/*******************************************/
	
	
	//the site and secure URLs should just contain the schema and the domain
	$arrConfig['SiteUrl'] = 'http://' . $_SERVER['HTTP_HOST'];
	$arrConfig['SecureUrl'] = 'https://' . $_SERVER['HTTP_HOST'];
	
	//the URLs to the assets relative to the document root (the schema and domain are optional) 
	$arrConfig['ImageUrl'] = '';
	$arrConfig['CssUrl'] = '';
	$arrConfig['JsUrl'] = '';
	
	//the URL of the front controller relative to the document root (excluding the filename if using mod rewrite)
	//$arrConfig['BaseUrl'] = '';                   //mod rewrite enabled
	$arrConfig['BaseUrl'] = '/index.php';           //no mod rewrite
	
	//the domain and path to use for cookies
	$arrConfig['CookieDomain'] = $_SERVER['HTTP_HOST'];
	$arrConfig['CookiePath'] = '/';
	
	
	/*******************************************/
	/**     REQUEST VARS                      **/
	/*******************************************/
	
	
	//the names of various session and cookie vars
	$arrConfig['DebugSessionName'] = '_d';
	$arrConfig['TokenSessionName'] = '_t';
	$arrConfig['HistorySessionName'] = '_h';
	$arrConfig['AlertSessionName'] = '_a';
	
	//the name of the form field containing the token used to verify post data
	$arrConfig['TokenField'] = '_t';
	
	
	/*******************************************/
	/**     PAGE CACHE                        **/
	/*******************************************/
	
	
	//define the url patterns for full page caches
	$arrConfig['CacheUrls'] = array(
		'#^/concat/(.*)#'	=> array(
			'Namespace'		=> null,
			'Expire'		=> 300
		)
	);
	
	
	/*******************************************/
	/**     CSS & JS CONCAT                   **/
	/*******************************************/
	
	
	//the CSS and JS versions for cache busting
	$arrConfig['CssVersion'] = 1;
	$arrConfig['JsVersion'] = 1;
	
	//the domains that are trusted for CSS and JS files
	$arrConfig['AssetUrls'] = array(
		$arrConfig['CssUrl'],
		$arrConfig['JsUrl']
	);
	
	//the paths that are trusted for CSS and JS files
	$arrConfig['AssetPaths'] = array(
		AppConfig::get('SiteDir') . 'htdocs/css/',
		AppConfig::get('SiteDir') . 'htdocs/js/',
		AppConfig::get('SiteDir') . 'htdocs/lib/'
	);
	
	//whether to display the raw CSS and JS
	$arrConfig['NoConcat'] = true;
	
	
	/*******************************************/
	/**     ROUTING                           **/
	/*******************************************/
	
	
	//route the css and javascript
	$arrConfig['Routes']['^/concat/(css|js)/([0-9]*)/([^/]*)/output.(css|js)$'] = '/concat/$1/version=$2/files=$3/';