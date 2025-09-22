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
	 * @return array{results: SearchResultItem[], nextPageToken: string}
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
			throw new RuntimeException($response['error']);
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
			foreach ($response['items'] as $item) {
				$results[] = new SearchResultItem(
					$item['id'][$type . 'Id'],
					$item['snippet']['title'],
					$item['snippet']['description'] ?: $this->l10n->t('No description'),
					$item['snippet']['thumbnails']['default']['url'],
					$item['snippet']['channelTitle'],
					$item['snippet']['publishedAt'],
				);
			}
			return [
				'results' => $results,
				'nextPageToken' => $response['nextPageToken'],
			];
		} catch (Exception $e) {
			$this->logger->warning('Youtube API error', ['exception' => $e, 'app' => Application::APP_ID]);
			throw new RuntimeException($this->l10n->t('Youtube API error'));
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
		$token = $this->appConfig->getAppValueString(Application::APP_ID, 'token', lazy: true);

		try {
			if ($token === '') {
				return ['error' => $this->l10n->t('Youtube API Key not set')];
			}
			$token = $this->crypto->decrypt($token);
			$params['key'] = $token;
		} catch (Exception $e) {
			// logger takes care not to leak the secret
			$this->logger->error('Failed to decrypt the api key', ['exception' => $e]);
			return ['error' => $this->l10n->t('Could not decrypt the Youtube api key')];
		}

		try {
			$url = Application::YOUTUBE_API_ENDPOINT . $endPoint;
			$options = [
				'headers' => [
					'Content-Type' => 'application/json; charset=utf-8',
				],
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
			if ($jsonResponse) {
				return json_decode($body, true);
			}
			return $body;
		} catch (ServerException|ClientException $e) {
			$body = $e->getResponse()->getBody();
			$this->logger->warning('Youtube API error : ' . $body, ['app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		} catch (Exception|Throwable $e) {
			$this->logger->warning('Youtube API error', ['exception' => $e, 'app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}
}
