<template>
  <div class="agora-comments-list">
    <div class="comment-wrapper" v-for="comment in comments" :key="comment.id">
      <div class="comment">
        <div v-if="comment.isDeleted" class="comment__body-deleted">
          <div class="comment__body-deleted-wrapper">
            <div class="comment__body-deleted-text">
              {{ $i18n('agora-comment-deleted').text() }}
            </div>
            <cdx-icon class="comment__restore-icon" :icon="cdxIconRestore"></cdx-icon>
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
const { cdxIconEllipsis, cdxIconSpeechBubbleAdd, cdxIconRestore } = require( '../../icons.json' );
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
      commentActions,
      cdxIconRestore
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
  box-sizing: border-box;
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
  display: flex;
  justify-content: end;

  &-reply {
    display: flex;
    align-items: center;
    gap: 5px;

    &:hover {
      cursor: pointer;
    }
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

.comment__body-deleted {
  background-color: @background-color-neutral;
}

.comment__body-deleted-wrapper {
  padding: 12px 0 11px 0;
  margin: 0 24px;
  border-bottom: 1px solid @border-color-base;
  box-sizing: border-box;
  display: flex;

  .comment__body-deleted-text {
    flex-grow: 1;
  }
}


.comment__restore-icon {
  color: green;
  opacity: 0.5;

  &:hover {
    cursor: pointer;
    opacity: 1;
  }
}
</style>