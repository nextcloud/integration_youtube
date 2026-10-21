/* jshint esversion: 6 */

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import PersonalSettings from './components/PersonalSettings.vue'

const app = createApp(PersonalSettings)
app.mixin({ methods: { t, n } })
app.mount('#integration_youtube_prefs')
