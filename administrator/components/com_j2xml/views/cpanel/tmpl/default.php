<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_j2xml
 *
 * @version     __DEPLOY_VERSION__
 * @since       1.5.3
 *
 * @author      Helios Ciancio <info (at) eshiol (dot) it>
 * @link        https://www.eshiol.it
 * @copyright   Copyright (C) 2010 - 2022 Helios Ciancio. All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die();

JHTML::_('behavior.tooltip');
jimport('joomla.language.language');

$data = file_get_contents(JPATH_COMPONENT_ADMINISTRATOR . '/j2xml.xml');
$xml = simplexml_load_string($data);

$title = JText::_('Welcome_to_j2xml');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');
$exts = array();
$version = new \JVersion();
$libraries = '/libraries' . ($version->isCompatible('3.9') ? '/eshiol' : '');

$files = array();

if ($version->isCompatible('3.9'))
{
	if (JFolder::exists(JPATH_MANIFESTS . '/libraries/eshiol'))
	{
		$libraries = JFolder::files(JPATH_MANIFESTS . '/libraries/eshiol');
		foreach ($libraries as $library)
		{
			$files[] = JPATH_MANIFESTS . '/libraries/eshiol/' . $library;
		}
	}
}
else
{
	$files[] = JPATH_MANIFESTS . '/libraries/eshiol.xml';
	$files[] = JPATH_MANIFESTS . '/libraries/j2xml.xml';
	$files[] = JPATH_MANIFESTS . '/libraries/j2xmlpro.xml';
	$files[] = JPATH_MANIFESTS . '/libraries/phpxmlrpc.xml';
}

$files[] = JPATH_SITE . '/plugins/system/j2xml/j2xml.xml';
$files[] = JPATH_SITE . '/plugins/content/setimages/setimages.xml';
$files[] = JPATH_SITE . '/plugins/content/j2xml/j2xml.xml';
$files[] = JPATH_SITE . '/plugins/content/j2xmlgi/j2xmlgi.xml';
$files[] = JPATH_SITE . '/plugins/content/j2xmlredirect/j2xmlredirect.xml';

