/**
 * Composable to access the Ziggy route helper
 */
export function useRoute() {
    const routeFn = (...args) => {
        if (typeof window !== 'undefined' && typeof window.route === 'function') {
            return window.route(...args);
        }
        // Fallback: build URL from route name and params
        console.warn('Ziggy route helper not available, using fallback');
        return buildFallbackUrl(args[0], args[1]);
    };

    return { route: routeFn };
}

// Build a fallback URL from route name and params
function buildFallbackUrl(name, params = {}) {
    if (!name) return '/';
    
    // Common route mappings
    const routes = {
        'dashboard': '/dashboard',
        'projects.index': '/projects',
        'projects.create': '/projects/create',
        'projects.show': (p) => `/projects/${p.project || p.id || p}`,
        'projects.edit': (p) => `/projects/${p.project || p.id || p}/edit`,
        'projects.store': '/projects',
        'projects.update': (p) => `/projects/${p.project || p.id || p}`,
        'projects.destroy': (p) => `/projects/${p.project || p.id || p}`,
        'categories.index': '/categories',
        'categories.store': '/categories',
        'categories.update': (p) => `/categories/${p.category || p.id || p}`,
        'categories.destroy': (p) => `/categories/${p.category || p.id || p}`,
        'risks.index': '/risks',
        'risks.create': '/risks/create',
        'risks.store': '/risks',
        'risks.show': (p) => `/risks/${p.risk || p.id || p}`,
        'risks.update': (p) => `/risks/${p.risk || p.id || p}`,
        'risks.destroy': (p) => `/risks/${p.risk || p.id || p}`,
        'change-requests.index': '/changes',
        'change-requests.create': '/changes/create',
        'change-requests.store': '/changes',
        'change-requests.show': (p) => `/changes/${p.change_request || p.id || p}`,
        'change-requests.update': (p) => `/changes/${p.change_request || p.id || p}`,
        'change-requests.destroy': (p) => `/changes/${p.change_request || p.id || p}`,
        'changes.index': '/changes',
        'changes.store': '/changes',
        'changes.update': (p) => `/changes/${p.change || p.id || p}`,
        'changes.destroy': (p) => `/changes/${p.change || p.id || p}`,
        'users.index': '/users',
        'users.store': '/users',
        'users.update': (p) => `/users/${p.user || p.id || p}`,
        'users.destroy': (p) => `/users/${p.user || p.id || p}`,
        'phases.update-status': (p) => `/phases/${p.phase || p.id || p}/status`,
        'projects.phases.bulk-update': (p) => `/projects/${p.project || p.id || p}/phases`,
        'import.index': '/import',
        'import.store': '/import',
        'settings.index': '/settings',
        'profile.update': '/profile',
        'password.update': '/password',
        'login': '/login',
        'logout': '/logout',
    };
    
    const route = routes[name];
    if (!route) {
        console.warn(`Unknown route: ${name}`);
        return `/${name.replace(/\./g, '/')}`;
    }
    
    if (typeof route === 'function') {
        return route(params);
    }
    
    return route;
}

// Export route directly for use in script setup
// Always use our fallback since window.route from Ziggy doesn't work properly
export const route = (name, params = {}) => {
    return buildFallbackUrl(name, params);
};
