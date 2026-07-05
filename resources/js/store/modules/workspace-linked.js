// store/modules/workspaces.js
import axios from 'axios'

export default {
    state: () => ({
        currentWorkspace: null,
        linkedWorkspaces: [],
        allWorkspaces: [],
        loading: false,
    }),

    getters: {
        workspaceLogo: (state) => state.currentWorkspace?.logo_url,
        workspaceLabel: (state) => state.currentWorkspace?.label,
        workspaceColor: (state) => state.currentWorkspace?.color || '#0d6efd',
        workspaceInitials: (state) => state.currentWorkspace?.initials || 'WS',
        workspaceName: (state) => state.currentWorkspace?.name || 'Workspace',
    },

    actions: {
        async loadLinkedWorkspaces() {
            this.loading = true
            try {
                const response = await axios.get(
                    `/api/workspaces/${this.uuid}/workspace/linked`
                )
                this.currentWorkspace = response.data.current
                this.linkedWorkspaces = response.data.linked
                return response.data
            } catch (error) {
                console.error('Load linked workspaces failed:', error)
                throw error
            } finally {
                this.loading = false
            }
        },

        async findWorkspaceByUuid(uuid) {
            try {
                const response = await axios.post(
                    `/api/workspaces/${this.uuid}/workspace/find-by-uuid`,
                    { uuid }
                )
                return response.data
            } catch (error) {
                console.error('Find workspace failed:', error)
                throw error
            }
        },


        async linkWorkspace(uuid) {
            try {
                const response = await axios.post(
                    `/api/workspaces/${this.uuid}/workspace/link`,
                    { uuid }
                )
                this.linkedWorkspaces = response.data.linked
                return response.data
            } catch (error) {
                console.error('Link workspace failed:', error)
                throw error
            }
        },

        async unlinkWorkspace(uuid) {
            try {
                const response = await axios.post(
                    `/api/workspaces/${this.uuid}/workspace/unlink`,
                    { uuid }
                )
                this.linkedWorkspaces = response.data.linked
                return response.data
            } catch (error) {
                console.error('Unlink workspace failed:', error)
                throw error
            }
        },

        async createAndLinkWorkspace(data) {
            try {
                const response = await axios.post(
                    `/api/workspaces/${this.uuid}/workspace/create-and-link`,
                    data
                )
                this.linkedWorkspaces = response.data.linked
                return response.data
            } catch (error) {
                console.error('Create and link failed:', error)
                throw error
            }
        },

        switchWorkspace(workspaceUuid) {
            window.location.href = `/workspace/${workspaceUuid}`
        },
    },
}
