import { createApp } from 'vue'
import Lpath from './components/Lpath.vue'

const LpathApp = createApp(Lpath)
LpathApp.provide('Joomla', window.Joomla)
LpathApp.mount('#lpath-wrap')
