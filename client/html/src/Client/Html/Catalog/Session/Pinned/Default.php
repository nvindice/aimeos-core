<?php

/**
 * @copyright Copyright (c) Metaways Infosystems GmbH, 2014
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @package Client
 * @subpackage Html
 */


/**
 * Default implementation of catalog session pinned section for HTML clients.
 *
 * @package Client
 * @subpackage Html
 */
class Client_Html_Catalog_Session_Pinned_Default
	extends Client_Html_Common_Client_Factory_Abstract
	implements Client_Html_Common_Client_Factory_Interface
{
	/** client/html/catalog/session/pinned/default/subparts
	 * List of HTML sub-clients rendered within the catalog session pinned section
	 *
	 * The output of the frontend is composed of the code generated by the HTML
	 * clients. Each HTML client can consist of serveral (or none) sub-clients
	 * that are responsible for rendering certain sub-parts of the output. The
	 * sub-clients can contain HTML clients themselves and therefore a
	 * hierarchical tree of HTML clients is composed. Each HTML client creates
	 * the output that is placed inside the container of its parent.
	 *
	 * At first, always the HTML code generated by the parent is printed, then
	 * the HTML code of its sub-clients. The order of the HTML sub-clients
	 * determines the order of the output of these sub-clients inside the parent
	 * container. If the configured list of clients is
	 *
	 *  array( "subclient1", "subclient2" )
	 *
	 * you can easily change the order of the output by reordering the subparts:
	 *
	 *  client/html/<clients>/subparts = array( "subclient1", "subclient2" )
	 *
	 * You can also remove one or more parts if they shouldn't be rendered:
	 *
	 *  client/html/<clients>/subparts = array( "subclient1" )
	 *
	 * As the clients only generates structural HTML, the layout defined via CSS
	 * should support adding, removing or reordering content by a fluid like
	 * design.
	 *
	 * @param array List of sub-client names
	 * @since 2014.03
	 * @category Developer
	 */
	private $subPartPath = 'client/html/catalog/session/pinned/default/subparts';
	private $subPartNames = array();
	private $cache;


	/**
	 * Returns the HTML code for insertion into the body.
	 *
	 * @param string $uid Unique identifier for the output if the content is placed more than once on the same page
	 * @param array &$tags Result array for the list of tags that are associated to the output
	 * @param string|null &$expire Result variable for the expiration date of the output (null for no expiry)
	 * @return string HTML code
	 */
	public function getBody( $uid = '', array &$tags = array(), &$expire = null )
	{
		$context = $this->getContext();
		$session = $context->getSession();

		/** client/html/catalog/session/pinned
		 * All parameters defined for the catalog session pinned subpart
		 *
		 * This returns all settings related to the catalog session pinned subpart.
		 * Please refer to the single settings for details.
		 *
		 * @param array Associative list of name/value settings
		 * @category Developer
		 * @see client/html/catalog/session#pinned
		 */
		$config = $context->getConfig()->get( 'client/html/catalog/session/pinned', array() );
		$key = $this->getParamHash( array(), $uid . ':catalog:session-pinned-body', $config );

		if( ( $html = $session->get( $key ) ) === null )
		{
			$view = $this->setViewParams( $this->getView(), $tags, $expire );

			$output = '';
			foreach( $this->getSubClients() as $subclient ) {
				$output .= $subclient->setView( $view )->getBody( $uid, $tags, $expire );
			}
			$view->pinnedBody = $output;

			/** client/html/catalog/session/pinned/default/template-body
			 * Relative path to the HTML body template of the catalog session pinned client.
			 *
			 * The template file contains the HTML code and processing instructions
			 * to generate the result shown in the body of the frontend. The
			 * configuration string is the path to the template file relative
			 * to the layouts directory (usually in client/html/layouts).
			 *
			 * You can overwrite the template file configuration in extensions and
			 * provide alternative templates. These alternative templates should be
			 * named like the default one but with the string "default" replaced by
			 * an unique name. You may use the name of your project for this. If
			 * you've implemented an alternative client class as well, "default"
			 * should be replaced by the name of the new class.
			 *
			 * @param string Relative path to the template creating code for the HTML page body
			 * @since 2014.03
			 * @category Developer
			 * @see client/html/catalog/session/pinned/default/template-header
			 */
			$tplconf = 'client/html/catalog/session/pinned/default/template-body';
			$default = 'catalog/session/pinned-body-default.html';

			$html = $view->render( $this->getTemplate( $tplconf, $default ) );

			$cached = $session->get( 'aimeos/catalog/session/pinned/cache', array() ) + array( $key => true );
			$session->set( 'aimeos/catalog/session/pinned/cache', $cached );
			$session->set( $key, $html );
		}

		return $html;
	}


	/**
	 * Returns the HTML string for insertion into the header.
	 *
	 * @param string $uid Unique identifier for the output if the content is placed more than once on the same page
	 * @param array &$tags Result array for the list of tags that are associated to the output
	 * @param string|null &$expire Result variable for the expiration date of the output (null for no expiry)
	 * @return string|null String including HTML tags for the header on error
	 */
	public function getHeader( $uid = '', array &$tags = array(), &$expire = null )
	{
		$context = $this->getContext();
		$session = $context->getSession();

		$config = $context->getConfig()->get( 'client/html/catalog/session/pinned', array() );
		$key = $this->getParamHash( array(), $uid . ':catalog:session-pinned-header', $config );

		if( ( $html = $session->get( $key ) ) === null )
		{
			$view = $this->setViewParams( $this->getView(), $tags, $expire );

			$output = '';
			foreach( $this->getSubClients() as $subclient ) {
				$output .= $subclient->setView( $view )->getHeader( $uid, $tags, $expire );
			}
			$view->pinnedHeader = $output;

			/** client/html/catalog/session/pinned/default/template-header
			 * Relative path to the HTML header template of the catalog session pinned client.
			 *
			 * The template file contains the HTML code and processing instructions
			 * to generate the HTML code that is inserted into the HTML page header
			 * of the rendered page in the frontend. The configuration string is the
			 * path to the template file relative to the layouts directory (usually
			 * in client/html/layouts).
			 *
			 * You can overwrite the template file configuration in extensions and
			 * provide alternative templates. These alternative templates should be
			 * named like the default one but with the string "default" replaced by
			 * an unique name. You may use the name of your project for this. If
			 * you've implemented an alternative client class as well, "default"
			 * should be replaced by the name of the new class.
			 *
			 * @param string Relative path to the template creating code for the HTML page head
			 * @since 2014.03
			 * @category Developer
			 * @see client/html/catalog/session/pinned/default/template-body
			 */
			$tplconf = 'client/html/catalog/session/pinned/default/template-header';
			$default = 'catalog/session/pinned-header-default.html';

			$html = $view->render( $this->getTemplate( $tplconf, $default ) );

			$cached = $session->get( 'aimeos/catalog/session/pinned/cache', array() ) + array( $key => true );
			$session->set( 'aimeos/catalog/session/pinned/cache', $cached );
			$session->set( $key, $html );
		}

		return $html;
	}


	/**
	 * Returns the sub-client given by its name.
	 *
	 * @param string $type Name of the client type
	 * @param string|null $name Name of the sub-client (Default if null)
	 * @return Client_Html_Interface Sub-client object
	 */
	public function getSubClient( $type, $name = null )
	{
		/** client/html/catalog/session/pinned/decorators/excludes
		 * Excludes decorators added by the "common" option from the catalog session pinned html client
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to remove a decorator added via
		 * "client/html/common/decorators/default" before they are wrapped
		 * around the html client.
		 *
		 *  client/html/catalog/session/pinned/decorators/excludes = array( 'decorator1' )
		 *
		 * This would remove the decorator named "decorator1" from the list of
		 * common decorators ("Client_Html_Common_Decorator_*") added via
		 * "client/html/common/decorators/default" to the html client.
		 *
		 * @param array List of decorator names
		 * @since 2015.08
		 * @category Developer
		 * @see client/html/common/decorators/default
		 * @see client/html/catalog/session/pinned/decorators/global
		 * @see client/html/catalog/session/pinned/decorators/local
		 */

		/** client/html/catalog/session/pinned/decorators/global
		 * Adds a list of globally available decorators only to the catalog session pinned html client
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to wrap global decorators
		 * ("Client_Html_Common_Decorator_*") around the html client.
		 *
		 *  client/html/catalog/session/pinned/decorators/global = array( 'decorator1' )
		 *
		 * This would add the decorator named "decorator1" defined by
		 * "Client_Html_Common_Decorator_Decorator1" only to the html client.
		 *
		 * @param array List of decorator names
		 * @since 2015.08
		 * @category Developer
		 * @see client/html/common/decorators/default
		 * @see client/html/catalog/session/pinned/decorators/excludes
		 * @see client/html/catalog/session/pinned/decorators/local
		 */

		/** client/html/catalog/session/pinned/decorators/local
		 * Adds a list of local decorators only to the catalog session pinned html client
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to wrap local decorators
		 * ("Client_Html_Catalog_Decorator_*") around the html client.
		 *
		 *  client/html/catalog/session/pinned/decorators/local = array( 'decorator2' )
		 *
		 * This would add the decorator named "decorator2" defined by
		 * "Client_Html_Catalog_Decorator_Decorator2" only to the html client.
		 *
		 * @param array List of decorator names
		 * @since 2015.08
		 * @category Developer
		 * @see client/html/common/decorators/default
		 * @see client/html/catalog/session/pinned/decorators/excludes
		 * @see client/html/catalog/session/pinned/decorators/global
		 */

		return $this->createSubClient( 'catalog/session/pinned/' . $type, $name );
	}


	/**
	 * Processes the input, e.g. store given values.
	 * A view must be available and this method doesn't generate any output
	 * besides setting view variables.
	 */
	public function process()
	{
		$refresh = false;
		$view = $this->getView();
		$context = $this->getContext();
		$session = $context->getSession();
		$pinned = $session->get( 'aimeos/catalog/session/pinned/list', array() );

		switch( $view->param( 'pin_action' ) )
		{
			case 'add':

				foreach( (array) $view->param( 'pin_id', array() ) as $id ) {
					$pinned[$id] = $id;
				}

				/** client/html/catalog/session/pinned/default/maxitems
				 * Maximum number of products displayed in the "pinned" section
				 *
				 * This option limits the number of products that are shown in the
				 * "pinned" section after the users added the product to their list
				 * of pinned products. It must be a positive integer value greater
				 * than 0.
				 *
				 * Note: The higher the value is the more data has to be transfered
				 * to the client each time the user loads a page with the list of
				 * pinned products.
				 *
				 * @param integer Number of products
				 * @since 2014.09
				 * @category User
				 * @category Developer
				 */
				$max = $context->getConfig()->get( 'client/html/catalog/session/pinned/default/maxitems', 50 );

				$pinned = array_slice( $pinned, -$max, $max, true );
				$refresh = true;
				break;

			case 'delete':

				foreach( (array) $view->param( 'pin_id', array() ) as $id ) {
					unset( $pinned[$id] );
				}

				$refresh = true;
				break;
		}


		if( $refresh )
		{
			$session->set( 'aimeos/catalog/session/pinned/list', $pinned );

			foreach( $session->get( 'aimeos/catalog/session/pinned/cache', array() ) as $key => $value ) {
				$session->set( $key, null );
			}
		}

		parent::process();
	}


	/**
	 * Returns the list of sub-client names configured for the client.
	 *
	 * @return array List of HTML client names
	 */
	protected function getSubClientNames()
	{
		return $this->getContext()->getConfig()->get( $this->subPartPath, $this->subPartNames );
	}


	/**
	 * Sets the necessary parameter values in the view.
	 *
	 * @param MW_View_Interface $view The view object which generates the HTML output
	 * @param array &$tags Result array for the list of tags that are associated to the output
	 * @param string|null &$expire Result variable for the expiration date of the output (null for no expiry)
	 * @return MW_View_Interface Modified view object
	 */
	protected function setViewParams( MW_View_Interface $view, array &$tags = array(), &$expire = null )
	{
		if( !isset( $this->cache ) )
		{
			$expire = null;
			$tags = $items = array();
			$context = $this->getContext();
			$config = $context->getConfig();
			$session = $context->getSession();

			$default = array( 'media', 'price', 'text' );
			$domains = $config->get( 'client/html/catalog/domains', $default );

			/** client/html/catalog/session/pinned/domains
			 * A list of domain names whose items should be available in the pinned view template for the product
			 *
			 * The templates rendering product details usually add the images,
			 * prices and texts, etc. associated to the product
			 * item. If you want to display additional or less content, you can
			 * configure your own list of domains (attribute, media, price, product,
			 * text, etc. are domains) whose items are fetched from the storage.
			 * Please keep in mind that the more domains you add to the configuration,
			 * the more time is required for fetching the content!
			 *
			 * From 2014.09 to 2015.03, this setting was available as
			 * client/html/catalog/detail/pinned/domains
			 *
			 * @param array List of domain names
			 * @since 2015.04
			 * @category Developer
			 * @see client/html/catalog/domains
			 * @see client/html/catalog/list/domains
			 * @see client/html/catalog/detail/domains
			 */
			$domains = $config->get( 'client/html/catalog/session/pinned/domains', $domains );

			$pinned = $session->get( 'aimeos/catalog/session/pinned/list', array() );

			$controller = Controller_Frontend_Factory::createController( $context, 'catalog' );
			$result = $controller->getProductItems( $pinned, $domains );

			foreach( array_reverse( $pinned ) as $id )
			{
				if( isset( $result[$id] ) ) {
					$items[$id] = $result[$id];
				}
			}

			$view->pinnedProductItems = $items;
			$view->pinnedParams = $this->getClientParams( $view->param() );

			$this->cache = $view;
		}

		return $this->cache;
	}
}