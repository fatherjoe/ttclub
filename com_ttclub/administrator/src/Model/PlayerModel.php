<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

class PlayerModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var string
     */
    public $typeAlias = 'com_ttclub.player';

    /**
     * Maximum allowed image file size in bytes (2 MB).
     */
    private const MAX_IMAGE_SIZE = 2 * 1024 * 1024;

    /**
     * Allowed MIME types for player images.
     */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
    ];

    /**
     * Allowed file extensions for player images.
     */
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png'];

    /**
     * Get the form for this model.
     *
     * @param array $data     Data for the form.
     * @param bool  $loadData True if the form is to load its own data.
     *
     * @return Form|false A Form object on success, false on failure.
     */
    public function getForm($data = [], $loadData = true): Form|false
    {
        $form = $this->loadForm(
            'com_ttclub.player',
            'player',
            ['control' => 'jform', 'load_data' => $loadData]
        );

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Load the data for the form.
     *
     * @return array|object The data for the form.
     */
    protected function loadFormData(): array|object
    {
        $data = Factory::getApplication()->getUserState('com_ttclub.edit.player.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Get the table for this model.
     *
     * @param string $name    The table name. Optional.
     * @param string $prefix  The class prefix. Optional.
     * @param array  $options Configuration array for model. Optional.
     *
     * @return Table A Table object.
     */
    public function getTable($name = 'Player', $prefix = 'Administrator', $options = []): Table
    {
        return parent::getTable($name, $prefix, $options);
    }

    /**
     * Save a player image for a specific half-season.
     *
     * Validates the uploaded file (JPEG/PNG, max 2 MB), stores it in
     * media/com_ttclub/images/, and creates or updates the player_images record.
     *
     * @param int    $playerId     The player ID to associate the image with.
     * @param int    $halfSeasonId The half-season ID to associate the image with.
     * @param array  $file         The uploaded file array (from $_FILES).
     *
     * @return string|false The stored image path on success, false on failure.
     */
    public function savePlayerImage(int $playerId, int $halfSeasonId, array $file): string|false
    {
        // Validate player ID and half-season ID
        if ($playerId <= 0) {
            $this->setError('A valid player ID is required.');
            return false;
        }

        if ($halfSeasonId <= 0) {
            $this->setError('A valid half-season ID is required.');
            return false;
        }

        // Validate file upload
        if (!$this->validateImageFile($file)) {
            return false;
        }

        // Generate unique filename and destination path
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = sprintf('player_%d_hs_%d_%s.%s', $playerId, $halfSeasonId, bin2hex(random_bytes(8)), $extension);
        $relativePath = 'media/com_ttclub/images/' . $filename;
        $absolutePath = JPATH_ROOT . '/' . $relativePath;

        // Ensure destination directory exists
        $directory = dirname($absolutePath);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                $this->setError('Failed to create image storage directory.');
                return false;
            }
        }

        // Move uploaded file to destination
        if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
            $this->setError('Failed to store the uploaded image.');
            return false;
        }

        // Save or update the player_images record
        if (!$this->saveImageRecord($playerId, $halfSeasonId, $relativePath)) {
            // Clean up the uploaded file if DB save fails
            if (file_exists($absolutePath)) {
                unlink($absolutePath);
            }
            return false;
        }

        return $relativePath;
    }

    /**
     * Validate an uploaded image file.
     *
     * Checks:
     * - File was uploaded without errors
     * - File size does not exceed 2 MB
     * - File is JPEG or PNG (by MIME type and extension)
     *
     * @param array $file The uploaded file array (from $_FILES).
     *
     * @return bool True if valid, false otherwise (error set via setError).
     */
    private function validateImageFile(array $file): bool
    {
        // Check for upload errors
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $this->setError('No file was uploaded or an upload error occurred.');
            return false;
        }

        // Check file size (max 2 MB)
        if (!isset($file['size']) || $file['size'] > self::MAX_IMAGE_SIZE) {
            $this->setError('The image file must not exceed 2 MB.');
            return false;
        }

        // Check file extension
        $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $this->setError('Only JPEG and PNG image formats are allowed.');
            return false;
        }

        // Check MIME type
        $mimeType = $file['type'] ?? '';
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            $this->setError('Only JPEG and PNG image formats are allowed.');
            return false;
        }

        // Additional MIME check using file content (if tmp_name is available and accessible)
        if (!empty($file['tmp_name']) && is_readable($file['tmp_name'])) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $detectedMime = $finfo->file($file['tmp_name']);

            if (!in_array($detectedMime, self::ALLOWED_MIME_TYPES, true)) {
                $this->setError('Only JPEG and PNG image formats are allowed.');
                return false;
            }
        }

        return true;
    }

    /**
     * Save or update a player image record in the database.
     *
     * Uses the unique constraint on (player_id, half_season_id) to determine
     * whether to insert a new record or update an existing one.
     *
     * @param int    $playerId     The player ID.
     * @param int    $halfSeasonId The half-season ID.
     * @param string $imagePath    The relative path to the stored image.
     *
     * @return bool True on success, false on failure.
     */
    private function saveImageRecord(int $playerId, int $halfSeasonId, string $imagePath): bool
    {
        $db = $this->getDatabase();

        // Check for existing record (unique per player + half-season)
        $query = $db->getQuery(true)
            ->select([$db->quoteName('id'), $db->quoteName('image_path')])
            ->from($db->quoteName('#__ttclub_player_images'))
            ->where($db->quoteName('player_id') . ' = :playerId')
            ->where($db->quoteName('half_season_id') . ' = :halfSeasonId')
            ->bind(':playerId', $playerId, \Joomla\Database\ParameterType::INTEGER)
            ->bind(':halfSeasonId', $halfSeasonId, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);
        $existing = $db->loadObject();

        /** @var PlayerImageTable $table */
        $table = $this->getMVCFactory()->createTable('PlayerImage', 'Administrator');

        if ($existing) {
            // Delete old image file if it exists and is different
            if ($existing->image_path !== $imagePath) {
                $oldFilePath = JPATH_ROOT . '/' . $existing->image_path;
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
            }

            // Update existing record
            $table->load((int) $existing->id);
            $table->image_path = $imagePath;
        } else {
            // Create new record
            $table->player_id = $playerId;
            $table->half_season_id = $halfSeasonId;
            $table->image_path = $imagePath;
            $table->created = Factory::getDate()->toSql();
        }

        if (!$table->check()) {
            $this->setError($table->getError());
            return false;
        }

        if (!$table->store()) {
            $this->setError($table->getError());
            return false;
        }

        return true;
    }
}
