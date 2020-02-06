<?php
/**
 * @Author: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 2/3/20
 * @Time: 2:17 PM
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Utils_Paginator extends Module
{
    var $items_per_page;
    var $items_total;
    var $current_page;
    var $num_pages;
    var $mid_range;
    var $low;
    var $high;
    var $limit;
    var $return;
    var $default_ipp;

    private $theme;
    private $tpl;
    private $page_links = array();
    private $previous_link;
    private $next_link;
    private $all_link;
    private $client = false;
    private $js_callback;

    public $show_raw = false;
    public $disable_previous = false;
    public $disable_next = false;
    public $disable_all = false;
    public $show_page_label = false;


    public function construct($ipp = 1) //defaults only.
    {
        $this->default_ipp = $ipp;
        $this->current_page = 1;
        $this->mid_range = 9;
        $this->theme = $this->init_module('Base/Theme');
        $this->items_per_page = (!empty($_REQUEST['ipp'])) ? $_REQUEST['ipp'] : $this->default_ipp;
    }


    public function body($page = false, $ipp = false, $tpl = false)
    {
        if ($tpl) {
            $this->tpl = $tpl;
        }
        if ($ipp) {
            $this->items_per_page = $ipp;
        }
        $this->paginate($page, $ipp);
        if ($this->client) {
            load_js('modules/Utils/Paginator/js/paginator.js');
            $this->load_js_defaults();
        }
        if ($this->show_raw) {
            $this->display_pages();
        } else {
            $this->theme->assign('show_page_label', $this->show_page_label);
            $this->theme->assign('page_pages', $this->page_links);
            $this->theme->assign('page_previous', $this->previous_link);
            $this->theme->assign('page_next', $this->next_link);
            $this->theme->assign('page_all', $this->all_link);
            if ($tpl) {
                $this->theme->display($tpl);
            } else {
                $this->theme->display();
            }
        }
    }

    public function get_paginator_html($page = false, $ipp = false, $tpl = false)
    {
        if ($tpl) {
            $this->tpl = $tpl;
        }
        if ($ipp) {
            $this->items_per_page = $ipp;
        }
        $this->paginate($page, $ipp);
        if ($this->client) {
            load_js('modules/Utils/Paginator/js/paginator.js');
            $this->load_js_defaults();
        }
        if ($this->show_raw) {
            $this->display_pages();
        } else {
            $theme = $this->init_module('Base/Theme');
            $theme->assign('show_page_label', $this->show_page_label);
            $theme->assign('page_pages', $this->page_links);
            $theme->assign('page_previous', $this->previous_link);
            $theme->assign('page_next', $this->next_link);
            $theme->assign('page_all', $this->all_link);
            if ($tpl) {
                return $theme->get_html($tpl);
            } else {
                return $theme->get_html();
            }
        }
    }

    private function load_js_defaults()
    {
        $val_arr = array(
            'items_per_page' => $this->items_per_page,
            'items_total' => $this->items_total,
            'current_page' => $this->current_page,
            'num_pages' => $this->num_pages,
            'mid_range' => $this->mid_range,
            'low' => $this->low,
            'high' => $this->high,
            'limit' => $this->limit,
            'default_ipp' => $this->default_ipp,
            'callback' => $this->js_callback,
            'disable_previous' => $this->disable_previous,
            'disable_next' => $this->disable_next,
            'disable_all' => $this->disable_all
        );
        $val_arr = json_encode($val_arr);
        eval_js("init_page($val_arr);");
    }

    public function set_tpl($tpl)
    {
        $this->tpl = $tpl;
    }

    public function get_page_href_js($page, $ipp)
    {
        if (!$this->js_callback || !$this->client) return "href='javascript:void(0);'";
        return "href='javascript:void(0);' onclick='javascript:paginate(\"$page\",\"$ipp\");'";
    }

    public function paginate($page = false, $ipp = false)
    {
        $ipp = $ipp ? $ipp : isset($_REQUEST['ipp']) ? $_REQUEST['ipp'] : false;
        $page = $page ? $page : isset($_REQUEST['page']) ? $_REQUEST['page'] : false;
        if ($ipp == 'All') {
            $this->num_pages = ceil($this->items_total / $this->default_ipp);
            $this->items_per_page = $this->default_ipp;
        } else {
            if (!is_numeric($this->items_per_page) OR $this->items_per_page <= 0) $this->items_per_page = $this->default_ipp;
            $this->num_pages = ceil($this->items_total / $this->items_per_page);
        }
        $this->current_page = (int)$page; // must be numeric > 0
        if ($this->current_page < 1 Or !is_numeric($this->current_page)) $this->current_page = 1;
        if ($this->current_page > $this->num_pages) $this->current_page = $this->num_pages;
        $prev_page = $this->current_page - 1;
        $next_page = $this->current_page + 1;
        $this->return = '';
        $this->page_links = array();
        if ($this->num_pages > 10) {

            if (!$this->disable_previous) {
                if ($this->client)
                    $previous_href = $this->get_page_href_js($prev_page, $this->items_per_page);
                else
                    $previous_href = $this->create_href(array('page' => $prev_page, 'ipp' => $this->items_per_page));

                $previous_link = ($this->current_page != 1 And $this->items_total >= 10) ? "<a class=\"paginate extra_button\" $previous_href>« Previous</a> " : "<span class=\"inactive\" href=\"#\">« Previous</span> ";
                if ($this->show_raw)
                    $this->return .= $previous_link;
                else
                    $this->previous_link = $previous_link;
            }

            $this->start_range = $this->current_page - floor($this->mid_range / 2);
            $this->end_range = $this->current_page + floor($this->mid_range / 2);
            if ($this->start_range <= 0) {
                $this->end_range += abs($this->start_range) + 1;
                $this->start_range = 1;
            }
            if ($this->end_range > $this->num_pages) {
                $this->start_range -= $this->end_range - $this->num_pages;
                $this->end_range = $this->num_pages;
            }
            $this->range = range($this->start_range, $this->end_range);
            $dot = false;
            for ($i = 1; $i <= $this->num_pages; $i++) {
                if ($this->range[0] > 2 And $i == $this->range[0]) {
                    $this->return .= " ... ";
                    $dot = true;
                }
                // loop through all pages. if first, last, or in range, display
                if ($i == 1 Or $i == $this->num_pages Or in_array($i, $this->range)) {
                    if ($this->client)
                        $page_href = $this->get_page_href_js($i, $this->items_per_page);
                    else
                        $page_href = $this->create_href(array('page' => $i, 'ipp' => $this->items_per_page));
                    $page = $i;

                    $page_link = ($i == $this->current_page And $page != 'All') ? "<a title=\"Go to page $i of $this->num_pages\" class=\"current\" href=\"javascript:void(0);\">$page</a> " : "<a class=\"paginate\" title=\"Go to page $i of $this->num_pages\" $page_href>$page</a> ";
                    if ($this->show_raw)
                        $this->return .= $page_link;
                    else {
                        if ($i == $this->current_page And $page != 'All') {
                            $page_links = $this->page_links;
                            $page_links['current'] = $page_link;
                            $this->page_links = $page_links;
                        } else {
                            array_push($this->page_links, $dot ? "<span>...</span>" . $page_link : $page_link);
                            $dot = false;
                        }
                    }
                }
                if ($this->range[$this->mid_range - 1] < $this->num_pages - 1 And $i == $this->range[$this->mid_range - 1]) {
                    $this->return .= " ... ";
                    $dot = true;
                }
            }
            if (!$this->disable_next) {
                if ($this->client)
                    $next_href = $this->get_page_href_js($next_page, $this->items_per_page);
                else
                    $next_href = $this->create_href(array('page' => $next_page, 'ipp' => $this->items_per_page));
                $next_link = (($this->current_page != $this->num_pages And $this->items_total >= 10) And ($page != 'All')) ? "<a class=\"paginate extra_button\" $next_href>Next »</a>\n" : "<span class=\"inactive\" href=\"#\">» Next</span>\n";
                if ($this->show_raw)
                    $this->return .= $next_link;
                else
                    $this->next_link = $next_link;
            }
            if (!$this->disable_all) {
                if ($this->client)
                    $all_href = $this->get_page_href_js(1, 'All');
                else
                    $all_href = $this->create_href(array('page' => 1, 'ipp' => 'All'));
                $all_link = ($page == 'All') ? "<a class=\"current extra_button\" style=\"margin-left:10px\" href=\"#\">All</a> \n" : "<a class=\"paginate\" style=\"margin-left:10px\" $all_href>All</a> \n";
                if ($this->show_raw)
                    $this->return .= $all_link;
                else
                    $this->all_link = $all_link;
            }
        } else {
            for ($i = 1; $i <= $this->num_pages; $i++) {
                if ($this->client)
                    $page_href = $this->get_page_href_js($i, $this->items_per_page);
                else
                    $page_href = $this->create_href(array('page' => $i, 'ipp' => $this->items_per_page));
                $page = $i;

                $page_link = ($i == $this->current_page) ? "<a class=\"current\" href=\"javascript:void(0);\">$page</a> " : "<a class=\"paginate\" $page_href>$page</a> ";
                if ($this->show_raw)
                    $this->return .= $page_link;
                else
                    array_push($this->page_links, $page_link);
            }
            if (!$this->disable_all) {
                if ($this->client)
                    $all_href = $this->get_page_href_js(1, 'All');
                else
                    $all_href = $this->create_href(array('page' => 1, 'ipp' => 'All'));
                $all_link = "<a class=\"paginate extra_button\" $all_href>All</a> \n";
                if ($this->show_raw)
                    $this->return .= $all_link;
                else
                    $this->all_link = $all_link;
            }
        }
        $this->low = ($this->current_page - 1) * $this->items_per_page;
        $this->high = ($ipp == 'All') ? $this->items_total : ($this->current_page * $this->items_per_page) - 1;
        $this->limit = ($ipp == 'All') ? "" : " LIMIT $this->low,$this->items_per_page";
    }

    public function display_items_per_page($ipp_array = false, $js_callback = false, $ipp_label = 'Show ')
    {
        if (!$ipp_array) {
            $ipp_array = array($this->default_ipp, 3, 5, 10, 25, 50, 100);
            array_unique($ipp_array);
            sort($ipp_array);
        }
        if (!$this->disable_all)
            array_push($ipp_array, 'All');
        if (ModuleManager::is_installed('Libs/QuickForm') >= 0) {
            $option = array();
            foreach ($ipp_array as $ipp_opt) {
                $option[$ipp_opt] = $ipp_label . $ipp_opt;
            }
            $qf = $this->init_module('Libs/QuickForm');
            if ($this->client && $js_callback)
                $change_href = "javascript:" . $js_callback . "(1);";
            else
                $change_href = $qf->get_submit_form_js();
            $qf->addElement('select', 'ipp_menu', _M('Items:'), $option, array('id' => 'ipp_menu', 'onchange' => $change_href));
            if ($this->items_per_page) {
                $qf->setDefaults(array('ipp_menu' => $this->items_per_page));
            }
            if (!$this->show_raw)
                $qf->assign_theme('ipp_menu', $this->theme);
            return $qf;
        } else {
            $items = '';
            foreach ($ipp_array as $ipp_opt) $items .= ($ipp_opt == $this->items_per_page) ? "<option selected value=\"$ipp_opt\">$ipp_opt</option>\n" : "<option value=\"$ipp_opt\">$ipp_opt</option>\n";
            return "<span class=\"paginate\">Items per page:</span><select class=\"paginate\" onchange=\"window.location='$_SERVER[PHP_SELF]?page=1&ipp='+this[this.selectedIndex].value;return false\">$items</select>\n";
        }
    }

    public function display_jump_menu($ipp = false, $jump_label = 'Jump to p.')
    {
        $num_pages = ceil($this->items_total / (($ipp && $ipp != 'All') ? $ipp : $this->default_ipp));
        if (ModuleManager::is_installed('Libs/QuickForm') >= 0) {
            $option = array();
            for ($i = 1; $i <= $num_pages; $i++) {
                $option[$i] = $jump_label . $i;
            }
            $qf = $this->init_module('Libs/QuickForm');
            if ($this->client)
                $change_href = "javascript:jump_page();";
            else
                $change_href = $qf->get_submit_form_js();
            $qf->addElement('select', 'jump_menu', _M('Jump to:'), $option, array('id' => 'jump_menu', 'onchange' => $change_href));
            $qf->addElement('hidden', 'ipp', ($ipp ? $ipp : $this->default_ipp));
            if (!$this->show_raw)
                $qf->assign_theme('jump_menu', $this->theme);
            return $qf;
        } else {
            $option = '';
            for ($i = 1; $i <= $this->num_pages; $i++) {
                $option .= ($i == $this->current_page) ? "<option value=\"$i\" selected>$i</option>\n" : "<option value=\"$i\">$i</option>\n";
            }
            return "<span class=\"paginate\">Page:</span><select class=\"paginate\" onchange=\"window.location='$_SERVER[PHP_SELF]?page='+this[this.selectedIndex].value+'&ipp=$this->items_per_page';return false\">$option</select>\n";
        }

        return false;
    }

    public function display_pages()
    {
        print $this->return;
    }

    public function set_client($js_callback)
    {
        $this->client = true;
        $this->js_callback = $js_callback;
    }
}
