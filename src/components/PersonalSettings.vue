<template>
	<div id="integration_youtube_prefs" class="settigs-section">
		<h2>
			<YoutubeIcon class="icon" :size="32" />
			{{ t('integration_youtube', 'Youtube Integration') }}
		</h2>
		<div id="integration_youtube_content">
			<NcCheckboxRadioSwitch
				:model-value="searchEnabled"
				@update:model-value="onSearchChange">
				{{ t('integration_youtube', 'Enable searching for Youtube videos/channels/playlists') }}
			</NcCheckboxRadioSwitch>
			<NcNoteCard v-if="searchEnabled" type="warning">
				{{ t('integration_youtube', 'Warning, everything you type in the search bar will be sent to Youtube.') }}
			</NcNoteCard>
		</div>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import YoutubeIcon from './icons/YoutubeIcon.vue'

export default {
	name: 'PersonalSettings',

	components: {
		NcCheckboxRadioSwitch,
		NcNoteCard,
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
				showError(t('integration_youtube', 'Failed to save Youtube user options')
					+ ': ' + (error.response ?? ''))
				console.error(error)
			})
		},
	},
}
</script>

<style scoped lang="scss">
#integration_youtube_prefs {
	#integration_youtube_content {
		margin-inline-start: 52px;
		max-width: 800px;
	}

	h2 {
		justify-content: start;
	}

	h2,
	.line,
	.settings-hint {
		display: flex;
		align-items: center;
		.icon {
			margin-inline-end: 4px;
		}
	}

	h2 .icon {
		margin: 0 8px 0 12px;
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
		margin-inline-start: 24px;
	}
}
</style>
