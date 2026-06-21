import axios from 'axios'

export default {
    state: () => ({
        ingredientGroups: [],
    }),

    actions: {
        async saveIngredientGroup(groupData, id = null) {
            try {
                let response

                if (id) {
                    response = await axios.put(`/api/workspaces/${this.uuid}/ingredient-groups/${id}`, groupData)
                    const index = this.ingredientGroups.findIndex(g => g.id === id)
                    if (index > -1) {
                        this.ingredientGroups[index] = response.data
                    }
                } else {
                    response = await axios.post(`/api/workspaces/${this.uuid}/ingredient-groups`, groupData)
                    this.ingredientGroups.push(response.data)
                }

                return response.data
            } catch (error) {
                console.error('Save ingredient group failed:', error)
                throw error
            }
        },

        async deleteIngredientGroup(id) {
            try {
                await axios.delete(`/api/workspaces/${this.uuid}/ingredient-groups/${id}`)
                this.ingredientGroups = this.ingredientGroups.filter(g => g.id !== id)
            } catch (error) {
                console.error('Delete ingredient group failed:', error)
                throw error
            }
        },
    },
}
