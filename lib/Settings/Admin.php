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

namespace OCA\IntegrationYoutube\Settings;

use Exception;
use OCA\IntegrationYoutube\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Security\ICrypto;
use OCP\Settings\ISettings;
use Psr\Log\LoggerInterface;

class Admin implements ISettings {

	private IConfig $config;
	private IInitialState $initialStateService;
	private ICrypto $crypto;
	private LoggerInterface $logger;

	public function __construct(
		IConfig $config,
		IInitialState $initialStateService,
		ICrypto $crypto,
		LoggerInterface $logger
	) {
		$this->config = $config;
		$this->initialStateService = $initialStateService;
		$this->crypto = $crypto;
		$this->logger = $logger;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$token = $this->config->getAppValue(Application::APP_ID, 'token');
		$searchEnabled = $this->config->getAppValue(Application::APP_ID, 'search_enabled', 'false');

		try {
			if ($token !== '') {
				$token = $this->crypto->decrypt($token);
			}
		} catch (Exception $e) {
			// logger takes care not to leak the secret
			$this->logger->error('Failed to decrypt the api key', ['exception' => $e]);
			$token = '';
		}

		$adminConfig = [
			'token' => $token,
			'search_enabled' => $searchEnabled,
		];
		$this->initialStateService->provideInitialState('admin-config', $adminConfig);
		return new TemplateResponse(Application::APP_ID, 'adminSettings');
	}

	public function getSection(): string {
		return 'connected-accounts';
	}

	public function getPriority(): int {
		return 10;
	}
}
