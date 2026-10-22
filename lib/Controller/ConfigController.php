<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IntegrationYoutube\Controller;

use Exception;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;

class ConfigController extends Controller {

	private const SENSITIVE_KEYS = ['token'];
	private const PASSWORD_CONFIRMATION_KEYS = ['token'];

	public function __construct(
		string $appName,
		IRequest $request,
		protected IAppConfig $appConfig,
		protected IL10N $l,
		protected LoggerInterface $logger,
		protected ICrypto $crypto,
		protected ?string $userId,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @param array $values
	 * @return DataResponse
	 */
	public function setAdminConfig(array $values): DataResponse {
		try {
			foreach ($values as $key => $value) {
				if (in_array($key, self::SENSITIVE_KEYS, true) || in_array($key, self::PASSWORD_CONFIRMATION_KEYS, true)) {
					continue;
				}
				$this->appConfig->setAppValueString($key, $value, lazy: true);
			}
			return new DataResponse([]);
		} catch (Exception $e) {
			$this->logger->error('Could not save the admin config', ['exception' => $e]);
			return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @param array<string, string> $values
	 * @return DataResponse
	 */
	#[PasswordConfirmationRequired]
	public function setAdminConfigWithPasswordConfirm(array $values): DataResponse {
		try {
			foreach ($values as $key => $value) {
				if (!in_array($key, self::PASSWORD_CONFIRMATION_KEYS, true)) {
					continue;
				}

				if (!in_array($key, self::SENSITIVE_KEYS, true)) {
					$this->appConfig->setAppValueString($key, $value, lazy: true);
					continue;
				}

				try {
					if ($value !== '') {
						$value = $this->crypto->encrypt($value);
					}
					$this->appConfig->setAppValueString($key, $value, lazy: true);
				} catch (Exception $e) {
					$this->appConfig->setAppValueString('token', '', lazy: true);
					// logger takes care not to leak the secret
					$this->logger->error('Could not encrypt the YouTube API key', ['exception' => $e]);
					return new DataResponse(['error' => $this->l->t('Could not encrypt the YouTube API key')], Http::STATUS_INTERNAL_SERVER_ERROR);
				}
			}
			return new DataResponse([]);
		} catch (Exception $e) {
			$this->logger->error('Could not save the admin config', ['exception' => $e]);
			return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @param array $values
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function setUserConfig(array $values): DataResponse {
		try {
			foreach ($values as $key => $value) {
				$this->appConfig->setUserValue($this->userId, $key, $value);
			}
			return new DataResponse([]);
		} catch (Exception $e) {
			$this->logger->error('Could not save the user config', ['exception' => $e]);
			return new DataResponse(['error' => $this->l->t('Could not save the user config')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}
