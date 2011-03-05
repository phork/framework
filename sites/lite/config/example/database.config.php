<?php
	//the database type must have a corresponding class
	$arrConfig['Type'] = 'MySql';
	
	//the database connection for reads
	$arrConfig['Connections']['Read'] = array(
		'User'			=> 'your_username',
		'Password'		=> 'your_password',
		'Host'			=> 'localhost',
		'Port'			=> 3306,
		'Database'		=> 'phork',
		'Persistent'	=> false
	);
	
	//the database connection for writes
	$arrConfig['Connections']['Write'] = array(
		'User'			=> 'your_username',
		'Password'		=> 'your_password',
		'Host'			=> 'localhost',
		'Port'			=> 3306,
		'Database'		=> 'phork',
		'Persistent'	=> false
	);