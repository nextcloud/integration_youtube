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

namespace OCA\IntegrationYoutube\AppInfo;

use OCA\IntegrationYoutube\Listener\ContentSecurityPolicyListener;
use OCA\IntegrationYoutube\Listener\YoutubeReferenceListener;
use OCA\IntegrationYoutube\Reference\YoutubeReferenceProvider;
use OCA\IntegrationYoutube\Search\YoutubeChannelSearchProvider;
use OCA\IntegrationYoutube\Search\YoutubePlaylistSearchProvider;
use OCA\IntegrationYoutube\Search\YoutubeVideoSearchProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Collaboration\Reference\RenderReferenceEvent;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'integration_youtube';
	public const YOUTUBE_API_ENDPOINT = 'https://www.googleapis.com/youtube/v3';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerReferenceProvider(YoutubeReferenceProvider::class);
		$context->registerEventListener(RenderReferenceEvent::class, YoutubeReferenceListener::class);

		$context->registerEventListener(AddContentSecurityPolicyEvent::class, ContentSecurityPolicyListener::class);

		$context->registerSearchProvider(YoutubeVideoSearchProvider::class);
		$context->registerSearchProvider(YoutubeChannelSearchProvider::class);
		$context->registerSearchProvider(YoutubePlaylistSearchProvider::class);
	}

	public function boot(IBootContext $context): void {
	}
}
