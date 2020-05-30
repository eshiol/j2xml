<?php
/**
 * @package		J2XML
 * @subpackage	com_j2xml
 *
 * @author		Helios Ciancio <info (at) eshiol (dot) it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010 - 2020 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access.
defined('_JEXEC') or die();

JFormHelper::loadFieldClass('list');

/**
 * Authentication type Field class for the Joomla Framework.
 *
 * @version __DEPLOY_VERSION__
 * @since 3.6.158
 */
class JFormFieldAuthType extends JFormFieldList
{

	/**
	 * The form field type.
	 *
	 * @var string
	 * @since 3.6.158
	 */
	protected $type = 'AuthType';

	/**
	 * Method to get the field options.
	 *
	 * @return array The field option objects.
	 *
	 * @since 3.6.158
	 */
	public function getOptions ()
	{
		$options = array();
		$options[] = (object) array (
			'value' => 0,
			'text' => JText::_('COM_J2XML_FIELD_AUTH_TYPE_USERNAMEPASSWORD')
		);
		$params = JComponentHelper::getParams('com_j2xml');
		if ($params->get('oauth2', 0) == 1)
		{
			$options[] = (object) array (
					'value' => 1,
					'text' => JText::_('COM_J2XML_FIELD_AUTH_TYPE_OAUTH2')
			);
		}
		return $options;
	}
}
