function Premium_Telemarketing_MergeFields_InitJS () {
  jQuery('.merge_fields_accordion').accordion({
    header: '> div.merge_fields > div.merge_field_group',
    collapsible: true,
    heightStyle: 'content',
    active: false,
    icons: {
      header: 'ui-icon-circle-triangle-s',
      activeHeader: 'ui-icon-circle-triangle-e'
    }
  })
}

function append_to_ck_or_textarea (id, value) {
  if (typeof CKEDITOR === 'undefined') {
    insertTextAtCursor(document.getElementById(id), value)
  } else {
    if (CKEDITOR && CKEDITOR.instances[id]) {
      CKEDITOR.instances[id].insertText(value)
      CKEDITOR.instances[id].focus()
    } else {
      insertTextAtCursor(document.getElementById(id), value)
    }
  }
}

function insertTextAtCursor (myField, myValue) {
  //IE support
  if (document.selection) {
    myField.focus()
    sel = document.selection.createRange()
    sel.text = myValue
  } else if (myField.selectionStart || myField.selectionStart == '0') {
    var startPos = myField.selectionStart
    var endPos = myField.selectionEnd
    myField.value = myField.value.substring(0, startPos)
      + myValue
      + myField.value.substring(endPos, myField.value.length)
    myField.selectionStart = startPos + myValue.length
    myField.selectionEnd = startPos + myValue.length
  } else {
    myField.value += myValue
  }
}
