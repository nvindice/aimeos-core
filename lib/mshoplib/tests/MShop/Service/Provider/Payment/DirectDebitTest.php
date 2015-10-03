<?php

/**
 * @copyright Copyright (c) Metaways Infosystems GmbH, 2012
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 */


/**
 * Test class for MShop_Service_Provider_Payment_DirectDebit.
 */
class MShop_Service_Provider_Payment_DirectDebitTest extends PHPUnit_Framework_TestCase
{
	private $object;
	private $ordServItem;


	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @access protected
	 */
	protected function setUp()
	{
		$context = TestHelper::getContext();

		$this->ordServItem = MShop_Factory::createManager( $context, 'order/base/service' )->createItem();
		$serviceItem = MShop_Factory::createManager( $context, 'service' )->createItem();
		$serviceItem->setCode( 'test' );

		$this->object = $this->getMockBuilder( 'MShop_Service_Provider_Payment_DirectDebit' )
			->setMethods( array( 'getOrder', 'getOrderBase', 'saveOrder', 'saveOrderBase' ) )
			->setConstructorArgs( array( $context, $serviceItem ) )
			->getMock();
	}


	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 *
	 * @access protected
	 */
	protected function tearDown()
	{
		unset( $this->object );
	}


	public function testGetConfigBE()
	{
		$this->assertEquals( 4, count( $this->object->getConfigBE() ) );
	}


	public function testCheckConfigBE()
	{
		$attributes = array(
			'payment.url-success' => 'http://returnUrl'
		);

		$result = $this->object->checkConfigBE( $attributes );

		$this->assertEquals( 4, count( $result ) );
		$this->assertEquals( null, $result['payment.url-success'] );
	}


	public function testGetConfigFE()
	{
		$orderManager = MShop_Order_Manager_Factory::createManager( TestHelper::getContext() );
		$orderBaseManager = $orderManager->getSubManager( 'base' );
		$search = $orderManager->createSearch();
		$expr = array(
			$search->compare( '==', 'order.type', MShop_Order_Item_Base::TYPE_WEB ),
			$search->compare( '==', 'order.statuspayment', MShop_Order_Item_Base::PAY_AUTHORIZED )
		);
		$search->setConditions( $search->combine( '&&', $expr ) );
		$orderItems = $orderManager->searchItems( $search );

		if( ( $order = reset( $orderItems ) ) === false ) {
			throw new Exception( sprintf( 'No Order found with statuspayment "%1$s" and type "%2$s"', MShop_Order_Item_Base::PAY_AUTHORIZED, MShop_Order_Item_Base::TYPE_WEB ) );
		}

		$basket = $orderBaseManager->load( $order->getBaseId() );

		$config = $this->object->getConfigFE( $basket );

		$this->assertArrayHasKey( 'directdebit.accountowner', $config );
		$this->assertArrayHasKey( 'directdebit.accountno', $config );
		$this->assertArrayHasKey( 'directdebit.bankcode', $config );
		$this->assertArrayHasKey( 'directdebit.bankname', $config );
		$this->assertEquals( 'Our Unittest', $config['directdebit.accountowner']->getDefault() );
	}


	public function testCheckConfigFE()
	{
		$config = array(
			'directdebit.accountowner' => 'test user',
			'directdebit.accountno' => '123456789',
			'directdebit.bankcode' => '1000000',
			'directdebit.bankname' => 'Federal reserve',
		);

		$result = $this->object->checkConfigFE( $config );

		$expected = array(
			'directdebit.accountowner' => null,
			'directdebit.accountno' => null,
			'directdebit.bankcode' => null,
			'directdebit.bankname' => null,
		);

		$this->assertEquals( $expected, $result );
	}


	public function testCheckConfigFEwrongType()
	{
		$config = array(
			'directdebit.accountowner' => 123,
			'directdebit.accountno' => 0.1,
			'directdebit.bankcode' => '1000000',
			'directdebit.bankname' => 'Federal reserve',
		);

		$result = $this->object->checkConfigFE( $config );

		$this->assertArrayHasKey( 'directdebit.accountowner', $result );
		$this->assertArrayHasKey( 'directdebit.accountno', $result );

		$this->assertFalse( $result['directdebit.accountowner'] === null );
		$this->assertFalse( $result['directdebit.accountno'] === null );
		$this->assertTrue( $result['directdebit.bankcode'] === null );
		$this->assertTrue( $result['directdebit.bankname'] === null );
	}


	public function testSetConfigFE()
	{
		$this->object->setConfigFE( $this->ordServItem, array( 'directdebit.accountno' => '123456' ) );

		$attrItem = $this->ordServItem->getAttributeItem( 'directdebit.accountno', 'payment' );
		$this->assertInstanceOf( 'MShop_Order_Item_Base_Service_Attribute_Iface', $attrItem );
		$this->assertEquals( 'XXX456', $attrItem->getValue() );

		$attrItem = $this->ordServItem->getAttributeItem( 'directdebit.accountno', 'payment/hidden' );
		$this->assertInstanceOf( 'MShop_Order_Item_Base_Service_Attribute_Iface', $attrItem );
		$this->assertEquals( '123456', $attrItem->getValue() );
	}


	public function testProcess()
	{
		$manager = MShop_Order_Manager_Factory::createManager( TestHelper::getContext() );
		$order = $manager->createItem();

		$this->object->process( $order );

		$this->assertEquals( MShop_Order_Item_Base::PAY_AUTHORIZED, $order->getPaymentStatus() );
	}


	public function testIsImplemented()
	{
		$this->assertFalse( $this->object->isImplemented( MShop_Service_Provider_Payment_Base::FEAT_QUERY ) );
		$this->assertFalse( $this->object->isImplemented( MShop_Service_Provider_Payment_Base::FEAT_CAPTURE ) );
		$this->assertFalse( $this->object->isImplemented( MShop_Service_Provider_Payment_Base::FEAT_CANCEL ) );
	}
}