<template>
	<div id="integration_youtube_prefs" class="section">
		<h2>
			<YoutubeIcon class="icon" />
			{{ t('integration_youtube', 'Youtube Integration') }}
		</h2>
		<p class="settings-hint">
			{{ t('integration_youtube', 'Enter a Youtube Data API Key below to use the smart picker and the search.') }}
		</p>
		<br>
		<p class="settings-hint">
			{{ t('integration_youtube', "A key can be obtained from Google Developers's Console in three simple steps:") }}
		</p>
		<p class="settings-hint">
			<ol type="1">
				<li>{{ t('integration_youtube', 'Visit') }}{{ " " }}<a>https://console.cloud.google.com</a></li>
				<li>{{ t('integration_youtube', 'Search and enable "YouTube Data API v3"') }}</li>
				<li>{{ t('integration_youtube', 'Create credentials for Public Data usage') }}</li>
			</ol>
		</p>
		<p class="settings-hint">
			<InformationIcon :size="24" class="icon" />
			{{ t('integration_youtube', "Note: Youtube search has a quota cost of 100. The default daily quota for Youtube API is 10000, which gives you a total of 100 searches per day.") }}
		</p>
		<br>
		<div id="integration_youtube_content">
			<div class="line">
				<label for="youtube_token">
					<KeyIcon :size="20" class="icon" />
					{{ t('integration_youtube', 'Youtube API Key') }}
				</label>
				<input id="youtube_token"
					v-model="state.token"
					type="password"
					:readonly="readonly"
					:placeholder="t('integration_youtube', 'API Key')"
					@focus="readonly = false"
					@input="onInput">
			</div>
		</div>
	</div>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import { showSuccess, showError } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'

import InformationIcon from './icons/Information.vue'
import KeyIcon from './icons/Key.vue'
import YoutubeIcon from './icons/Youtube.vue'

let timeout
const debounce = (fn, ms = 2000) => {
	clearTimeout(timeout)
	timeout = setTimeout(fn, ms)
}

export default {
	name: 'AdminSettings',

	components: {
		InformationIcon,
		KeyIcon,
		YoutubeIcon,
	},

	data() {
		return {
			state: loadState('integration_youtube', 'admin-config'),
			// to prevent some browsers to fill fields with remembered passwords
			readonly: true,
			timeout: null,
		}
	},

	methods: {
		onInput() {
			debounce(() => {
				this.saveOptions(this.state)
			})
		},
		saveOptions(values) {
			const req = { values }
			const url = generateUrl('/apps/integration_youtube/admin-config')
			axios.put(url, req).then((r) => {
				if (r.status >= 400) {
					throw new Error(r.statusText)
				}
				showSuccess(t('integration_youtube', 'Youtube admin options saved'))
			}).catch((error) => {
				showError(
					t('integration_youtube', 'Failed to save Youtube admin options')
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
