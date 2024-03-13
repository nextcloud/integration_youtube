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
		return $this->l10n->t('Youtube Channels');
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
