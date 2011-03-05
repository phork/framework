<?php
	//the cache type must have a corresponding class
	$arrConfig['Type'] = 'Dbcache';
	
	//the database configuration for the base tier
	$arrConfig['Tiers']['Base']['TierKey'] = 'base';
	$arrConfig['Tiers']['Base']['Database'] = array(
		'Type'	=> 'MySql',
		'Connections' => array(
			'Read' => array(
				'User'			=> 'your_username',
				'Password'		=> 'your_password',
				'Host'			=> 'localhost',
				'Port'			=> 3306,
				'Database'		=> 'phork',
				'Persistent'	=> false
			),
			'Write' => array(
				'User'			=> 'your_username',
				'Password'		=> 'your_password',
				'Host'			=> 'localhost',
				'Port'			=> 3306,
				'Database'		=> 'phork',
				'Persistent'	=> false
			)
		)
	);
	
	//the database configuration for the presentation tier
	$arrConfig['Tiers']['Presentation']['TierKey'] = 'pres';
	$arrConfig['Tiers']['Presentation']['Database'] = array(
		'Type'	=> 'MySql',
		'Connections' => array(
			'Read' => array(
				'User'			=> 'your_username',
				'Password'		=> 'your_password',
				'Host'			=> 'localhost',
				'Port'			=> 3306,
				'Database'		=> 'phork',
				'Persistent'	=> false
			),
			'Write' => array(
				'User'			=> 'your_username',
				'Password'		=> 'your_password',
				'Host'			=> 'localhost',
				'Port'			=> 3306,
				'Database'		=> 'phork',
				'Persistent'	=> false
			)
		)
	);