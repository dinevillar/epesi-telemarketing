
var CallCampaignManualReports = {
  submit_form: function () {
    void (0)
  },
  init_telemarketer: function () {
    jq('#call_campaign_select').off('change.ccmr').on('change.ccmr', function () {
      jq('#telemarketers_select').val('')
      CallCampaignManualReports.submit_form()
    })

    jq('#telemarketers_select').off('change.ccmr').on('change.ccmr', function () {
      CallCampaignManualReports.submit_form()
    })
  },
  init_call_campaigns: function () {
    jq('#call_campaign_select').off('change.ccmr').on('change.ccmr', function () {
      CallCampaignManualReports.submit_form()
    })
  }
}
