<?php
	require_once('php/core/CoreControllerLite.class.php');
	
	/**
	 * ConcatController.class.php
	 * 
	 * This controller concatenates CSS and JS assets
	 * into a single minified format. This should be
	 * used with the ConcatHelper utility.
	 * 
	 * Copyright 2006-2011, Phork Labs. (http://phorklabs.com)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author Elenor Collings <elenor@phork.org>
	 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
	 * @package phork-standard
	 * @subpackage controllers
	 */
	class ConcatController extends CoreControllerLite {
	
		protected $arrFiles;
		protected $strEmbed;
		protected $strBaseUrl;
		
	
		/**
		 * Sends the headers for the client to cache the
		 * CSS and JS for a year.
		 *
		 * @access protected
		 */
		protected function clientCache() {
			$intExpires = 60 * 60 * 24 * 365;
			
			$objDisplay = AppDisplay::getInstance();
			$objDisplay->appendHeader('Pragma: public');
			$objDisplay->appendHeader('Cache-Control: maxage=' . $intExpires);
			$objDisplay->appendHeader('Expires: ' . gmdate('D, d M Y H:i:s', time() + $intExpires) . ' GMT');
		}
		
	
		/*****************************************/
		/**     DISPLAY METHODS                 **/
		/*****************************************/
		
		
		/**
		 * Displays concatenated CSS files.
		 *
		 * @access public
		 */
		public function displayCss() {
			AppDisplay::getInstance()->appendHeader('Content-Type: text/css');
			$this->clientCache();
			$this->strEmbed = "@import url('%s');";
			$this->baseUrl = AppConfig::get('CssUrl');
			$this->parseUrl(array($this, 'minifyCss'));
		}
		
		
		/**
		 * Displays concatenated Javascript files.
		 *
		 * @access public
		 */
		public function displayJs() {
			AppDisplay::getInstance()->appendHeader('Content-Type: text/javascript');
			$this->clientCache();
			$this->strEmbed = "document.write('<script type=\"text/javascript\" src=\"%s\"></script>');";
			$this->baseUrl = AppConfig::get('JsUrl');
			$this->parseUrl(array($this, 'minifyJs'));
		}
		
		
		/*****************************************/
		/**     MINIFY METHODS                  **/
		/*****************************************/
		
		
		/**
		 * Minifies the CSS and returns the result. First
		 * removes comments, then whitespace, then any extra
		 * extra whitespace, and finally any unnecessary semi
		 * colons.
		 *
		 * @access public
		 * @param string $strContents The CSS to minify
		 * @return string The minified CSS
		 */
		public function minifyCss($strContents) {
			$strContents = preg_replace('#/\*.*?\*/#s', '', $strContents);
			$strContents = preg_replace('/\s*([{}|:;,])\s+/', '$1', $strContents);
			$strContents = preg_replace('/\s+/', ' ', $strContents);
			$strContents = str_replace(';}', '}', $strContents);
			
			return trim($strContents);
		}
		
		
		/**
		 * Minifies the Javascript and returns the result.
		 * Currently this doesn't actually do any minification
		 * but it can be modifed to do so.
		 *
		 * @access public
		 * @param string $strContents The Javascript to minify
		 * @return string The minified Javascript
		 */
		public function minifyJs($strContents) {
			return $strContents;
		}
		
		
		/*****************************************/
		/**     HELPER METHODS                  **/
		/*****************************************/
		
		
		/**
		 * Parses the URLs of the files to concatenate out
		 * of the URL segments and outputs the results. The 
		 * URLs segment is base64 encoded because just using 
		 * urlencode outputs the file but also sends a 404 
		 * header. This reads the files from the URL to be 
		 * 100% sure that the file is public.
		 *
		 * @access protected
		 * @param mixed A callback function to handle processing the data before output
		 */
		protected function parseUrl($mxdCallback = null) {
			$this->arrFiles = array_map(array($this, 'initFile'), explode(',', base64_decode(AppRegistry::get('Url')->getFilter('files'))));
			
			$strContents = '';
			foreach ($this->arrFiles as $strUrl) {
				if ($strUrl) {
					if ($strUrlContents = @file_get_contents($strUrl, false)) {
						$strContents .= $strUrlContents;
					} else {
						$strContents .= sprintf($this->strEmbed, $strUrl);
					}
					$strContents .= "\n";
				}
			}
						
			if ($mxdCallback) {
				$strContents = call_user_func($mxdCallback, $strContents);
			}
				
			$strOutput = "/*\n\t" . implode("\n\t", $this->arrFiles) . "\n*/\n\n" . $strContents;
			AppDisplay::getInstance()->appendString('content', $strOutput);
		}
		
		
		/**
		 * Verifies the file to concat and makes sure it's
		 * in a trusted domain, then returns the full URL of
		 * the file.
		 *
		 * @access protected
		 * @param string $strFile The urlencoded filename to verify
		 */
		protected function initFile($strFile) {
			$strFile = urldecode($strFile);
			if (preg_match_all('|([a-z]+://([^/]*)).*|', $strFile, $arrMatches)) {
				if (!empty($arrMatches[1][0])) {
					if (in_array($arrMatches[1][0], AppConfig::get('AssetUrls'))) {
						$strUrl = $strFile;
					}
				}
			} else {
				if (file_exists($strFilePath = $_SERVER['DOCUMENT_ROOT'] . $strFile)) {
					$strFileDir = dirname(realpath($strFilePath));
					foreach (AppConfig::get('AssetPaths') as $strAssetPath) {
						if (substr($strFileDir, 0, strlen($strAssetPath)) == $strAssetPath) {
							$strUrl = $this->strBaseUrl . substr($strFile, 1);
							break;
						}
					}
				}
			}
			return !empty($strUrl) ? $strUrl : null;
		}
	}