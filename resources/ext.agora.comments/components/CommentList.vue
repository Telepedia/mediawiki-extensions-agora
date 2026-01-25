<template>
  <div class="agora-comments-list">
    <div class="comment-wrapper" v-for="comment in comments" :key="comment.id">
      <div class="comment">
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
          <div v-html="comment.html"></div>
          <div class="comment__body-interactions">
            <div class="comment__body-interactions-reply">
            <cdx-icon
                :icon="cdxIconSpeechBubbleAdd"
                size="small"
            ></cdx-icon> Reply
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
const { useCommentStore } = require("./../store.js");
const { defineComponent, computed, onMounted } = require( 'vue' );
const { cdxIconEllipsis, cdxIconSpeechBubbleAdd } = require( '../../icons.json' );
const { CdxIcon } = require( '../../codex.js' );
const Popover  = require( './Popover.vue' );
const restClient = require('telepedia.fetch');
module.exports = defineComponent( {
  name: "CommentList",
  components: {
    CdxIcon,
    Popover
  },
  setup() {
    const store = useCommentStore();

    const comments = computed( () => store.comments );

    onMounted(async () => {
      try {
        await store.fetchComments();
      } catch ( e ) {
        console.error( e );
      }
    } );

    /**
     * Simple map of each of the items in the dropdown
     * @TODO: add icons to this eventually
     */
    const commentActions = computed( () => {
      const actions = {
        edit: {
          title: mw.message( 'agora-action-edit' ).text(),
          action: () => console.log( 'Edit clicked' )
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
          action: ( id ) => deleteComment( id )
        };
      }

      return actions;
    } );

    /**
     * Delete a comment; if the user has the toggle to show deleted comments, the comment will still appear
     * in the list for them. If they have toggled that off, then it will immediately be removed from the stack
     * Deleting a comment will remove its children from the stack also
     * @param id
     * @returns {Promise<void>}
     */
    async function deleteComment( id ) {
      try {
        await restClient.delete(`/comments/v0/comments/delete`, {
          commentId: id,
          token: mw.user.tokens.get( 'csrfToken' )
        });

      } catch ( e ) {
        console.error( e );
      }
    }

    return {
      comments,
      cdxIconEllipsis,
      cdxIconSpeechBubbleAdd,
      commentActions
    }
  }
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';
.comment-wrapper {
  margin-bottom: 18px;

  p {
    margin: 0;
  }
}

.comment__body {
  padding: 18px;
  background: @background-color-neutral;
  width: 100%;
}

.comment {
  display: flex;
  flex-direction: row;
}

.comment__body-header {
  display: flex;
  flex-direction: row;
  align-items: center;
  margin-bottom: 14px;
}

.comment__body-details {
  display: flex;
  flex-direction: row;
  align-items: center;
  flex-grow: 1;
}

.comment__body-actions:hover {
  cursor: pointer;
}

.comment__body-details .time::before {
  content: "•";
  margin: 0 8px;
  font-size: 1rem;
  line-height: 1;
}

.comment__body-interactions {
  float: right;

  &-reply {
    display: flex;
    align-items: center;
    gap: 5px;
  }

  &:hover {
    cursor: pointer;
  }
}

.agora-actions-list {
  margin-left: 0;
  width: max-content;

  li {
    list-style: none;
  }
}

.comment__body-actions-wrapper {
  position: relative;
  display: inline-flex;
  align-items: center;

  &:hover {
    cursor: pointer;

    .agora-comment__popover {
      display: block;
    }
  }
}

.agora-actions-list {
  margin: 0;
  padding: 0;
  width: max-content;
  min-width: 140px;

  li {
    list-style: none;
    padding: 6px 12px;
    white-space: nowrap;

    &:hover {
      background-color: @background-color-neutral-subtle;
    }
  }
}
</style>