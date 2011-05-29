<?php
	//the cache type must have a corresponding class
	$arrConfig['Type'] = 'Filecache';
	$arrConfig['KeyPrefix'] = null;
	
	
	//the cache filesystem for the base tier; the base path is relative to the files dir
	$arrConfig['Tiers']['Base'] = array(
		'FileSystem'	=> 'Local',
		'RootPath'		=> 'app/cache/base/',
		'HashLevel'		=> 10
	);
	
	
	//the cache filesystem for the presentation tier; the base path is relative to the files dir
	$arrConfig['Tiers']['Presentation'] = array(
		'FileSystem'	=> 'Local',
		'RootPath'		=> 'app/cache/presentation/',
		'HashLevel'		=> 10
	);