import "bootstrap/dist/js/bootstrap.bundle.min.js";
import "admin-lte/dist/js/adminlte.min.js";

import { createApp } from "vue";
import axios from "axios";
import App from "./App.vue";
import router from "./router";
import { createPinia } from "pinia";
import piniaPluginPersistedstate from "pinia-plugin-persistedstate";
import { useUserStore } from "./stores/user";
import { apiVerify } from "./functions/api/auth";

const app = createApp(App);

const pinia = createPinia();
pinia.use(piniaPluginPersistedstate);
app.use(pinia);

app.use(router);

app.mount("#app");

const userStore = useUserStore();
// Set up Axios interceptor to add Authorization header dynamically
// Only when the token is available and not already set in the request
axios.interceptors.request.use((config) => {
    const token = userStore.getSanctumToken();
    if (token && !config.headers.Authorization) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});
router.beforeEach(async (to, from) => {
    if (to.meta.guarded === undefined) {
        return true;
    }

    try {
        const token = userStore.getSanctumToken();
        const response = await apiVerify(token);
        const { data } = response;
        userStore.setState(data.data);
    } catch (error) {
        if (error.response && error.response.status === 401) {
            userStore.reset();
        }
    }

    if (to.meta.guarded && !userStore.isAuthenticated) {
        return { name: "auth.signin" };
    }

    if (!to.meta.guarded && userStore.isAuthenticated) {
        return { name: "dashboard" };
    }
});
