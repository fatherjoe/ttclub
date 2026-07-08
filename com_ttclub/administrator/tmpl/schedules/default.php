<?php

declare(strict_types=1);

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Fatherjoe\Component\Ttclub\Administrator\View\Schedules\HtmlView $this */

HTMLHelper::_('behavior.multiselect');

$listOrder = $this->escape($this->state?->get('list.ordering'));
$listDirn  = $this->escape($this->state?->get('list.direction'));
?>

<form action="<?php echo Route::_('index.php?option=com_ttclub&view=schedules'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table" id="scheduleList">
                        <caption class="visually-hidden">
                            <?php echo Text::_('Schedules Table'); ?>
                        </caption>
                        <thead>
                            <tr>
                                <td class="w-1 text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </td>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'Match Date', 'a.match_date', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo Text::_('Time'); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'Team', 'a.team_id', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'Opponent', 'a.opponent', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo Text::_('Venue'); ?>
                                </th>
                                <th scope="col">
                                    <?php echo Text::_('H/A'); ?>
                                </th>
                                <th scope="col">
                                    <?php echo Text::_('Result'); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'Season', 'a.season_id', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'Status', 'a.published', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-5 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'ID', 'a.id', $listDirn, $listOrder); ?>
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
                                        <a href="<?php echo Route::_('index.php?option=com_ttclub&task=schedule.edit&id=' . (int) $item->id); ?>">
                                            <?php echo $this->escape($item->match_date); ?>
                                        </a>
                                    </th>
                                    <td>
                                        <?php echo $this->escape($item->match_time ?? '—'); ?>
                                    </td>
                                    <td>
                                        <?php echo $this->escape($item->team_number !== null ? 'Team ' . $item->team_number : '—'); ?>
                                    </td>
                                    <td>
                                        <?php echo $this->escape($item->opponent); ?>
                                    </td>
                                    <td>
                                        <?php echo $this->escape($item->venue); ?>
                                    </td>
                                    <td>
                                        <?php echo (int) $item->home_away === 1 ? 'Home' : 'Away'; ?>
                                    </td>
                                    <td>
                                        <?php echo $this->escape($item->result ?? '—'); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $sy = (int) ($item->season_start_year ?? 0);
                                        echo $sy > 0 ? $this->escape(sprintf('%d/%02d', $sy, ($sy + 1) % 100)) : '—';
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'schedules.'); ?>
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
