var Dialer = {
  submit_form: function () {
    void (0)
  },
  check_optimal: function () {
    var param = {
      'cid': Epesi.client_id,
      'campaign': Dialer.callcampaign,
      'record': Dialer.currentrecord,
      'record_type': Dialer.currentrecordtype
    }
    new Ajax.Request('modules/Apps/Dialer/checkoptimal.php', {
      method: 'post',
      parameters: param,
      onSuccess: function (t) {
        var result = JSON.parse(t.responseText)
        if (result.optimal) {
          Dialer.call_record(true)
        } else {
          var message = 'The current local time of this record is ' + result['record_local_time'] +
            '. This call campaign\'s optimal call time is ' + result['optimal_call_time_start'] +
            ' to ' + result['optimal_call_time_end'] + '. ' +
            'Are you sure you want to continue calling this record?'
          if (confirm(message)) {
            Dialer.call_record(true)
          }
        }
      },
      onException: function (t, e) {
        console.log(e)
      },
      onFailure: function (t) {
        Epesi.text(t.responseText, 'error_box', 'p')
      }
    })
  },
  call_record: function (nocheck) {
    if (Dialer.checkoptimal && !nocheck) {
      Dialer.check_optimal()
    } else {
      jQuery('#dialer_hidden_mode').val('call')
      Dialer.calledrecord = Dialer.currentrecord
      if (Dialer.autocallcdvar) {
        clearInterval(Dialer.autocallcdvar)
      }
      this.called = true
      Dialer.submit_form()
      window.setTimeout(function () {
        Dialer.talktimestart = new Date()
      }, 3000)
      Dialer.hide_dialog()
    }
  },
  is_web_phone_active: function () {
    return typeof WebPhone != 'undefined' && WebPhone.active
  },
  web_phone_attach: function () {
    if (this.is_web_phone_active()) {
      WebPhone.add_cb('disconnectCb', Dialer.end_call)
      WebPhone.add_cb('connectCb', function () {
        WebPhone.slideUp()
      })
    }
  },
  save_campaign: function () {
    jQuery('#dialer_hidden_mode').val('save')
    jQuery('#dialer_hidden_talktime').val(this.totaltalktime)
    this.submit_form()
  },
  add_log: function () {
    var val = jQuery(':input[name=\'log_text\']').val()
    if (val) {
      jQuery('#dialer_hidden_mode').val('add_log')
      this.submit_form()
      jQuery('#dialer_hidden_mode').val('')
    }
  },
  set_talk_time: function () {
    if (this.is_web_phone_active() && WebPhone.talk_time) {
      jQuery('#dialer_hidden_talktime').val(WebPhone.talk_time)
    } else {
      var talktime = Math.floor((new Date().getTime() - this.talktimestart.getTime()) / 1000)
      this.totaltalktime += talktime
      jQuery('#dialer_hidden_talktime').val(this.totaltalktime)
    }
  },
  end_call: function () {
    if (this.is_web_phone_active()) {
      WebPhone.end_call()
    }
    Dialer.autoscrollstop()
    this.calling = false
    jQuery('#dialer_hidden_mode').val('end_call')
    Dialer.set_talk_time()
    Dialer.submit_form()
  },
  end_call_save: function () {
    if (this.is_web_phone_active()) {
      WebPhone.end_call()
    }
    this.autoscrollstop()
    jQuery('#dialer_hidden_mode').val('end_call_save')
    this.set_talk_time()
    this.submit_form()
    this.hide_dialog()
  },
  save: function (nocheck) {
    if (!this.called && !nocheck) {
      if (confirm('Are you sure you want to save this disposition without calling the record?')) {
        Dialer.save(true)
      }
    } else {
      if (this.called) {
        this.autoscrollstop()
        this.set_talk_time()
      }
      jQuery('#dialer_hidden_mode').val('save')
      this.submit_form()
      this.hide_dialog()
    }
  },
  next: function () {
    jQuery('#dialer_hidden_mode').val('next')
    this.submit_form()
  },
  user_setting: function () {
    jQuery('#dialer_hidden_mode').val('user_setting')
    this.submit_form()
  },
  start_auto_call: function () {
    this.calledrecord = 0
    jQuery('#dialer_hidden_mode').val('start_auto_call')
    this.submit_form()
  },
  stop_auto_call: function () {
    jQuery('#dialer_hidden_mode').val('stop_auto_call')
    this.submit_form()
  },
  init: function () {
    Epesi.setCookie('recent_campaign_dialled', this.callcampaign)
    this.talktimestart = new Date()
    if (parseInt(this.currentrecord) !== parseInt(this.calledrecord)) {
      this.totaltalktime = 0
      this.called = 0
    }
    jQuery(document).ready(function () {

      jQuery('#dialer_auto_call_countdown').hide()
      this.autocallcdvar = false
      if (Dialer.autocall && parseInt(Dialer.currentrecord) !== parseInt(Dialer.calledrecord)) {
        Dialer.autocallcount(Dialer.call_record, Dialer.autocalldelay)
      }
      jQuery('#user_settings_container').on('change', ':input', function () {
        Dialer.user_setting()
      })
      var autoScrollSlider = jQuery('#auto_scroll_slider')
      jQuery(autoScrollSlider).slider({
        range: 'max',
        value: Dialer.autoscrollvalue,
        min: 1,
        max: 200,
        slide: function (event, ui) {
          Dialer.autoscrollvalue = ui.value
          if (Dialer.autoscrollinstance) {
            Dialer.autoscrollstop(true)
            Dialer.autoscrollstart(true)
          }
          jQuery('#auto_scroll_label').html('(' + Dialer.autoscrollvalue + 'ms)')
        }
      })

      jQuery(autoScrollSlider).on('mousewheel DOMMouseScroll', function (e) {
        var o = e.originalEvent
        var delta = o && (o.wheelDelta || (o.detail && -o.detail))

        if (delta) {
          e.preventDefault()

          var step = jQuery(this).slider('option', 'step')
          step *= delta < 0 ? 1 : -1
          var value = jQuery(this).slider('value')

          var result = jQuery(this).slider('option', 'slide').call(jQuery(this), e, { value: value + step })
          if (result !== false) {
            jQuery(this).slider('value', value + step)
          }
        }
      })

      jQuery('#auto_scroll_label').html('(' + jQuery(autoScrollSlider).slider('value') + 'ms)')

      jQuery(':input[name=\'log_text\']').keypress(function (e) {
        if ((e.which === 13 || e.keyCode === 13) && !e.shiftKey) {
          Dialer.add_log()
          e.preventDefault()
        }
      })
      var disposition = jQuery(':input[name=\'disposition\']')
      jQuery(disposition).change(function (e) {
        var val = jQuery(disposition).val()
        if (Dialer.cldispositions.indexOf(val) >= 0) {
          jQuery('#cl_extra_row :input').prop('disabled', false)
        } else {
          jQuery('#cl_extra_row :input').prop('disabled', true)
        }
      })
      jQuery(disposition).trigger('change')

      function rsize () {
        var windowHeight = jQuery(window).height()
        jQuery('#dialer_parent').css('height', (windowHeight - 155) + 'px')
      }

      jQuery(window).resize(rsize)
      rsize()

    })
  },
  autocallcount: function (cb, duration) {
    var container = jQuery('#auto_call_count').html(duration)
    jQuery('#dialer_auto_call_countdown').show('fast', function () {
      this.autocallcdvar = setInterval(function () {
        if (--duration) {
          container.html(duration)
        } else {
          jQuery('#dialer_auto_call_countdown').hide('fast', function () {
            cb.call()
          })
        }
      }, 1000)
    })
  },
  cancel_auto_call: function () {
    if (this.autocallcdvar) {
      clearInterval(this.autocallcdvar)
    }
    jQuery('#dialer_auto_call_countdown').hide()
  },
  autoscrollstart: function (notog) {
    if (!notog) {
      jQuery('#auto_scroll_start_button').hide()
      jQuery('#auto_scroll_pause_button').show()
    }
    if (Dialer.autoscrollinstance) {
      Dialer.autoscrollinstance.toggle()
    } else {
      Dialer.autoscrollinstance = new AutoDivScroll('callscript_content', Dialer.autoscrollvalue, 1, 1, null, function () {
        Dialer.autoscrollstop(false, true)
      })
    }
  },
  autoscrollstop: function (notog, topage) {
    if (!notog) {
      jQuery('#auto_scroll_start_button').show()
      jQuery('#auto_scroll_pause_button').hide()
    }
    if (Dialer.autoscrollinstance) {
      Dialer.autoscrollinstance.toggle()
      if (topage) {
        //TODO: Paginator enclose
        if (CallScripts.currentPage < items_total) {
          Paginator.paginate(CallScripts.currentPage + 1)
        } else {
          Dialer.autoscrollinstance = null
        }
      } else {
        Dialer.autoscrollinstance = null
      }
    }
  },
  hide_dialog: function () {
    leightbox_deactivate(Dialer.dialogname)
  },
  show_dialog: function (mode) {
    if (mode === 'disposition') {
      jQuery('#dialer_dialog_header').html('Set Call Disposition for ' + Dialer.recordinfo['identifier'])
      jQuery('.disposition_visible').show()
      jQuery('.call_visible').hide()
    } else {
      jQuery('#dialer_dialog_header').html('Call ' + Dialer.recordinfo['identifier'])
      jQuery('.disposition_visible').hide()
      jQuery('.call_visible').show()
    }
    leightbox_activate(Dialer.dialogname)
  },
  dialogname: '',
  cldispositions: [],
  callcampaign: null,
  autoscrollinstance: null,
  autoscrollvalue: 150,
  currentrecord: 0,
  currentrecordtype: 'contact',
  autocall: false,
  autocalldelay: 0,
  calledrecord: 0,
  autocallcdvar: false,
  talktimestart: new Date(),
  totaltalktime: 0,
  called: false,
  calling: false,
  recordinfo: [],
  checkoptimal: false
}
