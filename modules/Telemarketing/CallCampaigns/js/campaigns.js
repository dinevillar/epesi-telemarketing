const CallCampaigns = {
  init: function () {
    const listTypeField = jQuery(':input#list_type')
    const leadListField = jQuery(':input[name=\'lead_list\']')
    const timelessField = jQuery('#timeless')
    jQuery(listTypeField).change(function () {
      const listType = jQuery(listTypeField).val()
      jQuery('tr.lead_list').hide()
      switch (listType) {
        case 'AP':
        case 'AC':
        case 'APC':
          jQuery(leadListField).val('')
          break
        default:
          jQuery(leadListField).val(jQuery('tr#lead_list_' + listType + ' :input').val())
          jQuery('tr#lead_list_' + listType).show()
      }
    })
    jQuery('tr.lead_list :input').change(function () {
      jQuery(leadListField).val(jQuery(this).val())
    })
    jQuery(timelessField).change(function () {
      if (jQuery(this).is(':checked')) {
        jQuery('#_end_date__data').parent('tr').hide()
      } else {
        jQuery('#_end_date__data').parent('tr').show()
      }
    })
    jQuery(listTypeField).trigger('change')
    jQuery(timelessField).trigger('change')
  }
}
