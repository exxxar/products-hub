import axios from 'axios'

export default {
    state: () => ({
        products: [],
        selectedCategoryProducts: [],
        totalProducts: 0,
        hasMoreProducts: false,
        productsLoading: false,
        productsLoadingMore: false,
        categoryProductsLoading: false, // ✅ явно в state
        pagination: null,
        filters: {
            in_stop_list: false,
            is_active: false,
        },
        selectedIds: [],
        batchSize: 50,
        search: '',
        editingProduct: null,
        showOnlyStopList: false,
        showOnlyActive: false,
        isLoadingProducts: false,
    }),

    getters: {
        loadProgress(state) {
            if (state.totalProducts === 0) return 0
            return Math.round((state.products.length / state.totalProducts) * 100)
        },
        productsTotalCount(state) {
            return state.totalProducts
        },
        filteredProducts(state) {
            let filtered = state.products
            if (state.search) {
                const query = state.search.toLowerCase()
                filtered = filtered.filter(p =>
                    p.name?.toLowerCase().includes(query) ||
                    p.sku?.toLowerCase().includes(query) ||
                    p.description?.toLowerCase().includes(query)
                )
            }
            if (state.showOnlyStopList) filtered = filtered.filter(p => p.in_stop_list)
            if (state.showOnlyActive) filtered = filtered.filter(p => p.is_active && !p.in_stop_list)
            return filtered
        },
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

        // ✅ ИСПРАВЛЕНО: корректный парсинг ответа
        async loadProductsByCategoryId(categoryId, options = {}) {
            this.categoryProductsLoading = true
            try {
                const params = new URLSearchParams()
                if (options.paginate) {
                    params.append('paginate', 'true')
                    params.append('per_page', options.perPage || 50)
                }
                if (options.page) params.append('page', options.page)
                if (options.search) params.append('search', options.search)
                if (options.isActive !== undefined) {
                    params.append('is_active', options.isActive ? '1' : '0')
                }

                const qs = params.toString()
                const url = `/api/workspaces/${this.uuid}/categories/${categoryId}/products${qs ? '?' + qs : ''}`
                const response = await axios.get(url)

                // Бэк возвращает:
                //   - без пагинации: массив
                //   - с пагинацией: { data: [...], pagination: {...} }
                let productsData
                if (Array.isArray(response.data)) {
                    productsData = response.data
                } else {
                    productsData = response.data.data || response.data.products || []
                }

                this.selectedCategoryProducts = Array.isArray(productsData) ? productsData : []
                this.pagination = response.data.pagination || null
                return this.selectedCategoryProducts
            } catch (error) {
                console.error('Load products by category failed:', error)
                throw error
            } finally {
                this.categoryProductsLoading = false
            }
        },

        async loadProducts(reset = true, options = {}) {
            if (this.productsLoading) return
            this.productsLoading = true
            if (reset) this.products = []

            try {
                const limit = options.limit || this.batchSize
                const offset = reset ? 0 : this.products.length
                const params = new URLSearchParams({ limit, offset })

                if (options.filter === 'active') params.append('is_active', '1')
                else if (options.filter === 'stop') params.append('in_stop_list', '1')
                if (this.search) params.append('search', this.search)

                const response = await axios.get(`/api/workspaces/${this.uuid}/products?${params}`)

                this.products = reset ? response.data.products : [...this.products, ...response.data.products]
                this.totalProducts = response.data.total
                this.hasMoreProducts = response.data.has_more
                this.batchSize = limit
                return response.data
            } catch (error) {
                console.error('Load products failed:', error)
                throw error
            } finally {
                this.productsLoading = false
            }
        },

        async loadMoreProducts(options = {}) {
            if (this.productsLoadingMore || !this.hasMoreProducts) return
            this.productsLoadingMore = true
            try {
                const limit = options.limit || this.batchSize
                const offset = this.products.length
                const params = new URLSearchParams({ limit, offset })
                if (options.filter === 'active') params.append('is_active', '1')
                else if (options.filter === 'stop') params.append('in_stop_list', '1')
                if (this.search) params.append('search', this.search)

                const response = await axios.get(`/api/workspaces/${this.uuid}/products?${params}`)
                this.products.push(...response.data.products)
                this.hasMoreProducts = response.data.has_more
                return response.data
            } catch (error) {
                console.error('Load more failed:', error)
                throw error
            } finally {
                this.productsLoadingMore = false
            }
        },

        setSearch(value) {
            this.search = value
            this.loadProducts(true)
        },

        toggleStopListFilter() {
            this.filters.in_stop_list = !this.filters.in_stop_list
            if (this.filters.in_stop_list) this.filters.is_active = false
            this.loadProducts(true)
        },

        toggleActiveFilter() {
            this.filters.is_active = !this.filters.is_active
            if (this.filters.is_active) this.filters.in_stop_list = false
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
            if (index > -1) this.selectedIds.splice(index, 1)
            else this.selectedIds.push(id)
        },

        selectAll() {
            this.selectedIds = this.filteredProducts.map(p => p.id)
        },

        clearSelection() {
            this.selectedIds = []
        },

        // === Вспомогательный метод: обновить товар во всех списках ===
        _updateProductInAllLists(id, patch) {
            const apply = (arr) => {
                if (!Array.isArray(arr)) return
                const p = arr.find(x => x.id === id)
                if (p) Object.assign(p, patch)
            }
            apply(this.products)
            apply(this.selectedCategoryProducts)
        },

        // ✅ ИСПРАВЛЕНО: ищет товар везде + корректно мутирует
        async toggleProductStopList(productId) {
            // Ищем товар в обоих возможных списках
            let product = this.products.find(p => p.id === productId)
                || this.selectedCategoryProducts.find(p => p.id === productId)

            if (!product) {
                console.warn('Product not found in any list:', productId)
                return { success: false, reason: 'not_found' }
            }

            const newStatus = !product.in_stop_list
            const endpoint = newStatus
                ? `/api/workspaces/${this.uuid}/products/add-to-stop-list`
                : `/api/workspaces/${this.uuid}/products/remove-from-stop-list`

            try {
                const response = await axios.post(endpoint, { ids: [productId] })

                // Бэкенд возвращает обновленный продукт? Если да — используем, если нет — локально
                const backendProduct = response.data?.product || response.data
                const patch = (backendProduct && typeof backendProduct === 'object')
                    ? {
                        in_stop_list: backendProduct.in_stop_list ?? newStatus,
                        is_active: backendProduct.is_active ?? !newStatus,
                    }
                    : { in_stop_list: newStatus, is_active: !newStatus }

                this._updateProductInAllLists(productId, patch)

                return {
                    success: true,
                    in_stop_list: patch.in_stop_list,
                }
            } catch (error) {
                console.error('Toggle stop list failed:', error)
                throw error
            }
        },

        // ✅ Обновлены массовые методы — тоже синхронизируют оба списка
        async addSelectedToStopList() {
            if (this.selectedIds.length === 0) return
            const ids = [...this.selectedIds]
            try {
                await axios.post(`/api/workspaces/${this.uuid}/products/add-to-stop-list`, { ids })
                ids.forEach(id => this._updateProductInAllLists(id, { in_stop_list: true }))
                this.selectedIds = []
                return { success: true, count: ids.length }
            } catch (error) {
                console.error('Add to stop list failed:', error)
                throw error
            }
        },

        async removeSelectedFromStopList() {
            if (this.selectedIds.length === 0) return
            const ids = [...this.selectedIds]
            try {
                await axios.post(`/api/workspaces/${this.uuid}/products/remove-from-stop-list`, { ids })
                ids.forEach(id => this._updateProductInAllLists(id, { in_stop_list: false, is_active: true }))
                this.selectedIds = []
                return { success: true, count: ids.length }
            } catch (error) {
                console.error('Remove from stop list failed:', error)
                throw error
            }
        },

        async saveProduct(formData, id = null) {
            try {
                let response
                if (id) {
                    formData.append('_method', 'PUT')
                    response = await axios.post(`/api/workspaces/${this.uuid}/products/${id}`, formData, {
                        headers: { 'Content-Type': 'multipart/form-data' }
                    })
                    const idx = this.products.findIndex(p => p.id === id)
                    if (idx > -1) this.products[idx] = response.data
                    const idxCat = this.selectedCategoryProducts.findIndex(p => p.id === id)
                    if (idxCat > -1) this.selectedCategoryProducts[idxCat] = response.data
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
                const msg = error.response?.data?.message || 'Ошибка при сохранении'
                throw new Error(msg)
            }
        },

        async deleteProduct(id) {
            try {
                await axios.delete(`/api/workspaces/${this.uuid}/products/${id}`)
                this.products = this.products.filter(p => p.id !== id)
                this.selectedCategoryProducts = this.selectedCategoryProducts.filter(p => p.id !== id)
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
                    await axios.delete(`/api/workspaces/${this.uuid}/products/bulk`, { data: { ids: idsToDelete } })
                } else {
                    await Promise.all(idsToDelete.map(id =>
                        axios.delete(`/api/workspaces/${this.uuid}/products/${id}`)
                    ))
                }
                this.products = this.products.filter(p => !idsToDelete.includes(p.id))
                this.selectedCategoryProducts = this.selectedCategoryProducts.filter(p => !idsToDelete.includes(p.id))
                this.selectedIds = []
            } catch (error) {
                console.error('Remove products failed:', error)
                const msg = error.response?.data?.message || 'Ошибка при удалении товаров'
                throw new Error(msg)
            }
        },

        async uploadProductImages(productId, files) {
            const formData = new FormData()
            files.forEach((file, index) => formData.append(`images[${index}]`, file))
            try {
                const response = await axios.post(
                    `/api/workspaces/${this.uuid}/products/${productId}/images`,
                    formData,
                    { headers: { 'Content-Type': 'multipart/form-data' } }
                )
                this._updateProductInAllLists(productId, { images: response.data.images })
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
                this._updateProductInAllLists(productId, { images: response.data.images })
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
                this._updateProductInAllLists(productId, { images: response.data.images })
                return response.data
            } catch (error) {
                console.error('Reorder images failed:', error)
                throw error
            }
        },
    },
}
