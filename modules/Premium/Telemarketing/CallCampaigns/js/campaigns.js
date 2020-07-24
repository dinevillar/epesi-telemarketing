const CallCampaigns = {
    setCookie: function (key, value, expires) {
        if (!expires) {
            expires = '0';
        } else {
            var d = new Date();
            expires.setTime(d.getTime() + expires);
            expires = expires.toUTCString();
        }
        document.cookie = key + '=' + value + ';expires=' + expires;
    },
    getCookie: function (key) {
        var keyValue = document.cookie.match('(^|;) ?' + key + '=([^;]*)(;|$)');
        return keyValue ? keyValue[2] : null;
    },
    deleteCookie: function (key) {
        document.cookie = key + '=;expires=Thu, 01 Jan 1970 00:00:01 GMT;';
    },
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
