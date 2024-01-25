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

namespace OCA\IntegrationYoutube\Controller;

use Exception;
use OCA\IntegrationYoutube\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;

class ConfigController extends Controller {

	private IConfig $config;
	private IL10N $l;
	private LoggerInterface $logger;
	private ICrypto $crypto;
	private ?string $userId;

	public function __construct(
		string $appName,
		IRequest $request,
		IConfig $config,
		IL10N $l,
		LoggerInterface $logger,
		ICrypto $crypto,
		?string $userId,
	) {
		parent::__construct($appName, $request);

		$this->config = $config;
		$this->l = $l;
		$this->logger = $logger;
		$this->crypto = $crypto;
		$this->userId = $userId;
	}

	/**
	 * @param array $values
	 * @return DataResponse
	 */
	public function setAdminConfig(array $values): DataResponse {
		foreach ($values as $key => $value) {
			try {
				if ($key === 'token' && $value !== '') {
					$value = $this->crypto->encrypt($value);
				}
			} catch (Exception $e) {
				$this->config->setAppValue(Application::APP_ID, 'token', '');
				// logger takes care not to leak the secret
				$this->logger->error('Could not encrypt the Youtube api key', ['exception' => $e]);
				return new DataResponse(['message' => $this->l->t('Could not encrypt the Youtube api key')]);
			}

			$this->config->setAppValue(Application::APP_ID, $key, $value);
		}
		return new DataResponse(1);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param array $values
	 * @return DataResponse
	 */
	public function setUserConfig(array $values): DataResponse {
		foreach ($values as $key => $value) {
			$this->config->setUserValue($this->userId, Application::APP_ID, $key, $value);
		}
		return new DataResponse(1);
	}
}
