<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IntegrationYoutube\Tests\Unit\Service;

use Exception;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use OCA\IntegrationYoutube\Service\YoutubeAPIService;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IL10N;
use OCP\Security\ICrypto;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class YoutubeAPIServiceTest extends TestCase {
	// made up, and of no word or number that other text in a test would carry, since any four characters of it count
	private const API_KEY = 'AIzaQx7Vb2Nw9Kp4Zr6Ty1Lm8Hd3Gf5Js0WcUeY';

	/** @var list<array{url: string, options: array}> */
	private array $requests = [];
	/** @var list<array{level: string, message: string, context: array}> */
	private array $logged = [];
	/** @var callable(string, array): IResponse */
	private $answer;
	private YoutubeAPIService $service;
	private string $storedKey = self::API_KEY;

	protected function setUp(): void {
		parent::setUp();
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getAppValueString')->willReturn('encrypted-api-key');
		$crypto = $this->createMock(ICrypto::class);
		$crypto->method('decrypt')->willReturnCallback(fn (string $ciphertext): string => $ciphertext === 'encrypted-api-key' ? $this->storedKey : throw new Exception('unexpected ciphertext'));
		$l10n = $this->createMock(IL10N::class);
		// with its parameters filled in, as a key handed to a translation would otherwise go unseen
		$l10n->method('t')->willReturnCallback(fn (string $text, array|string $parameters = []): string => vsprintf($text, (array)$parameters));

		$logger = $this->createMock(LoggerInterface::class);
		foreach (['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'] as $level) {
			$logger->method($level)->willReturnCallback(function (string|\Stringable $message, array $context = []) use ($level): void {
				$this->logged[] = ['level' => $level, 'message' => (string)$message, 'context' => $context];
			});
		}
		$logger->method('log')->willReturnCallback(function (mixed $level, string|\Stringable $message, array $context = []): void {
			$this->logged[] = ['level' => (string)$level, 'message' => (string)$message, 'context' => $context];
		});

		$client = $this->createMock(IClient::class);
		$client->method('get')->willReturnCallback(function (string $url, array $options): IResponse {
			$this->requests[] = ['url' => $url, 'options' => $options];
			return ($this->answer)($url, $options);
		});
		$clientService = $this->createMock(IClientService::class);
		$clientService->method('newClient')->willReturn($client);

		$this->service = new YoutubeAPIService($appConfig, $logger, $l10n, $crypto, $clientService);
	}

	private function respond(string $body, int $status = 200): IResponse {
		$response = $this->createMock(IResponse::class);
		$response->method('getStatusCode')->willReturn($status);
		$response->method('getBody')->willReturn($body);
		return $response;
	}

	public function testTheKeyIsSentInAHeaderAndNotInTheUrl(): void {
		$this->answer = fn (): IResponse => $this->respond((string)json_encode([
			'nextPageToken' => 'next',
			'items' => [[
				'id' => ['videoId' => 'dQw4w9WgXcQ'],
				'snippet' => [
					'title' => 'A video',
					'description' => 'About it',
					'thumbnails' => ['default' => ['url' => 'https://i.ytimg.com/vi/dQw4w9WgXcQ/default.jpg']],
					'channelTitle' => 'A channel',
					'publishedAt' => '2026-01-01T00:00:00Z',
				],
			]],
		]));

		$result = $this->service->search('nextcloud', 'video', 1);

		$this->assertCount(1, $result['results']);
		$this->assertSame('dQw4w9WgXcQ', $result['results'][0]->id);
		$this->assertCount(1, $this->requests);
		$this->assertStringStartsWith('https://www.googleapis.com/youtube/v3/search?', $this->requests[0]['url']);
		$this->assertStringNotContainsString(self::API_KEY, $this->requests[0]['url']);
		$this->assertStringNotContainsString('key=', $this->requests[0]['url']);
		$this->assertSame(self::API_KEY, $this->requests[0]['options']['headers']['X-Goog-Api-Key'] ?? null);
		$otherOptions = $this->requests[0]['options'];
		unset($otherOptions['headers']['X-Goog-Api-Key']);
		$this->assertKeyNowhere($otherOptions, 'the request options other than its header');
		$this->assertFalse($this->requests[0]['options']['allow_redirects'] ?? true, 'the header is not sent on to where a redirect points');
		$this->assertSame([], $this->logged, 'a successful search logs nothing');
	}

	/**
	 * @param array<string, mixed> $snippet
	 */
	private static function item(string $type, ?string $id, array $snippet = []): array {
		return [
			'id' => $id === null ? [] : [$type . 'Id' => $id],
			'snippet' => $snippet + [
				'title' => 'A ' . $type,
				'description' => 'About it',
				'thumbnails' => ['default' => ['url' => 'https://i.ytimg.com/vi/' . $id . '/default.jpg']],
				'channelTitle' => 'A channel',
				'publishedAt' => '2026-01-01T00:00:00Z',
			],
		];
	}

	public function testAResultOfAnotherTypeIsLeftOut(): void {
		// what YouTube answered for a video search for "nextcloud hub": a channel among the videos, whose ID the
		// fields filter of the request leaves out
		$this->answer = fn (): IResponse => $this->respond((string)json_encode([
			'nextPageToken' => 'next',
			'items' => [self::item('video', 'first'), self::item('video', null), self::item('video', 'second')],
		]));

		$result = $this->service->search('nextcloud hub', 'video', 3);

		$this->assertSame(['first', 'second'], array_map(fn ($item) => $item->id, $result['results']));
		$this->assertSame('next', $result['nextPageToken']);
		$this->assertSame([], $this->logged);
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function escapedTypes(): array {
		return ['videos' => ['video'], 'playlists' => ['playlist']];
	}

	#[DataProvider('escapedTypes')]
	public function testTitlesAreDecodedAndDescriptionsKeptAsTheyAre(string $type): void {
		// as YouTube sends them: the title HTML-escaped once, the description and the channel title as they are
		$this->answer = fn (): IResponse => $this->respond((string)json_encode([
			'items' => [
				self::item($type, 'promo', [
					'title' => 'It&#39;s Only Rock &#39;N&#39; Roll &amp; more &quot;live&quot;',
					'description' => 'Out now & available, see <a> &amp; more',
					'channelTitle' => 'Tom &amp; Jerry&#39;s',
				]),
				// a title that itself says "&amp;"
				self::item($type, 'literal', ['title' => 'Write &amp;amp; for an ampersand']),
			],
		]));

		$result = $this->service->search('rock', $type, 2);

		$this->assertSame('It\'s Only Rock \'N\' Roll & more "live"', $result['results'][0]->title);
		$this->assertSame('Out now & available, see <a> &amp; more', $result['results'][0]->description);
		$this->assertSame('Tom &amp; Jerry&#39;s', $result['results'][0]->channelName);
		$this->assertSame('Write &amp; for an ampersand', $result['results'][1]->title);
	}

	public function testChannelTitlesAreKeptAsTheyAre(): void {
		// YouTube sends the titles of channels unescaped
		$this->answer = fn (): IResponse => $this->respond((string)json_encode([
			'items' => [self::item('channel', 'tom', ['title' => 'Tom & Jerry']), self::item('channel', 'literal', ['title' => 'Tom &amp; Jerry'])],
		]));

		$result = $this->service->search('tom', 'channel', 2);

		$this->assertSame(['Tom & Jerry', 'Tom &amp; Jerry'], array_map(fn ($item) => $item->title, $result['results']));
	}

	public function testTheLastPageHasNoNextPageToken(): void {
		$this->answer = fn (): IResponse => $this->respond((string)json_encode(['items' => [self::item('playlist', 'last')]]));

		$result = $this->service->search('nextcloud', 'playlist', 1, 'previous');

		$this->assertCount(1, $result['results']);
		$this->assertNull($result['nextPageToken']);
		$this->assertStringContainsString('pageToken=previous', $this->requests[0]['url']);
	}

	public function testAResultMissingAFieldIsLeftOut(): void {
		$withoutThumbnail = self::item('video', 'no-thumbnail');
		unset($withoutThumbnail['snippet']['thumbnails']);
		$withoutDate = self::item('video', 'no-date');
		unset($withoutDate['snippet']['publishedAt']);
		$withoutTitle = self::item('video', 'no-title');
		unset($withoutTitle['snippet']['title']);
		$withoutChannel = self::item('video', 'no-channel');
		unset($withoutChannel['snippet']['channelTitle']);
		$withoutDescription = self::item('video', 'no-description');
		unset($withoutDescription['snippet']['description']);
		$withoutSnippet = self::item('video', 'no-snippet');
		unset($withoutSnippet['snippet']);
		$withAnUnreadableDate = self::item('video', 'bad-date', ['publishedAt' => 'yesterday-ish']);
		// a part of a result that is text instead of a list of fields holds none of them
		$withAnIdThatIsText = ['id' => 'text-id'] + self::item('video', 'x');
		$withASnippetThatIsText = ['snippet' => 'text'] + self::item('video', 'text-snippet');
		$withThumbnailsThatAreText = self::item('video', 'text-thumbnails', ['thumbnails' => 'text']);
		$withADefaultThumbnailThatIsText = self::item('video', 'text-default', ['thumbnails' => ['default' => 'text']]);
		$this->answer = fn (): IResponse => $this->respond((string)json_encode([
			'items' => [$withoutThumbnail, self::item('video', 'complete'), $withoutDate, $withoutTitle, $withoutChannel,
				$withoutSnippet, $withAnUnreadableDate, $withoutDescription, self::item('video', 'empty-description', ['description' => '']),
				$withAnIdThatIsText, $withASnippetThatIsText, $withThumbnailsThatAreText, $withADefaultThumbnailThatIsText],
		]));

		$result = $this->service->search('nextcloud', 'video', 13);

		// the description is the one field a result does without
		$this->assertSame(['complete', 'no-description', 'empty-description'], array_map(fn ($item) => $item->id, $result['results']));
		$this->assertSame(['About it', 'No description', 'No description'], array_map(fn ($item) => $item->description, $result['results']));
		$this->assertSame([], $this->logged);
	}

	public function testANullFieldCountsAsMissing(): void {
		$withANullId = self::item('video', 'null-id');
		$withANullId['id']['videoId'] = null;
		// and the parts that hold fields
		$withANullIdPart = ['id' => null] + self::item('video', 'x');
		$withANullSnippet = ['snippet' => null] + self::item('video', 'null-snippet');
		$this->answer = fn (): IResponse => $this->respond((string)json_encode([
			'items' => [$withANullId, self::item('video', 'null-title', ['title' => null]), self::item('video', 'complete'),
				self::item('video', 'null-thumbnail', ['thumbnails' => ['default' => ['url' => null]]]),
				self::item('video', 'null-channel', ['channelTitle' => null]), self::item('video', 'null-date', ['publishedAt' => null]),
				self::item('video', 'null-description', ['description' => null]),
				$withANullIdPart, $withANullSnippet, self::item('video', 'null-thumbnails', ['thumbnails' => null]),
				self::item('video', 'null-default', ['thumbnails' => ['default' => null]])],
		]));

		$result = $this->service->search('nextcloud', 'video', 11);

		$this->assertSame(['complete', 'null-description'], array_map(fn ($item) => $item->id, $result['results']));
		$this->assertSame(['About it', 'No description'], array_map(fn ($item) => $item->description, $result['results']));
		$this->assertSame([], $this->logged);
	}

	/**
	 * @return array<string, array{0: string, 1: mixed}>
	 */
	public static function fieldsOfTheWrongType(): array {
		return [
			'a title that is a list' => ['title', ['not', 'a', 'string']],
			'a title that is a number' => ['title', 123],
			'a channel title that is true' => ['channelTitle', true],
			'a date that is a number' => ['publishedAt', 1700000000],
			'a description that is a number' => ['description', 5],
			'a thumbnail URL that is a number' => ['thumbnails', ['default' => ['url' => 9]]],
			// empty values too, which are of the wrong type, not missing
			'a title that is false' => ['title', false],
			'a channel title that is zero' => ['channelTitle', 0],
			'a date that is false' => ['publishedAt', false],
			'a description that is false' => ['description', false],
			'a description that is zero' => ['description', 0],
			'a thumbnail URL that is an empty list' => ['thumbnails', ['default' => ['url' => []]]],
		];
	}

	#[DataProvider('fieldsOfTheWrongType')]
	public function testAResultOfAnUnexpectedShapeEndsTheSearchWithAnError(string $field, mixed $value): void {
		$this->answer = fn (): IResponse => $this->respond((string)json_encode(['items' => [self::item('video', 'odd', [$field => $value])]]));

		$error = null;
		try {
			$this->service->search('nextcloud', 'video', 1);
		} catch (RuntimeException $e) {
			$error = $e;
		}

		// a RuntimeException, which the search providers turn into no results, not the TypeError behind it
		$this->assertSame(RuntimeException::class, $error !== null ? $error::class : null);
		$this->assertSame('YouTube API error', $error->getMessage());
		$this->assertSame(['warning'], array_column($this->logged, 'level'));
	}

	public function testAResultMissingAFieldIsLeftOutEvenWithAFieldOfTheWrongType(): void {
		// one reason to leave a result out, and every other field of the wrong type, so that a check of any field's type
		// made before the reason ends the search; for each kind of wrong value and each type of search
		foreach (['video', 'channel', 'playlist'] as $type) {
			foreach ([5, 1.5, true, false, 0, ['a']] as $wrong) {
				$allOfTheWrongType = ['title' => $wrong, 'description' => $wrong, 'thumbnails' => ['default' => ['url' => $wrong]], 'channelTitle' => $wrong, 'publishedAt' => $wrong];
				$items = [
					self::item($type, null, $allOfTheWrongType),
					['id' => [$type . 'Id' => $wrong]] + self::item($type, 'x', ['publishedAt' => 'yesterday-ish'] + $allOfTheWrongType),
				];
				foreach (['title', 'thumbnails', 'channelTitle', 'publishedAt'] as $missing) {
					$item = ['id' => [$type . 'Id' => $wrong]] + self::item($type, 'x', $allOfTheWrongType);
					unset($item['snippet'][$missing]);
					$items[] = $item;
				}
				// and the reason a field that is empty text
				$items[] = ['id' => [$type . 'Id' => '']] + self::item($type, 'x', $allOfTheWrongType);
				foreach ([['title' => ''], ['thumbnails' => ['default' => ['url' => '']]], ['channelTitle' => ''], ['publishedAt' => '']] as $emptyText) {
					$items[] = ['id' => [$type . 'Id' => $wrong]] + self::item($type, 'x', $emptyText + $allOfTheWrongType);
				}
				$this->answer = fn (): IResponse => $this->respond((string)json_encode(['items' => [self::item($type, 'complete'), ...$items]]));

				$result = $this->service->search('nextcloud', $type, 7);

				$this->assertSame(['complete'], array_map(fn ($item) => $item->id, $result['results']), $type . ' ' . json_encode($wrong));
				$this->assertSame([], $this->logged, $type . ' ' . json_encode($wrong));
			}
		}
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function unreadableAnswers(): array {
		return [
			'not JSON' => ['<html>Service Unavailable</html>'],
			'an answer that is text' => ['"Service Unavailable"'],
			'an answer that is a number' => ['503'],
			'an answer that is true' => ['true'],
			'an answer that is false' => ['false'],
			'an answer that is null' => ['null'],
			'results that are not a list' => ['{"items": "none"}'],
			'results that are null' => ['{"items": null}'],
			'results that are a number' => ['{"items": 5}'],
			'results that are true' => ['{"items": true}'],
			'results that are false' => ['{"items": false}'],
			'results that are a fraction' => ['{"items": 1.5}'],
			'an error object' => ['{"error": {"code": 400, "message": "Bad Request"}}'],
			'an error that is a number' => ['{"error": 0}'],
			'an error that is false' => ['{"error": false}'],
			'an error that is a list' => ['{"error": []}'],
		];
	}

	public function testAnIdOrTheTitleOfAChannelOfTheWrongTypeEndsTheSearchWithAnError(): void {
		foreach ([['video', ['id' => ['videoId' => 7]] + self::item('video', 'x')], ['video', ['id' => ['videoId' => false]] + self::item('video', 'x')],
			['channel', self::item('channel', 'x', ['title' => 5])]] as [$type, $item]) {
			$this->answer = fn (): IResponse => $this->respond((string)json_encode(['items' => [$item]]));
			$error = null;
			try {
				$this->service->search('nextcloud', $type, 1);
			} catch (RuntimeException $e) {
				$error = $e;
			}
			$this->assertSame('YouTube API error', $error?->getMessage(), $type);
		}
	}

	public function testAnErrorObjectIsLoggedWithTheAnswer(): void {
		$this->answer = fn (): IResponse => $this->respond('{"error": {"code": 400, "message": "Bad Request"}}');

		try {
			$this->service->search('nextcloud', 'video', 1);
		} catch (RuntimeException) {
		}

		$this->assertSame('YouTube API answered with an error', $this->logged[0]['message'] ?? null);
		$this->assertSame('{"code":400,"message":"Bad Request"}', $this->logged[0]['context']['response'] ?? null);
	}

	#[DataProvider('unreadableAnswers')]
	public function testAnAnswerWithoutAListOfResultsEndsTheSearchWithAnError(string $body): void {
		$this->answer = fn (): IResponse => $this->respond($body);

		$error = null;
		try {
			$this->service->search('nextcloud', 'video', 1);
		} catch (RuntimeException $e) {
			$error = $e;
		}

		$this->assertSame('YouTube API error', $error?->getMessage());
		$this->assertSame(['warning'], array_column($this->logged, 'level'));
	}

	public function testAFieldThatIsEmptyTextCountsAsMissing(): void {
		$this->answer = fn (): IResponse => $this->respond((string)json_encode([
			'items' => [['id' => ['videoId' => '']] + self::item('video', 'x'), self::item('video', 'empty-title', ['title' => '']),
				self::item('video', 'complete'), self::item('video', 'empty-thumbnail', ['thumbnails' => ['default' => ['url' => '']]]),
				self::item('video', 'empty-channel', ['channelTitle' => '']), self::item('video', 'empty-date', ['publishedAt' => '']),
				self::item('video', 'empty-description', ['description' => ''])],
		]));

		$result = $this->service->search('nextcloud', 'video', 7);

		$this->assertSame(['complete', 'empty-description'], array_map(fn ($item) => $item->id, $result['results']));
		$this->assertSame(['About it', 'No description'], array_map(fn ($item) => $item->description, $result['results']));
		$this->assertSame([], $this->logged);
	}

	public function testAnErrorThatIsNullIsNoError(): void {
		$this->answer = fn (): IResponse => $this->respond((string)json_encode(['error' => null, 'items' => [self::item('video', 'complete')]]));

		$result = $this->service->search('nextcloud', 'video', 1);

		$this->assertSame(['complete'], array_map(fn ($item) => $item->id, $result['results']));
		$this->assertSame([], $this->logged);
	}

	public function testAnAnswerWithoutResultsGivesNone(): void {
		$this->answer = fn (): IResponse => $this->respond('{}');

		$this->assertSame(['results' => [], 'nextPageToken' => null], $this->service->search('nothing', 'channel', 5));
	}

	/**
	 * The HTTP and network failures are built the way Guzzle builds them: from the URL and the options the request
	 * was made with, so the key would be in their messages if it were in the URL, and in their trace arguments
	 * wherever PHP keeps those. The HTTP errors come from Guzzle's own factory, whose message ends with a summary of
	 * YouTube's answer.
	 *
	 * @return array<string, array{0: callable(string, array): Throwable, 1: string, 2: ?string}>
	 */
	public static function failures(): array {
		$googleError = (string)json_encode(['error' => ['code' => 400, 'message' => 'API key not valid. Please pass a valid API key.', 'errors' => [['reason' => 'badRequest']]]]);
		$quotaError = (string)json_encode(['error' => ['code' => 403, 'message' => 'The request cannot be completed because you have exceeded your quota.']]);
		return [
			'bad request' => [fn (string $url, array $options): Throwable => RequestException::create(
				new Request('GET', $url, $options['headers']), new Response(400, [], $googleError)), 'HTTP 400', $googleError],
			'quota used up' => [fn (string $url, array $options): Throwable => RequestException::create(
				new Request('GET', $url, $options['headers']), new Response(403, [], $quotaError)), 'HTTP 403', $quotaError],
			'server error' => [fn (string $url, array $options): Throwable => RequestException::create(
				new Request('GET', $url, $options['headers']), new Response(503, [], 'unavailable')), 'HTTP 503', 'unavailable'],
			'network error' => [fn (string $url, array $options): Throwable => new ConnectException(
				"cURL error 28: Operation timed out after 30001 milliseconds with 0 bytes received (see https://curl.se/libcurl/c/libcurl-errors.html) for {$url}",
				new Request('GET', $url, $options['headers'])), 'Operation timed out', null],
			'unexpected exception' => [fn (string $url, array $options): Throwable => new Exception("Could not fetch {$url}"), 'Could not fetch', null],
		];
	}

	/**
	 * @param callable(string, array): Throwable $failure
	 * @param string $loggedAs what the log line of the failure says
	 * @param ?string $answer YouTube's answer, which is logged with it
	 */
	#[DataProvider('failures')]
	public function testAFailedRequestKeepsTheKeyOutOfTheLogAndTheError(callable $failure, string $loggedAs, ?string $answer): void {
		$this->answer = function (string $url, array $options) use ($failure): IResponse {
			throw $failure($url, $options);
		};

		$this->assertTheSearchFailsWithoutTheKey();
		$this->assertStringContainsString($loggedAs, $this->logged[0]['message'], 'the failure is logged with its HTTP status or its error');
		$this->assertSame($answer, $this->logged[0]['context']['response'] ?? null, "YouTube's answer is logged with it");
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function keysWithStrayWhitespace(): array {
		return [
			'a line break after it' => [self::API_KEY . "\n"],
			'a space before it and a Windows line break after it' => [' ' . self::API_KEY . "\r\n"],
			'a line break and a space after it' => [self::API_KEY . "\n "],
		];
	}

	/**
	 * A key stored with stray whitespace is refused as a header value, and the HTTP client quotes it in its message.
	 */
	#[DataProvider('keysWithStrayWhitespace')]
	public function testAKeyStoredWithStrayWhitespaceStaysOutOfTheLog(string $storedKey): void {
		$this->storedKey = $storedKey;
		$this->answer = function (string $url, array $options): IResponse {
			// as the HTTP client does with the options, which throws for a header value it refuses
			new Request('GET', $url, $options['headers']);
			return $this->respond('{}');
		};

		$this->assertTheSearchFailsWithoutTheKey();
		$this->assertStringContainsString('is not valid header value', $this->logged[0]['message'], 'the HTTP client refused the key');
	}

	/**
	 * @return array<string, array{0: int}>
	 */
	public static function redirects(): array {
		return [
			'HTTP 300' => [300],
			'HTTP 301' => [301],
			'HTTP 302' => [302],
			'HTTP 303' => [303],
			'HTTP 307' => [307],
			'HTTP 308' => [308],
		];
	}

	#[DataProvider('redirects')]
	public function testARedirectIsNotFollowedAndEndsTheSearchWithAnError(int $status): void {
		$this->answer = fn (): IResponse => $this->respond('<html>Moved</html>', $status);

		$error = $this->assertTheSearchFailsWithoutTheKey();
		$this->assertCount(1, $this->requests, 'the redirect is not followed');
		$this->assertSame('YouTube API error', $error->getMessage());
		$this->assertSame(['warning'], array_column($this->logged, 'level'));
		$this->assertStringContainsString("HTTP {$status}", $this->logged[0]['message']);
	}

	private function assertTheSearchFailsWithoutTheKey(): RuntimeException {
		$error = null;
		try {
			$this->service->search('nextcloud', 'video', 1);
		} catch (RuntimeException $e) {
			$error = $e;
		}
		// checked after the try, as a failed assertion throws a RuntimeException too, and by its exact class, as Guzzle's
		// exceptions are RuntimeExceptions as well
		$this->assertSame(RuntimeException::class, $error !== null ? $error::class : null, 'a failed request ends the search with an error');
		$this->assertKeyNowhere($error->getMessage(), 'the error the search ends with');
		$this->assertNull($error->getPrevious(), 'the error does not carry the request exception, which the server would log with it');

		$this->assertNotEmpty($this->logged, 'the failure is logged');
		foreach ($this->logged as $entry) {
			$this->assertKeyNowhere($entry['message'], "the {$entry['level']} message");
			$this->assertKeyNowhere($entry['context'], "the {$entry['level']} context");
			foreach ($entry['context'] as $value) {
				// a logged exception would carry the request options, and the key with them, in its trace
				$this->assertNotInstanceOf(Throwable::class, $value, 'the request exception itself is not logged');
			}
		}
		return $error;
	}

	private function assertKeyNowhere(mixed $value, string $where): void {
		$this->assertFalse($this->containsKey($value), "the API key is in {$where}");
	}

	private function containsKey(mixed $value, int $depth = 0): bool {
		if ($depth > 10) {
			return false;
		}
		if (is_string($value)) {
			// any four characters of it in a row, so that a key taken out only in part is found too
			for ($i = 0; $i + 4 <= strlen(self::API_KEY); $i++) {
				if (str_contains($value, substr(self::API_KEY, $i, 4))) {
					return true;
				}
			}
			return false;
		}
		if (is_array($value)) {
			foreach ($value as $item) {
				if ($this->containsKey($item, $depth + 1)) {
					return true;
				}
			}
			return false;
		}
		if ($value instanceof Throwable) {
			return $this->containsKey($value->getMessage(), $depth + 1)
				|| $this->containsKey(array_map(fn (array $frame) => $frame['args'] ?? [], $value->getTrace()), $depth + 1)
				|| $this->containsKey($value->getPrevious(), $depth + 1);
		}
		if ($value instanceof \Stringable) {
			return $this->containsKey((string)$value, $depth + 1);
		}
		return false;
	}
}
