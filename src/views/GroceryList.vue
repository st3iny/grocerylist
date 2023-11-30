<template>
	<div class="page-wrapper">
		<h1>{{ groceryList?.title ?? t('grocerylist', 'Grocery list') }}</h1>
		<div class="grocery-list">
			<NcCheckboxRadioSwitch :checked="!!groceryList?.showOnlyUnchecked" type="switch" @update:checked="toggleVisibility">
				{{ t('grocerylist', 'Show only unchecked') }}
			</NcCheckboxRadioSwitch>

			<div class="grocery-list__item-form">
				<input v-model="newItemQuantity"
					class="grocery-list__item-form__quantity"
					placeholder="Quantity…"
					:disabled="updating">
				<input v-model="newItemName"
					class="grocery-list__item-form__name"
					placeholder="Item…"
					:disabled="updating"
					@keyup.enter="onSaveItem()"
					@input="toggleSaveButton()">
				<NcSelect v-model="newItemCategory"
					class="grocery-list__item-form__category"
					:options="allCategories"
					label="name"
					:value="object"
					:close-on-outside-click="true"
					@updateOption="updateNewItemCategory" />
				<NcButton :disabled="!canSave"
					style="display:inline-block;"
					@click="onSaveItem()">
					<template #icon>
						<Check v-if="showDeleteButton" :size="20" />
						<Plus v-else :size="20" />
					</template>
				</NcButton>
				<NcButton v-if="showDeleteButton"
					id="deleteButton"
					style="display:inline-block;"
					@click="deleteItem()">
					<template #icon>
						<Delete :size="20" />
					</template>
				</NcButton>
			</div>

			<br>
			<span v-for="category in filteredCategories" :key="category.id">
				<h2 style="font-size: larger; text-transform: uppercase;">
					{{ category.name }}
				</h2>
				<ul class="grocery-list__item-list">
					<li v-for="item in filteredItems"
						v-if="item.category === category.id"
						class="grocery-list__item-list__item">
						<NcButton aria-label="Snooze"
							type="tertiary"
							style="display:inline-block;"
							@click="hideItem(item)">
							<template #icon>
								<AlarmSnooze :size="20" />
							</template>
						</NcButton>
						<input type="checkbox"
							:checked="item.checked === true"
							@input="checkItem(item)">
						<span class="grocery-list__item-list__item__desc"
							@click="editItem(item)">
							<template v-if="item.quantity">
								{{ item.quantity }}
							</template>
							{{ item.name }}
						</span>
					</li>
				</ul>
			</span>
		</div>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import {
	NcSelect,
	NcCheckboxRadioSwitch,
	NcButton,
} from '@nextcloud/vue'
import AlarmSnooze from 'vue-material-design-icons/AlarmSnooze.vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import Check from 'vue-material-design-icons/Check.vue'
import Delete from 'vue-material-design-icons/Delete.vue'

