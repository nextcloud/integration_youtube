import Vue from 'vue'
import { translate, translatePlural } from '@nextcloud/l10n'
import { getRequestToken } from '@nextcloud/auth'
import { linkTo } from '@nextcloud/router'

Vue.prototype.t = translate
Vue.prototype.n = translatePlural
Vue.prototype.OC = window.OC
Vue.prototype.OCA = window.OCA

__webpack_nonce__ = btoa(getRequestToken()) // eslint-disable-line
__webpack_public_path__ = linkTo('integration_youtube', 'js/') // eslint-disable-line
