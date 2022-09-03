<?php
/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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

namespace OCA\IntegrationYoutube\Reference;

use OC\Collaboration\Reference\LinkReferenceProvider;
use OCP\Collaboration\Reference\IReference;

class YoutubeReferenceProvider implements \OCP\Collaboration\Reference\IReferenceProvider {
	private LinkReferenceProvider $linkReferenceProvider;

	public function __construct(LinkReferenceProvider $linkReferenceProvider) {
		$this->linkReferenceProvider = $linkReferenceProvider;
	}

	/**
	 * @inheritDoc
	 */
	public function matchReference(string $referenceText): bool {
		if (mb_strpos($referenceText, 'https://www.youtube.com/') === 0 && $this->getVideoId($referenceText) !== null) {
			return true;
		}
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function resolveReference(string $referenceText): ?IReference {
		if ($this->matchReference($referenceText)) {
			$reference = $this->linkReferenceProvider->resolveReference($referenceText);
			$reference->setRichObject('integration_youtube', [
				'videoId' => $this->getVideoId($referenceText),
			]);
			return $reference;
		}

		return null;
	}

	private function getVideoId(string $url): ?string {
		preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user|shorts)\/))([^\?&\"'>]+)/", $url, $matches);
		return $matches ? $matches[1] : null;
	}

	public function getCachePrefix(string $referenceId): string {
		return $referenceId;
	}

	public function getCacheKey(string $referenceId): ?string {
		return null;
	}
}
