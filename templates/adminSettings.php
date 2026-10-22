<?php
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

$appId = \OCA\IntegrationYoutube\AppInfo\Application::APP_ID;
\OCP\Util::addScript($appId, $appId . '-adminSettings');

?>

<div id="integration_youtube_prefs"></div>
