<?php

declare(strict_types=1);

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \Fatherjoe\Component\Ttclub\Administrator\View\Players\HtmlView $this */

HTMLHelper::_('behavior.multiselect');

$listOrder = $this->escape($this->state?->get('list.ordering'));
$listDirn  = $this->escape($this->state?->get('list.direction'));
$clubIdFilter = $this->state?->get('filter.club_id');

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('table.columns');
?>

<form action="<?php echo Route::_('index.php?option=com_ttclub&view=players'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php if (!empty($this->clubIdOptions)) : ?>
                    <div class="js-stools mb-3">
                        <div class="js-stools-container-filters">
                            <div class="row g-2 align-items-center">
                                <div class="col-auto">
                                    <select name="filter_club_id" class="form-select" onchange="this.form.submit();">
                                        <option value=""><?php echo Text::_('- Filter Club ID -'); ?></option>
                                        <?php foreach ($this->clubIdOptions as $option) : ?>
                                            <option value="<?php echo (int) $option->id; ?>"<?php echo ((int) $clubIdFilter === (int) $option->id) ? ' selected' : ''; ?>>
                                                <?php echo $this->escape($option->label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table" id="playerList">
                        <caption class="visually-hidden">
                            <?php echo Text::_('Players Table'); ?>
                        </caption>
                        <thead>
                            <tr>
                                <td class="w-1 text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </td>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('grid.sort', 'Last Name', 'a.last_name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('grid.sort', 'First Name', 'a.first_name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    Club IDs
                                </th>
                                <th scope="col" class="w-10 text-center">
                                    <?php echo HTMLHelper::_('grid.sort', 'Status', 'a.published', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-5 text-center">
                                    <?php echo HTMLHelper::_('grid.sort', 'ID', 'a.id', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $i => $item) : ?>
                                <tr class="row<?php echo $i % 2; ?>">
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                                    </td>
                                    <th scope="row">
                                        <a href="<?php echo Route::_('index.php?option=com_ttclub&task=player.edit&id=' . (int) $item->id); ?>">
                                            <?php echo $this->escape($item->last_name); ?>
                                        </a>
                                    </th>
                                    <td>
                                        <?php echo $this->escape($item->first_name); ?>
                                    </td>
                                    <td>
                                        <?php echo $this->escape($item->club_labels ?? ''); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'players.'); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo (int) $item->id; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php echo $this->pagination->getListFooter(); ?>
                <?php endif; ?>

                <input type="hidden" name="task" value="">
                <input type="hidden" name="boxchecked" value="0">
                <input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>">
                <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>">
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>
