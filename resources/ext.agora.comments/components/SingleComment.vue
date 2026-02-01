<template>
	<div class="comment-wrapper">
		<div class="comment">
			<div v-if="comment.isDeleted" class="comment__body-deleted">
				<div class="comment__body-deleted-wrapper">
					<div class="comment__body-deleted-text">
						{{ $i18n('agora-comment-deleted').text() }}
					</div>
					<cdx-icon class="comment__restore-icon" :icon="cdxIconRestore" @click="restoreComment(comment.id)"></cdx-icon>
				</div>
			</div>

			<div class="comment__body">

				<div class="comment__body-header">
					<div class="comment__body-details">
						<div v-html="comment.getAvatar()"></div>
						<div v-html="comment.getLinkToUserPage()"></div>
						<div class="time" v-html="comment.getFormattedTime()"></div>
					</div>

					<div class="comment__body-actions-wrapper">
						<cdx-icon
							class="comment__body-actions"
							:icon="cdxIconEllipsis"
							size="small"
						></cdx-icon>

						<Popover>
							<ul class="agora-actions-list">
								<li
									v-for="( action, key ) in commentActions"
									:key="key"
									@click="action.action( comment.id )"
								>
									{{ action.title }}
								</li>
							</ul>
						</Popover>
					</div>
				</div>

				<div class="comment__content">
					<div v-if="isEditing">
						<div v-if="isLoadingHtml">Loading source...</div>
						<agora-editor
							v-else
							:initial-html="comment.html"
							@save="handleEditSave"
							@cancel="cancelEdit"
						></agora-editor>
					</div>

					<div v-else>
						<div v-html="comment.html"></div>

						<div class="comment__body-interactions">
							<div class="comment__body-interactions-reply">
								<cdx-icon :icon="cdxIconSpeechBubbleAdd"></cdx-icon> Reply
							</div>
						</div>
					</div>
				</div>

			</div>
		</div>
	</div>
</template>

<script>
const { defineComponent, ref, computed } = require( 'vue' );
const { cdxIconEllipsis, cdxIconSpeechBubbleAdd, cdxIconRestore } = require( '../../icons.json' );
const { CdxIcon } = require( '../../codex.js' );
const Popover  = require( './Popover.vue' );
const restClient = require('telepedia.fetch');
const AgoraEditor = require('./AgoraEditor.vue');
const { useCommentStore } = require("./../store.js");

module.exports = defineComponent( {
	name: "SingleComment",
	components: {
		CdxIcon,
		AgoraEditor,
		Popover
	},
	props: [ 'comment' ],
	emits: [ 'reply' ],
	setup( props ) {
		const store = useCommentStore();
		const isEditing = ref( false );
		const wikitext = ref( '' );
		const isLoadingHtml = ref( false );

		const startEdit = async () => {
			isEditing.value = true;
			isLoadingHtml.value = true;

			try {
				// here we need to fetch the HTML for the comment from the API to ensure at the point a user
				// starts editing, we are editing the most recent version - for now, we just use the one fetched
				// initially for development
				console.log( "..." )
			} catch ( e ) {
				console.error("Failed to fetch wikitext for comment", e );
			} finally {
				isLoadingHtml.value = false;
			}
		};

		const handleEditSave = async ( newWikitext ) => {
			try {
				await store.editComment( props.comment.id, newWikitext );
				isEditing.value = false;
			} catch ( e ) {
				console.error("Failed to edit comment", e );
			}
		};

		const cancelEdit = () => {
			isEditing.value = false;
			wikitext.value = '';
		};

		/**
		 * Simple map of each of the items in the dropdown
		 * @TODO: add icons to this eventually
		 */
		const commentActions = computed( () => {
			const actions = {
				edit: {
					// @TODO: move this - a user can only only edit if A) its their own comment or B) they're a mod
					// API needs to return eligibility with the response
					title: mw.message( 'agora-action-edit' ).text(),
					action: () => startEdit()
				},
				follow: {
					title: mw.message( 'agora-action-follow' ).text(),
					action: () => console.log( 'Follow clicked' )
				},
				report: {
					title: mw.message( 'agora-action-report' ).text(),
					action: () => console.log( 'Report clicked' )
				},
				history: {
					title: mw.message( 'agora-action-history' ).text(),
					action: () => console.log( 'History clicked' )
				}
			};

			// if the user has the comments-admin permission (the API returns this check and sets it on the store)
			// then add the delete action to the API - this is mostly just for visual, the API endpoint will enforce this in
			// any case
			if ( store.isModerator ) {
				actions.delete = {
					title: mw.message( 'agora-action-delete' ).text(),
					action: ( id ) => store.deleteComment( id )
				};
			}

			return actions;
		} );

		return {
			store,
			isEditing,
			wikitext,
			isLoadingHtml,
			startEdit,
			cancelEdit,
			handleEditSave,
			cdxIconEllipsis,
			cdxIconSpeechBubbleAdd,
			cdxIconRestore,
			commentActions,
			restoreComment: store.restoreComment
		}
	}
} );
</script>
