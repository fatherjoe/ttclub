<?php

declare(strict_types=1);

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/**
 * @var \Fatherjoe\Component\Ttclub\Site\View\Players\HtmlView $this
 */

$items = $this->items;
$currentHalfSeason = $this->currentHalfSeason;
$params = $this->params;
$placeholderImage = $params ? $params->get('default_placeholder_image', 'media/com_ttclub/images/placeholder.png') : 'media/com_ttclub/images/placeholder.png';
?>

<div class="com-ttclub-players">
    <h2><?php echo Text::_('COM_TTCLUB_PLAYERS_TITLE'); ?></h2>

    <?php if ($currentHalfSeason === null) : ?>
        <div class="alert alert-info">
            <?php echo Text::_('COM_TTCLUB_PLAYERS_NO_CURRENT_HALF_SEASON'); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($items)) : ?>
        <div class="alert alert-info">
            <?php echo Text::_('COM_TTCLUB_PLAYERS_NO_PLAYERS_AVAILABLE'); ?>
        </div>
    <?php else : ?>
        <div class="com-ttclub-players__grid" role="list">
            <?php foreach ($items as $item) : ?>
                <div class="com-ttclub-players__item" role="listitem">
                    <a href="<?php echo Route::_('index.php?option=com_ttclub&view=player&id=' . (int) $item->id); ?>">
                        <div class="com-ttclub-players__image">
                            <?php
                            $imagePath = !empty($item->image_path) ? $item->image_path : $placeholderImage;
                            ?>
                            <img src="<?php echo htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8'); ?>"
                                 alt="<?php echo htmlspecialchars($item->first_name . ' ' . $item->last_name, ENT_QUOTES, 'UTF-8'); ?>"
                                 loading="lazy" />
                        </div>
                        <div class="com-ttclub-players__name">
                            <?php echo htmlspecialchars($item->first_name . ' ' . $item->last_name, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
