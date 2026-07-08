<?php

declare(strict_types=1);

/**
 * @var \Fatherjoe\Component\Ttclub\Site\View\Teams\HtmlView $this
 */

use Fatherjoe\Component\Ttclub\Administrator\Helper\TtclubHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$placeholderImage = 'media/com_ttclub/images/placeholder.png';
?>
<div class="com-ttclub-teams">
    <h2><?php echo Text::_('COM_TTCLUB_TEAMS_TITLE'); ?></h2>

    <?php // Season and half-season navigation ?>
    <div class="com-ttclub-teams__navigation">
        <?php if (!empty($this->seasons)) : ?>
            <form method="get" class="com-ttclub-teams__season-form">
                <label for="season_select"><?php echo Text::_('COM_TTCLUB_SEASON'); ?>:</label>
                <select id="season_select" name="season_id" onchange="this.form.submit()">
                    <?php foreach ($this->seasons as $season) : ?>
                        <option value="<?php echo (int) $season->id; ?>"
                            <?php echo ($this->season !== null && (int) $this->season->id === (int) $season->id) ? 'selected' : ''; ?>>
                            <?php echo $this->escape(TtclubHelper::getSeasonDisplayName((int) $season->start_year, $season->label ?? '')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="option" value="com_ttclub">
                <input type="hidden" name="view" value="teams">
                <noscript><button type="submit"><?php echo Text::_('COM_TTCLUB_GO'); ?></button></noscript>
            </form>
        <?php endif; ?>

        <?php if (!empty($this->halfSeasons) && count($this->halfSeasons) > 1) : ?>
            <div class="com-ttclub-teams__half-season-switch" role="navigation" aria-label="<?php echo Text::_('COM_TTCLUB_HALF_SEASON_NAVIGATION'); ?>">
                <?php foreach ($this->halfSeasons as $hs) : ?>
                    <?php
                    $isActive = $this->halfSeason !== null && (int) $this->halfSeason->id === (int) $hs->id;
                    $label = (int) $hs->half === 1 ? Text::_('COM_TTCLUB_FIRST_HALF') : Text::_('COM_TTCLUB_SECOND_HALF');
                    ?>
                    <a href="<?php echo Route::_('index.php?option=com_ttclub&view=teams&half_season_id=' . (int) $hs->id); ?>"
                       class="com-ttclub-teams__half-season-link<?php echo $isActive ? ' active' : ''; ?>"
                       <?php echo $isActive ? 'aria-current="true"' : ''; ?>>
                        <?php echo $label; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($this->items)) : ?>
        <div class="com-ttclub-teams__empty alert alert-info">
            <p><?php echo Text::_('COM_TTCLUB_NO_TEAMS_AVAILABLE'); ?></p>
        </div>
    <?php else : ?>
        <div class="com-ttclub-teams__grid">
            <?php foreach ($this->items as $team) : ?>
                <?php
                $teamPhoto = !empty($team->team_photo)
                    ? Uri::root() . $team->team_photo
                    : Uri::root() . $placeholderImage;
                $teamLink = Route::_('index.php?option=com_ttclub&view=team&id=' . (int) $team->id);
                ?>
                <div class="com-ttclub-teams__item">
                    <a href="<?php echo $teamLink; ?>" class="com-ttclub-teams__item-link">
                        <div class="com-ttclub-teams__item-photo">
                            <img src="<?php echo $this->escape($teamPhoto); ?>"
                                 alt="<?php echo $this->escape(Text::sprintf('COM_TTCLUB_TEAM_PHOTO_ALT', (string) $team->team_number)); ?>"
                                 class="com-ttclub-teams__item-image"
                                 loading="lazy">
                        </div>
                        <div class="com-ttclub-teams__item-info">
                            <h3 class="com-ttclub-teams__item-name">
                                <?php echo $this->escape(Text::sprintf('COM_TTCLUB_TEAM_NAME', (string) $team->team_number)); ?>
                            </h3>
                            <p class="com-ttclub-teams__item-age-class">
                                <?php echo $this->escape($team->age_class_name ?? ''); ?>
                            </p>
                            <p class="com-ttclub-teams__item-league">
                                <?php echo $this->escape($team->league_name ?? ''); ?>
                            </p>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
