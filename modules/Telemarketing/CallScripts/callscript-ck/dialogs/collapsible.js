CKEDITOR.dialog.add('collapse_dialog', function (editor) {
  return {
    title: 'Call Script Page Collpasible Text',
    minWidth: 400,
    minHeight: 200,
    onShow: function () {
      var selection = editor.getSelection()
      var element = selection.getStartElement()

      if (element)
        element = element.getAscendant('div')

      if (!element || element.getName() != 'div' || element.getAttribute('class') != 'collapsible') {
        element = editor.document.createElement('div')
        this.insertMode = true
      } else
        this.insertMode = false
      this.element = element
      if (!this.insertMode)
        this.setupContent(this.element)
    },
    contents: [
      {
        id: 'general',
        label: 'Collapsible Text Properties',
        elements: [
          {
            type: 'html',
            html: 'This dialog window lets you create collapsible texts in your call script.'
          },
          {
            type: 'text',
            id: 'display_text',
            label: 'Collapse button label',
            validate: CKEDITOR.dialog.validate.notEmpty('Please enter the collapse button label.'),
            setup: function (element) {
              var label_element = element.getChild(0)
              this.setValue(label_element.getHtml())
            },
            required: true,
            commit: function (element) {
              var count = element.getChildCount()
              if (count < 1) {
                var label_element = editor.document.createElement('a')
                label_element.setAttribute('href', 'javascript:void(0);')
                label_element.setAttribute('class', 'collapsible_button')
                label_element.setHtml(this.getValue())
                element.appendHtml(label_element.getOuterHtml())
              } else {
                var label_element = element.getChild(0)
                label_element.setHtml(this.getValue())
              }
            }
          },
          {
            type: 'hbox',
            widths: ['40%', '60%'],
            children: [

              {
                type: 'select',
                id: 'merge_field',
                label: '',
                items: [
                  ['<none>', '']
                ]
              },
              {
                type: 'button',
                id: 'merge_button',
                label: 'Insert Placeholder',
                title: 'Insert Placeholder',
                onClick: function () {
                  var dialog = this.getDialog()
                  var merge_field_obj = dialog.getContentElement('general', 'merge_field')
                  var content_obj = dialog.getContentElement('general', 'content')
                  var selected_merge = merge_field_obj.getValue()
                  var prev_value = content_obj.getValue()
                  content_obj.setValue(prev_value + '[' + selected_merge.replace('f_', '') + ']')
                }
              }
            ]
          },
          {
            type: 'textarea',
            id: 'content',
            label: 'Content',
            default: '',
            validate: CKEDITOR.dialog.validate.notEmpty('Please enter the content.'),
            required: true,
            setup: function (element) {
              var content_element = element.getChild(1)
              var valu = content_element.getHtml().split(/<br[^>]*>/gi)
              this.setValue(valu.join('\r\n'))
            },
            commit: function (element) {
              var count = element.getChildCount()
              if (count < 2) {
                var content_element = editor.document.createElement('div')
                arrayOfLines = this.getValue().match(/[^\r\n]+/g)
                content_element.setHtml(arrayOfLines.join('<br/>'))
                element.appendHtml(content_element.getOuterHtml())
              } else {
                var content_element = element.getChild(1)
                arrayOfLines = this.getValue().match(/[^\r\n]+/g)
                content_element.setHtml(arrayOfLines.join('<br/>'))
              }
            }
          }, {
            type: 'select',
            id: 'size',
            label: 'Size',
            setup: function (element) {
              var content_element = element.getChild(1)
              var size = content_element.getStyle('width')
              switch (size) {
                case '25%':
                  this.setValue('small')
                  break
                case '60%':
                  this.setValue('med')
                  break
                default:
                  this.setValue('full')
                  break
              }
            },
            required: true,
            items: [
              ['Small', 'small'],
              ['Medium', 'med'],
              ['Full', 'full']
            ],
            commit: function (element) {
              var value = this.getValue()
              var content_element = element.getChild(1)
              switch (value) {
                case 'small':
                  content_element.setStyle('width', '25%')
                  break
                case 'med':
                  content_element.setStyle('width', '60%')
                  break
                case 'full':
                  content_element.setStyle('width', '95%')
                  break
              }
            },
            default: 'full'
          },
        ]
      }
    ],
    onOk: function () {
      var dialog = this,
        div = this.element

      this.commitContent(div)

      if (this.insertMode) {
        div.setAttribute('class', 'collapsible')
        editor.insertElement(div)
      }
    }
  }
})
