import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

export function useTranslation() {
    const page = usePage()

    const locale = computed(() => page.props.locale || 'fr')
    const translations = computed(() => page.props.translations || {})

    /**
     * Translate a key with optional parameter interpolation
     * @param {string} key - The translation key
     * @param {object} params - Optional parameters for interpolation
     * @returns {string} - The translated string
     *
     * Usage:
     * t('Hello') => 'Bonjour'
     * t('Welcome, :name!', { name: 'John' }) => 'Bienvenue, John!'
     * t('{count} items', { count: 5 }) => '5 éléments'
     */
    const t = (key, params = {}) => {
        if (!key) return ''
        
        let translation = translations.value[key] || key
        
        // Ensure translation is a string
        if (typeof translation !== 'string') {
            translation = String(key)
        }

        // Replace :param and {param} style placeholders
        if (params && typeof params === 'object') {
            Object.keys(params).forEach(param => {
                const value = params[param]
                // Support both :param and {param} syntax
                translation = translation.replace(new RegExp(`:${param}`, 'g'), value)
                translation = translation.replace(new RegExp(`\\{${param}\\}`, 'g'), value)
            })
        }

        return translation
    }

    /**
     * Translate enum values
     * @param {string} category - The enum category (e.g., 'dev_status', 'priority')
     * @param {string} key - The enum value to translate
     * @returns {string} - The translated enum value
     *
     * Usage:
     * te('dev_status', 'In Development') => 'En développement'
     * te('priority', 'High') => 'Haute'
     */
    const te = (category, key) => {
        if (!key) return ''
        if (translations.value.enums && translations.value.enums[category]) {
            return translations.value.enums[category][key] || key
        }
        return key
    }

    /**
     * Check if a translation key exists
     * @param {string} key - The translation key
     * @returns {boolean} - Whether the key exists
     */
    const has = (key) => {
        return key in translations.value
    }

    /**
     * Format a date according to the current locale
     * @param {string|Date} date - The date to format
     * @param {object} options - Intl.DateTimeFormat options
     * @returns {string} - The formatted date
     */
    const formatDate = (date, options = {}) => {
        if (!date) return ''

        const dateObj = date instanceof Date ? date : new Date(date)
        if (isNaN(dateObj.getTime())) return ''

        const defaultOptions = {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            ...options
        }

        const localeCode = locale.value === 'fr' ? 'fr-FR' : 'en-US'
        return dateObj.toLocaleDateString(localeCode, defaultOptions)
    }

    /**
     * Format a date with time according to the current locale
     * @param {string|Date} date - The date to format
     * @param {object} options - Intl.DateTimeFormat options
     * @returns {string} - The formatted date and time
     */
    const formatDateTime = (date, options = {}) => {
        if (!date) return ''

        const dateObj = date instanceof Date ? date : new Date(date)
        if (isNaN(dateObj.getTime())) return ''

        const defaultOptions = {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            ...options
        }

        const localeCode = locale.value === 'fr' ? 'fr-FR' : 'en-US'
        return dateObj.toLocaleString(localeCode, defaultOptions)
    }

    /**
     * Format a number according to the current locale
     * @param {number} number - The number to format
     * @param {object} options - Intl.NumberFormat options
     * @returns {string} - The formatted number
     */
    const formatNumber = (number, options = {}) => {
        if (number === null || number === undefined) return ''

        const localeCode = locale.value === 'fr' ? 'fr-FR' : 'en-US'
        return new Intl.NumberFormat(localeCode, options).format(number)
    }

    /**
     * Format currency according to the current locale
     * @param {number} amount - The amount to format
     * @param {string} currency - The currency code (default: EUR)
     * @returns {string} - The formatted currency
     */
    const formatCurrency = (amount, currency = 'EUR') => {
        if (amount === null || amount === undefined) return ''

        const localeCode = locale.value === 'fr' ? 'fr-FR' : 'en-US'
        return new Intl.NumberFormat(localeCode, {
            style: 'currency',
            currency: currency
        }).format(amount)
    }

    /**
     * Get relative time (e.g., "2 days ago", "in 3 hours")
     * @param {string|Date} date - The date to compare
     * @returns {string} - The relative time string
     */
    const formatRelativeTime = (date) => {
        if (!date) return ''

        const dateObj = date instanceof Date ? date : new Date(date)
        if (isNaN(dateObj.getTime())) return ''

        const now = new Date()
        const diffInSeconds = Math.floor((dateObj - now) / 1000)
        const absSeconds = Math.abs(diffInSeconds)

        const localeCode = locale.value === 'fr' ? 'fr-FR' : 'en-US'
        const rtf = new Intl.RelativeTimeFormat(localeCode, { numeric: 'auto' })

        if (absSeconds < 60) {
            return rtf.format(diffInSeconds, 'second')
        } else if (absSeconds < 3600) {
            return rtf.format(Math.floor(diffInSeconds / 60), 'minute')
        } else if (absSeconds < 86400) {
            return rtf.format(Math.floor(diffInSeconds / 3600), 'hour')
        } else if (absSeconds < 2592000) {
            return rtf.format(Math.floor(diffInSeconds / 86400), 'day')
        } else if (absSeconds < 31536000) {
            return rtf.format(Math.floor(diffInSeconds / 2592000), 'month')
        } else {
            return rtf.format(Math.floor(diffInSeconds / 31536000), 'year')
        }
    }

    return {
        locale,
        t,
        te,
        has,
        formatDate,
        formatDateTime,
        formatNumber,
        formatCurrency,
        formatRelativeTime,
    }
}
