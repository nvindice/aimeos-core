<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2012
 * @copyright Aimeos (aimeos.org), 2015-2018
 */


namespace Aimeos\MShop\Index\Manager\Attribute;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;


	protected function setUp()
	{
		$this->context = \TestHelperMShop::getContext();
		$this->object = new \Aimeos\MShop\Index\Manager\Attribute\Standard( $this->context );
	}


	protected function tearDown()
	{
		unset( $this->object );
	}


	public function testCleanup()
	{
		$this->object->cleanup( array( -1 ) );
	}


	public function testAggregate()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'attribute' );

		$search = $manager->createSearch();
		$expr = array(
			$search->compare( '==', 'attribute.code', 'white' ),
			$search->compare( '==', 'attribute.domain', 'product' ),
			$search->compare( '==', 'attribute.type', 'color' ),
		);
		$search->setConditions( $search->combine( '&&', $expr ) );

		$items = $manager->searchItems( $search );

		if( ( $item = reset( $items ) ) === false ) {
			throw new \RuntimeException( 'No attribute item found' );
		}


		$search = $this->object->createSearch( true );
		$result = $this->object->aggregate( $search, 'index.attribute.id' );

		$this->assertEquals( 14, count( $result ) );
		$this->assertArrayHasKey( $item->getId(), $result );
		$this->assertEquals( 3, $result[$item->getId()] );
	}


	public function testGetResourceType()
	{
		$result = $this->object->getResourceType();

		$this->assertContains( 'index/attribute', $result );
	}


	public function testGetSearchAttributes()
	{
		foreach( $this->object->getSearchAttributes() as $attribute ) {
			$this->assertInstanceOf( \Aimeos\MW\Criteria\Attribute\Iface::class, $attribute );
		}
	}


	public function testSaveDeleteItem()
	{
		$productManager = \Aimeos\MShop\Product\Manager\Factory::create( $this->context );
		$search = $productManager->createSearch();
		$search->setConditions( $search->compare( '==', 'product.code', 'CNC' ) );

		$result = $productManager->searchItems( $search, array( 'attribute' ) );

		if( ( $product = reset( $result ) ) === false ) {
			throw new \RuntimeException( 'No product item with code CNE found!' );
		}

		$attributes = $product->getRefItems( 'attribute' );
		if( ( $attrItem = reset( $attributes ) ) === false ) {
			throw new \RuntimeException( 'Product doesnt have any attribute item' );
		}

		$product = $this->object->saveItem( $product->setId( null )->setCode( 'ModifiedCNC' ) );


		$search = $this->object->createSearch();
		$search->setConditions( $search->compare( '==', 'index.attribute.id', $attrItem->getId() ) );
		$result = $this->object->searchItems( $search );


		$this->object->deleteItem( $product->getId() );
		$productManager->deleteItem( $product->getId() );


		$search = $this->object->createSearch();
		$search->setConditions( $search->compare( '==', 'index.attribute.id', $attrItem->getId() ) );
		$result2 = $this->object->searchItems( $search );


		$this->assertContains( $product->getId(), array_keys( $result ) );
		$this->assertFalse( in_array( $product->getId(), array_keys( $result2 ) ) );
	}


	public function testGetSubManager()
	{
		$this->setExpectedException( \Aimeos\MShop\Exception::class );
		$this->object->getSubManager( 'unknown' );
	}


	public function testSearchItems()
	{
		$attributeManager = \Aimeos\MShop\Attribute\Manager\Factory::create( $this->context );
		$id = $attributeManager->findItem( '30', [], 'product', 'length' )->getId();

		$search = $this->object->createSearch();
		$search->setConditions( $search->compare( '==', 'index.attribute.id', $id ) );
		$result = $this->object->searchItems( $search, [] );

		$this->assertEquals( 2, count( $result ) );
	}


	public function testSearchItemsNoId()
	{
		$search = $this->object->createSearch();
		$search->setConditions( $search->compare( '!=', 'index.attribute.id', null ) );
		$result = $this->object->searchItems( $search, [] );

		$this->assertGreaterThanOrEqual( 2, count( $result ) );
	}


	public function testSearchItemsAllof()
	{
		$manager = \Aimeos\MShop\Attribute\Manager\Factory::create( $this->context );

		$attrIds = [
			$manager->findItem( '30', [], 'product', 'length' )->getId(),
			$manager->findItem( '29', [], 'product', 'width' )->getId()
		];

		$search = $this->object->createSearch();

		$func = $search->createFunction( 'index.attribute:allof', [$attrIds] );
		$search->setConditions( $search->compare( '!=', $func, null ) );
		$search->setSortations( array( $search->sort( '+', 'product.code' ) ) );

		$result = $this->object->searchItems( $search, [] );

		$this->assertEquals( 1, count( $result ) );
		$this->assertEquals( 'CNE', reset( $result )->getCode() );
	}


	public function testSearchItemsOneof()
	{
		$manager = \Aimeos\MShop\Attribute\Manager\Factory::create( $this->context );

		$attrIds = [
			$manager->findItem( '30', [], 'product', 'length' )->getId(),
			$manager->findItem( '30', [], 'product', 'width' )->getId()
		];

		$search = $this->object->createSearch();

		$func = $search->createFunction( 'index.attribute:oneof', [$attrIds] );
		$search->setConditions( $search->compare( '!=', $func, null ) );
		$search->setSortations( array( $search->sort( '+', 'product.code' ) ) );

		$result = $this->object->searchItems( $search, [] );

		$this->assertEquals( 2, count( $result ) );
		$this->assertEquals( 'CNE', reset( $result )->getCode() );
	}


	public function testCleanupIndex()
	{
		$this->object->cleanupIndex( '1970-01-01 00:00:00' );
	}

}
