import axios from 'axios'

export default {
    state: () => ({
        products: [],
        selectedIds: [],
        search: '',
        editingProduct: null,

        showOnlyStopList: false, // ✅ НОВОЕ: фильтр по стоп-листу
        showOnlyActive: false,   // ✅ НОВОЕ: фильтр по активности
    }),

    getters: {
        filteredProducts(state) {
            let filtered = state.products

            // Поиск
            if (state.search) {
                const query = state.search.toLowerCase()
                filtered = filtered.filter(p =>
                    p.name?.toLowerCase().includes(query) ||
                    p.sku?.toLowerCase().includes(query) ||
                    p.description?.toLowerCase().includes(query)
                )
            }

            // ✅ Фильтр по стоп-листу
            if (state.showOnlyStopList) {
                filtered = filtered.filter(p => p.in_stop_list)
            }

            // ✅ Фильтр по активности
            if (state.showOnlyActive) {
                filtered = filtered.filter(p => p.is_active && !p.in_stop_list)
            }

            return filtered
        },

        // ✅ Геттеры для статистики
        stopListCount(state) {
            return state.products.filter(p => p.in_stop_list).length
        },

        activeCount(state) {
            return state.products.filter(p => p.is_active && !p.in_stop_list).length
        },

        selectedProducts(state) {
            return state.products.filter(p => state.selectedIds.includes(p.id))
        },

        productById: (state) => (id) => state.products.find(p => p.id === id),
    },

    actions: {
        setProducts(products) {
            this.products = products || []
        },

        setSearch(query) {
            this.search = query
        },

        toggleSelect(id) {
            const index = this.selectedIds.indexOf(id)
            if (index > -1) {
                this.selectedIds.splice(index, 1)
            } else {
                this.selectedIds.push(id)
            }
        },

        selectAll() {
            this.selectedIds = this.filteredProducts.map(p => p.id)
        },

        clearSelection() {
            this.selectedIds = []
        },

        async saveProduct(formData, id = null) {
            try {
                let response

                if (id) {
                    formData.append('_method', 'PUT')
                    response = await axios.post(`/api/workspaces/${this.uuid}/products/${id}`, formData, {
                        headers: { 'Content-Type': 'multipart/form-data' }
                    })

                    const index = this.products.findIndex(p => p.id === id)
                    if (index > -1) {
                        this.products[index] = response.data
                    }
                } else {
                    response = await axios.post(`/api/workspaces/${this.uuid}/products`, formData, {
                        headers: { 'Content-Type': 'multipart/form-data' }
                    })
                    this.products.push(response.data)
                }

                await this.loadCategories()
                return response.data
            } catch (error) {
                console.error('Save product failed:', error)
                const errorMessage = error.response?.data?.message || 'Ошибка при сохранении'
                throw new Error(errorMessage)
            }
        },

        async deleteProduct(id) {
            try {
                await axios.delete(`/api/workspaces/${this.uuid}/products/${id}`)
                this.products = this.products.filter(p => p.id !== id)
                this.selectedIds = this.selectedIds.filter(sid => sid !== id)
            } catch (error) {
                console.error('Delete product failed:', error)
                throw error
            }
        },

        async removeProductsByIds() {
            if (this.selectedIds.length === 0) return

            const idsToDelete = [...this.selectedIds]

            try {
                if (idsToDelete.length > 5) {
                    await axios.delete(`/api/workspaces/${this.uuid}/products/bulk`, {
                        data: { ids: idsToDelete }
                    })
                } else {
                    const deletePromises = idsToDelete.map(id =>
                        axios.delete(`/api/workspaces/${this.uuid}/products/${id}`)
                    )
                    await Promise.all(deletePromises)
                }

                this.products = this.products.filter(p => !idsToDelete.includes(p.id))
                this.selectedIds = []
            } catch (error) {
                console.error('Remove products failed:', error)
                const errorMessage = error.response?.data?.message || 'Ошибка при удалении товаров'
                throw new Error(errorMessage)
            }
        },

        // ✅ Переключение фильтра стоп-листа
        toggleStopListFilter() {
            this.showOnlyStopList = !this.showOnlyStopList
            // Если включили стоп-лист — выключаем фильтр активных
            if (this.showOnlyStopList) {
                this.showOnlyActive = false
            }
        },

        // ✅ Переключение фильтра активных
        toggleActiveFilter() {
            this.showOnlyActive = !this.showOnlyActive
            // Если включили активные — выключаем стоп-лист
            if (this.showOnlyActive) {
                this.showOnlyStopList = false
            }
        },

        // ✅ Сброс всех фильтров
        clearFilters() {
            this.showOnlyStopList = false
            this.showOnlyActive = false
            this.search = ''
        },

        // ✅ Добавление выбранных товаров в стоп-лист
        async addSelectedToStopList() {
            if (this.selectedIds.length === 0) return

            const ids = [...this.selectedIds]

            try {
                await axios.post(`/api/workspaces/${this.uuid}/products/add-to-stop-list`, {
                    ids: ids
                })

                // Обновляем локальные данные
                this.products = this.products.map(p => {
                    if (ids.includes(p.id)) {
                        return { ...p, in_stop_list: true }
                    }
                    return p
                })

                // Очищаем выбор
                this.selectedIds = []

                return { success: true, count: ids.length }
            } catch (error) {
                console.error('Add to stop list failed:', error)
                throw error
            }
        },

        // ✅ Удаление выбранных товаров из стоп-листа
        async removeSelectedFromStopList() {
            if (this.selectedIds.length === 0) return

            const ids = [...this.selectedIds]

            try {
                await axios.post(`/api/workspaces/${this.uuid}/products/remove-from-stop-list`, {
                    ids: ids
                })

                // Обновляем локальные данные
                this.products = this.products.map(p => {
                    if (ids.includes(p.id)) {
                        return { ...p, in_stop_list: false }
                    }
                    return p
                })

                this.selectedIds = []

                return { success: true, count: ids.length }
            } catch (error) {
                console.error('Remove from stop list failed:', error)
                throw error
            }
        },

        // ✅ Быстрое переключение стоп-листа для одного товара
        async toggleProductStopList(productId) {
            const product = this.products.find(p => p.id === productId)
            if (!product) return

            const newStatus = !product.in_stop_list

            try {
                if (newStatus) {
                    await axios.post(`/api/workspaces/${this.uuid}/products/add-to-stop-list`, {
                        ids: [productId]
                    })
                } else {
                    await axios.post(`/api/workspaces/${this.uuid}/products/remove-from-stop-list`, {
                        ids: [productId]
                    })
                }

                // Обновляем локально
                product.in_stop_list = newStatus

                return { success: true, in_stop_list: newStatus }
            } catch (error) {
                console.error('Toggle stop list failed:', error)
                throw error
            }
        },
    },
}
