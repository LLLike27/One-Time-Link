import { createApp } from 'vue'
import Antd from 'ant-design-vue'
import QrcodeVue from 'qrcode.vue'
import App from './App.vue'
import router from './router'
import pinia from './store'
import 'ant-design-vue/dist/reset.css'
import './assets/styles/global.less'

const app = createApp(App)

app.component('qrcode-vue', QrcodeVue)
app.use(pinia)
app.use(router)
app.use(Antd)

app.mount('#app')
