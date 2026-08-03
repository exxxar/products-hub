import axios from 'axios'

export default {
    state: () => ({
        categories: [],
        editingCategory: null,
        selectedCategoryProducts: [],
        categoryProductsLoading: false,
    }),

    getters: {
        categoryById: (state) => (id) => state.categories.find(c => c.id === id),
    },

    actions: {
        setCategories(categories) {
            this.categories = categories || []
        },

        async loadCategories() {
            try {
                const response = await axios.get(`/api/workspaces/${this.uuid}/categories`)
                this.categories = response.data
                return response.data
            } catch (error) {
                console.error('Load categories failed:', error)
                throw error
            }
        },

        async saveCategory(categoryData, id = null) {
            try {
                let response

                if (id) {
                    response = await axios.put(`/api/workspaces/${this.uuid}/categories/${id}`, categoryData)
                    const index = this.categories.findIndex(c => c.id === id)
                    if (index > -1) {
                        this.categories[index] = response.data
                    }
                } else {
                    response = await axios.post(`/api/workspaces/${this.uuid}/categories`, categoryData)
                    this.categories.push(response.data)
                }

                return response.data
            } catch (error) {
                console.error('Save category failed:', error)
                throw error
            }
        },

        async deleteCategory(id) {
            try {
                await axios.delete(`/api/workspaces/${this.uuid}/categories/${id}`)
                this.categories = this.categories.filter(c => c.id !== id)
            } catch (error) {
                console.error('Delete category failed:', error)
                throw error
            }
        },

        async addProductsToCategory(categoryId, productIds) {
            try {
                const response = await axios.post(
                    `/api/workspaces/${this.uuid}/categories/${categoryId}/products`,
                    { product_ids: productIds }
                )

                const index = this.categories.findIndex(c => c.id === categoryId)
                if (index > -1) {
                    this.categories[index] = response.data
                }

                return response.data
            } catch (error) {
                console.error('Add products to category failed:', error)
                throw error
            }
        },

        async removeProductsFromCategory(categoryId, productIds) {
            try {
                const response = await axios.delete(
                    `/api/workspaces/${this.uuid}/categories/${categoryId}/products`,
                    { data: { product_ids: productIds } }
                )

                const index = this.categories.findIndex(c => c.id === categoryId)
                if (index > -1) {
                    this.categories[index] = response.data
                }

                return response.data
            } catch (error) {
                console.error('Remove products from category failed:', error)
                throw error
            }
        },

        async syncCategoryProducts(categoryId, productIds) {
            try {
                const response = await axios.put(
                    `/api/workspaces/${this.uuid}/categories/${categoryId}/products`,
                    { product_ids: productIds }
                )

                const index = this.categories.findIndex(c => c.id === categoryId)
                if (index > -1) {
                    this.categories[index] = response.data
                }

                return response.data
            } catch (error) {
                console.error('Sync category products failed:', error)
                throw error
            }
        },



        async selectCategoryWithProducts(category) {
            if (!category) {
                this.selectedCategoryProducts = []
                return
            }

            try {
                const products = await this.loadProductsByCategoryId(category.id)
                this.selectedCategoryProducts = products
                return products
            } catch (error) {
                console.error('Failed to load category products:', error)
                throw error
            }
        },

        // === Пресеты категорий ===

        async loadCategoryPresets() {
            try {
                const response = await axios.get(`/api/workspaces/${this.uuid}/category-presets`)
                return response.data
            } catch (error) {
                console.error('Load category presets failed:', error)
                throw error
            }
        },

        async applyCategoryPreset(presetKey, replaceExisting = false) {
            try {
                const response = await axios.post(
                    `/api/workspaces/${this.uuid}/category-presets/${presetKey}/apply`,
                    { replace_existing: replaceExisting }
                )

                await this.loadCategories()
                return response.data
            } catch (error) {
                console.error('Apply category preset failed:', error)
                const errorMessage = error.response?.data?.error || 'Ошибка при применении пресета'
                throw new Error(errorMessage)
            }
        },
    },
}
