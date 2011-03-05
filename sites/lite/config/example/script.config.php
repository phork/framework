<?php
	/*******************************************/
	/**     DATA STORAGE                      **/
	/*******************************************/
	
	
	//whether the database should be enabled
	$arrConfig['DatabaseEnabled'] = true;
	
	//whether caching should be enabled
	$arrConfig['CacheEnabled'] = true;
	
	//the type of filesystem to use with the app-writable files
	$arrConfig['FileSystem'] = 'Local';
	
	//whether the sessions should be enabled
	$arrConfig['SessionsEnabled'] = false;
	
	
	/*******************************************/
	/**     URLS                              **/
	/*******************************************/
	
	
	//the base url should always be empty for scripts
	$arrConfig['BaseUrl'] = '';