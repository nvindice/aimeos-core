<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2011
 * @copyright Aimeos (aimeos.org), 2015-2018
 */


namespace Aimeos\MShop\Order\Manager;


/**
 * Test class for \Aimeos\MShop\Order\Manager\Factory.
 */
class FactoryTest extends \PHPUnit\Framework\TestCase
{
	public function testCreateManager()
	{
		$manager = \Aimeos\MShop\Order\Manager\Factory::create( \TestHelperMShop::getContext() );
		$this->assertInstanceOf( \Aimeos\MShop\Common\Manager\Iface::class, $manager );
	}


	public function testCreateManagerName()
	{
		$manager = \Aimeos\MShop\Order\Manager\Factory::create( \TestHelperMShop::getContext(), 'Standard' );
		$this->assertInstanceOf( \Aimeos\MShop\Common\Manager\Iface::class, $manager );
	}


	public function testCreateManagerInvalidName()
	{
		$this->setExpectedException( \Aimeos\MShop\Order\Exception::class );
		\Aimeos\MShop\Order\Manager\Factory::create( \TestHelperMShop::getContext(), '%^&' );
	}


	public function testCreateManagerNotExisting()
	{
		$this->setExpectedException( \Aimeos\MShop\Exception::class );
		\Aimeos\MShop\Order\Manager\Factory::create( \TestHelperMShop::getContext(), 'test' );
	}
}
