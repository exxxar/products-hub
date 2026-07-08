import axios from 'axios'

export default {
    state: () => ({
        products: [],              // Загруженные товары
        totalProducts: 0,          // Общее количество в БД
        hasMoreProducts: false,    // Есть ли ещё для загрузки
        productsLoading: false,    // Идёт загрузка
        productsLoadingMore: false,// Идёт догрузка

        filters: {
            in_stop_list: false,
            is_active: false,
        },
        selectedIds: [],
        batchSize: 50,
        search: '',
        editingProduct: null,

        showOnlyStopList: false, // ✅ НОВОЕ: фильтр по стоп-листу
        showOnlyActive: false,   // ✅ НОВОЕ: фильтр по активности
    }),

    getters: {
        // Прогресс загрузки
        loadProgress(state) {
            if (state.totalProducts === 0) return 0
            return Math.round((state.products.length / state.totalProducts) * 100)
        },

        // Статистика для футера (всегда общее количество)
        productsTotalCount(state) {
            return state.totalProducts
        },

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

        async loadProducts(reset = true) {
            if (this.productsLoading) return

            this.productsLoading = true

            if (reset) {
                this.products = []
            }

            try {
                const params = new URLSearchParams({
                    limit: this.batchSize,
                    offset: reset ? 0 : this.products.length,
                })

                if (this.search) {
                    params.append('search', this.search)
                }

                if (this.filters.in_stop_list) {
                    params.append('in_stop_list', '1')
                }

                if (this.filters.is_active) {
                    params.append('is_active', '1')
                }

                const response = await axios.get(
                    `/api/workspaces/${this.uuid}/products?${params.toString()}`
                )

                if (reset) {
                    this.products = response.data.products
                } else {
                    // Добавляем новые к существующим
                    this.products = [...this.products, ...response.data.products]
                }

                this.totalProducts = response.data.total
                this.hasMoreProducts = response.data.has_more

                return response.data
            } catch (error) {
                console.error('Load products failed:', error)
                throw error
            } finally {
                this.productsLoading = false
            }
        },

        // ✅ Догрузка следующей порции
        async loadMoreProducts() {
            if (this.productsLoadingMore || !this.hasMoreProducts) return

            this.productsLoadingMore = true

            try {
                const params = new URLSearchParams({
                    limit: this.batchSize,
                    offset: this.products.length,
                })

                if (this.search) {
                    params.append('search', this.search)
                }

                if (this.filters.in_stop_list) {
                    params.append('in_stop_list', '1')
                }

                if (this.filters.is_active) {
                    params.append('is_active', '1')
                }

                const response = await axios.get(
                    `/api/workspaces/${this.uuid}/products?${params.toString()}`
                )

                this.products = [...this.products, ...response.data.products]
                this.hasMoreProducts = response.data.has_more

                return response.data
            } catch (error) {
                console.error('Load more products failed:', error)
                throw error
            } finally {
                this.productsLoadingMore = false
            }
        },

        // ✅ Установка поиска с перезагрузкой
        setSearch(value) {
            this.search = value
            this.loadProducts(true) // Сброс и новая загрузка
        },

        // ✅ Переключение фильтров
        toggleStopListFilter() {
            this.filters.in_stop_list = !this.filters.in_stop_list
            if (this.filters.in_stop_list) {
                this.filters.is_active = false
            }
            this.loadProducts(true)
        },

        toggleActiveFilter() {
            this.filters.is_active = !this.filters.is_active
            if (this.filters.is_active) {
                this.filters.in_stop_list = false
            }
            this.loadProducts(true)
        },

        clearFilters() {
            this.filters = { in_stop_list: false, is_active: false }
            this.search = ''
            this.loadProducts(true)
        },

        setProducts(products) {
            this.products = products || []
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
                        return { ...p, in_stop_list: false, is_active: true}
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

        async uploadProductImages(productId, files) {
            const formData = new FormData()

            files.forEach((file, index) => {
                formData.append(`images[${index}]`, file)
            })

            try {
                const response = await axios.post(
                    `/api/workspaces/${this.uuid}/products/${productId}/images`,
                    formData,
                    { headers: { 'Content-Type': 'multipart/form-data' } }
                )

                // Обновляем товар в списке
                const product = this.products.find(p => p.id === productId)
                if (product) {
                    product.images = response.data.images
                }

                return response.data
            } catch (error) {
                console.error('Upload images failed:', error)
                throw error
            }
        },

        async deleteProductImage(productId, index) {
            try {
                const response = await axios.delete(
                    `/api/workspaces/${this.uuid}/products/${productId}/images`,
                    { data: { index } }
                )

                // Обновляем товар в списке
                const product = this.products.find(p => p.id === productId)
                if (product) {
                    product.images = response.data.images
                }

                return response.data
            } catch (error) {
                console.error('Delete image failed:', error)
                throw error
            }
        },

        async reorderProductImages(productId, order) {
            try {
                const response = await axios.put(
                    `/api/workspaces/${this.uuid}/products/${productId}/images/reorder`,
                    { order }
                )

                const product = this.products.find(p => p.id === productId)
                if (product) {
                    product.images = response.data.images
                }

                return response.data
            } catch (error) {
                console.error('Reorder images failed:', error)
                throw error
            }
        },
    },
}
