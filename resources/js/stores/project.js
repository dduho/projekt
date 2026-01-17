import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useProjectStore = defineStore('project', () => {
    const projects = ref([]);
    const currentProject = ref(null);
    const loading = ref(false);
    const error = ref(null);
    const filters = ref({
        search: '',
        status: '',
        category_id: '',
        rag_status: ''
    });
    const pagination = ref({
        current_page: 1,
        per_page: 15,
        total: 0,
        last_page: 1
    });

    const filteredProjects = computed(() => {
        let result = projects.value;

        if (filters.value.search) {
            const search = filters.value.search.toLowerCase();
            result = result.filter(p => 
                p.name.toLowerCase().includes(search) ||
                p.project_code.toLowerCase().includes(search)
            );
        }

        if (filters.value.status) {
            result = result.filter(p => p.status === filters.value.status);
        }

        if (filters.value.category_id) {
            result = result.filter(p => p.category_id === filters.value.category_id);
        }

        if (filters.value.rag_status) {
            result = result.filter(p => p.rag_status === filters.value.rag_status);
        }

        return result;
    });

    const fetchProjects = async (page = 1) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.get('/api/projects', {
                params: {
                    page,
                    per_page: pagination.value.per_page,
                    ...filters.value
                }
            });
            projects.value = response.data.data;
            pagination.value = {
                current_page: response.data.meta.current_page,
                per_page: response.data.meta.per_page,
                total: response.data.meta.total,
                last_page: response.data.meta.last_page
            };
        } catch (err) {
            error.value = err.message;
        } finally {
            loading.value = false;
        }
    };

    const fetchProject = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.get(`/api/projects/${id}`);
            currentProject.value = response.data.data;
        } catch (err) {
            error.value = err.message;
        } finally {
            loading.value = false;
        }
    };

    const createProject = async (data) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.post('/api/projects', data);
            projects.value.unshift(response.data.data);
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to create project';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const updateProject = async (id, data) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.put(`/api/projects/${id}`, data);
            const index = projects.value.findIndex(p => p.id === id);
            if (index !== -1) {
                projects.value[index] = response.data.data;
            }
            if (currentProject.value?.id === id) {
                currentProject.value = response.data.data;
            }
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to update project';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const deleteProject = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            await axios.delete(`/api/projects/${id}`);
            projects.value = projects.value.filter(p => p.id !== id);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const setFilters = (newFilters) => {
        filters.value = { ...filters.value, ...newFilters };
    };

    const resetFilters = () => {
        filters.value = {
            search: '',
            status: '',
            category_id: '',
            rag_status: ''
        };
    };

    return {
        projects,
        currentProject,
        loading,
        error,
        filters,
        pagination,
        filteredProjects,
        fetchProjects,
        fetchProject,
        createProject,
        updateProject,
        deleteProject,
        setFilters,
        resetFilters
    };
});
