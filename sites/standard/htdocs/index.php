<?php
	//set the error reporting level (must always include E_USER errors)
	error_reporting(E_ALL | E_STRICT);
	
	
	/*****************************************/
	/**     PATHS                           **/
	/*****************************************/
	
	
	//the path to the directory containing the site files (eg. /path/to/phork/sites/lite/)
	if (empty($strSiteDir)) {
		$strSiteDir = dirname(dirname(__FILE__)) . '/';
	}
	
	//the path to the directory containing the site config files (eg. /path/to/phork/sites/lite/config/live/)
	if (empty($strConfigDir)) {
		if (empty($strConfigType)) {
			$strConfigType = 'live';
		}
		$strConfigDir = "{$strSiteDir}config/{$strConfigType}/";
	}
	
	//the path to the directory containing the phork package (eg. /path/to/phork/)
	if (empty($strInstallDir)) {
		$strInstallDir = dirname(dirname($strSiteDir)) . '/';
	}
	
	//add the phork directory to the include path
	set_include_path(implode(PATH_SEPARATOR, array(
		$strInstallDir
	)) . PATH_SEPARATOR . get_include_path());
	
	
	/*****************************************/
	/**     TIMER                           **/
	/*****************************************/
	
	
	//include the timer utility
	require_once('php/utilities/Timer.class.php');
	
	//instantiate the script timer
	$objTimer = new Timer();
	$objTimer->init();
	
	
	/*****************************************/
	/**     BOOTSTRAP                       **/
	/*****************************************/
	
	
	//include the bootstrap class
	require_once("{$strSiteDir}bootstraps/SiteBootstrap.class.php");
	
	//register and run the bootstrap to build the page
	try { 
		$objBootstrap = new SiteBootstrap(array(
			'strInstallDir'		=> $strInstallDir,
			'strSiteDir'		=> $strSiteDir,
			'strConfigDir'		=> $strConfigDir
		));
		unset($strInstallDir, $strSiteDir, $strConfigDir, $strConfigType);
		
		AppRegistry::register('Bootstrap', $objBootstrap);
		$objBootstrap->run();	
	}
		
	//handle core exceptions by showing details or a generic error
	catch (CoreException $objException) {
		if (AppConfig::get('ErrorVerbose')) {
			$objException->handleException();
		} else {
			$objException->flushBuffer();
			if ($objError = AppRegistry::get('Error', false)) {
				$arrErrors = $objError->flushErrors();
			}
			require(AppConfig::get('TemplateDir') . 'system/error.phtml');
		}
	}
	
	//handle any other exceptions with a generic message
	catch (Exception $objException) {
		print 'There was a fatal error';
	}
	
	
	/*****************************************/
	/**     OUTPUT                          **/
	/*****************************************/
	
	
	//explicitly output the page instead of relying on the destructor
	AppDisplay::getInstance()->output();
?>