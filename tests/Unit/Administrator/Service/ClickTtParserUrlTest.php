<?php

declare(strict_types=1);

namespace Tests\Unit\Administrator\Service;

use Fatherjoe\Component\Ttclub\Administrator\Service\ClickTtParser;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Fatherjoe\Component\Ttclub\Administrator\Service\ClickTtParser::parseClickTtUrl
 */
class ClickTtParserUrlTest extends TestCase
{
    private ClickTtParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ClickTtParser();
    }

    public function testParseClubPoolsUrl(): void
    {
        $url = 'https://battv.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/clubPools?club=6658&displayTyp=vorrunde&contestType=Erwachsene&seasonName=2025%2F26';

        $result = $this->parser->parseClickTtUrl($url);

        self::assertSame('battv', $result['federation']);
        self::assertSame(6658, $result['clubId']);
        self::assertSame('clubPools', $result['action']);
        self::assertSame('6658', $result['params']['club']);
        self::assertSame('vorrunde', $result['params']['displayTyp']);
        self::assertSame('Erwachsene', $result['params']['contestType']);
        self::assertSame('2025/26', $result['params']['seasonName']);
    }

    public function testParseClubTeamsUrl(): void
    {
        $url = 'https://battv.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/clubTeams?club=6658&championship=SK+Bz.+KA+25%2F26';

        $result = $this->parser->parseClickTtUrl($url);

        self::assertSame('battv', $result['federation']);
        self::assertSame(6658, $result['clubId']);
        self::assertSame('clubTeams', $result['action']);
        self::assertSame('SK Bz. KA 25/26', $result['params']['championship']);
    }

    public function testParseClubSearchUrl(): void
    {
        $url = 'https://battv.click-tt.de/cgi-bin/WebObjects/ClickTTVBW.woa/wa/clubSearch?federation=BaTTV&searchFor=445';

        $result = $this->parser->parseClickTtUrl($url);

        self::assertSame('battv', $result['federation']);
        self::assertSame(0, $result['clubId']); // No "club" param in search URL
        self::assertSame('clubSearch', $result['action']);
        self::assertSame('BaTTV', $result['params']['federation']);
        self::assertSame('445', $result['params']['searchFor']);
    }

    public function testParseDifferentFederation(): void
    {
        $url = 'https://wttv.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/clubPools?club=12345';

        $result = $this->parser->parseClickTtUrl($url);

        self::assertSame('wttv', $result['federation']);
        self::assertSame(12345, $result['clubId']);
        self::assertSame('clubPools', $result['action']);
    }

    public function testThrowsForEmptyUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL must not be empty');

        $this->parser->parseClickTtUrl('');
    }

    public function testThrowsForNonClickTtDomain(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Not a valid click-tt.de URL');

        $this->parser->parseClickTtUrl('https://example.com/some/page');
    }

    public function testThrowsForInvalidUrlFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->parser->parseClickTtUrl('not a url at all');
    }

    public function testThrowsForMissingAction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not extract action from URL path');

        $this->parser->parseClickTtUrl('https://battv.click-tt.de/some/other/path');
    }

    public function testParseUrlWithoutClubParam(): void
    {
        $url = 'https://battv.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/groupPage?championship=SK+Bz.+KA+25%2F26&group=499592';

        $result = $this->parser->parseClickTtUrl($url);

        self::assertSame('battv', $result['federation']);
        self::assertSame(0, $result['clubId']);
        self::assertSame('groupPage', $result['action']);
        self::assertSame('499592', $result['params']['group']);
    }

    public function testParseUrlTrimsWhitespace(): void
    {
        $url = '  https://battv.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/clubPools?club=6658  ';

        $result = $this->parser->parseClickTtUrl($url);

        self::assertSame('battv', $result['federation']);
        self::assertSame(6658, $result['clubId']);
        self::assertSame('clubPools', $result['action']);
    }
}
