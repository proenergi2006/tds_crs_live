// import { createApp } from "vue";
// import { createPinia } from "pinia";
// import axios from 'axios'
// import App from "./App.vue";
// import router from "./router";
// import "./assets/css/app.css";
// import { useAuthStore } from './stores/auth'
// import 'sweetalert2/dist/sweetalert2.min.css'
// import Vue3SignaturePad from 'vue3-signature-pad';



// // ganti sesuai URL Laravel Anda
// axios.defaults.baseURL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000'
// const token = localStorage.getItem('access_token')
// if (token) {
//   axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
// }


// const app = createApp(App);
// app.config.globalProperties.$axios = axios

// app.use(createPinia());
// app.use(router);

// // Install vue-signature-pad plugin
// app.component('Vue3SignaturePad', Vue3SignaturePad);

// app.mount("#app");

// const auth = useAuthStore()

// const idleEvents = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart']

// idleEvents.forEach(event => {
//   window.addEventListener(
//     event,
//     () => {
//       auth.resetIdleTimer()
//     },
//     { passive: true }
//   )
// })


// // setelah mount, fetch user jika ada token
// if (token) {
//     const auth = useAuthStore(pinia)
//     auth.fetchUser()
//   }

// // setelah mount, hook router untuk update title
// router.afterEach(to => {
//   // bungkus nama route atau meta.title dengan prefix "Halaman "
//   const base = to.meta.title || (typeof to.name === 'string' ? to.name : '')
//   document.getElementById('app-title')!.textContent = `Halaman ${base}`
// })


import { createApp } from "vue";
import { createPinia } from "pinia";
import axios from 'axios'
import App from "./App.vue";
import router from "./router";
import "./assets/css/app.css";
import { useAuthStore } from './stores/auth'
import 'sweetalert2/dist/sweetalert2.min.css'
import Vue3SignaturePad from 'vue3-signature-pad';



// ganti sesuai URL Laravel Anda
console.log("📌 axios baseURL:", axios.defaults.baseURL)
axios.defaults.baseURL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000'
const token = localStorage.getItem('access_token')
if (token) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
}

axios.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response?.status === 401) {
      // token expired / session expired
      const auth = useAuthStore(pinia)
      auth.forceLogout()
    }
    return Promise.reject(err)
  }
)

const app = createApp(App);
app.config.globalProperties.$axios = axios

const pinia = createPinia()
app.use(pinia)
//app.use(createPinia());
app.use(router);

// Install vue-signature-pad plugin
app.component('Vue3SignaturePad', Vue3SignaturePad);

app.mount("#app");


// setelah mount, fetch user jika ada token
if (token) {
    const auth = useAuthStore(pinia)
    auth.fetchUser()
  }

// setelah mount, hook router untuk update title
router.afterEach(to => {
  // bungkus nama route atau meta.title dengan prefix "Halaman "
  const base = to.meta.title || (typeof to.name === 'string' ? to.name : '')
  document.getElementById('app-title')!.textContent = `Halaman ${base}`
})

