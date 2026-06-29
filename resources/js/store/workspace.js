import { defineStore } from 'pinia'

import core from './modules/core'
import auth from './modules/auth'
import products from './modules/products'
import categories from './modules/categories'
import collections from './modules/collections'
import webhooks from './modules/webhooks'
import ingredients from './modules/ingredients'
import importExport from './modules/importExport'
import menu from './modules/menu'
import master from './modules/master'
import activity from './modules/activity'

/**
 * Объединяет модули в единую структуру store
 */
function mergeModules(...modules) {
    const merged = {
        state: () => ({}),
        getters: {},
        actions: {},
    }

    for (const mod of modules) {
        // Объединяем state
        if (mod.state) {
            const modState = typeof mod.state === 'function' ? mod.state() : mod.state
            merged.state = ((originalState) => () => ({
                ...originalState(),
                ...modState,
            }))(merged.state)
        }

        // Объединяем getters
        if (mod.getters) {
            Object.assign(merged.getters, mod.getters)
        }

        // Объединяем actions
        if (mod.actions) {
            Object.assign(merged.actions, mod.actions)
        }
    }

    return merged
}

const merged = mergeModules(
    core,
    auth,
    products,
    categories,
    collections,
    webhooks,
    ingredients,
    menu,
    master,
    activity,
    importExport
)

export const useWorkspaceStore = defineStore('workspace', merged)