export default {
	name: 'GroceryList',

	components: {
		NcCheckboxRadioSwitch,
		NcSelect,
		NcButton,
		AlarmSnooze,
		Plus,
		Check,
		Delete,
	},

	props: {
		listId: {
			type: Number,
			required: true,
		},
	},

	data() {
		return {
			groceryList: null,
			categories: null,
			allCategories: [],
			items: null,
			itemsAll: null,
			updating: false,
			canSave: true,
			category: '',
			newCategoryId: -1,
			loading: false,
			newItemId: -1,
			newItemName: '',
			newItemQuantity: '',
			newItemCategory: null,
			object: {
				name: 'Select a category…',
			},
			showDeleteButton: false,
		}
	},
	computed: {
		name() {
			return 'Test'
		},
		title() {
			return this.groceryList === null ? '' : this.groceryList.title
		},
		filteredCategories() {
			if (this.categories == null) {
				return []
			}

			return this.categories.filter(category => {
				if (this.groceryList.showOnlyUnchecked) {
					return this.filteredItems.filter(item => {
						return item.category === category.id
					}).length > 0
				} else {
					return true
				}
			})
		},
		filteredItems() {
			if (this.items == null) {
				return []
			}

			return this.items.filter(item => {
				if (this.groceryList.showOnlyUnchecked) {
					return item.checked === false
				} else {
					return true
				}
			})
		},
	},
	watch: {
		listId: {
			immediate: true,
			async handler() {
				await Promise.all([
					this.loadGroceryList(this.listId),
					this.loadCategories(this.listId),
					this.loadAllCategories(this.listId),
					this.loadItems(this.listId),
				])
			},
		},
	},
	methods: {
		toggleSaveButton() {
			if (this.newItemName !== '') {
				this.canSave = true
			} else {
				this.canSave = false
			}
		},
		toggleVisibility() {
			this.groceryList.showOnlyUnchecked = !this.groceryList.showOnlyUnchecked ? 1 : 0
			this.updateGroceryList(this.listId, this.groceryList.showOnlyUnchecked)
		},
		async updateGroceryList(id, value) {
			this.updating = true
			try {
				await axios.post(generateUrl('/apps/grocerylist/api/lists/' + id + '/visibility'),
					{
						showOnlyUnchecked: value,
					})
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not update groceryList'))
			}
			this.updating = false
		},
		updateNewItemCategory(category) {
			this.newItemCategory = category
		},
		async loadGroceryList(id) {
			try {
				const response = await axios.get(generateUrl('/apps/grocerylist/api/list/' + id))
				this.groceryList = response.data
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', ('Could not fetch list ' + id)))
			}
			this.loading = false
		},
		async loadCategories(id) {
			try {
				const response = await axios.get(generateUrl('/apps/grocerylist/api/categories/' + id))
				this.categories = response.data
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', ('Could not fetch categories for groceryList ' + id)))
			}
			this.loading = false
		},
		async loadAllCategories(id) {
			try {
				const response = await axios.get(generateUrl('/apps/grocerylist/api/all_categories/' + id))
				this.allCategories = response.data
				this.newItemCategory = this.allCategories[0]
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', ('Could not fetch all categories for groceryList ' + id)))
			}
			this.loading = false
		},
		async loadItems(id) {
			try {
				const response = await axios.get(generateUrl('/apps/grocerylist/api/items/' + id))
				console.warn('Load items for ' + id)
				this.items = response.data
				console.warn('Size: ' + this.items.length)

				this.items.sort((a, b) => {
					if (a.checked === b.checked) {
						if (a.category === b.category) {
							return a.name.toLowerCase().localeCompare(b.name.toLowerCase())
						} else {
							return a.category - b.category
						}
					} else {
						return a.checked - b.checked
					}
				})
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', ('Could not fetch items for groceryList ' + id)))
			}
			this.loading = false
		},
		async checkItem(item) {
			this.updating = true
			if (item.checked === true) {
				item.checked = false
			} else {
				item.checked = true
			}
			try {
				await axios.post(generateUrl('/apps/grocerylist/api/item/check'),
					{ id: item.id, checked: item.checked },
				)

				this.items.sort((a, b) => {
					if (a.checked === b.checked) {
						if (a.category === b.category) {
							return a.name.toLowerCase().localeCompare(b.name.toLowerCase())
						} else {
							return a.category - b.category
						}
					} else {
						return a.checked - b.checked
					}
				})

				this.updating = false
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not check item'))
			}
		},
		async hideItem(item) {
			this.updating = true
			try {
				await axios.post(generateUrl('/apps/grocerylist/api/item/hide'),
					{ id: item.id },
				)

				await this.loadItems(this.listId)
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not add item'))
			}
			this.updating = false
		},
		async editItem(item) {
			this.showDeleteButton = true
			this.newItemId = item.id
			this.newItemName = item.name
			this.newItemQuantity = item.quantity
			this.object.name = this.allCategories.find(i => i.id === item.category).name
			this.newItemCategory = this.allCategories.find(i => i.id === item.category)

			this.toggleSaveButton()
		},
		async onSaveItem() {
			if (this.newItemId === -1) {
				await this.addItem()
			} else {
				await this.updateItem()
			}

			this.toggleSaveButton()
		},
		async addItem() {
			this.updating = true
			try {
				await axios.post(generateUrl('/apps/grocerylist/api/item/add'),
					{
						name: this.newItemName.trim(),
						quantity: this.newItemQuantity,
						category: this.newItemCategory.id,
						list: this.listId,
					},
				)

				await this.loadItems(this.listId)
				await this.loadCategories(this.listId)

				this.newItemName = ''
				this.newItemQuantity = ''

				this.toggleSaveButton()
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not add item'))
			}
			this.updating = false
		},
		async updateItem() {
			this.updating = true
			try {
				await axios.post(generateUrl('/apps/grocerylist/api/item/update'),
					{
						id: this.newItemId,
						name: this.newItemName.trim(),
						quantity: this.newItemQuantity,
						category: this.newItemCategory.id,
					},
				)

				await this.loadItems(this.listId)
				this.newItemId = -1;
				this.newItemName = ''
				this.newItemQuantity = ''
				this.newItemCategory = null
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not add item'))
			}

			this.showDeleteButton = false
			this.updating = false
		},
		async deleteItem() {
			this.updating = true

			try {
				await axios.delete(generateUrl('/apps/grocerylist/api/item/' + this.newItemId))

				await this.loadItems(this.listId)
				this.newItemId = -1
				this.newItemName = ''
				this.newItemQuantity = ''
				this.newItemCategory = null
				this.object.name = 'Select a category…'
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not delete item'))
			}

			this.showDeleteButton = false
			this.updating = false
		},
	},
}
</script>

<style lang="scss">
// Wrapper around all of the view content
.page-wrapper {
	width: 100%;
	max-width: 900px;
	// center
	margin-inline: auto;
	margin-block: var(--app-navigation-padding);
	// ensure we do not conflict with App Navigation toggle
	padding-inline: calc(44px + 2 * var(--app-navigation-padding));
}

h1 {
	color: var(--color-text-light);
	font-weight: bold;
	font-size: 24px;
	line-height: 30px;
	text-align: center;
	// to align with the toggle we need 44px (the toggle) - 30px (h2 line-height) / 2 + padding => 7px + padding
	margin-block: calc(7px + var(--app-navigation-padding)) 12px;
}

.grocery-list {
	&__item-form {
		display: flex;
		align-items: center;
		gap: var(--default-grid-baseline);
		flex-wrap: wrap;

		> input {
			height: 44px !important;
			margin: 0;
		}

		&__quantity {
			width: 50px;
			min-width: 5rem;
			flex: 1 auto;
		}

		&__name {
			width: 200px;
			flex: 1 auto;
		}

		&__category {
			width: 250px;
			min-width: unset !important;
			flex: 1 auto;
		}
	}

	&__item-list {
		&__item {
			display: flex;
			align-items: center;

			input[type="checkbox"] {
				cursor: pointer;
				margin: 0 10px 0 0;
				width: 18px;
				height: 18px;
			}

			&__desc {
				display: flex;
				align-items: center;
				flex: 1 auto;
				cursor: pointer;
				min-height: 44px;
			}
		}
	}
}
</style>
