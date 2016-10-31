<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
?>

<tr>
        <th>
                <?php echo JText::_('COM_WEBTT_WEBTT_HEADING_DESCRIPTION'); ?>
        </th>
        <th>
                <?php echo JText::_('COM_WEBTT_WEBTT_HEADING_VALUE'); ?>
        </th>
</tr>

<?php
foreach($this->items as $i => $item):
		if ($i >= 0 && $i <= 4) { ?>
        <tr class="row<?php echo $i % 2; ?>">
                <td>
                        <?php echo $item->descr; ?>
                </td>
                <td>
                        <input type="hidden" <?php echo $item->id; ?> />
                        <input <?php echo $item->val; ?> />
                </td>
        </tr>
<?php } endforeach; ?>
