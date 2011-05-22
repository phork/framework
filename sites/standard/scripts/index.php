<?php
	//set the error reporting level (must always include E_USER errors)
	error_reporting(E_ALL | E_STRICT);

	//make sure the correct arguments were passed
	if ($GLOBALS['argc'] < 4) {
		die(
			'Usage: php /path/to/scripts/index.php configtype controller method [arg1] [arg2] ... [argn]' . "\n" .
			'Example: php ' . __FILE__ . ' live script foo' . "\n"
		);
	}
	
	
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
			$strConfigType = $GLOBALS['argv'][1];
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
	/**     BOOTSTRAP                       **/
	/*****************************************/
	
	
	//include the bootstrap class
	require_once("{$strSiteDir}bootstraps/ScriptBootstrap.class.php");
	
	//register and run the bootstrap to build the page
	try { 
		$objBootstrap = new ScriptBootstrap(array(
			'strInstallDir'		=> $strInstallDir,
			'strSiteDir'		=> $strSiteDir,
			'strConfigDir'		=> $strConfigDir
		));
		unset($strInstallDir, $strSiteDir, $strConfigDir, $strConfigType);
		
		AppRegistry::register('Bootstrap', $objBootstrap);
		$objBootstrap->run();	
	}
		
	//handle core exceptions by showing details
	catch (CoreException $objException) {
		$objException->handleException();
	} 
		
	//display any other errors
	if ($objError = AppRegistry::get('Error', false)) {
		if ($arrErrors = $objError->flushErrors()) {
			print_r($arrErrors);
		}
	}
	
	
	/*****************************************/
	/**     OUTPUT                          **/
	/*****************************************/
	
	
	//explicitly output the page instead of relying on the destructor (recommended)
	AppDisplay::getInstance()->output();
?>