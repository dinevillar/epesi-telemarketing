let Paginator = {
  get_js_paginate_callback: get_js_paginate_callback,
  paginate: paginate
}

let items_per_page, items_total, current_page, num_pages, mid_range, low,
  high, limit, ret, default_ipp, callback, disable_previous, disable_next, disable_all

function init_page (arr) {
  items_per_page = arr.items_per_page
  items_total = arr.items_total
  current_page = arr.current_page
  num_pages = arr.num_pages
  mid_range = arr.mid_range
  low = arr.low
  high = arr.high
  limit = arr.limit
  default_ipp = arr.default_ipp
  callback = arr.callback
  disable_previous = arr.disable_previous
  disable_next = arr.disable_next
  disable_all = arr.disable_all
}

function paginate (page, ipp) {
  if (ipp == 'All') {
    num_pages = Math.ceil(items_total / default_ipp)
    items_per_page = default_ipp
  } else {
    if (!is_numeric(items_per_page) || items_per_page <= 0) items_per_page = default_ipp
    num_pages = Math.ceil(items_total / items_per_page)
  }
  current_page = parseInt(page) // must be numeric > 0
  if (current_page < 1 || !is_numeric(current_page)) current_page = 1
  if (current_page > num_pages) current_page = num_pages
  let prev_page = current_page - 1
  let next_page = current_page + 1

  ret = ''
  if (num_pages > 10) {
    if (!disable_previous) {
      let prev_href = get_js_paginate_callback(prev_page, items_per_page)
      ret = '<td class=\'page_link\'>' + ((current_page != 1 && items_total >= 10) ? '<a class="paginate extra_button" ' + prev_href + '>« Previous</a> ' : '<span class="inactive" href="#">« Previous</span> ') + '</td>'
    }
    let start_range = current_page - Math.floor(mid_range / 2)
    let end_range = current_page + Math.floor(mid_range / 2)
    if (start_range <= 0) {
      end_range += Math.abs(start_range) + 1
      start_range = 1
    }
    if (end_range > num_pages) {
      start_range -= end_range - num_pages
      end_range = num_pages
    }
    let range = get_range(start_range, end_range, 1)
    let i = 1
    for (i = 1; i <= num_pages; i++) {
      if (range[0] > 2 && i == range[0]) ret += ' ... '

      if (i == 1 || i == num_pages || in_array(i, range)) {
        let page_href = get_js_paginate_callback(i, items_per_page)
        ret += '<td class=\'page_link\'>' + ((i == current_page && page != 'All') ? '<a title="Go to page ' + i + ' of ' + num_pages + '" class="current" href="javascript:void(0);">' + i + '</a> ' : '<a class="paginate" title="Go to page ' + i + ' of ' + num_pages + '" ' + page_href + '>' + i + '</a> ') + '</td>'
      }
      if (range[mid_range - 1] < num_pages - 1 && i == range[mid_range - 1]) ret += ' ... '
    }
    if (!disable_next) {
      let next_href = get_js_paginate_callback(next_page, items_per_page)
      ret += '<td class=\'page_link\'>' + (((current_page != num_pages && items_total >= 10) && (page != 'All')) ? '<a class="paginate extra_button" ' + next_href + '>Next »</a>\n' : '<span class="inactive" href="#">» Next</span>\n') + '</td>'
    }
    if (!disable_all) {
      let all_href = 'href=\'javascript:void(0);\' onclick=\'javascript:paginate(1,"All");\''
      ret += '<td class=\'page_link\'>' + ((page == 'All') ? '<a class="current" style="margin-left:10px" href="#">All</a> \n' : '<a class="paginate" style="margin-left:10px" ' + all_href + '>All</a> \n') + '</td>'
    }
  } else {
    let i = 1
    for (i = 1; i <= num_pages; i++) {
      let page_href = get_js_paginate_callback(i, items_per_page)
      ret += '<td class=\'page_link\'>' + ((i == current_page) ? '<a class="current" href="javascript:void(0);">' + i + '</a> ' : '<a class="paginate" ' + page_href + '>' + i + '</a> ') + '</td>'
    }
    if (!disable_all) {
      let all_href = 'href=\'javascript:void(0);\' onclick=\'javascript:paginate(1,"All");\''
      ret += '<td class=\'page_link\'>' + ('<a class="paginate" ' + all_href + '>All</a> \n') + '</td>'
    }
  }
  low = (current_page - 1) * items_per_page
  high = (ipp == 'All') ? items_total : (current_page * items_per_page) - 1
  limit = (ipp == 'All') ? '' : ' LIMIT ' + low + ',' + items_per_page
  jQuery('#pagination_row > .page_link').remove()
  jQuery('#pagination_row').prepend(ret)
  eval(callback + '(' + page + ');')
}

function get_js_paginate_callback (page, ipp) {
  return 'href=\'javascript:void(0);\' onclick=\'javascript:paginate(' + page + ',' + ipp + ');\''
}

function jump_page () {
  let value = jQuery('#jump_menu').val()
  paginate(value, items_per_page)
}

function get_range (low, high, step) {
  let matrix = []
  let inival, endval, plus
  let walker = step || 1
  let chars = false

  if (!isNaN(low) && !isNaN(high)) {
    inival = low
    endval = high
  } else if (isNaN(low) && isNaN(high)) {
    chars = true
    inival = low.charCodeAt(0)
    endval = high.charCodeAt(0)
  } else {
    inival = (isNaN(low) ? 0 : low)
    endval = (isNaN(high) ? 0 : high)
  }

  plus = ((inival > endval) ? false : true)
  if (plus) {
    while (inival <= endval) {
      matrix.push(((chars) ? String.fromCharCode(inival) : inival))
      inival += walker
    }
  } else {
    while (inival >= endval) {
      matrix.push(((chars) ? String.fromCharCode(inival) : inival))
      inival -= walker
    }
  }

  return matrix
}

function in_array (needle, haystack) {
  let length = haystack.length
  for (let i = 0; i < length; i++) {
    if (haystack[i] == needle) return true
  }
  return false
}

function is_numeric (mixed_let) {
  return (typeof (mixed_let) === 'number' || typeof (mixed_let) === 'string') && mixed_let !== '' && !isNaN(mixed_let)
}
