var WebPhone = {
  active: false,
  user: false,
  status: false,
  talk_time_start: false,
  talk_time: false,
  call_ended: false,
  callbacks: {
    'dtmfCb': [],
    'clearCb': [],
    'callCb': [],
    'endCb': [],
    'disconnectCb': [],
    'connectCb': [],
    'errorCb': []
  },
  putCursorAtEnd: function (elem) {
    jq(elem).focus()
    if (elem.setSelectionRange) {
      var len = jq(elem).val().length * 2
      this.setSelectionRange(len, len)
    } else {
      jq(this).val(jq(this).val())
    }
    this.scrollTop = 999999
  },
  add_cb: function (cb, f) {
    this.callbacks[cb].push(f)
  },
  call_cb: function (cb, params) {
    if (!jQuery.isArray(params)) {
      params = [params]
    }
    var callbacks = WebPhone.callbacks
    for (var i = 0; i < callbacks[cb].length; i++) {
      if (typeof callbacks[cb][i] == 'function') {
        callbacks[cb][i].apply(this, params)
      }
    }
  },
  trigger: function (event) {
    switch (event) {
      case 'error':
        jQuery('.web-phone-icon-mic').stop().fadeOut()
        jQuery('#web_phone_end_button').stop().fadeOut(function () {
          jQuery('#web_phone_call_button').stop().fadeIn()
        })
        this.status = 'ready'
        jQuery('.web_phone_screen').prop('disabled', false)
        if (WebPhone.talk_time_start) {
          WebPhone.talk_time = Math.floor((new Date().getTime() - WebPhone.talk_time_start.getTime()) / 1000)
        }
        WebPhone.call_cb('errorCb')
        break
      case 'connected':
        jQuery('#web_phone_call_button').stop().fadeOut(function () {
          jQuery('#web_phone_end_button').stop().fadeIn()
          jQuery('.web-phone-icon-mic').stop().fadeIn()
        })
        WebPhone.status = 'on_call'
        WebPhone.talk_time_start = new Date()
        WebPhone.call_cb('connectCb')
        break
      case 'disconnected':
        jQuery('.web-phone-icon-mic').stop().fadeOut()
        jQuery('#web_phone_end_button').stop().fadeOut(function () {
          jQuery('#web_phone_call_button').stop().fadeIn()
        })
        this.status = 'ready'
        jQuery('.web_phone_screen').prop('disabled', false)
        if (WebPhone.talk_time) {
          WebPhone.talk_time = Math.floor((new Date().getTime() - WebPhone.talk_time_start.getTime()) / 1000)
        }
        WebPhone.call_cb('disconnectCb')
        break
    }
  },
  setMessage: function (message, type, heading, fadetime) {
    switch (type) {
      case 'error':
        jQuery('.web_phone_status_c')
          .removeClass('web_phone_status_ok')
          .removeClass('web_phone_status_normal')
          .addClass('web_phone_status_error')

        break
      case 'ok':
        jQuery('.web_phone_status_c')
          .removeClass('web_phone_status_error')
          .removeClass('web_phone_status_normal')
          .addClass('web_phone_status_ok')
        break
      default:
        jQuery('.web_phone_status_c')
          .removeClass('web_phone_status_error')
          .removeClass('web_phone_status_ok')
          .addClass('web_phone_status_normal')
        break
    }
    if (heading) {
      jQuery('#web_phone_status').text(heading + ': ')
    }
    if (type == 'normal') {
      message = message + '...'
    }
    jQuery('#web_phone_status_spec').text(message)
    jQuery('.web_phone_status_c').show()
  },
  slideUp: function () {
    jQuery('#web_phone_slide').stop().slideUp(function () {
      jQuery('.web-phone-icon-up').toggle()
      jQuery('.web-phone-icon-down').toggle()
    })
  },
  slideDown: function () {
    jQuery('#web_phone_slide').stop().slideDown(function () {
      jQuery('.web-phone-icon-up').toggle()
      jQuery('.web-phone-icon-down').toggle()
    })
  },
  slide: function () {
    if (jQuery('#web_phone_slide').is(':visible')) {
      WebPhone.slideUp()
    } else {
      WebPhone.slideDown()
    }

  },
  init: function (user) {
    console.log(user);
    WebPhone.active = true
    WebPhone.user = user
    jQuery('.web_phone_item').show()
    jQuery('#web-phone-bar1').off('click.webphone').on('click.webphone', WebPhone.slide)

    jQuery('#web_phone_slide .web_phone_button').off('click.webphone').on('click.webphone', function () {
      var value = jQuery(this).val()
      switch (value) {
        case 'C':
          if (WebPhone.status == 'ready') {
            jQuery('.web_phone_screen').val('')
            jQuery('.web_phone_status_c').hide()
            WebPhone.call_cb('clearCb')
          }
          break
        case 'CALL':
          WebPhone.call()
          break
        case 'END':
          WebPhone.end_call()
          break
        default:
          if (WebPhone.status == 'ready' || WebPhone.status == 'on_call') {
            var curVal = jQuery('.web_phone_screen').val()
            jQuery('.web_phone_screen').val(curVal + value)
            WebPhone.putCursorAtEnd(jQuery('.web_phone_screen'))
            if (WebPhone.status == 'connected') {
              WebPhone.call_cb('dtmfCb', value)
            }
          }
          break
      }
    })

    jQuery('#web_phone_slide .web_phone_screen').off('keyup.webphone').on('keyup.webphone', function () {
      var val = jQuery(this).val()
      if (val != val.replace(/[^0-9#+\.]/g, '')) {
        jQuery(this).val(val.replace(/[^0-9#+\.]/g, ''))
      }
    })

    this.status = 'ready'
  },
  dial: function (phoneNumber) {
    if (this.status == 'ready') {
      WebPhone.slideDown()
      jQuery('.web_phone_screen').val(phoneNumber)
      this.call()
    } else {
      if (confirm('Web phone is busy or currently on a call. Do you want to end the current call and continue calling ' + phoneNumber + '?')) {
        this.end_call(function () {
          WebPhone.dial(phoneNumber)
        })
      }
    }
  },
  call: function () {
    if (this.status == 'ready') {
      this.talk_time_start = false
      var val = jQuery('.web_phone_screen').val()
      if (val.length >= 9) {
        jQuery('.web_phone_screen').prop('disabled', true)
        this.status = 'calling'
        this.call_cb('callCb', [val, this.user])
      } else {
        this.setMessage('Invalid Phone Number.', 'error', 'Error')
      }
    }
  },
  end_call: function (cb) {
    if (this.status == 'on_call' || this.status == 'calling') {
      if (cb) {
        this.call_cb('endCb', [cb])
      } else {
        this.call_cb('endCb')
      }
    }
  },
  destroy: function () {
    WebPhone.active = false
    jQuery('.web_phone_item').hide()
  }
}
