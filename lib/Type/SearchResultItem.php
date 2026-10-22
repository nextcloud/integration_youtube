<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IntegrationYoutube\Type;

class SearchResultItem {
	public function __construct(
		public string $id,
		public string $title,
		public string $description,
		public string $thumbnailUrl,
		public string $channelName,
		public string $publishedAt,
	) {
	}
}
