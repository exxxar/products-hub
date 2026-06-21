import axios from 'axios'

export default {
    state: () => ({
        webhooks: [],
    }),

    getters: {
        autoSyncWebhooks(state) {
            return state.webhooks.filter(w => w.sync_on_update)
        },
    },

    actions: {
        setWebhooks(webhooks) {
            this.webhooks = webhooks || []
        },

        async loadWebhooks() {
            try {
                const response = await axios.get(`/api/workspaces/${this.uuid}/webhooks`)
                this.webhooks = response.data
                return response.data
            } catch (error) {
                console.error('Load webhooks failed:', error)
                throw error
            }
        },

        async saveWebhook(webhookData, id = null) {
            try {
                let response

                if (id) {
                    response = await axios.put(`/api/workspaces/${this.uuid}/webhooks/${id}`, webhookData)
                    const index = this.webhooks.findIndex(w => w.id === id)
                    if (index > -1) {
                        this.webhooks[index] = response.data
                    }
                } else {
                    response = await axios.post(`/api/workspaces/${this.uuid}/webhooks`, webhookData)
                    this.webhooks.push(response.data)
                }

                return response.data
            } catch (error) {
                console.error('Save webhook failed:', error)
                throw error
            }
        },

        async deleteWebhook(id) {
            try {
                await axios.delete(`/api/workspaces/${this.uuid}/webhooks/${id}`)
                this.webhooks = this.webhooks.filter(w => w.id !== id)
            } catch (error) {
                console.error('Delete webhook failed:', error)
                throw error
            }
        },

        async syncWebhook(webhookId, productId = null) {
            try {
                const url = productId
                    ? `/api/workspaces/${this.uuid}/webhooks/${webhookId}/sync?product_id=${productId}`
                    : `/api/workspaces/${this.uuid}/webhooks/${webhookId}/sync`

                const response = await axios.post(url)

                const index = this.webhooks.findIndex(w => w.id === webhookId)
                if (index > -1) {
                    this.webhooks[index] = response.data.webhook
                }

                return response.data
            } catch (error) {
                console.error('Sync webhook failed:', error)
                throw error
            }
        },

        async syncAllWebhooks(productId = null) {
            try {
                const url = productId
                    ? `/api/workspaces/${this.uuid}/webhooks/sync-all?product_id=${productId}`
                    : `/api/workspaces/${this.uuid}/webhooks/sync-all`

                const response = await axios.post(url)

                response.data.results.forEach(result => {
                    const index = this.webhooks.findIndex(w => w.id === result.id)
                    if (index > -1) {
                        this.webhooks[index] = {
                            ...this.webhooks[index],
                            last_synced_at: result.last_synced_at,
                            last_status: result.last_status
                        }
                    }
                })

                return response.data
            } catch (error) {
                console.error('Sync all webhooks failed:', error)
                throw error
            }
        },

        async triggerWebhooksForProduct(product) {
            const autoSyncWebhooks = this.autoSyncWebhooks
            if (autoSyncWebhooks.length === 0) return

            autoSyncWebhooks.forEach(webhook => {
                this.syncWebhook(webhook.id, product.id).catch(err => {
                    console.error(`Auto-sync webhook ${webhook.id} failed:`, err)
                })
            })
        },
    },
}
