<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024, Anupam Kumar
 *
 * @author Anupam Kumar <kyteinsky@gmail.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\IntegrationYoutube\Search;

use DateTime;
use OCA\IntegrationYoutube\AppInfo\Application;
use OCA\IntegrationYoutube\Service\YoutubeAPIService;
use OCA\IntegrationYoutube\Type\SearchResultItem;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IDateTimeFormatter;
use OCP\IL10N;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;
use Psr\Log\LoggerInterface;

abstract class YoutubeBaseSearchProvider implements IProvider {

	protected IL10N $l10n;
	private IConfig $config;
	private YoutubeAPIService $service;
	private LoggerInterface $logger;
	private IDateTimeFormatter $dateTimeFormatter;
	private ?ICache $cache = null;

	public function __construct(
		IL10N $l10n,
		IConfig $config,
		YoutubeAPIService $service,
		LoggerInterface $logger,
		IDateTimeFormatter $dateTimeFormatter,
		ICacheFactory $cacheFactory,
	) {
		$this->l10n = $l10n;
		$this->config = $config;
		$this->service = $service;
		$this->logger = $logger;
		$this->dateTimeFormatter = $dateTimeFormatter;
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
		if ($this->config->getUserValue($user->getUID(), Application::APP_ID, 'search_enabled', 'false') === 'false') {
			return SearchResult::complete($this->getName(), []);
		}

		if ($this->config->getAppValue(Application::APP_ID, 'token') === '') {
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

		$searchResult = $this->service->search($term, $type, $limit);

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
		$userId = $user->getUID();
		$limit = $query->getLimit();
		$term = $query->getTerm();
		$offset = $query->getCursor();

		$nextPageToken = $this->cache->get($userId . $term);

		if (is_null($nextPageToken) && $offset > 0) {
			// cache cleared before "Load more" could be clicked.
			// we can calculate the page no. to set limit (n * $limit) and return the $limit no. of elements
			// from the end of the array, but we choose to ignore this since it has very less chance of happening or not at all
			$this->logger->info('Memory cache cleared, could not get the nextPageToken to get the next page of the search results', ['app' => Application::APP_ID]);
			return SearchResult::complete($this->getName(), []);
		}

		$searchResult = $this->service->search($term, $type, $limit, $nextPageToken);
		$this->cache->set($userId . $term, $searchResult['nextPageToken'], 60 * 60);

		$formattedResults = array_map(function (SearchResultItem $entry): SearchResultEntry {
			return new SearchResultEntry(
				$entry->thumbnailUrl,
				$entry->title,
				$this->getSubline($entry),
				$this->getYoutubeLink($entry->id),
			);
		}, $searchResult['results']);

		return SearchResult::paginated(
			$this->getName(),
			$formattedResults,
			intval($offset) + $limit,
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
}