if (JFolder::exists(JPATH_SITE . '/plugins/j2xml'))
{
	$plugins = JFolder::folders(JPATH_SITE . '/plugins/j2xml');
	foreach ($plugins as $plugin)
	{
		$files[] = JPATH_SITE . '/plugins/j2xml/' . $plugin . '/' . $plugin . '.xml';
	}
}
$lang = JFactory::getLanguage();
foreach ($files as $file)
{
	if (JFile::exists($file))
	{
		$xml = JFactory::getXML($file);
		if ($xml)
		{
			if ($xml->getName() == 'extension')
			{
				$extension = pathinfo($file, PATHINFO_FILENAME);
				$attr = $xml->attributes();
				if ($attr['type'] == 'plugin')
				{
					$lang->load('plg_' . $attr['group'] . '_' . $extension);
				}
				else if ($attr['type'] = 'library')
				{
					$lang->load('lib_' . $extension);
				}
				$exts = $exts + array(
						JText::_((string) $xml->name) .
						// .' '.ucfirst($attr['group'])
						' ' . ucfirst($attr['type']) => (string) $xml->version
				);
			}
		}
	}
}
?>
<form action="index.php" method="post" name="adminForm">
<?php if (class_exists('JPlatform') && version_compare(JPlatform::RELEASE, '12', 'ge')) { ?>
<?php if (!empty( $this->sidebar)) : ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
<?php else : ?>
	<div id="j-main-container">
<?php endif;?>
<table>
				<tr>
<?php if (!class_exists('JPlatform') || version_compare(JPlatform::RELEASE, '12', 'lt')): ?>
    	<td width='45%' class='adminform' valign='top'>
						<p><?php JText::_('COM_J2XML_MSG_EXPORT'); ?></p>
						<div id='cpanel'>
<?php
		$link = 'index.php?option=com_content';
		$this->_quickiconButton($link, 'icon-48-article.png', JText::_('COM_J2XML_TOOLBAR_ARTICLE_MANAGER'));

		$link = 'index.php?option=com_j2xml&amp;view=websites';
		$this->_quickiconButton($link, 'icon-48-websites.png', JText::_('COM_J2XML_TOOLBAR_WEBSITE_MANAGER'), '../media/com_j2xml/images/');
		?>
		</div>
						<div class='clr'></div>
					</td>
<?php endif; ?>
		<td valign='top' style='padding: 7px 0 0 5px'>
						<table class='adminlist'>
							<tr>
								<td colspan='3'>
									<p><?php echo JText::_('COM_J2XML_XML_DESCRIPTION')?></p>
								</td>
							</tr>
							<tr>
								<td>
					<?php echo JText::_('Installed_Version'); ?>
				</td>
								<td width='100px'>
					<?php
	$xml = JFactory::getXML(JPATH_COMPONENT . '/j2xml.xml');
	echo $xml->version;
	?>
				</td>
								<td rowspan='<?php echo 3 + count($exts); ?>'
									style="text-align: center; width: 150px"><a
									href='<?php echo JText::_('COM_J2XML_LINK'); ?>'> <img
										src='../media/com_j2xml/images/j2xml.png' width='110'
										height='110' alt='j2xml' title='j2xml' align='middle'
										border='0'>
								</a></td>
							</tr>
							<tr>
								<td colspan="2">
					<?php echo JText::_('Copyright'); ?>
					<a href='https://www.eshiol.it' target='_blank'>
					<?php echo str_replace("(C)", "&copy", $xml->copyright); ?>
					<img src='../media/com_j2xml/images/eshiol.png' alt='eshiol.it'
										title='eshiol.it' border='0'>
								</a>
								</td>
							</tr>
							<tr>
								<td>
					<?php echo JText::_('License'); ?>
				</td>
								<td><a href='http://www.gnu.org/licenses/gpl-3.0.html'
									target='_blank'>GNU/GPL v3</a></td>
							</tr>
			<?php foreach ($exts as $k=>$v): ?>
			<tr>
								<td><?php echo $k; ?></td>
								<td><?php echo $v; ?></td>
							</tr>
			<?php endforeach; ?>
			</table>
					</td>
				</tr>
			</table>
		</div>
<?php } else { ?>
<?php if (!empty( $this->sidebar)) : ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
		<div id="j-main-container" class="span10">
<?php else : ?>
	<div id="j-main-container">
<?php endif;?>
<table>
					<tr>
<?php if (!class_exists('JPlatform') || version_compare(JPlatform::RELEASE, '12', 'lt')): ?>
    	<td width='45%' class='adminform' valign='top'>
							<p><?php echo JText::_('COM_J2XML_MSG_EXPORT'); ?></p>
							<div id='cpanel'>
<?php
		$link = 'index.php?option=com_content';
		$this->_quickiconButton($link, 'icon-48-article.png', JText::_('COM_J2XML_TOOLBAR_ARTICLE_MANAGER'));

		if (class_exists('JPlatform'))
		{
			$link = 'index.php?option=com_j2xml&amp;view=websites';
			$this->_quickiconButton($link, 'icon-48-websites.png', JText::_('COM_J2XML_TOOLBAR_WEBSITE_MANAGER'), '../media/com_j2xml/images/');
		}
		?>
		</div>
							<div class='clr'></div>
						</td>
<?php endif; ?>
		<td valign='top' style='padding: 7px 0 0 5px'>
							<table class='adminlist'>
								<tr>
									<td colspan='3'>
										<p><?php echo JText::_('COM_J2XML_XML_DESCRIPTION')?></p>
									</td>
								</tr>
								<tr>
									<td>
					<?php echo JText::_('Installed_Version'); ?>
				</td>
									<td width='100px'>
					<?php
	$xml = JFactory::getXML(JPATH_COMPONENT . '/j2xml.xml');
	echo $xml->version;
	?>
				</td>
									<td rowspan='<?php echo 3 + count($exts); ?>'
										style="text-align: center; width: 150px"><a
										href='<?php echo JText::_('COM_J2XML_LINK'); ?>'> <img
											src='../media/com_j2xml/images/j2xml.png' width='110'
											height='110' alt='j2xml' title='j2xml' align='middle'
											border='0'>
									</a></td>
								</tr>
								<tr>
									<td colspan="2">
					<?php echo JText::_('Copyright'); ?>
					<a href='https://www.eshiol.it' target='_blank'>
					<?php echo str_replace("(C)", "&copy", $xml->copyright); ?>
					<img src='../media/com_j2xml/images/eshiol.png' alt='eshiol.it'
											title='eshiol.it' border='0'>
									</a>
									</td>
								</tr>
								<tr>
									<td>
					<?php echo JText::_('License'); ?>
				</td>
									<td><a href='http://www.gnu.org/licenses/gpl-3.0.html'
										target='_blank'>GNU/GPL v3</a></td>
								</tr>
			<?php foreach ($exts as $k=>$v): ?>
			<tr>
									<td><?php echo $k; ?></td>
									<td><?php echo $v; ?></td>
								</tr>
			<?php endforeach; ?>
			<?php $title = JText::_('Support_us'); ?>
			<tr>
									<td colspan="3">
										<p><?php echo JText::_('COM_J2XML_MSG_DONATION1'); ?></p>
										<div style="text-align: center;">
											<form action="https://www.paypal.com/cgi-bin/webscr"
												method="post">
												<input type="hidden" name="cmd" value="_donations"> <input
													type="hidden" name="business" value="info@eshiol.it"> <input
													type="hidden" name="lc" value="en_US"> <input type="hidden"
													name="item_name" value="eshiol.it"> <input type="hidden"
													name="currency_code" value="EUR"> <input type="hidden"
													name="bn"
													value="PP-DonationsBF:btn_donateCC_LG.gif:NonHosted"> <input
													type="image"
													src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif"
													border="0" name="submit" alt="PayPal secure payments."> <img
													alt="" border="0"
													src="https://www.paypal.com/en_US/i/scr/pixel.gif"
													width="1" height="1">
											</form>
										</div>
										<p><?php echo JText::_('COM_J2XML_MSG_DONATION2'); ?></p>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</div>
<?php } ?>
	<input type="hidden" name="option" value="com_j2xml" /> <input
				type="hidden" name="view" value="cpanel" /> <input type="hidden"
				name="task" value="" />
	<?php echo JHTML::_('form.token'); ?>






</form>
