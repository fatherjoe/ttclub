<?php
/** 
 *------------------------------------------------------------------------------
 * @package       TTC T3 template
 *------------------------------------------------------------------------------
 * @copyright     Copyright (C) 2015 TTC Wöschbach 58 e.V.
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 *------------------------------------------------------------------------------
 */

 
// no direct access
defined('_JEXEC') or die;
?>

<div class="span4 col-md-4">
	<div class="tpl-preview">
		<img src="<?php echo T3_TEMPLATE_URL ?>/template_preview.png" alt="Template Preview"/>
	</div>
</div>
<div class="span8 col-md-8">
	<div class="t3-admin-overview-header">
		<h2>
			<?php echo JText::_('T3_TPL_DESC_1') ?>
			<small><?php echo JText::_('T3_TPL_DESC_2') ?></small>
		</h2>
		<p><?php echo JText::_('T3_TPL_DESC_3') ?></p>
	</div>
</div>