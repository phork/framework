<?php
	//the base folder path for the amazon s3 services
	$arrConfig['S3FolderBase'] = 'YOUR_BUCKET_NAME/';
	$arrConfig['S3FolderRoot'] = 's3://' . $arrConfig['S3FolderBase'];
	$arrConfig['S3FilesUrl'] = 'http://YOUR_BUCKET_URL.amazonaws.com/' . AppConfig::get('PublicFilePath');
	
	//access keys for the amazon s3 account
	$arrConfig['S3AccessKey'] = 'YOUR_ACCESS_KEY';
	$arrConfig['S3SecretKey'] = 'YOUR_SECRET_KEY'; 