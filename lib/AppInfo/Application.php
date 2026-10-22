<?php

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
