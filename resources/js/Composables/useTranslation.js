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
     * Format planned_release field which can contain:
     * - Dates (7/15/2026, 4/15/2026)
     * - Version codes with dates (CV7.5(0715) where 0715 = July 15, 2025)
     * - Status text (TBD, deployed, Need PO, etc.)
     * @param {string} plannedRelease - The planned_release value
     * @returns {string} - The formatted value
     */
    const formatPlannedRelease = (plannedRelease) => {
        if (!plannedRelease) return ''
        
        // Check for version code pattern: CV7.5(0715)
        const versionMatch = plannedRelease.match(/^(.+)\((\d{4})\)(.*)$/)
        if (versionMatch) {
            const [, prefix, dateCode, suffix] = versionMatch
            const month = parseInt(dateCode.substring(0, 2), 10)
            const day = parseInt(dateCode.substring(2, 4), 10)
            
            if (month >= 1 && month <= 12 && day >= 1 && day <= 31) {
                const year = 2025 // Par défaut 2025 pour les codes de version
                const date = new Date(year, month - 1, day)
                
                if (!isNaN(date.getTime())) {
                    const formattedDate = formatDate(date)
                    return `${prefix.trim()} (${formattedDate})${suffix}`
                }
            }
        }
        
        // Try to parse as a regular date
        const dateObj = new Date(plannedRelease)
        if (!isNaN(dateObj.getTime())) {
            // Check if it looks like a date string
            if (/^\d{1,2}\/\d{1,2}\/\d{4}$/.test(plannedRelease) || 
                /^\d{4}-\d{2}-\d{2}/.test(plannedRelease) ||
                plannedRelease.includes('T')) {
                return formatDate(dateObj)
            }
        }
        
        // Return as-is if it's not a recognizable date format (TBD, deployed, etc.)
        return plannedRelease
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
        formatPlannedRelease,
        formatDateTime,
        formatNumber,
        formatCurrency,
        formatRelativeTime,
    }
}
