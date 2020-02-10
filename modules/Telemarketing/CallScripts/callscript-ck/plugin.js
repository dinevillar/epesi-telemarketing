CKEDITOR.plugins.add('callscript',
  {
    init: function (editor) {
      //PAGE LINK
      editor.addCommand('page_link_dialog', new CKEDITOR.dialogCommand('page_link_dialog'))
      if (editor.contextMenu) {
        editor.addMenuGroup('callscript_group')
        editor.addMenuItem('page_link_item', {
          label: 'Edit Page Link',
          icon: this.path + 'page_go.png',
          command: 'page_link_dialog',
          group: 'callscript_group'
        })
        editor.contextMenu.addListener(function (element) {
          if (element.getAscendant('input', true) && element.getAttribute('class') == 'page_link_button') {
            return { page_link_item: CKEDITOR.TRISTATE_OFF }
          }
        })
      }
      CKEDITOR.dialog.add('page_link_dialog', this.path + 'dialogs/page_link.js')

      editor.addCommand('collapse_dialog', new CKEDITOR.dialogCommand('collapse_dialog'))
      if (editor.contextMenu) {
        editor.addMenuGroup('callscript_group')
        editor.addMenuItem('collapse_item', {
          label: 'Edit Collapsible Text',
          icon: this.path + 'collapse.gif',
          command: 'collapse_dialog',
          group: 'callscript_group'
        })
        editor.contextMenu.addListener(function (element) {
          var div = element.getAscendant('div')
          if (div && div.getAttribute('class') == 'collapsible') {
            return { collapse_item: CKEDITOR.TRISTATE_OFF }
          }
        })
      }
      CKEDITOR.dialog.add('collapse_dialog', this.path + 'dialogs/collapsible.js')
    }

  })
