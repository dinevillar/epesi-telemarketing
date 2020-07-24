CKEDITOR.dialog.add('page_link_dialog', function (editor) {
  return {
    title: 'Call Script Page Link Properties',
    minWidth: 400,
    minHeight: 200,
    onShow: function () {
      var selection = editor.getSelection()
      var element = selection.getStartElement()
      if (element)
        element = element.getAscendant('input', true)

      if (!element || element.getName() != 'input') {
        element = editor.document.createElement('input')
        this.insertMode = true
      } else
        this.insertMode = false
      this.element = element
      this.setupContent(this.element)
    },
    contents: [
      {
        id: 'general',
        label: 'Page Link Properties',
        elements: [
          {
            type: 'html',
            html: 'This dialog window lets you create page links for your call script.'
          },
          {
            type: 'text',
            id: 'display_text',
            label: 'Link label',
            validate: CKEDITOR.dialog.validate.notEmpty('Please enter the link label.'),
            setup: function (element) {
              if (element.hasAttribute('value')) {
                this.setValue(element.getValue())
              }
            },
            required: true,
            commit: function (element) {
              element.setValue(this.getValue())
            }
          },
          {
            type: 'select',
            id: 'page_number',
            label: 'Page Number',
            validate: CKEDITOR.dialog.validate.notEmpty('Please choose a page.'),
            required: true,
            items: [
              ['<none>', '']
            ],
            commit: function (element) {
              element.setAttribute('rel', this.getValue())
              element.setAttribute('title', 'Go to page ' + this.getValue())
            }
          },
        ]
      }
    ],
    onOk: function () {
      var dialog = this,
        link = this.element

      this.commitContent(link)
      if (this.insertMode) {
        link.setAttribute('class', 'page_link_button')
        link.setAttribute('type', 'button')
        editor.insertElement(link)
      }
    }
  }
})
