<?php
	//whether the sessions should be enabled
	$arrConfig['SessionsEnabled'] = true;
	
	//the url of the front controller (no trailing slash) excluding the filename if using mod rewrite
	//$arrConfig['BaseUrl'] = '';					//mod rewrite enabled
	$arrConfig['BaseUrl'] = '/index.php';			//no mod rewrite