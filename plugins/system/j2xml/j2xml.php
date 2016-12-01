<?php
/**
 * @version		3.4.34 plugins/system/j2xml/j2xml.php
 * 
 * @package		J2XML
 * @subpackage	plg_system_j2xml
 * @since		1.5.2
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010, 2016 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License 
 * or other free or open source software licenses.
 */
 
// no direct access
defined('_JEXEC') or die('Restricted access.');

jimport('joomla.plugin.plugin');
jimport('joomla.application.component.helper');
jimport('joomla.filesystem.file');
jimport('eshiol.core.send');
jimport('eshiol.core.standard2');

class plgSystemJ2XML extends JPlugin
{
	var $params = null;
	/**
	 * CONSTRUCTOR
	 * @param object $subject The object to observe
	 * @param object $params  The object that holds the plugin parameters
	 * @since 1.5
	 */
	function __construct(&$subject, $params)
	{
		$this->params = $params;
		parent::__construct($subject, $params);
		JPlugin::loadLanguage('plg_system_j2xml');
	}

	/**
	 * Method is called by index.php and administrator/index.php
	 *
	 * @access	public
	 */
	public function onAfterDispatch()
	{
		$app = JFactory::getApplication();
		if($app->getName() != 'administrator') {
			return true;
		}

		$enabled = JComponentHelper::getComponent('com_j2xml', true);
		if (!$enabled->enabled) 
			return true; 

		$option = JRequest::getVar('option');
		$view = JRequest::getVar('view');
		$extension = JRequest::getVar('extension');
		
		if (class_exists('JPlatform') && version_compare(JPlatform::RELEASE, '12', 'ge'))
		{
			if (($option == 'com_content') && (!$view || $view == 'articles' || $view == 'featured')
				|| ($option == 'com_users') && (!$view || $view == 'users')
				|| ($option == 'com_weblinks') && (!$view || $view == 'weblinks')
				|| ($option == 'com_categories') && ($extension == 'com_content') && (!$view || $view == 'categories')
				|| ($option == 'com_categories') && ($extension == 'com_buttons') && (!$view || $view == 'categories')
			) {			
				$toolbar = JToolBar::getInstance('toolbar');
				$control = substr($option, 4);
				$toolbar->appendButton('Standard2', 'download', 'PLG_SYSTEM_J2XML_BUTTON_EXPORT', "j2xml.{$control}.export", true);
				$doc = JFactory::getDocument();
				//$doc->addStyleDeclaration(" .icon-32-waiting {background:url(../media/lib_eshiol/images/icon-32-waiting.gif) no-repeat; }");
				$doc->addScript("../media/lib_eshiol_core/js/encryption.js");
				$doc->addScript("../media/lib_eshiol_core/js/core.js");
				$websites = self::getWebsites();
				if ($n = count($websites))
				{
					for($i = 0; $i < $n; $i++)
						$websites[$i]->url = "index.php?option=com_j2xml&task={$control}.send&w_id=".$websites[$i]->id;
					$toolbar->appendButton('Send', 'out', 'PLG_SYSTEM_J2XML_BUTTON_SEND', $websites, true);
				}
			}
		}
		else
		{
			if (($option == 'com_content') && (!$view || $view == 'articles' || $view == 'featured')
					|| ($option == 'com_users') && (!$view || $view == 'users')
					|| ($option == 'com_weblinks') && (!$view || $view == 'weblinks')
					|| ($option == 'com_categories') && ($extension == 'com_content') && (!$view || $view == 'categories')
			) {
				$toolbar = JToolBar::getInstance('toolbar');
				$doc = JFactory::getDocument();
				$doc->addStyleDeclaration(".icon-32-j2xml_export{background:url(../media/plg_system_j2xml/images/icon-32-export.png) no-repeat;}");
				$doc->addStyleDeclaration(".icon-32-j2xml_send{background:url(../media/plg_system_j2xml/images/icon-32-send.png) no-repeat;}");			
				$control = substr($option, 4);
				$toolbar->prependButton('Separator', 'divider');
				if (class_exists('JPlatform'))
				{
					$websites = self::getWebsites();
					if (count($websites))
						$toolbar->prependButton('Send', 'j2xml_send', 'PLG_SYSTEM_J2XML_BUTTON_SEND', "j2xml.{$control}.send", 'websites');
				}
				$toolbar->prependButton('Standard2', 'j2xml_export', 'PLG_SYSTEM_J2XML_BUTTON_EXPORT', "j2xml.{$control}.export");
			}
		}
		return true;
	}
	
	/**
	 * Method to return a list of all websites
	 *
	 * @return  array  List of websites (empty array if none).
	 */
	private static function getWebsites()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('id, title')
			->from('#__j2xml_websites')
			->where('state = 1')
			;
		$db->setQuery($query);
		try {
			$websites = $db->loadObjectList();
		} catch (Exception $e) {
			$websites = null;
		}
		return $websites;
	}
}