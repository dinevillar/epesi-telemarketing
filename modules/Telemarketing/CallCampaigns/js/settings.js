var CallCampaignSettings = {
  init: function () {
    jq('#cc_settings_form .epesi_label').css('width', '300px')
    var phonePrioSelector = ':input[name=\'prio_work\'], :input[name=\'prio_home\'], :input[name=\'prio_mobile\']'
    var phonePrev
    var self = this
    jq(phonePrioSelector).on('focus', function () {
      phonePrev = jq(this).val()
    }).change(function () {
      var sel = this
      jq.each(jq(phonePrioSelector), function (i, phonePrio) {
        if (jq(phonePrio).attr('name') == jq(sel).attr('name')) {
          return true
        }
        if (jq(phonePrio).val() == jq(sel).val()) {
          jq(phonePrio).val(phonePrev)
        }
      })
    })
  }
}
