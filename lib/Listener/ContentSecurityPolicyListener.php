<?php

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IntegrationYoutube\Listener;

use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;

/**
 * @template-implements IEventListener<Event>
 */
class ContentSecurityPolicyListener implements IEventListener {

	/**
	 * @inheritDoc
	 */
	public function handle(Event $event): void {
		if (!$event instanceof AddContentSecurityPolicyEvent) {
			return;
		}

		$policy = new ContentSecurityPolicy();
		$policy->addAllowedFrameDomain('https://www.youtube-nocookie.com');
		$policy->addAllowedImageDomain('https://i.ytimg.com'); // video thumbnails
		$policy->addAllowedImageDomain('https://yt3.ggpht.com'); // channel thumbnails
		$event->addPolicy($policy);
	}
}
