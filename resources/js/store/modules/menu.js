import axios from 'axios'

export default {
    state: () => ({
        menuConfig: null,
        menuPreview: null,
        menuLoading: false,
        menuDefaultImages: [],
    }),

    actions: {
        async loadMenuConfig() {
            try {
                const response = await axios.get(`/api/workspaces/${this.uuid}/menu/config`)
                this.menuConfig = response.data
                return response.data
            } catch (error) {
                console.error('Load menu config failed:', error)
                throw error
            }
        },

        async saveMenuConfig(config) {
            try {
                const response = await axios.post(`/api/workspaces/${this.uuid}/menu/config`, config)
                this.menuConfig = response.data
                return response.data
            } catch (error) {
                console.error('Save menu config failed:', error)
                throw error
            }
        },

        async uploadMenuLogo(file) {
            const formData = new FormData()
            formData.append('logo', file)

            try {
                const response = await axios.post(`/api/workspaces/${this.uuid}/menu/logo`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                })

                if (this.menuConfig) {
                    this.menuConfig.logo_path = response.data.logo_path
                }

                return response.data
            } catch (error) {
                console.error('Upload menu logo failed:', error)
                throw error
            }
        },

        async generateMenuPdf() {
            try {
                const response = await axios.get(`/api/workspaces/${this.uuid}/menu/generate`, {
                    responseType: 'blob'
                })

                const url = window.URL.createObjectURL(new Blob([response.data]))
                const link = document.createElement('a')
                link.href = url

                const config = this.menuConfig
                const filename = `${config?.name || 'menu'}-${new Date().toISOString().slice(0, 10)}.pdf`

                link.setAttribute('download', filename)
                document.body.appendChild(link)
                link.click()
                link.remove()
                window.URL.revokeObjectURL(url)

                return true
            } catch (error) {
                console.error('Generate menu PDF failed:', error)
                throw error
            }
        },

        async getMenuPreview() {
            try {
                const response = await axios.get(`/api/workspaces/${this.uuid}/menu/preview`)
                this.menuPreview = response.data
                return response.data
            } catch (error) {
                console.error('Get menu preview failed:', error)
                throw error
            }
        },

        async loadMenuDefaultImages() {
            try {
                const response = await axios.get(`/api/workspaces/${this.uuid}/menu/default-images`)
                this.menuDefaultImages = response.data
                return response.data
            } catch (error) {
                console.error('Load menu default images failed:', error)
                throw error
            }
        },

        async uploadMenuDefaultImage(file, name = null) {
            const formData = new FormData()
            formData.append('image', file)
            if (name) formData.append('name', name)

            try {
                const response = await axios.post(
                    `/api/workspaces/${this.uuid}/menu/default-images`,
                    formData,
                    { headers: { 'Content-Type': 'multipart/form-data' } }
                )

                this.menuDefaultImages.push(response.data)
                return response.data
            } catch (error) {
                console.error('Upload menu default image failed:', error)
                throw error
            }
        },

        async deleteMenuDefaultImage(imageId) {
            try {
                await axios.delete(`/api/workspaces/${this.uuid}/menu/default-images/${imageId}`)
                this.menuDefaultImages = this.menuDefaultImages.filter(img => img.id !== imageId)

                // Если удалили выбранную дефолтную картинку — сбрасываем в конфиге
                if (this.menuConfig?.default_image_id === imageId) {
                    this.menuConfig.default_image_id = null
                }
            } catch (error) {
                console.error('Delete menu default image failed:', error)
                throw error
            }
        },
    },
}
