<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IntegrationYoutube\Tests\Unit\Search;

use OCA\IntegrationYoutube\Search\YoutubeBaseSearchProvider;
use OCA\IntegrationYoutube\Search\YoutubeChannelSearchProvider;
use OCA\IntegrationYoutube\Search\YoutubeVideoSearchProvider;
use OCA\IntegrationYoutube\Service\YoutubeAPIService;
use OCA\IntegrationYoutube\Type\SearchResultItem;
use OCP\AppFramework\Services\IAppConfig;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IDateTimeFormatter;
use OCP\IL10N;
use OCP\IUser;
use OCP\Search\ISearchQuery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class YoutubeVideoSearchProviderTest extends TestCase {
	/** @var list<array{0: string, 1: ?string}> the type and page token of each search the service was asked for */
	private array $searches = [];
	/** @var list<array{results: list<SearchResultItem>, nextPageToken: ?string}|RuntimeException> */
	private array $answers = [];
	private YoutubeAPIService $service;
	private IUser $user;

	protected function setUp(): void {
		parent::setUp();
		$this->service = $this->createMock(YoutubeAPIService::class);
		$this->service->method('search')->willReturnCallback(function (string $term, string $type, int $limit, ?string $nextPageToken = null): array {
			$this->searches[] = [$type, $nextPageToken];
			$answer = array_shift($this->answers);
			if ($answer instanceof RuntimeException) {
				throw $answer;
			}
			return $answer;
		});
		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn('alice');
	}

	/**
	 * The provider searches page by page when the server has a local cache for the page tokens, and in one go
	 * otherwise.
	 *
	 * @return array<string, array{0: bool}>
	 */
	public static function caches(): array {
		return ['with a local cache' => [true], 'without a local cache' => [false]];
	}

	private static function cache(): ICache {
		return new class implements ICache {
			/** @var array<string, mixed> */
			public array $values = [];
			/** a Memcached that cannot read a key answers false for it */
			public bool $unreadable = false;

			public function get($key) {
				return $this->unreadable ? false : ($this->values[$key] ?? null);
			}

			public function set($key, $value, $ttl = 0) {
				$this->values[$key] = $value;
				return true;
			}

			public function hasKey($key) {
				return array_key_exists($key, $this->values);
			}

			public function remove($key) {
				unset($this->values[$key]);
				return true;
			}

			public function clear($prefix = '') {
				foreach (array_keys($this->values) as $key) {
					if (str_starts_with($key, $prefix)) {
						unset($this->values[$key]);
					}
				}
				return true;
			}

			public static function isAvailable(): bool {
				return true;
			}
		};
	}

	/**
	 * @param class-string<YoutubeBaseSearchProvider> $class
	 */
	private function provider(?ICache $cache, string $class = YoutubeVideoSearchProvider::class): YoutubeBaseSearchProvider {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getUserValue')->willReturn('true');
		$appConfig->method('getAppValueString')->willReturn('encrypted-api-key');
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('isLocalCacheAvailable')->willReturn($cache !== null);
		$cacheFactory->method('createLocal')->willReturn($cache ?? self::cache());
		$dateTimeFormatter = $this->createMock(IDateTimeFormatter::class);
		$dateTimeFormatter->method('formatTimeSpan')->willReturn('1 month ago');
		return new $class($l10n, $appConfig, $this->service, $this->createMock(LoggerInterface::class), $dateTimeFormatter, $cacheFactory);
	}

	private function query(?int $cursor, string $term = 'nextcloud'): ISearchQuery {
		$query = $this->createMock(ISearchQuery::class);
		$query->method('getTerm')->willReturn($term);
		$query->method('getLimit')->willReturn(5);
		$query->method('getCursor')->willReturn($cursor);
		return $query;
	}

	/**
	 * @return array{results: list<SearchResultItem>, nextPageToken: ?string}
	 */
	private static function page(?string $nextPageToken, string $id = 'dQw4w9WgXcQ'): array {
		return [
			'results' => [new SearchResultItem($id, "It's a video", 'About it', 'https://i.ytimg.com/vi/' . $id . '/default.jpg', 'A channel', '2026-01-01T00:00:00Z')],
			'nextPageToken' => $nextPageToken,
		];
	}

	public function testAFailedLoadMoreGivesNoResultsInsteadOfAnError(): void {
		$provider = $this->provider(self::cache());
		// the daily quota used up between two pages
		$this->answers = [self::page('page-2'), new RuntimeException('Client error: `GET https://www.googleapis.com/youtube/v3/search?…` resulted in a `403 Forbidden` response')];

		$provider->search($this->user, $this->query(null));
		$result = $provider->search($this->user, $this->query(5))->jsonSerialize();

		$this->assertSame([['video', null], ['video', 'page-2']], $this->searches);
		$this->assertSame([], $result['entries']);
		$this->assertFalse($result['isPaginated'], 'there is no next page to ask for');
	}

	#[DataProvider('caches')]
	public function testAFailedSearchGivesNoResultsInsteadOfAnError(bool $withCache): void {
		$cache = $withCache ? self::cache() : null;
		// what the service throws when YouTube refuses the request, for example for an invalid API key
		$this->answers = [new RuntimeException('Client error: `GET https://www.googleapis.com/youtube/v3/search?…` resulted in a `400 Bad Request` response')];

		$result = $this->provider($cache)->search($this->user, $this->query(null))->jsonSerialize();

		$this->assertSame([], $result['entries']);
		$this->assertFalse($result['isPaginated'], 'there is no next page to ask for');
		$this->assertSame([], $cache?->values ?? [], 'no page token is kept');
	}

	#[DataProvider('caches')]
	public function testResultsBecomeSearchEntries(bool $withCache): void {
		$this->answers = [self::page('next')];

		$result = $this->provider($withCache ? self::cache() : null)->search($this->user, $this->query(null))->jsonSerialize();

		$this->assertCount(1, $result['entries']);
		$entry = $result['entries'][0]->jsonSerialize();
		$this->assertSame("It's a video", $entry['title']);
		$this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $entry['resourceUrl']);
		$this->assertSame('A channel - 1 month ago - About it', $entry['subline']);
		$this->assertSame($withCache, $result['isPaginated']);
	}

	public function testLoadMoreGoesOnFromWhereThePageBeforeEnded(): void {
		$provider = $this->provider(self::cache());
		$this->answers = [self::page('page-2'), self::page('page-3')];

		$first = $provider->search($this->user, $this->query(null))->jsonSerialize();
		$second = $provider->search($this->user, $this->query(5))->jsonSerialize();

		$this->assertSame([['video', null], ['video', 'page-2']], $this->searches);
		$this->assertSame(5, $first['cursor']);
		$this->assertSame(10, $second['cursor']);
	}

	public function testANewSearchStartsAtTheFirstPage(): void {
		$provider = $this->provider(self::cache());
		$this->answers = [self::page('page-2'), self::page('page-2')];

		$provider->search($this->user, $this->query(null));
		// the same term again, from the start
		$provider->search($this->user, $this->query(null));

		$this->assertSame([['video', null], ['video', null]], $this->searches);
	}

	public function testEachSearchTypeKeepsItsOwnPage(): void {
		// one cache for the app, as the providers share it on a server
		$cache = self::cache();
		$videos = $this->provider($cache);
		$channels = $this->provider($cache, YoutubeChannelSearchProvider::class);
		$this->answers = [self::page('video-page-2'), self::page('channel-page-2'), self::page('video-page-3')];

		$videos->search($this->user, $this->query(null));
		$channels->search($this->user, $this->query(null));
		$videos->search($this->user, $this->query(5));

		$this->assertSame([['video', null], ['channel', null], ['video', 'video-page-2']], $this->searches);
	}

	public function testEachUserAndTermKeepsItsOwnPage(): void {
		$cache = self::cache();
		$provider = $this->provider($cache);
		$bob = $this->createMock(IUser::class);
		$bob->method('getUID')->willReturn('bob');
		$this->answers = [self::page('alice-page-2'), self::page('bob-page-2'), self::page('other-term-page-2'), self::page('alice-page-3')];

		$provider->search($this->user, $this->query(null));
		$provider->search($bob, $this->query(null));
		$provider->search($this->user, $this->query(null, 'other term'));
		$provider->search($this->user, $this->query(5));

		$this->assertSame('alice-page-2', $this->searches[3][1]);
		$this->assertCount(3, $cache->values);
	}

	public function testALostPageTokenEndsTheSearch(): void {
		// what the provider reads when the cache lost the token: nothing, or false from a Memcached that cannot read it
		foreach ([false, true] as $unreadable) {
			$cache = self::cache();
			$cache->unreadable = $unreadable;
			$this->searches = [];

			$result = $this->provider($cache)->search($this->user, $this->query(5))->jsonSerialize();

			$this->assertSame([], $this->searches, 'YouTube is not asked for the first page again');
			$this->assertSame([], $result['entries']);
			$this->assertFalse($result['isPaginated']);
		}
	}

	public function testTheLastPageReachedByLoadingMoreOffersNoMore(): void {
		$cache = self::cache();
		$provider = $this->provider($cache);
		$channels = $this->provider($cache, YoutubeChannelSearchProvider::class);
		$bob = $this->createMock(IUser::class);
		$bob->method('getUID')->willReturn('bob');
		// the pages other searches are at, of another user, a longer term and another type, which stay
		$this->answers = [self::page('bob-page-2'), self::page('longer-term-page-2'), self::page('channel-page-2')];
		$provider->search($bob, $this->query(null));
		$provider->search($this->user, $this->query(null, 'nextcloud hub'));
		$channels->search($this->user, $this->query(null));
		$this->answers = [self::page('page-2'), self::page(null, 'last')];

		$provider->search($this->user, $this->query(null));
		$last = $provider->search($this->user, $this->query(5))->jsonSerialize();

		$this->assertCount(1, $last['entries']);
		$this->assertFalse($last['isPaginated'], 'no "load more" after the last page');
		// the token of the page before it is gone, and only that one
		$this->searches = [];
		$this->answers = [self::page(null), self::page(null), self::page(null)];
		$provider->search($this->user, $this->query(5));
		$provider->search($bob, $this->query(5));
		$provider->search($this->user, $this->query(5, 'nextcloud hub'));
		$channels->search($this->user, $this->query(5));
		$this->assertSame([['video', 'bob-page-2'], ['video', 'longer-term-page-2'], ['channel', 'channel-page-2']], $this->searches);
	}

	public function testTheLastPageOffersNoMore(): void {
		$cache = self::cache();
		$this->answers = [self::page(null)];

		$result = $this->provider($cache)->search($this->user, $this->query(null))->jsonSerialize();

		$this->assertCount(1, $result['entries']);
		$this->assertFalse($result['isPaginated'], 'no "load more" after the last page');
		$this->assertSame([], $cache->values);
	}
}
