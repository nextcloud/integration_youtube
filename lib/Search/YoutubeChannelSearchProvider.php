<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\IntegrationYoutube\Search;

use OCA\IntegrationYoutube\Type\SearchResultItem;
use OCP\IUser;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;

class YoutubeChannelSearchProvider extends YoutubeBaseSearchProvider {

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'youtube-channel-search';
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->l10n->t('YouTube Channels');
	}

	/**
	 * @inheritDoc
	 */
	public function search(IUser $user, ISearchQuery $query): SearchResult {
		return $this->searchFor('channel', $user, $query);
	}

	/**
	 * @inheritDoc
	 */
	protected function getYoutubeLink(string $id): string {
		return 'https://www.youtube.com/channel/' . $id;
	}

	/**
	 * @inheritDoc
	 */
	protected function getSubline(SearchResultItem $entry): string {
		return $entry->description;
	}
}
