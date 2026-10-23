<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IntegrationYoutube\Service;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use OCA\IntegrationYoutube\AppInfo\Application;
use OCA\IntegrationYoutube\Type\SearchResultItem;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IL10N;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class YoutubeAPIService {

	protected IClient $client;

	public function __construct(
		protected IAppConfig $appConfig,
		protected LoggerInterface $logger,
		protected IL10N $l10n,
		protected ICrypto $crypto,
		IClientService $clientService,
	) {
		$this->client = $clientService->newClient();
	}

	/**
	 * Calls the Youtube API to get the search results
	 *
	 * @param string $query
	 * @param string $type video|channel|playlist
	 * @param int $limit
	 * @param ?string $nextPageToken
	 * @return array{results: SearchResultItem[], nextPageToken: ?string}
	 * @throws RuntimeException
	 */
	public function search(string $query, string $type, int $limit = 25, ?string $nextPageToken = null): array {
		$params = [
			'part' => 'snippet',
			'q' => $query,
			'maxResults' => $limit,
			'type' => $type,
			'fields' => "nextPageToken,items(id/{$type}Id,snippet(title,description,thumbnails/default/url,channelTitle,publishedAt))",
		];

		if (!is_null($nextPageToken)) {
			$params['pageToken'] = $nextPageToken;
		}

		$response = $this->request('/search', $params);

		if (isset($response['error'])) {
			if (!is_string($response['error'])) {
				// an error that is not text, such as an error object: not one of the errors request() returns
				$this->logger->warning('YouTube API answered with an error', ['app' => Application::APP_ID, 'response' => json_encode($response['error'])]);
				throw new RuntimeException($this->l10n->t('YouTube API error'));
			}
			throw new RuntimeException($response['error']);
		}
		if (!is_array($response) || (array_key_exists('items', $response) && !is_array($response['items']))) {
			$this->logger->warning('YouTube API answered without a list of results', ['app' => Application::APP_ID]);
			throw new RuntimeException($this->l10n->t('YouTube API error'));
		}

		/* example response for type 'video'
		{
			"nextPageToken": "CBkQAA",
			"id": {
				"videoId": "mzXKZf1pWo4"
			},
			"snippet": {
				"publishedAt": "2024-01-18T02:33:30Z",
				"title": "Hello Are You There? (Original)  iphone cat EP.31",
				"description": "iphone #iphone14 #hello #areyouthere #cat.",
				"thumbnails": {
					"default": {
						"url": "https://i.ytimg.com/vi/mzXKZf1pWo4/mqdefault.jpg"
					}
				},
				"channelTitle": "MIBO"
			}
		} */
		try {
			$results = [];
			foreach ($response['items'] ?? [] as $item) {
				$id = $item['id'][$type . 'Id'] ?? null;
				$snippet = $item['snippet'] ?? [];
				$title = $snippet['title'] ?? null;
				$thumbnailUrl = $snippet['thumbnails']['default']['url'] ?? null;
				$channelTitle = $snippet['channelTitle'] ?? null;
				$publishedAt = $snippet['publishedAt'] ?? null;
				// left out: a result without an ID of the searched type, such as a channel among videos, which the fields
				// filter leaves without one, a result without a title, thumbnail, channel title or date, the fields a search
				// result cannot do without, and a result with a date text that cannot be read; empty text counts as missing
				if (in_array($id, [null, ''], true) || in_array($title, [null, ''], true) || in_array($thumbnailUrl, [null, ''], true)
					|| in_array($channelTitle, [null, ''], true) || $publishedAt === null || strtotime($publishedAt) === false) {
					continue;
				}
				$description = $snippet['description'] ?? '';
				$results[] = new SearchResultItem(
					$id,
					// the API sends the titles of videos and playlists HTML-escaped, as in "It&#39;s", and channel titles
					// and all descriptions as they are
					$type === 'channel' ? $title : html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
					$description !== '' ? $description : $this->l10n->t('No description'),
					$thumbnailUrl,
					$channelTitle,
					$publishedAt,
				);
			}
			return [
				'results' => $results,
				// there is none on the last page
				'nextPageToken' => $response['nextPageToken'] ?? null,
			];
		} catch (Throwable $e) {
			$this->logger->warning('YouTube API error', ['exception' => $e, 'app' => Application::APP_ID]);
			throw new RuntimeException($this->l10n->t('YouTube API error'));
		}
	}

	/**
	 * @param string $endPoint
	 * @param array $params
	 * @param string $method
	 * @param bool $jsonResponse
	 * @return array|mixed|resource|string|string[]
	 */
	protected function request(
		string $endPoint,
		array $params = [],
		string $method = 'GET',
		bool $jsonResponse = true,
	) {
		$token = $this->appConfig->getAppValueString('token', lazy: true);

		try {
			if ($token === '') {
				return ['error' => $this->l10n->t('YouTube API Key not set')];
			}
			$token = $this->crypto->decrypt($token);
		} catch (Exception $e) {
			// logger takes care not to leak the secret
			$this->logger->error('Failed to decrypt the API key', ['exception' => $e]);
			return ['error' => $this->l10n->t('Could not decrypt the YouTube API key')];
		}

		try {
			$url = Application::YOUTUBE_API_ENDPOINT . $endPoint;
			$options = [
				'headers' => [
					'Content-Type' => 'application/json; charset=utf-8',
					// a header rather than a URL parameter, so the error messages that quote the URL do not carry the key
					'X-Goog-Api-Key' => $token,
				],
				// and the header goes to YouTube only, which does not redirect these requests
				'allow_redirects' => false,
			];

			if (count($params) > 0) {
				if ($method === 'GET') {
					// manage array parameters
					$paramsContent = '';
					foreach ($params as $key => $value) {
						if (is_array($value)) {
							foreach ($value as $oneArrayValue) {
								$paramsContent .= $key . '[]=' . urlencode($oneArrayValue) . '&';
							}
							unset($params[$key]);
						}
					}
					$paramsContent .= http_build_query($params);

					$url .= '?' . $paramsContent;
				} else {
					$options['body'] = $params;
				}
			}

			if ($method === 'GET') {
				$response = $this->client->get($url, $options);
			} elseif ($method === 'POST') {
				$response = $this->client->post($url, $options);
			} elseif ($method === 'PUT') {
				$response = $this->client->put($url, $options);
			} elseif ($method === 'DELETE') {
				$response = $this->client->delete($url, $options);
			} else {
				return ['error' => $this->l10n->t('Bad HTTP method')];
			}
			$body = $response->getBody();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('Bad credentials')];
			}
			if ($respCode >= 300) {
				// a redirect, which is not followed
				$this->logger->warning('YouTube API error: HTTP ' . $respCode . ', a redirect that is not followed', ['app' => Application::APP_ID]);
				return ['error' => $this->l10n->t('YouTube API error')];
			}
			if ($jsonResponse) {
				return json_decode($body, true);
			}
			return $body;
		} catch (ServerException|ClientException $e) {
			// not the exception itself: the arguments in its trace hold the request options, and the key with them
			$response = $e->getResponse();
			$body = (string)$response->getBody();
			$this->logger->warning('YouTube API error: HTTP ' . $response->getStatusCode(), ['app' => Application::APP_ID, 'response' => $body]);
			return ['error' => $e->getMessage()];
		} catch (Exception|Throwable $e) {
			$message = $this->withoutKey($e->getMessage(), $token);
			$this->logger->warning('YouTube API request failed: ' . get_class($e) . ': ' . $message, ['app' => Application::APP_ID]);
			return ['error' => $message];
		}
	}

	/**
	 * The text without the API key. The HTTP client quotes a header value it refuses, and a key stored with stray
	 * whitespace, such as a trailing line break, is one; it trims spaces and tabs first, so look for the bare key.
	 */
	private function withoutKey(string $text, string $key): string {
		$key = trim($key);
		return $key === '' ? $text : str_replace($key, '***', $text);
	}
}
