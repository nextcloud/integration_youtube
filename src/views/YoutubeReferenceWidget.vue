<!--
  - @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
  -
  - @author Julius Härtl <jus@bitgrid.net>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<NcReferenceWidget
		v-if="!interactive && reference"
		class="non-interactive-widget"
		:reference="reference" />
	<iframe
		v-else-if="interactive"
		width="100%"
		height="315"
		:src="youtubeEmbed"
		frameborder="0"
		allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
		referrerpolicy="strict-origin-when-cross-origin"
		allowfullscreen />
	<div v-else>
		{{ t('integration_youtube', 'No video reference found') }}
	</div>
</template>

<script>
import { NcReferenceWidget } from '@nextcloud/vue/components/NcRichText'

export default {
	name: 'YoutubeReferenceWidget',

	components: {
		NcReferenceWidget,
	},

	props: {
		richObject: {
			type: Object,
			default: null,
		},

		accessible: {
			type: Boolean,
			default: false,
		},

		interactive: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
		youtubeEmbed() {
			return 'https://www.youtube-nocookie.com/embed/' + this.richObject?.videoId
		},

		reference() {
			return {
				richObjectType: 'open-graph',
				richObject: this.richObject,
				accessible: this.accessible,
				openGraphObject: { ...this.richObject },
			}
		},
	},
}
</script>

<style scoped>
/* stylelint-disable-next-line selector-pseudo-class-no-unknown */
:global(.non-interactive-widget a) {
	border: unset !important;
	margin: unset !important;
}
</style>
