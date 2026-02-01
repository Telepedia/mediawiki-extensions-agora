<template>
	<div class="agora-editor-wrapper">
		<div ref="veContainer" class="ve-mount-point"></div>
		<div class="agora-editor-actions">
			<cdx-button weight="quiet" @click="$emit('cancel')">Cancel</cdx-button>
			<cdx-button
				action="progressive"
				weight="primary"
				:disabled="isSaving"
				@click="onSave"
			>
				Post
			</cdx-button>
		</div>
	</div>
</template>

<script>
const { defineComponent, emit, ref, onMounted, onBeforeUnmount } = require( 'vue' );
const { CdxButton } = require( '../../codex.js' );
module.exports = defineComponent( {
	name: "AgoraEditor",
	components: {
		CdxButton
	},
	props: {
		initialHtml: {
			type: String,
			default: ''
		}
	},
	emits: [ 'save', 'cancel' ],
	setup( props, { emit } ) {
		const veContainer = ref( null );
		let editorInstance = null;
		const isSaving = ref( false );

		onMounted( () => {
			try {
				const target = new mw.agora.ve.Target();
				target.$element = $( veContainer.value );

				target.loadContent( props.initialHtml );

				editorInstance = target;
			} catch ( e ) {
				console.error( "Initialising VE failed", e )
			}
		} );

		onBeforeUnmount( () => {
			if ( editorInstance ) {
				editorInstance.destroy();
				editorInstance = null;
			}
		} );

		const onSave = async () => {
			if ( !editorInstance ) return;

			isSaving.value = true;

			try {
				const surface = editorInstance.getSurface();
				const doc = surface.getModel().getDocument();

				const wikitext = await editorInstance.getWikitextFragment( doc );

				emit( 'save', wikitext );
			} catch ( e ) {
				console.error( e );
			} finally {
				isSaving.value = false;
			}
		}

		return {
			veContainer,
			onSave,
			isSaving
		}
	}
} );
</script>
