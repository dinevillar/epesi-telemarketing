/**
 * Created by RodineMarkPaul on 4/3/2016.
 */

var EpesiTwilio = {
  previousCallStatus: false,
  connection: false,
  disconnectCb: false,
  setupParams: {
    'debug': true,
    'closeProtection': true
  },
  callParams: {
    'epesi_cid': Epesi.client_id
  },
  webPhoneAttach: function () {
    WebPhone.add_cb('callCb', EpesiTwilio.call)
    WebPhone.add_cb('endCb', EpesiTwilio.end)
    setTimeout(function () {
      EpesiTwilio.requestToken()
    }, 2000)
    //TODO: DTMF
  },
  end: function (cb) {
    this.disconnectCb = cb
    if (EpesiTwilio.connection && EpesiTwilio.connection.status() !== 'closed') {
      EpesiTwilio.connection.disconnect()
    }
  },
  call: function (phoneNumber, user) {
    try {
      var status = Twilio.Device.status()
    } catch (Exception) {
      status = 'offline'
    }
    if (status == 'offline') {
      EpesiTwilio.requestToken(user)
    } else if (status == 'busy') {
      WebPhone.setMessage('Twilio Device is busy.', 'error', 'Twilio Error')
      WebPhone.trigger('error')
    } else {
      EpesiTwilio.previousCallStatus = false
      WebPhone.setMessage(phoneNumber, 'normal', 'Dialing')
      var callParams = EpesiTwilio.callParams
      callParams['To'] = phoneNumber
      callParams['epesi_user'] = user
      callParams['action'] = 'call'
      EpesiTwilio.connection = Twilio.Device.connect(callParams)
      EpesiTwilio.connection.disconnect(function (cn) {
        WebPhone.trigger('disconnected')
        WebPhone.setMessage('Your call to ' + cn.message.To + ' has ended.', 'normal', 'Call Ended')
        if (EpesiTwilio.disconnectCb && typeof EpesiTwilio.disconnectCb == 'function') {
          EpesiTwilio.disconnectCb()
          EpesiTwilio.disconnectCb = false
        } else {
          setTimeout(function () {
            EpesiTwilio.queryCallStatus(cn)
          }, 2000)
        }
      })
    }
  },
  queryCallStatus: function (c) {
    if (c.hasOwnProperty('parameters') && c.parameters.hasOwnProperty('CallSid')) {
      var call_sid = c.parameters.CallSid
      new Ajax.Request('modules/Libs/Twilio/call_log_handler.php', {
        method: 'post',
        parameters: {
          call_sid: call_sid,
          cid: Epesi.client_id,
          user: c.message.epesi_user
        },
        onSuccess: function (t) {
          if (t.responseText.trim()) {
            EpesiTwilio.previousCallStatus = t.responseText.trim()
          }
        },
        onException: function (t, e) {
          console.error(e)
        },
        onFailure: function (t) {
          console.error(t)
        }
      })
    }
  },
  requestToken: function () {
    WebPhone.setMessage('Authorizing...', 'normal')
    new Ajax.Request('modules/Libs/Twilio/token_handler.php', {
      method: 'post',
      parameters: {
        user: WebPhone.user,
        cid: Epesi.client_id
      },
      onSuccess: function (t) {
        var result = JSON.parse(t.responseText)
        if (result.status == 1) {
          WebPhone.setMessage('Connecting...', 'normal')

          Twilio.Device.setup(result.message, EpesiTwilio.setupParams)

          Twilio.Device.offline(function () {
            EpesiTwilio.requestToken()
          })

          Twilio.Device.error(function (error) {
            WebPhone.setMessage(error.message, 'error', 'Twilio Error ' + error.code)
            //WebPhone.trigger('error');
            console.log(error)
          })

          Twilio.Device.connect(function (cn) {
            WebPhone.trigger('connected')
            WebPhone.setMessage('You are now connected to ' + cn.message.To, 'ok', 'On call')
          })

          Twilio.Device.disconnect(function (cn) {
            WebPhone.trigger('disconnected')
            WebPhone.setMessage('Your call to ' + cn.message.To + ' has ended.', 'normal', 'Call Ended')
            if (EpesiTwilio.disconnectCb && typeof EpesiTwilio.disconnectCb == 'function') {
              EpesiTwilio.disconnectCb()
              EpesiTwilio.disconnectCb = false
            } else {
              setTimeout(function () {
                EpesiTwilio.queryCallStatus(cn)
              }, 2000)
            }
          })

          Twilio.Device.ready(function () {
            WebPhone.setMessage('Ready...', 'ok')
          })

        } else {
          WebPhone.setMessage(result.message, 'error', 'Twilio Error')
        }
      },
      onException: function (t, e) {
        WebPhone.setMessage(e, 'error', 'Error')
        WebPhone.trigger('error')
        console.error(e)
      },
      onFailure: function (t) {
        WebPhone.setMessage('Failure (' + t.status + ')', 'error', 'Error')
        WebPhone.trigger('error')
        console.error(t)
      }
    })
  }
}
