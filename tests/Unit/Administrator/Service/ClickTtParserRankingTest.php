<?php

declare(strict_types=1);

namespace Tests\Unit\Administrator\Service;

use Fatherjoe\Component\Ttclub\Administrator\Service\ClickTtParser;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Fatherjoe\Component\Ttclub\Administrator\Service\ClickTtParser::parseRankingTable
 * @covers \Fatherjoe\Component\Ttclub\Administrator\Service\ClickTtParser::isCupCompetition
 */
class ClickTtParserRankingTest extends TestCase
{
    private ClickTtParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ClickTtParser();
    }

    public function testParseRankingTableWithValidHtml(): void
    {
        $html = <<<'HTML'
        <html><body>
        <table class="result-set">
            <thead><tr><th>Pl.</th><th>Mannschaft</th><th>Sp.</th><th>G</th><th>U</th><th>V</th><th>Punkte</th></tr></thead>
            <tbody>
                <tr><td>1</td><td>TTV Musterstadt</td><td>14</td><td>12</td><td>1</td><td>1</td><td>25:3</td></tr>
                <tr><td>2</td><td>SV Opponent A</td><td>14</td><td>10</td><td>2</td><td>2</td><td>22:6</td></tr>
                <tr><td>3</td><td>FC Another</td><td>14</td><td>8</td><td>0</td><td>6</td><td>16:12</td></tr>
            </tbody>
        </table>
        </body></html>
        HTML;

        $result = $this->parser->parseRankingTable($html);

        self::assertCount(3, $result);

        self::assertSame(1, $result[0]['position']);
        self::assertSame('TTV Musterstadt', $result[0]['team_name']);
        self::assertSame(14, $result[0]['matches']);
        self::assertSame(12, $result[0]['wins']);
        self::assertSame(1, $result[0]['draws']);
        self::assertSame(1, $result[0]['losses']);
        self::assertSame('25:3', $result[0]['points']);

        self::assertSame(2, $result[1]['position']);
        self::assertSame('SV Opponent A', $result[1]['team_name']);
    }

    public function testParseRankingTableReturnsEmptyForNoTable(): void
    {
        $html = '<html><body><p>No ranking data here.</p></body></html>';

        $result = $this->parser->parseRankingTable($html);

        self::assertSame([], $result);
    }

    public function testParseRankingTableReturnsEmptyForEmptyHtml(): void
    {
        $result = $this->parser->parseRankingTable('');

        self::assertSame([], $result);
    }

    public function testParseRankingTableSkipsNonNumericPositionRows(): void
    {
        $html = <<<'HTML'
        <html><body>
        <table class="result-set">
            <tbody>
                <tr><td>Pl.</td><td>Mannschaft</td><td>Sp.</td><td>G</td><td>U</td><td>V</td><td>Punkte</td></tr>
                <tr><td>1</td><td>Team A</td><td>10</td><td>8</td><td>1</td><td>1</td><td>17:3</td></tr>
                <tr><td>–</td><td>Absteiger</td><td>0</td><td>0</td><td>0</td><td>0</td><td>0:0</td></tr>
            </tbody>
        </table>
        </body></html>
        HTML;

        $result = $this->parser->parseRankingTable($html);

        self::assertCount(1, $result);
        self::assertSame('Team A', $result[0]['team_name']);
    }

    public function testParseRankingTableSkipsRowsWithTooFewColumns(): void
    {
        $html = <<<'HTML'
        <html><body>
        <table class="result-set">
            <tbody>
                <tr><td>1</td><td>Team A</td><td>10</td></tr>
                <tr><td>1</td><td>Team B</td><td>10</td><td>8</td><td>1</td><td>1</td><td>17:3</td></tr>
            </tbody>
        </table>
        </body></html>
        HTML;

        $result = $this->parser->parseRankingTable($html);

        self::assertCount(1, $result);
        self::assertSame('Team B', $result[0]['team_name']);
    }

    public function testIsCupCompetitionReturnsTrueForPokal(): void
    {
        self::assertTrue($this->parser->isCupCompetition('Bezirkspokal Karlsruhe'));
        self::assertTrue($this->parser->isCupCompetition('POKAL 2025'));
        self::assertTrue($this->parser->isCupCompetition('Kreispokal Mannheim'));
    }

    public function testIsCupCompetitionReturnsTrueForCup(): void
    {
        self::assertTrue($this->parser->isCupCompetition('Club Cup 2025'));
    }

    public function testIsCupCompetitionReturnsFalseForLeague(): void
    {
        self::assertFalse($this->parser->isCupCompetition('Kreisliga Staffel 1'));
        self::assertFalse($this->parser->isCupCompetition('Bezirksliga'));
        self::assertFalse($this->parser->isCupCompetition('Verbandsliga'));
    }
}
