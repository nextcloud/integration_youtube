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

use OCA\IntegrationYoutube\AppInfo\Application;
use OCP\Collaboration\Reference\ADiscoverableReferenceProvider;
use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\ISearchableReferenceProvider;
use OCP\Collaboration\Reference\LinkReferenceProvider;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Http\Client\IClientService;
use OCP\IL10N;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

class YoutubeReferenceProvider extends ADiscoverableReferenceProvider implements ISearchableReferenceProvider {

	/* 5 MiB; for image size and webpage header */
	private const MAX_CONTENT_LENGTH = 5 * 1024 * 1024;

	private const ALLOWED_CONTENT_TYPES = [
		'image/png',
		'image/jpg',
		'image/jpeg',
		'image/gif',
		'image/svg+xml',
		'image/webp'
	];

	public function __construct(
		protected IAppDataFactory $appDataFactory,
		protected IClientService $clientService,
		protected IURLGenerator $urlGenerator,
		protected LoggerInterface $logger,
		protected LinkReferenceProvider $linkReferenceProvider,
		protected IL10N $l10n,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'integration-youtube';
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): string {
		return $this->l10n->t('Youtube Link');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(): int {
		return 10;
	}

	/**
	 * @inheritDoc
	 */
	public function getIconUrl(): string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg')
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getSupportedSearchProviderIds(): array {
		return ['youtube-video-search', 'youtube-channel-search', 'youtube-playlist-search'];
	}

	/**
	 * @inheritDoc
	 */
	public function matchReference(string $referenceText): bool {
		return $this->getVideoId($referenceText) !== null;
	}

	/**
	 * @inheritDoc
	 */
	public function resolveReference(string $referenceText): ?IReference {
		if ($this->matchReference($referenceText)) {
			$reference = $this->linkReferenceProvider->resolveReference($referenceText);
			if ($reference === null) {
				return null;
			}

			$reference->setRichObject('integration_youtube', [
				'videoId' => $this->getVideoId($referenceText),
				// open-graph data
				'id' => $reference->getId(),
				'name' => $reference->getTitle(),
				'description' => $reference->getDescription(),
				'thumb' => $reference->getImageUrl(),
				'link' => $referenceText,
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
