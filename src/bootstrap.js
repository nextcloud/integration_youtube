import { translate, translatePlural } from '@nextcloud/l10n'
import Vue from 'vue'

Vue.prototype.t = translate
Vue.prototype.n = translatePlural
Vue.prototype.OC = window.OC
Vue.prototype.OCA = window.OCA
