<?php
// No direct access
defined('_JEXEC') or die;

/**
 * Content component helper.
 *
 * @package		j2xml
 * @subpackage	com_j2xml
 * @since		1.6
 */
class J2XMLHelper
{
	public static $extension = 'com_j2xml';

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @return	JObject
	 * @since	1.6
	 */
	public static function getActions()
	{
		$user	= JFactory::getUser();
		$result	= new JObject;

		$assetName = 'com_content';

		$actions = array(
			'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete'
		);

		foreach ($actions as $action) {
			$result->set($action,	$user->authorise($action, $assetName));
		}

		return $result;
	}
}