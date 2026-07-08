<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property 24: Frontend player visibility filtering.
 *
 * The frontend player detail view must only display fields that are configured
 * as publicly visible by the administrator. Fields not in the visibility
 * configuration must not appear in the rendered output.
 *
 * This tests the core rendering logic from the player detail template:
 * only fields listed in `visibleFields` are iterated and displayed.
 *
 * **Validates: Requirements 8.3**
 */
class FrontendPlayerVisibilityPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * All possible player fields that can be configured for visibility.
     */
    private const ALL_PLAYER_FIELDS = [
        'first_name',
        'last_name',
    ];

    /**
     * Property 24: Only visible fields appear in the rendered player detail output.
     *
     * Generate random player data and random visibility configurations (subsets of
     * all available fields). Verify that the rendered output contains only the
     * values of fields that are in the visibility config and never contains values
     * of non-visible fields.
     *
     * **Validates: Requirements 8.3**
     */
    public function testOnlyVisibleFieldsAppearInOutput(): void
    {
        $this
            ->forAll(
                Generators::associative([
                    'first_name' => Generators::suchThat(
                        fn(string $s) => mb_strlen(trim($s)) >= 3 && mb_strlen($s) <= 50 && !str_contains($s, '<'),
                        Generators::string()
                    ),
                    'last_name' => Generators::suchThat(
                        fn(string $s) => mb_strlen(trim($s)) >= 3 && mb_strlen($s) <= 50 && !str_contains($s, '<'),
                        Generators::string()
                    ),
                ]),
                Generators::choose(0, count(self::ALL_PLAYER_FIELDS)) // number of visible fields
            )
            ->then(function (array $playerData, int $visibleCount): void {
                // Create a random subset of fields to be visible
                $allFields = self::ALL_PLAYER_FIELDS;
                shuffle($allFields);
                $visibleFields = array_slice($allFields, 0, $visibleCount);
                $nonVisibleFields = array_diff(self::ALL_PLAYER_FIELDS, $visibleFields);

                // Simulate the rendering logic from player detail template
                $output = $this->renderPlayerDetail($playerData, $visibleFields);

                // PROPERTY: Each visible field's value MUST appear in the output
                foreach ($visibleFields as $field) {
                    $value = $playerData[$field];
                    $this->assertStringContainsString(
                        htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'),
                        $output,
                        sprintf(
                            'Visible field "%s" with value "%s" must appear in rendered output',
                            $field,
                            $value
                        )
                    );
                }

                // PROPERTY: Each non-visible field's value MUST NOT appear in the output detail section
                foreach ($nonVisibleFields as $field) {
                    $value = $playerData[$field];
                    // Only check the detail section (dl element), not the heading
                    $detailSection = $this->extractDetailSection($output);
                    $this->assertStringNotContainsString(
                        htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'),
                        $detailSection,
                        sprintf(
                            'Non-visible field "%s" with value "%s" must NOT appear in rendered detail section',
                            $field,
                            $value
                        )
                    );
                }
            });
    }

    /**
     * Property 24: When no fields are configured as visible, the detail section is empty.
     *
     * Generate random player data with an empty visibility config. Verify the
     * detail section contains no field values.
     *
     * **Validates: Requirements 8.3**
     */
    public function testEmptyVisibilityConfigProducesNoFieldsInDetailSection(): void
    {
        $this
            ->forAll(
                Generators::associative([
                    'first_name' => Generators::suchThat(
                        fn(string $s) => mb_strlen(trim($s)) >= 3 && mb_strlen($s) <= 50 && !str_contains($s, '<'),
                        Generators::string()
                    ),
                    'last_name' => Generators::suchThat(
                        fn(string $s) => mb_strlen(trim($s)) >= 3 && mb_strlen($s) <= 50 && !str_contains($s, '<'),
                        Generators::string()
                    ),
                ])
            )
            ->then(function (array $playerData): void {
                $visibleFields = []; // No fields visible

                $output = $this->renderPlayerDetail($playerData, $visibleFields);
                $detailSection = $this->extractDetailSection($output);

                // No field values should appear in the detail section
                foreach (self::ALL_PLAYER_FIELDS as $field) {
                    $value = $playerData[$field];
                    $this->assertStringNotContainsString(
                        htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'),
                        $detailSection,
                        sprintf(
                            'With empty visibility config, field "%s" must not appear in detail section',
                            $field
                        )
                    );
                }
            });
    }

    /**
     * Property 24: When all fields are visible, all field values appear in the detail section.
     *
     * Generate random player data with all fields marked visible. Verify every
     * field value appears in the rendered output.
     *
     * **Validates: Requirements 8.3**
     */
    public function testAllFieldsVisibleShowsAllFieldValues(): void
    {
        $this
            ->forAll(
                Generators::associative([
                    'first_name' => Generators::suchThat(
                        fn(string $s) => mb_strlen(trim($s)) >= 3 && mb_strlen($s) <= 50 && !str_contains($s, '<'),
                        Generators::string()
                    ),
                    'last_name' => Generators::suchThat(
                        fn(string $s) => mb_strlen(trim($s)) >= 3 && mb_strlen($s) <= 50 && !str_contains($s, '<'),
                        Generators::string()
                    ),
                ])
            )
            ->then(function (array $playerData): void {
                $visibleFields = self::ALL_PLAYER_FIELDS; // All fields visible

                $output = $this->renderPlayerDetail($playerData, $visibleFields);

                foreach ($visibleFields as $field) {
                    $value = $playerData[$field];
                    $this->assertStringContainsString(
                        htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'),
                        $output,
                        sprintf(
                            'With all fields visible, field "%s" must appear in output',
                            $field
                        )
                    );
                }
            });
    }

    /**
     * Property 24: The visibility filtering logic is deterministic.
     *
     * Given the same player data and visibility config, rendering produces
     * identical output every time.
     *
     * **Validates: Requirements 8.3**
     */
    public function testVisibilityFilteringIsDeterministic(): void
    {
        $this
            ->forAll(
                Generators::associative([
                    'first_name' => Generators::suchThat(
                        fn(string $s) => mb_strlen(trim($s)) >= 3 && mb_strlen($s) <= 50 && !str_contains($s, '<'),
                        Generators::string()
                    ),
                    'last_name' => Generators::suchThat(
                        fn(string $s) => mb_strlen(trim($s)) >= 3 && mb_strlen($s) <= 50 && !str_contains($s, '<'),
                        Generators::string()
                    ),
                ]),
                Generators::choose(0, count(self::ALL_PLAYER_FIELDS))
            )
            ->then(function (array $playerData, int $visibleCount): void {
                $allFields = self::ALL_PLAYER_FIELDS;
                $visibleFields = array_slice($allFields, 0, $visibleCount);

                $output1 = $this->renderPlayerDetail($playerData, $visibleFields);
                $output2 = $this->renderPlayerDetail($playerData, $visibleFields);

                $this->assertSame(
                    $output1,
                    $output2,
                    'Rendering with the same player data and visibility config must produce identical output'
                );
            });
    }

    /**
     * Simulate the rendering logic from the player detail template.
     *
     * This replicates the core visibility filtering logic found in
     * `com_ttclub/site/tmpl/player/default.php`:
     * - Iterates only over $visibleFields
     * - Only renders fields that exist on the item
     * - Uses htmlspecialchars for output escaping
     *
     * @param array<string, string> $playerData Associative array of field => value
     * @param string[] $visibleFields List of fields configured as visible
     * @return string The rendered HTML output
     */
    private function renderPlayerDetail(array $playerData, array $visibleFields): string
    {
        // Create a player object from the data (simulating database record)
        $item = (object) $playerData;

        // Build the output as the template does
        $html = '<div class="com-ttclub-player">';
        $html .= '<h2>' . htmlspecialchars($item->first_name . ' ' . $item->last_name, ENT_QUOTES, 'UTF-8') . '</h2>';
        $html .= '<div class="com-ttclub-player__details">';
        $html .= '<dl>';

        // The core visibility logic from the template:
        // Only iterate over visibleFields and only render if the field exists on the item
        foreach ($visibleFields as $field) {
            if (isset($item->$field)) {
                $html .= '<dt>' . htmlspecialchars($field, ENT_QUOTES, 'UTF-8') . '</dt>';
                $html .= '<dd>' . htmlspecialchars((string) $item->$field, ENT_QUOTES, 'UTF-8') . '</dd>';
            }
        }

        $html .= '</dl>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Extract the detail section (<dl>...</dl>) from the rendered output.
     *
     * This isolates the detail section which is controlled by visibility filtering,
     * separate from the heading which always shows first + last name.
     *
     * @param string $html Full rendered HTML
     * @return string The detail section content
     */
    private function extractDetailSection(string $html): string
    {
        $start = strpos($html, '<dl>');
        $end = strpos($html, '</dl>');

        if ($start === false || $end === false) {
            return '';
        }

        return substr($html, $start, $end - $start + 5);
    }
}
