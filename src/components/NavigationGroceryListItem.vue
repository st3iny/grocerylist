<template>
	<NcAppNavigationItem :name="title"
		:icon="icon"
		:editable="true"
		:edit-label="rename"
		:to="{ name: 'list', params: { listId: groceryList.id.toString() } }"
		@click="openGroceryList(groceryList)"
		@update:name="onRename">
		<template #actions>
			<NcActionButton icon="icon-rename"
				@click="openSettings(groceryList)">
				{{ t('grocerylist', 'Settings') }}
			</NcActionButton>
			<NcActionButton icon="icon-delete"
				@click="deleteGroceryList(groceryList)">
				{{ t('grocerylist', 'Delete list') }}
			</NcActionButton>
		</template>
	</NcAppNavigationItem>
</template>
<script>
import { showError, showInfo, showSuccess } from '@nextcloud/dialogs'
import {
	NcActionButton,
	NcAppNavigationItem,
} from '@nextcloud/vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'NavigationGroceryListItem',
	components: {
		NcActionButton,
		NcAppNavigationItem,
	},
	props: {
		groceryList: {
			type: Object,
			required: true,
		},
	},
	emits: ['delete'],
	data() {
		return {}
	},
	computed: {
		icon() {
			return 'icon-toggle-filelist'
		},
		rename() {
			return 'Rename ' + this.groceryList.title
		},
		title() {
			return this.groceryList.title
		},
		currentGroceryListId() {
			return parseInt(this.$route.params.listId)
		},
	},
	methods: {
		async onRename(newTitle) {
			showInfo('rename to ' + newTitle)
			console.error('rename to ' + newTitle)
			this.updating = true
			this.groceryList.title = newTitle

			try {
				if (this.groceryList.id === -1) {
					const response = await axios.post(generateUrl('/apps/grocerylist/api/lists'), this.groceryList)
				} else {
					await axios.post(generateUrl(`/apps/grocerylist/api/lists/${this.groceryList.id}`), this.groceryList)
				}
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not create groceryList'))
			}
			this.updating = false
		},
		async deleteGroceryList(groceryList) {
			try {
				await axios.delete(generateUrl(`/apps/grocerylist/api/lists/${this.groceryList.id}`))
				// this.groceryLists.splice(this.groceryLists.indexOf(groceryList), 1)
				// if (this.currentGroceryListId === groceryList.id) {
				// 	this.currentGroceryListId = null
				// }
				showSuccess(t('grocerylist', 'GroceryList deleted'))
				this.$emit('delete')
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not delete groceryList'))
			}
		},
		openSettings(groceryList) {
			this.$router.push({
				name: 'settings',
				params: {
					listId: groceryList.id,
				},
			})
		},
		openGroceryList(groceryList) {
			this.$router.push({
				name: 'list',
				params: {
					listId: groceryList.id,
				},
			})
		},
	},
}
</script>
