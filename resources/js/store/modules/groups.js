import axios from 'axios'

export default {
    state: () => ({
        groups: [],
        currentGroupId: null,
    }),

    getters: {
        currentGroup(state) {
            if (!state.currentGroupId) return null
            return state.groups.find(g => g.id === state.currentGroupId) || null
        },
        groupsCount(state) {
            return state.groups.length
        }
    },

    actions: {
        async loadGroups() {
            try {
                const res = await axios.get(`/api/workspaces/${this.uuid}/workspace-groups`)
                this.groups = res.data
                return res.data
            } catch (e) {
                console.error('Load groups failed', e)
            }
        },

        async createGroup(data) {
            const res = await axios.post(`/api/workspaces/${this.uuid}/workspace-groups`, data)
            await this.loadGroups()
            this.currentGroupId = res.data.id
            return res.data
        },

        async updateGroup(groupId, data) {
            const res = await axios.put(`/api/workspaces/${this.uuid}/workspace-groups/${groupId}`, data)
            await this.loadGroups()
            return res.data
        },

        async deleteGroup(groupId) {
            await axios.delete(`/api/workspaces/${this.uuid}/workspace-groups/${groupId}`)
            await this.loadGroups()
            if (this.currentGroupId === groupId) {
                this.currentGroupId = null
            }
        },

        async updateGroupWorkspaces(groupId, workspaceIds) {
            await axios.put(`/api/workspaces/${this.uuid}/workspace-groups/${groupId}/workspaces`, {
                workspace_ids: workspaceIds
            })
            await this.loadGroups()
        },

        async updateGroupWebhooks(groupId, webhooksData) {
            const res = await axios.post(`/api/workspaces/${this.uuid}/workspace-groups/${groupId}/webhooks`, {
                webhooks: webhooksData
            })
            // Перезагружаем группы, чтобы получить обновленные данные о вебхуках внутри workspaces
            await this.loadGroups()
            return res.data
        },

        async syncGroup(groupId, workspaceIds) {
            const res = await axios.post(`/api/workspaces/${this.uuid}/workspace-groups/${groupId}/sync`, {
                workspace_ids: workspaceIds
            })
            await this.loadGroups()
            return res.data.results
        },

        selectGroup(groupId) {
            this.currentGroupId = groupId
        },

        clearCurrentGroup() {
            this.currentGroupId = null
        }
    }
}
