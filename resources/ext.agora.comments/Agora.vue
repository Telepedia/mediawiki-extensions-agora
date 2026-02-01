<template>
	<div id="agora-container">
		<agora-comment-input
			:placeholder="$i18n( 'agora-comments-input-placeholder', store.pageTitle ).text()"
		></agora-comment-input>
		<h3>{{ $i18n( 'agora-comments-header', store.totalComments ).text() }}</h3>
		<comment-list></comment-list>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { useCommentStore } = require( './store.js' );
const AgoraCommentInput = require( './components/AgoraCommentInput.vue' );
const CommentList = require( './components/CommentList.vue' );
module.exports = defineComponent( {
  name: 'Agora',
  components: {
    CommentList,
	AgoraCommentInput
  },
  setup() {
	const store = useCommentStore();
	store.initFromMW();
	return {
		store
	};
  }
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';
#agora-container {
  margin-top: 15px;
}
</style>
