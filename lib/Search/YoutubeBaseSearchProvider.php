<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\IntegrationYoutube\Search;

use DateTime;
use OCA\IntegrationYoutube\AppInfo\Application;
use OCA\IntegrationYoutube\Service\YoutubeAPIService;
use OCA\IntegrationYoutube\Type\SearchResultItem;
use OCP\AppFramework\Services\IAppConfig;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IDateTimeFormatter;
use OCP\IL10N;
use OCP\IUser;
use OCP\Search\IExternalProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;
use Psr\Log\LoggerInterface;
use RuntimeException;

abstract class YoutubeBaseSearchProvider implements IExternalProvider {

	private ?ICache $cache = null;

	public function __construct(
		protected IL10N $l10n,
		protected IAppConfig $appConfig,
		protected YoutubeAPIService $service,
		protected LoggerInterface $logger,
		protected IDateTimeFormatter $dateTimeFormatter,
		ICacheFactory $cacheFactory,
	) {
		if ($cacheFactory->isLocalCacheAvailable()) {
			$this->cache = $cacheFactory->createLocal(Application::APP_ID);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(string $route, array $routeParameters): int {
		return 20;
	}

	protected function searchFor(string $type, IUser $user, ISearchQuery $query): SearchResult {
		if ($this->appConfig->getUserValue($user->getUID(), 'search_enabled', 'false') === 'false') {
			return SearchResult::complete($this->getName(), []);
		}

		if ($this->appConfig->getAppValueString('token', lazy: true) === '') {
			$this->logger->warning('YouTube search provider is not configured, set a API Key in the settings');
			return SearchResult::complete($this->getName(), []);
		}

		if (is_null($this->cache)) {
			return $this->completeSearch($type, $user, $query);
		}

		return $this->paginatedSearch($type, $user, $query);
	}

	private function completeSearch(string $type, IUser $user, ISearchQuery $query): SearchResult {
		$limit = $query->getLimit();
		$term = $query->getTerm();

		try {
			$searchResult = $this->service->search($term, $type, $limit);
		} catch (RuntimeException $e) {
			// no results instead of a server error
			return SearchResult::complete($this->getName(), []);
		}

		$formattedResults = array_map(function (SearchResultItem $entry): SearchResultEntry {
			return new SearchResultEntry(
				$entry->thumbnailUrl,
				$entry->title,
				$this->getSubline($entry),
				$this->getYoutubeLink($entry->id),
			);
		}, $searchResult['results']);

		return SearchResult::complete(
			$this->getName(),
			$formattedResults,
		);
	}

	private function paginatedSearch(string $type, IUser $user, ISearchQuery $query): SearchResult {
		$limit = $query->getLimit();
		$term = $query->getTerm();
		$offset = intval($query->getCursor());
		// per search type too, so that the video, channel and playlist searches for a term each keep their own page
		$cacheKey = $user->getUID() . '/' . $type . '/' . $term;

		// a new search starts at the first page, and "load more" goes on from where the page before it ended
		$nextPageToken = $offset > 0 ? $this->cache->get($cacheKey) : null;
		if (!is_string($nextPageToken)) {
			// Memcached answers false for a key it cannot read
			$nextPageToken = null;
		}

		if (is_null($nextPageToken) && $offset > 0) {
			// no token: the last page was reached, or the cache lost the token before "load more" was clicked.
			// for a lost token, we can calculate the page no. to set limit (n * $limit) and return the $limit no. of elements
			// from the end of the array, but we choose to ignore this since it has very less chance of happening or not at all
			$this->logger->info('No page token to get the next page of the search results with, the last page was reached or the cache lost it', ['app' => Application::APP_ID]);
			return SearchResult::complete($this->getName(), []);
		}

		try {
			$searchResult = $this->service->search($term, $type, $limit, $nextPageToken);
		} catch (RuntimeException $e) {
			// no results instead of a server error
			return SearchResult::complete($this->getName(), []);
		}

		$formattedResults = array_map(function (SearchResultItem $entry): SearchResultEntry {
			return new SearchResultEntry(
				$entry->thumbnailUrl,
				$entry->title,
				$this->getSubline($entry),
				$this->getYoutubeLink($entry->id),
			);
		}, $searchResult['results']);

		if ($searchResult['nextPageToken'] === null) {
			// the last page, so there is no more to load
			$this->cache->remove($cacheKey);
			return SearchResult::complete($this->getName(), $formattedResults);
		}
		$this->cache->set($cacheKey, $searchResult['nextPageToken'], 60 * 60);

		return SearchResult::paginated(
			$this->getName(),
			$formattedResults,
			$offset + $limit,
		);
	}

	/**
	 * @param string $id
	 * @return string
	 */
	abstract protected function getYoutubeLink(string $id): string;

	/**
	 * @param SearchResultItem $entry
	 * @return string
	 */
	protected function getSubline(SearchResultItem $entry): string {
		$date = $this->dateTimeFormatter->formatTimeSpan(new DateTime($entry->publishedAt), null, $this->l10n);
		return $entry->channelName . ' - ' . $date . ' - ' . $entry->description;
	}

	public function isExternalProvider(): bool {
		return true;
	}
}
