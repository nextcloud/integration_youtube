/* jshint esversion: 6 */

/**
 * Nextcloud - Integration Youtube
 *
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Anupam Kumar <kyteinsky@gmail.com>
 * @copyright Anupam Kumar 2024
 */

import Vue from 'vue'
import './bootstrap.js'
import AdminSettings from './components/AdminSettings.vue'

const VueAdminSettings = Vue.extend(AdminSettings)
new VueAdminSettings().$mount('#integration_youtube_prefs')
