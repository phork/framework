<?php
	//the cache type must have a corresponding class
	$arrConfig['Type'] = 'Dbcache';
	
	//the database configuration for the base tier
	$arrConfig['Tiers']['Base']['TierKey'] = 'base';
	$arrConfig['Tiers']['Base']['Database'] = array(
		'Type'	=> 'MySql',
		'Connections' => array(
			'Read' => array(
				'User'			=> 'YOUR_USERNAME',
				'Password'		=> 'YOUR_PASSWORD',
				'Host'			=> 'localhost',
				'Port'			=> 3306,
				'Database'		=> 'phorkit',
				'Persistent'	=> false
			),
			'Write' => array(
				'User'			=> 'YOUR_USERNAME',
				'Password'		=> 'YOUR_PASSWORD',
				'Host'			=> 'localhost',
				'Port'			=> 3306,
				'Database'		=> 'phorkit',
				'Persistent'	=> false
			)
		)
	);
	
	//the database configuration for the presentation tier
	$arrConfig['Tiers']['Presentation']['TierKey'] = 'pres';
	$arrConfig['Tiers']['Presentation']['Database'] =& $arrConfig['Tiers']['Base']['Database'];