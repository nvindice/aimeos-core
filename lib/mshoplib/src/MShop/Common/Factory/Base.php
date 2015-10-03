<?php

/**
 * @copyright Copyright (c) Metaways Infosystems GmbH, 2011
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @package MShop
 * @subpackage Common
 */


/**
 * Common methods for all factories.
 *
 * @package MShop
 * @subpackage Common
 */
abstract class MShop_Common_Factory_Base
{
	private static $objects = array();


	/**
	 * Injects a manager object.
	 * The object is returned via createManager() if an instance of the class
	 * with the name name is requested.
	 *
	 * @param string $classname Full name of the class for which the object should be returned
	 * @param MShop_Common_Manager_Iface|null $manager Manager object or null for removing the manager object
	 */
	public static function injectManager( $classname, MShop_Common_Manager_Iface $manager = null )
	{
		self::$objects[$classname] = $manager;
	}


	/**
	 * Adds the decorators to the manager object.
	 *
	 * @param MShop_Context_Item_Iface $context Context instance with necessary objects
	 * @param MShop_Common_Manager_Iface $manager Manager object
	 * @param string $classprefix Decorator class prefix, e.g. "MShop_Product_Manager_Decorator_"
	 * @return MShop_Common_Manager_Iface Manager object
	 */
	protected static function addDecorators( MShop_Context_Item_Iface $context,
		MShop_Common_Manager_Iface $manager, array $decorators, $classprefix )
	{
		$iface = 'MShop_Common_Manager_Decorator_Iface';

		foreach( $decorators as $name )
		{
			if( ctype_alnum( $name ) === false ) {
				throw new MShop_Exception( sprintf( 'Invalid characters in class name "%1$s"', $name ) );
			}

			$classname = $classprefix . $name;

			if( class_exists( $classname ) === false ) {
				throw new MShop_Exception( sprintf( 'Class "%1$s" not available', $classname ) );
			}

			$manager = new $classname( $context, $manager );

			if( !( $manager instanceof $iface ) ) {
				throw new MShop_Exception( sprintf( 'Class "%1$s" does not implement interface "%2$s"', $classname, $iface ) );
			}
		}

		return $manager;
	}


	/**
	 * Adds the decorators to the manager object.
	 *
	 * @param MShop_Context_Item_Iface $context Context instance with necessary objects
	 * @param MShop_Common_Manager_Iface $manager Manager object
	 * @param string $domain Domain name in lower case, e.g. "product"
	 * @return MShop_Common_Manager_Iface Manager object
	 */
	protected static function addManagerDecorators( MShop_Context_Item_Iface $context,
		MShop_Common_Manager_Iface $manager, $domain )
	{
		$config = $context->getConfig();

		/** mshop/common/manager/decorators/default
		 * Configures the list of decorators applied to all shop managers
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to configure a list of decorator names that should
		 * be wrapped around the original instances of all created managers:
		 *
		 *  mshop/common/manager/decorators/default = array( 'decorator1', 'decorator2' )
		 *
		 * This would wrap the decorators named "decorator1" and "decorator2" around
		 * all controller instances in that order. The decorator classes would be
		 * "MShop_Common_Manager_Decorator_Decorator1" and
		 * "MShop_Common_Manager_Decorator_Decorator2".
		 *
		 * @param array List of decorator names
		 * @since 2014.03
		 * @category Developer
		 */
		$decorators = $config->get( 'mshop/common/manager/decorators/default', array() );
		$excludes = $config->get( 'mshop/' . $domain . '/manager/decorators/excludes', array() );

		foreach( $decorators as $key => $name )
		{
			if( in_array( $name, $excludes ) ) {
				unset( $decorators[$key] );
			}
		}

		$classprefix = 'MShop_Common_Manager_Decorator_';
		$manager = self::addDecorators( $context, $manager, $decorators, $classprefix );

		$classprefix = 'MShop_Common_Manager_Decorator_';
		$decorators = $config->get( 'mshop/' . $domain . '/manager/decorators/global', array() );
		$manager = self::addDecorators( $context, $manager, $decorators, $classprefix );

		$classprefix = 'MShop_' . ucfirst( $domain ) . '_Manager_Decorator_';
		$decorators = $config->get( 'mshop/' . $domain . '/manager/decorators/local', array() );
		$manager = self::addDecorators( $context, $manager, $decorators, $classprefix );

		return $manager;
	}


	/**
	 * Creates a manager object.
	 *
	 * @param MShop_Context_Item_Iface $context Context instance with necessary objects
	 * @param string $classname Name of the manager class
	 * @param string $interface Name of the manager interface
	 * @return MShop_Common_Manager_Iface Manager object
	 */
	protected static function createManagerBase( MShop_Context_Item_Iface $context, $classname, $interface )
	{
		if( isset( self::$objects[$classname] ) ) {
			return self::$objects[$classname];
		}

		if( class_exists( $classname ) === false ) {
			throw new MShop_Exception( sprintf( 'Class "%1$s" not available', $classname ) );
		}

		$manager = new $classname( $context );

		if( !( $manager instanceof $interface ) ) {
			throw new MShop_Exception( sprintf( 'Class "%1$s" does not implement interface "%2$s"', $classname, $interface ) );
		}

		return $manager;
	}
}
