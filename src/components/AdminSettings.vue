<template>
	<div id="integration_youtube_prefs" class="settings-section">
		<h2>
			<YoutubeIcon class="icon" :size="32" />
			{{ t('integration_youtube', 'YouTube Integration') }}
		</h2>
		<div id="integration_youtube_content">
			<p class="line gap-bottom">
				{{ t('integration_youtube', 'Enter a YouTube Data API Key below to use the smart picker and the search.') }}
			</p>
			<p class="line">
				{{ t('integration_youtube', "A key can be obtained from Google Developers's Console in three simple steps:") }}
			</p>
			<div class="line">
				<ol type="1">
					<li>{{ t('integration_youtube', 'Visit') }} <a href="https://console.cloud.google.com" target="_blank">https://console.cloud.google.com</a></li>
					<li>{{ t('integration_youtube', 'Search and enable "YouTube Data API v3"') }}</li>
					<li>{{ t('integration_youtube', 'Create credentials for Public Data usage') }}</li>
				</ol>
			</div>
			<NcNoteCard type="info">
				{{ t('integration_youtube', "Note: YouTube search has a quota cost of 100. The default daily quota for YouTube API is 10000, which boils down to a total of 100 searches per day.") }}
			</NcNoteCard>
			<div class="line">
				<NcTextField
					v-model="state.token"
					class="token-field"
					type="password"
					:label="t('integration_youtube', 'YouTube API Key')"
					:readonly="readonly"
					:show-trailing-button="state.token !== ''"
					@trailing-button-click="state.token = ''; onSensitiveInput()"
					@focus="readonly = false"
					@update:model-value="onSensitiveInput">
					<template #icon>
						<KeyIcon :size="20" class="icon" />
					</template>
				</NcTextField>
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
import NcTextField from '@nextcloud/vue/components/NcTextField'
import KeyIcon from './icons/KeyIcon.vue'
import YoutubeIcon from './icons/YoutubeIcon.vue'

export default {
	name: 'AdminSettings',

	components: {
		KeyIcon,
		YoutubeIcon,
		NcNoteCard,
		NcLoadingIcon,
		NcTextField,
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
		debounce(fn, ms = 2000) {
			clearTimeout(this.timeout)
			this.timeout = setTimeout(fn, ms)
		},

		onSensitiveInput() {
			this.debounce(() => {
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

			try {
				if (confirm) {
					await confirmPassword()
					url = generateUrl('/apps/integration_youtube/admin-config-password-confirm')
				} else {
					url = generateUrl('/apps/integration_youtube/admin-config')
				}

				const r = await axios.put(url, { values })
				if (r.status >= 400) {
					throw new Error(r.statusText)
				}
				showSuccess(t('integration_youtube', 'YouTube admin options saved'))
			} catch (error) {
				showError(t('integration_youtube', 'Failed to save YouTube admin options')
					+ ': ' + (error.response?.data?.error ?? error.message ?? ''))
				console.error(error)
			} finally {
				this.loading = false
			}
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

	.settings-hint, .token-field {
		margin-top: 0 !important;
	}

	.line {
		> label {
			width: 300px;
			display: flex;
			align-items: center;
		}
		> .token-field {
			width: 300px;
		}
	}

	.gap-bottom {
		margin-bottom: 8px;
	}

	a {
		color: #006196;
	}

	ol {
		margin-inline-start: 24px;
	}
}
</style>
