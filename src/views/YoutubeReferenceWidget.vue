<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
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
