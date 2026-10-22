<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

return [
	'routes' => [
		/** @see \OCA\IntegrationYoutube\Controller\ConfigController::setAdminConfig */
		['name' => 'config#setAdminConfig', 'url' => '/admin-config', 'verb' => 'PUT'],
		/** @see \OCA\IntegrationYoutube\Controller\ConfigController::setAdminConfigWithPasswordConfirm */
		['name' => 'config#setAdminConfigWithPasswordConfirm', 'url' => '/admin-config-password-confirm', 'verb' => 'PUT'],
		/** @see \OCA\IntegrationYoutube\Controller\ConfigController::setUserConfig */
		['name' => 'config#setUserConfig', 'url' => '/user-config', 'verb' => 'PUT'],
	]
];
