import axios from 'axios'

export default {
    actions: {
        async importProducts(file) {
            const formData = new FormData()
            formData.append('file', file)

            try {
                const response = await axios.post(`/api/workspaces/${this.uuid}/import`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                    timeout: 120000
                })

                await this.loadWorkspace()
                return response.data
            } catch (error) {
                console.error('Import failed:', error)

                if (error.response?.data?.errors) {
                    return {
                        success: false,
                        errors: error.response.data.errors,
                        message: error.response.data.message
                    }
                }

                throw error
            }
        },

        async downloadImportTemplate() {
            try {
                const response = await axios.get(`/api/workspaces/${this.uuid}/import/template`, {
                    responseType: 'blob'
                })

                const url = window.URL.createObjectURL(new Blob([response.data]))
                const link = document.createElement('a')
                link.href = url
                link.setAttribute('download', 'import-template.xlsx')
                document.body.appendChild(link)
                link.click()
                link.remove()
                window.URL.revokeObjectURL(url)
            } catch (error) {
                console.error('Download template failed:', error)
                throw error
            }
        },

        async exportWorkspace() {
            try {
                const response = await axios.get(`/api/workspaces/${this.uuid}/export`, {
                    responseType: 'blob'
                })

                const disposition = response.headers['content-disposition']
                let filename = `workspace-${this.uuid}-${new Date().toISOString().slice(0, 10)}.xlsx`

                if (disposition && disposition.indexOf('filename=') !== -1) {
                    filename = disposition.split('filename=')[1].replace(/"/g, '')
                }

                const url = window.URL.createObjectURL(new Blob([response.data]))
                const link = document.createElement('a')
                link.href = url
                link.setAttribute('download', filename)
                document.body.appendChild(link)
                link.click()
                link.remove()
                window.URL.revokeObjectURL(url)

                return true
            } catch (error) {
                console.error('Export failed:', error)
                throw error
            }
        },

        // === VK интеграция ===

        async getVKAuthLink() {
            try {
                const response = await axios.get(`/api/workspaces/${this.uuid}/vk/auth-link`)
                return response.data.url
            } catch (error) {
                console.error('Failed to get VK auth link:', error)
                throw error
            }
        },

        async exportToVk() {
            try {
                const authUrl = await this.getVKAuthLink()
                window.open(authUrl, '_blank', 'width=800,height=600')
                return authUrl
            } catch (error) {
                console.error('Export to VK failed:', error)
                throw error
            }
        },
    },
}
