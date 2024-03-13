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

use Fusonic\OpenGraph\Consumer;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\LimitStream;
use GuzzleHttp\Psr7\Utils;
use OCA\IntegrationYoutube\AppInfo\Application;
use OCP\Collaboration\Reference\ADiscoverableReferenceProvider;
use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\ISearchableReferenceProvider;
use OCP\Collaboration\Reference\Reference;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\NotFoundException;
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

	private IAppDataFactory $appDataFactory;
	private IClientService $clientService;
	private IURLGenerator $urlGenerator;
	private LoggerInterface $logger;
	private IL10N $l10n;

	public function __construct(
		IAppDataFactory $appDataFactory,
		IClientService $clientService,
		IURLGenerator $urlGenerator,
		LoggerInterface $logger,
		IL10N $l10n
	) {
		$this->appDataFactory = $appDataFactory;
		$this->clientService = $clientService;
		$this->urlGenerator = $urlGenerator;
		$this->logger = $logger;
		$this->l10n = $l10n;
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
			$reference = new Reference($referenceText);
			$this->populateReferenceOpengraph($reference);
			$reference->setRichObject('integration_youtube', [
				'videoId' => $this->getVideoId($referenceText),
			]);
			return $reference;
		}

		return null;
	}

	private function populateReferenceOpengraph(Reference $reference): void {
		$client = $this->clientService->newClient();
		try {
			$headResponse = $client->head($reference->getId(), [ 'timeout' => 10 ]);
		} catch (\Exception $e) {
			$this->logger->debug('Failed to perform HEAD request to get target metadata', ['exception' => $e]);
			return;
		}

		$linkContentLength = $headResponse->getHeader('Content-Length');
		if (is_numeric($linkContentLength) && (int) $linkContentLength > self::MAX_CONTENT_LENGTH) {
			$this->logger->debug('Skip resolving links pointing to content length > 5 MiB');
			return;
		}

		$linkContentType = $headResponse->getHeader('Content-Type');
		$expectedContentTypeRegex = '/^text\/html;?/i';

		// check the header begins with the expected content type
		if (!preg_match($expectedContentTypeRegex, $linkContentType)) {
			$this->logger->debug('Skip resolving links pointing to content type that is not "text/html"');
			return;
		}

		try {
			$response = $client->get($reference->getId(), [ 'timeout' => 10 ]);
		} catch (\Exception $e) {
			$this->logger->debug('Failed to fetch link for obtaining open graph data', ['exception' => $e]);
			return;
		}

		$responseBody = (string)$response->getBody();

		// OpenGraph handling
		$consumer = new Consumer();
		$consumer->useFallbackMode = true;
		$object = $consumer->loadHtml($responseBody);

		$reference->setUrl($reference->getId());

		if ($object->title) {
			$reference->setTitle($object->title);
		}

		if ($object->description) {
			$reference->setDescription($object->description);
		}

		if ($object->images) {
			try {
				$host = parse_url($object->images[0]->url, PHP_URL_HOST);
				if ($host === false || $host === null) {
					$this->logger->warning('Could not detect host of open graph image URI for ' . $reference->getId());
					return;
				}

				$appData = $this->appDataFactory->get('core');
				try {
					$folder = $appData->getFolder('opengraph');
				} catch (NotFoundException $e) {
					$folder = $appData->newFolder('opengraph');
				}

				$response = $client->get($object->images[0]->url, ['timeout' => 10]);
				$contentType = $response->getHeader('Content-Type');
				$contentLength = $response->getHeader('Content-Length');

				if (in_array($contentType, self::ALLOWED_CONTENT_TYPES, true) && $contentLength < self::MAX_CONTENT_LENGTH) {
					$stream = Utils::streamFor($response->getBody());
					$bodyStream = new LimitStream($stream, self::MAX_CONTENT_LENGTH, 0);
					$reference->setImageContentType($contentType);
					$folder->newFile(md5($reference->getId()), $bodyStream->getContents());
					$reference->setImageUrl($this->urlGenerator->linkToRouteAbsolute('core.Reference.preview', ['referenceId' => md5($reference->getId())]));
				}
			} catch (GuzzleException $e) {
				$this->logger->info('Failed to fetch and store the open graph image for ' . $reference->getId(), ['exception' => $e]);
			} catch (\Throwable $e) {
				$this->logger->error('Failed to fetch and store the open graph image for ' . $reference->getId(), ['exception' => $e]);
			}
		}
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
