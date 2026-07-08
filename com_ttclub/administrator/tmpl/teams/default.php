<?php

declare(strict_types=1);

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \Fatherjoe\Component\Ttclub\Administrator\View\Teams\HtmlView $this */

HTMLHelper::_('behavior.multiselect');

$listOrder = $this->escape($this->state?->get('list.ordering'));
$listDirn  = $this->escape($this->state?->get('list.direction'));
?>

<form action="<?php echo Route::_('index.php?option=com_ttclub&view=teams'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table" id="teamList">
                        <caption class="visually-hidden">
                            <?php echo Text::_('Teams Table'); ?>
                        </caption>
                        <thead>
                            <tr>
                                <td class="w-1 text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </td>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'Team Number', 'a.team_number', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'Age Class', 'age_class_name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'Season', 'season_start_year', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'League', 'league_name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    Championship
                                </th>
                                <th scope="col">
                                    Group
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'Source', 'club_id_source_label', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 text-center">
                                    Roster
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
                                        <a href="<?php echo Route::_('index.php?option=com_ttclub&task=team.edit&id=' . (int) $item->id); ?>">
                                            <?php echo $this->escape($item->team_number); ?>
                                        </a>
                                    </th>
                                    <td>
                                        <?php echo $this->escape($item->age_class_name ?? '—'); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $sy = (int) ($item->season_start_year ?? 0);
                                        echo $sy > 0 ? $this->escape(sprintf('%d/%02d', $sy, ($sy + 1) % 100)) : '—';
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo $this->escape($item->league_name ?? '—'); ?>
                                    </td>
                                    <td>
                                        <?php echo $this->escape($item->championship_id ?? '—'); ?>
                                    </td>
                                    <td>
                                        <?php echo $this->escape($item->group_id ?? '—'); ?>
                                    </td>
                                    <td>
                                        <?php echo $this->escape($item->club_id_source_label ?? ($item->club_id_source === null ? 'Manual' : '—')); ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?php echo Route::_('index.php?option=com_ttclub&view=roster&team_id=' . (int) $item->id); ?>" class="btn btn-sm btn-outline-primary">
                                            <span class="icon-users" aria-hidden="true"></span> Roster
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'teams.'); ?>
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
