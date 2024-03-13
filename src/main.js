/*
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

import { registerWidget } from '@nextcloud/vue/dist/Components/NcRichText.js'
import { linkTo } from '@nextcloud/router'
import { getRequestToken } from '@nextcloud/auth'
import Vue from 'vue'

__webpack_nonce__ = btoa(getRequestToken()) // eslint-disable-line
__webpack_public_path__ = linkTo('integration_youtube', 'js/') // eslint-disable-line

registerWidget('integration_youtube', async (el, { richObjectType, richObject, accessible }) => {
	const { default: YtIframe } = await import(/* webpackChunkName: "integration-youtube-iframe-lazy" */'./views/Youtube.vue')
	const Widget = Vue.extend(YtIframe)
	new Widget({
		propsData: {
			richObjectType,
			richObject,
			accessible,
		},
	}).$mount(el)
})
