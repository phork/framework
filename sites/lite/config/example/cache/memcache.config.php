<?php
	//the cache type must have a corresponding class
	$arrConfig['Type'] = 'Memcache';
	
	
	//the cache server(s) for the base tier
	$arrConfig['Tiers']['Base']['Servers'] = array(
		array(
			'Host' 			=> 'localhost',
			'Port'			=> 11211,
			'Persistent'	=> true,
			'Weight'		=> 1,
			'Timeout'		=> 1
		)
	);
	
	//if this is set to MEMCACHE_COMPRESSED then the data will be compressed with zlib
	$arrConfig['Tiers']['Base']['Compressed'] = MEMCACHE_COMPRESSED;
	
	
	//the cache server(s) for the presentation tier
	$arrConfig['Tiers']['Presentation']['Servers'] = array(
		array(
			'Host' 			=> 'localhost',
			'Port'			=> 11212,
			'Persistent'	=> true,
			'Weight'		=> 1,
			'Timeout'		=> 1
		)
	);
	
	//if this is set to MEMCACHE_COMPRESSED then the data will be compressed with zlib
	$arrConfig['Tiers']['Presentation']['Compressed'] = MEMCACHE_COMPRESSED;