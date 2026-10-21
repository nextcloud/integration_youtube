/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { registerWidget } from '@nextcloud/vue/functions/registerReference'

registerWidget('integration_youtube', async (el, { richObject, accessible, interactive }) => {
	const { createApp } = await import('vue')
	const { default: YtIframe } = await import('./views/YoutubeReferenceWidget.vue')
	const app = createApp(YtIframe, { richObject, accessible, interactive })
	app.mixin({ methods: { t, n } })
	app.mount(el)
})
