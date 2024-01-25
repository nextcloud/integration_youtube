<template>
	<div id="integration_youtube_prefs" class="section">
		<h2>
			<YoutubeIcon class="icon" />
			{{ t('integration_youtube', 'Youtube Integration') }}
		</h2>
		<NcCheckboxRadioSwitch
			:checked.sync="searchEnabled"
			@update:checked="onSearchChange">
			{{ t('integration_youtube', 'Enable searching for Youtube videos/channels/playlists') }}
		</NcCheckboxRadioSwitch>
		<br>
		<p v-if="searchEnabled" class="settings-hint">
			<InformationIcon :size="24" class="icon" />
			{{ t('integration_youtube', 'Warning, everything you type in the search bar will be sent to Youtube.') }}
		</p>
	</div>
</template>

<script>
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import { showSuccess, showError } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'

import InformationIcon from './icons/Information.vue'
import YoutubeIcon from './icons/Youtube.vue'

export default {
	name: 'PersonalSettings',

	components: {
		InformationIcon,
		NcCheckboxRadioSwitch,
		YoutubeIcon,
	},

	data() {
		return {
			state: loadState('integration_youtube', 'user-config'),
			// to prevent some browsers to fill fields with remembered passwords
			readonly: true,
			timeout: null,
		}
	},

	computed: {
		searchEnabled() {
			return this.state.search_enabled === 'true'
		},
	},

	methods: {
		onSearchChange(value) {
			this.state.search_enabled = value ? 'true' : 'false'
			this.onInput()
		},
		onInput() {
			this.saveOptions(this.state)
		},
		saveOptions(values) {
			const req = { values }
			const url = generateUrl('/apps/integration_youtube/user-config')
			axios.put(url, req).then((r) => {
				if (r.status >= 400) {
					throw new Error(r.statusText)
				}
				showSuccess(t('integration_youtube', 'Youtube user options saved'))
			}).catch((error) => {
				showError(
					t('integration_youtube', 'Failed to save Youtube user options')
					+ ': ' + (error.response ?? ''),
				)
				console.error(error)
			})
		},
	},
}
</script>

<style scoped lang="scss">
#integration_youtube_prefs {
	#integration_youtube_content {
		margin-left: 40px;
	}

	h2,
	.line,
	.settings-hint {
		display: flex;
		align-items: center;
		margin-left: 32px;
		.icon {
			margin-right: 4px;
		}
	}

	h2 .icon {
		margin-right: 8px;
	}

	.line {
		> label {
			width: 300px;
			display: flex;
			align-items: center;
		}
		> input {
			width: 300px;
		}
	}

	a {
		color: #006196;
	}

	ol {
		margin-left: 24px;
	}
}
</style>
