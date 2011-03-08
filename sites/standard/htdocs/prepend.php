<?php
	//called whenever a non-existant class is invoked
	function __autoload($strClassName) {
		if (class_exists('AppLoader', false)) {
			if (substr($strClassName, 0, 4) == 'Core') {
				AppLoader::includeClass('php/core/', $strClassName);
			} else if (substr($strClassName, 0, 3) == 'App') {
				AppLoader::includeClass('php/app/', $strClassName);
			} else if (substr($strClassName, -5) == 'Model') {
				AppLoader::includeModel($strClassName);
			} else if (substr($strClassName, -6) == 'Record') {
				AppLoader::includeModel($strClassName);
			} else if (substr($strClassName, -10) == 'Controller') {
				AppLoader::includeController($strClassName);
			} else if (substr($strClassName, -3) == 'Api') {
				AppLoader::includeApi($strClassName);
			} else if (substr($strClassName, -5) == 'Hooks') {
				AppLoader::includeHooks($strClassName);
			}
		}
	}
	if (function_exists('spl_autoload_register')) {
		spl_autoload_register('__autoload');
	}
?>