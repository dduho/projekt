import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const loading = ref(false);
    const error = ref(null);

    const isAuthenticated = computed(() => !!user.value);
    const userRole = computed(() => user.value?.role || null);

    const fetchUser = async () => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.get('/api/user');
            user.value = response.data.data;
        } catch (err) {
            error.value = err.message;
            user.value = null;
        } finally {
            loading.value = false;
        }
    };

    const login = async (credentials) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.post('/api/login', credentials);
            user.value = response.data.data.user;
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Login failed';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const logout = async () => {
        loading.value = true;
        error.value = null;
        try {
            await axios.post('/api/logout');
            user.value = null;
        } catch (err) {
            error.value = err.message;
        } finally {
            loading.value = false;
        }
    };

    return {
        user,
        loading,
        error,
        isAuthenticated,
        userRole,
        fetchUser,
        login,
        logout
    };
});
