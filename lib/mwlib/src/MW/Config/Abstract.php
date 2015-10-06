<?php

/**
 * @copyright Copyright (c) Metaways Infosystems GmbH, 2011
 * @license LGPLv3, http://www.gnu.org/licenses/lgpl.html
 * @package MW
 * @subpackage Config
 */


/**
 * Common methods for all configuration classes
 *
 * @package MW
 * @subpackage Config
 */
abstract class MW_Config_Abstract implements MW_Config_Interface
{
	private $includeCache = array();


	/**
	* Includes config files using a simple caching.
	*
	* @param string $file Path and file name of a config file
	* @return array Value of the requested config file
	**/
	protected function includeFile( $file )
	{
		if( !isset( $this->includeCache[$file] ) ) {
			$this->includeCache[$file] = include $file;
		}

		return $this->includeCache[$file];
	}
}