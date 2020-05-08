<?php
/**
 * @version		2.5.85 views/vebsite/tmpl/edit.php
 * 
 * @package		J2XML
 * @subpackage	com_j2xml
 * @since		2.5.85
 * 
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010-2013 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
$canDo	= J2XMLHelper::getActions();
?>
<script type="text/javascript">
	Joomla.submitbutton = function(task)
	{
		if (task == 'website.cancel' || document.formvalidator.isValid(document.id('website-form'))) {
			Joomla.submitform(task, document.getElementById('website-form'));
		}
	}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_j2xml&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="website-form" class="form-validate">

<div class="width-60 fltlft">
	<fieldset class="adminform">
		<legend><?php echo empty($this->item->id) ? JText::_('COM_J2XML_NEW_WEBSITE') : JText::sprintf('COM_J2XML_EDIT_WEBSITE', $this->item->id); ?></legend>
		<ul class="adminformlist">
				<li><?php echo $this->form->getLabel('title'); ?>
				<?php echo $this->form->getInput('title'); ?></li>

				<li><?php echo $this->form->getLabel('remote_url'); ?>
				<?php echo $this->form->getInput('remote_url'); ?></li>

				<li><?php echo $this->form->getLabel('username'); ?>
				<?php echo $this->form->getInput('username'); ?></li>

				<li><?php echo $this->form->getLabel('password'); ?>
				<?php echo $this->form->getInput('password'); ?></li>

				<?php if ($canDo->get('core.edit.state')) : ?>
					<li><?php echo $this->form->getLabel('state'); ?>
					<?php echo $this->form->getInput('state'); ?></li>
				<?php endif; ?>

				<li><?php echo $this->form->getLabel('id'); ?>
				<?php echo $this->form->getInput('id'); ?></li>
		</ul>

	</fieldset>
</div>

	<input type="hidden" name="task" value="" />
	<?php echo JHtml::_('form.token'); ?>
</div>

<div class="clr"></div>
</form>
