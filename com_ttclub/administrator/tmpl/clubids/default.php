<?php

declare(strict_types=1);

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Fatherjoe\Component\Ttclub\Administrator\View\Clubids\HtmlView $this */

HTMLHelper::_('behavior.multiselect');

$listOrder = $this->escape($this->state?->get('list.ordering', 'a.ordering') ?? 'a.ordering');
$listDirn = $this->escape($this->state?->get('list.direction', 'ASC') ?? 'ASC');
?>

<form action="<?php echo Route::_('index.php?option=com_ttclub&view=clubids'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table" id="clubIdList">
                        <caption class="visually-hidden">
                            <?php echo Text::_('COM_TTCLUB_CLUBIDS_TABLE_CAPTION'); ?>
                        </caption>
                        <thead>
                            <tr>
                                <td class="w-1 text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </td>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_TTCLUB_FIELD_CLUBID_LABEL_LABEL', 'a.label', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_TTCLUB_FIELD_CLUBID_CLICK_TT_CLUB_ID_LABEL', 'a.click_tt_club_id', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 text-center">
                                    <?php echo Text::_('COM_TTCLUB_FIELD_CLUBID_LEGACY_CLUB_ID_LABEL'); ?>
                                </th>
                                <th scope="col">
                                    <?php echo Text::_('COM_TTCLUB_FIELD_CLUBID_CLUB_NAME_LABEL'); ?>
                                </th>
                                <th scope="col" class="w-10 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_TTCLUB_FIELD_CLUBID_FEDERATION_LABEL', 'a.federation', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-5 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ORDERING', 'a.ordering', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-5 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $i => $item) : ?>
                                <tr class="row<?php echo $i % 2; ?>">
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo Route::_('index.php?option=com_ttclub&task=clubid.edit&id=' . (int) $item->id); ?>">
                                            <?php echo $this->escape($item->label); ?>
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <?php echo (int) $item->click_tt_club_id; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo $item->legacy_club_id ? (int) $item->legacy_club_id : '—'; ?>
                                    </td>
                                    <td>
                                        <?php echo $this->escape($item->club_name); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo $this->escape($item->federation); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo (int) $item->ordering; ?>
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
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>
