<template>
	<div id="agora-input-box">
		<div class="agora-input-row" :class="{ 'agora-input-row--editor-open': isExpanded }">
			<div v-html="avatarHtml"></div>

			<div class="agora-input-body">
				<div v-if="isExpanded" class="agora-editor-container">
					<agora-editor
						:initial-html="''"
						@save="handleSave"
						@cancel="closeEditor"
					></agora-editor>
				</div>

				<div
					v-else
					class="agora-input-box-placeholder"
					@click="openEditor"
				>
					{{ placeholderText }}
				</div>
			</div>
		</div>
	</div>
</template>

<script>
const { defineComponent, ref, computed } = require( 'vue' );
const AgoraEditor = require( './AgoraEditor.vue' );
const { generateAvatar } = require( './../utils.js' );
const { useCommentStore } = require("./../store.js");
module.exports = defineComponent( {
	name: "AgoraCommentInput",
	components: {
		AgoraEditor
	},
	props: {
		parentId: {
			type: Number,
			default: null
		},
		placeholder: {
			type: String,
			default: ''
		}
	},
	emits: [ 'post-created', 'cancel' ],
	setup( props, { emit } ) {
		const store = useCommentStore();
		const isExpanded = ref( false );

		const avatarHtml = generateAvatar( store.currentUserAvatar );

		const placeholderText = computed(() =>
			props.placeholder || mw.message( 'agora-comments-input-placeholder', store.pageTitle ).text()
		);

		const openEditor = () => {
			isExpanded.value = true;
		};

		const closeEditor = () => {
			isExpanded.value = false;
			emit('cancel');
		};

		const handleSave = async ( wikitext ) => {
			try {
				await store.postComment({
					parentId: props.parentId,
					wikitext: wikitext
				});

				isExpanded.value = false;
				emit( 'post-created' );
			} catch ( e ) {
				console.error( "Failed to post comment", e );
				mw.notify( "Failed to post comment", { type: 'error' } );
			}
		};

		return {
			isExpanded,
			avatarHtml,
			placeholderText,
			openEditor,
			closeEditor,
			handleSave
		};
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

#agora-input-box {
	background: @background-color-base;
	border: 1px solid @border-color-subtle;
	border-radius: 3px;
	padding: 12px;
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.agora-input-row {
	display: flex;
	flex-direction: row;
	align-items: center;

	&.agora-input-row--editor-open {
		align-items: flex-start;
	}
}

.agora-avatar {
	color: inherit;
	height: 30px;
	margin-right: 12px;
	min-width: 30px;
	width: 30px;
}

.agora-avatar__img {
	border-radius: 50%;
	border: 2px solid @border-color-base;
	box-sizing: border-box;
	display: inline-block;
	fill: currentcolor;
	height: 100%;
	min-width: 100%;
	object-fit: contain;
	width: 100%;
}

.agora-input-body {
	flex: 1;
}

.agora-input-box-placeholder {
	font-size: 16px;
	line-height: 1;

	&:hover {
		cursor: pointer;
	}
}

.agora-editor-actions {
	display: flex;
	justify-content: flex-end;
	gap: 8px;
}

.agora-input-row.agora-input-row--editor-open {
	padding-bottom: 18px;
	border-bottom: 1px solid @border-color-base;
}

.agora-editor-container {
	p {
		font-size: 16px;
	}

	.oo-ui-toolbar-bar {
		width: fit-content;
		box-shadow: none;
		border-radius: 3px;
		border: 1px solid @border-color-muted;
	}
}

.ve-mount-point {
	min-height: 8em;
}
</style>
