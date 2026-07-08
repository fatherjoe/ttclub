<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Site\Model;

use Fatherjoe\Component\Ttclub\Administrator\Helper\TtclubHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Site Player detail model.
 *
 * Loads a single player record with visibility enforcement.
 */
class PlayerModel extends BaseDatabaseModel
{
    /**
     * Get a player item by ID with visibility enforcement.
     *
     * @param int|null $pk The primary key of the player to load. Defaults to input 'id'.
     *
     * @return object|null The player object, or null on failure.
     */
    public function getItem(?int $pk = null): ?object
    {
        $app = Factory::getApplication();
        $pk = $pk ?: (int) $app->getInput()->getInt('id', 0);

        if ($pk <= 0) {
            $app->enqueueMessage(Text::_('COM_TTCLUB_ERROR_PLAYER_NOT_FOUND'), 'error');
            return null;
        }

        try {
            $db = $this->getDatabase();
            $query = $db->getQuery(true);

            $query->select('a.*')
                ->from($db->quoteName('#__ttclub_players', 'a'))
                ->where($db->quoteName('a.id') . ' = :pk')
                ->where($db->quoteName('a.published') . ' = 1')
                ->bind(':pk', $pk, \Joomla\Database\ParameterType::INTEGER);

            $db->setQuery($query);
            $item = $db->loadObject();

            if ($item === null) {
                $app->enqueueMessage(Text::_('COM_TTCLUB_ERROR_PLAYER_NOT_FOUND'), 'error');
                return null;
            }

            // Load player image for the current half-season
            $halfSeason = TtclubHelper::getCurrentHalfSeason($db);
            $item->current_half_season = $halfSeason;
            $item->image_path = null;

            if ($halfSeason !== null) {
                $imgQuery = $db->getQuery(true)
                    ->select($db->quoteName('image_path'))
                    ->from($db->quoteName('#__ttclub_player_images'))
                    ->where($db->quoteName('player_id') . ' = :playerId')
                    ->where($db->quoteName('half_season_id') . ' = :hsId')
                    ->bind(':playerId', $pk, \Joomla\Database\ParameterType::INTEGER)
                    ->bind(':hsId', $halfSeason->id, \Joomla\Database\ParameterType::INTEGER);

                $db->setQuery($imgQuery);
                $item->image_path = $db->loadResult();
            }

            return $item;
        } catch (\Exception $e) {
            $app->enqueueMessage(Text::_('COM_TTCLUB_ERROR_PLAYER_DETAILS_UNAVAILABLE'), 'error');
            return null;
        }
    }

    /**
     * Get the list of player fields configured as publicly visible.
     *
     * @return array
     */
    public function getVisibleFields(): array
    {
        $app = Factory::getApplication();
        $params = $app->getParams('com_ttclub');
        $fields = $params->get('player_visible_fields', ['first_name', 'last_name']);

        if (\is_string($fields)) {
            $fields = explode(',', $fields);
        }

        return \is_array($fields) ? array_map('trim', $fields) : [];
    }
}
