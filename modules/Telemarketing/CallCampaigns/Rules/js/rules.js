var CallCampaignRules = {
  callCampaign: 0,
  queryTimer: false,
  doQuery: false,
  allowCookie: false,
  ckinstances: {},
  ckmodpath: '',
  updateDisabled: function (en, dis) {
    if (en) jq(en).prop('disabled', true)
    if (dis) jq(dis).prop('disabled', false)
  },
  copyToClipboard: function (text) {
    var aux = document.createElement('input')
    aux.setAttribute('value', text)
    document.body.appendChild(aux)
    aux.select()
    document.execCommand('copy')
    document.body.removeChild(aux)
  },
  submit_form: function () {
    void (0)
  },
  init: function () {
    CallCampaignRules.doQuery = false
    jq('.rule_item .rule_item_field_change').off('change').on('change', function () {
      var val = jq(this).val()
      var _rel = jq(this).attr('rel').split(':')
      var _name = _rel[0] + '_' + val.toLowerCase()
      var _index = _rel[1]
      var _level = (_name.match(/_/g) || []).length
      jq.each(jq('#rule_item_' + _index + ' .rule_item_field'), function () {
        var rel = jq(this).attr('rel').split(':')
        var name = rel[0]
        var index = rel[1]
        var level = (name.match(/_/g) || []).length
        if ((level >= _level && name == _name) || (level < _level && _name.substring(0, name.length) == name)) {
          jq(this).closest('div.field_group :input').prop('disabled', false)
          jq(this).closest('div.field_group').show()
        } else {
          jq(this).closest('div.field_group :input').prop('disabled', true)
          jq(this).closest('div.field_group').hide()
        }
      })
      jq(':input[rel="' + _name + ':' + _index + '"]').trigger('change')
      if (CallCampaignRules.doQuery) {
        if (CallCampaignRules.queryTimer) {
          clearTimeout(CallCampaignRules.queryTimer)
        }
        CallCampaignRules.queryTimer = setTimeout(function () {
          CallCampaignRules.query_details(_index)
        }, 100)
      }
    })

    jq('.rule_item .rule_item_field_root').trigger('change')
    CallCampaignRules.doQuery = true
    CallCampaignRules.reload_ck()
    if (CallCampaignRules.allowCookie) {
      var shownRules = Epesi.getCookie('shown_rules')
      if (shownRules != null) {
        shownRules = JSON.parse(shownRules)
        jq.each(shownRules, function (key, val) {
          if (val !== null) {
            jq('#rule_item_' + val + ' .details_box').show()
            jq('#rule_item_' + val + ' .show_text').hide()
            jq('#rule_item_' + val + ' .hide_text').show()
          }
        })
      }
    }
  },
  reload_ck: function () {
    jq.each(jq('.rule_ck_field'), function () {
      var id = jq(this).attr('id')
      var rel = jq(this).attr('rel')
      rel = rel.split(';')
      if (CallCampaignRules.ckinstances.hasOwnProperty(id)) {
        try {
          CallCampaignRules.ckinstances[id].destroy()
        } catch (e) {
        }
      }
      var config = {
        contentsCss: CKEDITOR.basePath + 'contents.css'
      }
      for (var i = 0; i < rel.length; i++) {
        var cp = rel[i].split(':')
        if (cp[0] == 'toolbar') {
          config['toolbar'] = cp[1]
        }
      }
      CallCampaignRules.ckinstances[id] = CKEDITOR.replace(id, config)
      CallCampaignRules.ckinstances[id].on('change', function () {
        if (CallCampaignRules.ckinstances[id].checkDirty()) {
          jq('#' + id).val(CallCampaignRules.ckinstances[id].getData())
        }
      })
    })
  },
  add_rule: function () {
    jq('#rules_hidden_mode').val('add')
    this.submit_form()
    window.scrollTo(0, document.body.scrollHeight)
  },
  delete_rule: function (index) {
    if (confirm('Are you sure you want to delete this rule?')) {
      jq('#rules_hidden_mode').val('delete_' + index)
      this.submit_form()
    }
  },
  show_details: function (index) {
    jq('#rule_item_' + index + ' .details_box').toggle()
    jq('#rule_item_' + index + ' .show_text').toggle()
    jq('#rule_item_' + index + ' .hide_text').toggle()
    if (CallCampaignRules.allowCookie) {
      if (jq('#rule_item_' + index + ' .details_box').is(':visible')) {
        var shownRules = Epesi.getCookie('shown_rules')
        if (shownRules == null) {
          shownRules = [index]
          Epesi.setCookie('shown_rules', JSON.stringify(shownRules))
        } else {
          shownRules = JSON.parse(shownRules)
          if (shownRules.indexOf(index) < 0) {
            shownRules.push(index)
          }
          Epesi.setCookie('shown_rules', JSON.stringify(shownRules))
        }
      } else {
        var shownRules = Epesi.getCookie('shown_rules')
        if (shownRules) {
          shownRules = JSON.parse(shownRules)
          if (shownRules.indexOf(index) >= 0) {
            shownRules.splice(shownRules.indexOf(index), 1)
          }
          Epesi.setCookie('shown_rules', JSON.stringify(shownRules))
        }
      }
    }
  },
  build_rule: function (index) {
    var rule = {
      'type': '',
      'condition': '',
      'action': ''
    }
    jq.each(jq('#rule_item_' + index + ' :input:visible'), function () {
      if (jq(this).attr('name')) {
        var name = jq(this).attr('name').replace(/\[(.+?)\]/g, '')
        if (name.match(/^callcampaign_rules/)) {
          var val = jq(this).val()
          if (name.match(/_type$/)) {
            rule.type = val
          } else if (name.match(/_condition_0$/)) {
            rule.condition = val + rule.condition
          } else if (name.match(/_condition_[0-9]+$/)) {
            rule.condition = rule.condition + ':' + val
          } else if (name.match(/_action_0$/)) {
            rule.action = val + rule.action
          } else if (name.match(/_action_[0-9]+$/)) {
            rule.action = rule.action + ':' + val
          }
        }
      }
    })
    return rule
  },
  query_details: function (index) {
    var param = {
      index: index,
      rule: JSON.stringify(CallCampaignRules.build_rule(index)),
      cid: Epesi.client_id
    }
    if (CallCampaignRules.callCampaign) {
      param.call_campaign = CallCampaignRules.callCampaign
    }
    Epesi.procOn++
    Epesi.updateIndicator()
    new Ajax.Request('modules/Telemarketing/CallCampaigns/Rules/details.php', {
      method: 'post',
      parameters: param,
      onComplete: function (t) {
        Epesi.procOn--
        Epesi.append_js('Event.fire(document,\'e:load\');Epesi.updateIndicator();')
      },
      onSuccess: function (t) {
        if (t.responseText) {
          jq('#rule_item_' + index + ' .has_details').show()
          jq('#rule_item_' + index + ' .details_box').html(t.responseText)
          if (!jq('#rule_item_' + index + ' .details_box').is(':visible')) {
            CallCampaignRules.show_details(index)
          }
          CallCampaignRules.reload_ck()
        } else {
          jq('#rule_item_' + index + ' .has_details').hide()
          jq('#rule_item_' + index + ' .details_box').html('')
          jq('#rule_item_' + index + ' .details_box').hide()
        }
      },
      onException: function (t, e) {
        console.log(e)
      },
      onFailure: function (t) {
        alert('Failure (' + t.status + ')')
        Epesi.text(t.responseText, 'error_box', 'p')
      }
    })
  }
}
