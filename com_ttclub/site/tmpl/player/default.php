<?php

declare(strict_types=1);

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/**
 * @var \Fatherjoe\Component\Ttclub\Site\View\Player\HtmlView $this
 */

$item = $this->item;
$visibleFields = $this->visibleFields;
$params = $this->params;
$placeholderImage = $params ? $params->get('default_placeholder_image', 'media/com_ttclub/images/placeholder.png') : 'media/com_ttclub/images/placeholder.png';

// Field label mapping
$fieldLabels = [
    'first_name' => Text::_('COM_TTCLUB_FIELD_FIRST_NAME'),
    'last_name'  => Text::_('COM_TTCLUB_FIELD_LAST_NAME'),
];
?>

<div class="com-ttclub-player">
    <?php if ($item === null) : ?>
        <div class="alert alert-warning">
            <?php echo Text::_('COM_TTCLUB_ERROR_PLAYER_DETAILS_UNAVAILABLE'); ?>
        </div>
    <?php else : ?>
        <h2><?php echo htmlspecialchars($item->first_name . ' ' . $item->last_name, ENT_QUOTES, 'UTF-8'); ?></h2>

        <div class="com-ttclub-player__content">
            <div class="com-ttclub-player__image">
                <?php
                $imagePath = !empty($item->image_path) ? $item->image_path : $placeholderImage;
                ?>
                <img src="<?php echo htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8'); ?>"
                     alt="<?php echo htmlspecialchars($item->first_name . ' ' . $item->last_name, ENT_QUOTES, 'UTF-8'); ?>" />
            </div>

            <div class="com-ttclub-player__details">
                <dl>
                    <?php foreach ($visibleFields as $field) : ?>
                        <?php if (isset($item->$field)) : ?>
                            <dt><?php echo htmlspecialchars($fieldLabels[$field] ?? $field, ENT_QUOTES, 'UTF-8'); ?></dt>
                            <dd><?php echo htmlspecialchars((string) $item->$field, ENT_QUOTES, 'UTF-8'); ?></dd>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </dl>
            </div>
        </div>

        <p class="com-ttclub-player__back">
            <a href="<?php echo Route::_('index.php?option=com_ttclub&view=players'); ?>">
                <?php echo Text::_('COM_TTCLUB_BACK_TO_PLAYERS'); ?>
            </a>
        </p>
    <?php endif; ?>
</div>
