<?php

/**
 * @copyright Copyright (c) 2024 Anupam Kumar <kyteinsky@gmail.com>
 *
 * @author Anupam Kumar <kyteinsky@gmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\IntegrationYoutube\Type;

class SearchResultItem {
	public string $id;
	public string $title;
	public string $description;
	public string $thumbnailUrl;
	public string $channelName;
	public string $publishedAt;

	public function __construct(
		string $id,
		string $title,
		string $description,
		string $thumbnailUrl,
		string $channelName,
		string $publishedAt,
	) {
		$this->id = $id;
		$this->title = $title;
		$this->description = $description;
		$this->thumbnailUrl = $thumbnailUrl;
		$this->channelName = $channelName;
		$this->publishedAt = $publishedAt;
	}
}
