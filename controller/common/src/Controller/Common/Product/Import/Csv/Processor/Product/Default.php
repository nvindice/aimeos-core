<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015
 * @package Controller
 * @subpackage Common
 */


/**
 * Product processor for CSV imports
 *
 * @package Controller
 * @subpackage Common
 */
class Controller_Common_Product_Import_Csv_Processor_Product_Default
	extends Controller_Common_Product_Import_Csv_Processor_Base
	implements Controller_Common_Product_Import_Csv_Processor_Iface
{
	private $cache;
	private $listTypes;


	/**
	 * Initializes the object
	 *
	 * @param MShop_Context_Item_Iface $context Context object
	 * @param array $mapping Associative list of field position in CSV as key and domain item key as value
	 * @param Controller_Common_Product_Import_Csv_Processor_Iface $object Decorated processor
	 */
	public function __construct( MShop_Context_Item_Iface $context, array $mapping,
		Controller_Common_Product_Import_Csv_Processor_Iface $object = null )
	{
		parent::__construct( $context, $mapping, $object );

		/** controller/common/product/import/csv/processor/product/listtypes
		 * Names of the product list types that are updated or removed
		 *
		 * Aimeos offers associated items like "bought together" products that
		 * are automatically generated by other job controllers. These relations
		 * shouldn't normally be overwritten or deleted by default during the
		 * import and this confiuration option enables you to specify the list
		 * types that should be updated or removed if not available in the import
		 * file.
		 *
		 * Contrary, if you don't generate any relations automatically in the
		 * shop and want to import those relations too, you can set the option
		 * to null to update all associated items.
		 *
		 * @param array|null List of product list type names or null for all
		 * @since 2015.05
		 * @category Developer
		 * @category User
		 * @see controller/common/product/import/csv/domains
		 * @see controller/common/product/import/csv/processor/attribute/listtypes
		 * @see controller/common/product/import/csv/processor/catalog/listtypes
		 * @see controller/common/product/import/csv/processor/media/listtypes
		 * @see controller/common/product/import/csv/processor/price/listtypes
		 * @see controller/common/product/import/csv/processor/text/listtypes
		 */
		$default = array( 'default', 'suggestion' );
		$key = 'controller/common/product/import/csv/processor/product/listtypes';
		$this->listTypes = $context->getConfig()->get( $key, $default );

		$this->cache = $this->getCache( 'product' );
	}


	/**
	 * Saves the product related data to the storage
	 *
	 * @param MShop_Product_Item_Iface $product Product item with associated items
	 * @param array $data List of CSV fields with position as key and data as value
	 * @return array List of data which hasn't been imported
	 */
	public function process( MShop_Product_Item_Iface $product, array $data )
	{
		$context = $this->getContext();
		$manager = MShop_Factory::createManager( $context, 'product' );
		$listManager = MShop_Factory::createManager( $context, 'product/list' );
		$separator = $context->getConfig()->get( 'controller/common/product/import/csv/separator', "\n" );

		$this->cache->set( $product );

		$manager->begin();

		try
		{
			$pos = 0;
			$map = $this->getMappedChunk( $data );
			$listItems = $this->getListItemPool( $product, $map );

			foreach( $map as $list )
			{
				if( !isset( $list['product.code'] ) || $list['product.code'] === '' || isset( $list['product.list.type'] )
					&& $this->listTypes !== null && !in_array( $list['product.list.type'], (array) $this->listTypes )
				) {
					continue;
				}

				$codes = explode( $separator, $list['product.code'] );
				$type = ( isset( $list['product.list.type'] ) ? $list['product.list.type'] : 'default' );

				foreach( $codes as $code )
				{
					if( ( $prodid = $this->cache->get( $code ) ) === null )
					{
						$msg = 'No product for code "%1$s" available when importing product with code "%2$s"';
						throw new Controller_Jobs_Exception( sprintf( $msg, $code, $product->getCode() ) );
					}

					if( ( $listItem = array_shift( $listItems ) ) === null ) {
						$listItem = $listManager->createItem();
					}

					$list['product.list.typeid'] = $this->getTypeId( 'product/list/type', 'product', $type );
					$list['product.list.parentid'] = $product->getId();
					$list['product.list.refid'] = $prodid;
					$list['product.list.domain'] = 'product';

					$listItem->fromArray( $this->addListItemDefaults( $list, $pos++ ) );
					$listManager->saveItem( $listItem );
				}
			}

			$remaining = $this->getObject()->process( $product, $data );

			$manager->commit();
		}
		catch( Exception $e )
		{
			$manager->rollback();
			throw $e;
		}

		return $remaining;
	}


	/**
	 * Returns the pool of list items that can be reassigned
	 *
	 * @param MShop_Product_Item_Iface $product Product item object
	 * @param array $map List of associative arrays containing the chunked properties
	 * @return array List of list items implementing MShop_Common_Item_List_Iface
	 */
	protected function getListItemPool( MShop_Product_Item_Iface $product, array $map )
	{
		$pos = 0;
		$delete = array();
		$listItems = $product->getListItems( 'product', $this->listTypes );

		foreach( $listItems as $listId => $listItem )
		{
			$refItem = $listItem->getRefItem();

			if( isset( $map[$pos] ) && ( !isset( $map[$pos]['product.code'] )
				|| ( $refItem !== null && $map[$pos]['product.code'] === $refItem->getCode() ) )
			) {
				$pos++;
				continue;
			}

			$listItems[$listId] = null;
			$delete[] = $listId;
			$pos++;
		}

		$listManager = MShop_Factory::createManager( $this->getContext(), 'product/list' );
		$listManager->deleteItems( $delete );

		return $listItems;
	}
}
