<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

class TeamModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var string
     */
    public $typeAlias = 'com_ttclub.team';

    /**
     * Maximum allowed team photo file size in bytes (5 MB).
     */
    private const MAX_PHOTO_SIZE = 5 * 1024 * 1024;

    /**
     * Allowed MIME types for team photos.
     */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
    ];

    /**
     * Allowed file extensions for team photos.
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
            'com_ttclub.team',
            'team',
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
        $data = Factory::getApplication()->getUserState('com_ttclub.edit.team.data', []);

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
    public function getTable($name = 'Team', $prefix = 'Administrator', $options = []): Table
    {
        return parent::getTable($name, $prefix, $options);
    }

    /**
     * Save the team record.
     *
     * Enforces league immutability: if the record already exists, the league_id
     * cannot be changed. This is also enforced at the Table level via check(),
     * but we add model-level enforcement for clarity and early rejection.
     *
     * @param array $data The form data.
     *
     * @return bool True on success, false on failure.
     */
    public function save($data): bool
    {
        // Enforce league immutability on existing records
        if (!empty($data['id'])) {
            $table = $this->getTable();
            $table->load($data['id']);

            if ($table->id && (int) $table->league_id > 0 && (int) $data['league_id'] !== (int) $table->league_id) {
                $this->setError('League changes are not permitted after creation. The league assignment is fixed for the entire season.');
                return false;
            }
        }

        return parent::save($data);
    }

    /**
     * Save a team photo for a specific half-season.
     *
     * Validates the uploaded file (JPEG/PNG, max 5 MB), stores it in
     * media/com_ttclub/images/, and creates or updates the team_photos record.
     *
     * @param int   $teamId       The team ID to associate the photo with.
     * @param int   $halfSeasonId The half-season ID to associate the photo with.
     * @param array $file         The uploaded file array (from $_FILES).
     *
     * @return string|false The stored image path on success, false on failure.
     */
    public function saveTeamPhoto(int $teamId, int $halfSeasonId, array $file): string|false
    {
        // Validate team ID and half-season ID
        if ($teamId <= 0) {
            $this->setError('A valid team ID is required.');
            return false;
        }

        if ($halfSeasonId <= 0) {
            $this->setError('A valid half-season ID is required.');
            return false;
        }

        // Validate file upload
        if (!$this->validatePhotoFile($file)) {
            return false;
        }

        // Generate unique filename and destination path
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = sprintf('team_%d_hs_%d_%s.%s', $teamId, $halfSeasonId, bin2hex(random_bytes(8)), $extension);
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
            $this->setError('Failed to store the uploaded photo.');
            return false;
        }

        // Save or update the team_photos record
        if (!$this->savePhotoRecord($teamId, $halfSeasonId, $relativePath)) {
            // Clean up the uploaded file if DB save fails
            if (file_exists($absolutePath)) {
                unlink($absolutePath);
            }
            return false;
        }

        return $relativePath;
    }

    /**
     * Validate an uploaded photo file.
     *
     * Checks:
     * - File was uploaded without errors
     * - File size does not exceed 5 MB
     * - File is JPEG or PNG (by MIME type and extension)
     *
     * @param array $file The uploaded file array (from $_FILES).
     *
     * @return bool True if valid, false otherwise (error set via setError).
     */
    private function validatePhotoFile(array $file): bool
    {
        // Check for upload errors
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $this->setError('No file was uploaded or an upload error occurred.');
            return false;
        }

        // Check file size (max 5 MB)
        if (!isset($file['size']) || $file['size'] > self::MAX_PHOTO_SIZE) {
            $this->setError('The photo file must not exceed 5 MB.');
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
     * Save or update a team photo record in the database.
     *
     * Uses the unique constraint on (team_id, half_season_id) to determine
     * whether to insert a new record or update an existing one.
     *
     * @param int    $teamId       The team ID.
     * @param int    $halfSeasonId The half-season ID.
     * @param string $imagePath    The relative path to the stored photo.
     *
     * @return bool True on success, false on failure.
     */
    private function savePhotoRecord(int $teamId, int $halfSeasonId, string $imagePath): bool
    {
        $db = $this->getDatabase();

        // Check for existing record (unique per team + half-season)
        $query = $db->getQuery(true)
            ->select([$db->quoteName('id'), $db->quoteName('image_path')])
            ->from($db->quoteName('#__ttclub_team_photos'))
            ->where($db->quoteName('team_id') . ' = :teamId')
            ->where($db->quoteName('half_season_id') . ' = :halfSeasonId')
            ->bind(':teamId', $teamId, \Joomla\Database\ParameterType::INTEGER)
            ->bind(':halfSeasonId', $halfSeasonId, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);
        $existing = $db->loadObject();

        /** @var \Fatherjoe\Component\Ttclub\Administrator\Table\TeamPhotoTable $table */
        $table = $this->getMVCFactory()->createTable('TeamPhoto', 'Administrator');

        if ($existing) {
            // Delete old photo file if it exists and is different
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
            $table->team_id = $teamId;
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
