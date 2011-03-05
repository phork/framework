<?php
	//the cache type must have a corresponding class
	$arrConfig['Type'] = 'Rediska';
	$arrConfig['RediskaBase'] = AppConfig::get('InstallDir') . 'php/ext/rediska';
	
	
	//the cache server(s) for the base tier
	$arrConfig['Tiers']['Base']['Servers'] = array(
		array(
			'host' 			=> 'localhost',
			'port'			=> 6379,
			'db'			=> 0,
			'password'		=> null,
			'alias'			=> null,
			'weight'		=> 1,
			'persistent'	=> false,
			'timeout'		=> null
		)
	);
	
	
	//the cache server(s) for the presentation tier
	$arrConfig['Tiers']['Presentation']['Servers'] = array(
		array(
			'host' 			=> 'localhost',
			'port'			=> 6379,
			'db'			=> 0,
			'password'		=> null,
			'alias'			=> null,
			'weight'		=> 1,
			'persistent'	=> false,
			'timeout'		=> null
		)
	);