import axios from 'axios'

export default {
    state: () => ({
        collections: [],
        selectedCollection: null,
        collectionProducts: [],
        collectionsLoading: false,
        viewingCollectionProducts: false, // ✅ Флаг: просматриваем товары коллекции
    }),

    getters: {
        // Активные коллекции (не в стоп-листе)
        activeCollections(state) {
            return state.collections.filter(c => c.is_active && !c.in_stop_list)
        },

        // Коллекции в стоп-листе
        stopListCollections(state) {
            return state.collections.filter(c => c.in_stop_list)
        },

        // Неактивные коллекции
        inactiveCollections(state) {
            return state.collections.filter(c => !c.is_active)
        },

        // Общая стоимость всех коллекций
        totalCollectionsValue(state) {
            return state.collections.reduce((sum, c) => sum + (c.price || 0), 0)
        },

        // Количество товаров во всех коллекциях
        totalProductsInCollections(state) {
            return state.collections.reduce((sum, c) => sum + (c.products_count || 0), 0)
        },

        // ✅ Можно ли редактировать товары коллекции
        canEditCollectionProducts(state) {
            if (!state.selectedCollection) return false
            return ['manual', 'category_select'].includes(state.selectedCollection.type)
        },
    },

    actions: {
        setCollections(collections) {
            this.collections = collections || []
        },

        async loadCollections() {
            this.collectionsLoading = true
            try {
                const response = await axios.get(`/api/workspaces/${this.uuid}/collections`)
                this.collections = response.data
                return response.data
            } catch (error) {
                console.error('Load collections failed:', error)
                throw error
            } finally {
                this.collectionsLoading = false
            }
        },
// ✅ Загрузка товаров коллекции с полными данными
        async loadCollectionProducts(collectionId) {
            this.collectionProductsLoading = true
            try {
                const response = await axios.get(
                    `/api/workspaces/${this.uuid}/collections/${collectionId}/show`
                )
                this.collectionProducts = response.data.products
                return response.data
            } catch (error) {
                console.error('Load collection products failed:', error)
                throw error
            } finally {
                this.collectionProductsLoading = false
            }
        },

        // ✅ Выбор коллекции для просмотра товаров
        async selectCollection(collection) {
            this.selectedCollection = collection

            if (collection) {
                this.viewingCollectionProducts = true
                await this.loadCollectionProducts(collection.id)
            } else {
                this.viewingCollectionProducts = false
                this.collectionProducts = []
            }
        },

        // ✅ Выход из просмотра коллекции
        exitCollectionView() {
            this.selectedCollection = null
            this.collectionProducts = []
            this.viewingCollectionProducts = false
        },
        async loadCollection(collectionId) {
            try {
                const response = await axios.get(
                    `/api/workspaces/${this.uuid}/collections/${collectionId}/preview`
                )
                this.collectionProducts = response.data.products
                return response.data
            } catch (error) {
                console.error('Load collection products failed:', error)
                throw error
            }
        },

        // === Создание коллекции ===
        async saveCollection(collectionData) {
            try {
                const response = await axios.post(
                    `/api/workspaces/${this.uuid}/collections`,
                    collectionData
                )

                this.collections.push(response.data)
                return response.data
            } catch (error) {
                console.error('Save collection failed:', error)
                throw error
            }
        },

        // === Обновление коллекции ===
        async updateCollection(id, data) {
            try {
                const response = await axios.put(
                    `/api/workspaces/${this.uuid}/collections/${id}`,
                    data
                )

                const index = this.collections.findIndex(c => c.id === id)
                if (index > -1) {
                    this.collections[index] = response.data
                }

                return response.data
            } catch (error) {
                console.error('Update collection failed:', error)
                throw error
            }
        },

        // === Удаление коллекции ===
        async deleteCollection(id) {
            try {
                await axios.delete(`/api/workspaces/${this.uuid}/collections/${id}`)
                this.collections = this.collections.filter(c => c.id !== id)

                // Если удалили выбранную — сбрасываем
                if (this.selectedCollection?.id === id) {
                    this.selectedCollection = null
                    this.collectionProducts = []
                }
            } catch (error) {
                console.error('Delete collection failed:', error)
                throw error
            }
        },

        async addProductsToCollection(collectionId, productIds) {
            try {
                const response = await axios.post(
                    `/api/workspaces/${this.uuid}/collections/${collectionId}/products`,
                    { product_ids: productIds }
                )

                const index = this.collections.findIndex(c => c.id === collectionId)
                if (index > -1) {
                    this.collections[index] = response.data
                }

                this.selectedIds = []
                return response.data
            } catch (error) {
                console.error('Add products to collection failed:', error)
                throw error
            }
        },

        async removeProductsFromCollection(collectionId, productIds) {
            try {
                await this.updateCollection(collectionId, {
                    in_stop_list: newStatus
                })

                collection.in_stop_list = newStatus

                return { success: true, in_stop_list: newStatus }
            } catch (error) {
                console.error('Toggle collection stop list failed:', error)
                throw error
            }
        },

        async reorderCollectionProducts(collectionId, productIds) {
            try {
                await this.updateCollection(collectionId, {
                    is_active: newStatus
                })

                collection.is_active = newStatus

                return { success: true, is_active: newStatus }
            } catch (error) {
                console.error('Toggle collection active failed:', error)
                throw error
            }
        },
    },
}
