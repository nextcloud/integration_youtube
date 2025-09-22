<template>
	<div id="integration_youtube_prefs" class="settings-section">
		<h2>
			<YoutubeIcon class="icon" :size="32" />
			{{ t('integration_youtube', 'Youtube Integration') }}
		</h2>
		<div id="integration_youtube_content">
			<p class="line settings-hint">
				{{ t('integration_youtube', 'Enter a Youtube Data API Key below to use the smart picker and the search.') }}
			</p>
			<p class="line settings-hint">
				{{ t('integration_youtube', "A key can be obtained from Google Developers's Console in three simple steps:") }}
			</p>
			<ol type="1">
				<li>{{ t('integration_youtube', 'Visit') }} <a href="https://console.cloud.google.com" target="_blank">https://console.cloud.google.com</a></li>
				<li>{{ t('integration_youtube', 'Search and enable "YouTube Data API v3"') }}</li>
				<li>{{ t('integration_youtube', 'Create credentials for Public Data usage') }}</li>
			</ol>
			<NcNoteCard type="info">
				{{ t('integration_youtube', "Note: Youtube search has a quota cost of 100. The default daily quota for Youtube API is 10000, which gives you a total of 100 searches per day.") }}
			</NcNoteCard>
			<div class="line">
				<label for="youtube_token">
					<KeyIcon :size="20" class="icon" />
					{{ t('integration_youtube', 'Youtube API Key') }}
				</label>
				<input
					id="youtube_token"
					v-model="state.token"
					type="password"
					:readonly="readonly"
					:placeholder="t('integration_youtube', 'API Key')"
					@focus="readonly = false"
					@input="onSensitiveInput">
				<NcLoadingIcon v-if="loading" :size="20" class="icon" />
			</div>
		</div>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { generateUrl } from '@nextcloud/router'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import KeyIcon from './icons/KeyIcon.vue'
import YoutubeIcon from './icons/YoutubeIcon.vue'

let timeout
/**
 *
 * @param {Function} fn function to debounce
 * @param {number} ms milliseconds to wait, default 2000
 */
function debounce(fn, ms = 2000) {
	clearTimeout(timeout)
	timeout = setTimeout(fn, ms)
}

export default {
	name: 'AdminSettings',

	components: {
		KeyIcon,
		YoutubeIcon,
		NcNoteCard,
		NcLoadingIcon,
	},

	data() {
		return {
			state: loadState('integration_youtube', 'admin-config'),
			// to prevent some browsers to fill fields with remembered passwords
			readonly: true,
			timeout: null,
			loading: false,
		}
	},

	methods: {
		onSensitiveInput() {
			debounce(() => {
				this.saveOptions(true)
			})
		},

		async saveOptions(confirm = false) {
			this.loading = true
			let url

			const values = { ...this.state }
			if (values.token === 'dummyToken') {
				delete values.token
			}
			if (Object.keys(values).length === 0) {
				return
			}

			if (confirm) {
				await confirmPassword()
				url = generateUrl('/apps/integration_youtube/admin-config-password-confirm')
			} else {
				url = generateUrl('/apps/integration_youtube/admin-config')
			}

			axios.put(url, { values }).then((r) => {
				if (r.status >= 400) {
					throw new Error(r.statusText)
				}
				showSuccess(t('integration_youtube', 'Youtube admin options saved'))
			}).catch((error) => {
				showError(t('integration_youtube', 'Failed to save Youtube admin options')
					+ ': ' + (error.response ?? ''))
				console.error(error)
			}).finally(() => {
				this.loading = false
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

	.settings-hint {
		margin-top: 0 !important;
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
