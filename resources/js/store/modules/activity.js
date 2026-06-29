// store/modules/activity.js
import axios from 'axios'

export default {
    state: () => ({
        logs: [],
        logsPagination: null,
        logsLoading: false,
        logsFilters: {
            entity_type: null,
            action: null,
            days: null,
            search: '',
            page: 1,
            per_page: 30,
        },
        stats: null,
    }),

    actions: {
        async loadActivityLogs(filters = {}) {
            this.logsLoading = true
            this.logsFilters = { ...this.logsFilters, ...filters }

            try {
                const params = new URLSearchParams()

                Object.entries(this.logsFilters).forEach(([key, value]) => {
                    if (value !== null && value !== '' && value !== undefined) {
                        params.append(key, value)
                    }
                })

                const response = await axios.get(
                    `/api/workspaces/${this.uuid}/activity-logs?${params.toString()}`
                )

                this.logs = response.data.data
                this.logsPagination = {
                    current_page: response.data.current_page,
                    last_page: response.data.last_page,
                    total: response.data.total,
                    per_page: response.data.per_page,
                }

                return response.data
            } catch (error) {
                console.error('Load activity logs failed:', error)
                throw error
            } finally {
                this.logsLoading = false
            }
        },

        async loadActivityStats() {
            try {
                const response = await axios.get(`/api/workspaces/${this.uuid}/activity-logs/stats`)
                this.stats = response.data
                return response.data
            } catch (error) {
                console.error('Load activity stats failed:', error)
                throw error
            }
        },

        async clearActivityLogs(days = 30) {
            try {
                const response = await axios.delete(
                    `/api/workspaces/${this.uuid}/activity-logs`,
                    { data: { older_than_days: days } }
                )
                await this.loadActivityLogs()
                await this.loadActivityStats()
                return response.data
            } catch (error) {
                console.error('Clear activity logs failed:', error)
                throw error
            }
        },

        setActivityFilter(key, value) {
            this.logsFilters[key] = value
            this.logsFilters.page = 1
            this.loadActivityLogs()
        },

        resetActivityFilters() {
            this.logsFilters = {
                entity_type: null,
                action: null,
                days: null,
                search: '',
                page: 1,
                per_page: 30,
            }
            this.loadActivityLogs()
        },
    },
}
